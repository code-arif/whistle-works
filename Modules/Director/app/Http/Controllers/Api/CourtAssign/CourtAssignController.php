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
    Crew,
    Schedule
};
use Modules\Director\Transformers\CourtAssign\GameSlotRefereeResource;

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
                    'avatar' => $m->avatar ? asset($m->avatar) : asset('default/profile.jpg'),
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

        // Get available slots
        $availableSlots = GameSlot::whereHas('schedule', function ($q) use ($campId) {
            $q->where('camp_id', $campId);
        })
            ->where('is_block', false)
            ->whereDoesntHave('assignments', function ($q) {
                $q->where('assignment_type', 'crew');
            })
            ->orderBy('game_date')
            ->orderBy('start_time')
            ->get();

        if ($availableSlots->isEmpty()) {
            return $this->error('No available slots found.', null, 404);
        }

        // Get checked-in referees
        $checkedInRefereeIds = CampRefereeCheckin::where('camp_id', $campId)
            ->pluck('referee_id')
            ->shuffle()
            ->toArray();

        if (empty($checkedInRefereeIds)) {
            return $this->error('No checked-in referees available.', null, 404);
        }

        $totalReferees = count($checkedInRefereeIds);
        $totalSlots = $availableSlots->count();
        $maxPerSlot = 3;

        // Clear previous individual assignments (recommended for true auto-assign)
        DB::transaction(function () use ($availableSlots) {
            GameSlotAssignment::whereIn('game_slot_id', $availableSlots->pluck('id'))
                ->where('assignment_type', 'individual')
                ->delete();

            // Reset status
            GameSlot::whereIn('id', $availableSlots->pluck('id'))
                ->update(['status' => 'available']);
        });

        $assignmentsCreated = 0;
        $slotsAssigned = 0; // slots with at least 1 referee

        // Referee queue - will cycle
        $refereeQueue = $checkedInRefereeIds;

        foreach ($availableSlots as $slot) {
            $currentCount = 0; // how many assigned in this slot

            while ($currentCount < $maxPerSlot && !empty($refereeQueue)) {
                $refereeId = array_shift($refereeQueue);

                GameSlotAssignment::create([
                    'game_slot_id'     => $slot->id,
                    'assignable_type'  => User::class,
                    'assignable_id'    => $refereeId,
                    'assignment_type'  => 'individual',
                    'is_auto_assigned' => true,
                    'assigned_at'      => now(),
                ]);

                $assignmentsCreated++;
                $currentCount++;

                // Put back in queue for reuse
                $refereeQueue[] = $refereeId;
            }

            if ($currentCount > 0) {
                $slotsAssigned++;
                $slot->update(['status' => 'assigned']);
            }

            // Optional: Stop early if referees are too few and slots are too many
            // Example: if referees < slots, don't over-assign
            // But if you want maximum coverage, keep going
        }

        // Final stats
        $stats = [
            'total_slots'               => $totalSlots,
            'slots_assigned'            => $slotsAssigned,
            'total_referee_assignments' => $assignmentsCreated,
            'total_checked_in_referees' => $totalReferees,
            'average_per_referee'       => $totalReferees > 0 ? round($assignmentsCreated / $totalReferees, 2) : 0,
        ];

        return $this->success(
            'Auto-assignment completed successfully with balanced distribution.',
            $stats,
            200
        );
    }

    /**
     * Remove assignment (individual referee or entire crew)
     */
    public function removeAssignment($assignmentId)
    {
        $user = auth('api')->user();

        $assignment = GameSlotAssignment::with('gameSlot.schedule.camp')
            ->find($assignmentId);

        if (!$assignment) {
            return $this->error([], 'Assignment not found.', 404);
        }

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
     * Clear all assinment for a schedule
     */
    public function clearScheduleAssignments($scheduleId)
    {
        $user = auth('api')->user();

        // Step 1: Find the schedule and verify ownership
        $schedule = Schedule::with('camp')->find($scheduleId); // assume Schedule model exists

        if (!$schedule) {
            return $this->error('Schedule not found.', null, 404);
        }

        if ($schedule->camp->director_id !== $user->id) {
            return $this->error('Unauthorized. You can only clear assignments for your own camp.', null, 403);
        }

        // Step 2: Check if any slots exist
        $slotCount = GameSlot::where('schedule_id', $scheduleId)->count();
        if ($slotCount === 0) {
            return $this->error('No game slots found for this schedule.', null, 404);
        }

        // Step 3: Count current assignments (for response)
        $assignmentCount = GameSlotAssignment::whereHas('gameSlot', function ($q) use ($scheduleId) {
            $q->where('schedule_id', $scheduleId);
        })->count();

        if ($assignmentCount === 0) {
            return $this->success(
                'No assignments to clear. All slots are already available.',
                [
                    'cleared_count' => 0,
                    'schedule_id' => $scheduleId,
                    'slot_count' => $slotCount,
                ],
                200
            );
        }

        // Step 4: Clear assignments in transaction (safe delete)
        DB::beginTransaction();
        try {
            // Delete all assignments for this schedule's slots
            $deleted = GameSlotAssignment::whereHas('gameSlot', function ($q) use ($scheduleId) {
                $q->where('schedule_id', $scheduleId);
            })->delete();

            // Optional: Reset slot statuses to 'available'
            GameSlot::where('schedule_id', $scheduleId)
                ->where('status', 'assigned') // only assigned ones
                ->update(['status' => 'available']);

            DB::commit();

            return $this->success(
                'All assignments cleared successfully.',
                [
                    'cleared_count' => $deleted,
                    'schedule_id' => $scheduleId,
                    'slot_count' => $slotCount,
                    'available_slots' => $slotCount, // now all are available
                ],
                200
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to clear assignments: ' . $e->getMessage(), null, 500);
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
                    'avatar' => $ref->avatar ? asset($ref->avatar) : asset('default/profile.jpg'),
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
     * Get assigned referees for a slot
     */
    public function getAssignedRefereesOrCrew($slotId)
    {
        $user = auth('api')->user();

        $slot = GameSlot::with('schedule.camp')->findOrFail($slotId);

        if ($slot->schedule->camp->director_id !== $user->id) {
            return $this->error('Unauthorized.', null, 403);
        }

        $court = [
            'court_id'   => $slot->id,
            'court_name' => $slot->court_name ?? 'Unknown',
        ];

        // Individual assignments
        $individualAssignments = GameSlotAssignment::with([
            'assignable' => fn($q) => $q->select('id', 'first_name', 'last_name', 'email', 'avatar'),
        ])
            ->where('game_slot_id', $slotId)
            ->where('assignment_type', 'individual')
            ->get();

        // Crew assignments (max 1 expected)
        $crewAssignments = GameSlotAssignment::with([
            'assignable.members.referee' => fn($q) => $q->select('id', 'first_name', 'last_name', 'email', 'avatar'),
        ])
            ->where('game_slot_id', $slotId)
            ->where('assignment_type', 'crew')
            ->get();

        // Prepare referees list
        $referees = $individualAssignments->map(function ($item) {
            $referee = $item->assignable;

            return [
                'assignment_id' => $item->id,
                'referee' => [
                    'referee_id' => $referee->id,
                    'name'       => trim("{$referee->first_name} {$referee->last_name}"),
                    'email'      => $referee->email,
                    'avatar'     => $referee->avatar
                        ? asset($referee->avatar)
                        : asset('default/profile.jpg'),
                ],
            ];
        })->values();

        // Prepare crew (if exists)
        $crewData = null;
        if ($crewAssignments->isNotEmpty()) {
            $crewAssignment = $crewAssignments->first();
            $crew = $crewAssignment->assignable;

            $crewData = [
                'assignment_id' => $crewAssignment->id,
                'crew' => [
                    'crew_id'      => $crew->id,
                    'crew_name'    => $crew->name,
                    'description'  => $crew->description,
                    'member_count' => $crew->members->count(),
                    'members'      => $crew->members->map(function ($member) {
                        $ref = $member->referee ?? $member;
                        return [
                            'referee_id' => $ref->id,
                            'name'       => trim("{$ref->first_name} {$ref->last_name}"),
                            'email'      => $ref->email,
                            'avatar'     => $ref->avatar
                                ? asset($ref->avatar)
                                : asset('default/profile.jpg'),
                        ];
                    })->values(),
                ],
            ];
        }

        // Final structured data
        $responseData = [
            'court'     => $court,
            'referees'  => $referees->isNotEmpty() ? $referees : [],
            'crew'      => $crewData,
        ];

        return $this->success(
            'Assigned referees and crew fetched successfully.',
            $responseData,
            200
        );
    }

    /**
     * Helper: Get all referees assigned to a slot
     */
    private function getSlotReferees($slotId)
    {
        $assignments = GameSlotAssignment::with('assignable')
            ->where('game_slot_id', $slotId)
            ->where('assignment_type', 'individual')
            ->where('assignable_type', User::class) // or your User model
            ->get();

        return GameSlotRefereeResource::collection($assignments);
    }

    /**
     * Toggle block/unblock a court slot
     */
    public function toggleBlockSlot($slotId)
    {
        $user = auth('api')->user();

        $slot = GameSlot::with('schedule.camp')->findOrFail($slotId);

        if ($slot->schedule->camp->director_id !== $user->id) {
            return $this->error('Unauthorized.', null, 403);
        }

        $newBlockStatus = !$slot->is_block;
        $slot->update(['is_block' => $newBlockStatus]);

        return $this->success(
            $newBlockStatus ? 'Slot blocked successfully.' : 'Slot unblocked successfully.',
            ['slot_id' => $slotId, 'is_blocked' => $newBlockStatus],
            200
        );
    }
}
