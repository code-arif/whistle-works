<?php

namespace App\Services\Api\Referee;

use App\Models\AssistantDirectorPermission;
use App\Models\CampEvaluatorRegistration;
use App\Models\CampRefereeJearsyNumber;
use App\Models\RefereeEvaluation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Director\Models\Camp;
use Modules\Director\Models\CampRefereeCheckin;
use Modules\Director\Models\Crew;
use Modules\Director\Models\GameSlotAssignment;

class RefereeDetailsService
{
    /**
     * Get comprehensive referee details for a specific camp.
     *
     * @param  User  $user
     * @param  int|string  $campId
     * @param  int|string  $refereeId
     * @return array
     */
    public function getRefereeDetails(User $user, $campId, $refereeId): array
    {
        if (!$user->hasAnyRole(['director', 'evaluator', 'referee'])) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'Unauthorized access.',
                'data'    => [],
            ];
        }

        $camp = Camp::find($campId);
        if (!$camp) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Camp not found.',
                'data'    => [],
            ];
        }

        // Director or Assistant Director permission check
        if ($user->hasRole('director')) {
            $isOwner = $camp->director_id === $user->id;
            $isAssistant = AssistantDirectorPermission::where('camp_id', $camp->id)
                ->where('assistant_director_id', $user->id)
                ->exists();

            if (!$isOwner && !$isAssistant) {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => 'You can only view referee details for your own or assigned camps.',
                    'data'    => [],
                ];
            }
        }

        $referee = User::find($refereeId);
        if (!$referee || !$referee->hasRole('referee')) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Referee not found.',
                'data'    => [],
            ];
        }

        $checkin = CampRefereeCheckin::where('camp_id', $campId)
            ->where('referee_id', $refereeId)
            ->first();

        if (!$checkin) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Referee is not registered for this camp.',
                'data'    => [],
            ];
        }

        // Evaluator permission check
        if ($user->hasRole('evaluator') && !$user->hasRole('director')) {
            $registration = CampEvaluatorRegistration::where('camp_id', $campId)
                ->where('evaluator_id', $user->id)
                ->where('status', 'approved')
                ->first();

            if (!$registration) {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => 'You must be registered and approved for this camp.',
                    'data'    => [],
                ];
            }
        }

        // Referee permission check
        if ($user->hasRole('referee') && !$user->hasRole('director')) {
            $refereeRegistration = CampRefereeCheckin::where('camp_id', $campId)
                ->where('referee_id', $user->id)
                ->first();

            if (!$refereeRegistration) {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => 'You must be registered for this camp.',
                    'data'    => [],
                ];
            }
        }

        // 1. Referee Profile
        $jerseyNumber = CampRefereeJearsyNumber::forCampAndReferee($campId, $refereeId)
            ->value('jersey_number');

        $profile = [
            'id'                  => $referee->id,
            'first_name'          => $referee->first_name,
            'last_name'           => $referee->last_name,
            'email'               => $referee->email,
            'phone'               => $referee->phone,
            'address'             => $referee->address ?? '',
            'avatar'              => $referee->avatar ? asset($referee->avatar) : asset('default/profile.jpg'),
            'biography'           => $referee->biography ?? '',
            'jersey_number'       => $jerseyNumber,
            'registration_status' => $checkin->registration_status,
            'checked_in_at'       => $checkin->checked_in_at?->format('Y-m-d H:i:s'),
        ];

        // 2. Evaluation History & Statistics
        $evaluations = RefereeEvaluation::with(['evaluator', 'gameSlot', 'recommendedLevels'])
            ->where('camp_id', $campId)
            ->where('referee_id', $refereeId)
            ->orderBy('submitted_at', 'desc')
            ->get();

        $statistics = null;
        if ($evaluations->isNotEmpty()) {
            $avgCallAccuracy = round($evaluations->avg('call_accuracy'), 3);
            $avgCommunication = round($evaluations->avg('communication_skills'), 3);
            $avgConsistency = round($evaluations->avg('consistency_of_calls'), 3);
            $avgCourtPosition = round($evaluations->avg('court_position_mechanics'), 3);
            $avgFitness = round($evaluations->avg('fitness_mobility'), 3);
            $avgGameAwareness = round($evaluations->avg('game_awareness'), 3);

            $overallAvg = round(($avgCallAccuracy + $avgCommunication + $avgConsistency +
                $avgCourtPosition + $avgFitness + $avgGameAwareness) / 6, 3);

            $recommendedLevels = [];
            foreach ($evaluations as $evaluation) {
                foreach ($evaluation->recommendedLevels as $level) {
                    $key = $level->level;
                    $recommendedLevels[$key] = ($recommendedLevels[$key] ?? 0) + 1;
                }
            }

            $recommendedLevelsFormatted = [];
            foreach ($recommendedLevels as $level => $count) {
                $recommendedLevelsFormatted[] = [
                    'level' => $level,
                    'count' => $count,
                ];
            }
            usort($recommendedLevelsFormatted, fn($a, $b) => $b['count'] <=> $a['count']);

            $statistics = [
                'total_evaluations'         => $evaluations->count(),
                'unique_evaluators'         => $evaluations->pluck('evaluator_id')->unique()->count(),
                'averages'                  => [
                    'overall'                  => $overallAvg,
                    'call_accuracy'            => $avgCallAccuracy,
                    'communication_skills'     => $avgCommunication,
                    'consistency_of_calls'     => $avgConsistency,
                    'court_position_mechanics' => $avgCourtPosition,
                    'fitness_mobility'         => $avgFitness,
                    'game_awareness'           => $avgGameAwareness,
                ],
                'recommended_levels'        => $recommendedLevelsFormatted,
                'highest_recommended_level' => !empty($recommendedLevelsFormatted)
                    ? $recommendedLevelsFormatted[0]['level']
                    : null,
            ];
        }

        $formattedEvaluations = $evaluations->map(function ($evaluation) {
            return [
                'id'        => $evaluation->id,
                'evaluator' => [
                    'id'    => $evaluation->evaluator_id,
                    'name'  => trim(($evaluation->evaluator->first_name ?? '') . ' ' . ($evaluation->evaluator->last_name ?? '')),
                    'email' => $evaluation->evaluator->email ?? '',
                    'role'  => $evaluation->evaluator?->getRoleNames()->first(),
                ],
                'game_slot' => $evaluation->gameSlot ? [
                    'id'         => $evaluation->gameSlot->id,
                    'date'       => $evaluation->gameSlot->game_date?->toDateString(),
                    'time'       => $evaluation->gameSlot->start_time . ' - ' . $evaluation->gameSlot->end_time,
                    'court_name' => $evaluation->gameSlot->court_name ?? 'N/A',
                ] : null,
                'scores' => [
                    'call_accuracy'            => $evaluation->call_accuracy,
                    'communication_skills'     => $evaluation->communication_skills,
                    'consistency_of_calls'     => $evaluation->consistency_of_calls,
                    'court_position_mechanics' => $evaluation->court_position_mechanics,
                    'fitness_mobility'         => $evaluation->fitness_mobility,
                    'game_awareness'           => $evaluation->game_awareness,
                ],
                'total_score'       => (float) $evaluation->total_score,
                'average_score'     => (float) $evaluation->average_score,
                'max_score'         => 60,
                'percentage'        => $evaluation->total_score
                    ? round(($evaluation->total_score / 60) * 100, 2)
                    : 0,
                'recommended_level' => $evaluation->relationLoaded('recommendedLevels')
                    && $evaluation->recommendedLevels->isNotEmpty()
                        ? $evaluation->recommendedLevels->first()->level
                        : null,
                'referee_feedback'  => $evaluation->referee_feedback,
                'status'            => $evaluation->status,
                'submitted_at'      => $evaluation->submitted_at?->toDateString(),
                'created_at'        => $evaluation->created_at->toDateString(),
            ];
        })->values();

        // 3. Assigned Game Slots
        $assignedSlots = $this->getRefereeAssignedSlots($campId, $refereeId);

        // 4. Build Response
        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Referee details retrieved successfully.',
            'data'    => [
                'camp'           => [
                    'id'         => $camp->id,
                    'name'       => $camp->camp_name,
                    'location'   => $camp->location,
                    'start_date' => $camp->start_date?->toDateString(),
                    'end_date'   => $camp->end_date?->toDateString(),
                    'address'    => $camp->address,
                    'timezone'   => $camp->timezone,
                    'logo'       => $camp->camp_logo ? asset($camp->camp_logo) : asset('default/no_image.webp'),
                ],
                'referee'        => $profile,
                'statistics'     => $statistics,
                'evaluations'    => $formattedEvaluations,
                'assigned_slots' => $assignedSlots,
            ],
        ];
    }

    /**
     * Get all game slots assigned to a specific referee in a camp (individual + crew).
     */
    private function getRefereeAssignedSlots($campId, $refereeId): array
    {
        $individualAssignments = GameSlotAssignment::with([
            'gameSlot.location',
            'gameSlot.schedule',
            'gameSlot.slotAssignments.assignable',
        ])
            ->where('assignable_type', User::class)
            ->where('assignable_id', $refereeId)
            ->where('assignment_type', 'individual')
            ->whereHas('gameSlot.schedule', fn($q) => $q->where('camp_id', $campId))
            ->get();

        $crewIds = DB::table('crew_members')
            ->where('referee_id', $refereeId)
            ->pluck('crew_id');

        $crewAssignments = collect();
        if ($crewIds->isNotEmpty()) {
            $crewAssignments = GameSlotAssignment::with([
                'gameSlot.location',
                'gameSlot.schedule',
                'gameSlot.slotAssignments.assignable',
            ])
                ->where('assignable_type', Crew::class)
                ->whereIn('assignable_id', $crewIds)
                ->where('assignment_type', 'crew')
                ->whereHas('gameSlot.schedule', fn($q) => $q->where('camp_id', $campId))
                ->get();
        }

        $allAssignments = $individualAssignments->concat($crewAssignments)
            ->sortBy(fn($a) => $a->gameSlot->game_date?->toDateString() . ' ' . $a->gameSlot->start_time)
            ->values();

        if ($allAssignments->isEmpty()) {
            return [
                'total_slots' => 0,
                'slots'       => [],
            ];
        }

        $seenSlotIds = [];
        $slots = [];

        foreach ($allAssignments as $assignment) {
            $gameSlot = $assignment->gameSlot;
            if (in_array($gameSlot->id, $seenSlotIds, true)) {
                continue;
            }
            $seenSlotIds[] = $gameSlot->id;

            $allSlotAssignments = $gameSlot->slotAssignments;
            $referees = [];

            foreach ($allSlotAssignments as $slotAssignment) {
                if ($slotAssignment->assignment_type === 'crew') {
                    $crew = $slotAssignment->assignable;
                    if ($crew && $crew->members) {
                        foreach ($crew->members as $member) {
                            $referees[] = [
                                'id'                 => $member->id,
                                'name'               => $member->first_name . ' ' . $member->last_name,
                                'avatar'             => $member->avatar ? asset($member->avatar) : asset('default/profile.jpg'),
                                'email'              => $member->email,
                                'type'               => 'crew_member',
                                'crew_name'          => $crew->crew_name ?? 'N/A',
                                'is_current_referee' => $member->id === (int) $refereeId,
                            ];
                        }
                    }
                } else {
                    $ref = $slotAssignment->assignable;
                    if (!$ref) {
                        continue;
                    }
                    $referees[] = [
                        'id'                 => $ref->id,
                        'name'               => $ref->first_name . ' ' . $ref->last_name,
                        'avatar'             => $ref->avatar ? asset($ref->avatar) : asset('default/profile.jpg'),
                        'email'              => $ref->email,
                        'type'               => 'individual',
                        'is_current_referee' => $ref->id === (int) $refereeId,
                    ];
                }
            }

            $assignmentSource = $assignment->assignment_type === 'crew' ? 'crew' : 'individual';
            $crewNameIfVia    = ($assignmentSource === 'crew') ? ($assignment->assignable->crew_name ?? null) : null;

            $slots[] = [
                'game_slot_id'      => $gameSlot->id,
                'assignment_source' => $assignmentSource,
                'crew_name'         => $crewNameIfVia,
                'game_details'      => [
                    'date'         => $gameSlot->game_date?->toDateString(),
                    'start_time'   => $gameSlot->start_time,
                    'end_time'     => $gameSlot->end_time,
                    'court_name'   => $gameSlot->court_name,
                    'court_number' => $gameSlot->court_number,
                    'status'       => $gameSlot->status,
                    'is_blocked'   => (bool) $gameSlot->is_block,
                ],
                'location' => [
                    'id'        => $gameSlot->location?->id,
                    'name'      => $gameSlot->location?->location_name ?? 'N/A',
                    'latitude'  => $gameSlot->location?->latitude,
                    'longitude' => $gameSlot->location?->longitude,
                    'address'   => $gameSlot->location?->address,
                ],
                'position'          => $assignment->position,
                'assigned_at'       => $assignment->assigned_at?->format('Y-m-d H:i:s'),
                'is_auto_assigned'  => (bool) $assignment->is_auto_assigned,
                'assigned_referees' => [
                    'total'    => count($referees),
                    'referees' => $referees,
                ],
            ];
        }

        return [
            'total_slots' => count($slots),
            'slots'       => $slots,
        ];
    }
}
