<?php

namespace Modules\Director\Http\Controllers\Api\Camp;

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Director\Http\Requests\CampCreateRequest;
use Modules\Director\Services\Camp\CampService;

class CampManageController extends Controller
{
    use ApiResponse;

    protected CampService $campService;

    public function __construct(CampService $campService)
    {
        $this->campService = $campService;
    }

    /**
     * Create camp with timezone support.
     *
     * @param  CampCreateRequest $request
     * @return JsonResponse
     */
    public function createCamp(CampCreateRequest $request): JsonResponse
    {
        $user   = auth('api')->user();
        $result = $this->campService->createCamp(
            $user,
            $request->validated(),
            $request->hasFile('camp_logo') ? $request->file('camp_logo') : null
        );

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Edit / update a camp.
     *
     * @param  Request $request
     * @param  int     $id
     * @return JsonResponse
     */
    public function updateCamp(Request $request, $id): JsonResponse
    {
        $user   = auth('api')->user();
        $result = $this->campService->updateCamp($user, (int) $id, $request);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get available timezones for dropdown.
     *
     * @return JsonResponse
     */
    public function getAvailableTimezones(): JsonResponse
    {
        $result = $this->campService->getAvailableTimezones();

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Update camp status.
     *
     * @param  Request $request
     * @param  int     $id
     * @return JsonResponse
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        $user = auth('api')->user();

        $request->validate([
            'status' => 'required|in:active,inactive',
        ]);

        $result = $this->campService->updateStatus($user, (int) $id, $request->status);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get camp details.
     *
     * @param  int $id
     * @return JsonResponse
     */
    public function campDetails($id): JsonResponse
    {
        $user   = auth('api')->user();
        $result = $this->campService->campDetails($user, (int) $id);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * No-auth camp details.
     *
     * @param  int $id
     * @return JsonResponse
     */
    public function noAuthCampDetails($id): JsonResponse
    {
        $result = $this->campService->noAuthCampDetails((int) $id);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Delete camp.
     *
     * @param  int $id
     * @return JsonResponse
     */
    public function deleteCamp($id): JsonResponse
    {
        $user   = auth('api')->user();
        $result = $this->campService->deleteCamp($user, (int) $id);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Director camp list.
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function directorCampList(Request $request): JsonResponse
    {
        $user    = auth('api')->user();
        $perPage = (int) $request->input('per_page', 8);
        $result  = $this->campService->directorCampList($user, $perPage);

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get admin / sports fee list.
     *
     * @return JsonResponse
     */
    public function getAdminFee(): JsonResponse
    {
        $result = $this->campService->getAdminFee();

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get camp details for editing.
     *
     * @param  int $id
     * @return JsonResponse
     */
    public function campEdit($id): JsonResponse
    {
        $user   = auth('api')->user();
        $result = $this->campService->campEdit($user, (int) $id);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }
}
