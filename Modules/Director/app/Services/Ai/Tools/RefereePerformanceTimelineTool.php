<?php

namespace Modules\Director\app\Services\Ai\Tools;

use App\Models\RefereeEvaluation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Director\app\Services\Ai\Contracts\AiToolInterface;
use Modules\Director\Models\Camp;

class RefereePerformanceTimelineTool implements AiToolInterface
{
    public function getName(): string
    {
        return 'get_referee_performance_timeline';
    }

    public function getDescription(): string
    {
        return 'Generate a multi-year chronological timeline and performance line graph for a specific referee over time (e.g. past 3 years). Returns line chart widget plotting scores per camp/date.';
    }

    public function getParametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'referee_name' => [
                    'type' => 'string',
                    'description' => 'Full name or username of the referee (e.g. "John Doe")',
                ],
                'referee_id' => [
                    'type' => 'integer',
                    'description' => 'User ID of the referee if known',
                ],
                'years_back' => [
                    'type' => 'integer',
                    'description' => 'Number of years to look back (default: 3)',
                ],
            ],
            'required' => [],
        ];
    }

    public function execute(array $arguments, int $userId): array
    {
        $refereeId = $arguments['referee_id'] ?? null;
        $refereeName = $arguments['referee_name'] ?? null;
        $yearsBack = (int) ($arguments['years_back'] ?? 3);

        $user = null;
        if ($refereeId) {
            $user = User::find($refereeId);
        } elseif (!empty($refereeName)) {
            $trimmed = trim($refereeName);
            $user = User::where(function ($q) use ($trimmed) {
                $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$trimmed}%"])
                    ->orWhere('first_name', 'LIKE', "%{$trimmed}%")
                    ->orWhere('last_name', 'LIKE', "%{$trimmed}%")
                    ->orWhere('username', 'LIKE', "%{$trimmed}%");
            })->first();
        }

        if (!$user) {
            return [
                'result' => [
                    'message' => 'Referee not found. Please verify the referee name or ID.',
                    'timeline' => [],
                ],
                'widget_type' => null,
                'widget_payload' => null,
                'summary' => 'Referee not found.',
            ];
        }

        $startDate = Carbon::now()->subYears($yearsBack)->startOfDay();

        $evaluations = RefereeEvaluation::where('referee_id', $user->id)
            ->where('status', 'submitted')
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'asc')
            ->get();

        if ($evaluations->isEmpty()) {
            // Fallback to all submitted evaluations if time constraint returns empty
            $evaluations = RefereeEvaluation::where('referee_id', $user->id)
                ->where('status', 'submitted')
                ->orderBy('created_at', 'asc')
                ->get();
        }

        $timelinePoints = [];
        $labels = [];
        $overallScores = [];
        $accuracyScores = [];
        $mechanicsScores = [];

        foreach ($evaluations as $ev) {
            $dateLabel = $ev->created_at ? $ev->created_at->format('M Y') : 'N/A';
            $camp = Camp::find($ev->camp_id);
            $campLabel = $camp ? ($camp->camp_name ?? $camp->name) : 'Camp #' . $ev->camp_id;
            $pointLabel = "{$dateLabel} ({$campLabel})";

            $labels[] = $pointLabel;
            $overall = (float) ($ev->average_score ?? 0);
            $overallScores[] = round($overall, 2);
            $accuracyScores[] = (float) ($ev->call_accuracy ?? 0);
            $mechanicsScores[] = (float) ($ev->court_position_mechanics ?? 0);

            $timelinePoints[] = [
                'evaluation_id' => $ev->id,
                'date' => $ev->created_at ? $ev->created_at->format('Y-m-d') : null,
                'camp' => $campLabel,
                'average_score' => round($overall, 2),
                'call_accuracy' => (int) $ev->call_accuracy,
                'communication_skills' => (int) $ev->communication_skills,
                'consistency_of_calls' => (int) $ev->consistency_of_calls,
                'court_position_mechanics' => (int) $ev->court_position_mechanics,
                'fitness_mobility' => (int) $ev->fitness_mobility,
                'game_awareness' => (int) $ev->game_awareness,
            ];
        }

        $fullName = trim("{$user->first_name} {$user->last_name}") ?: $user->username;

        $widgetPayload = [
            'chart_type' => 'line',
            'title' => "Performance Trend for {$fullName} (Past {$yearsBack} Years)",
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Overall Average Score',
                    'data' => $overallScores,
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Call Accuracy',
                    'data' => $accuracyScores,
                    'borderColor' => '#10B981',
                    'backgroundColor' => 'transparent',
                    'borderDash' => [5, 5],
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Mechanics & Positioning',
                    'data' => $mechanicsScores,
                    'borderColor' => '#F59E0B',
                    'backgroundColor' => 'transparent',
                    'borderDash' => [3, 3],
                    'tension' => 0.3,
                ],
            ],
            'y_axis' => [
                'min' => 0,
                'max' => 10,
                'title' => 'Score (1 - 10)',
            ],
        ];

        return [
            'result' => [
                'referee_id' => $user->id,
                'name' => $fullName,
                'evaluations_count' => count($evaluations),
                'timeline' => $timelinePoints,
            ],
            'widget_type' => 'chart',
            'widget_payload' => $widgetPayload,
            'summary' => "Retrieved {$evaluations->count()} chronological evaluations for {$fullName}.",
        ];
    }
}
