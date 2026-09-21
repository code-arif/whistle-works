<?php

namespace App\Http\Controllers\Api\Evaluator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Evaluator\EvaluatorRegistrationListRequest;
use App\Http\Requests\Api\Evaluator\RejectEvaluatorRegistrationRequest;
use App\Services\Api\Evaluator\CampEvaluatorManageService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampEvaluatorRegisterManageForDirectorController extends Controller
{
    use ApiResponse;

    protected CampEvaluatorManageService $service;

    public function __construct(CampEvaluatorManageService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * Director views evaluator registrations for their camp.
     *
     * @param  EvaluatorRegistrationListRequest  $request
     * @param  int|string  $campId
     * @return JsonResponse
     */
    public function getCampRegistrations(EvaluatorRegistrationListRequest $request, $campId): JsonResponse
    {
        $user = auth('api')->user();

        $result = $this->service->getCampRegistrations(
            $user,
            $campId,
            $request->validated('status'),
            (int) ($request->validated('per_page') ?? 12)
        );

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Director approves evaluator registration.
     *
     * @param  int|string  $registrationId
     * @return JsonResponse
     */
    public function approve($registrationId): JsonResponse
    {
        $user = auth('api')->user();

        $result = $this->service->approveRegistration($user, $registrationId);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Director rejects evaluator registration.
     *
     * @param  RejectEvaluatorRegistrationRequest  $request
     * @param  int|string  $registrationId
     * @return JsonResponse
     */
    public function reject(RejectEvaluatorRegistrationRequest $request, $registrationId): JsonResponse
    {
        $user = auth('api')->user();

        $result = $this->service->rejectRegistration($user, $registrationId, $request->validated('rejection_reason'));

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Director toggles evaluator's permission to view their own evaluations.
     *
     * @param  int|string  $registrationId
     * @return JsonResponse
     */
    public function toggleEvaluatorVisibility($registrationId): JsonResponse
    {
        $user = auth('api')->user();

        $result = $this->service->toggleEvaluatorVisibility($user, $registrationId);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Director removes evaluator from camp.
     *
     * @param  int|string  $registrationId
     * @return JsonResponse
     */
    public function removeEvaluator($registrationId): JsonResponse
    {
        $user = auth('api')->user();

        $result = $this->service->removeEvaluator($user, $registrationId);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get all registered evaluators for roster.
     *
     * @param  EvaluatorRegistrationListRequest  $request
     * @param  int|string  $campId
     * @return JsonResponse
     */
    public function index(EvaluatorRegistrationListRequest $request, $campId): JsonResponse
    {
        $result = $this->service->getRosterEvaluators(
            $campId,
            $request->validated('status'),
            (int) ($request->validated('per_page') ?? 12)
        );

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get approved evaluators for roster.
     *
     * @param  Request  $request
     * @param  int|string  $campId
     * @return JsonResponse
     */
    public function approved(Request $request, $campId): JsonResponse
    {
        $result = $this->service->getRosterEvaluators($campId, 'approved', (int) $request->get('per_page', 12));

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get pending evaluators for roster.
     *
     * @param  Request  $request
     * @param  int|string  $campId
     * @return JsonResponse
     */
    public function pending(Request $request, $campId): JsonResponse
    {
        $result = $this->service->getRosterEvaluators($campId, 'pending', (int) $request->get('per_page', 12));

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get rejected evaluators for roster.
     *
     * @param  Request  $request
     * @param  int|string  $campId
     * @return JsonResponse
     */
    public function rejected(Request $request, $campId): JsonResponse
    {
        $result = $this->service->getRosterEvaluators($campId, 'rejected', (int) $request->get('per_page', 12));

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Export evaluator registrations for a camp.
     *
     * @param  Request  $request
     * @param  int|string  $campId
     * @return mixed
     */
    public function exportEvaluatorRegistrations(Request $request, $campId)
    {
        $user = auth('api')->user();
        $format = (string) $request->query('format', $request->query('type', 'xlsx'));

        $result = $this->service->exportEvaluatorRegistrations($user, $campId, $format);

        if (is_array($result) && !($result['success'] ?? true)) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $result;
    }
}
