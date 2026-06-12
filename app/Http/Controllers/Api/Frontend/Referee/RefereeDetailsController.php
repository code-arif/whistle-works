<?php

namespace App\Http\Controllers\Api\Frontend\Referee;

use App\Http\Controllers\Controller;
use App\Models\CampEvaluatorRegistration;
use App\Models\CampRefereeJearsyNumber;
use App\Models\RefereeEvaluation;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Director\Models\Camp;
use Modules\Director\Models\CampRefereeCheckin;
use Modules\Director\Models\Crew;
use Modules\Director\Models\GameSlotAssignment;

class RefereeDetailsController extends Controller
{
    use ApiResponse;

    /**
     * Get comprehensive referee details for a specific camp.
     *
     * Returns: referee profile, evaluation statistics, assigned game slots.
     */
    public function getRefereeDetails(Request $request, $campId, $refereeId)
    {
        $user = auth('api')->user();

        // Only directors, evaluators, and referees can access
        if (!$user->hasAnyRole(['director', 'evaluator', 'referee'])) {
            return $this->error([], 'Unauthorized access.', 403);
        }

        // Check if camp exists
        $camp = Camp::find($campId);
        if (!$camp) {
            return $this->error([], 'Camp not found.', 404);
        }

        // Check if referee exists and has referee role
        $referee = User::find($refereeId);
        if (!$referee || !$referee->hasRole('referee')) {
            return $this->error([], 'Referee not found.', 404);
        }

        // Check if referee is registered for this camp
        $checkin = CampRefereeCheckin::where('camp_id', $campId)
            ->where('referee_id', $refereeId)
            ->first();

        if (!$checkin) {
            return $this->error([], 'Referee is not registered for this camp.', 404);
        }

        // Director permission check
        if ($user->hasRole('director') && $camp->director_id !== $user->id) {
            return $this->error([], 'You can only view details from your own camps.', 403);
        }

        // Evaluator permission check
        if ($user->hasRole('evaluator') && !$user->hasRole('director')) {
            $registration = CampEvaluatorRegistration::where('camp_id', $campId)
                ->where('evaluator_id', $user->id)
                ->where('status', 'approved')
                ->first();

            if (!$registration) {
                return $this->error([], 'You must be registered and approved for this camp.', 403);
            }

            if (!$registration->can_view_own_evaluations) {
                return $this->error([], 'You do not have permission to view details for this camp. Contact the director.', 403);
            }
        }

        // Referee permission check — mirrors getRefereeEvaluationHistory pattern
        if ($user->hasRole('referee') && !$user->hasRole('director')) {
            $refereeRegistration = CampRefereeCheckin::where('camp_id', $campId)
                ->where('referee_id', $user->id)
                ->first();

            if (!$refereeRegistration) {
                return $this->error([], 'You must be registered for this camp.', 403);
            }

            if (!$camp->publish_ranking_for_referees) {
                return $this->error([], 'You do not have permission to view referee details for this camp. Contact the director.', 403);
            }
        }

        // 1. Referee Profile
        $jerseyNumber = CampRefereeJearsyNumber::forCampAndReferee($campId, $refereeId)
            ->value('jersey_number');

        $profile = [
            'id'            => $referee->id,
            'first_name'    => $referee->first_name,
            'last_name'     => $referee->last_name,
            'email'         => $referee->email,
            'phone'         => $referee->phone,
            'address'       => $referee->address ?? '',
            'avatar'        => $referee->avatar
                ? asset($referee->avatar)
                : asset('default/profile.jpg'),
            'biography'     => $referee->biography ?? '',
            'jersey_number' => $jerseyNumber,
            'registration_status' => $checkin->registration_status,
            'checked_in_at'       => $checkin->checked_in_at?->format('Y-m-d H:i:s'),
        ];

        // 2. Evaluation History & Statistics
        // Build evaluation query
        $evaluationQuery = RefereeEvaluation::with(['evaluator', 'gameSlot', 'recommendedLevels'])
            ->where('camp_id', $campId)
            ->where('referee_id', $refereeId);

        // Referees (and referees only) see only submitted evaluations
        if ($user->hasRole('referee')) {
            $evaluationQuery->where('status', 'submitted');
        }

        // Evaluators (non-director) can only see their own evaluations
        if ($user->hasRole('evaluator') && !$user->hasRole('director')) {
            $evaluationQuery->where('evaluator_id', $user->id);
        }

        $evaluations = $evaluationQuery->orderBy('submitted_at', 'desc')->get();

        // Statistics calculations (use all evaluations the user can see)
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

            // Recommended levels count
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
                'total_evaluations' => $evaluations->count(),
                'unique_evaluators' => $evaluations->pluck('evaluator_id')->unique()->count(),
                'averages' => [
                    'overall'              => $overallAvg,
                    'call_accuracy'        => $avgCallAccuracy,
                    'communication_skills' => $avgCommunication,
                    'consistency_of_calls' => $avgConsistency,
                    'court_position_mechanics' => $avgCourtPosition,
                    'fitness_mobility'     => $avgFitness,
                    'game_awareness'       => $avgGameAwareness,
                ],
                'recommended_levels'          => $recommendedLevelsFormatted,
                'highest_recommended_level'    => !empty($recommendedLevelsFormatted)
                    ? $recommendedLevelsFormatted[0]['level']
                    : null,
            ];
        }

        // Format individual evaluations
        $formattedEvaluations = $evaluations->map(function ($evaluation) {
            return [
                'id' => $evaluation->id,
                'evaluator' => [
                    'id'    => $evaluation->evaluator_id,
                    'name'  => $evaluation->evaluator->first_name . ' ' . $evaluation->evaluator->last_name,
                    'email' => $evaluation->evaluator->email,
                    'role'  => $evaluation->evaluator->getRoleNames()->first(),
                ],
                'game_slot' => $evaluation->gameSlot ? [
                    'id'         => $evaluation->gameSlot->id,
                    'date'       => $evaluation->gameSlot->game_date?->toDateString(),
                    'time'       => $evaluation->gameSlot->start_time . ' - ' . $evaluation->gameSlot->end_time,
                    'court_name' => $evaluation->gameSlot->court_name ?? 'N/A',
                ] : null,
                'scores' => [
                    'call_accuracy'           => $evaluation->call_accuracy,
                    'communication_skills'    => $evaluation->communication_skills,
                    'consistency_of_calls'    => $evaluation->consistency_of_calls,
                    'court_position_mechanics' => $evaluation->court_position_mechanics,
                    'fitness_mobility'        => $evaluation->fitness_mobility,
                    'game_awareness'          => $evaluation->game_awareness,
                ],
                'total_score'         => (float) $evaluation->total_score,
                'average_score'       => (float) $evaluation->average_score,
                'max_score'           => 60,
                'percentage'          => $evaluation->total_score
                    ? round(($evaluation->total_score / 60) * 100, 2)
                    : 0,
                'recommended_level'   => $evaluation->relationLoaded('recommendedLevels')
                    && $evaluation->recommendedLevels->isNotEmpty()
                        ? $evaluation->recommendedLevels->first()->level
                        : null,
                'referee_feedback'    => $evaluation->referee_feedback,
                'status'              => $evaluation->status,
                'submitted_at'        => $evaluation->submitted_at?->toDateString(),
                'created_at'          => $evaluation->created_at->toDateString(),
            ];
        })->values();

        // ── 3. Assigned Game Slots ──────────────────────────────────────────────
        $assignedSlots = $this->getRefereeAssignedSlots($campId, $refereeId);

        // ── 4. Build Response ────────────────────────────────────────────────────
        return $this->success(
            'Referee details retrieved successfully.',
            [
                'camp' => [
                    'id'         => $camp->id,
                    'name'       => $camp->camp_name,
                    'location'   => $camp->location,
                    'start_date' => $camp->start_date?->toDateString(),
                    'end_date'   => $camp->end_date?->toDateString(),
                    'address'    => $camp->address,
                    'timezone'   => $camp->timezone,
                    'logo'       => $camp->camp_logo
                        ? asset($camp->camp_logo)
                        : asset('default/no_image.webp'),
                ],
                'referee' => $profile,
                'statistics' => $statistics,
                'evaluations' => $formattedEvaluations,
                'assigned_slots' => $assignedSlots,
            ]
        );
    }

    /**
     * Get all game slots assigned to a specific referee in a camp.
     * Includes individual assignments and crew-based assignments.
     */
    private function getRefereeAssignedSlots($campId, $refereeId): array
    {
        // Individual assignments
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

        // Crew assignments
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

        // Merge and deduplicate by game_slot_id
        $allAssignments = $individualAssignments->concat($crewAssignments)
            ->sortBy(fn($a) => $a->gameSlot->game_date?->toDateString() . ' ' . $a->gameSlot->start_time)
            ->values();

        if ($allAssignments->isEmpty()) {
            return [
                'total_slots' => 0,
                'slots' => [],
            ];
        }

        $seenSlotIds = [];
        $slots = [];

        foreach ($allAssignments as $assignment) {
            $gameSlot = $assignment->gameSlot;
            if (in_array($gameSlot->id, $seenSlotIds)) {
                continue;
            }
            $seenSlotIds[] = $gameSlot->id;

            // Build assigned referees list for this slot
            $allSlotAssignments = $gameSlot->slotAssignments;
            $referees = [];

            foreach ($allSlotAssignments as $slotAssignment) {
                if ($slotAssignment->assignment_type === 'crew') {
                    $crew = $slotAssignment->assignable;
                    if ($crew && $crew->members) {
                        foreach ($crew->members as $member) {
                            $referees[] = [
                                'id'        => $member->id,
                                'name'      => $member->first_name . ' ' . $member->last_name,
                                'avatar'    => $member->avatar ? asset($member->avatar) : asset('default/profile.jpg'),
                                'email'     => $member->email,
                                'type'      => 'crew_member',
                                'crew_name' => $crew->crew_name ?? 'N/A',
                                'is_current_referee' => $member->id === (int) $refereeId,
                            ];
                        }
                    }
                } else {
                    $ref = $slotAssignment->assignable;
                    if (!$ref) continue;
                    $referees[] = [
                        'id'        => $ref->id,
                        'name'      => $ref->first_name . ' ' . $ref->last_name,
                        'avatar'    => $ref->avatar ? asset($ref->avatar) : asset('default/profile.jpg'),
                        'email'     => $ref->email,
                        'type'      => 'individual',
                        'is_current_referee' => $ref->id === (int) $refereeId,
                    ];
                }
            }

            $assignmentSource = $assignment->assignment_type === 'crew' ? 'crew' : 'individual';
            $crewNameIfVia = ($assignmentSource === 'crew') ? ($assignment->assignable->crew_name ?? null) : null;

            $slots[] = [
                'game_slot_id'     => $gameSlot->id,
                'assignment_source' => $assignmentSource,
                'crew_name'        => $crewNameIfVia,
                'game_details'     => [
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
                'position'         => $assignment->position,
                'assigned_at'      => $assignment->assigned_at?->format('Y-m-d H:i:s'),
                'is_auto_assigned' => (bool) $assignment->is_auto_assigned,
                'assigned_referees' => [
                    'total'    => count($referees),
                    'referees' => $referees,
                ],
            ];
        }

        return [
            'total_slots' => count($slots),
            'slots' => $slots,
        ];
    }
}
