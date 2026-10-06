<?php

namespace App\Services\Api\Roster;

use App\Models\CampRefereeJearsyNumber;
use App\Models\User;
use Modules\Director\Models\Camp;
use Modules\Director\Models\GameSlot;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RosterService
{
    /**
     * Get comprehensive camp details for roster view.
     *
     * @param  int|string  $campId
     * @return array
     */
    public function getCampDetails($campId): array
    {
        $camp = Camp::where('id', $campId)
            ->with([
                'sportsType',
                'director',
                'schedule.locations.gameSlots',
                'schedule.gameSlots.slotAssignments.assignable',
                'schedule.gameSlots.slotAssignments.gameSlotAssignmentPosition',
                'checkedInReferees.referee',
                'evaluations.evaluator',
                'crews.members',
            ])
            ->first();

        if (!$camp) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Camp not found.',
                'data'    => [],
            ];
        }

        // Get all game slot assignments for this camp
        $gameSlotAssignments = $camp->schedule?->gameSlots->flatMap(function ($gameSlot) {
            return $gameSlot->slotAssignments;
        }) ?? collect();

        $response = [
            'camp' => [
                'camp_id'     => $camp->id,
                'camp_name'   => $camp->camp_name,
                'camp_logo'   => $camp->camp_logo ? asset($camp->camp_logo) : asset('default/no_image.webp'),
                'location'    => $camp->location,
                'address'     => $camp->address ?? null,
                'latitude'    => $camp->latitude,
                'longitude'   => $camp->longitude,
                'price'       => $camp->price,
                'sports_type' => [
                    'id'   => $camp->sportsType->id ?? null,
                    'name' => $camp->sportsType->sports_name ?? null,
                    'icon' => $camp->sportsType?->icon ? asset($camp->sportsType->icon) : asset('default/no_image.webp'),
                ],
                'director'    => [
                    'id'        => $camp->director->id ?? null,
                    'name'      => $camp->director ? trim($camp->director->first_name . ' ' . $camp->director->last_name) : 'N/A',
                    'avatar'    => $camp->director?->avatar ? asset($camp->director->avatar) : asset('default/profile.jpg'),
                    'email'     => $camp->director->email ?? null,
                    'phone'     => $camp->director->phone ?? null,
                    'address'   => $camp->director->address ?? null,
                    'biography' => $camp->director->biography ?? null,
                ],
                'schedule'    => [
                    'locations'   => $camp->schedule?->locations->map(function ($location) {
                        return [
                            'id'        => $location->id,
                            'name'      => $location->location_name,
                            'latitude'  => $location->latitude,
                            'longitude' => $location->longitude,
                            'address'   => $location->address ?? null,
                        ];
                    }) ?? collect(),
                    'game_courts' => $camp->schedule?->gameSlots->map(function ($gameSlot) use ($camp) {
                        return [
                            'id'           => $gameSlot->id,
                            'game_date'    => $gameSlot->game_date->toDateString(),
                            'start_time'   => $gameSlot->start_time,
                            'end_time'     => $gameSlot->end_time,
                            'court_name'   => $gameSlot->court_name,
                            'status'       => $gameSlot->status,
                            'is_block'     => $gameSlot->is_block,
                            'location'     => $gameSlot->location->location_name ?? 'N/A',
                            'address'      => $gameSlot->location->address ?? null,
                            'assignments'  => $gameSlot->slotAssignments
                                ->map(function ($assignment) use ($camp) {
                                    if ($assignment->assignment_type === 'crew') {
                                        return [
                                            'type'             => 'crew',
                                            'crew_id'          => $assignment->assignable_id,
                                            'crew_name'        => $assignment->assignable->name ?? 'N/A',
                                            'position'         => $assignment->position,
                                            'is_auto_assigned' => $assignment->is_auto_assigned,
                                            'jourcy_number'    => $assignment->jourcy_number ?? null,
                                            'phone'            => $assignment->phone ?? null,
                                            'address'          => $assignment->address ?? null,
                                            'biography'        => $assignment->biography ?? null,
                                            'sort_name'        => $assignment->assignable->name ?? 'N/A',
                                        ];
                                    }

                                    $referee = $assignment->assignable;
                                    $jerseyNumber = $referee ? CampRefereeJearsyNumber::where('camp_id', $camp->id)
                                        ->where('referee_id', $referee->id)
                                        ->value('jersey_number') : null;

                                    return [
                                        'type'             => 'individual',
                                        'referee_id'       => $referee->id ?? null,
                                        'name'             => $referee ? trim($referee->first_name . ' ' . $referee->last_name) : 'N/A',
                                        'avatar'           => $referee?->avatar ? asset($referee->avatar) : asset('default/profile.jpg'),
                                        'email'            => $referee->email ?? null,
                                        'position'         => $assignment->position,
                                        'is_auto_assigned' => $assignment->is_auto_assigned,
                                        'jourcy_number'    => $jerseyNumber,
                                        'phone'            => $referee->phone ?? null,
                                        'address'          => $referee->address ?? null,
                                        'biography'        => $referee->biography ?? null,
                                        'sort_name'        => $referee ? ($referee->first_name . ' ' . $referee->last_name) : 'N/A',
                                    ];
                                })
                                ->sortBy('sort_name')
                                ->map(function ($item) {
                                    unset($item['sort_name']);
                                    return $item;
                                })
                                ->values(),
                        ];
                    }) ?? collect(),
                ],
                'referees'    => $camp->checkedInReferees
                    ->sortBy(fn($checkin) => strtolower($checkin->referee?->last_name ?? ''))
                    ->map(function ($referee) use ($gameSlotAssignments, $camp) {
                        $refereeUser = $referee->referee;
                        if (!$refereeUser) {
                            return null;
                        }

                        $refereeCrewIds = $camp->crews->filter(function ($crew) use ($refereeUser) {
                            return $crew->members->contains('id', $refereeUser->id);
                        })->pluck('id')->toArray();

                        $assignedGamesCount = $gameSlotAssignments->where('assignment_type', 'individual')
                                ->where('assignable_id', $refereeUser->id)
                                ->count()
                            + $gameSlotAssignments->where('assignment_type', 'crew')
                                ->whereIn('assignable_id', $refereeCrewIds)
                                ->count();

                        $jerseyNumber = CampRefereeJearsyNumber::where('camp_id', $camp->id)
                            ->where('referee_id', $refereeUser->id)
                            ->value('jersey_number');

                        return [
                            'id'                   => $refereeUser->id,
                            'name'                 => trim($refereeUser->first_name . ' ' . $refereeUser->last_name),
                            'avatar'               => $refereeUser->avatar ? asset($refereeUser->avatar) : asset('default/profile.jpg'),
                            'email'                => $refereeUser->email,
                            'phone'                => $refereeUser->phone,
                            'address'              => $refereeUser->address ?? null,
                            'jourcy_number'        => $jerseyNumber,
                            'biography'            => $refereeUser->biography ?? null,
                            'status'               => $referee->registration_status,
                            'assigned_games_count' => $assignedGamesCount,
                        ];
                    })
                    ->filter()
                    ->values(),
                'evaluators'  => $camp->evaluatorRegistrations
                    ->where('status', 'approved')
                    ->pluck('evaluator')
                    ->filter()
                    ->unique('id')
                    ->sortBy('last_name', SORT_NATURAL | SORT_FLAG_CASE)
                    ->values()
                    ->map(function ($evaluator) {
                        return [
                            'id'        => $evaluator->id,
                            'name'      => trim($evaluator->first_name . ' ' . $evaluator->last_name),
                            'avatar'    => $evaluator->avatar ? asset($evaluator->avatar) : asset('default/profile.jpg'),
                            'email'     => $evaluator->email,
                            'phone'     => $evaluator->is_phone_show ? $evaluator->phone : null,
                            'address'   => $evaluator->is_address_show ? $evaluator->address : null,
                            'biography' => $evaluator->biography ?? null,
                        ];
                    }),
                'crews'       => $camp->crews
                    ->sortBy(fn($crew) => strtolower($crew->name ?? ''))
                    ->map(function ($crew) use ($camp) {
                        return [
                            'id'          => $crew->id,
                            'name'        => $crew->name ?? 'N/A',
                            'description' => $crew->description ?? 'N/A',
                            'status'      => $crew->status ?? 'N/A',
                            'members'     => $crew->members
                                ->sortBy(fn($member) => strtolower($member->last_name ?? ''))
                                ->map(function ($member) use ($camp) {
                                    $jerseyNumber = CampRefereeJearsyNumber::where('camp_id', $camp->id)
                                        ->where('referee_id', $member->id)
                                        ->value('jersey_number');

                                    return [
                                        'id'            => $member->id,
                                        'name'          => trim($member->first_name . ' ' . $member->last_name),
                                        'avatar'        => $member->avatar ? asset($member->avatar) : asset('default/profile.jpg'),
                                        'email'         => $member->email,
                                        'position'      => $member->pivot?->position ?? null,
                                        'phone'         => $member->phone ?? null,
                                        'address'       => $member->address ?? null,
                                        'jersey_number' => $jerseyNumber,
                                        'biography'     => $member->biography ?? null,
                                    ];
                                })
                                ->values(),
                        ];
                    })
                    ->values(),
            ],
        ];

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Camp details fetched successfully.',
            'data'    => $response,
        ];
    }

    /**
     * Export camp details game courts as CSV (Director only).
     *
     * @param  int|string  $campId
     * @return StreamedResponse|array
     */
    public function exportCampDetailsCsv($campId)
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

        $schedule = $camp->schedule;
        $gameSlots = collect();

        if ($schedule) {
            $locationIds = $schedule->locations()->pluck('id');

            $gameSlots = GameSlot::where('schedule_id', $schedule->id)
                ->orWhereIn('schedule_location_id', $locationIds)
                ->with([
                    'location',
                    'slotAssignments.assignable',
                    'slotAssignments.gameSlotAssignmentPosition',
                ])
                ->orderBy('game_date', 'asc')
                ->orderBy('start_time', 'asc')
                ->get();
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $campName = $camp->camp_name ?? 'Camp';

        $sheet->setCellValue('A1', 'Title:');
        $sheet->setCellValue('B1', $campName . ' - Game Courts Schedule Export');

        $sheet->setCellValue('A3', 'Game Date');
        $sheet->setCellValue('B3', 'Time');
        $sheet->setCellValue('C3', 'Location');
        $sheet->setCellValue('D3', 'Court Name');
        $sheet->setCellValue('E3', 'Assigned Referees');

        $rowIndex = 4;
        foreach ($gameSlots as $slot) {
            $gameDate  = $slot->game_date ? $slot->game_date->toDateString() : 'N/A';
            $startTime = $slot->start_time ? date('h:i A', strtotime($slot->start_time)) : '';
            $endTime   = $slot->end_time ? date('h:i A', strtotime($slot->end_time)) : '';
            $timeSlot  = ($startTime && $endTime) ? ($startTime . ' - ' . $endTime) : ($startTime ?: 'N/A');
            $location  = $slot->location->address ?? ($camp->address ?? ($slot->location->location_name ?? 'N/A'));
            $courtName = $slot->court_name ?? 'N/A';

            $assignmentsList = $slot->slotAssignments->map(function ($assignment) {
                if ($assignment->assignment_type === 'crew') {
                    return $assignment->assignable->name ?? 'N/A';
                }
                $referee = $assignment->assignable;
                return $referee ? trim($referee->first_name . ' ' . $referee->last_name) : 'N/A';
            })->filter()->implode(', ');

            if (empty($assignmentsList)) {
                $assignmentsList = 'N/A';
            }

            $sheet->setCellValue('A' . $rowIndex, $gameDate);
            $sheet->setCellValue('B' . $rowIndex, $timeSlot);
            $sheet->setCellValue('C' . $rowIndex, $location);
            $sheet->setCellValue('D' . $rowIndex, $courtName);
            $sheet->setCellValue('E' . $rowIndex, $assignmentsList);

            $rowIndex++;
        }

        $writer = new Csv($spreadsheet);
        $writer->setUseBOM(true);
        $fileName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $campName) . '_game_courts_' . date('Y_m_d') . '.csv';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type'  => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Pragma'        => 'public',
        ]);
    }

    /**
     * Export Roster Training Camp Referee data as CSV or Excel (Director only).
     *
     * @param  User  $user
     * @param  int|string  $campId
     * @param  string  $format
     * @return StreamedResponse|array
     */
    public function exportCampReferees(User $user, $campId, string $format = 'csv')
    {
        if (!$user->hasRole('director')) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'Unauthorized access. Only directors can export roster referees.',
                'data'    => [],
            ];
        }

        $camp = Camp::where('id', $campId)
            ->with([
                'schedule.gameSlots.slotAssignments.assignable',
                'schedule.gameSlots.slotAssignments.gameSlotAssignmentPosition',
                'checkedInReferees.referee',
                'crews.members',
            ])
            ->first();

        if (!$camp) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Camp not found.',
                'data'    => [],
            ];
        }

        if ($camp->director_id !== $user->id) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'You can only export roster referees from your own camps.',
                'data'    => [],
            ];
        }

        $gameSlotAssignments = $camp->schedule?->gameSlots->flatMap(function ($gameSlot) {
            return $gameSlot->slotAssignments;
        }) ?? collect();

        $referees = $camp->checkedInReferees
            ->sortBy(fn($checkin) => strtolower($checkin->referee?->last_name ?? ''))
            ->map(function ($referee) use ($gameSlotAssignments, $camp) {
                $refereeUser = $referee->referee;
                if (!$refereeUser) {
                    return null;
                }

                $refereeCrewIds = $camp->crews->filter(function ($crew) use ($refereeUser) {
                    return $crew->members->contains('id', $refereeUser->id);
                })->pluck('id')->toArray();

                $assignedGamesCount = $gameSlotAssignments->where('assignment_type', 'individual')
                        ->where('assignable_id', $refereeUser->id)
                        ->count()
                    + $gameSlotAssignments->where('assignment_type', 'crew')
                        ->whereIn('assignable_id', $refereeCrewIds)
                        ->count();

                $jerseyNumber = CampRefereeJearsyNumber::where('camp_id', $camp->id)
                    ->where('referee_id', $refereeUser->id)
                    ->value('jersey_number');

                return [
                    'name'                 => trim(($refereeUser->first_name ?? '') . ' ' . ($refereeUser->last_name ?? '')),
                    'email'                => $refereeUser->email ?? '-',
                    'address'              => $refereeUser->address ?? '-',
                    'phone'                => $refereeUser->phone ?? '-',
                    'jourcy_number'        => $jerseyNumber ?? '-',
                    'assigned_games_count' => $assignedGamesCount ?? '-',
                    'status'               => $referee->registration_status ?? '-',
                ];
            })
            ->filter()
            ->values();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $campName = $camp->camp_name ?? 'Camp';

        $sheet->setCellValue('A1', 'Camp Name:');
        $sheet->setCellValue('B1', $campName . ' - Referee Roster Export');

        $headers = [
            'A3' => 'Referee Name',
            'B3' => 'Email',
            'C3' => 'Address',
            'D3' => 'Phone',
            'E3' => 'Jersey Number',
            'F3' => 'Assigned Games Count',
            'G3' => 'Status',
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        $rowIndex = 4;
        foreach ($referees as $data) {
            $sheet->setCellValue('A' . $rowIndex, $data['name']);
            $sheet->setCellValue('B' . $rowIndex, $data['email']);
            $sheet->setCellValue('C' . $rowIndex, $data['address']);
            $sheet->setCellValue('D' . $rowIndex, $data['phone']);
            $sheet->setCellValue('E' . $rowIndex, $data['jourcy_number']);
            $sheet->setCellValue('F' . $rowIndex, $data['assigned_games_count']);
            $sheet->setCellValue('G' . $rowIndex, $data['status']);
            $rowIndex++;
        }

        $isExcel = in_array(strtolower($format), ['excel', 'xlsx'], true);
        $safeCampName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $campName);

        if ($isExcel) {
            $writer = new Xlsx($spreadsheet);
            $fileName = $safeCampName . '_referees_roster_' . date('Y_m_d') . '.xlsx';
            $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        } else {
            $writer = new Csv($spreadsheet);
            $writer->setUseBOM(true);
            $fileName = $safeCampName . '_referees_roster_' . date('Y_m_d') . '.csv';
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
