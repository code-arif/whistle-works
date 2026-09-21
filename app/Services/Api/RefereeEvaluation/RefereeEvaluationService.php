<?php

namespace App\Services\Api\RefereeEvaluation;

use App\Http\Resources\RefereeEvaluationListResource;
use App\Http\Resources\RefereeEvaluationResource;
use App\Models\AssistantDirectorPermission;
use App\Models\CampEvaluatorRegistration;
use App\Models\CampRefereeJearsyNumber;
use App\Models\RecommendedLevel;
use App\Models\RefereeEvaluation;
use App\Models\SportsType;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Director\Models\Camp;
use Modules\Director\Models\CampRefereeCheckin;
use Modules\Director\Transformers\Referee\CheckedInRefereeResource;

class RefereeEvaluationService
{
    /**
     * Create or update referee evaluation.
     *
     * @param  User   $user
     * @param  array  $validated
     * @return array
     */
    public function storeOrUpdate(User $user, array $validated): array
    {
        if (!$user->hasAnyRole(['evaluator', 'director'])) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'Only evaluators and directors can create evaluations.',
                'data'    => [],
            ];
        }

        $camp = Camp::find($validated['camp_id']);
        if (!$camp) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Camp not found.',
                'data'    => [],
            ];
        }

        if (!RefereeEvaluation::canEvaluateInCamp($user, $camp->id)) {
            if ($user->hasRole('director')) {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => 'You can only evaluate referees in your own camps.',
                    'data'    => [],
                ];
            } else {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => 'You must be registered and approved for this camp to evaluate referees.',
                    'data'    => [],
                ];
            }
        }

        $referee = User::find($validated['referee_id']);
        if (!$referee || !$referee->hasRole('referee')) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Invalid referee selected.',
                'data'    => [],
            ];
        }

        if ($referee->id === $user->id) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'You cannot evaluate yourself.',
                'data'    => [],
            ];
        }

        $checkin = CampRefereeCheckin::where('camp_id', $camp->id)
            ->where('referee_id', $referee->id)
            ->first();

        if (!$checkin) {
            return [
                'success' => false,
                'code'    => 400,
                'message' => 'Referee has not registered for this camp.',
                'data'    => [],
            ];
        }

        DB::beginTransaction();

        try {
            if (isset($validated['evaluation_id'])) {
                $evaluation = RefereeEvaluation::find($validated['evaluation_id']);

                if (!$evaluation) {
                    return [
                        'success' => false,
                        'code'    => 404,
                        'message' => 'Evaluation not found.',
                        'data'    => [],
                    ];
                }

                if ($evaluation->evaluator_id !== $user->id) {
                    return [
                        'success' => false,
                        'code'    => 403,
                        'message' => 'You can only update your own evaluations.',
                        'data'    => [],
                    ];
                }

                $evaluation->update([
                    'call_accuracy'            => $validated['call_accuracy'] ?? null,
                    'communication_skills'     => $validated['communication_skills'] ?? null,
                    'consistency_of_calls'     => $validated['consistency_of_calls'] ?? null,
                    'court_position_mechanics' => $validated['court_position_mechanics'] ?? null,
                    'fitness_mobility'         => $validated['fitness_mobility'] ?? null,
                    'game_awareness'           => $validated['game_awareness'] ?? null,
                    'private_comments'         => $validated['private_comments'] ?? null,
                    'referee_feedback'         => $validated['referee_feedback'] ?? null,
                    'status'                   => $validated['status'] ?? 'draft',
                    'submitted_at'             => ($validated['status'] ?? 'draft') === 'submitted' ? now() : null,
                ]);

                if (isset($validated['recommended_level'])) {
                    $evaluation->recommendedLevels()->delete();
                    RecommendedLevel::create([
                        'evaluation_id' => $evaluation->id,
                        'level'         => $validated['recommended_level'],
                    ]);
                }

                $message = 'Evaluation updated successfully.';
                $statusCode = 200;
            } else {
                $evaluation = RefereeEvaluation::create([
                    'referee_id'               => $validated['referee_id'],
                    'evaluator_id'             => $user->id,
                    'camp_id'                  => $validated['camp_id'],
                    'game_slot_id'             => $validated['game_slot_id'] ?? null,
                    'call_accuracy'            => $validated['call_accuracy'] ?? null,
                    'communication_skills'     => $validated['communication_skills'] ?? null,
                    'consistency_of_calls'     => $validated['consistency_of_calls'] ?? null,
                    'court_position_mechanics' => $validated['court_position_mechanics'] ?? null,
                    'fitness_mobility'         => $validated['fitness_mobility'] ?? null,
                    'game_awareness'           => $validated['game_awareness'] ?? null,
                    'private_comments'         => $validated['private_comments'] ?? null,
                    'referee_feedback'         => $validated['referee_feedback'] ?? null,
                    'status'                   => $validated['status'] ?? 'draft',
                    'submitted_at'             => ($validated['status'] ?? 'draft') === 'submitted' ? now() : null,
                ]);

                if (isset($validated['recommended_level'])) {
                    RecommendedLevel::create([
                        'evaluation_id' => $evaluation->id,
                        'level'         => $validated['recommended_level'],
                    ]);
                }

                $message = 'Evaluation created successfully.';
                $statusCode = 201;
            }

            DB::commit();

            return [
                'success' => true,
                'code'    => $statusCode,
                'message' => $message,
                'data'    => new RefereeEvaluationResource($evaluation->load(['referee', 'evaluator', 'camp', 'gameSlot', 'recommendedLevels'])),
            ];
        } catch (Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'code'    => 500,
                'message' => 'Failed to save evaluation: ' . $e->getMessage(),
                'data'    => [],
            ];
        }
    }

    /**
     * Get evaluations by camp for directors, evaluators, or referees.
     *
     * @param  User        $user
     * @param  int|string  $campId
     * @return array
     */
    public function getEvaluationsByCamp(User $user, $campId): array
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

        if ($user->hasRole('director')) {
            $isOwner = $camp->director_id === $user->id;
            $isAssistant = AssistantDirectorPermission::where('camp_id', $campId)
                ->where('assistant_director_id', $user->id)
                ->exists();

            if (!$isOwner && !$isAssistant) {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => 'You can only view evaluations from your own or assigned camps.',
                    'data'    => [],
                ];
            }
        }

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

            if (!$registration->can_view_own_evaluations) {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => 'You do not have permission to view evaluations for this camp. Contact the director.',
                    'data'    => [],
                ];
            }
        }

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

            if (!$camp->publish_ranking_for_referees) {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => 'You do not have permission to view evaluations for this camp. Contact the director.',
                    'data'    => [],
                ];
            }
        }

        $query = RefereeEvaluation::with(['referee', 'evaluator', 'gameSlot', 'recommendedLevels'])
            ->forCamp($campId);

        $evaluations = $query->orderBy('created_at', 'asc')->get();

        $groupedByReferee = $evaluations->groupBy('referee_id');
        $formattedData = [];

        foreach ($groupedByReferee as $refereeId => $refereeEvaluations) {
            $referee = $refereeEvaluations->first()->referee;

            $avgCallAccuracy = round($refereeEvaluations->avg('call_accuracy'), 3);
            $avgCommunication = round($refereeEvaluations->avg('communication_skills'), 3);
            $avgConsistency = round($refereeEvaluations->avg('consistency_of_calls'), 3);
            $avgCourtPosition = round($refereeEvaluations->avg('court_position_mechanics'), 3);
            $avgFitness = round($refereeEvaluations->avg('fitness_mobility'), 3);
            $avgGameAwareness = round($refereeEvaluations->avg('game_awareness'), 3);

            $overallAvg = round(($avgCallAccuracy + $avgCommunication + $avgConsistency +
                $avgCourtPosition + $avgFitness + $avgGameAwareness) / 6, 3);

            $recommendedLevels = [];
            foreach ($refereeEvaluations as $evaluation) {
                foreach ($evaluation->recommendedLevels as $level) {
                    if (isset($recommendedLevels[$level->level])) {
                        $recommendedLevels[$level->level]++;
                    } else {
                        $recommendedLevels[$level->level] = 1;
                    }
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

            $highestRecommendedLevel = !empty($recommendedLevelsFormatted)
                ? $recommendedLevelsFormatted[0]['level']
                : null;

            $evaluators = $refereeEvaluations->map(function ($eval) {
                return $eval->evaluator->first_name . ' ' . $eval->evaluator->last_name;
            })->unique()->values()->toArray();

            $allComments = $refereeEvaluations->filter(function ($eval) {
                return !empty($eval->referee_feedback);
            })->pluck('referee_feedback')->toArray();

            $formattedData[] = [
                'referee_id'                => $referee->id,
                'referee_name'              => $referee->first_name . ' ' . $referee->last_name,
                'referee_email'             => $referee->email,
                'referee_avatar'            => $referee->avatar ? asset($referee->avatar) : asset('default/profile.jpg'),
                'referee_address'           => $referee->address ?? '',
                'total_evaluations'         => $refereeEvaluations->count(),
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
                'highest_recommended_level' => $highestRecommendedLevel,
                'evaluators'                => $evaluators,
                'comments'                  => $allComments,
            ];
        }

        usort($formattedData, fn($a, $b) => $b['averages']['overall'] <=> $a['averages']['overall']);

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Evaluations retrieved successfully.',
            'data'    => [
                'evaluations'       => $formattedData,
                'total_referees'    => count($formattedData),
                'total_evaluations' => $evaluations->count(),
            ],
        ];
    }

    /**
     * Get evaluations created by the authenticated evaluator.
     *
     * @param  User        $user
     * @param  int|string  $campId
     * @param  int         $perPage
     * @return array
     */
    public function getMyEvaluations(User $user, $campId, int $perPage = 15): array
    {
        if (!$user->hasAnyRole(['evaluator', 'director'])) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'Only evaluators and directors can access this.',
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

        if ($user->hasRole('evaluator') && !$user->hasRole('director')) {
            $registration = CampEvaluatorRegistration::where('camp_id', $campId)
                ->where('evaluator_id', $user->id)
                ->where('status', 'approved')
                ->first();

            if (!$registration || !$registration->can_view_own_evaluations) {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => 'You do not have permission to view evaluations for this camp. Contact the director.',
                    'data'    => [],
                ];
            }
        }

        if ($user->hasRole('director') && $camp->director_id !== $user->id) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'You can only view evaluations from your own camps.',
                'data'    => [],
            ];
        }

        $evaluations = RefereeEvaluation::with(['referee', 'camp', 'gameSlot', 'recommendedLevels'])
            ->byEvaluator($user->id)
            ->where('camp_id', $campId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $summaryEvaluations = RefereeEvaluation::with(['recommendedLevels'])
            ->byEvaluator($user->id)
            ->where('camp_id', $campId)
            ->has('recommendedLevels')
            ->get();

        $allLevels = ['NCAA D1', 'NCAA D2', 'NAIA', 'JUCO', 'HS', 'JH/ELEM'];
        $overallSummary = [];

        foreach ($allLevels as $level) {
            $count = RecommendedLevel::whereIn(
                'evaluation_id',
                $summaryEvaluations->pluck('id')
            )->where('level', $level)->count();

            if ($count > 0) {
                $overallSummary[] = [
                    'level' => $level,
                    'count' => $count,
                ];
            }
        }

        usort($overallSummary, fn($a, $b) => $b['count'] <=> $a['count']);

        $uniqueRefereesEvaluated = RefereeEvaluation::where('evaluator_id', $user->id)
            ->where('camp_id', $campId)
            ->distinct('referee_id')
            ->count('referee_id');

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Your evaluations retrieved successfully.',
            'data'    => [
                'camp'                  => [
                    'id'         => $camp->id,
                    'name'       => $camp->camp_name,
                    'location'   => $camp->location,
                    'start_date' => $camp->start_date,
                    'end_date'   => $camp->end_date,
                    'address'    => $camp->address,
                ],
                'evaluations'           => RefereeEvaluationListResource::collection($evaluations),
                'overall_level_summary' => $overallSummary,
                'statistics'            => [
                    'total_evaluations'         => $evaluations->total(),
                    'unique_referees_evaluated' => $uniqueRefereesEvaluated,
                ],
                'pagination'            => [
                    'total'        => $evaluations->total(),
                    'per_page'     => $evaluations->perPage(),
                    'current_page' => $evaluations->currentPage(),
                    'last_page'    => $evaluations->lastPage(),
                ],
            ],
        ];
    }

    /**
     * Get a single evaluation by ID.
     *
     * @param  User        $user
     * @param  int|string  $id
     * @return array
     */
    public function show(User $user, $id): array
    {
        $evaluation = RefereeEvaluation::with(['referee', 'evaluator', 'camp', 'gameSlot'])->find($id);

        if (!$evaluation) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Evaluation not found.',
                'data'    => [],
            ];
        }

        if (!$evaluation->canBeViewedBy($user)) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'You do not have permission to view this evaluation.',
                'data'    => [],
            ];
        }

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Evaluation retrieved successfully.',
            'data'    => new RefereeEvaluationResource($evaluation),
        ];
    }

    /**
     * Delete an evaluation.
     *
     * @param  User        $user
     * @param  int|string  $id
     * @return array
     */
    public function destroy(User $user, $id): array
    {
        $evaluation = RefereeEvaluation::find($id);

        if (!$evaluation) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Evaluation not found.',
                'data'    => [],
            ];
        }

        if (!$evaluation->canBeEditedBy($user)) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'You do not have permission to delete this evaluation.',
                'data'    => [],
            ];
        }

        $evaluation->delete();

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Evaluation deleted successfully.',
            'data'    => [],
        ];
    }

    /**
     * Get referee evaluation statistics.
     *
     * @param  User        $user
     * @param  int|string  $refereeId
     * @return array
     */
    public function getRefereeStats(User $user, $refereeId): array
    {
        if (!$user->hasAnyRole(['director', 'evaluator']) && $user->id != $refereeId) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'Unauthorized access.',
                'data'    => [],
            ];
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

        $evaluations = RefereeEvaluation::forReferee($refereeId)->submitted()->get();

        if ($evaluations->isEmpty()) {
            return [
                'success' => true,
                'code'    => 200,
                'message' => 'No evaluations found for this referee.',
                'data'    => [
                    'referee'           => [
                        'id'   => $referee->id,
                        'name' => $referee->first_name . ' ' . $referee->last_name,
                    ],
                    'total_evaluations' => 0,
                    'averages'          => null,
                ],
            ];
        }

        $stats = [
            'referee'            => [
                'id'    => $referee->id,
                'name'  => $referee->first_name . ' ' . $referee->last_name,
                'email' => $referee->email,
            ],
            'total_evaluations'  => $evaluations->count(),
            'averages'           => [
                'call_accuracy'            => round($evaluations->avg('call_accuracy'), 2),
                'communication_skills'     => round($evaluations->avg('communication_skills'), 2),
                'consistency_of_calls'     => round($evaluations->avg('consistency_of_calls'), 2),
                'court_position_mechanics' => round($evaluations->avg('court_position_mechanics'), 2),
                'fitness_mobility'         => round($evaluations->avg('fitness_mobility'), 2),
                'game_awareness'           => round($evaluations->avg('game_awareness'), 2),
                'total_score'              => round($evaluations->avg('total_score'), 2),
            ],
            'overall_percentage' => round(($evaluations->avg('total_score') / 60) * 100, 2),
        ];

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Referee statistics retrieved successfully.',
            'data'    => $stats,
        ];
    }

    /**
     * Get paginated registered referees for a camp.
     *
     * @param  User        $user
     * @param  int|string  $campId
     * @param  int         $perPage
     * @return array
     */
    public function getAllRegisteredInReferees(User $user, $campId, int $perPage = 15): array
    {
        $camp = Camp::find($campId);
        if (!$camp) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Camp not found.',
                'data'    => [],
            ];
        }

        if (!RefereeEvaluation::canEvaluateInCamp($user, $campId)) {
            if ($user->hasRole('director')) {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => 'You can only view referees from your own camps.',
                    'data'    => [],
                ];
            } else {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => 'You must be registered and approved for this camp.',
                    'data'    => [],
                ];
            }
        }

        $jerseyNumbers = CampRefereeJearsyNumber::where('camp_id', $campId)
            ->pluck('jersey_number', 'referee_id');

        $checkedInReferees = CampRefereeCheckin::select('camp_referee_checkins.*')
            ->where('camp_referee_checkins.camp_id', $campId)
            ->where('camp_referee_checkins.registration_status', 'registered')
            ->join('users', 'camp_referee_checkins.referee_id', '=', 'users.id')
            ->with('referee')
            ->orderBy('users.last_name', 'asc')
            ->paginate($perPage);

        $checkedInReferees->getCollection()->transform(function ($checkin) use ($jerseyNumbers) {
            $checkin->jersey_number = $jerseyNumbers[$checkin->referee_id] ?? null;
            return $checkin;
        });

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Registered referees fetched successfully.',
            'data'    => [
                'total'      => $checkedInReferees->total(),
                'referees'   => CheckedInRefereeResource::collection($checkedInReferees),
                'pagination' => [
                    'total'        => $checkedInReferees->total(),
                    'per_page'     => $checkedInReferees->perPage(),
                    'current_page' => $checkedInReferees->currentPage(),
                    'last_page'    => $checkedInReferees->lastPage(),
                ],
            ],
        ];
    }

    /**
     * Get all unpaginated referees for a camp.
     *
     * @param  User        $user
     * @param  int|string  $campId
     * @return array
     */
    public function getAllReferees(User $user, $campId): array
    {
        $camp = Camp::find($campId);
        if (!$camp) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Camp not found.',
                'data'    => [],
            ];
        }

        $jerseyNumbers = CampRefereeJearsyNumber::where('camp_id', $camp->id)
            ->pluck('jersey_number', 'referee_id');

        if (!RefereeEvaluation::canEvaluateInCamp($user, $campId)) {
            if ($user->hasRole('director')) {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => 'You can only view referees from your own or assigned camps.',
                    'data'    => [],
                ];
            } else {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => 'You must be registered and approved for this camp.',
                    'data'    => [],
                ];
            }
        }

        $checkedInReferees = CampRefereeCheckin::select('camp_referee_checkins.*')
            ->where('camp_referee_checkins.camp_id', $campId)
            ->join('users', 'camp_referee_checkins.referee_id', '=', 'users.id')
            ->with('referee')
            ->orderBy('users.last_name', 'asc')
            ->get()
            ->map(function ($checkin) use ($jerseyNumbers) {
                $checkin->jersey_number = $jerseyNumbers[$checkin->referee_id] ?? null;
                return $checkin;
            });

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Registered referees fetched successfully.',
            'data'    => [
                'total'    => $checkedInReferees->count(),
                'referees' => CheckedInRefereeResource::collection($checkedInReferees),
            ],
        ];
    }

    /**
     * Get evaluation history for a referee in a camp.
     *
     * @param  User         $user
     * @param  int|string   $campId
     * @param  int|string   $refereeId
     * @param  string|null  $status
     * @param  int          $perPage
     * @return array
     */
    public function getRefereeEvaluationHistory(User $user, $campId, $refereeId, ?string $status = null, int $perPage = 15): array
    {
        if (!$user->hasAnyRole(['director', 'evaluator'])) {
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

        if ($user->hasRole('director')) {
            $isOwner = $camp->director_id === $user->id;
            $isAssistant = AssistantDirectorPermission::where('camp_id', $campId)
                ->where('assistant_director_id', $user->id)
                ->exists();

            if (!$isOwner && !$isAssistant) {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => 'You can only view evaluations from your own or assigned camps.',
                    'data'    => [],
                ];
            }
        }

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

            if (!$registration->can_view_own_evaluations) {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => 'You do not have permission to view evaluations for this camp. Contact the director.',
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

        $query = RefereeEvaluation::with(['evaluator', 'gameSlot', 'recommendedLevels'])
            ->where('camp_id', $campId)
            ->where('referee_id', $refereeId)
            ->orderBy('submitted_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        if ($user->hasRole('evaluator') && !$user->hasRole('director')) {
            $query->where('evaluator_id', $user->id);
        }

        $evaluations = $query->paginate($perPage);

        $allEvaluations = RefereeEvaluation::where('camp_id', $campId)
            ->where('referee_id', $refereeId)
            ->when($user->hasRole('evaluator') && !$user->hasRole('director'), function ($q) use ($user) {
                $q->where('evaluator_id', $user->id);
            })
            ->get();

        $statistics = null;
        if ($allEvaluations->isNotEmpty()) {
            $avgCallAccuracy = round($allEvaluations->avg('call_accuracy'), 3);
            $avgCommunication = round($allEvaluations->avg('communication_skills'), 3);
            $avgConsistency = round($allEvaluations->avg('consistency_of_calls'), 3);
            $avgCourtPosition = round($allEvaluations->avg('court_position_mechanics'), 3);
            $avgFitness = round($allEvaluations->avg('fitness_mobility'), 3);
            $avgGameAwareness = round($allEvaluations->avg('game_awareness'), 3);

            $overallAvg = round(($avgCallAccuracy + $avgCommunication + $avgConsistency +
                $avgCourtPosition + $avgFitness + $avgGameAwareness) / 6, 3);

            $recommendedLevels = [];
            foreach ($allEvaluations as $evaluation) {
                if ($evaluation->relationLoaded('recommendedLevels')) {
                    foreach ($evaluation->recommendedLevels as $level) {
                        if (isset($recommendedLevels[$level->level])) {
                            $recommendedLevels[$level->level]++;
                        } else {
                            $recommendedLevels[$level->level] = 1;
                        }
                    }
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
                'total_evaluations'         => $allEvaluations->count(),
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
                'id'                 => $evaluation->id,
                'evaluator'          => [
                    'id'    => $evaluation->evaluator_id,
                    'name'  => $evaluation->evaluator->first_name . ' ' . $evaluation->evaluator->last_name,
                    'email' => $evaluation->evaluator->email,
                    'role'  => $evaluation->evaluator->getRoleNames()->first(),
                ],
                'game_slot'          => $evaluation->game_slot_id ? [
                    'id'    => $evaluation->gameSlot->id,
                    'date'  => $evaluation->gameSlot->game_date,
                    'time'  => $evaluation->gameSlot->start_time . ' - ' . $evaluation->gameSlot->end_time,
                    'court' => $evaluation->gameSlot->court_name ?? 'N/A',
                ] : null,
                'scores'             => [
                    'call_accuracy'            => $evaluation->call_accuracy,
                    'communication_skills'     => $evaluation->communication_skills,
                    'consistency_of_calls'     => $evaluation->consistency_of_calls,
                    'court_position_mechanics' => $evaluation->court_position_mechanics,
                    'fitness_mobility'         => $evaluation->fitness_mobility,
                    'game_awareness'           => $evaluation->game_awareness,
                ],
                'total_score'        => (float) $evaluation->total_score,
                'average_score'      => (float) $evaluation->average_score,
                'max_score'          => 60,
                'percentage'         => $evaluation->total_score ? round(($evaluation->total_score / 60) * 100, 2) : 0,
                'recommended_level'  => $evaluation->relationLoaded('recommendedLevels') && $evaluation->recommendedLevels->isNotEmpty()
                    ? $evaluation->recommendedLevels->first()->level
                    : null,
                'private_comments'   => $evaluation->private_comments,
                'referee_feedback'   => $evaluation->referee_feedback,
                'status'             => $evaluation->status,
                'submitted_at'       => $evaluation->submitted_at ? $evaluation->submitted_at->toDateString() : null,
                'created_at'         => $evaluation->created_at->toDateString(),
            ];
        });

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Referee evaluation history retrieved successfully.',
            'data'    => [
                'camp'        => [
                    'id'         => $camp->id,
                    'name'       => $camp->camp_name,
                    'location'   => $camp->location,
                    'start_date' => $camp->start_date,
                    'end_date'   => $camp->end_date,
                    'address'    => $camp->address,
                ],
                'referee'     => [
                    'id'     => $referee->id,
                    'name'   => $referee->first_name . ' ' . $referee->last_name,
                    'email'  => $referee->email,
                    'avatar' => $referee->avatar ? asset($referee->avatar) : asset('default/profile.jpg'),
                ],
                'statistics'  => $statistics,
                'evaluations' => $formattedEvaluations,
                'pagination'  => [
                    'total'        => $evaluations->total(),
                    'per_page'     => $evaluations->perPage(),
                    'current_page' => $evaluations->currentPage(),
                    'last_page'    => $evaluations->lastPage(),
                ],
            ],
        ];
    }

    /**
     * Get sports types for referee levels.
     *
     * @param  string|null  $sportsName
     * @return array
     */
    public function getSportTypes(?string $sportsName): array
    {
        $sportType = SportsType::where('status', 'active')
            ->where('sports_name', $sportsName)
            ->first();

        if ($sportType && $sportType->sports_name === 'HS Basketball') {
            $data = [
                'Large School Varsity',
                'Mid Size School Varsity',
                'Small School Varsity',
                'Junior Varsity',
                'JH/ELEM',
            ];
        } else {
            $data = [
                'NCAA D1',
                'NCAA D2',
                'NAIA',
                'JUCO',
                'HS',
                'JH/ELEM',
            ];
        }

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Data retrieved successfully.',
            'data'    => $data,
        ];
    }
}
