<?php

namespace Modules\Director\Http\Controllers\Api\CourtAssign;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Director\Services\CourtAssign\CourtAssignService;

class CourtAssignController extends Controller
{
    use ApiResponse;

    protected CourtAssignService $courtAssignService;

    public function __construct(CourtAssignService $courtAssignService)
    {
        $this->courtAssignService = $courtAssignService;
    }

    /**
     * Assign individual referees to a game slot.
     *
     * @param  Request $request
     * @param  int     $slotId
     * @return JsonResponse
     */
    public function assignIndividualReferees(Request $request, $slotId): JsonResponse
    {
        $request->validate([
            'assignments'            => 'nullable|array',
            'referee_ids'            => 'nullable',
            'position_ids'           => 'nullable',
            'override_restrictions'  => 'sometimes',
        ]);

        // Parse both payload formats here so the service stays HTTP-agnostic
        $assignments   = $request->input('assignments', $request->json('assignments'));
        $refereeIds    = $request->input('referee_ids', $request->json('referee_ids'));
        $positionIds   = $request->input('position_ids', $request->json('position_ids', []));
        $overrideRestrictions = $request->override_restrictions ?? false;

        $user   = auth('api')->user();
        $result = $this->courtAssignService->assignIndividualReferees(
            $user,
            (int) $slotId,
            $assignments,
            $refereeIds,
            $positionIds,
            $overrideRestrictions
        );

        // assignIndividualReferees returns a custom response format (raw json for partial success 207)
        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data'    => $result['data'],
            'code'    => $result['code'],
        ], $result['code']);
    }

    /**
     * Assign crew to a game slot.
     *
     * @param  Request $request
     * @param  int     $slotId
     * @return JsonResponse
     */
    public function assignCrew(Request $request, $slotId): JsonResponse
    {
        $request->validate([
            'crew_id' => 'required|exists:crews,id',
        ]);

        $user   = auth('api')->user();
        $result = $this->courtAssignService->assignCrew($user, (int) $slotId, (int) $request->input('crew_id'));

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get all referees for a specific slot with availability status.
     *
     * @param  int $slotId
     * @return JsonResponse
     */
    public function getAvailableRefereesForSlot($slotId): JsonResponse
    {
        $user   = auth('api')->user();
        $result = $this->courtAssignService->getAvailableRefereesForSlot($user, (int) $slotId);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Remove assignment (individual referee or entire crew).
     *
     * @param  int $assignmentId
     * @return JsonResponse
     */
    public function removeAssignment($assignmentId): JsonResponse
    {
        $user   = auth('api')->user();
        $result = $this->courtAssignService->removeAssignment($user, (int) $assignmentId);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Clear all assignments for a schedule.
     *
     * @param  int $scheduleId
     * @return JsonResponse
     */
    public function clearScheduleAssignments($scheduleId): JsonResponse
    {
        $user   = auth('api')->user();
        $result = $this->courtAssignService->clearScheduleAssignments($user, (int) $scheduleId);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get all assignments for a specific slot.
     *
     * @param  int $slotId
     * @return JsonResponse
     */
    public function getSlotAssignments($slotId): JsonResponse
    {
        $user   = auth('api')->user();
        $result = $this->courtAssignService->getSlotAssignments($user, (int) $slotId);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get available referees for a camp (not assigned anywhere).
     *
     * @param  int $campId
     * @return JsonResponse
     */
    public function getAvailableReferees($campId): JsonResponse
    {
        $user   = auth('api')->user();
        $result = $this->courtAssignService->getAvailableReferees($user, (int) $campId);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get assigned referees or crew for a slot.
     *
     * @param  int $slotId
     * @return JsonResponse
     */
    public function getAssignedRefereesOrCrew($slotId): JsonResponse
    {
        $user   = auth('api')->user();
        $result = $this->courtAssignService->getAssignedRefereesOrCrew($user, (int) $slotId);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get all crews for a specific slot with availability status.
     *
     * @param  int $slotId
     * @return JsonResponse
     */
    public function getAvailableCrewsForSlot($slotId): JsonResponse
    {
        $user   = auth('api')->user();
        $result = $this->courtAssignService->getAvailableCrewsForSlot($user, (int) $slotId);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Switch slot mode between crew and individual.
     *
     * @param  Request $request
     * @param  int     $slotId
     * @return JsonResponse
     */
    public function switchMode(Request $request, $slotId): JsonResponse
    {
        $request->validate([
            'mode' => 'nullable|in:crew,individual',
        ]);

        $user   = auth('api')->user();
        $result = $this->courtAssignService->switchMode($user, (int) $slotId, $request->input('mode'));

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Bulk switch mode for all unassigned game slots in a schedule.
     *
     * @param  Request $request
     * @param  int     $campId
     * @param  int     $scheduleId
     * @return JsonResponse
     */
    public function bulkSwitchMode(Request $request, $campId, $scheduleId): JsonResponse
    {
        $request->validate([
            'mode' => 'required|in:crew,individual',
        ]);

        $user   = auth('api')->user();
        $result = $this->courtAssignService->bulkSwitchMode(
            $user,
            (int) $campId,
            (int) $scheduleId,
            $request->input('mode')
        );

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }
}
