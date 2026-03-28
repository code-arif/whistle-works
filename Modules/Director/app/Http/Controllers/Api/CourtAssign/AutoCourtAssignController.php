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

        // 5. Group slots by time window (date + start_time) and build a PER-DATE index map.
        //
        //    WHY per-date and not global?
        //    Multiple locations (e.g. Dhaka + Rajshahi) can have different time windows
        //    on the same date. A global sequential index inserts "foreign" location windows
        //    between two Dhaka windows, inflating the distance and letting the rest rule
        //    silently pass a back-to-back assignment on the same date.
        //
        //    Per-date index treats each date as its own sequence:
        //      2026-04-10 → [05:00 → pos 0,  07:00 → pos 1]
        //      2026-04-11 → [08:00 → pos 0,  10:00 → pos 1]
        //
        $slotsByTimeWindow = $availableSlots->groupBy(
            fn($slot) => $slot->game_date . '|' . $slot->start_time
        );

        // Build: timeKey → ['date' => '2026-04-10', 'position' => 1]
        $windowMeta    = [];
        $datePositions = [];

        foreach ($slotsByTimeWindow->keys() as $timeKey) {
            [$date] = explode('|', $timeKey);
            if (!isset($datePositions[$date])) {
                $datePositions[$date] = 0;
            }
            $windowMeta[$timeKey] = [
                'date'     => $date,
                'position' => $datePositions[$date]++,
            ];
        }

        // 6. Build in-memory referee state
        //    assignment_count      → fair distribution (Rule 3)
        //    last_played_date      → date of last assignment  (Rule 1)
        //    last_played_position  → per-date position of last assignment (Rule 1)
        //    used_courts           → courts used in current rotation cycle (Rule 2)
        $refereeStats = [];
        foreach ($checkedInReferees as $referee) {
            $refereeStats[$referee->id] = [
                'assignment_count'     => 0,
                'last_played_date'     => null,
                'last_played_position' => null,
                'used_courts'          => [],
            ];
        }

        // 7. Main assignment loop
        $assignmentsCreated = 0;
        $slotsAssigned      = 0;
        $restSkips          = 0;

        foreach ($slotsByTimeWindow as $timeKey => $windowSlots) {

            $firstSlot      = $windowSlots->first();
            $maxPerSlot     = $firstSlot->schedule->max_referees_per_slot ?? 3;
            $currentMeta    = $windowMeta[$timeKey]; // ['date', 'position']

            // 7a. Build eligible referee list — strictly enforce rest rule (Rule 1).
            $sortedRefereeIds   = $this->getSortedRefereeIds($refereeStats);
            $eligibleRefereeIds = [];

            foreach ($sortedRefereeIds as $refereeId) {
                if ($this->needsRest(
                    $refereeStats[$refereeId]['last_played_date'],
                    $refereeStats[$refereeId]['last_played_position'],
                    $currentMeta
                )) {
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
                    $refereeStats[$refereeId]['last_played_date']     = $currentMeta['date'];
                    $refereeStats[$refereeId]['last_played_position']  = $currentMeta['position'];

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
     * Rule 1 — Minimum 1 window rest, scoped per date.
     *
     * A referee who played at position P on date D is blocked at position P+1 on the SAME date.
     * They become eligible again at P+2 or later (on date D), or on any other date.
     *
     * Per-date scoping is critical: without it, a window from another location (e.g. Rajshahi
     * at 06:00) inserted between two same-date Dhaka windows (05:00 and 07:00) would inflate
     * the global distance to 2, incorrectly allowing a back-to-back assignment on Dhaka.
     */
    private function needsRest(?string $lastDate, ?int $lastPosition, array $currentMeta): bool
    {
        if ($lastDate === null || $lastPosition === null) {
            return false; // Never played → no rest needed
        }

        // Different date → no rest carry-over (new day resets)
        if ($lastDate !== $currentMeta['date']) {
            return false;
        }

        // Block only the immediately next position on the same date (distance == 1)
        return ($currentMeta['position'] - $lastPosition) === 1;
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
