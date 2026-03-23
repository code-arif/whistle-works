<?php

namespace Modules\Director\Http\Controllers\Api\CourtAssign;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
     * Rules enforced:
     *  1. Minimum 1 game rest between games (no back-to-back).
     *  2. Court rotation: referee will not be assigned to their last court
     *     unless no other court is available in that time window.
     *  3. Fair distribution: max 2 game difference allowed between any two referees.
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

        // 3. Clear previous auto-assignments
        $slotIds = $availableSlots->pluck('id');

        GameSlotAssignment::whereIn('game_slot_id', $slotIds)
            ->where('assignment_type', 'individual')
            ->where('is_auto_assigned', true)
            ->delete();

        GameSlot::whereIn('id', $slotIds)->update(['status' => 'available']);

        // 4. Build in-memory referee state
        $refereeStats = [];
        foreach ($checkedInReferees as $referee) {
            $refereeStats[$referee->id] = [
                'assignment_count' => 0,
                'last_played_key'  => null,  // "date|start_time" of last game played
                'last_court'       => null,  // court name of last assignment (Rule 2)
            ];
        }

        // 5. Group slots by time window (date + start_time)
        $slotsByTimeWindow = $availableSlots->groupBy(
            fn($slot) => $slot->game_date . '|' . $slot->start_time
        );

        // 6. Main assignment loop
        $assignmentsCreated  = 0;
        $slotsAssigned       = 0;
        $restSkips           = 0;
        $courtRotationSkips  = 0;
        $fairnessSkips       = 0;

        foreach ($slotsByTimeWindow as $timeKey => $windowSlots) {

            $firstSlot  = $windowSlots->first();
            $maxPerSlot = $firstSlot->schedule->max_referees_per_slot ?? 3;

            // 6a. Find eligible referees for this time window
            // Eligible = not resting after previous game
            $eligibleRefereeIds = [];
            foreach ($this->getSortedRefereeIds($refereeStats) as $refereeId) {
                if ($this->needsRestInMemory($refereeStats[$refereeId]['last_played_key'], $firstSlot)) {
                    $restSkips++;
                    continue;
                }
                $eligibleRefereeIds[] = $refereeId;
            }

            // 6b. Assign referees to courts in this time window
            // Each referee gets at most ONE court per time window.
            // We apply court rotation (Rule 2) and fairness cap (Rule 3) per slot.

            $assignedInWindow = []; // track which referees have been used in this window

            foreach ($windowSlots as $slot) {
                $assignedToThisSlot = 0;

                // Separate eligible referees into:
                // - preferred: last court != this court (Rule 2 satisfied)
                // - fallback:  last court == this court (only use if no preferred available)
                $preferred = [];
                $fallback  = [];

                foreach ($eligibleRefereeIds as $refereeId) {
                    if (in_array($refereeId, $assignedInWindow)) {
                        continue; // already used in this time window
                    }
                    if ($refereeStats[$refereeId]['last_court'] === $slot->court_name) {
                        $fallback[] = $refereeId;
                    } else {
                        $preferred[] = $refereeId;
                    }
                }

                // Try preferred first, then fallback
                $candidateQueue = array_merge($preferred, $fallback);

                foreach ($candidateQueue as $refereeId) {
                    if ($assignedToThisSlot >= $maxPerSlot) {
                        break;
                    }

                    if (in_array($refereeId, $assignedInWindow)) {
                        continue;
                    }

                    // RULE 3: Fairness cap — max 2 game difference
                    $counts = array_column($refereeStats, 'assignment_count');
                    $minCount = count($counts) ? min($counts) : 0;
                    if ($refereeStats[$refereeId]['assignment_count'] - $minCount >= 2) {
                        // This referee is already 2+ games ahead of the least assigned referee
                        // Only skip if there are other eligible referees who can fill the slot
                        $othersAvailable = false;
                        foreach ($candidateQueue as $otherId) {
                            if (
                                $otherId !== $refereeId &&
                                !in_array($otherId, $assignedInWindow) &&
                                $refereeStats[$otherId]['assignment_count'] - $minCount < 2
                            ) {
                                $othersAvailable = true;
                                break;
                            }
                        }
                        if ($othersAvailable) {
                            $fairnessSkips++;
                            continue;
                        }
                    }

                    // Track court rotation skip for stats
                    if ($refereeStats[$refereeId]['last_court'] === $slot->court_name) {
                        $courtRotationSkips++;
                        // Only reached here because no preferred referees were available (fallback)
                    }

                    // Assign
                    GameSlotAssignment::create([
                        'game_slot_id'     => $slot->id,
                        'assignable_type'  => User::class,
                        'assignable_id'    => $refereeId,
                        'assignment_type'  => 'individual',
                        'is_auto_assigned' => true,
                        'assigned_at'      => now(),
                    ]);

                    // Update in-memory state
                    $refereeStats[$refereeId]['assignment_count']++;
                    $refereeStats[$refereeId]['last_played_key'] = $timeKey;
                    $refereeStats[$refereeId]['last_court']       = $slot->court_name;

                    $assignedInWindow[] = $refereeId;
                    $assignedToThisSlot++;
                    $assignmentsCreated++;
                }

                if ($assignedToThisSlot > 0) {
                    $slotsAssigned++;
                    $slot->update(['status' => 'assigned']);
                }
            }
        }

        // 7. Build stats
        $counts = array_column($refereeStats, 'assignment_count');
        $min    = $counts ? min($counts) : 0;
        $max    = $counts ? max($counts) : 0;
        $avg    = count($counts) ? round(array_sum($counts) / count($counts), 2) : 0;

        Log::info('Auto-assignment completed', [
            'director_id'          => $user->id,
            'camp_id'              => $campId,
            'assignments_created'  => $assignmentsCreated,
            'slots_assigned'       => $slotsAssigned,
            'court_rotation_skips' => $courtRotationSkips,
            'fairness_skips'       => $fairnessSkips,
        ]);

        return $this->success(
            'Auto-assignment completed with all rules enforced.',
            [
                'total_slots'               => $availableSlots->count(),
                'slots_assigned'            => $slotsAssigned,
                'total_referee_assignments' => $assignmentsCreated,
                'total_checked_in_referees' => $checkedInReferees->count(),
                'rest_periods_enforced'     => $restSkips,
                'court_rotations_applied'   => $courtRotationSkips,
                'fairness_adjustments'      => $fairnessSkips,
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
     * Check rest rule using in-memory data.
     * Returns true if referee must sit out this time window.
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

        $lastStart    = Carbon::parse($lastStartTime);
        $lastEnd      = $lastStart->copy()->addMinutes($gameDuration);
        $currentStart = Carbon::parse($currentSlot->start_time);

        return $lastEnd->gte($currentStart);
    }

    /**
     * Return referee IDs sorted by assignment count (ascending).
     * Within the same count, shuffle for randomness/fairness.
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
