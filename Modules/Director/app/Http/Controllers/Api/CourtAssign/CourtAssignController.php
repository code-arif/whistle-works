<?php

namespace Modules\Director\Http\Controllers\Api\CourtAssign;

use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Modules\Director\Models\{
    Camp,
    GameSlot,
    GameSlotAssignment,
    CampRefereeCheckin,
    Crew
};

class CourtAssignController extends Controller
{
    use ApiResponse;

    /**
     * Assign individual referees to a game slot
     * Max 3 referees per slot
     */
    public function assignIndividualReferees(Request $request, $slotId)
    {
        $request->validate([
            'referee_ids' => 'required|array|min:1|max:3',
            'referee_ids.*' => 'exists:users,id',
        ]);

        $user = auth('api')->user();
        $slot = GameSlot::with('schedule.camp')->findOrFail($slotId);

        // Authorization check
        if ($slot->schedule->camp->director_id !== $user->id) {
            return $this->error('Unauthorized.', null, 403);
        }

        // Check if slot already has crew assignment
        $hasCrewAssignment = GameSlotAssignment::where('game_slot_id', $slotId)
            ->where('assignment_type', 'crew')
            ->exists();

        if ($hasCrewAssignment) {
            return $this->error('This slot is already assigned to a crew. Remove crew first.', null, 400);
        }

        // Get current individual assignments
        $currentAssignments = GameSlotAssignment::where('game_slot_id', $slotId)
            ->where('assignment_type', 'individual')
            ->count();

        $availableSlots = 3 - $currentAssignments;

        if ($availableSlots <= 0) {
            return $this->error('This slot already has maximum 3 referees.', null, 400);
        }

        $assignedCount = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($request->referee_ids as $refereeId) {
                if ($assignedCount >= $availableSlots) {
                    break;
                }

                // Check if referee is checked-in
                $checkedIn = CampRefereeCheckin::where('camp_id', $slot->schedule->camp_id)
                    ->where('referee_id', $refereeId)
                    ->exists();

                if (!$checkedIn) {
                    $errors[] = "Referee ID {$refereeId} is not checked-in.";
                    continue;
                }

                // Check if already assigned to this slot
                $alreadyAssigned = GameSlotAssignment::where('game_slot_id', $slotId)
                    ->where('assignable_type', User::class)
                    ->where('assignable_id', $refereeId)
                    ->exists();

                if ($alreadyAssigned) {
                    $errors[] = "Referee ID {$refereeId} is already assigned.";
                    continue;
                }

                // Create assignment
                GameSlotAssignment::create([
                    'game_slot_id' => $slotId,
                    'assignable_type' => User::class,
                    'assignable_id' => $refereeId,
                    'assignment_type' => 'individual',
                    'is_auto_assigned' => false,
                ]);

                $assignedCount++;
            }

            // Update slot status
            if ($assignedCount > 0) {
                $slot->update(['status' => 'assigned']);
            }

            DB::commit();

            // Fetch assigned referees
            $assignedReferees = $this->getSlotReferees($slotId);

            return $this->success(
                "{$assignedCount} referee(s) assigned successfully.",
                [
                    'assigned_count' => $assignedCount,
                    'assigned_referees' => $assignedReferees,
                    'errors' => $errors
                ],
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to assign referees: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Assign crew to a game slot
     * Crew replaces any individual assignments
     */
    public function assignCrew(Request $request, $slotId)
    {
        $request->validate([
            'crew_id' => 'required|exists:crews,id',
        ]);

        $user = auth('api')->user();
        $slot = GameSlot::with('schedule.camp')->findOrFail($slotId);

        // Authorization check
        if ($slot->schedule->camp->director_id !== $user->id) {
            return $this->error('Unauthorized.', null, 403);
        }

        $crew = Crew::with('members')->findOrFail($request->crew_id);

        // Verify crew belongs to same camp
        if ($crew->camp_id !== $slot->schedule->camp_id) {
            return $this->error('Crew does not belong to this camp.', null, 400);
        }

        // Check if crew is already assigned to another slot in same time
        $conflictingSlot = GameSlotAssignment::where('assignable_type', Crew::class)
            ->where('assignable_id', $crew->id)
            ->whereHas('gameSlot', function ($q) use ($slot) {
                $q->where('game_date', $slot->game_date)
                    ->where(function ($query) use ($slot) {
                        $query->whereBetween('start_time', [$slot->start_time, $slot->end_time])
                            ->orWhereBetween('end_time', [$slot->start_time, $slot->end_time]);
                    });
            })
            ->exists();

        if ($conflictingSlot) {
            return $this->error('This crew is already assigned to another slot at the same time.', null, 400);
        }

        DB::beginTransaction();
        try {
            // Remove all existing individual assignments
            GameSlotAssignment::where('game_slot_id', $slotId)
                ->where('assignment_type', 'individual')
                ->delete();

            // Create crew assignment
            GameSlotAssignment::create([
                'game_slot_id' => $slotId,
                'assignable_type' => Crew::class,
                'assignable_id' => $crew->id,
                'assignment_type' => 'crew',
                'is_auto_assigned' => false,
            ]);

            // Update slot status
            $slot->update(['status' => 'assigned']);

            DB::commit();

            $crewData = [
                'crew_id' => $crew->id,
                'crew_name' => $crew->name,
                'members' => $crew->members->map(fn($m) => [
                    'id' => $m->id,
                    'name' => $m->first_name . ' ' . $m->last_name,
                    'avatar' => $m->avatar ? asset($m->avatar) : null,
                ])
            ];

            return $this->success(
                'Crew assigned successfully.',
                ['crew' => $crewData],
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to assign crew: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Auto-assign available referees to all slots
     * Assigns 3 random referees per slot
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

        // Get all available slots (no crew assignments)
        $availableSlots = GameSlot::whereHas('schedule', function ($q) use ($campId) {
            $q->where('camp_id', $campId);
        })
            ->where('is_block', false)
            ->whereDoesntHave('assignments', function ($q) {
                $q->where('assignment_type', 'crew');
            })
            ->get();

        if ($availableSlots->isEmpty()) {
            return $this->error('No available slots found.', null, 404);
        }

        // Get all checked-in referees
        $checkedInRefereeIds = CampRefereeCheckin::where('camp_id', $campId)
            ->pluck('referee_id')
            ->toArray();

        if (empty($checkedInRefereeIds)) {
            return $this->error('No checked-in referees available.', null, 404);
        }

        DB::beginTransaction();
        try {
            $assignedSlotsCount = 0;
            $totalAssignments = 0;

            foreach ($availableSlots as $slot) {
                // Get already assigned referee IDs for this slot
                $alreadyAssigned = GameSlotAssignment::where('game_slot_id', $slot->id)
                    ->where('assignment_type', 'individual')
                    ->where('assignable_type', User::class)
                    ->pluck('assignable_id')
                    ->toArray();

                $availableCount = 3 - count($alreadyAssigned);

                if ($availableCount <= 0) {
                    continue; // Slot is full
                }

                // Get referees not assigned to this slot
                $availableReferees = array_diff($checkedInRefereeIds, $alreadyAssigned);

                if (empty($availableReferees)) {
                    continue;
                }

                // Randomly select referees
                shuffle($availableReferees);
                $selectedReferees = array_slice($availableReferees, 0, $availableCount);

                foreach ($selectedReferees as $refereeId) {
                    GameSlotAssignment::create([
                        'game_slot_id' => $slot->id,
                        'assignable_type' => User::class,
                        'assignable_id' => $refereeId,
                        'assignment_type' => 'individual',
                        'is_auto_assigned' => true,
                    ]);
                    $totalAssignments++;
                }

                // Update slot status
                $slot->update(['status' => 'assigned']);
                $assignedSlotsCount++;
            }

            DB::commit();

            return $this->success(
                'Auto-assignment completed successfully.',
                [
                    'total_slots_assigned' => $assignedSlotsCount,
                    'total_referee_assignments' => $totalAssignments
                ],
                200
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Auto-assignment failed: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Remove assignment (individual referee or entire crew)
     */
    public function removeAssignment($assignmentId)
    {
        $user = auth('api')->user();

        $assignment = GameSlotAssignment::with('gameSlot.schedule.camp')
            ->findOrFail($assignmentId);

        // Authorization check
        if ($assignment->gameSlot->schedule->camp->director_id !== $user->id) {
            return $this->error('Unauthorized.', null, 403);
        }

        DB::beginTransaction();
        try {
            $gameSlot = $assignment->gameSlot;
            $assignment->delete();

            // Update slot status if no more assignments
            $remainingAssignments = GameSlotAssignment::where('game_slot_id', $gameSlot->id)->count();

            if ($remainingAssignments === 0) {
                $gameSlot->update(['status' => 'available']);
            }

            DB::commit();

            return $this->success('Assignment removed successfully.', null, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to remove assignment: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get all assignments for a specific slot
     */
    public function getSlotAssignments($slotId)
    {
        $user = auth('api')->user();

        $slot = GameSlot::with('schedule.camp')->findOrFail($slotId);

        if ($slot->schedule->camp->director_id !== $user->id) {
            return $this->error('Unauthorized.', null, 403);
        }

        $assignments = GameSlotAssignment::where('game_slot_id', $slotId)
            ->with(['assignable'])
            ->get()
            ->map(function ($assignment) {
                if ($assignment->assignment_type === 'crew') {
                    return [
                        'assignment_id' => $assignment->id,
                        'type' => 'crew',
                        'crew' => [
                            'id' => $assignment->assignable->id,
                            'name' => $assignment->assignable->name,
                            'members' => $assignment->assignable->members->map(fn($m) => [
                                'id' => $m->id,
                                'name' => $m->first_name . ' ' . $m->last_name,
                                'avatar' => $m->avatar ? asset($m->avatar) : null,
                            ])
                        ],
                        'is_auto_assigned' => $assignment->is_auto_assigned,
                    ];
                } else {
                    return [
                        'assignment_id' => $assignment->id,
                        'type' => 'individual',
                        'referee' => [
                            'id' => $assignment->assignable->id,
                            'name' => $assignment->assignable->first_name . ' ' . $assignment->assignable->last_name,
                            'email' => $assignment->assignable->email,
                            'avatar' => $assignment->assignable->avatar ? asset($assignment->assignable->avatar) : null,
                        ],
                        'is_auto_assigned' => $assignment->is_auto_assigned,
                    ];
                }
            });

        return $this->success(
            'Slot assignments fetched successfully.',
            [
                'slot_id' => $slotId,
                'court_name' => $slot->court_name,
                'total_assignments' => $assignments->count(),
                'assignments' => $assignments
            ],
            200
        );
    }

    /**
     * Get available referees for a camp (not assigned anywhere)
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

        // Get checked-in referees
        $checkedInRefereeIds = CampRefereeCheckin::where('camp_id', $campId)
            ->pluck('referee_id');

        // Get assigned referee IDs (individual assignments only)
        $assignedRefereeIds = GameSlotAssignment::whereHas('gameSlot.schedule', function ($q) use ($campId) {
            $q->where('camp_id', $campId);
        })
            ->where('assignment_type', 'individual')
            ->where('assignable_type', User::class)
            ->pluck('assignable_id')
            ->toArray();

        // Available = checked-in but not assigned
        $availableRefereeIds = $checkedInRefereeIds->diff($assignedRefereeIds);

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
     * Helper: Get all referees assigned to a slot
     */
    private function getSlotReferees($slotId)
    {
        return GameSlotAssignment::where('game_slot_id', $slotId)
            ->where('assignment_type', 'individual')
            ->where('assignable_type', User::class)
            ->with('assignable:id,first_name,last_name,email,avatar')
            ->get()
            ->map(function ($assignment) {
                return [
                    'assignment_id' => $assignment->id,
                    'referee_id' => $assignment->assignable->id,
                    'name' => $assignment->assignable->first_name . ' ' . $assignment->assignable->last_name,
                    'email' => $assignment->assignable->email,
                    'avatar' => $assignment->assignable->avatar ? asset($assignment->assignable->avatar) : null,
                ];
            });
    }
}
