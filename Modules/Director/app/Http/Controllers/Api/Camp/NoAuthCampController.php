<?php

namespace Modules\Director\Http\Controllers\Api\Camp;

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Director\Services\Camp\CampService;

class NoAuthCampController extends Controller
{
    use ApiResponse;

    protected CampService $campService;

    public function __construct(CampService $campService)
    {
        $this->campService = $campService;
    }

    /**
     * Get all active sports types.
     *
     * @return JsonResponse
     */
    public function getSportsType(): JsonResponse
    {
        $result = $this->campService->getSportsType();

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * List active camps with filters, sorting, and pagination.
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function campList(Request $request): JsonResponse
    {
        $result = $this->campService->campList($request);

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get paginated list of unique camp locations.
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function getLocations(Request $request): JsonResponse
    {
        $result = $this->campService->getLocations($request);

        return $this->success($result['message'], $result['data'], $result['code']);
    }
}
