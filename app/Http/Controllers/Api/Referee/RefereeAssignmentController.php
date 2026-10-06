<?php

namespace App\Http\Controllers\Api\Referee;

use App\Http\Controllers\Controller;
use App\Services\Api\Referee\RefereeAssignmentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RefereeAssignmentController extends Controller
{
    use ApiResponse;

    protected RefereeAssignmentService $service;

    public function __construct(RefereeAssignmentService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * Get all game slots assigned to referee for a specific camp.
     *
     * @param  Request  $request
     * @param  int|string  $campId
     * @return JsonResponse
     */
    public function getCampAssignedSlots(Request $request, $campId): JsonResponse
    {
        $referee = auth('api')->user();
        $perPage = (int) $request->get('per_page', 15);
        $page    = (int) $request->get('page', 1);

        $result = $this->service->getCampAssignedSlots($referee, $campId, $perPage, $page);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get all game slots where the authenticated referee is assigned.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function getMyAssignedSlots(Request $request): JsonResponse
    {
        $referee = auth('api')->user();

        $result = $this->service->getMyAssignedSlots($referee);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get details of a specific game slot.
     *
     * @param  int|string  $gameSlotId
     * @return JsonResponse
     */
    public function getGameSlotDetails($gameSlotId): JsonResponse
    {
        $referee = auth('api')->user();

        $result = $this->service->getGameSlotDetails($referee, $gameSlotId);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get upcoming game slots for the referee.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function getUpcomingSlots(Request $request): JsonResponse
    {
        $referee = auth('api')->user();

        $result = $this->service->getUpcomingSlots($referee);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get referee's previous camp check-in list.
     *
     * @return JsonResponse
     */
    public function getMyCheckins(): JsonResponse
    {
        $referee = auth('api')->user();

        $result = $this->service->getMyCheckins($referee);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }
}
