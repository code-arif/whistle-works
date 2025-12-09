<?php

namespace Modules\Director\Http\Controllers\Api\Schedule;

use App\Models\User;
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
    public function assignReferees(Request $request, $slotId)
    {
        $request->validate([
            'referee_ids' => 'required|array|min:1',
            'referee_ids.*' => 'exists:users,id',
        ]);

        $user = auth('api')->user();

        $slot = GameSlot::with('schedule.camp', 'refereeAssignments.referee')->findOrFail($slotId);

        if ($slot->schedule->camp->director_id !== $user->id) {
            return $this->error('Unauthorized.', null, 403);
        }

        $currentCount = $slot->refereeAssignments->count();
        $availableSlots = 3 - $currentCount; // max 3 referees per slot

        if ($availableSlots <= 0) {
            return $this->error('This slot already has maximum 3 referees.', null, 400);
        }

        $assignedCount = 0;

        foreach ($request->referee_ids as $refereeId) {
            if ($assignedCount >= $availableSlots) {
                break;
            }

            // Must be checked-in
            $checkedIn = CampRefereeCheckin::where('camp_id', $slot->schedule->camp_id)
                ->where('referee_id', $refereeId)
                ->exists();

            if (!$checkedIn) {
                continue;
            }

            // Prevent duplicate
            $exists = RefereeAssignment::where('game_slot_id', $slotId)
                ->where('referee_id', $refereeId)
                ->exists();

            if ($exists) {
                continue;
            }

            // Create assignment
            RefereeAssignment::create([
                'game_slot_id' => $slotId,
                'referee_id' => $refereeId,
                'assignment_type' => 'manual'
            ]);

            $assignedCount++;
        }

        if ($assignedCount > 0) {
            $slot->update(['status' => 'assigned']);
        }

        // Fetch clean referee list
        $assignedReferees = $slot->refereeAssignments()
            ->with('referee:id,first_name,last_name,avatar')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->referee->id,
                    'name' => $item->referee->first_name . ' ' . $item->referee->last_name,
                    'avatar' => $item->referee->avatar ? asset($item->referee->avatar) : asset('default/profile.jpg'),
                ];
            });

        return $this->success(
            "{$assignedCount} referees assigned successfully.",
            [
                'assigned_referees' => $assignedReferees
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
    public function getAvailableReferees($campId)
    {
        $user = auth('api')->user();

        $camp = Camp::where('id', $campId)
            ->where('director_id', $user->id)
            ->first();

        if (!$camp) {
            return $this->error('Camp not found.', null, 404);
        }

        // Step 1: Get all checked-in referees
        $checkedInReferees = CampRefereeCheckin::where('camp_id', $campId)
            ->pluck('referee_id');

        // Step 2: Get referees already assigned to ANY slot of this camp
        $assignedReferees = RefereeAssignment::whereHas('gameSlot.schedule', function ($q) use ($campId) {
            $q->where('camp_id', $campId);
        })
            ->pluck('referee_id')
            ->toArray();

        // Step 3: Filter available referees (checked-in but not assigned)
        $availableRefereeIds = $checkedInReferees->filter(function ($refId) use ($assignedReferees) {
            return !in_array($refId, $assignedReferees);
        });

        // Step 4: Fetch referee basic info
        $referees = User::whereIn('id', $availableRefereeIds)
            ->select('id', 'first_name', 'last_name', 'email', 'avatar')
            ->get()
            ->map(function ($ref) {
                return [
                    'id' => $ref->id,
                    'name' => $ref->first_name . ' ' . $ref->last_name,
                    'email' => $ref->email,
                    'avatar' => $ref->avatar ? asset($ref->avatar) : null,
                ];
            });

        return $this->success(
            'Available referees fetched successfully.',
            [
                'total' => $referees->count(),
                'referees' => $referees
            ],
            200
        );
    }

    /**
     * Get available referees for a camp
     */
    public function getAssignedReferees($slotId)
    {
        $user = auth('api')->user();

        $slot = GameSlot::with('schedule.camp')->findOrFail($slotId);

        if ($slot->schedule->camp->director_id !== $user->id) {
            return $this->error('Unauthorized.', null, 403);
        }

        $assigned = $slot->refereeAssignments()
            ->with('referee:id,first_name,last_name,email')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'court' => [
                        'court_id' => $item->game_slot_id,
                        'court_name' => $item->gameSlot->court_name,
                    ],
                    'refree' => [
                        'referee_id'    => $item->referee->id,
                        'name'    => $item->referee->first_name . ' ' . $item->referee->last_name,
                        'email'         => $item->referee->email,
                    ],
                    'assignment_type' => $item->assignment_type,
                ];
            });

        return $this->success(
            'Assigned referees fetched successfully.',
            ['assigned_referees' => $assigned],
            200
        );
    }
}
