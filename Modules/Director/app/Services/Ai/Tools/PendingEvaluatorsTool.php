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
        $campId = $arguments['camp_id'] ?? null;
        $campName = $arguments['camp_name'] ?? null;

        $camp = null;
        if ($campId) {
            $camp = Camp::find($campId);
        } elseif (!empty($campName)) {
            $camp = Camp::where('camp_name', 'LIKE', "%{$campName}%")->first();
        }

        if (!$camp) {
            // Pick most recent active camp for user if none specified
            $camp = Camp::where('director_id', $userId)->orderBy('id', 'desc')->first()
                ?? Camp::orderBy('id', 'desc')->first();
        }

        if (!$camp) {
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

        // Get all approved evaluators for this camp
        $registrations = CampEvaluatorRegistration::where('camp_id', $camp->id)
            ->where('status', 'approved')
            ->get();

        $nonCompliantEvaluators = [];
        $activeEvaluators = [];

        foreach ($registrations as $reg) {
            $evaluator = User::find($reg->evaluator_id);
            if (!$evaluator) {
                continue;
            }

            $submittedCount = RefereeEvaluation::where('camp_id', $camp->id)
                ->where('evaluator_id', $evaluator->id)
                ->where('status', 'submitted')
                ->count();

            $draftCount = RefereeEvaluation::where('camp_id', $camp->id)
                ->where('evaluator_id', $evaluator->id)
                ->where('status', 'draft')
                ->count();

            $fullName = trim("{$evaluator->first_name} {$evaluator->last_name}") ?: $evaluator->username;

            $evaluatorData = [
                'evaluator_id' => $evaluator->id,
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

        $campTitle = $camp->camp_name ?? $camp->name ?? 'Camp #' . $camp->id;

        $widgetPayload = [
            'type' => 'table',
            'title' => "Evaluator Submission Audit: {$campTitle}",
            'headers' => ['Evaluator Name', 'Submitted Evals', 'Drafts', 'Email', 'Phone', 'Approved Date'],
            'rows' => array_map(function ($ev) {
                return [
                    'name' => $ev['name'],
                    'submitted' => $ev['submitted_evaluations'],
                    'drafts' => $ev['draft_evaluations'],
                    'email' => $ev['email'],
                    'phone' => $ev['phone'],
                    'approved_at' => $ev['approved_at'],
                ];
            }, $nonCompliantEvaluators),
        ];

        return [
            'result' => [
                'camp_id' => $camp->id,
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
