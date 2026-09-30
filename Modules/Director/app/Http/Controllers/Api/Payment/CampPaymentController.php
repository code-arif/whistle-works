<?php

namespace Modules\Director\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Director\Services\Payment\CampPaymentService;

class CampPaymentController extends Controller
{
    use ApiResponse;

    protected CampPaymentService $campPaymentService;

    public function __construct(CampPaymentService $campPaymentService)
    {
        $this->campPaymentService = $campPaymentService;
    }

    /**
     * Initiate payment for camp check-in.
     *
     * @param  Request      $request
     * @param  mixed        $campId
     * @return JsonResponse
     */
    public function initiatePayment(Request $request, $campId): JsonResponse
    {
        $user   = auth('api')->user();
        $result = $this->campPaymentService->initiatePayment($user, $campId);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Handle successful payment callback.
     *
     * @param  Request      $request
     * @return JsonResponse
     */
    public function success(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Invalid request.', 422);
        }

        $result = $this->campPaymentService->handleSuccess($request->session_id);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Handle cancelled payment callback.
     *
     * @param  Request      $request
     * @return JsonResponse
     */
    public function cancel(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Invalid request.', 422);
        }

        $result = $this->campPaymentService->handleCancel($request->session_id);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get payment status for a camp.
     *
     * @param  Request      $request
     * @param  mixed        $campId
     * @return JsonResponse
     */
    public function getPaymentStatus(Request $request, $campId): JsonResponse
    {
        $user   = auth('api')->user();
        $result = $this->campPaymentService->getPaymentStatus($user, $campId);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get payment history for authenticated referee.
     *
     * @param  Request      $request
     * @return JsonResponse
     */
    public function getPaymentHistory(Request $request): JsonResponse
    {
        $user   = auth('api')->user();
        $result = $this->campPaymentService->getPaymentHistory($user);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }
}
