<?php

namespace App\Http\Controllers\Api\Evaluator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Evaluator\ActiveCampsRequest;
use App\Services\Api\Evaluator\EvaluatorService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class EvaluatorController extends Controller
{
    use ApiResponse;

    protected EvaluatorService $service;

    public function __construct(EvaluatorService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * All Active Camps List for Evaluator.
     *
     * @param  ActiveCampsRequest  $request
     * @return JsonResponse
     */
    public function getActiveCamps(ActiveCampsRequest $request): JsonResponse
    {
        $response = $this->service->getActiveCamps($request->validated());

        return $this->success('Camp list fetched successfully.', $response, 200);
    }
}
