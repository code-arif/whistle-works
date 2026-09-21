<?php

namespace App\Http\Controllers\Api\Roster;

use App\Http\Controllers\Controller;
use App\Services\Api\Roster\RosterService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RosterController extends Controller
{
    use ApiResponse;

    protected RosterService $rosterService;

    public function __construct(RosterService $rosterService)
    {
        $this->rosterService = $rosterService;
    }

    /**
     * Get camp details for roster view.
     *
     * @param  int|string  $id
     * @return JsonResponse
     */
    public function campDetails($id): JsonResponse
    {
        $result = $this->rosterService->getCampDetails($id);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Export camp details game courts as CSV (Director only).
     *
     * @param  int|string  $id
     * @return StreamedResponse|JsonResponse
     */
    public function exportCampDetailsCsv($id)
    {
        $result = $this->rosterService->exportCampDetailsCsv($id);

        if (is_array($result) && !($result['success'] ?? true)) {
            return $this->error($result['data'] ?? [], $result['message'] ?? 'Failed to export.', $result['code'] ?? 400);
        }

        return $result;
    }

    /**
     * Export Roster Training Camp Referee data as CSV or Excel (Director only).
     *
     * @param  Request  $request
     * @param  int|string  $id
     * @return StreamedResponse|JsonResponse
     */
    public function exportCampReferees(Request $request, $id)
    {
        $user = auth('api')->user();

        if (!$user) {
            return $this->error([], 'Unauthorized access.', 401);
        }

        $format = (string) $request->query('format', $request->query('type', 'csv'));
        $result = $this->rosterService->exportCampReferees($user, $id, $format);

        if (is_array($result) && !($result['success'] ?? true)) {
            return $this->error($result['data'] ?? [], $result['message'] ?? 'Failed to export.', $result['code'] ?? 400);
        }

        return $result;
    }
}
