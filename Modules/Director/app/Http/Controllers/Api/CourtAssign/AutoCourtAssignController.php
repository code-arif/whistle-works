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
     *  1. Back-to-back rest  → after playing slot X, referee sits out the immediately next time slot.
     *  2. Same time window   → a referee can only be assigned to ONE court per time slot.
     *  3. Fair distribution  → least-assigned referees get priority.
     *  4. Randomness         → among equally-loaded referees the order is shuffled each round.
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

        // 4. Build in-memory referee state
        // assignment_count → for fair distribution
        // last_played_key  → "date|start_time" of last slot played (for rest rule)
        $refereeStats = [];
        foreach ($checkedInReferees as $referee) {
            $refereeStats[$referee->id] = [
                'assignment_count' => 0,
                'last_played_key'  => null,
            ];
        }

        // 5. Group slots by time window (date + start_time)
        $slotsByTimeWindow = $availableSlots->groupBy(
            fn($slot) => $slot->game_date . '|' . $slot->start_time
        );

        // 6. Main assignment loop
        $assignmentsCreated = 0;
        $slotsAssigned = 0;
        $restSkips = 0;

        foreach ($slotsByTimeWindow as $timeKey => $windowSlots) {

            $firstSlot  = $windowSlots->first();
            $maxPerSlot = $firstSlot->schedule->max_referees_per_slot ?? 3;

            // 6a. Find ALL eligible referees for this time window
            // Eligible = not resting (didn't play in immediately previous slot)
            // We check rest using our in-memory last_played_key to avoid DB calls.
            $eligibleRefereeIds = [];

            foreach ($this->getSortedRefereeIds($refereeStats) as $refereeId) {
                if ($this->needsRestInMemory($refereeStats[$refereeId]['last_played_key'], $firstSlot)) {
                    $restSkips++;
                    continue;
                }
                $eligibleRefereeIds[] = $refereeId;
            }

            // 6b. Distribute eligible referees across courts in this window ─
            // Each referee gets at most ONE court per window.
            // We walk through eligibleRefereeIds sequentially and fill courts one by one.
            $refQueue = $eligibleRefereeIds; // already sorted: least assigned first, shuffled within ties
            $refIndex = 0;

            foreach ($windowSlots as $slot) {
                $assignedToThisSlot = 0;

                while ($assignedToThisSlot < $maxPerSlot && $refIndex < count($refQueue)) {

                    $refereeId = $refQueue[$refIndex];
                    $refIndex++;

                    // Assign
                    GameSlotAssignment::create([
                        'game_slot_id' => $slot->id,
                        'assignable_type' => User::class,
                        'assignable_id' => $refereeId,
                        'assignment_type' => 'individual',
                        'is_auto_assigned' => true,
                        'assigned_at' => now(),
                    ]);

                    // Update in-memory state
                    $refereeStats[$refereeId]['assignment_count']++;
                    $refereeStats[$refereeId]['last_played_key'] = $timeKey;

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
            'director_id' => $user->id,
            'camp_id' => $campId,
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
     * We only block if they played in the IMMEDIATELY previous time slot
     * (i.e., their last slot ended exactly when this slot starts).
     */
    private function needsRestInMemory(?string $lastPlayedKey, GameSlot $currentSlot): bool
    {
        if ($lastPlayedKey === null) {
            return false; // Never played → no rest needed
        }

        [$lastDate, $lastStartTime] = explode('|', $lastPlayedKey);

        // Different date → no rest needed (new day)
        if ($lastDate !== $currentSlot->game_date) {
            return false;
        }

        $gameDuration = $currentSlot->schedule->game_duration; // in minutes

        $lastStart  = Carbon::parse($lastStartTime);
        $lastEnd    = $lastStart->copy()->addMinutes($gameDuration);
        $currentStart = Carbon::parse($currentSlot->start_time);

        // Needs rest if last game hasn't finished before current slot starts
        // i.e., back-to-back → lastEnd == currentStart → blocked
        return $lastEnd->gte($currentStart);
    }

    /**
     * Return referee IDs sorted by assignment count (ascending).
     * Within the same count, shuffle for randomness.
     */
    private function getSortedRefereeIds(array $refereeStats): array
    {
        // Group referee IDs by their assignment count
        $grouped = [];
        foreach ($refereeStats as $id => $stats) {
            $grouped[$stats['assignment_count']][] = $id;
        }

        ksort($grouped); // lowest count first

        $sorted = [];
        foreach ($grouped as $ids) {
            shuffle($ids); // random order within same count
            foreach ($ids as $id) {
                $sorted[] = $id;
            }
        }

        return $sorted;
    }
}
