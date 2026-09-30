<?php

namespace Modules\Director\Http\Controllers\Api\Schedule;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Director\Http\Requests\ScheduleCreateRequest;
use Modules\Director\Services\Schedule\ScheduleService;

class ScheduleController extends Controller
{
    use ApiResponse;

    protected ScheduleService $scheduleService;

    public function __construct(ScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
    }

    /**
     * Get camp date range for schedule creation.
     *
     * @param  mixed        $campId
     * @return JsonResponse
     */
    public function getCampDateRange($campId): JsonResponse
    {
        $user   = auth('api')->user();
        $result = $this->scheduleService->getCampDateRange($user, (int) $campId);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Create schedule.
     *
     * @param  ScheduleCreateRequest $request
     * @param  mixed                 $campId
     * @return JsonResponse
     */
    public function createSchedule(ScheduleCreateRequest $request, $campId): JsonResponse
    {
        $user   = auth('api')->user();
        $result = $this->scheduleService->createSchedule($user, (int) $campId, $request);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get schedule details.
     *
     * @param  mixed        $campId
     * @param  Request      $request
     * @return JsonResponse
     */
    public function getSchedule($campId, Request $request): JsonResponse
    {
        $user   = auth('api')->user();
        $result = $this->scheduleService->getSchedule($user, (int) $campId, $request);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get game slots grouped by date, time, and court.
     *
     * @param  mixed        $campId
     * @param  Request      $request
     * @return JsonResponse
     */
    public function getGameSlots($campId, Request $request): JsonResponse
    {
        $user   = auth('api')->user();
        $result = $this->scheduleService->getGameSlots($user, (int) $campId, $request);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Publish schedule and notify all registered referees and evaluators.
     *
     * @param  mixed        $campId
     * @return JsonResponse
     */
    public function publishSchedule($campId): JsonResponse
    {
        $user   = auth('api')->user();
        $result = $this->scheduleService->publishSchedule($user, (int) $campId);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Clear schedule (remove all assignments but keep slots).
     *
     * @param  mixed        $campId
     * @return JsonResponse
     */
    public function clearSchedule($campId): JsonResponse
    {
        $user   = auth('api')->user();
        $result = $this->scheduleService->clearSchedule($user, (int) $campId);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Delete entire schedule.
     *
     * @param  mixed        $campId
     * @return JsonResponse
     */
    public function deleteSchedule($campId): JsonResponse
    {
        $user   = auth('api')->user();
        $result = $this->scheduleService->deleteSchedule($user, (int) $campId);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }
}
