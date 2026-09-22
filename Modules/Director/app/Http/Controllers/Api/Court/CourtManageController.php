<?php

namespace Modules\Director\Http\Controllers\Api\Court;

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Director\Services\Court\CourtService;

class CourtManageController extends Controller
{
    use ApiResponse;

    protected CourtService $courtService;

    public function __construct(CourtService $courtService)
    {
        $this->courtService = $courtService;
    }

    /**
     * Toggle block/unblock a court slot.
     *
     * @param  int $slotId
     * @return JsonResponse
     */
    public function toggleBlockSlot($slotId): JsonResponse
    {
        $user   = auth('api')->user();
        $result = $this->courtService->toggleBlockSlot($user, (int) $slotId);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Update court name for a location (updates all slots for that court number).
     *
     * @param  Request $request
     * @param  int     $locationId
     * @param  int     $courtNumber
     * @return JsonResponse
     */
    public function updateCourtName(Request $request, $locationId, $courtNumber): JsonResponse
    {
        $user = auth('api')->user();

        $request->validate([
            'new_court_name' => 'required|string|max:100',
        ]);

        $result = $this->courtService->updateCourtName(
            $user,
            (int) $locationId,
            (int) $courtNumber,
            $request->new_court_name
        );

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Bulk update court name for all time slots of a specific court in a location.
     *
     * @param  Request $request
     * @param  int     $locationId
     * @return JsonResponse
     */
    public function bulkUpdateCourtNames(Request $request, $locationId): JsonResponse
    {
        $user = auth('api')->user();

        $request->validate([
            'court_number'   => 'required|integer|min:1',
            'new_court_name' => 'required|string|max:100',
        ]);

        $result = $this->courtService->bulkUpdateCourtNames(
            $user,
            (int) $locationId,
            (int) $request->court_number,
            $request->new_court_name
        );

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get all court names for a location, grouped by court number.
     *
     * @param  int $locationId
     * @return JsonResponse
     */
    public function getCourtNames($locationId): JsonResponse
    {
        $user   = auth('api')->user();
        $result = $this->courtService->getCourtNames($user, (int) $locationId);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Block or unblock all courts at a specific date and time (bulk by time row).
     *
     * @param  Request $request
     * @param  int     $scheduleId
     * @return JsonResponse
     */
    public function bulkToggleBlockByTime(Request $request, $scheduleId): JsonResponse
    {
        $user = auth('api')->user();

        $request->validate([
            'date'       => 'required|date',
            'start_time' => 'required|date_format:H:i:s',
            'action'     => 'required|in:block,unblock',
        ]);

        $result = $this->courtService->bulkToggleBlockByTime($user, (int) $scheduleId, $request);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }
}
