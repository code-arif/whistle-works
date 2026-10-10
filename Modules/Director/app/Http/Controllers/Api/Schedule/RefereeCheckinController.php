<?php

namespace Modules\Director\Http\Controllers\Api\Schedule;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Director\Services\Schedule\RefereeCheckinService;

class RefereeCheckinController extends Controller
{
    use ApiResponse;

    protected RefereeCheckinService $refereeCheckinService;

    public function __construct(RefereeCheckinService $refereeCheckinService)
    {
        $this->refereeCheckinService = $refereeCheckinService;
    }

    /**
     * Register for camp (Payment = Registration) (V1).
     *
     * @param  Request      $request
     * @param  mixed        $campId
     * @return JsonResponse
     */
    public function registerForCamp(Request $request, $campId): JsonResponse
    {
        $referee = auth('api')->user();
        $result  = $this->refereeCheckinService->registerForCamp($referee, (int) $campId, $request);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Handle payment success callback.
     * Automatically creates registration when payment succeeds.
     *
     * @param  Request      $request
     * @return JsonResponse
     */
    public function handlePaymentSuccess(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string',
            'camp_id'    => 'required|exists:camps,id',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Invalid request.', 422);
        }

        $referee = auth('api')->user();
        $result  = $this->refereeCheckinService->handlePaymentSuccess($referee, $request->session_id, (int) $request->camp_id);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * STEP 2: Check-in to camp (Physical attendance).
     * Only works during camp dates (start_date to end_date).
     *
     * @param  mixed        $campId
     * @return JsonResponse
     */
    public function checkIn($campId): JsonResponse
    {
        $referee = auth('api')->user();
        $result  = $this->refereeCheckinService->checkIn($referee, (int) $campId);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get my registrations (all camps I've registered for).
     *
     * @param  Request      $request
     * @return JsonResponse
     */
    public function getMyRegistrations(Request $request): JsonResponse
    {
        $referee = auth('api')->user();
        $result  = $this->refereeCheckinService->getMyRegistrations($referee, $request);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get my checked-in camps (Referee).
     *
     * @param  Request      $request
     * @return JsonResponse
     */
    public function getMyCheckins(Request $request): JsonResponse
    {
        $referee = auth('api')->user();
        $result  = $this->refereeCheckinService->getMyCheckins($referee, $request);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get active camps (upcoming + ongoing).
     *
     * @param  Request      $request
     * @return JsonResponse
     */
    public function getActiveCamps(Request $request): JsonResponse
    {
        $referee = auth('api')->user();
        $result  = $this->refereeCheckinService->getActiveCamps($referee, $request);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get previous/completed camps.
     *
     * @param  Request      $request
     * @return JsonResponse
     */
    public function getPreviousCamps(Request $request): JsonResponse
    {
        $referee = auth('api')->user();
        $result  = $this->refereeCheckinService->getPreviousCamps($referee, $request);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get previous/completed camps for director only.
     *
     * @param  Request      $request
     * @return JsonResponse
     */
    public function getDirectorPreviousCamps(Request $request): JsonResponse
    {
        $director = auth('api')->user();
        $result   = $this->refereeCheckinService->getDirectorPreviousCamps($director, $request);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }
}
