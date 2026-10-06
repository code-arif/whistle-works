<?php

namespace App\Http\Controllers\Api\Evaluator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Evaluator\CampEvaluatorRegisterRequest;
use App\Http\Requests\Api\Evaluator\EvaluatorRegistrationListRequest;
use App\Services\Api\Evaluator\CampEvaluatorRegistrationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class CampEvaluatorRegistrationController extends Controller
{
    use ApiResponse;

    protected CampEvaluatorRegistrationService $service;

    public function __construct(CampEvaluatorRegistrationService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * Evaluator registers for a camp.
     *
     * @param  CampEvaluatorRegisterRequest  $request
     * @return JsonResponse
     */
    public function register(CampEvaluatorRegisterRequest $request): JsonResponse
    {
        $user = auth('api')->user();

        $result = $this->service->register($user, $request->validated());

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get evaluator's own active/upcoming registrations.
     *
     * @param  EvaluatorRegistrationListRequest  $request
     * @return JsonResponse
     */
    public function myRegistrations(EvaluatorRegistrationListRequest $request): JsonResponse
    {
        $user = auth('api')->user();

        $result = $this->service->getMyRegistrations(
            $user,
            $request->validated('status'),
            (int) ($request->validated('per_page') ?? 12)
        );

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Cancel registration (by evaluator).
     *
     * @param  int|string  $registrationId
     * @return JsonResponse
     */
    public function cancel($registrationId): JsonResponse
    {
        $user = auth('api')->user();

        $result = $this->service->cancelRegistration($user, $registrationId);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get previous (past) registrations for evaluator.
     *
     * @param  EvaluatorRegistrationListRequest  $request
     * @return JsonResponse
     */
    public function previousCamp(EvaluatorRegistrationListRequest $request): JsonResponse
    {
        $user = auth('api')->user();

        $result = $this->service->getPreviousCamps(
            $user,
            $request->validated('status'),
            (int) ($request->validated('per_page') ?? 12)
        );

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }
}
