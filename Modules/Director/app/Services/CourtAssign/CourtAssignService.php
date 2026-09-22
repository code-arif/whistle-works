<?php

namespace Modules\Director\Services\CourtAssign;

use App\Models\AssistantDirectorPermission;
use App\Models\CampRefereeJearsyNumber;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\RefereeAssignedNotification;
use App\Notifications\RefereeRemoveFromCourtNotification;
use Modules\Director\Models\Camp;
use Modules\Director\Models\Crew;
use Modules\Director\Models\GameSlot;
use Modules\Director\Models\GameSlotAssignment;
use Modules\Director\Models\GameSlotAssignmentPosition;
use Modules\Director\Models\CampRefereeCheckin;
use Modules\Director\Models\Schedule;
use Modules\Director\Transformers\CourtAssign\GameSlotRefereeResource;

class CourtAssignService
{
    /**
     * Assign individual referees to a game slot.
     * Max referees per slot based on schedule settings.
     * Supports assignments array and referee_ids/position_ids formats.
     *
     * @param  mixed   $user
     * @param  int     $slotId
     * @param  Request $request
     * @return array
     */
    public function assignIndividualReferees($user, int $slotId, Request $request): array
    {
        $slot = GameSlot::with('schedule.camp', 'location')->find($slotId);

        if (!$slot) {
            return ['success' => false, 'code' => 404, 'message' => 'Game slot not found!', 'data' => null];
        }

        $camp = $slot->schedule?->camp;

        if (!$camp) {
            return ['success' => false, 'code' => 404, 'message' => 'Camp not found for this game slot.', 'data' => null];
        }

        // Authorization check
        if ($camp->director_id !== $user->id) {
            $permission = AssistantDirectorPermission::where('camp_id', $camp->id)
                ->where('assistant_director_id', $user->id)
                ->first();

            if (!$permission) {
                return ['success' => false, 'code' => 403, 'message' => 'Unauthorized.', 'data' => null];
            }

            if (!$permission->assign_referees) {
                return ['success' => false, 'code' => 403, 'message' => 'You do not have permission to assign referee for this schedule.', 'data' => null];
            }
        }

        // Check if slot already has crew assignment
        $hasCrewAssignment = GameSlotAssignment::where('game_slot_id', $slotId)
            ->where('assignment_type', 'crew')
            ->exists();

        if ($hasCrewAssignment) {
            return ['success' => false, 'code' => 400, 'message' => 'This slot is already assigned to a crew. Remove crew first.', 'data' => null];
        }

        // Prepare assignments array from request payload formats
        $itemsToAssign = [];

        $assignments = $request->input('assignments', $request->json('assignments'));
        if (!empty($assignments) && is_array($assignments)) {
            foreach ($assignments as $item) {
                if (is_array($item)) {
                    $refId = $item['referee_id'] ?? $item['referee_ids'] ?? $item['referee'] ?? null;
                    $posId = $item['position_id'] ?? $item['position_ids'] ?? $item['position'] ?? null;
                    if ($refId) {
                        $itemsToAssign[] = ['referee_id' => $refId, 'position_id' => $posId];
                    }
                }
            }
        }

        if (empty($itemsToAssign)) {
            $refereeIds = $request->input('referee_ids', $request->json('referee_ids'));
            if (!empty($refereeIds)) {
                $refereeIds  = is_array($refereeIds) ? $refereeIds : [$refereeIds];
                $positionIds = $request->input('position_ids', $request->json('position_ids', []));
                $positionIds = is_array($positionIds) ? $positionIds : [$positionIds];
                foreach ($refereeIds as $index => $refId) {
                    $itemsToAssign[] = ['referee_id' => $refId, 'position_id' => $positionIds[$index] ?? null];
                }
            }
        }

        if (empty($itemsToAssign)) {
            return ['success' => false, 'code' => 400, 'message' => 'Please provide referee_ids or assignments array.', 'data' => null];
        }

        $maxReferees          = $slot->schedule->max_referees_per_slot;
        $overrideRestrictions = $request->override_restrictions ?? false;

        $successfulAssignments = [];
        $failedAssignments     = [];
        $skippedReferees       = [];
        $overriddenWarnings    = [];

        DB::beginTransaction();
        try {
            $refereesToNotify = collect();

            foreach ($itemsToAssign as $item) {
                $refereeId      = $item['referee_id'];
                $targetPosition = $item['position_id'];

                // Resolve target GameSlotAssignmentPosition record
                $targetPosRecord = null;
                if (!empty($targetPosition)) {
                    if (is_numeric($targetPosition)) {
                        $targetPosRecord = GameSlotAssignmentPosition::where('game_slot_id', $slotId)
                            ->where('id', $targetPosition)
                            ->first() ?? GameSlotAssignmentPosition::find($targetPosition);
                    }
                    if (!$targetPosRecord && !is_numeric($targetPosition)) {
                        $targetPosRecord = GameSlotAssignmentPosition::where('game_slot_id', $slotId)
                            ->where('position', $targetPosition)
                            ->first();
                    }
                }

                $referee     = User::find($refereeId);
                $refereeName = $referee ? "{$referee->first_name} {$referee->last_name}" : "Referee #{$refereeId}";

                // Check if referee is checked-in
                $checkedIn = CampRefereeCheckin::where('camp_id', $slot->schedule->camp_id)
                    ->where('referee_id', $refereeId)
                    ->exists();

                if (!$checkedIn) {
                    $failedAssignments[] = [
                        'referee_id'   => $refereeId,
                        'position'     => $targetPosRecord?->position ?? $targetPosition,
                        'referee_name' => $refereeName,
                        'reason'       => 'Referee is not checked-in to this camp',
                        'can_retry'    => false,
                        'can_override' => false,
                    ];
                    continue;
                }

                // Check if already assigned to this slot
                $existingAssignment = GameSlotAssignment::where('game_slot_id', $slotId)
                    ->where('assignable_type', User::class)
                    ->where('assignable_id', $refereeId)
                    ->first();

                if ($existingAssignment) {
                    $currentPosRecord = GameSlotAssignmentPosition::where('game_slot_assignment_id', $existingAssignment->id)->first();
                    if ($targetPosRecord && $currentPosRecord && $currentPosRecord->id === $targetPosRecord->id) {
                        $existingAssignment->update(['assigned_at' => now()]);
                        $skippedReferees[] = [
                            'referee_id'  => $refereeId,
                            'position_id' => $targetPosRecord->id,
                            'position'    => $targetPosRecord->position,
                            'referee_name' => $refereeName,
                            'reason'      => 'Already assigned to this position in this slot',
                            'action'      => 'updated',
                        ];
                        continue;
                    } elseif ($targetPosRecord && $currentPosRecord && $currentPosRecord->id !== $targetPosRecord->id) {
                        // Clear old position link before reassigning
                        $currentPosRecord->update(['game_slot_assignment_id' => null]);
                    }
                }

                // Check rest restrictions
                $needsRest = GameSlotAssignment::needsRest($refereeId, User::class, $slot);

                if ($needsRest && !$overrideRestrictions) {
                    $failedAssignments[] = [
                        'referee_id'   => $refereeId,
                        'position'     => $targetPosRecord?->position ?? $targetPosition,
                        'referee_name' => $refereeName,
                        'reason'       => 'Referee needs rest - consecutive assignment restriction',
                        'can_retry'    => false,
                        'can_override' => true,
                    ];
                    continue;
                } elseif ($needsRest && $overrideRestrictions) {
                    $overriddenWarnings[] = [
                        'referee_id'   => $refereeId,
                        'position'     => $targetPosRecord?->position ?? $targetPosition,
                        'referee_name' => $refereeName,
                        'warning'      => 'Back-to-back assignment restriction overridden by director',
                    ];
                }

                // Check for time conflicts across courts
                $hasConflict = GameSlotAssignment::hasTimeConflict($refereeId, User::class, $slot);

                if ($hasConflict) {
                    $conflictingSlots = GameSlotAssignment::getConflictingSlots($refereeId, User::class, $slot);

                    $conflictDetails = $conflictingSlots->map(function ($assignment) {
                        $conflictSlot = $assignment->gameSlot;
                        return [
                            'court' => $conflictSlot->court_name,
                            'date'  => $conflictSlot->game_date,
                            'time'  => Carbon::parse($conflictSlot->start_time)->format('h:i A') . ' - ' .
                                Carbon::parse($conflictSlot->end_time)->format('h:i A'),
                        ];
                    })->toArray();

                    $failedAssignments[] = [
                        'referee_id'        => $refereeId,
                        'position'          => $targetPosRecord?->position ?? $targetPosition,
                        'referee_name'      => $refereeName,
                        'reason'            => 'Time conflict with other assignments',
                        'conflicting_slots' => $conflictDetails,
                        'can_retry'         => false,
                        'can_override'      => false,
                    ];
                    continue;
                }

                // SUCCESS: Create or update assignment record, then link GameSlotAssignmentPosition
                $assignmentRecord = GameSlotAssignment::updateOrCreate(
                    [
                        'game_slot_id'    => $slotId,
                        'assignable_type' => User::class,
                        'assignable_id'   => $refereeId,
                    ],
                    [
                        'assignment_type'  => 'individual',
                        'is_auto_assigned' => false,
                        'assigned_at'      => now(),
                    ]
                );

                if ($targetPosRecord) {
                    $targetPosRecord->update(['game_slot_assignment_id' => $assignmentRecord->id]);
                    $assignedPositionName = $targetPosRecord->position;
                    $assignedPositionId   = $targetPosRecord->id;
                } elseif (!empty($targetPosition) && !is_numeric($targetPosition)) {
                    $newPos = GameSlotAssignmentPosition::create([
                        'camp_id'                  => $camp->id,
                        'game_slot_id'             => $slotId,
                        'game_slot_assignment_id'  => $assignmentRecord->id,
                        'position'                 => $targetPosition,
                    ]);
                    $assignedPositionName = $newPos->position;
                    $assignedPositionId   = $newPos->id;
                } else {
                    $unassignedPos = GameSlotAssignmentPosition::where('game_slot_id', $slotId)
                        ->whereNull('game_slot_assignment_id')
                        ->first();

                    if ($unassignedPos) {
                        $assignedPositionName = $unassignedPos->position;
                        $assignedPositionId   = $unassignedPos->id;
                        $unassignedPos->update(['game_slot_assignment_id' => $assignmentRecord->id]);
                    } else {
                        $assignedPositionName = null;
                        $assignedPositionId   = null;
                    }
                }

                $successfulAssignments[] = [
                    'referee_id'   => $refereeId,
                    'referee_name' => $refereeName,
                    'position_id'  => $assignedPositionId,
                    'position'     => $assignedPositionName,
                    'assigned_at'  => now()->format('Y-m-d H:i:s'),
                    'overridden'   => $needsRest,
                ];

                $refereesToNotify->push($referee);
            }

            // Update slot status if any assignments were made
            if (count($successfulAssignments) > 0) {
                $slot->update(['status' => 'assigned']);

                if ($refereesToNotify->isNotEmpty() && $slot->schedule->status === 'published') {
                    Notification::send(
                        $refereesToNotify,
                        new RefereeAssignedNotification($slot, $slot->schedule->camp, $user, 'individual')
                    );
                }
            }

            DB::commit();

            $assignedReferees = $this->getSlotReferees($slotId);

            $totalAttempted  = count($itemsToAssign);
            $totalSuccessful = count($successfulAssignments);
            $totalFailed     = count($failedAssignments);
            $totalSkipped    = count($skippedReferees);

            if ($totalSuccessful === 0) {
                $message    = 'No referees could be assigned.';
                $statusCode = 400;
            } elseif ($totalSuccessful === $totalAttempted) {
                $message    = "All {$totalSuccessful} referee(s) assigned successfully.";
                $statusCode = 201;
            } else {
                $message    = "{$totalSuccessful} of {$totalAttempted} referee(s) assigned successfully.";
                $statusCode = 207;
            }

            Log::info('Individual referees assigned', [
                'director_id'        => $user->id,
                'camp_id'            => $slot->schedule->camp_id,
                'slot_id'            => $slotId,
                'successful'         => $totalSuccessful,
                'failed'             => $totalFailed,
                'overridden'         => count($overriddenWarnings),
                'notifications_sent' => $refereesToNotify->count(),
            ]);

            return [
                'success' => $totalSuccessful > 0,
                'code'    => $statusCode,
                'message' => $message,
                'data'    => [
                    'summary' => [
                        'total_attempted'         => $totalAttempted,
                        'successful'              => $totalSuccessful,
                        'failed'                  => $totalFailed,
                        'skipped'                 => $totalSkipped,
                        'restrictions_overridden' => count($overriddenWarnings),
                        'notifications_sent'      => $refereesToNotify->count(),
                    ],
                    'successful_assignments' => $successfulAssignments,
                    'failed_assignments'     => $failedAssignments,
                    'skipped_assignments'    => $skippedReferees,
                    'overridden_warnings'    => $overriddenWarnings,
                    'assigned_referees'      => $assignedReferees,
                ],
            ];
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to assign referees', [
                'error'   => $e->getMessage(),
                'slot_id' => $slotId,
            ]);
            return ['success' => false, 'code' => 500, 'message' => 'Failed to assign referees: ' . $e->getMessage(), 'data' => null];
        }
    }

    /**
     * Assign a crew to a game slot.
     * Removes any existing individual assignments first.
     *
     * @param  mixed   $user
     * @param  int     $slotId
     * @param  int     $crewId
     * @return array
     */
    public function assignCrew($user, int $slotId, int $crewId): array
    {
        $slot = GameSlot::with('schedule.camp')->findOrFail($slotId);
        $camp = $slot->schedule?->camp;

        if (!$camp) {
            return ['success' => false, 'code' => 404, 'message' => 'Camp not found for this game slot.', 'data' => null];
        }

        // Authorization check
        if ($camp->director_id !== $user->id) {
            $permission = AssistantDirectorPermission::where('camp_id', $camp->id)
                ->where('assistant_director_id', $user->id)
                ->first();

            if (!$permission) {
                return ['success' => false, 'code' => 403, 'message' => 'Unauthorized.', 'data' => null];
            }

            if (!$permission->assign_referees) {
                return ['success' => false, 'code' => 403, 'message' => 'You do not have permission to assign crew for this schedule.', 'data' => null];
            }
        }

        $crew = Crew::with('members')->findOrFail($crewId);

        // Verify crew belongs to same camp
        if ($crew->camp_id !== $slot->schedule->camp_id) {
            return ['success' => false, 'code' => 400, 'message' => 'Crew does not belong to this camp.', 'data' => null];
        }

        $hasConflict = GameSlotAssignment::hasTimeConflict($crew->id, Crew::class, $slot);

        if ($hasConflict) {
            $conflictingSlots = GameSlotAssignment::getConflictingSlots($crew->id, Crew::class, $slot);

            $conflictDetails = $conflictingSlots->map(function ($assignment) {
                $conflictSlot = $assignment->gameSlot;
                return [
                    'court' => $conflictSlot->court_name,
                    'date'  => $conflictSlot->game_date,
                    'time'  => Carbon::parse($conflictSlot->start_time)->format('h:i A') . ' - ' .
                        Carbon::parse($conflictSlot->end_time)->format('h:i A'),
                ];
            })->toArray();

            return [
                'success' => false,
                'code'    => 400,
                'message' => 'This crew has time conflicts with other assignments.',
                'data'    => ['conflicting_slots' => $conflictDetails],
            ];
        }

        DB::beginTransaction();
        try {
            // Remove all existing individual assignments
            GameSlotAssignment::where('game_slot_id', $slotId)
                ->where('assignment_type', 'individual')
                ->delete();

            // Create crew assignment
            $slotAssign = GameSlotAssignment::create([
                'game_slot_id'    => $slotId,
                'assignable_type' => Crew::class,
                'assignable_id'   => $crew->id,
                'assignment_type' => 'crew',
                'is_auto_assigned' => false,
            ]);

            $slot->update(['status' => 'assigned']);

            if ($crew->members->isNotEmpty() && $slot->schedule->status === 'published') {
                Notification::send(
                    $crew->members,
                    new RefereeAssignedNotification($slot, $slot->schedule->camp, $user, 'crew', $crew->name)
                );
            }

            DB::commit();

            Log::info('Crew assigned to slot', [
                'director_id'        => $user->id,
                'crew_id'            => $crew->id,
                'slot_id'            => $slotId,
                'crew_members'       => $crew->members->count(),
                'notifications_sent' => $crew->members->count(),
            ]);

            $crewData = [
                'assignment_id' => $slotAssign->id,
                'crew_id'       => $crew->id,
                'crew_name'     => $crew->name,
                'members'       => $crew->members->map(fn($m) => [
                    'id'     => $m->id,
                    'name'   => $m->first_name . ' ' . $m->last_name,
                    'avatar' => $m->avatar ? asset($m->avatar) : asset('default/profile.jpg'),
                ]),
            ];

            return [
                'success' => true,
                'code'    => 201,
                'message' => 'Crew assigned successfully.',
                'data'    => ['crew' => $crewData],
            ];
        } catch (Exception $e) {
            DB::rollBack();
            return ['success' => false, 'code' => 500, 'message' => 'Failed to assign crew: ' . $e->getMessage(), 'data' => null];
        }
    }

    /**
     * Get all referees for a specific slot with availability status.
     * Shows ALL checked-in referees with their current status.
     *
     * @param  mixed $user
     * @param  int   $slotId
     * @return array
     */
    public function getAvailableRefereesForSlot($user, int $slotId): array
    {
        $slot = GameSlot::with('schedule.camp')->find($slotId);

        if (!$slot) {
            return ['success' => false, 'code' => 404, 'message' => 'Game court not found!', 'data' => []];
        }

        $campId = $slot->schedule?->camp?->id;
        $camp   = Camp::forDirectorOrAssistant($user->id)->find($campId);

        if (!$camp) {
            return ['success' => false, 'code' => 404, 'message' => 'Camp not found.', 'data' => []];
        }

        if ($camp->director_id !== $user->id) {
            $permission = AssistantDirectorPermission::where('camp_id', $camp->id)
                ->where('assistant_director_id', $user->id)
                ->first();

            if (!$permission) {
                return ['success' => false, 'code' => 403, 'message' => 'Unauthorized to approve this registration.', 'data' => []];
            }
        }

        $jerseyNumbers = CampRefereeJearsyNumber::where('camp_id', $campId)
            ->pluck('jersey_number', 'referee_id');

        $assignmentCounts = GameSlotAssignment::where('assignable_type', User::class)
            ->whereHas('gameSlot.schedule', function ($q) use ($campId) {
                $q->where('camp_id', $campId);
            })
            ->select('assignable_id', DB::raw('count(*) as total'))
            ->groupBy('assignable_id')
            ->pluck('total', 'assignable_id');

        $allReferees = User::whereIn('id', function ($query) use ($slot) {
            $query->select('referee_id')
                ->from('camp_referee_checkins')
                ->where('camp_id', $slot->schedule->camp_id);
        })->orderBy('last_name', 'asc')->get();

        $assignedRefereeIds = GameSlotAssignment::where('game_slot_id', $slotId)
            ->where('assignment_type', 'individual')
            ->where('assignable_type', User::class)
            ->pluck('assignable_id')
            ->toArray();

        // Get all referee IDs assigned to ANY slot on THIS COURT across all dates and times in this camp
        $courtSlotIds = GameSlot::where('schedule_location_id', $slot->schedule_location_id)
            ->where('court_number', $slot->court_number)
            ->whereHas('schedule', function ($q) use ($campId) {
                $q->where('camp_id', $campId);
            })
            ->pluck('id');

        $individualAssignedOnCourt = GameSlotAssignment::whereIn('game_slot_id', $courtSlotIds)
            ->where('assignment_type', 'individual')
            ->where('assignable_type', User::class)
            ->pluck('assignable_id')
            ->toArray();

        $crewIdsAssignedOnCourt = GameSlotAssignment::whereIn('game_slot_id', $courtSlotIds)
            ->where('assignment_type', 'crew')
            ->where('assignable_type', Crew::class)
            ->pluck('assignable_id')
            ->toArray();

        $crewRefereesAssignedOnCourt = [];
        if (!empty($crewIdsAssignedOnCourt)) {
            $crewRefereesAssignedOnCourt = DB::table('crew_members')
                ->whereIn('crew_id', $crewIdsAssignedOnCourt)
                ->pluck('referee_id')
                ->toArray();
        }

        $allRefereesAssignedToCourtIds = array_unique(array_merge(
            $individualAssignedOnCourt,
            $crewRefereesAssignedOnCourt
        ));

        $refereesWithStatus = $allReferees->map(function ($referee) use ($slot, $assignedRefereeIds, $jerseyNumbers, $assignmentCounts, $allRefereesAssignedToCourtIds) {
            $isAssignedToThisSlot  = in_array($referee->id, $assignedRefereeIds);
            $isAssignedToThisCourt = in_array($referee->id, $allRefereesAssignedToCourtIds);

            $hasTimeConflict = GameSlotAssignment::hasTimeConflict($referee->id, User::class, $slot);
            $needsRest       = GameSlotAssignment::needsRest($referee->id, User::class, $slot);

            $status        = 'available';
            $statusMessage = 'Available for assignment';
            $canAssign     = true;

            if ($isAssignedToThisSlot) {
                $status        = 'assigned_to_this_slot';
                $statusMessage = 'Already assigned to this slot';
                $canAssign     = false;
            } elseif ($hasTimeConflict) {
                $status        = 'time_conflict';
                $statusMessage = 'Already assigned to another court at this time';
                $canAssign     = false;
            } elseif ($needsRest) {
                $status        = 'needs_rest';
                $statusMessage = 'Consecutive assignment restriction - needs rest';
                $canAssign     = false;
            }

            $conflictDetails = null;
            if ($hasTimeConflict) {
                $conflictingAssignments = GameSlotAssignment::getConflictingSlots($referee->id, User::class, $slot);

                if ($conflictingAssignments->isNotEmpty()) {
                    $conflictSlot    = $conflictingAssignments->first()->gameSlot;
                    $conflictDetails = [
                        'court_name' => $conflictSlot->court_name,
                        'time'       => Carbon::parse($conflictSlot->start_time)->format('h:i A') . ' - ' .
                            Carbon::parse($conflictSlot->end_time)->format('h:i A'),
                    ];
                }
            }

            return [
                'id'                         => $referee->id,
                'name'                       => trim("{$referee->first_name} {$referee->last_name}"),
                'email'                      => $referee->email,
                'phone'                      => $referee->phone,
                'avatar'                     => $referee->avatar ? asset($referee->avatar) : asset('default/profile.jpg'),
                'status'                     => $status,
                'is_assigned_to_this_court'  => $isAssignedToThisCourt,
                'jourcy_number'              => $jerseyNumbers[$referee->id] ?? null,
                'status_message'             => $statusMessage,
                'can_assign'                 => $canAssign,
                'conflict_details'           => $conflictDetails,
                'total_assignments'          => (int) ($assignmentCounts[$referee->id] ?? 0),
            ];
        });

        $sorted      = $refereesWithStatus->values();
        $statusCounts = [
            'available'             => $refereesWithStatus->where('status', 'available')->count(),
            'assigned_to_this_slot' => $refereesWithStatus->where('status', 'assigned_to_this_slot')->count(),
            'needs_rest'            => $refereesWithStatus->where('status', 'needs_rest')->count(),
            'time_conflict'         => $refereesWithStatus->where('status', 'time_conflict')->count(),
        ];

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Referees with availability status fetched successfully.',
            'data'    => [
                'slot_info' => [
                    'slot_id'    => $slot->id,
                    'court_name' => $slot->court_name,
                    'date'       => $slot->game_date,
                    'start_time' => Carbon::parse($slot->start_time)->format('h:i A'),
                    'end_time'   => Carbon::parse($slot->end_time)->format('h:i A'),
                ],
                'total_checked_in' => $allReferees->count(),
                'status_summary'   => $statusCounts,
                'referees'         => $sorted,
            ],
        ];
    }

    /**
     * Remove an assignment (individual referee or entire crew).
     * Sends notification to affected referees.
     *
     * @param  mixed $user
     * @param  int   $assignmentId
     * @return array
     */
    public function removeAssignment($user, int $assignmentId): array
    {
        $assignment = GameSlotAssignment::with([
            'gameSlot.schedule.camp',
            'gameSlot.location',
            'assignable',
        ])->find($assignmentId);

        if (!$assignment) {
            return ['success' => false, 'code' => 404, 'message' => 'Assignment not found.', 'data' => null];
        }

        $camp = $assignment->gameSlot?->schedule?->camp;

        if (!$camp) {
            return ['success' => false, 'code' => 404, 'message' => 'Camp not found for this game slot.', 'data' => null];
        }

        if ($camp->director_id !== $user->id) {
            $permission = AssistantDirectorPermission::where('camp_id', $camp->id)
                ->where('assistant_director_id', $user->id)
                ->first();

            if (!$permission) {
                return ['success' => false, 'code' => 403, 'message' => 'Unauthorized.', 'data' => null];
            }

            if (!$permission->assign_referees) {
                return ['success' => false, 'code' => 403, 'message' => 'You do not have permission to remove referees for this schedule.', 'data' => null];
            }
        }

        DB::beginTransaction();
        try {
            $gameSlot       = $assignment->gameSlot;
            $camp           = $gameSlot->schedule->camp;
            $refereesToNotify = collect();
            $assignmentType = $assignment->assignment_type;
            $crewName       = null;

            if ($assignmentType === 'crew') {
                $crew     = $assignment->assignable;
                $crewName = $crew->name;
                $refereesToNotify = $crew->members;

                Log::info('Crew assignment removed', [
                    'director_id'  => $user->id,
                    'crew_id'      => $crew->id,
                    'crew_name'    => $crewName,
                    'slot_id'      => $gameSlot->id,
                    'members_count' => $refereesToNotify->count(),
                ]);
            } else {
                $referee = $assignment->assignable;
                $refereesToNotify->push($referee);

                Log::info('Individual referee assignment removed', [
                    'director_id'  => $user->id,
                    'referee_id'   => $referee->id,
                    'referee_name' => "{$referee->first_name} {$referee->last_name}",
                    'slot_id'      => $gameSlot->id,
                ]);
            }

            $assignment->delete();

            $remainingAssignments = GameSlotAssignment::where('game_slot_id', $gameSlot->id)->count();

            if ($remainingAssignments === 0) {
                $gameSlot->update(['status' => 'available']);
            }

            if ($refereesToNotify->isNotEmpty() && $assignment->gameSlot->schedule->status === 'published') {
                Notification::send(
                    $refereesToNotify,
                    new RefereeRemoveFromCourtNotification(
                        $gameSlot,
                        $camp,
                        $user,
                        $assignmentType,
                        $crewName,
                        'Assignment removed by director'
                    )
                );
            }

            DB::commit();

            $responseData = [
                'assignment_id'         => $assignmentId,
                'assignment_type'       => $assignmentType,
                'slot_id'               => $gameSlot->id,
                'court_name'            => $gameSlot->court_name,
                'notifications_sent'    => $refereesToNotify->count(),
                'remaining_assignments' => $remainingAssignments,
                'slot_status'           => $remainingAssignments === 0 ? 'available' : 'assigned',
            ];

            if ($assignmentType === 'crew') {
                $responseData['crew_name']       = $crewName;
                $responseData['affected_members'] = $refereesToNotify->count();
            } else {
                $responseData['referee_name'] = $refereesToNotify->first()->first_name . ' ' .
                    $refereesToNotify->first()->last_name;
            }

            return [
                'success' => true,
                'code'    => 200,
                'message' => 'Assignment removed successfully. Notifications sent to affected referee(s).',
                'data'    => $responseData,
            ];
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to remove assignment', [
                'error'         => $e->getMessage(),
                'assignment_id' => $assignmentId,
                'trace'         => $e->getTraceAsString(),
            ]);
            return ['success' => false, 'code' => 500, 'message' => 'Failed to remove assignment: ' . $e->getMessage(), 'data' => null];
        }
    }

    /**
     * Clear all assignments for a schedule.
     *
     * @param  mixed $user
     * @param  int   $scheduleId
     * @return array
     */
    public function clearScheduleAssignments($user, int $scheduleId): array
    {
        $schedule = Schedule::with('camp')->find($scheduleId);

        if (!$schedule) {
            return ['success' => false, 'code' => 404, 'message' => 'Schedule not found.', 'data' => null];
        }

        if ($schedule->camp->director_id !== $user->id) {
            $permission = AssistantDirectorPermission::where('camp_id', $schedule->camp->id)
                ->where('assistant_director_id', $user->id)
                ->first();

            if (!$permission) {
                return ['success' => false, 'code' => 403, 'message' => 'Unauthorized. You can only clear assignments for your own camp.', 'data' => null];
            }

            if (!$permission->build_schedule) {
                return ['success' => false, 'code' => 403, 'message' => 'You do not have permission to clear schedule for this camp.', 'data' => null];
            }
        }

        $slotCount = GameSlot::where('schedule_id', $scheduleId)->count();

        if ($slotCount === 0) {
            return ['success' => false, 'code' => 404, 'message' => 'No game slots found for this schedule.', 'data' => null];
        }

        $assignmentCount = GameSlotAssignment::whereHas('gameSlot', function ($q) use ($scheduleId) {
            $q->where('schedule_id', $scheduleId);
        })->count();

        if ($assignmentCount === 0) {
            return [
                'success' => true,
                'code'    => 200,
                'message' => 'No assignments to clear. All slots are already available.',
                'data'    => [
                    'cleared_count' => 0,
                    'schedule_id'   => $scheduleId,
                    'slot_count'    => $slotCount,
                ],
            ];
        }

        DB::beginTransaction();
        try {
            $deleted = GameSlotAssignment::whereHas('gameSlot', function ($q) use ($scheduleId) {
                $q->where('schedule_id', $scheduleId);
            })->delete();

            GameSlot::where('schedule_id', $scheduleId)
                ->where('status', 'assigned')
                ->update(['status' => 'available']);

            DB::commit();

            return [
                'success' => true,
                'code'    => 200,
                'message' => 'All assignments cleared successfully.',
                'data'    => [
                    'cleared_count'  => $deleted,
                    'schedule_id'    => $scheduleId,
                    'slot_count'     => $slotCount,
                    'available_slots' => $slotCount,
                ],
            ];
        } catch (Exception $e) {
            DB::rollBack();
            return ['success' => false, 'code' => 500, 'message' => 'Failed to clear assignments: ' . $e->getMessage(), 'data' => null];
        }
    }

    /**
     * Get all assignments for a specific slot.
     *
     * @param  mixed $user
     * @param  int   $slotId
     * @return array
     */
    public function getSlotAssignments($user, int $slotId): array
    {
        $slot = GameSlot::with('schedule.camp')->findOrFail($slotId);

        if ($slot->schedule->camp->director_id !== $user->id) {
            return ['success' => false, 'code' => 403, 'message' => 'Unauthorized.', 'data' => null];
        }

        $assignments = GameSlotAssignment::where('game_slot_id', $slotId)
            ->with(['assignable'])
            ->get()
            ->map(function ($assignment) {
                if ($assignment->assignment_type === 'crew') {
                    return [
                        'assignment_id'    => $assignment->id,
                        'type'             => 'crew',
                        'crew'             => [
                            'id'      => $assignment->assignable->id,
                            'name'    => $assignment->assignable->name,
                            'members' => $assignment->assignable->members->map(fn($m) => [
                                'id'     => $m->id,
                                'name'   => $m->first_name . ' ' . $m->last_name,
                                'avatar' => $m->avatar ? asset($m->avatar) : null,
                            ]),
                        ],
                        'is_auto_assigned' => $assignment->is_auto_assigned,
                    ];
                } else {
                    return [
                        'assignment_id'    => $assignment->id,
                        'type'             => 'individual',
                        'referee'          => [
                            'id'     => $assignment->assignable->id,
                            'name'   => $assignment->assignable->first_name . ' ' . $assignment->assignable->last_name,
                            'email'  => $assignment->assignable->email,
                            'avatar' => $assignment->assignable->avatar ? asset($assignment->assignable->avatar) : null,
                        ],
                        'is_auto_assigned' => $assignment->is_auto_assigned,
                    ];
                }
            });

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Slot assignments fetched successfully.',
            'data'    => [
                'slot_id'           => $slotId,
                'court_name'        => $slot->court_name,
                'total_assignments' => $assignments->count(),
                'assignments'       => $assignments,
            ],
        ];
    }

    /**
     * Get available referees for a camp (checked-in but not assigned anywhere).
     *
     * @param  mixed $user
     * @param  int   $campId
     * @return array
     */
    public function getAvailableReferees($user, int $campId): array
    {
        $camp = Camp::where('id', $campId)
            ->where('director_id', $user->id)
            ->first();

        if (!$camp) {
            return ['success' => false, 'code' => 404, 'message' => 'Camp not found.', 'data' => null];
        }

        $checkedInRefereeIds = CampRefereeCheckin::where('camp_id', $campId)->pluck('referee_id');

        $assignedRefereeIds = GameSlotAssignment::whereHas('gameSlot.schedule', function ($q) use ($campId) {
            $q->where('camp_id', $campId);
        })
            ->where('assignment_type', 'individual')
            ->where('assignable_type', User::class)
            ->pluck('assignable_id')
            ->toArray();

        $availableRefereeIds = $checkedInRefereeIds->diff($assignedRefereeIds);

        $referees = User::whereIn('id', $availableRefereeIds)
            ->select('id', 'first_name', 'last_name', 'email', 'avatar')
            ->get()
            ->map(function ($ref) {
                return [
                    'id'     => $ref->id,
                    'name'   => $ref->first_name . ' ' . $ref->last_name,
                    'email'  => $ref->email,
                    'avatar' => $ref->avatar ? asset($ref->avatar) : asset('default/profile.jpg'),
                ];
            });

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Available referees fetched successfully.',
            'data'    => [
                'total'   => $referees->count(),
                'referees' => $referees,
            ],
        ];
    }

    /**
     * Get assigned referees or crew for a slot.
     *
     * @param  mixed $user
     * @param  int   $slotId
     * @return array
     */
    public function getAssignedRefereesOrCrew($user, int $slotId): array
    {
        $slot = GameSlot::with('schedule.camp')->findOrFail($slotId);

        if ($slot->schedule->camp->director_id !== $user->id) {
            return ['success' => false, 'code' => 403, 'message' => 'Unauthorized.', 'data' => null];
        }

        $court = [
            'court_id'   => $slot->id,
            'court_name' => $slot->court_name ?? 'Unknown',
        ];

        $individualAssignments = GameSlotAssignment::with([
            'assignable' => fn($q) => $q->select('id', 'first_name', 'last_name', 'email', 'avatar'),
        ])
            ->where('game_slot_id', $slotId)
            ->where('assignment_type', 'individual')
            ->get();

        $crewAssignments = GameSlotAssignment::with([
            'assignable.members.referee' => fn($q) => $q->select('id', 'first_name', 'last_name', 'email', 'avatar'),
        ])
            ->where('game_slot_id', $slotId)
            ->where('assignment_type', 'crew')
            ->get();

        $referees = $individualAssignments->map(function ($item) {
            $referee = $item->assignable;
            return [
                'assignment_id' => $item->id,
                'referee'       => [
                    'referee_id' => $referee->id,
                    'name'       => trim("{$referee->first_name} {$referee->last_name}"),
                    'email'      => $referee->email,
                    'avatar'     => $referee->avatar ? asset($referee->avatar) : asset('default/profile.jpg'),
                ],
            ];
        })->values();

        $crewData = null;
        if ($crewAssignments->isNotEmpty()) {
            $crewAssignment = $crewAssignments->first();
            $crew           = $crewAssignment->assignable;

            $crewData = [
                'assignment_id' => $crewAssignment->id,
                'crew'          => [
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
                            'avatar'     => $ref->avatar ? asset($ref->avatar) : asset('default/profile.jpg'),
                        ];
                    })->values(),
                ],
            ];
        }

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Assigned referees and crew fetched successfully.',
            'data'    => [
                'court'   => $court,
                'referees' => $referees->isNotEmpty() ? $referees : [],
                'crew'    => $crewData,
            ],
        ];
    }

    /**
     * Get all crews for a specific slot with availability status.
     *
     * @param  mixed $user
     * @param  int   $slotId
     * @return array
     */
    public function getAvailableCrewsForSlot($user, int $slotId): array
    {
        $slot = GameSlot::with('schedule.camp')->find($slotId);

        if (!$slot) {
            return ['success' => false, 'code' => 404, 'message' => 'Game court not found!', 'data' => []];
        }

        $campId = $slot->schedule?->camp?->id;
        $camp   = Camp::forDirectorOrAssistant($user->id)->find($campId);

        if (!$camp) {
            return ['success' => false, 'code' => 404, 'message' => 'Camp not found.', 'data' => []];
        }

        if ($camp->director_id !== $user->id) {
            $permission = AssistantDirectorPermission::where('camp_id', $camp->id)
                ->where('assistant_director_id', $user->id)
                ->first();

            if (!$permission) {
                return ['success' => false, 'code' => 403, 'message' => 'Unauthorized to approve this registration.', 'data' => []];
            }
        }

        $crewAssignmentCounts = GameSlotAssignment::where('assignable_type', Crew::class)
            ->where('assignment_type', 'crew')
            ->whereHas('gameSlot.schedule', function ($q) use ($slot) {
                $q->where('camp_id', $slot->schedule->camp_id);
            })
            ->select('assignable_id', DB::raw('count(*) as total'))
            ->groupBy('assignable_id')
            ->pluck('total', 'assignable_id');

        $allCrews = Crew::where('camp_id', $slot->schedule->camp_id)
            ->where('status', 'active')
            ->withCount('members')
            ->with(['members' => function ($query) {
                $query->select('users.id', 'users.first_name', 'users.last_name', 'users.email', 'users.avatar');
            }])
            ->get();

        $assignedCrewId = GameSlotAssignment::where('game_slot_id', $slotId)
            ->where('assignment_type', 'crew')
            ->where('assignable_type', Crew::class)
            ->value('assignable_id');

        $crewsWithStatus = $allCrews->map(function ($crew) use ($slot, $assignedCrewId, $crewAssignmentCounts) {
            $isAssignedToThisSlot = ($assignedCrewId && $crew->id == (int) $assignedCrewId);

            $hasTimeConflict = GameSlotAssignment::hasTimeConflict($crew->id, Crew::class, $slot);
            $needsRest       = GameSlotAssignment::needsRest($crew->id, Crew::class, $slot);

            $status        = 'available';
            $statusMessage = 'Available for assignment';
            $canAssign     = true;

            if ($isAssignedToThisSlot) {
                $status        = 'assigned_to_this_slot';
                $statusMessage = 'Already assigned to this slot';
                $canAssign     = false;
            } elseif ($hasTimeConflict) {
                $status        = 'time_conflict';
                $statusMessage = 'Already assigned to another court at this time';
                $canAssign     = false;
            } elseif ($needsRest) {
                $status        = 'needs_rest';
                $statusMessage = 'Played in previous slot - needs rest';
                $canAssign     = false;
            }

            $conflictDetails = null;
            if ($hasTimeConflict) {
                $conflictingAssignments = GameSlotAssignment::getConflictingSlots($crew->id, Crew::class, $slot);

                if ($conflictingAssignments->isNotEmpty()) {
                    $conflictSlot    = $conflictingAssignments->first()->gameSlot;
                    $conflictDetails = [
                        'court_name' => $conflictSlot->court_name,
                        'time'       => Carbon::parse($conflictSlot->start_time)->format('h:i A') . ' - ' .
                            Carbon::parse($conflictSlot->end_time)->format('h:i A'),
                    ];
                }
            }

            return [
                'id'                => $crew->id,
                'name'              => $crew->name,
                'description'       => $crew->description,
                'status'            => $status,
                'status_message'    => $statusMessage,
                'can_assign'        => $canAssign,
                'conflict_details'  => $conflictDetails,
                'member_count'      => $crew->members_count,
                'total_assignments' => (int) ($crewAssignmentCounts[$crew->id] ?? 0),
                'members'           => $crew->members->map(function ($member) {
                    return [
                        'id'        => $member->id,
                        'name'      => $member->first_name . ' ' . $member->last_name,
                        'email'     => $member->email,
                        'avatar'    => $member->avatar ? asset($member->avatar) : asset('default/profile.jpg'),
                        'position'  => $member->pivot->position ?? null,
                        'joined_at' => $member->pivot->joined_at,
                    ];
                }),
                'created_at'        => $crew->created_at->format('Y-m-d H:i:s'),
            ];
        });

        // Sort: assigned first, then time_conflict, needs_rest, then available
        $sorted = $crewsWithStatus->sort(function ($a, $b) {
            $order = [
                'assigned_to_this_slot' => 1,
                'time_conflict'         => 2,
                'needs_rest'            => 3,
                'available'             => 4,
            ];
            return ($order[$a['status']] ?? 5) <=> ($order[$b['status']] ?? 5);
        })->values();

        $statusCounts = [
            'available'             => $crewsWithStatus->where('status', 'available')->count(),
            'assigned_to_this_slot' => $crewsWithStatus->where('status', 'assigned_to_this_slot')->count(),
            'needs_rest'            => $crewsWithStatus->where('status', 'needs_rest')->count(),
            'time_conflict'         => $crewsWithStatus->where('status', 'time_conflict')->count(),
        ];

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Crews with availability status fetched successfully.',
            'data'    => [
                'slot_info' => [
                    'slot_id'    => $slot->id,
                    'court_name' => $slot->court_name,
                    'date'       => $slot->game_date,
                    'start_time' => Carbon::parse($slot->start_time)->format('h:i A'),
                    'end_time'   => Carbon::parse($slot->end_time)->format('h:i A'),
                ],
                'total_crews'    => $allCrews->count(),
                'status_summary' => $statusCounts,
                'crews'          => $sorted,
            ],
        ];
    }

    /**
     * Switch a single slot's mode between crew and individual.
     *
     * @param  mixed   $user
     * @param  int     $slotId
     * @param  ?string $targetMode
     * @return array
     */
    public function switchMode($user, int $slotId, ?string $targetMode): array
    {
        $slot = GameSlot::with('schedule.camp')->find($slotId);

        if (!$slot) {
            return ['success' => false, 'code' => 404, 'message' => 'Game slot not found!', 'data' => null];
        }

        $camp = $slot->schedule?->camp;

        if (!$camp) {
            return ['success' => false, 'code' => 404, 'message' => 'Camp not found for this game slot.', 'data' => null];
        }

        if ($camp->director_id !== $user->id) {
            $permission = AssistantDirectorPermission::where('camp_id', $camp->id)
                ->where('assistant_director_id', $user->id)
                ->first();

            if (!$permission) {
                return ['success' => false, 'code' => 403, 'message' => 'Unauthorized.', 'data' => null];
            }

            if (!$permission->assign_referees) {
                return ['success' => false, 'code' => 403, 'message' => 'You do not have permission to modify this schedule slot mode.', 'data' => null];
            }
        }

        // Toggle if no explicit mode provided
        if (!$targetMode) {
            $targetMode = ($slot->mode === 'crew') ? 'individual' : 'crew';
        }

        $slot->update(['mode' => $targetMode]);

        return [
            'success' => true,
            'code'    => 200,
            'message' => "Game slot mode switched to {$targetMode} successfully.",
            'data'    => [
                'slot_id'   => $slot->id,
                'mode'      => $slot->mode,
                'court_name' => $slot->court_name,
                'game_date' => $slot->game_date,
            ],
        ];
    }

    /**
     * Bulk switch mode for all unassigned game slots in a schedule.
     * Slots with assigned referees/crews will be skipped.
     *
     * @param  mixed  $user
     * @param  int    $campId
     * @param  int    $scheduleId
     * @param  string $targetMode
     * @return array
     */
    public function bulkSwitchMode($user, int $campId, int $scheduleId, string $targetMode): array
    {
        $schedule = Schedule::with('camp')
            ->where('id', $scheduleId)
            ->where('camp_id', $campId)
            ->first();

        if (!$schedule) {
            return ['success' => false, 'code' => 404, 'message' => 'Schedule not found for this camp!', 'data' => null];
        }

        $camp = $schedule->camp;

        if (!$camp) {
            return ['success' => false, 'code' => 404, 'message' => 'Camp not found for this schedule.', 'data' => null];
        }

        if ($camp->director_id !== $user->id) {
            $permission = AssistantDirectorPermission::where('camp_id', $camp->id)
                ->where('assistant_director_id', $user->id)
                ->first();

            if (!$permission) {
                return ['success' => false, 'code' => 403, 'message' => 'Unauthorized.', 'data' => null];
            }

            if (!$permission->assign_referees) {
                return ['success' => false, 'code' => 403, 'message' => 'You do not have permission to modify schedule slot modes.', 'data' => null];
            }
        }

        $allSlots = GameSlot::where('schedule_id', $schedule->id)->get();

        if ($allSlots->isEmpty()) {
            return ['success' => false, 'code' => 404, 'message' => 'No game slots found for this schedule.', 'data' => null];
        }

        $allSlotIds = $allSlots->pluck('id');

        $assignedSlotIds = GameSlotAssignment::whereIn('game_slot_id', $allSlotIds)
            ->pluck('game_slot_id')
            ->unique()
            ->toArray();

        $unassignedSlotIds = $allSlotIds->diff($assignedSlotIds);
        $updatedCount      = 0;
        $skippedCount      = count($assignedSlotIds);

        if ($unassignedSlotIds->isNotEmpty()) {
            $updatedCount = GameSlot::whereIn('id', $unassignedSlotIds)
                ->update(['mode' => $targetMode]);
        }

        return [
            'success' => true,
            'code'    => 200,
            'message' => "Bulk slot mode updated to '{$targetMode}'. {$updatedCount} slot(s) updated, {$skippedCount} assigned slot(s) skipped.",
            'data'    => [
                'schedule_id'                => $schedule->id,
                'camp_id'                    => $camp->id,
                'mode'                       => $targetMode,
                'total_slots'                => $allSlots->count(),
                'updated_slots_count'        => $updatedCount,
                'skipped_assigned_slots_count' => $skippedCount,
            ],
        ];
    }

    /**
     * Helper: Get all referees assigned to a slot (individual only).
     *
     * @param  int $slotId
     * @return mixed
     */
    private function getSlotReferees(int $slotId)
    {
        $assignments = GameSlotAssignment::with('assignable')
            ->where('game_slot_id', $slotId)
            ->where('assignment_type', 'individual')
            ->where('assignable_type', User::class)
            ->get();

        return GameSlotRefereeResource::collection($assignments);
    }
}
