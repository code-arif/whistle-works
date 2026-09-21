<?php

namespace App\Http\Controllers\Api\Referee;

use App\Http\Controllers\Controller;
use App\Services\Api\Referee\RefereeAssignmentCrewService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class RefereeAssignmentCrewController extends Controller
{
    use ApiResponse;

    protected RefereeAssignmentCrewService $service;

    public function __construct(RefereeAssignmentCrewService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * Get all crews where the referee is a member (across all camps).
     *
     * @return JsonResponse
     */
    public function getMyCrews(): JsonResponse
    {
        $referee = auth('api')->user();

        $result = $this->service->getMyCrews($referee);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get crews for a specific camp where the referee is a member.
     *
     * @param  int|string  $campId
     * @return JsonResponse
     */
    public function getCampCrews($campId): JsonResponse
    {
        $referee = auth('api')->user();

        $result = $this->service->getCampCrews($referee, $campId);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get specific crew details with all game assignments.
     *
     * @param  int|string  $crewId
     * @return JsonResponse
     */
    public function getCrewDetails($crewId): JsonResponse
    {
        $referee = auth('api')->user();

        $result = $this->service->getCrewDetails($referee, $crewId);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get upcoming games for all crews where referee is a member.
     *
     * @return JsonResponse
     */
    public function getMyCrewUpcomingGames(): JsonResponse
    {
        $referee = auth('api')->user();

        $result = $this->service->getMyCrewUpcomingGames($referee);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }
}
