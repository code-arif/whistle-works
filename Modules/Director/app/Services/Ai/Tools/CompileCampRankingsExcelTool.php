<?php

namespace Modules\Director\app\Services\Ai\Tools;

use App\Models\RefereeEvaluation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Modules\Director\app\Services\Ai\Contracts\AiToolInterface;
use Modules\Director\Models\Camp;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CompileCampRankingsExcelTool implements AiToolInterface
{
    public function getName(): string
    {
        return 'compile_camp_rankings_excel';
    }

    public function getDescription(): string
    {
        return 'Compile, aggregate, and rank referee evaluation results across one or multiple camps (e.g. "Camp A and Camp B"), generate a downloadable formatted Excel spreadsheet (.xlsx), and return download link and summary.';
    }

    public function getParametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'camp_names' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'List of camp names or titles (e.g. ["Camp A", "Camp B"])',
                ],
                'camp_ids' => [
                    'type' => 'array',
                    'items' => ['type' => 'integer'],
                    'description' => 'List of Camp IDs (optional if camp names are provided)',
                ],
            ],
            'required' => [],
        ];
    }

    public function execute(array $arguments, int $userId): array
    {
        $campNames = $arguments['camp_names'] ?? [];
        $campIds = $arguments['camp_ids'] ?? [];

        // Resolve Camps
        $campsQuery = Camp::query();
        if (!empty($campIds)) {
            $campsQuery->whereIn('id', $campIds);
        } elseif (!empty($campNames)) {
            $campsQuery->where(function ($q) use ($campNames) {
                foreach ($campNames as $name) {
                    $trimmed = trim($name);
                    $q->orWhere('camp_name', 'LIKE', "%{$trimmed}%");
                }
            });
        }

        $camps = $campsQuery->get();

        if ($camps->isEmpty()) {
            // Fallback to recent camps
            $camps = Camp::orderBy('id', 'desc')->limit(2)->get();
        }

        if ($camps->isEmpty()) {
            return [
                'result' => [
                    'message' => 'No camps found to compile rankings.',
                ],
                'widget_type' => null,
                'widget_payload' => null,
                'summary' => 'No camps found to compile rankings.',
            ];
        }

        $allCampRankings = [];
        $campTitles = [];

        foreach ($camps as $camp) {
            $campTitle = $camp->camp_name ?? $camp->name ?? 'Camp #' . $camp->id;
            $campTitles[] = $campTitle;

            $evalStats = RefereeEvaluation::where('camp_id', $camp->id)
                ->where('status', 'submitted')
                ->select([
                    'referee_id',
                    DB::raw('AVG(average_score) as avg_score'),
                    DB::raw('AVG(call_accuracy) as avg_accuracy'),
                    DB::raw('AVG(communication_skills) as avg_comm'),
                    DB::raw('AVG(consistency_of_calls) as avg_cons'),
                    DB::raw('AVG(court_position_mechanics) as avg_mech'),
                    DB::raw('AVG(fitness_mobility) as avg_fit'),
                    DB::raw('AVG(game_awareness) as avg_ga'),
                    DB::raw('COUNT(*) as eval_count'),
                ])
                ->groupBy('referee_id')
                ->orderBy('avg_score', 'desc')
                ->get();

            $campRows = [];
            $rank = 1;
            foreach ($evalStats as $stat) {
                $referee = User::find($stat->referee_id);
                $name = $referee ? (trim("{$referee->first_name} {$referee->last_name}") ?: $referee->username) : 'Referee #' . $stat->referee_id;
                $email = $referee ? $referee->email : 'N/A';

                $campRows[] = [
                    'rank' => $rank++,
                    'camp' => $campTitle,
                    'camp_id' => $camp->id,
                    'referee_id' => $stat->referee_id,
                    'name' => $name,
                    'email' => $email,
                    'average_score' => round((float) $stat->avg_score, 2),
                    'accuracy' => round((float) $stat->avg_accuracy, 2),
                    'communication' => round((float) $stat->avg_comm, 2),
                    'consistency' => round((float) $stat->avg_cons, 2),
                    'mechanics' => round((float) $stat->avg_mech, 2),
                    'fitness' => round((float) $stat->avg_fit, 2),
                    'game_awareness' => round((float) $stat->avg_ga, 2),
                    'evaluations_count' => (int) $stat->eval_count,
                ];
            }

            $allCampRankings[$campTitle] = $campRows;
        }

        // Generate Excel Workbook
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0); // remove default sheet

        // 1. Combined Sheet
        $combinedSheet = $spreadsheet->createSheet(0);
        $combinedSheet->setTitle('Combined Rankings');

        $headers = [
            'A1' => 'Combined Rank',
            'B1' => 'Camp Name',
            'C1' => 'Referee Name',
            'D1' => 'Email',
            'E1' => 'Average Score',
            'F1' => 'Accuracy',
            'G1' => 'Communication',
            'H1' => 'Consistency',
            'I1' => 'Mechanics',
            'J1' => 'Fitness',
            'K1' => 'Game Awareness',
            'L1' => 'Evals Count',
        ];

        foreach ($headers as $cell => $headerText) {
            $combinedSheet->setCellValue($cell, $headerText);
        }

        // Merge all rows and sort by average score
        $mergedRows = [];
        foreach ($allCampRankings as $rows) {
            foreach ($rows as $r) {
                $mergedRows[] = $r;
            }
        }
        usort($mergedRows, fn($a, $b) => $b['average_score'] <=> $a['average_score']);

        $rowIndex = 2;
        foreach ($mergedRows as $idx => $r) {
            $combinedSheet->setCellValue("A{$rowIndex}", $idx + 1);
            $combinedSheet->setCellValue("B{$rowIndex}", $r['camp']);
            $combinedSheet->setCellValue("C{$rowIndex}", $r['name']);
            $combinedSheet->setCellValue("D{$rowIndex}", $r['email']);
            $combinedSheet->setCellValue("E{$rowIndex}", $r['average_score']);
            $combinedSheet->setCellValue("F{$rowIndex}", $r['accuracy']);
            $combinedSheet->setCellValue("G{$rowIndex}", $r['communication']);
            $combinedSheet->setCellValue("H{$rowIndex}", $r['consistency']);
            $combinedSheet->setCellValue("I{$rowIndex}", $r['mechanics']);
            $combinedSheet->setCellValue("J{$rowIndex}", $r['fitness']);
            $combinedSheet->setCellValue("K{$rowIndex}", $r['game_awareness']);
            $combinedSheet->setCellValue("L{$rowIndex}", $r['evaluations_count']);
            $rowIndex++;
        }

        // Apply Styles to Combined Sheet
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A8A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ];
        $combinedSheet->getStyle('A1:L1')->applyFromArray($headerStyle);
        $combinedSheet->getRowDimension(1)->setRowHeight(28);

        foreach (range('A', 'L') as $col) {
            $combinedSheet->getColumnDimension($col)->setAutoSize(true);
        }

        // 2. Individual Camp Sheets
        $sheetIndex = 1;
        foreach ($allCampRankings as $title => $rows) {
            $safeTitle = Str::limit(preg_replace('/[^A-Za-z0-9 _-]/', '', $title), 28);
            $campSheet = $spreadsheet->createSheet($sheetIndex++);
            $campSheet->setTitle($safeTitle ?: "Camp {$sheetIndex}");

            $campHeaders = [
                'A1' => 'Camp Rank',
                'B1' => 'Referee Name',
                'C1' => 'Email',
                'D1' => 'Overall Score',
                'E1' => 'Accuracy',
                'F1' => 'Communication',
                'G1' => 'Consistency',
                'H1' => 'Mechanics',
                'I1' => 'Fitness',
                'J1' => 'Game Awareness',
                'K1' => 'Evals Count',
            ];

            foreach ($campHeaders as $cell => $text) {
                $campSheet->setCellValue($cell, $text);
            }

            $cRow = 2;
            foreach ($rows as $r) {
                $campSheet->setCellValue("A{$cRow}", $r['rank']);
                $campSheet->setCellValue("B{$cRow}", $r['name']);
                $campSheet->setCellValue("C{$cRow}", $r['email']);
                $campSheet->setCellValue("D{$cRow}", $r['average_score']);
                $campSheet->setCellValue("E{$cRow}", $r['accuracy']);
                $campSheet->setCellValue("F{$cRow}", $r['communication']);
                $campSheet->setCellValue("G{$cRow}", $r['consistency']);
                $campSheet->setCellValue("H{$cRow}", $r['mechanics']);
                $campSheet->setCellValue("I{$cRow}", $r['fitness']);
                $campSheet->setCellValue("J{$cRow}", $r['game_awareness']);
                $campSheet->setCellValue("K{$cRow}", $r['evaluations_count']);
                $cRow++;
            }

            $campSheet->getStyle('A1:K1')->applyFromArray($headerStyle);
            $campSheet->getRowDimension(1)->setRowHeight(25);
            foreach (range('A', 'K') as $col) {
                $campSheet->getColumnDimension($col)->setAutoSize(true);
            }
        }

        // Ensure storage export directory exists
        $exportDir = storage_path('app/public/ai_exports');
        if (!File::exists($exportDir)) {
            File::makeDirectory($exportDir, 0755, true);
        }

        $filename = 'camp_rankings_' . date('Ymd_His') . '_' . Str::random(6) . '.xlsx';
        $fullPath = "{$exportDir}/{$filename}";

        $writer = new Xlsx($spreadsheet);
        $writer->save($fullPath);

        $fileSizeBytes = File::size($fullPath);
        $fileSizeFormatted = round($fileSizeBytes / 1024, 1) . ' KB';
        $downloadUrl = url("storage/ai_exports/{$filename}");

        $top5Preview = array_slice($mergedRows, 0, 5);

        $widgetPayload = [
            'type' => 'excel_download',
            'title' => 'Camp Rankings Spreadsheet (.xlsx)',
            'download_url' => $downloadUrl,
            'filename' => $filename,
            'file_size' => $fileSizeFormatted,
            'camps_included' => $campTitles,
            'total_ranked_referees' => count($mergedRows),
            'preview_headers' => ['Rank', 'Camp', 'Referee', 'Avg Score', 'Evals'],
            'preview_rows' => array_map(function ($r, $idx) {
                return [
                    'rank' => '#' . ($idx + 1),
                    'camp' => $r['camp'],
                    'name' => $r['name'],
                    'score' => number_format($r['average_score'], 2),
                    'evals' => $r['evaluations_count'],
                ];
            }, $top5Preview, array_keys($top5Preview)),
        ];

        return [
            'result' => [
                'download_url' => $downloadUrl,
                'filename' => $filename,
                'file_size' => $fileSizeFormatted,
                'camps' => $campTitles,
                'total_referees' => count($mergedRows),
                'top_rankings' => $top5Preview,
            ],
            'widget_type' => 'excel_download',
            'widget_payload' => $widgetPayload,
            'summary' => "Compiled ranking results for " . count($campTitles) . " camps (" . implode(', ', $campTitles) . ") into Excel spreadsheet ({$fileSizeFormatted}). Download link ready.",
        ];
    }
}
