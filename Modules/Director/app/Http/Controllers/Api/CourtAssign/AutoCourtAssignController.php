<?php

namespace Modules\Director\Http\Controllers\Api\CourtAssign;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Director\Services\CourtAssign\AutoCourtAssignService;

class AutoCourtAssignController extends Controller
{
    use ApiResponse;

    protected AutoCourtAssignService $autoCourtAssignService;

    public function __construct(AutoCourtAssignService $autoCourtAssignService)
    {
        $this->autoCourtAssignService = $autoCourtAssignService;
    }

    /**
     * Auto-assign referees to all available slots in a camp.
     * Applies 4 rules: rest windows, court rotation, fair distribution, randomness.
     *
     * @param  int $campId
     * @return JsonResponse
     */
    public function autoAssignReferees($campId): JsonResponse
    {
        $user   = auth('api')->user();
        $result = $this->autoCourtAssignService->autoAssignReferees($user, (int) $campId);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }
}
