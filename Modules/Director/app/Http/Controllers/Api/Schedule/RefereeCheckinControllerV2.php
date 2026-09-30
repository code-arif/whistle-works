<?php

namespace Modules\Director\Http\Controllers\Api\Schedule;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Director\Services\Schedule\RefereeCheckinV2Service;

class RefereeCheckinControllerV2 extends Controller
{
    use ApiResponse;

    protected RefereeCheckinV2Service $refereeCheckinV2Service;

    public function __construct(RefereeCheckinV2Service $refereeCheckinV2Service)
    {
        $this->refereeCheckinV2Service = $refereeCheckinV2Service;
    }

    /**
     * Get camp details with sports fee breakdown (for referee before applying coupon).
     *
     * @param  mixed        $campId
     * @return JsonResponse
     */
    public function getCampPricing($campId): JsonResponse
    {
        $result = $this->refereeCheckinV2Service->getCampPricing((int) $campId);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Validate a coupon code without initiating payment.
     *
     * @param  Request      $request
     * @param  mixed        $campId
     * @return JsonResponse
     */
    public function validateCoupon(Request $request, $campId): JsonResponse
    {
        $referee = auth('api')->user();

        $request->validate([
            'coupon_code' => 'required|string',
        ]);

        $result = $this->refereeCheckinV2Service->validateCoupon(
            $referee,
            (int) $campId,
            $request->coupon_code
        );

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Register for camp V2 (Payment = Registration) with Coupon + Sports Fee support.
     *
     * @param  Request      $request
     * @param  mixed        $campId
     * @return JsonResponse
     */
    public function registerForCamp(Request $request, $campId): JsonResponse
    {
        $referee = auth('api')->user();
        $result  = $this->refereeCheckinV2Service->registerForCamp(
            $referee,
            (int) $campId,
            $request->coupon_code
        );

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }
}
