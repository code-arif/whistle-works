<?php

namespace Modules\Director\Http\Controllers\Api\Crew;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Director\Http\Requests\Crew\AddCrewMembersRequest;
use Modules\Director\Http\Requests\Crew\AssignCrewToSlotRequest;
use Modules\Director\Http\Requests\Crew\CreateCrewRequest;
use Modules\Director\Http\Requests\Crew\RemoveCrewMembersRequest;
use Modules\Director\Http\Requests\Crew\UpdateCrewRequest;
use Modules\Director\Services\Crew\CrewService;

class CrewManageController extends Controller
{
    use ApiResponse;

    protected CrewService $crewService;

    public function __construct(CrewService $crewService)
    {
        $this->crewService = $crewService;
    }

    /**
     * Create a new crew (with optional members).
     *
     * @param  CreateCrewRequest $request
     * @param  int               $campId
     * @return JsonResponse
     */
    public function createCrew(CreateCrewRequest $request, $campId): JsonResponse
    {
        $user   = auth('api')->user();
        $result = $this->crewService->createCrew($user, (int) $campId, $request);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get all crews for a camp.
     *
     * @param  int $campId
     * @return JsonResponse
     */
    public function getCrews($campId): JsonResponse
    {
        $user   = auth('api')->user();
        $result = $this->crewService->getCrews($user, (int) $campId);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Export camp crews data as CSV or Excel (Director only).
     *
     * @param  Request $request
     * @param  int     $campId
     * @return mixed
     */
    public function exportCampCrews(Request $request, $campId)
    {
        $user   = auth('api')->user();
        $format = strtolower($request->query('format', $request->query('type', 'csv')));
        $result = $this->crewService->exportCampCrews($user, (int) $campId, $format);

        // Service returns StreamedResponse directly on success
        if (is_array($result)) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $result;
    }

    /**
     * Get crew details.
     *
     * @param  int $crewId
     * @return JsonResponse
     */
    public function getCrewDetails($crewId): JsonResponse
    {
        $result = $this->crewService->getCrewDetails((int) $crewId);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Add referees to crew.
     *
     * @param  AddCrewMembersRequest $request
     * @param  int                   $crewId
     * @return JsonResponse
     */
    public function addMembers(AddCrewMembersRequest $request, $crewId): JsonResponse
    {
        $user   = auth('api')->user();
        $result = $this->crewService->addMembers($user, (int) $crewId, $request);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Remove referees from crew.
     *
     * @param  RemoveCrewMembersRequest $request
     * @param  int                      $crewId
     * @return JsonResponse
     */
    public function removeMembers(RemoveCrewMembersRequest $request, $crewId): JsonResponse
    {
        $user   = auth('api')->user();
        $result = $this->crewService->removeMembers($user, (int) $crewId, $request->referee_ids);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Update crew details and/or sync members.
     *
     * @param  UpdateCrewRequest $request
     * @param  int               $crewId
     * @return JsonResponse
     */
    public function updateCrew(UpdateCrewRequest $request, $crewId): JsonResponse
    {
        $user   = auth('api')->user();
        $result = $this->crewService->updateCrew($user, (int) $crewId, $request);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Delete a crew.
     *
     * @param  int $crewId
     * @return JsonResponse
     */
    public function deleteCrew($crewId): JsonResponse
    {
        $user   = auth('api')->user();
        $result = $this->crewService->deleteCrew($user, (int) $crewId);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Assign crew to game slot.
     *
     * @param  AssignCrewToSlotRequest $request
     * @param  int                     $gameSlotId
     * @return JsonResponse
     */
    public function assignCrewToSlot(AssignCrewToSlotRequest $request, $gameSlotId): JsonResponse
    {
        $user   = auth('api')->user();
        $result = $this->crewService->assignCrewToSlot($user, (int) $gameSlotId, (int) $request->crew_id);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Remove crew assignment from game slot.
     *
     * @param  int $gameSlotId
     * @return JsonResponse
     */
    public function removeCrewFromSlot($gameSlotId): JsonResponse
    {
        $user   = auth('api')->user();
        $result = $this->crewService->removeCrewFromSlot($user, (int) $gameSlotId);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get available referees for crew (not in any crew for this camp).
     *
     * @param  Request $request
     * @param  int     $campId
     * @return JsonResponse
     */
    public function getAvailableReferees(Request $request, $campId): JsonResponse
    {
        $user   = auth('api')->user();
        $result = $this->crewService->getAvailableReferees($user, (int) $campId, $request);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get all checked-in referees for a camp.
     *
     * @param  Request $request
     * @param  int     $campId
     * @return JsonResponse
     */
    public function getAllCheckedInReferees(Request $request, $campId): JsonResponse
    {
        $user   = auth('api')->user();
        $result = $this->crewService->getAllCheckedInReferees($user, (int) $campId, $request);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }
}
