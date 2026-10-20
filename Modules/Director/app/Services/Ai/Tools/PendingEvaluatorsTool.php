<?php

namespace Modules\Director\app\Services\Ai\Tools;

use App\Models\CampEvaluatorRegistration;
use App\Models\RefereeEvaluation;
use App\Models\User;
use Modules\Director\app\Services\Ai\Contracts\AiToolInterface;
use Modules\Director\Models\Camp;

class PendingEvaluatorsTool implements AiToolInterface
{
    public function getName(): string
    {
        return 'get_pending_evaluators';
    }

    public function getDescription(): string
    {
        return 'Audit camp evaluators and identify approved evaluators who have NOT submitted any evaluations for a specific camp. Returns non-compliant evaluators with contact information and status.';
    }

    public function getParametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'camp_name' => [
                    'type' => 'string',
                    'description' => 'Name or title of the camp (e.g. "Camp A", "National Referee Camp")',
                ],
                'camp_id' => [
                    'type' => 'integer',
                    'description' => 'ID of the camp to audit',
                ],
            ],
            'required' => [],
        ];
    }

    public function execute(array $arguments, int $userId): array
    {
        $campId = !empty($arguments['camp_id']) ? (int) $arguments['camp_id'] : null;
        $campName = $arguments['camp_name'] ?? null;
        $isAllCamps = empty($campId) && empty($campName);

        $camps = collect();
        if ($campId) {
            $c = Camp::find($campId);
            if ($c) {
                $camps->push($c);
            }
        } elseif (!empty($campName)) {
            $c = Camp::where('camp_name', 'LIKE', "%{$campName}%")->first();
            if ($c) {
                $camps->push($c);
            }
        }

        if ($camps->isEmpty()) {
            if ($isAllCamps) {
                $camps = Camp::forDirectorOrAssistant($userId)->get();
                if ($camps->isEmpty()) {
                    $camps = Camp::orderBy('id', 'desc')->limit(5)->get();
                }
            } else {
                $c = Camp::where('director_id', $userId)->orderBy('id', 'desc')->first()
                    ?? Camp::orderBy('id', 'desc')->first();
                if ($c) {
                    $camps->push($c);
                }
            }
        }

        if ($camps->isEmpty()) {
            return [
                'result' => [
                    'message' => 'No camps found in the system to audit.',
                    'pending_evaluators' => [],
                ],
                'widget_type' => null,
                'widget_payload' => null,
                'summary' => 'No camps found.',
            ];
        }

        $campIds = $camps->pluck('id')->all();
        $campTitle = $isAllCamps ? 'All Camps' : ($camps->first()->camp_name ?? 'Camp #' . $camps->first()->id);

        // Get all approved evaluators for target camp(s)
        $registrations = CampEvaluatorRegistration::whereIn('camp_id', $campIds)
            ->where('status', 'approved')
            ->get();

        $nonCompliantEvaluators = [];
        $activeEvaluators = [];

        foreach ($registrations as $reg) {
            $evaluator = User::find($reg->evaluator_id);
            if (!$evaluator) {
                continue;
            }

            $submittedCount = RefereeEvaluation::where('camp_id', $reg->camp_id)
                ->where('evaluator_id', $evaluator->id)
                ->where('status', 'submitted')
                ->count();

            $draftCount = RefereeEvaluation::where('camp_id', $reg->camp_id)
                ->where('evaluator_id', $evaluator->id)
                ->where('status', 'draft')
                ->count();

            $fullName = trim("{$evaluator->first_name} {$evaluator->last_name}") ?: $evaluator->username;
            $campObj = $camps->firstWhere('id', $reg->camp_id);
            $cName = $campObj ? ($campObj->camp_name ?? "Camp #{$campObj->id}") : "Camp #{$reg->camp_id}";

            $evaluatorData = [
                'evaluator_id' => $evaluator->id,
                'camp_id' => $reg->camp_id,
                'camp_name' => $cName,
                'name' => $fullName,
                'email' => $evaluator->email,
                'phone' => $evaluator->phone ?: 'N/A',
                'submitted_evaluations' => $submittedCount,
                'draft_evaluations' => $draftCount,
                'approved_at' => $reg->approved_at ? $reg->approved_at->format('Y-m-d') : 'N/A',
            ];

            if ($submittedCount === 0) {
                $nonCompliantEvaluators[] = $evaluatorData;
            } else {
                $activeEvaluators[] = $evaluatorData;
            }
        }

        $headers = $isAllCamps
            ? ['Evaluator Name', 'Camp', 'Submitted Evals', 'Drafts', 'Email', 'Phone', 'Approved Date']
            : ['Evaluator Name', 'Submitted Evals', 'Drafts', 'Email', 'Phone', 'Approved Date'];

        $widgetPayload = [
            'type' => 'table',
            'title' => "Evaluator Submission Audit: {$campTitle}",
            'headers' => $headers,
            'rows' => array_map(function ($ev) use ($isAllCamps) {
                $row = [
                    'name' => $ev['name'],
                    'evaluator_name' => $ev['name'],
                    'submitted' => $ev['submitted_evaluations'],
                    'submitted_evaluations' => $ev['submitted_evaluations'],
                    'drafts' => $ev['draft_evaluations'],
                    'draft_evaluations' => $ev['draft_evaluations'],
                    'email' => $ev['email'],
                    'phone' => $ev['phone'],
                    'approved_at' => $ev['approved_at'],
                ];
                if ($isAllCamps) {
                    $row['camp'] = $ev['camp_name'];
                }
                return $row;
            }, $nonCompliantEvaluators),
        ];

        return [
            'result' => [
                'camp_id' => $isAllCamps ? null : $camps->first()->id,
                'camp_name' => $campTitle,
                'total_approved_evaluators' => count($registrations),
                'zero_submission_count' => count($nonCompliantEvaluators),
                'active_evaluators_count' => count($activeEvaluators),
                'pending_evaluators' => $nonCompliantEvaluators,
            ],
            'widget_type' => 'table',
            'widget_payload' => $widgetPayload,
            'summary' => count($nonCompliantEvaluators) > 0
                ? "Found " . count($nonCompliantEvaluators) . " approved evaluators with 0 submitted evaluations for {$campTitle}."
                : "All approved evaluators have submitted evaluations for {$campTitle}.",
        ];
    }
}
