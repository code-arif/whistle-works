<?php

namespace App\Http\Controllers\Api\Referee;

use App\Http\Controllers\Controller;
use App\Services\Api\Referee\RefereeDetailsService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RefereeDetailsController extends Controller
{
    use ApiResponse;

    protected RefereeDetailsService $service;

    public function __construct(RefereeDetailsService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * Get comprehensive referee details for a specific camp.
     *
     * @param  Request  $request
     * @param  int|string  $campId
     * @param  int|string  $refereeId
     * @return JsonResponse
     */
    public function getRefereeDetails(Request $request, $campId, $refereeId): JsonResponse
    {
        $user = auth('api')->user();

        $result = $this->service->getRefereeDetails($user, $campId, $refereeId);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }
}
