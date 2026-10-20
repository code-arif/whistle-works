<?php

namespace Modules\Director\app\Services\Ai\Tools;

use App\Models\CampEvaluatorRegistration;
use App\Models\CampRefereeJearsyNumber;
use App\Models\RefereeEvaluation;
use Modules\Director\app\Services\Ai\Contracts\AiToolInterface;
use Modules\Director\Models\Camp;
use Modules\Director\Models\CampRefereeCheckin;

class GetCampInventoryTool implements AiToolInterface
{
    public function getName(): string
    {
        return 'get_camp_inventory';
    }

    public function getDescription(): string
    {
        return 'Retrieve the complete inventory of all camps owned or managed by the director. Returns all camps with dates, statuses, total referee registrations, evaluator assignments, and evaluation counts.';
    }

    public function getParametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'status' => [
                    'type' => 'string',
                    'description' => 'Optional status filter ("active", "all", "inactive")',
                ],
                'search' => [
                    'type' => 'string',
                    'description' => 'Optional keyword or camp name to filter',
                ],
            ],
            'required' => [],
        ];
    }

    public function execute(array $arguments, int $userId): array
    {
        $status = strtolower($arguments['status'] ?? 'all');
        $search = trim($arguments['search'] ?? '');

        $query = Camp::forDirectorOrAssistant($userId);

        if ($status !== 'all' && !empty($status)) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where('camp_name', 'LIKE', "%{$search}%");
        }

        $camps = $query->orderBy('start_date', 'desc')->get();

        if ($camps->isEmpty()) {
            return [
                'result' => [
                    'total_camps' => 0,
                    'camps' => [],
                    'message' => 'No camps found for this director.',
                ],
                'widget_type' => null,
                'widget_payload' => null,
                'summary' => 'No camps found in inventory.',
            ];
        }

        $campIds = $camps->pluck('id')->all();

        // Preload counts
        $checkinCounts = CampRefereeCheckin::whereIn('camp_id', $campIds)
            ->whereIn('registration_status', ['registered', 'checked_in'])
            ->selectRaw('camp_id, count(distinct referee_id) as total')
            ->groupBy('camp_id')
            ->pluck('total', 'camp_id')
            ->all();

        $jerseyCounts = CampRefereeJearsyNumber::whereIn('camp_id', $campIds)
            ->selectRaw('camp_id, count(distinct referee_id) as total')
            ->groupBy('camp_id')
            ->pluck('total', 'camp_id')
            ->all();

        $evaluatorCounts = CampEvaluatorRegistration::whereIn('camp_id', $campIds)
            ->where('status', 'approved')
            ->selectRaw('camp_id, count(distinct evaluator_id) as total')
            ->groupBy('camp_id')
            ->pluck('total', 'camp_id')
            ->all();

        $evaluationCounts = RefereeEvaluation::whereIn('camp_id', $campIds)
            ->where('status', 'submitted')
            ->selectRaw('camp_id, count(*) as total')
            ->groupBy('camp_id')
            ->pluck('total', 'camp_id')
            ->all();

        $inventory = [];
        foreach ($camps as $camp) {
            $refCount = max($checkinCounts[$camp->id] ?? 0, $jerseyCounts[$camp->id] ?? 0);
            $evalCount = $evaluatorCounts[$camp->id] ?? 0;
            $submittedCount = $evaluationCounts[$camp->id] ?? 0;

            $campName = $camp->camp_name ?? "Camp #{$camp->id}";
            $startDate = $camp->start_date ? $camp->start_date->format('Y-m-d') : 'TBD';
            $endDate = $camp->end_date ? $camp->end_date->format('Y-m-d') : 'TBD';

            $inventory[] = [
                'camp_id' => $camp->id,
                'camp_name' => $campName,
                'name' => $campName,
                'status' => ucfirst($camp->status ?? 'active'),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'referees_count' => $refCount,
                'evaluators_count' => $evalCount,
                'submitted_evaluations' => $submittedCount,
            ];
        }

        $widgetPayload = [
            'type' => 'table',
            'title' => 'Camp Inventory & Roster Summary (' . count($inventory) . ' Camps)',
            'headers' => ['Camp Name', 'Status', 'Start Date', 'End Date', 'Referees', 'Evaluators', 'Evaluations Submitted'],
            'rows' => array_map(function ($c) {
                return [
                    'name' => $c['camp_name'],
                    'camp_name' => $c['camp_name'],
                    'status' => $c['status'],
                    'start_date' => $c['start_date'],
                    'end_date' => $c['end_date'],
                    'referees' => $c['referees_count'],
                    'referees_count' => $c['referees_count'],
                    'evaluators' => $c['evaluators_count'],
                    'evaluators_count' => $c['evaluators_count'],
                    'evaluations' => $c['submitted_evaluations'],
                    'evaluations_count' => $c['submitted_evaluations'],
                ];
            }, $inventory),
        ];

        return [
            'result' => [
                'total_camps' => count($inventory),
                'camps' => $inventory,
            ],
            'widget_type' => 'table',
            'widget_payload' => $widgetPayload,
            'summary' => 'Found ' . count($inventory) . ' camps in director inventory.',
        ];
    }
}
