<?php

namespace App\Services\Api\RefereeEvaluation;

use App\Models\User;
use Illuminate\Support\Str;
use Modules\Director\Models\Camp;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RefereeEvaluationExportService
{
    protected RefereeEvaluationService $refereeEvaluationService;

    public function __construct(RefereeEvaluationService $refereeEvaluationService)
    {
        $this->refereeEvaluationService = $refereeEvaluationService;
    }

    /**
     * Export referee evaluations by camp as CSV or Excel (Directors & Evaluators).
     *
     * @param  int|string  $campId
     * @return StreamedResponse|array
     */
    public function exportEvaluationsByCamp(User $user, $campId, string $format = 'xlsx')
    {
        if (! $user->hasAnyRole(['director', 'evaluator'])) {
            return [
                'success' => false,
                'code' => 403,
                'message' => 'Unauthorized access. Only directors and evaluators can export evaluations.',
                'data' => [],
            ];
        }

        $camp = Camp::with(['sportsType.levels', 'sportsType.activeLevels'])->find($campId);
        if (! $camp) {
            return [
                'success' => false,
                'code' => 404,
                'message' => 'Camp not found.',
                'data' => [],
            ];
        }

        // Fetch evaluations using the exact same service and logic as the ranking report
        $evaluationResult = $this->refereeEvaluationService->getEvaluationsByCamp($user, $campId);

        if (! ($evaluationResult['success'] ?? false)) {
            return $evaluationResult;
        }

        $formattedData = $evaluationResult['data']['evaluations'] ?? [];
        $totalEvaluations = (int) ($evaluationResult['data']['total_evaluations'] ?? 0);

        // Resolve all distinct sports levels dynamically for this camp
        $sportLevels = $this->resolveCampSportLevels($camp, $formattedData);

        $isExcel = in_array(strtolower($format), ['excel', 'xlsx'], true) || empty($format);
        $safeCampName = Str::slug($camp->camp_name ?? 'camp', '_');

        if ($isExcel) {
            $spreadsheet = $this->buildStyledExcelWorkbook($camp, $formattedData, $totalEvaluations, $sportLevels, $user);
            $writer = new Xlsx($spreadsheet);
            $fileName = $safeCampName.'_referee_evaluations_'.date('Y_m_d_His').'.xlsx';
            $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        } else {
            $spreadsheet = $this->buildCsvWorkbook($formattedData, $sportLevels, $user);
            $writer = new Csv($spreadsheet);
            $writer->setUseBOM(true);
            $fileName = $safeCampName.'_referee_evaluations_'.date('Y_m_d_His').'.csv';
            $contentType = 'text/csv; charset=UTF-8';
        }

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
            'Cache-Control' => 'max-age=0, must-revalidate, post-check=0, pre-check=0',
            'Pragma' => 'public',
        ]);
    }

    /**
     * Resolve all unique sport levels associated with the camp or present in evaluations.
     */
    protected function resolveCampSportLevels(Camp $camp, array $formattedData): array
    {
        $levels = [];

        if ($camp->sportsType) {
            $campLevels = $camp->sportsType->levels ?? $camp->sportsType->activeLevels ?? collect();
            foreach ($campLevels as $lvl) {
                $levelName = trim($lvl->name ?? '');
                if ($levelName && ! in_array($levelName, $levels, true)) {
                    $levels[] = $levelName;
                }
            }
        }

        // Append any recommended levels from evaluation records that might not be in master sportsType
        foreach ($formattedData as $refData) {
            if (! empty($refData['recommended_levels']) && is_array($refData['recommended_levels'])) {
                foreach ($refData['recommended_levels'] as $rec) {
                    $levelName = trim($rec['name'] ?? $rec['level'] ?? '');
                    if ($levelName && ! in_array($levelName, $levels, true)) {
                        $levels[] = $levelName;
                    }
                }
            }
        }

        if (empty($levels)) {
            $levels = ['NCAA D1', 'NCAA D2', 'NAIA', 'JUCO', 'HS', 'JH/ELEM'];
        }

        return $levels;
    }

    /**
     * Build rich, executive-styled Excel workbook (.xlsx).
     */
    protected function buildStyledExcelWorkbook(
        Camp $camp,
        array $formattedData,
        int $totalEvaluations,
        array $sportLevels,
        User $user
    ): Spreadsheet {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $sheetTitle = Str::limit(preg_replace('/[^A-Za-z0-9 _-]/', '', $camp->camp_name ?? 'Camp'), 28) ?: 'Rankings';
        $sheet->setTitle($sheetTitle);
        $sheet->setShowGridLines(true);

        // Build Column Definitions
        $columns = [
            ['key' => 'rank',                      'label' => 'Rank',                     'category' => 'info',    'width' => 9],
            ['key' => 'jersey_number',             'label' => 'Jersey #',                 'category' => 'info',    'width' => 11],
            ['key' => 'referee_name',              'label' => 'Referee Name',             'category' => 'info',    'width' => 24],
            ['key' => 'referee_email',             'label' => 'Email',                    'category' => 'info',    'width' => 26],
            ['key' => 'referee_phone',             'label' => 'Phone',                    'category' => 'info',    'width' => 16],
            ['key' => 'referee_address',           'label' => 'Address',                  'category' => 'info',    'width' => 22],
            ['key' => 'total_evaluations',         'label' => 'Total Evals',              'category' => 'info',    'width' => 13],

            ['key' => 'overall_avg',               'label' => 'Overall Avg Score',        'category' => 'score',   'width' => 18],
            ['key' => 'call_accuracy',             'label' => 'Call Accuracy',            'category' => 'score',   'width' => 15],
            ['key' => 'communication_skills',      'label' => 'Communication',            'category' => 'score',   'width' => 16],
            ['key' => 'consistency_of_calls',      'label' => 'Consistency',              'category' => 'score',   'width' => 15],
            ['key' => 'court_position_mechanics',  'label' => 'Court Mechanics',          'category' => 'score',   'width' => 16],
            ['key' => 'fitness_mobility',          'label' => 'Fitness & Mobility',       'category' => 'score',   'width' => 17],
            ['key' => 'game_awareness',            'label' => 'Game Awareness',           'category' => 'score',   'width' => 16],

            ['key' => 'highest_recommended_level', 'label' => 'Highest Recommended Level', 'category' => 'level',   'width' => 24],
        ];

        // Dynamic sport level columns
        foreach ($sportLevels as $levelName) {
            $columns[] = [
                'key' => 'level_'.$levelName,
                'label' => $levelName,
                'category' => 'level',
                'width' => max(12, strlen($levelName) + 4),
                'level' => $levelName,
            ];
        }

        // Additional notes & feedback columns
        $columns[] = ['key' => 'evaluators',        'label' => 'Evaluators',               'category' => 'notes',   'width' => 28];
        $columns[] = ['key' => 'comments',          'label' => 'Referee Feedback',         'category' => 'notes',   'width' => 38];

        if ($user->hasAnyRole(['director', 'evaluator'])) {
            $columns[] = ['key' => 'private_comments',  'label' => 'Private Director Comments', 'category' => 'notes',   'width' => 38];
        }

        if ($user->hasRole('director')) {
            $columns[] = ['key' => 'referee_note',      'label' => 'Director Notes',           'category' => 'notes',   'width' => 30];
        }

        $totalCols = count($columns);
        $lastColLetter = Coordinate::stringFromColumnIndex($totalCols);

        // ----------------------------------------------------
        // ROW 1: Luxury Dark Banner
        // ----------------------------------------------------
        $sheet->mergeCells("A1:{$lastColLetter}1");
        $sheet->setCellValue('A1', 'WHISTLE WORKS — CAMP REFEREE EVALUATION & RANKING DOSSIER');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
                'color' => ['rgb' => 'FFFFFF'],
                'name' => 'Segoe UI',
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E293B'], // Slate-800
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(36);

        // ----------------------------------------------------
        // ROW 2: Metadata Sub-banner
        // ----------------------------------------------------
        $sportTitle = $camp->sportsType->sports_name ?? 'Sports Camp';
        $locationTitle = $camp->location ?? ($camp->address ?? 'On-Site');
        $totalReferees = count($formattedData);
        $generatedAt = date('M d, Y h:i A');

        $metaText = "Camp: {$camp->camp_name}   |   Sport: {$sportTitle}   |   Location: {$locationTitle}   |   Evaluated Referees: {$totalReferees}   |   Total Evaluations: {$totalEvaluations}   |   Exported: {$generatedAt}";
        $sheet->mergeCells("A2:{$lastColLetter}2");
        $sheet->setCellValue('A2', $metaText);
        $sheet->getStyle('A2')->applyFromArray([
            'font' => [
                'bold' => false,
                'size' => 10,
                'color' => ['rgb' => 'CBD5E1'], // Slate-300
                'name' => 'Segoe UI',
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0F172A'], // Slate-900
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(24);

        // ----------------------------------------------------
        // ROW 3: Whistle Works Coral Accent Line
        // ----------------------------------------------------
        $sheet->mergeCells("A3:{$lastColLetter}3");
        $sheet->getStyle('A3')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F29F67'], // Brand Signature Accent
            ],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(4);

        // ----------------------------------------------------
        // ROW 4: Table Headers
        // ----------------------------------------------------
        $sheet->getRowDimension(4)->setRowHeight(28);

        $categoryFills = [
            'info' => '1E3A8A', // Deep Navy Blue
            'score' => '0F766E', // Deep Teal / Whistle Works Emerald
            'level' => '4338CA', // Deep Indigo
            'notes' => '334155', // Slate-700
        ];

        foreach ($columns as $idx => $col) {
            $colLetter = Coordinate::stringFromColumnIndex($idx + 1);
            $sheet->setCellValue("{$colLetter}4", $col['label']);

            $fillColor = $categoryFills[$col['category']] ?? '1E293B';
            $sheet->getStyle("{$colLetter}4")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 10,
                    'color' => ['rgb' => 'FFFFFF'],
                    'name' => 'Segoe UI',
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $fillColor],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '475569'],
                    ],
                ],
            ]);

            $sheet->getColumnDimension($colLetter)->setWidth($col['width']);
        }

        // ----------------------------------------------------
        // ROWS 5+: Data Rows
        // ----------------------------------------------------
        $currentRow = 5;

        if (empty($formattedData)) {
            $sheet->mergeCells("A5:{$lastColLetter}5");
            $sheet->setCellValue('A5', 'No referee evaluation records found for this camp.');
            $sheet->getStyle('A5')->applyFromArray([
                'font' => [
                    'italic' => true,
                    'color' => ['rgb' => '64748B'],
                    'size' => 11,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
            $sheet->getRowDimension(5)->setRowHeight(32);

            return $spreadsheet;
        }

        foreach ($formattedData as $dataIdx => $row) {
            $sheet->getRowDimension($currentRow)->setRowHeight(24);
            $isEven = ($dataIdx % 2 === 0);
            $zebraBg = $isEven ? 'FFFFFF' : 'F8FAFC';

            // Pre-calculate referee level recommendation map
            $levelMap = [];
            if (! empty($row['recommended_levels']) && is_array($row['recommended_levels'])) {
                foreach ($row['recommended_levels'] as $rec) {
                    $lvl = trim($rec['name'] ?? $rec['level'] ?? '');
                    if ($lvl) {
                        $levelMap[$lvl] = (int) ($rec['count'] ?? 1);
                    }
                }
            }

            $overallScore = isset($row['averages']['overall']) ? (float) $row['averages']['overall'] : 0.0;

            foreach ($columns as $colIdx => $col) {
                $colLetter = Coordinate::stringFromColumnIndex($colIdx + 1);
                $cellCoordinate = "{$colLetter}{$currentRow}";
                $key = $col['key'];

                $cellStyle = [
                    'font' => [
                        'name' => 'Segoe UI',
                        'size' => 10,
                        'color' => ['rgb' => '0F172A'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $zebraBg],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'E2E8F0'],
                        ],
                    ],
                ];

                switch ($key) {
                    case 'rank':
                        $rank = (int) ($row['rank'] ?? ($dataIdx + 1));
                        $sheet->setCellValue($cellCoordinate, $rank);
                        $cellStyle['alignment']['horizontal'] = Alignment::HORIZONTAL_CENTER;
                        $cellStyle['font']['bold'] = true;

                        if ($rank === 1) {
                            $cellStyle['fill']['startColor']['rgb'] = 'FEF3C7'; // Gold
                            $cellStyle['font']['color']['rgb'] = '92400E';
                        } elseif ($rank === 2) {
                            $cellStyle['fill']['startColor']['rgb'] = 'E2E8F0'; // Silver
                            $cellStyle['font']['color']['rgb'] = '334155';
                        } elseif ($rank === 3) {
                            $cellStyle['fill']['startColor']['rgb'] = 'FFEDD5'; // Bronze
                            $cellStyle['font']['color']['rgb'] = '9A3412';
                        }
                        break;

                    case 'jersey_number':
                        $jersey = $row['jersey_number'] ? '#'.$row['jersey_number'] : '-';
                        $sheet->setCellValue($cellCoordinate, $jersey);
                        $cellStyle['alignment']['horizontal'] = Alignment::HORIZONTAL_CENTER;
                        $cellStyle['font']['bold'] = true;
                        $cellStyle['font']['color']['rgb'] = '475569';
                        break;

                    case 'referee_name':
                        $sheet->setCellValue($cellCoordinate, $row['referee_name'] ?? 'N/A');
                        $cellStyle['font']['bold'] = true;
                        $cellStyle['alignment']['horizontal'] = Alignment::HORIZONTAL_LEFT;
                        break;

                    case 'referee_email':
                        $sheet->setCellValue($cellCoordinate, $row['referee_email'] ?? 'N/A');
                        $cellStyle['alignment']['horizontal'] = Alignment::HORIZONTAL_LEFT;
                        $cellStyle['font']['color']['rgb'] = '2563EB';
                        break;

                    case 'referee_phone':
                        $sheet->setCellValue($cellCoordinate, $row['referee_phone'] ?: '-');
                        $cellStyle['alignment']['horizontal'] = Alignment::HORIZONTAL_CENTER;
                        break;

                    case 'referee_address':
                        $sheet->setCellValue($cellCoordinate, $row['referee_address'] ?: '-');
                        $cellStyle['alignment']['horizontal'] = Alignment::HORIZONTAL_LEFT;
                        break;

                    case 'total_evaluations':
                        $sheet->setCellValue($cellCoordinate, (int) ($row['total_evaluations'] ?? 0));
                        $cellStyle['alignment']['horizontal'] = Alignment::HORIZONTAL_CENTER;
                        $cellStyle['font']['bold'] = true;
                        break;

                    case 'overall_avg':
                        $sheet->setCellValue($cellCoordinate, $overallScore);
                        $cellStyle['alignment']['horizontal'] = Alignment::HORIZONTAL_CENTER;
                        $cellStyle['font']['bold'] = true;

                        if ($overallScore >= 9.0) {
                            $cellStyle['fill']['startColor']['rgb'] = 'DCFCE7'; // Emerald
                            $cellStyle['font']['color']['rgb'] = '15803D';
                        } elseif ($overallScore >= 7.5) {
                            $cellStyle['fill']['startColor']['rgb'] = 'DBEAFE'; // Blue
                            $cellStyle['font']['color']['rgb'] = '1D4ED8';
                        } elseif ($overallScore >= 6.0) {
                            $cellStyle['fill']['startColor']['rgb'] = 'FEF9C3'; // Amber
                            $cellStyle['font']['color']['rgb'] = 'A16207';
                        } else {
                            $cellStyle['fill']['startColor']['rgb'] = 'FFE4E6'; // Rose
                            $cellStyle['font']['color']['rgb'] = 'BE123C';
                        }
                        break;

                    case 'call_accuracy':
                    case 'communication_skills':
                    case 'consistency_of_calls':
                    case 'court_position_mechanics':
                    case 'fitness_mobility':
                    case 'game_awareness':
                        $scoreVal = isset($row['averages'][$key]) ? (float) $row['averages'][$key] : 0.0;
                        $sheet->setCellValue($cellCoordinate, $scoreVal);
                        $cellStyle['alignment']['horizontal'] = Alignment::HORIZONTAL_CENTER;
                        break;

                    case 'highest_recommended_level':
                        $highest = $row['highest_recommended_level'] ?: '-';
                        $sheet->setCellValue($cellCoordinate, $highest);
                        $cellStyle['alignment']['horizontal'] = Alignment::HORIZONTAL_CENTER;
                        $cellStyle['font']['bold'] = true;
                        if ($highest !== '-') {
                            $cellStyle['fill']['startColor']['rgb'] = 'EEF2FF'; // Soft Indigo
                            $cellStyle['font']['color']['rgb'] = '4338CA';
                        }
                        break;

                    case 'evaluators':
                        $evals = is_array($row['evaluators'] ?? null)
                            ? implode(', ', array_filter($row['evaluators']))
                            : ($row['evaluators'] ?? '-');
                        $sheet->setCellValue($cellCoordinate, $evals ?: '-');
                        $cellStyle['alignment']['horizontal'] = Alignment::HORIZONTAL_LEFT;
                        $cellStyle['alignment']['wrapText'] = true;
                        break;

                    case 'comments':
                        $feedback = is_array($row['comments'] ?? null)
                            ? implode(' | ', array_filter($row['comments']))
                            : ($row['comments'] ?? '-');
                        $sheet->setCellValue($cellCoordinate, $feedback ?: '-');
                        $cellStyle['alignment']['horizontal'] = Alignment::HORIZONTAL_LEFT;
                        $cellStyle['alignment']['wrapText'] = true;
                        break;

                    case 'private_comments':
                        $priv = is_array($row['private_comments'] ?? null)
                            ? implode(' | ', array_filter($row['private_comments']))
                            : ($row['private_comments'] ?? '-');
                        $sheet->setCellValue($cellCoordinate, $priv ?: '-');
                        $cellStyle['alignment']['horizontal'] = Alignment::HORIZONTAL_LEFT;
                        $cellStyle['alignment']['wrapText'] = true;
                        $cellStyle['font']['italic'] = true;
                        $cellStyle['font']['color']['rgb'] = '475569';
                        break;

                    case 'referee_note':
                        $note = $row['referee_note'] ?? '-';
                        $sheet->setCellValue($cellCoordinate, $note ?: '-');
                        $cellStyle['alignment']['horizontal'] = Alignment::HORIZONTAL_LEFT;
                        $cellStyle['alignment']['wrapText'] = true;
                        break;

                    default:
                        // Dynamic Sport Level count
                        if (str_starts_with($key, 'level_')) {
                            $targetLevel = $col['level'] ?? substr($key, 6);
                            $cnt = $levelMap[$targetLevel] ?? 0;
                            if ($cnt > 0) {
                                $sheet->setCellValue($cellCoordinate, $cnt);
                                $cellStyle['font']['bold'] = true;
                                $cellStyle['font']['color']['rgb'] = '0F766E';
                                $cellStyle['fill']['startColor']['rgb'] = 'F0FDF4';
                            } else {
                                $sheet->setCellValue($cellCoordinate, '-');
                                $cellStyle['font']['color']['rgb'] = '94A3B8';
                            }
                            $cellStyle['alignment']['horizontal'] = Alignment::HORIZONTAL_CENTER;
                        } else {
                            $sheet->setCellValue($cellCoordinate, '-');
                        }
                        break;
                }

                $sheet->getStyle($cellCoordinate)->applyFromArray($cellStyle);
            }

            $currentRow++;
        }

        // Apply number format to score columns
        $sheet->getStyle("H5:N{$currentRow}")->getNumberFormat()->setFormatCode('0.00');

        // ----------------------------------------------------
        // SUMMARY FOOTER ROW: Camp Averages
        // ----------------------------------------------------
        $footerRow = $currentRow;
        $sheet->getRowDimension($footerRow)->setRowHeight(26);

        // Merge Columns A to F for Footer Label
        $sheet->mergeCells("A{$footerRow}:F{$footerRow}");
        $sheet->setCellValue("A{$footerRow}", 'CAMP AVERAGE / OVERALL STATS');
        $sheet->getStyle("A{$footerRow}:F{$footerRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 10,
                'color' => ['rgb' => '0F172A'],
                'name' => 'Segoe UI',
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2E8F0'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $lastDataRow = $currentRow - 1;

        // Sum of evaluations
        $sheet->setCellValue("G{$footerRow}", "=SUM(G5:G{$lastDataRow})");

        // Average formulas for Overall and 6 criteria
        foreach (['H', 'I', 'J', 'K', 'L', 'M', 'N'] as $scoreCol) {
            $sheet->setCellValue("{$scoreCol}{$footerRow}", "=AVERAGE({$scoreCol}5:{$scoreCol}{$lastDataRow})");
        }

        // Apply style to entire footer row
        $sheet->getStyle("A{$footerRow}:{$lastColLetter}{$footerRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 10,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2E8F0'],
            ],
            'borders' => [
                'top' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '64748B'],
                ],
                'bottom' => [
                    'borderStyle' => Border::BORDER_DOUBLE,
                    'color' => ['rgb' => '0F172A'],
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getStyle("H{$footerRow}:N{$footerRow}")->getNumberFormat()->setFormatCode('0.00');

        // Freeze panes at D5: Columns A-C and Rows 1-4 locked
        $sheet->freezePane('D5');

        return $spreadsheet;
    }

    /**
     * Build clean tabular CSV workbook without merged banners.
     */
    protected function buildCsvWorkbook(array $formattedData, array $sportLevels, User $user): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'Rank',
            'Jersey #',
            'Referee Name',
            'Email',
            'Phone',
            'Address',
            'Total Evaluations',
            'Overall Avg Score',
            'Call Accuracy Avg',
            'Communication Skills Avg',
            'Consistency of Calls Avg',
            'Court Position Mechanics Avg',
            'Fitness & Mobility Avg',
            'Game Awareness Avg',
            'Highest Recommended Level',
        ];

        foreach ($sportLevels as $levelName) {
            $headers[] = $levelName;
        }

        $headers[] = 'Evaluators';
        $headers[] = 'Referee Feedback';

        if ($user->hasAnyRole(['director', 'evaluator'])) {
            $headers[] = 'Private Director Comments';
        }

        if ($user->hasRole('director')) {
            $headers[] = 'Director Notes';
        }

        foreach ($headers as $idx => $label) {
            $colLetter = Coordinate::stringFromColumnIndex($idx + 1);
            $sheet->setCellValue("{$colLetter}1", $label);
        }

        $rowIndex = 2;
        foreach ($formattedData as $dataIdx => $row) {
            $levelMap = [];
            if (! empty($row['recommended_levels']) && is_array($row['recommended_levels'])) {
                foreach ($row['recommended_levels'] as $rec) {
                    $lvl = trim($rec['name'] ?? $rec['level'] ?? '');
                    if ($lvl) {
                        $levelMap[$lvl] = (int) ($rec['count'] ?? 1);
                    }
                }
            }

            $colIdx = 1;
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIdx++).$rowIndex, $row['rank'] ?? ($dataIdx + 1));
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIdx++).$rowIndex, $row['jersey_number'] ? '#'.$row['jersey_number'] : '-');
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIdx++).$rowIndex, $row['referee_name'] ?? 'N/A');
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIdx++).$rowIndex, $row['referee_email'] ?? 'N/A');
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIdx++).$rowIndex, $row['referee_phone'] ?: '-');
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIdx++).$rowIndex, $row['referee_address'] ?: '-');
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIdx++).$rowIndex, (int) ($row['total_evaluations'] ?? 0));

            $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIdx++).$rowIndex, isset($row['averages']['overall']) ? (float) $row['averages']['overall'] : 0.0);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIdx++).$rowIndex, isset($row['averages']['call_accuracy']) ? (float) $row['averages']['call_accuracy'] : 0.0);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIdx++).$rowIndex, isset($row['averages']['communication_skills']) ? (float) $row['averages']['communication_skills'] : 0.0);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIdx++).$rowIndex, isset($row['averages']['consistency_of_calls']) ? (float) $row['averages']['consistency_of_calls'] : 0.0);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIdx++).$rowIndex, isset($row['averages']['court_position_mechanics']) ? (float) $row['averages']['court_position_mechanics'] : 0.0);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIdx++).$rowIndex, isset($row['averages']['fitness_mobility']) ? (float) $row['averages']['fitness_mobility'] : 0.0);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIdx++).$rowIndex, isset($row['averages']['game_awareness']) ? (float) $row['averages']['game_awareness'] : 0.0);

            $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIdx++).$rowIndex, $row['highest_recommended_level'] ?: '-');

            foreach ($sportLevels as $levelName) {
                $cnt = $levelMap[$levelName] ?? 0;
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIdx++).$rowIndex, $cnt > 0 ? $cnt : '-');
            }

            $evals = is_array($row['evaluators'] ?? null) ? implode(', ', array_filter($row['evaluators'])) : ($row['evaluators'] ?? '-');
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIdx++).$rowIndex, $evals ?: '-');

            $feedback = is_array($row['comments'] ?? null) ? implode(' | ', array_filter($row['comments'])) : ($row['comments'] ?? '-');
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIdx++).$rowIndex, $feedback ?: '-');

            if ($user->hasAnyRole(['director', 'evaluator'])) {
                $priv = is_array($row['private_comments'] ?? null) ? implode(' | ', array_filter($row['private_comments'])) : ($row['private_comments'] ?? '-');
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIdx++).$rowIndex, $priv ?: '-');
            }

            if ($user->hasRole('director')) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIdx++).$rowIndex, $row['referee_note'] ?? '-');
            }

            $rowIndex++;
        }

        return $spreadsheet;
    }
}
