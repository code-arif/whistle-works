<?php

namespace App\Http\Controllers\Api\Evaluator;

use App\Http\Controllers\Controller;
use App\Services\Api\Evaluator\EvaluatorService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class GameOverviewController extends Controller
{
    use ApiResponse;

    protected EvaluatorService $service;

    public function __construct(EvaluatorService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * Game overview for evaluator.
     *
     * @param  int|string  $campId
     * @return JsonResponse
     */
    public function gameOverview($campId): JsonResponse
    {
        $result = $this->service->getGameOverview($campId);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }
}
