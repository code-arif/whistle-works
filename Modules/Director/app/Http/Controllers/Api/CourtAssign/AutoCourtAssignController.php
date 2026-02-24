<?php

namespace Modules\Director\Http\Controllers\Api\CourtAssign;

use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Modules\Director\Models\Camp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Modules\Director\Models\GameSlot;
use Modules\Director\Models\GameSlotAssignment;

class AutoCourtAssignController extends Controller
{
    use ApiResponse;

    /**
     * Auto-assign available referees to all slots
     * Rules:
     * 1. Minimum 1 game rest between games (no back-to-back)
     * 2. Court rotation: Referees skip the NEXT occurrence of their last court
     * 3. Fair distribution of games among referees
     */
    // public function autoAssignReferees($campId)
    // {
    //     $user = auth('api')->user();

    //     $camp = Camp::where('id', $campId)
    //         ->where('director_id', $user->id)
    //         ->first();

    //     if (!$camp) {
    //         return $this->error('Camp not found.', null, 404);
    //     }

    //     // Get available slots (not blocked, no crew assignments)
    //     $availableSlots = GameSlot::with('schedule')
    //         ->whereHas('schedule', function ($q) use ($campId) {
    //             $q->where('camp_id', $campId);
    //         })
    //         ->where('is_block', false)
    //         ->whereDoesntHave('slotAssignments', function ($q) {
    //             $q->where('assignment_type', 'crew');
    //         })
    //         ->orderBy('game_date')
    //         ->orderBy('start_time')
    //         ->orderBy('court_number')
    //         ->get();

    //     if ($availableSlots->isEmpty()) {
    //         return $this->error('No available slots found.', null, 404);
    //     }

    //     // Get checked-in referees
    //     $checkedInReferees = User::whereIn('id', function ($query) use ($campId) {
    //         $query->select('referee_id')
    //             ->from('camp_referee_checkins')
    //             ->where('camp_id', $campId);
    //     })->get();

    //     if ($checkedInReferees->isEmpty()) {
    //         return $this->error('No checked-in referees available.', null, 404);
    //     }

    //     // Clear previous auto-assignments
    //     DB::transaction(function () use ($availableSlots) {
    //         GameSlotAssignment::whereIn('game_slot_id', $availableSlots->pluck('id'))
    //             ->where('assignment_type', 'individual')
    //             ->where('is_auto_assigned', true)
    //             ->delete();
    //     });

    //     // Track assignment counts and court rotation for each referee
    //     $refereeStats = [];
    //     foreach ($checkedInReferees as $referee) {
    //         $refereeStats[$referee->id] = [
    //             'referee' => $referee,
    //             'assignment_count' => 0,
    //             'last_assigned_court' => null, // Track last court assigned
    //             'skip_next_court' => null, // Court to skip on next assignment
    //             'court_history' => [], // Full court assignment history
    //         ];
    //     }

    //     $assignmentsCreated = 0;
    //     $slotsAssigned = 0;
    //     $conflictCount = 0;
    //     $restNeededCount = 0;
    //     $courtRotationSkips = 0;

    //     foreach ($availableSlots as $slot) {
    //         $assignedToThisSlot = 0;
    //         $maxPerSlot = $slot->schedule->max_referees_per_slot ?? 3;

    //         // Sort referees by assignment count (least assigned first) for fair distribution
    //         uasort($refereeStats, function ($a, $b) {
    //             return $a['assignment_count'] <=> $b['assignment_count'];
    //         });

    //         foreach ($refereeStats as $refereeId => $stats) {
    //             if ($assignedToThisSlot >= $maxPerSlot) {
    //                 break;
    //             }

    //             $referee = $stats['referee'];

    //             // Check if already assigned to this slot
    //             $alreadyAssigned = GameSlotAssignment::where('game_slot_id', $slot->id)
    //                 ->where('assignable_type', User::class)
    //                 ->where('assignable_id', $referee->id)
    //                 ->exists();

    //             if ($alreadyAssigned) {
    //                 continue;
    //             }

    //             // RULE 1: Check if referee needs rest (played in previous slot)
    //             if (GameSlotAssignment::needsRest($referee->id, User::class, $slot)) {
    //                 $restNeededCount++;
    //                 continue;
    //             }

    //             // Check for time conflicts (overlapping games)
    //             $hasConflict = GameSlotAssignment::hasTimeConflict(
    //                 $referee->id,
    //                 User::class,
    //                 $slot
    //             );

    //             if ($hasConflict) {
    //                 $conflictCount++;
    //                 continue;
    //             }

    //             // RULE 2 (FIXED): Court rotation logic
    //             // If this is the court they should skip, look for alternatives
    //             if ($stats['skip_next_court'] === $slot->court_name) {
    //                 // Check if there are other available referees who can take this court
    //                 $hasAlternative = false;
    //                 foreach ($refereeStats as $altRefId => $altStats) {
    //                     if (
    //                         $altRefId != $refereeId &&
    //                         $altStats['skip_next_court'] !== $slot->court_name &&
    //                         $altStats['assignment_count'] <= $stats['assignment_count'] &&
    //                         !GameSlotAssignment::where('game_slot_id', $slot->id)
    //                             ->where('assignable_type', User::class)
    //                             ->where('assignable_id', $altRefId)
    //                             ->exists()
    //                     ) {
    //                         // Check if alternative doesn't need rest and has no conflicts
    //                         if (
    //                             !GameSlotAssignment::needsRest($altRefId, User::class, $slot) &&
    //                             !GameSlotAssignment::hasTimeConflict($altRefId, User::class, $slot)
    //                         ) {
    //                             $hasAlternative = true;
    //                             break;
    //                         }
    //                     }
    //                 }

    //                 // If alternatives exist, skip this referee for this court
    //                 if ($hasAlternative) {
    //                     $courtRotationSkips++;
    //                     continue;
    //                 }
    //                 // If no alternatives, clear the skip flag and allow assignment
    //             }

    //             // SUCCESS: Assign referee
    //             GameSlotAssignment::create([
    //                 'game_slot_id'     => $slot->id,
    //                 'assignable_type'  => User::class,
    //                 'assignable_id'    => $referee->id,
    //                 'assignment_type'  => 'individual',
    //                 'is_auto_assigned' => true,
    //                 'assigned_at'      => now(),
    //             ]);

    //             // Update referee stats with court rotation logic
    //             $refereeStats[$refereeId]['assignment_count']++;
    //             $refereeStats[$refereeId]['court_history'][] = $slot->court_name;

    //             // Set skip flag: Skip this court on the NEXT assignment
    //             $refereeStats[$refereeId]['last_assigned_court'] = $slot->court_name;
    //             $refereeStats[$refereeId]['skip_next_court'] = $slot->court_name;

    //             $assignmentsCreated++;
    //             $assignedToThisSlot++;

    //             // Clear skip flag after one skip (they can play on this court again after skipping once)
    //             // This is done in the next iteration when they're assigned to a different court
    //             if ($stats['last_assigned_court'] !== null && $stats['last_assigned_court'] !== $slot->court_name) {
    //                 $refereeStats[$refereeId]['skip_next_court'] = $slot->court_name;
    //             }
    //         }

    //         if ($assignedToThisSlot > 0) {
    //             $slotsAssigned++;
    //             $slot->update(['status' => 'assigned']);
    //         }
    //     }

    //     // Calculate distribution fairness
    //     $assignmentCounts = array_column($refereeStats, 'assignment_count');
    //     $minAssignments = min($assignmentCounts) ?: 0;
    //     $maxAssignments = max($assignmentCounts) ?: 0;
    //     $avgAssignments = $checkedInReferees->count() > 0
    //         ? round(array_sum($assignmentCounts) / $checkedInReferees->count(), 2)
    //         : 0;

    //     $stats = [
    //         'total_slots'               => $availableSlots->count(),
    //         'slots_assigned'            => $slotsAssigned,
    //         'total_referee_assignments' => $assignmentsCreated,
    //         'total_checked_in_referees' => $checkedInReferees->count(),
    //         'time_conflicts_avoided'    => $conflictCount,
    //         'rest_periods_enforced'     => $restNeededCount,
    //         'court_rotations_applied'   => $courtRotationSkips, // UPDATED
    //         'distribution' => [
    //             'min_assignments_per_referee' => $minAssignments,
    //             'max_assignments_per_referee' => $maxAssignments,
    //             'avg_assignments_per_referee' => $avgAssignments,
    //             'variance' => $maxAssignments - $minAssignments,
    //         ],
    //         'average_per_slot' => $slotsAssigned > 0 ? round($assignmentsCreated / $slotsAssigned, 2) : 0,
    //     ];

    //     Log::info('Auto-assignment completed', [
    //         'director_id' => $user->id,
    //         'camp_id' => $campId,
    //         'assignments_created' => $assignmentsCreated,
    //         'slots_assigned' => $slotsAssigned,
    //         'court_rotations' => $courtRotationSkips,
    //     ]);

    //     return $this->success(
    //         'Auto-assignment completed with fair distribution and court rotation applied.',
    //         $stats,
    //         200
    //     );
    // }


    /**
     * Auto-assign available referees to all slots.
     *
     * Rules:
     *  1. Same time slot → a referee can only be on ONE court (hasTimeConflict across all courts).
     *  2. Back-to-back rest → after playing a slot, referee skips the IMMEDIATELY next time slot.
     *  3. Fair distribution  → least-assigned referees get priority.
     *  4. Randomness        → among equally-loaded referees the order is shuffled.
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

        // ── 1. Collect slots ──────────────────────────────────────────────────
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

        // ── 2. Collect checked-in referees ────────────────────────────────────
        $checkedInReferees = User::whereIn('id', function ($query) use ($campId) {
            $query->select('referee_id')
                ->from('camp_referee_checkins')
                ->where('camp_id', $campId);
        })->get();

        if ($checkedInReferees->isEmpty()) {
            return $this->error('No checked-in referees available.', null, 404);
        }

        // ── 3. Clear previous auto-assignments ────────────────────────────────
        GameSlotAssignment::whereIn('game_slot_id', $availableSlots->pluck('id'))
            ->where('assignment_type', 'individual')
            ->where('is_auto_assigned', true)
            ->delete();

        // Reset slot statuses that were auto-assigned
        GameSlot::whereIn('id', $availableSlots->pluck('id'))
            ->where('status', 'assigned')
            ->update(['status' => 'available']);

        // ── 4. Build referee tracking state ───────────────────────────────────
        // assignment_count  → for fair distribution
        // last_slot_key     → "date|start_time" of the last slot played (for rest rule)
        $refereeStats = [];
        foreach ($checkedInReferees as $referee) {
            $refereeStats[$referee->id] = [
                'model'            => $referee,
                'assignment_count' => 0,
                'last_slot_key'    => null, // date|start_time of last played slot
            ];
        }

        // ── 5. Pre-group slots by (date, start_time) for the "same time" check ─
        // We process slots in chronological order. Slots sharing the same
        // (date, start_time) belong to the same "time window".
        $slotsByTimeWindow = $availableSlots->groupBy(
            fn($slot) => $slot->game_date . '|' . $slot->start_time
        );

        // ── 6. Main assignment loop ───────────────────────────────────────────
        $assignmentsCreated  = 0;
        $slotsAssigned       = 0;
        $conflictSkips       = 0;
        $restSkips           = 0;

        foreach ($slotsByTimeWindow as $timeKey => [$date, $startTime] = explode('|', $timeKey) + [null, null]) {

            // Re-fetch the window slots (already ordered by court_number)
            $windowSlots = $slotsByTimeWindow[$timeKey];

            // Track which referees were already assigned in THIS time window
            // to prevent same-time multi-court assignment within the loop itself
            $assignedInThisWindow = []; // refereeId → true

            foreach ($windowSlots as $slot) {
                $assignedToThisSlot = 0;
                $maxPerSlot         = $slot->schedule->max_referees_per_slot ?? 3;

                // Sort referees: least assigned first, then shuffle within same count
                $sortedReferees = $this->getSortedReferees($refereeStats);

                foreach ($sortedReferees as $refereeId => $stats) {
                    if ($assignedToThisSlot >= $maxPerSlot) {
                        break;
                    }

                    // Skip if this referee was already used in this time window
                    // (handles the case where assignment happened earlier in this loop iteration)
                    if (isset($assignedInThisWindow[$refereeId])) {
                        $conflictSkips++;
                        continue;
                    }

                    // DB-level time conflict check (covers manual assignments too)
                    if (GameSlotAssignment::hasNewTimeConflict($refereeId, User::class, $slot)) {
                        $conflictSkips++;
                        continue;
                    }

                    // Back-to-back rest rule
                    if (GameSlotAssignment::needsRest($refereeId, User::class, $slot)) {
                        $restSkips++;
                        continue;
                    }

                    // ── Assign ──────────────────────────────────────────────
                    GameSlotAssignment::create([
                        'game_slot_id'     => $slot->id,
                        'assignable_type'  => User::class,
                        'assignable_id'    => $refereeId,
                        'assignment_type'  => 'individual',
                        'is_auto_assigned' => true,
                        'assigned_at'      => now(),
                    ]);

                    // Update in-memory stats
                    $refereeStats[$refereeId]['assignment_count']++;
                    $refereeStats[$refereeId]['last_slot_key'] = $slot->game_date . '|' . $slot->start_time;
                    $assignedInThisWindow[$refereeId]          = true;

                    $assignedToThisSlot++;
                    $assignmentsCreated++;
                }

                if ($assignedToThisSlot > 0) {
                    $slotsAssigned++;
                    $slot->update(['status' => 'assigned']);
                }
            }
        }

        // ── 7. Stats ─────────────────────────────────────────────────────────
        $counts = array_column($refereeStats, 'assignment_count');
        $min    = $counts ? min($counts) : 0;
        $max    = $counts ? max($counts) : 0;
        $avg    = $checkedInReferees->count()
            ? round(array_sum($counts) / $checkedInReferees->count(), 2)
            : 0;

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
                'time_conflicts_avoided'    => $conflictSkips,
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
     * Return referees sorted by assignment count (ascending).
     * Referees with the same count are shuffled randomly for fairness.
     */
    private function getSortedReferees(array $refereeStats): array
    {
        // Group by assignment_count
        $grouped = [];
        foreach ($refereeStats as $id => $stats) {
            $grouped[$stats['assignment_count']][$id] = $stats;
        }

        ksort($grouped); // lowest count first

        $sorted = [];
        foreach ($grouped as $countGroup) {
            // Shuffle within same-count group for randomness
            $ids = array_keys($countGroup);
            shuffle($ids);
            foreach ($ids as $id) {
                $sorted[$id] = $countGroup[$id];
            }
        }

        return $sorted;
    }
}
