<?php

namespace Modules\Director\Http\Controllers\Api\Schedule;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Modules\Director\Http\Requests\{ScheduleCreateRequest, LocationAddRequest};
use Modules\Director\Models\{Camp, Schedule, ScheduleLocation, ScheduleTimeRange, GameSlot, RefereeAssignment, CampRefereeCheckin};

class RefereeAssignController extends Controller
{
    use ApiResponse;
    /**
     * Assign referee to game slot
     */
    public function assignReferee(Request $request, $gameSlotId)
    {
        $request->validate([
            'referee_id' => 'required|exists:users,id'
        ]);

        $user = auth('api')->user();

        $gameSlot = GameSlot::with('schedule.camp')->findOrFail($gameSlotId);

        // Verify ownership
        if ($gameSlot->schedule->camp->director_id !== $user->id) {
            return $this->error('Unauthorized.', null, 403);
        }

        // Check if referee is checked in
        $isCheckedIn = CampRefereeCheckin::where('camp_id', $gameSlot->schedule->camp_id)
            ->where('referee_id', $request->referee_id)
            ->exists();

        if (!$isCheckedIn) {
            return $this->error('Referee is not checked in to this camp.', null, 400);
        }

        // Check if referee already assigned to this slot
        $exists = RefereeAssignment::where('game_slot_id', $gameSlotId)
            ->where('referee_id', $request->referee_id)
            ->exists();

        if ($exists) {
            return $this->error('Referee already assigned to this slot.', null, 400);
        }

        // Create assignment
        $assignment = RefereeAssignment::create([
            'game_slot_id' => $gameSlotId,
            'referee_id' => $request->referee_id,
            'assignment_type' => 'manual'
        ]);

        $gameSlot->update(['status' => 'assigned']);

        return $this->success(
            'Referee assigned successfully.',
            [
                'assignment' => $assignment->load('referee'),
                'game_slot' => $gameSlot->load('refereeAssignments.referee')
            ],
            201
        );
    }

    /**
     * Remove referee assignment
     */
    public function removeReferee($assignmentId)
    {
        $user = auth('api')->user();

        $assignment = RefereeAssignment::with('gameSlot.schedule.camp')->findOrFail($assignmentId);

        // Verify ownership
        if ($assignment->gameSlot->schedule->camp->director_id !== $user->id) {
            return $this->error('Unauthorized.', null, 403);
        }

        $gameSlot = $assignment->gameSlot;
        $assignment->delete();

        // Update slot status if no more referees
        if ($gameSlot->refereeAssignments()->count() === 0) {
            $gameSlot->update(['status' => 'available']);
        }

        return $this->success('Referee removed successfully.', null, 200);
    }

    /**
     * Auto-assign referees to available slots
     */
    public function autoAssignReferees($campId, Request $request)
    {
        $request->validate([
            'referees_per_game' => 'sometimes|integer|min:1|max:4'
        ]);

        $user = auth('api')->user();

        $camp = Camp::where('id', $campId)
            ->where('director_id', $user->id)
            ->first();

        if (!$camp) {
            return $this->error('Camp not found.', null, 404);
        }

        $schedule = $camp->schedule;
        if (!$schedule) {
            return $this->error('Schedule not found.', null, 404);
        }

        $refereesPerGame = $request->referees_per_game ?? 2;

        // Get all checked-in referees
        $availableReferees = CampRefereeCheckin::where('camp_id', $campId)
            ->pluck('referee_id')
            ->toArray();

        if (empty($availableReferees)) {
            return $this->error('No referees checked in.', null, 400);
        }

        DB::beginTransaction();
        try {
            $assignedCount = 0;

            // Get available slots
            $availableSlots = GameSlot::where('schedule_id', $schedule->id)
                ->where('status', 'available')
                ->get();

            foreach ($availableSlots as $slot) {
                $currentAssignments = $slot->refereeAssignments()->count();
                $needed = $refereesPerGame - $currentAssignments;

                if ($needed > 0) {
                    // Get referees not yet assigned to this slot
                    $assignedRefereeIds = $slot->refereeAssignments()->pluck('referee_id')->toArray();
                    $unassignedReferees = array_diff($availableReferees, $assignedRefereeIds);

                    // Randomly assign referees
                    $toAssign = array_slice(array_values($unassignedReferees), 0, $needed);

                    foreach ($toAssign as $refereeId) {
                        RefereeAssignment::create([
                            'game_slot_id' => $slot->id,
                            'referee_id' => $refereeId,
                            'assignment_type' => 'auto'
                        ]);
                        $assignedCount++;
                    }

                    $slot->update(['status' => 'assigned']);
                }
            }

            DB::commit();

            return $this->success(
                "Auto-assigned {$assignedCount} referees successfully.",
                ['assigned_count' => $assignedCount],
                200
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Auto-assignment failed: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get available referees for a camp
     */
    public function getAvailableReferees($campId)
    {
        $user = auth('api')->user();

        $camp = Camp::where('id', $campId)
            ->where('director_id', $user->id)
            ->first();

        if (!$camp) {
            return $this->error('Camp not found.', null, 404);
        }

        $referees = CampRefereeCheckin::where('camp_id', $campId)
            ->with('referee:id,name,email')
            ->get()
            ->pluck('referee');

        return $this->success(
            'Available referees fetched successfully.',
            ['referees' => $referees],
            200
        );
    }
}
