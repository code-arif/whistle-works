<?php

namespace App\Http\Controllers\Api\Frontend\Evaluator;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Modules\Director\Models\Camp;
use App\Http\Controllers\Controller;
use App\Models\AssistantDirectorPermission;
use App\Models\CampEvaluatorRegistration;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Evaluator\CampRegistraionsListResource;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CampEvaluatorRegisterManageForDirectorController extends Controller
{
    use ApiResponse;

    /**
     * Director views evaluator registrations for their camp
     */
    public function getCampRegistrations(Request $request, $campId)
    {
        $user = auth('api')->user();

        // Only directors can view registrations
        if (!$user->hasRole('director')) {
            return $this->error([], 'Only directors can view camp registrations.', 403);
        }

        // Verify camp ownership
        $camp = Camp::where('id', $campId)
            ->where('director_id', $user->id)
            ->first();

        if (!$camp) {
            return $this->error([], 'Camp not found or unauthorized.', 404);
        }

        $query = CampEvaluatorRegistration::with(['evaluator'])
            ->where('camp_id', $campId);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $registrations = $query->orderBy('registered_at', 'desc')
            ->paginate($request->get('per_page', 12));

        $formattedRegistrations = CampRegistraionsListResource::collection($registrations);

        return $this->success(
            'Camp registrations retrieved successfully.',
            [
                'camp' => [
                    'id' => $camp->id,
                    'name' => $camp->camp_name,
                ],
                'registrations' => $formattedRegistrations,
                'pagination' => [
                    'total' => $registrations->total(),
                    'per_page' => $registrations->perPage(),
                    'current_page' => $registrations->currentPage(),
                    'last_page' => $registrations->lastPage(),
                ],
            ]
        );
    }

    /**
     * Director approves evaluator registration
     */
    public function approve(Request $request, $registrationId)
    {
        $user = auth('api')->user();

        $registration = CampEvaluatorRegistration::with('camp')->find($registrationId);

        if (!$registration) {
            return $this->error([], 'Registration not found.', 404);
        }

        $camp = $registration->camp;

        if ($camp->director_id !== $user->id) {
            $permission = AssistantDirectorPermission::where('camp_id', $camp->id)
                ->where('assistant_director_id', $user->id)
                ->first();

            if (!$permission) {
                return $this->error([], 'Unauthorized to approve this registration.', 403);
            }

            if (!$permission->manage_roster_evaluators) {
                return $this->error([], 'You do not have permission to approve evaluator registrations for this camp.', 403);
            }
        }

        if ($registration->status !== 'pending') {
            return $this->error([], 'Only pending registrations can be approved.', 400);
        }

        $registration->approve($user);

        return $this->success(
            'Evaluator registration approved successfully.',
            // [
            //     'registration' => $registration->fresh()->load(['evaluator', 'camp', 'approver']),
            // ]
            [],
            200
        );
    }

    /**
     * Director rejects evaluator registration
     */
    public function reject(Request $request, $registrationId)
    {
        $user = auth('api')->user();

        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation failed.', 422);
        }

        $registration = CampEvaluatorRegistration::with('camp')->find($registrationId);

        if (!$registration) {
            return $this->error([], 'Registration not found.', 404);
        }

        $camp = $registration->camp;

        if ($camp->director_id !== $user->id) {
            $permission = AssistantDirectorPermission::where('camp_id', $camp->id)
                ->where('assistant_director_id', $user->id)
                ->first();

            if (!$permission) {
                return $this->error([], 'Unauthorized to reject this registration.', 403);
            }

            if (!$permission->manage_roster_evaluators) {
                return $this->error([], 'You do not have permission to reject evaluator registrations for this camp.', 403);
            }
        }

        if ($registration->status !== 'pending') {
            return $this->error([], 'Only pending registrations can be rejected.', 400);
        }

        $registration->reject($request->rejection_reason);

        return $this->success(
            'Evaluator registration rejected.',
            // [
            //     'registration' => $registration->fresh()->load(['evaluator', 'camp']),
            // ]
            [],
            200
        );
    }

    /**
     * Director toggles evaluator's permission to view their own evaluations
     */
    public function toggleEvaluatorVisibility(Request $request, $registrationId)
    {
        $user = auth('api')->user();

        if (!$user->hasRole('director')) {
            return $this->error([], 'Only directors can toggle evaluator permissions.', 403);
        }

        $registration = CampEvaluatorRegistration::with('camp')->find($registrationId);

        if (!$registration) {
            return $this->error([], 'Registration not found.', 404);
        }

        // Verify camp ownership
        if ($registration->camp->director_id !== $user->id) {
            return $this->error([], 'Unauthorized to modify this registration.', 403);
        }

        // Only approved evaluators can have their visibility toggled
        if ($registration->status !== 'approved') {
            return $this->error([], 'Only approved evaluators can have their permissions modified.', 400);
        }

        $newStatus = $registration->toggleVisibility();

        return $this->success(
            $newStatus
                ? 'Evaluator can now view their evaluations.'
                : 'Evaluator visibility disabled.',
            [
                'registration' => $registration->fresh()->load(['evaluator', 'camp']),
                'can_view_evaluations' => $newStatus,
            ]
        );
    }

    /**
     * Director removes evaluator from camp (delete registration)
     */
    public function removeEvaluator($registrationId)
    {
        $user = auth('api')->user();

        $registration = CampEvaluatorRegistration::with(['camp', 'evaluator'])->find($registrationId);

        if (!$registration) {
            return $this->error([], 'Registration not found.', 404);
        }

        $camp = $registration->camp;

        if ($camp->director_id !== $user->id) {
            $permission = AssistantDirectorPermission::where('camp_id', $camp->id)
                ->where('assistant_director_id', $user->id)
                ->first();

            if (!$permission) {
                return $this->error([], 'Unauthorized to remove this evaluator.', 403);
            }

            if (!$permission->manage_roster_evaluators) {
                return $this->error([], 'You do not have permission to remove evaluators for this camp.', 403);
            }
        }

        // Store evaluator name for response message
        $evaluatorName = $registration->evaluator->first_name . ' ' . $registration->evaluator->last_name;
        $campName = $registration->camp->camp_name;

        // Delete the registration
        $registration->delete();

        return $this->success(
            "Evaluator {$evaluatorName} has been removed from {$campName}.",
            [],
            200
        );
    }


    /**
     * Get all register evalator
     */
    public function index(Request $request, $campId)
    {
        $user = auth('api')->user();

        // if (!$user->hasRole('director')) {
        //     return $this->error([], 'Only directors can view camp registrations.', 403);
        // }

        $camp = Camp::find($campId);

        if (!$camp) {
            return $this->error([], 'Camp not found!', 404);
        }

        $query = CampEvaluatorRegistration::with(['evaluator'])
            ->where('camp_id', $campId)
            ->join('users', 'users.id', '=', 'camp_evaluator_registrations.evaluator_id')
            ->orderBy('users.last_name', 'asc')
            ->select('camp_evaluator_registrations.*');

        // optional filter
        if ($request->filled('status')) {
            $query->where('camp_evaluator_registrations.status', $request->status);
        }

        $registrations = $query->paginate($request->get('per_page', 12));

        return $this->success(
            'Camp evaluators retrieved successfully.',
            [
                'camp' => [
                    'id' => $camp->id,
                    'name' => $camp->camp_name,
                ],
                'registrations' => CampRegistraionsListResource::collection($registrations),
                'pagination' => [
                    'total' => $registrations->total(),
                    'per_page' => $registrations->perPage(),
                    'current_page' => $registrations->currentPage(),
                    'last_page' => $registrations->lastPage(),
                ],
            ]
        );
    }

    public function approved(Request $request, $campId)
    {
        return $this->statusList($request, $campId, 'approved');
    }

    public function pending(Request $request, $campId)
    {
        return $this->statusList($request, $campId, 'pending');
    }

    public function rejected(Request $request, $campId)
    {
        return $this->statusList($request, $campId, 'rejected');
    }

    private function statusList(Request $request, $campId, $status)
    {
        $user = auth('api')->user();

        $camp = Camp::find($campId);

        if (!$camp) {
            return $this->error([], 'Camp not found or unauthorized.', 404);
        }

        $registrations = CampEvaluatorRegistration::with(['evaluator'])
            ->where('camp_evaluator_registrations.camp_id', $campId)
            ->where('camp_evaluator_registrations.status', $status)
            ->join('users', 'users.id', '=', 'camp_evaluator_registrations.evaluator_id')
            ->orderBy('users.last_name', 'asc')
            ->select('camp_evaluator_registrations.*')
            ->paginate($request->get('per_page', 12));

        return $this->success(
            ucfirst($status) . ' evaluators retrieved successfully.',
            [
                'camp' => [
                    'id' => $camp->id,
                    'name' => $camp->camp_name,
                ],
                'registrations' => CampRegistraionsListResource::collection($registrations),
                'pagination' => [
                    'total' => $registrations->total(),
                    'per_page' => $registrations->perPage(),
                    'current_page' => $registrations->currentPage(),
                    'last_page' => $registrations->lastPage(),
                ],
            ]
        );
    }

    /**
     * Export evaluator registrations for a camp (Approved, Pending, Rejected in separate tabs)
     */
    public function exportEvaluatorRegistrations(Request $request, $campId)
    {
        $user = auth('api')->user();

        if (!$user || !$user->hasRole('director')) {
            return $this->error([], 'Unauthorized access. Only directors can export registrations.', 403);
        }

        $camp = Camp::find($campId);

        if (!$camp) {
            return $this->error([], 'Camp not found.', 404);
        }

        if ($camp->director_id !== $user->id) {
            return $this->error([], 'You can only export registrations from your own camps.', 403);
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

        $format = strtolower($request->query('format', $request->query('type', 'xlsx')));
        $isCsv = in_array($format, ['csv']);

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
