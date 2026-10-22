<?php

namespace App\Http\Controllers\Api\Referee;

use App\Http\Controllers\Controller;
use App\Services\Api\Referee\EvaluatedRefereeService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class EvaluatedRefereeController extends Controller
{
    use ApiResponse;

    protected EvaluatedRefereeService $service;

    public function __construct(EvaluatedRefereeService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * Get all evaluations for a specific referee (for referee's own view).
     *
     * @param  int|string  $campId
     */
    public function getRefereeEvaluations($campId): JsonResponse
    {
        $user = auth('api')->user();

        $result = $this->service->getRefereeEvaluations($user, $campId);

        if (! $result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get details of a single evaluation for the authenticated referee.
     *
     * @param  int|string  $id
     */
    public function getRefereeEvaluationDetails($id): JsonResponse
    {
        $user = auth('api')->user();

        if (! $user) {
            return $this->error([], 'Unauthenticated.', 401);
        }

        $result = $this->service->getRefereeEvaluationDetails($user, $id);

        if (! $result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }
}
