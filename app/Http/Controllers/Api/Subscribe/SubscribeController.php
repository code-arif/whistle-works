<?php

namespace App\Http\Controllers\Api\Subscribe;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Subscribe\SubscribeRequest;
use App\Services\Api\Subscribe\SubscribeService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class SubscribeController extends Controller
{
    use ApiResponse;

    protected SubscribeService $subscribeService;

    public function __construct(SubscribeService $subscribeService)
    {
        $this->subscribeService = $subscribeService;
    }

    /**
     * Store newsletter subscriber.
     *
     * @param  SubscribeRequest  $request
     * @return JsonResponse
     */
    public function store(SubscribeRequest $request): JsonResponse
    {
        $result = $this->subscribeService->subscribe($request->validated()['email']);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'data'    => $result['data'],
                'code'    => $result['code'],
            ], $result['code']);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data'    => $result['data'],
            'code'    => $result['code'],
        ], $result['code']);
    }
}
