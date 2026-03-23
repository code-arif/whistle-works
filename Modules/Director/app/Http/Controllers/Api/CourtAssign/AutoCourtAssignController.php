<?php

namespace Modules\Director\Http\Controllers\Api\CourtAssign;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Log;
use Modules\Director\Models\Camp;
use Modules\Director\Models\GameSlot;
use Modules\Director\Models\GameSlotAssignment;

class AutoCourtAssignController extends Controller
{
    use ApiResponse;

    /**
     * Auto-assign referees to all available slots.
     *
     * Rules:
     *  1. Minimum 1 window rest → after playing in window index N, referee is blocked from
     *                              window N+1. They can play again at N+2 or later.
     *  2. Court rotation       → referee is NOT assigned to a previously used court unless all courts
     *                            have been exhausted in the current cycle (then cycle resets).
     *  3. Fair distribution    → least-assigned referees get priority.
     *  4. Randomness           → among equally-loaded referees the order is shuffled each round.
     */
    public function autoAssignReferees($campId)
    {
        $user = auth('api')->user();

        $camp = Camp::where('id', $campId)
            ->where('director_id', $user->id)
            ->first();

        if (!$camp) {
            return $this->error('Camp not found.', null, 404);
        }

        // 1. Fetch all available (non-blocked, non-crew) slots
        $availableSlots = GameSlot::with('schedule')
            ->whereHas('schedule', fn($q) => $q->where('camp_id', $campId))
            ->where('is_block', false)
            ->whereDoesntHave('slotAssignments', fn($q) => $q->where('assignment_type', 'crew'))
            ->orderBy('game_date')
            ->orderBy('start_time')
            ->orderBy('court_number')
            ->get();

        if ($availableSlots->isEmpty()) {
            return $this->error('No available slots found.', null, 404);
        }

        // 2. Fetch checked-in referees
        $checkedInReferees = User::whereIn('id', function ($query) use ($campId) {
            $query->select('referee_id')
                ->from('camp_referee_checkins')
                ->where('camp_id', $campId);
        })->get();

        if ($checkedInReferees->isEmpty()) {
            return $this->error('No checked-in referees available.', null, 404);
        }

        // 3. Clear previous auto-assignments only
        $slotIds = $availableSlots->pluck('id');

        GameSlotAssignment::whereIn('game_slot_id', $slotIds)
            ->where('assignment_type', 'individual')
            ->where('is_auto_assigned', true)
            ->delete();

        GameSlot::whereIn('id', $slotIds)
            ->update(['status' => 'available']);

        // 4. Determine total distinct courts for cycle-reset logic (Rule 2)
        $totalCourts = $availableSlots->pluck('court_number')->unique()->count();

        // 5. Group slots by time window (date + start_time) and build an index map
        //    timeWindowIndex: ['2024-04-03|07:00:00' => 0, '2024-04-03|08:00:00' => 1, ...]
        $slotsByTimeWindow = $availableSlots->groupBy(
            fn($slot) => $slot->game_date . '|' . $slot->start_time
        );

        $timeWindowIndex = array_flip(array_keys($slotsByTimeWindow->toArray()));

        // 6. Build in-memory referee state
        //    assignment_count         → fair distribution (Rule 3)
        //    last_played_window_index → sequential window index for rest rule (Rule 1)
        //    used_courts              → courts used in current rotation cycle (Rule 2)
        $refereeStats = [];
        foreach ($checkedInReferees as $referee) {
            $refereeStats[$referee->id] = [
                'assignment_count'          => 0,
                'last_played_window_index'  => null,   // ← index-based, not time-based
                'used_courts'               => [],
            ];
        }

        // 7. Main assignment loop
        $assignmentsCreated = 0;
        $slotsAssigned      = 0;
        $restSkips          = 0;

        foreach ($slotsByTimeWindow as $timeKey => $windowSlots) {

            $firstSlot       = $windowSlots->first();
            $maxPerSlot      = $firstSlot->schedule->max_referees_per_slot ?? 3;
            $currentWinIndex = $timeWindowIndex[$timeKey];

            // 7a. Build eligible referee list — strictly enforce rest rule (Rule 1).
            //     Resting referees are NEVER used as fallback.
            //     If eligible referees are insufficient, slots remain partially/fully empty.
            //     The director can fill remaining gaps via manual assignment.
            $sortedRefereeIds   = $this->getSortedRefereeIds($refereeStats);
            $eligibleRefereeIds = [];

            foreach ($sortedRefereeIds as $refereeId) {
                if ($this->needsRest($refereeStats[$refereeId]['last_played_window_index'], $currentWinIndex)) {
                    $restSkips++;
                    continue;
                }
                $eligibleRefereeIds[] = $refereeId;
            }

            // 7b. Track which referees have already been assigned in THIS window
            //     (one court per referee per time window)
            $assignedInWindow = [];

            foreach ($windowSlots as $slot) {

                // Remove referees already used elsewhere in this same time window
                $availableForCourt = array_values(array_filter(
                    $eligibleRefereeIds,
                    fn($id) => !in_array($id, $assignedInWindow)
                ));

                // ── Rule 2: Court-rotation priority ──────────────────────────
                // Split into two buckets:
                //   $freshForCourt   → haven't been on this court in current cycle (preferred)
                //   $repeatForCourt  → already used this court (fallback only)
                // Within each bucket the relative order from getSortedRefereeIds is preserved,
                // so fair-distribution (Rule 3) still applies inside each bucket.
                $freshForCourt  = [];
                $repeatForCourt = [];

                foreach ($availableForCourt as $refId) {
                    if (in_array($slot->court_number, $refereeStats[$refId]['used_courts'])) {
                        $repeatForCourt[] = $refId;
                    } else {
                        $freshForCourt[] = $refId;
                    }
                }

                // Preferred referees first, fallback last
                $sortedForCourt = array_merge($freshForCourt, $repeatForCourt);

                // 7c. Fill this slot up to maxPerSlot
                $assignedToThisSlot = 0;

                foreach ($sortedForCourt as $refereeId) {
                    if ($assignedToThisSlot >= $maxPerSlot) {
                        break;
                    }

                    // Persist assignment
                    GameSlotAssignment::create([
                        'game_slot_id'    => $slot->id,
                        'assignable_type' => User::class,
                        'assignable_id'   => $refereeId,
                        'assignment_type' => 'individual',
                        'is_auto_assigned' => true,
                        'assigned_at'     => now(),
                    ]);

                    // Update in-memory state
                    $refereeStats[$refereeId]['assignment_count']++;
                    $refereeStats[$refereeId]['last_played_window_index'] = $currentWinIndex; // ← index

                    // ── Rule 2: Record court usage ────────────────────────────
                    if (!in_array($slot->court_number, $refereeStats[$refereeId]['used_courts'])) {
                        $refereeStats[$refereeId]['used_courts'][] = $slot->court_number;
                    }

                    // Reset cycle when referee has now visited every distinct court
                    if (count($refereeStats[$refereeId]['used_courts']) >= $totalCourts) {
                        $refereeStats[$refereeId]['used_courts'] = [];
                    }

                    $assignedInWindow[]   = $refereeId;
                    $assignedToThisSlot++;
                    $assignmentsCreated++;
                }

                if ($assignedToThisSlot > 0) {
                    $slotsAssigned++;
                    $slot->update(['status' => 'assigned']);
                }
            }
        }

        // 8. Build distribution stats
        $counts = array_column($refereeStats, 'assignment_count');
        $min    = $counts ? min($counts) : 0;
        $max    = $counts ? max($counts) : 0;
        $avg    = count($counts) ? round(array_sum($counts) / count($counts), 2) : 0;

        Log::info('Auto-assignment completed', [
            'director_id'         => $user->id,
            'camp_id'             => $campId,
            'assignments_created' => $assignmentsCreated,
            'slots_assigned'      => $slotsAssigned,
        ]);

        return $this->success(
            'Auto-assignment completed with fair distribution and rest rules applied.',
            [
                'total_slots'               => $availableSlots->count(),
                'slots_assigned'            => $slotsAssigned,
                'total_referee_assignments' => $assignmentsCreated,
                'total_checked_in_referees' => $checkedInReferees->count(),
                'rest_periods_enforced'     => $restSkips,
                'distribution'             => [
                    'min_per_referee' => $min,
                    'max_per_referee' => $max,
                    'avg_per_referee' => $avg,
                    'variance'        => $max - $min,
                ],
                'average_per_slot' => $slotsAssigned
                    ? round($assignmentsCreated / $slotsAssigned, 2)
                    : 0,
            ],
            200
        );
    }

    /**
     * Rule 1 — Minimum 1 window rest (index-based, duration-independent).
     *
     * A referee who played at window index N is blocked at window N+1.
     * They become eligible again at N+2 or later.
     *
     * This is intentionally NOT time/duration based — it purely counts
     * time-window slots, so it works regardless of game length or gap size.
     */
    private function needsRest(?int $lastPlayedWindowIndex, int $currentWindowIndex): bool
    {
        if ($lastPlayedWindowIndex === null) {
            return false; // Never played → no rest needed
        }

        // Block only the immediately next window (distance == 1)
        return ($currentWindowIndex - $lastPlayedWindowIndex) === 1;
    }

    /**
     * Return referee IDs sorted by assignment count (ascending).
     * Within the same count, shuffle for randomness (Rule 4).
     */
    private function getSortedRefereeIds(array $refereeStats): array
    {
        $grouped = [];
        foreach ($refereeStats as $id => $stats) {
            $grouped[$stats['assignment_count']][] = $id;
        }

        ksort($grouped);

        $sorted = [];
        foreach ($grouped as $ids) {
            shuffle($ids);
            foreach ($ids as $id) {
                $sorted[] = $id;
            }
        }

        return $sorted;
    }
}
