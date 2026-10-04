<?php

namespace Modules\Director\app\Services\Ai\Tools;

use App\Models\RefereeEvaluation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Director\app\Services\Ai\Contracts\AiToolInterface;

class CompareRefereesTool implements AiToolInterface
{
    public function getName(): string
    {
        return 'compare_referees';
    }

    public function getDescription(): string
    {
        return 'Compare evaluation scores of 2 or more referees across the 6 officiating criteria (Call Accuracy, Communication, Consistency, Mechanics, Fitness, Game Awareness). Returns comparative analytics and line graph chart widget.';
    }

    public function getParametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'referee_names' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'List of referee full names or first/last names (e.g. ["John Doe", "Jane Smith"])',
                ],
                'referee_ids' => [
                    'type' => 'array',
                    'items' => ['type' => 'integer'],
                    'description' => 'List of referee user IDs (optional if names are provided)',
                ],
                'camp_id' => [
                    'type' => 'integer',
                    'description' => 'Optional Camp ID to scope evaluations to a specific camp',
                ],
            ],
            'required' => [],
        ];
    }

    public function execute(array $arguments, int $userId): array
    {
        $refereeIds = $arguments['referee_ids'] ?? [];
        $refereeNames = $arguments['referee_names'] ?? [];
        $campId = $arguments['camp_id'] ?? null;

        // Resolve referee IDs from names if IDs are not directly provided
        if (empty($refereeIds) && !empty($refereeNames)) {
            foreach ($refereeNames as $name) {
                $trimmed = trim($name);
                $user = User::where(function ($q) use ($trimmed) {
                    $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$trimmed}%"])
                        ->orWhere('first_name', 'LIKE', "%{$trimmed}%")
                        ->orWhere('last_name', 'LIKE', "%{$trimmed}%")
                        ->orWhere('username', 'LIKE', "%{$trimmed}%");
                })->first();

                if ($user) {
                    $refereeIds[] = $user->id;
                }
            }
        }

        $refereeIds = array_unique(array_filter($refereeIds));

        if (empty($refereeIds)) {
            return [
                'result' => [
                    'message' => 'No matching referees found for the provided names or IDs.',
                    'comparisons' => [],
                ],
                'widget_type' => null,
                'widget_payload' => null,
                'summary' => 'No referees found.',
            ];
        }

        $criteria = [
            'call_accuracy' => 'Accuracy',
            'communication_skills' => 'Communication',
            'consistency_of_calls' => 'Consistency',
            'court_position_mechanics' => 'Mechanics',
            'fitness_mobility' => 'Fitness',
            'game_awareness' => 'Game Awareness',
        ];

        $chartColors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899'];
        $datasets = [];
        $comparisons = [];
        $colorIndex = 0;

        foreach ($refereeIds as $refId) {
            $user = User::find($refId);
            if (!$user) {
                continue;
            }

            $query = RefereeEvaluation::where('referee_id', $refId)
                ->where('status', 'submitted');

            if ($campId) {
                $query->where('camp_id', $campId);
            }

            $stats = $query->select([
                DB::raw('AVG(call_accuracy) as avg_accuracy'),
                DB::raw('AVG(communication_skills) as avg_communication'),
                DB::raw('AVG(consistency_of_calls) as avg_consistency'),
                DB::raw('AVG(court_position_mechanics) as avg_mechanics'),
                DB::raw('AVG(fitness_mobility) as avg_fitness'),
                DB::raw('AVG(game_awareness) as avg_game_awareness'),
                DB::raw('AVG(average_score) as overall_avg'),
                DB::raw('COUNT(*) as eval_count'),
            ])->first();

            $evalCount = (int) ($stats->eval_count ?? 0);
            $scores = [
                'Accuracy' => round((float) ($stats->avg_accuracy ?? 0), 2),
                'Communication' => round((float) ($stats->avg_communication ?? 0), 2),
                'Consistency' => round((float) ($stats->avg_consistency ?? 0), 2),
                'Mechanics' => round((float) ($stats->avg_mechanics ?? 0), 2),
                'Fitness' => round((float) ($stats->avg_fitness ?? 0), 2),
                'Game Awareness' => round((float) ($stats->avg_game_awareness ?? 0), 2),
            ];

            $overallAvg = round((float) ($stats->overall_avg ?? 0), 2);
            $color = $chartColors[$colorIndex % count($chartColors)];
            $colorIndex++;

            $fullName = trim("{$user->first_name} {$user->last_name}") ?: $user->username;

            $comparisons[] = [
                'referee_id' => $user->id,
                'name' => $fullName,
                'email' => $user->email,
                'evaluations_count' => $evalCount,
                'scores' => $scores,
                'overall_average' => $overallAvg,
            ];

            $datasets[] = [
                'label' => $fullName,
                'data' => array_values($scores),
                'borderColor' => $color,
                'backgroundColor' => "rgba(" . implode(',', sscanf($color, "#%02x%02x%02x")) . ", 0.2)",
                'fill' => true,
                'tension' => 0.3,
            ];
        }

        $labels = array_values($criteria);

        $widgetPayload = [
            'chart_type' => 'line',
            'title' => 'Referee Performance Comparison (Scale 1-10)',
            'labels' => $labels,
            'datasets' => $datasets,
            'y_axis' => [
                'min' => 0,
                'max' => 10,
                'title' => 'Score (1 - 10)',
            ],
        ];

        return [
            'result' => [
                'criteria' => $labels,
                'referees' => $comparisons,
            ],
            'widget_type' => 'chart',
            'widget_payload' => $widgetPayload,
            'summary' => "Compared " . count($comparisons) . " referees across 6 criteria.",
        ];
    }
}
