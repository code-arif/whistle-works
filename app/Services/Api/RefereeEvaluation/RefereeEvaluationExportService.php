<?php

namespace App\Services\Api\RefereeEvaluation;

use App\Models\AssistantDirectorPermission;
use App\Models\RefereeEvaluation;
use App\Models\User;
use Modules\Director\Models\Camp;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RefereeEvaluationExportService
{
    /**
     * Export referee evaluations by camp as CSV or Excel (Director only).
     *
     * @param  User        $user
     * @param  int|string  $campId
     * @param  string      $format
     * @return StreamedResponse|array
     */
    public function exportEvaluationsByCamp(User $user, $campId, string $format = 'csv')
    {
        if (!$user->hasRole('director')) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'Unauthorized access. Only directors can export evaluations.',
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

        $isOwner = $camp->director_id === $user->id;
        $isAssistant = AssistantDirectorPermission::where('camp_id', $campId)
            ->where('assistant_director_id', $user->id)
            ->exists();

        if (!$isOwner && !$isAssistant) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'You can only export evaluations from your own or assigned camps.',
                'data'    => [],
            ];
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

            $fixedLevels = ['NCAA D1', 'NCAA D2', 'NAIA', 'JUCO', 'HS', 'JH/ELEM'];
            $levelCounts = array_fill_keys($fixedLevels, 0);

            foreach ($refereeEvaluations as $evaluation) {
                foreach ($evaluation->recommendedLevels as $level) {
                    if (array_key_exists($level->level, $levelCounts)) {
                        $levelCounts[$level->level]++;
                    } else {
                        $levelCounts[$level->level] = 1;
                    }
                }
            }

            $recommendedLevelsFormatted = [];
            foreach ($levelCounts as $level => $count) {
                if ($count > 0) {
                    $recommendedLevelsFormatted[] = [
                        'level' => $level,
                        'count' => $count,
                    ];
                }
            }

            usort($recommendedLevelsFormatted, fn($a, $b) => $b['count'] <=> $a['count']);

            $highestRecommendedLevel = !empty($recommendedLevelsFormatted)
                ? $recommendedLevelsFormatted[0]['level']
                : 'N/A';

            $evaluators = $refereeEvaluations->map(function ($eval) {
                return $eval->evaluator ? ($eval->evaluator->first_name . ' ' . $eval->evaluator->last_name) : 'N/A';
            })->unique()->filter()->implode(', ');

            $allComments = $refereeEvaluations->filter(function ($eval) {
                return !empty($eval->referee_feedback);
            })->pluck('referee_feedback')->implode(' | ');

            $formattedData[] = [
                'referee_name'              => $referee ? trim($referee->first_name . ' ' . $referee->last_name) : 'N/A',
                'referee_email'             => $referee->email ?? 'N/A',
                'referee_address'           => $referee->address ?? 'N/A',
                'total_evaluations'         => $refereeEvaluations->count(),
                'overall_avg'               => $overallAvg,
                'call_accuracy'             => $avgCallAccuracy,
                'communication_skills'      => $avgCommunication,
                'consistency_of_calls'      => $avgConsistency,
                'court_position_mechanics'  => $avgCourtPosition,
                'fitness_mobility'          => $avgFitness,
                'game_awareness'            => $avgGameAwareness,
                'highest_recommended_level' => $highestRecommendedLevel ?: 'N/A',
                'ncaa_d1_count'             => !empty($levelCounts['NCAA D1']) ? $levelCounts['NCAA D1'] : '-',
                'ncaa_d2_count'             => !empty($levelCounts['NCAA D2']) ? $levelCounts['NCAA D2'] : '-',
                'naia_count'                => !empty($levelCounts['NAIA']) ? $levelCounts['NAIA'] : '-',
                'juco_count'                => !empty($levelCounts['JUCO']) ? $levelCounts['JUCO'] : '-',
                'hs_count'                  => !empty($levelCounts['HS']) ? $levelCounts['HS'] : '-',
                'jh_elem_count'             => !empty($levelCounts['JH/ELEM']) ? $levelCounts['JH/ELEM'] : '-',
                'evaluators'                => $evaluators ?: 'N/A',
                'comments'                  => $allComments ?: 'N/A',
            ];
        }

        usort($formattedData, fn($a, $b) => $b['overall_avg'] <=> $a['overall_avg']);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $campName = $camp->camp_name ?? 'Camp';

        $sheet->setCellValue('A1', 'Camp Name:');
        $sheet->setCellValue('B1', $campName . ' - Referee Evaluations Export');

        $headers = [
            'A3' => 'Referee Name',
            'B3' => 'Email',
            'C3' => 'Address',
            'D3' => 'Total Evaluations',
            'E3' => 'Overall Avg Score',
            'F3' => 'Call Accuracy Avg',
            'G3' => 'Communication Skills Avg',
            'H3' => 'Consistency of Calls Avg',
            'I3' => 'Court Position Mechanics Avg',
            'J3' => 'Fitness & Mobility Avg',
            'K3' => 'Game Awareness Avg',
            'L3' => 'Highest Recommended Level',
            'M3' => 'NCAA D1',
            'N3' => 'NCAA D2',
            'O3' => 'NAIA',
            'P3' => 'JUCO',
            'Q3' => 'HS',
            'R3' => 'JH/ELEM',
            'S3' => 'Evaluators',
            'T3' => 'Comments / Feedback',
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        $rowIndex = 4;
        foreach ($formattedData as $data) {
            $sheet->setCellValue('A' . $rowIndex, $data['referee_name']);
            $sheet->setCellValue('B' . $rowIndex, $data['referee_email']);
            $sheet->setCellValue('C' . $rowIndex, $data['referee_address']);
            $sheet->setCellValue('D' . $rowIndex, $data['total_evaluations']);
            $sheet->setCellValue('E' . $rowIndex, $data['overall_avg']);
            $sheet->setCellValue('F' . $rowIndex, $data['call_accuracy']);
            $sheet->setCellValue('G' . $rowIndex, $data['communication_skills']);
            $sheet->setCellValue('H' . $rowIndex, $data['consistency_of_calls']);
            $sheet->setCellValue('I' . $rowIndex, $data['court_position_mechanics']);
            $sheet->setCellValue('J' . $rowIndex, $data['fitness_mobility']);
            $sheet->setCellValue('K' . $rowIndex, $data['game_awareness']);
            $sheet->setCellValue('L' . $rowIndex, $data['highest_recommended_level']);
            $sheet->setCellValue('M' . $rowIndex, $data['ncaa_d1_count']);
            $sheet->setCellValue('N' . $rowIndex, $data['ncaa_d2_count']);
            $sheet->setCellValue('O' . $rowIndex, $data['naia_count']);
            $sheet->setCellValue('P' . $rowIndex, $data['juco_count']);
            $sheet->setCellValue('Q' . $rowIndex, $data['hs_count']);
            $sheet->setCellValue('R' . $rowIndex, $data['jh_elem_count']);
            $sheet->setCellValue('S' . $rowIndex, $data['evaluators']);
            $sheet->setCellValue('T' . $rowIndex, $data['comments']);
            $rowIndex++;
        }

        $isExcel = in_array(strtolower($format), ['excel', 'xlsx'], true);
        $safeCampName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $campName);

        if ($isExcel) {
            $writer = new Xlsx($spreadsheet);
            $fileName = $safeCampName . '_referee_evaluations_' . date('Y_m_d') . '.xlsx';
            $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        } else {
            $writer = new Csv($spreadsheet);
            $writer->setUseBOM(true);
            $fileName = $safeCampName . '_referee_evaluations_' . date('Y_m_d') . '.csv';
            $contentType = 'text/csv; charset=UTF-8';
        }

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type'  => $contentType,
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Pragma'        => 'public',
        ]);
    }
}
