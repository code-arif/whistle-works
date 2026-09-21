<?php

namespace App\Http\Controllers\Api\CampRanking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CampRanking\ToggleEvaluatorPermissionRequest;
use App\Http\Requests\Api\CampRanking\UpdateRankingSettingsRequest;
use App\Services\Api\CampRanking\CampRankingSettingsService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class CampRankingSettingsController extends Controller
{
    use ApiResponse;

    protected CampRankingSettingsService $service;

    public function __construct(CampRankingSettingsService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * Get ranking settings for a camp.
     *
     * @param  int|string  $campId
     * @return JsonResponse
     */
    public function getRankingSettings($campId): JsonResponse
    {
        $user = auth('api')->user();

        $result = $this->service->getRankingSettings($user, $campId);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data']);
    }

    /**
     * Update ranking settings for a camp.
     *
     * @param  UpdateRankingSettingsRequest  $request
     * @param  int|string  $campId
     * @return JsonResponse
     */
    public function updateRankingSettings(UpdateRankingSettingsRequest $request, $campId): JsonResponse
    {
        $user = auth('api')->user();

        $result = $this->service->updateRankingSettings($user, $campId, $request->validated());

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data']);
    }

    /**
     * Toggle individual evaluator's permission to view evaluations.
     *
     * @param  ToggleEvaluatorPermissionRequest  $request
     * @param  int|string  $campId
     * @param  int|string  $evaluatorId
     * @return JsonResponse
     */
    public function toggleEvaluatorPermission(ToggleEvaluatorPermissionRequest $request, $campId, $evaluatorId): JsonResponse
    {
        $user = auth('api')->user();

        $result = $this->service->toggleEvaluatorPermission(
            $user,
            $campId,
            $evaluatorId,
            (bool) $request->validated('can_view_evaluations')
        );

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data']);
    }
}
