<?php

namespace App\Services\Api\Referee;

use App\Models\RefereeEvaluation;
use App\Models\User;
use Modules\Director\Models\Camp;
use Modules\Director\Models\CampRefereeCheckin;

class EvaluatedRefereeService
{
    /**
     * Get all evaluations for a specific referee (referee's own view for a camp).
     *
     * @param  User  $user
     * @param  int|string  $campId
     * @return array
     */
    public function getRefereeEvaluations(User $user, $campId): array
    {
        if (!$user->hasRole('referee')) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'This endpoint is only for referees.',
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

        $checkin = CampRefereeCheckin::where('camp_id', $campId)
            ->where('referee_id', $user->id)
            ->first();

        if (!$checkin) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'You are not registered for this camp.',
                'data'    => [],
            ];
        }

        $evaluations = RefereeEvaluation::with(['evaluator', 'camp', 'gameSlot', 'recommendedLevels'])
            ->forReferee($user->id)
            ->where('camp_id', $campId)
            ->submitted()
            ->orderBy('submitted_at', 'desc')
            ->get();

        if ($evaluations->isEmpty()) {
            return [
                'success' => true,
                'code'    => 200,
                'message' => 'No evaluations found for this camp.',
                'data'    => [
                    'camp' => [
                        'id'         => $camp->id,
                        'name'       => $camp->camp_name,
                        'location'   => $camp->location,
                        'start_date' => $camp->start_date,
                        'end_date'   => $camp->end_date,
                        'address'    => $camp->address,
                    ],
                    'overall_performance'   => null,
                    'performance_breakdown' => null,
                    'recent_feedback'       => [],
                ],
            ];
        }

        // Calculate category averages
        $avgCallAccuracy = round($evaluations->avg('call_accuracy'), 3);
        $avgCommunication = round($evaluations->avg('communication_skills'), 3);
        $avgConsistency = round($evaluations->avg('consistency_of_calls'), 3);
        $avgCourtPosition = round($evaluations->avg('court_position_mechanics'), 3);
        $avgFitness = round($evaluations->avg('fitness_mobility'), 3);
        $avgGameAwareness = round($evaluations->avg('game_awareness'), 3);

        $overallAvg = round(($avgCallAccuracy + $avgCommunication + $avgConsistency +
            $avgCourtPosition + $avgFitness + $avgGameAwareness) / 6, 3);

        $overallPercentage = round(($overallAvg / 10) * 100, 3);
        $performanceRating = $this->getPerformanceRating($overallAvg);

        $rankingData = $this->calculateRefereeRanking($campId, $user->id, $overallAvg);

        // Recommended levels
        $recommendedLevels = [];
        foreach ($evaluations as $evaluation) {
            foreach ($evaluation->recommendedLevels as $level) {
                $recommendedLevels[$level->level] = ($recommendedLevels[$level->level] ?? 0) + 1;
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

        // Recent Feedback
        $recentFeedback = $evaluations->filter(function ($eval) {
            return !empty($eval->referee_feedback);
        })->take(10)->map(function ($eval) use ($camp) {
            $feedback = [
                'id'           => $eval->id,
                'camp_name'    => $eval->camp->camp_name,
                'total_score'  => (float) $eval->total_score,
                'max_score'    => 60,
                'feedback'     => $eval->referee_feedback,
                'submitted_at' => $eval->submitted_at->format('M j, Y'),
            ];

            if (!$camp->hide_evaluator_name_from_referees) {
                $feedback['evaluator_name'] = trim(($eval->evaluator->first_name ?? '') . ' ' . ($eval->evaluator->last_name ?? ''));
            } else {
                $feedback['evaluator_name'] = 'Anonymous Evaluator';
            }

            return $feedback;
        })->values();

        $response = [
            'camp' => [
                'id'         => $camp->id,
                'name'       => $camp->camp_name,
                'location'   => $camp->location,
                'start_date' => $camp->start_date,
                'end_date'   => $camp->end_date,
                'address'    => $camp->address,
            ],
            'overall_performance' => [
                'score'             => $overallAvg,
                'max_score'         => 10,
                'percentage'        => $overallPercentage,
                'rating'            => $performanceRating,
                'total_evaluations' => $evaluations->count(),
            ],
            'ranking' => $rankingData,
            'performance_breakdown' => [
                'call_accuracy' => [
                    'score'     => $avgCallAccuracy,
                    'max_score' => 10,
                ],
                'communication_skills' => [
                    'score'     => $avgCommunication,
                    'max_score' => 10,
                ],
                'consistency_of_calls' => [
                    'score'     => $avgConsistency,
                    'max_score' => 10,
                ],
                'court_position_mechanics' => [
                    'score'     => $avgCourtPosition,
                    'max_score' => 10,
                ],
                'fitness_mobility' => [
                    'score'     => $avgFitness,
                    'max_score' => 10,
                ],
                'game_awareness' => [
                    'score'     => $avgGameAwareness,
                    'max_score' => 10,
                ],
            ],
            'recommended_levels'        => $recommendedLevelsFormatted,
            'highest_recommended_level' => $highestRecommendedLevel,
            'recent_feedback'           => $recentFeedback,
            'settings'                  => [
                'hide_evaluator_name'  => $camp->hide_evaluator_name_from_referees,
                'hide_ranking_numbers' => $camp->hide_ranking_numbers_from_referees,
            ],
        ];

        if ($camp->hide_ranking_numbers_from_referees) {
            $response['ranking'] = null;
            $response['overall_performance']['score'] = $overallAvg;
            $response['recent_feedback'] = $recentFeedback->map(fn($f) => $f);
        }

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Your evaluations retrieved successfully.',
            'data'    => $response,
        ];
    }

    /**
     * Calculate referee's ranking position in the camp.
     */
    private function calculateRefereeRanking($campId, $refereeId, $currentRefereeAvg)
    {
        $allRefereeScores = RefereeEvaluation::select('referee_id')
            ->selectRaw('
                ROUND(
                    (AVG(call_accuracy) +
                     AVG(communication_skills) +
                     AVG(consistency_of_calls) +
                     AVG(court_position_mechanics) +
                     AVG(fitness_mobility) +
                     AVG(game_awareness)) / 6,
                    1
                ) as overall_average
            ')
            ->where('camp_id', $campId)
            ->where('status', 'submitted')
            ->groupBy('referee_id')
            ->having('overall_average', '>', 0)
            ->orderByDesc('overall_average')
            ->get();

        $totalReferees = $allRefereeScores->count();

        if ($totalReferees == 0) {
            return null;
        }

        $position = 1;
        foreach ($allRefereeScores as $index => $score) {
            if ($score->referee_id == $refereeId) {
                $position = $index + 1;
                break;
            }
        }

        return [
            'position' => $position,
        ];
    }

    /**
     * Helper function to get performance rating from score.
     */
    private function getPerformanceRating($score): string
    {
        if ($score >= 9) {
            return 'Excellent';
        } elseif ($score >= 7) {
            return 'Good';
        } elseif ($score >= 5) {
            return 'Average';
        } else {
            return 'Needs Improvement';
        }
    }
}
