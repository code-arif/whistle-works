<?php

namespace Modules\Director\Http\Controllers\Api\CourtAssign;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Carbon\Carbon;
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
     *  1. Back-to-back rest    → after playing slot X, referee sits out the immediately next time slot.
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

        // 5. Build in-memory referee state
        //    assignment_count → fair distribution (Rule 3)
        //    last_played_key  → "date|start_time" for rest rule (Rule 1)
        //    used_courts      → courts used in current rotation cycle (Rule 2)
        $refereeStats = [];
        foreach ($checkedInReferees as $referee) {
            $refereeStats[$referee->id] = [
                'assignment_count' => 0,
                'last_played_key'  => null,
                'used_courts'      => [],   // ← NEW: tracks court rotation
            ];
        }

        // 6. Group slots by time window (date + start_time)
        $slotsByTimeWindow = $availableSlots->groupBy(
            fn($slot) => $slot->game_date . '|' . $slot->start_time
        );

        // 7. Main assignment loop
        $assignmentsCreated = 0;
        $slotsAssigned      = 0;
        $restSkips          = 0;

        foreach ($slotsByTimeWindow as $timeKey => $windowSlots) {

            $firstSlot  = $windowSlots->first();
            $maxPerSlot = $firstSlot->schedule->max_referees_per_slot ?? 3;

            // 7a. Build eligible referee list for this time window
            //     (sorted by assignment count, shuffled within ties)
            $sortedRefereeIds  = $this->getSortedRefereeIds($refereeStats);
            $eligibleRefereeIds = [];

            foreach ($sortedRefereeIds as $refereeId) {
                if ($this->needsRestInMemory($refereeStats[$refereeId]['last_played_key'], $firstSlot)) {
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
                        'is_auto_assigned'=> true,
                        'assigned_at'     => now(),
                    ]);

                    // Update in-memory state
                    $refereeStats[$refereeId]['assignment_count']++;
                    $refereeStats[$refereeId]['last_played_key'] = $timeKey;

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
     * Check rest rule using in-memory data (no DB call needed).
     * Returns true if referee must sit out this time window.
     *
     * "last_played_key" format: "Y-m-d|H:i:s"
     * Blocked only when the last game ends exactly when (or after) the current slot starts.
     */
    private function needsRestInMemory(?string $lastPlayedKey, GameSlot $currentSlot): bool
    {
        if ($lastPlayedKey === null) {
            return false;
        }

        [$lastDate, $lastStartTime] = explode('|', $lastPlayedKey);

        if ($lastDate !== $currentSlot->game_date) {
            return false;
        }

        $gameDuration = $currentSlot->schedule->game_duration;

        $lastEnd      = Carbon::parse($lastStartTime)->addMinutes($gameDuration);
        $currentStart = Carbon::parse($currentSlot->start_time);

        return $lastEnd->gte($currentStart);
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
