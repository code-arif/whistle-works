<?php

namespace Modules\Director\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Director\Services\Payment\StripeConnectService;

class StripeConnectController extends Controller
{
    use ApiResponse;

    protected StripeConnectService $stripeConnectService;

    public function __construct(StripeConnectService $stripeConnectService)
    {
        $this->stripeConnectService = $stripeConnectService;
    }

    /**
     * Start Stripe Connect onboarding for director
     * POST /api/v1/director/stripe/connect
     *
     * @param  Request      $request
     * @return JsonResponse
     */
    public function connect(Request $request): JsonResponse
    {
        $user   = auth('api')->user();
        $result = $this->stripeConnectService->connect(
            $user,
            $request->input('return_url'),
            $request->input('refresh_url')
        );

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Check if director's Stripe account is fully onboarded
     * GET /api/v1/director/stripe/status
     *
     * @param  Request      $request
     * @return JsonResponse
     */
    public function status(Request $request): JsonResponse
    {
        $user   = auth('api')->user();
        $result = $this->stripeConnectService->status($user);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get Stripe Express Dashboard link
     * GET /api/v1/director/stripe/dashboard
     *
     * @param  Request      $request
     * @return JsonResponse
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user   = auth('api')->user();
        $result = $this->stripeConnectService->dashboard($user);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }
}
