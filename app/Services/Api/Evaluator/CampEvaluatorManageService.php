<?php

namespace App\Services\Api\Evaluator;

use App\Http\Resources\Evaluator\CampRegistraionsListResource;
use App\Models\AssistantDirectorPermission;
use App\Models\CampEvaluatorRegistration;
use App\Models\User;
use Modules\Director\Models\Camp;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CampEvaluatorManageService
{
    /**
     * Director views evaluator registrations for their camp.
     *
     * @param  User  $user
     * @param  int|string  $campId
     * @param  string|null  $status
     * @param  int  $perPage
     * @return array
     */
    public function getCampRegistrations(User $user, $campId, ?string $status = null, int $perPage = 12): array
    {
        if (!$user->hasRole('director')) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'Only directors can view camp registrations.',
                'data'    => [],
            ];
        }

        $camp = Camp::where('id', $campId)
            ->where('director_id', $user->id)
            ->first();

        if (!$camp) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Camp not found or unauthorized.',
                'data'    => [],
            ];
        }

        $query = CampEvaluatorRegistration::with(['evaluator'])
            ->where('camp_id', $campId);

        if ($status) {
            $query->where('status', $status);
        }

        $registrations = $query->orderBy('registered_at', 'desc')
            ->paginate($perPage);

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Camp registrations retrieved successfully.',
            'data'    => [
                'camp' => [
                    'id'   => $camp->id,
                    'name' => $camp->camp_name,
                ],
                'registrations' => CampRegistraionsListResource::collection($registrations),
                'pagination'    => [
                    'total'        => $registrations->total(),
                    'per_page'     => $registrations->perPage(),
                    'current_page' => $registrations->currentPage(),
                    'last_page'    => $registrations->lastPage(),
                ],
            ],
        ];
    }

    /**
     * Director / authorized Assistant Director approves evaluator registration.
     *
     * @param  User  $user
     * @param  int|string  $registrationId
     * @return array
     */
    public function approveRegistration(User $user, $registrationId): array
    {
        $registration = CampEvaluatorRegistration::with('camp')->find($registrationId);

        if (!$registration) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Registration not found.',
                'data'    => [],
            ];
        }

        $camp = $registration->camp;

        if ($camp->director_id !== $user->id) {
            $permission = AssistantDirectorPermission::where('camp_id', $camp->id)
                ->where('assistant_director_id', $user->id)
                ->first();

            if (!$permission) {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => 'Unauthorized to approve this registration.',
                    'data'    => [],
                ];
            }

            if (!$permission->manage_roster_evaluators) {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => 'You do not have permission to approve evaluator registrations for this camp.',
                    'data'    => [],
                ];
            }
        }

        if ($registration->status !== 'pending') {
            return [
                'success' => false,
                'code'    => 400,
                'message' => 'Only pending registrations can be approved.',
                'data'    => [],
            ];
        }

        $registration->approve($user);

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Evaluator registration approved successfully.',
            'data'    => [],
        ];
    }

    /**
     * Director / authorized Assistant Director rejects evaluator registration.
     *
     * @param  User  $user
     * @param  int|string  $registrationId
     * @param  string|null  $rejectionReason
     * @return array
     */
    public function rejectRegistration(User $user, $registrationId, ?string $rejectionReason = null): array
    {
        $registration = CampEvaluatorRegistration::with('camp')->find($registrationId);

        if (!$registration) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Registration not found.',
                'data'    => [],
            ];
        }

        $camp = $registration->camp;

        if ($camp->director_id !== $user->id) {
            $permission = AssistantDirectorPermission::where('camp_id', $camp->id)
                ->where('assistant_director_id', $user->id)
                ->first();

            if (!$permission) {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => 'Unauthorized to reject this registration.',
                    'data'    => [],
                ];
            }

            if (!$permission->manage_roster_evaluators) {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => 'You do not have permission to reject evaluator registrations for this camp.',
                    'data'    => [],
                ];
            }
        }

        if ($registration->status !== 'pending') {
            return [
                'success' => false,
                'code'    => 400,
                'message' => 'Only pending registrations can be rejected.',
                'data'    => [],
            ];
        }

        $registration->reject($rejectionReason);

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Evaluator registration rejected.',
            'data'    => [],
        ];
    }

    /**
     * Director toggles evaluator's permission to view their own evaluations.
     *
     * @param  User  $user
     * @param  int|string  $registrationId
     * @return array
     */
    public function toggleEvaluatorVisibility(User $user, $registrationId): array
    {
        if (!$user->hasRole('director')) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'Only directors can toggle evaluator permissions.',
                'data'    => [],
            ];
        }

        $registration = CampEvaluatorRegistration::with('camp')->find($registrationId);

        if (!$registration) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Registration not found.',
                'data'    => [],
            ];
        }

        if ($registration->camp->director_id !== $user->id) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'Unauthorized to modify this registration.',
                'data'    => [],
            ];
        }

        if ($registration->status !== 'approved') {
            return [
                'success' => false,
                'code'    => 400,
                'message' => 'Only approved evaluators can have their permissions modified.',
                'data'    => [],
            ];
        }

        $newStatus = $registration->toggleVisibility();

        return [
            'success' => true,
            'code'    => 200,
            'message' => $newStatus
                ? 'Evaluator can now view their evaluations.'
                : 'Evaluator visibility disabled.',
            'data'    => [
                'registration'         => $registration->fresh()->load(['evaluator', 'camp']),
                'can_view_evaluations' => $newStatus,
            ],
        ];
    }

    /**
     * Director / authorized Assistant Director removes evaluator from camp.
     *
     * @param  User  $user
     * @param  int|string  $registrationId
     * @return array
     */
    public function removeEvaluator(User $user, $registrationId): array
    {
        $registration = CampEvaluatorRegistration::with(['camp', 'evaluator'])->find($registrationId);

        if (!$registration) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Registration not found.',
                'data'    => [],
            ];
        }

        $camp = $registration->camp;

        if ($camp->director_id !== $user->id) {
            $permission = AssistantDirectorPermission::where('camp_id', $camp->id)
                ->where('assistant_director_id', $user->id)
                ->first();

            if (!$permission) {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => 'Unauthorized to remove this evaluator.',
                    'data'    => [],
                ];
            }

            if (!$permission->manage_roster_evaluators) {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => 'You do not have permission to remove evaluators for this camp.',
                    'data'    => [],
                ];
            }
        }

        $evaluatorName = trim(($registration->evaluator->first_name ?? '') . ' ' . ($registration->evaluator->last_name ?? ''));
        $campName = $registration->camp->camp_name ?? 'Camp';

        $registration->delete();

        return [
            'success' => true,
            'code'    => 200,
            'message' => "Evaluator {$evaluatorName} has been removed from {$campName}.",
            'data'    => [],
        ];
    }

    /**
     * Get roster evaluator registrations for a camp.
     *
     * @param  int|string  $campId
     * @param  string|null  $status
     * @param  int  $perPage
     * @return array
     */
    public function getRosterEvaluators($campId, ?string $status = null, int $perPage = 12): array
    {
        $camp = Camp::find($campId);

        if (!$camp) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Camp not found!',
                'data'    => [],
            ];
        }

        $query = CampEvaluatorRegistration::with(['evaluator'])
            ->where('camp_id', $campId)
            ->join('users', 'users.id', '=', 'camp_evaluator_registrations.evaluator_id')
            ->orderBy('users.last_name', 'asc')
            ->select('camp_evaluator_registrations.*');

        if ($status) {
            $query->where('camp_evaluator_registrations.status', $status);
        }

        $registrations = $query->paginate($perPage);

        $message = $status
            ? ucfirst($status) . ' evaluators retrieved successfully.'
            : 'Camp evaluators retrieved successfully.';

        return [
            'success' => true,
            'code'    => 200,
            'message' => $message,
            'data'    => [
                'camp' => [
                    'id'   => $camp->id,
                    'name' => $camp->camp_name,
                ],
                'registrations' => CampRegistraionsListResource::collection($registrations),
                'pagination'    => [
                    'total'        => $registrations->total(),
                    'per_page'     => $registrations->perPage(),
                    'current_page' => $registrations->currentPage(),
                    'last_page'    => $registrations->lastPage(),
                ],
            ],
        ];
    }

    /**
     * Export evaluator registrations for a camp (Approved, Pending, Rejected in separate sheets).
     *
     * @param  User  $user
     * @param  int|string  $campId
     * @param  string  $format
     * @return StreamedResponse|array
     */
    public function exportEvaluatorRegistrations(User $user, $campId, string $format = 'xlsx')
    {
        if (!$user->hasRole('director')) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'Unauthorized access. Only directors can export registrations.',
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

        if ($camp->director_id !== $user->id) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'You can only export registrations from your own camps.',
                'data'    => [],
            ];
        }

        $spreadsheet = new Spreadsheet();
        $statuses = ['approved', 'pending', 'rejected'];

        foreach ($statuses as $sheetIndex => $status) {
            if ($sheetIndex === 0) {
                $sheet = $spreadsheet->getActiveSheet();
            } else {
                $sheet = $spreadsheet->createSheet();
            }

            $sheetTitle = ucfirst($status);
            $sheet->setTitle($sheetTitle);

            $campName = $camp->camp_name ?? 'Camp';

            // Title Row
            $sheet->setCellValue('A1', 'Camp Name:');
            $sheet->setCellValue('B1', $campName . ' - ' . $sheetTitle . ' Evaluator Registrations Export');

            // Header Row
            $headers = [
                'A3' => 'Evaluator Name',
                'B3' => 'Email',
                'C3' => 'Phone',
                'D3' => 'Address',
                'E3' => 'Registered At',
                'F3' => 'Status',
            ];

            foreach ($headers as $cell => $value) {
                $sheet->setCellValue($cell, $value);
            }

            $registrations = CampEvaluatorRegistration::with(['evaluator'])
                ->where('camp_evaluator_registrations.camp_id', $campId)
                ->where('camp_evaluator_registrations.status', $status)
                ->join('users', 'users.id', '=', 'camp_evaluator_registrations.evaluator_id')
                ->orderBy('users.last_name', 'asc')
                ->select('camp_evaluator_registrations.*')
                ->get();

            $rowIndex = 4;
            foreach ($registrations as $reg) {
                $evaluator = $reg->evaluator;
                $name = $evaluator ? trim(($evaluator->first_name ?? '') . ' ' . ($evaluator->last_name ?? '')) : '-';
                $email = $evaluator->email ?? '-';
                $phone = $evaluator->phone ?? '-';
                $address = $evaluator->address ?? '-';
                $registeredAt = $reg->registered_at
                    ? (is_string($reg->registered_at)
                        ? date('Y-m-d H:i:s', strtotime($reg->registered_at))
                        : $reg->registered_at->format('Y-m-d H:i:s'))
                    : '-';
                $regStatus = $reg->status ?? '-';

                $sheet->setCellValue('A' . $rowIndex, !empty($name) ? $name : '-');
                $sheet->setCellValue('B' . $rowIndex, !empty($email) ? $email : '-');
                $sheet->setCellValue('C' . $rowIndex, !empty($phone) ? $phone : '-');
                $sheet->setCellValue('D' . $rowIndex, !empty($address) ? $address : '-');
                $sheet->setCellValue('E' . $rowIndex, !empty($registeredAt) ? $registeredAt : '-');
                $sheet->setCellValue('F' . $rowIndex, !empty($regStatus) ? $regStatus : '-');

                $rowIndex++;
            }
        }

        $spreadsheet->setActiveSheetIndex(0);

        $isCsv = in_array(strtolower($format), ['csv'], true);
        $safeCampName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $camp->camp_name ?? 'Camp');

        if ($isCsv) {
            $writer = new Csv($spreadsheet);
            $writer->setUseBOM(true);
            $fileName = $safeCampName . '_evaluator_registrations_' . date('Y_m_d') . '.csv';
            $contentType = 'text/csv; charset=UTF-8';
        } else {
            $writer = new Xlsx($spreadsheet);
            $fileName = $safeCampName . '_evaluator_registrations_' . date('Y_m_d') . '.xlsx';
            $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
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
