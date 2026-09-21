<?php

namespace App\Http\Controllers\Api\DirectorCampManage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DirectorCampManage\AssignAssistantDirectorPermissionRequest;
use App\Http\Requests\Api\DirectorCampManage\AssistantDirectorListRequest;
use App\Http\Requests\Api\DirectorCampManage\RemoveAssistantDirectorPermissionRequest;
use App\Services\Api\DirectorCampManage\AssistantDirectorPermissionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class AssistantDirectorPermissionController extends Controller
{
    use ApiResponse;

    protected AssistantDirectorPermissionService $service;

    public function __construct(AssistantDirectorPermissionService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * Retrieve candidate assistant directors list with current camp permissions.
     *
     * @param  AssistantDirectorListRequest  $request
     * @return JsonResponse
     */
    public function assistantDirectorList(AssistantDirectorListRequest $request): JsonResponse
    {
        $campId = $request->validated('camp_id');
        $search = $request->validated('search');

        $data = $this->service->getAssistantDirectorList(
            (int) auth('api')->id(),
            $campId ? (int) $campId : null,
            $search ? (string) $search : null
        );

        return $this->success('Director list retrieved successfully.', $data, 200);
    }

    /**
     * Assign or update assistant director permissions for a camp.
     *
     * @param  AssignAssistantDirectorPermissionRequest  $request
     * @return JsonResponse
     */
    public function storeOrUpdate(AssignAssistantDirectorPermissionRequest $request): JsonResponse
    {
        $user = auth('api')->user();

        $result = $this->service->assignOrUpdatePermissions($user, $request->validated());

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get list of camps where the logged-in user is an assistant director.
     *
     * @return JsonResponse
     */
    public function assistantDirectorCampList(): JsonResponse
    {
        $user = auth('api')->user();

        $result = $this->service->getAssistantDirectorCampList($user);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Remove assistant director permission for a camp.
     *
     * @param  RemoveAssistantDirectorPermissionRequest  $request
     * @return JsonResponse
     */
    public function assistantDirectorCampPermissionRemove(RemoveAssistantDirectorPermissionRequest $request): JsonResponse
    {
        $user = auth('api')->user();

        $result = $this->service->removeCampPermission(
            (int) $user->id,
            (int) $request->validated('assistant_director_id'),
            (int) $request->validated('camp_id')
        );

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }
}
