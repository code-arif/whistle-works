<?php

namespace Modules\Director\Http\Controllers\Api\Referee;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Director\Services\Referee\RefereeService;

class RefereeManageController extends Controller
{
    use ApiResponse;

    protected RefereeService $refereeService;

    public function __construct(RefereeService $refereeService)
    {
        $this->refereeService = $refereeService;
    }

    /**
     * Director manually checks in a referee.
     * Use case: Referee registered but forgot to check-in, or has proof of attendance.
     *
     * @param  mixed        $campId
     * @param  mixed        $refereeId
     * @return JsonResponse
     */
    public function directorManualCheckInReferee($campId, $refereeId): JsonResponse
    {
        $director = auth('api')->user();
        $result   = $this->refereeService->directorManualCheckInReferee(
            $director,
            (int) $campId,
            (int) $refereeId
        );

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Bulk manual check-in (multiple referees at once).
     *
     * @param  Request      $request
     * @param  mixed        $campId
     * @return JsonResponse
     */
    public function bulkManualCheckIn(Request $request, $campId): JsonResponse
    {
        $director = auth('api')->user();

        $request->validate([
            'referee_ids'   => 'required|array|min:1',
            'referee_ids.*' => 'required|exists:users,id',
        ]);

        $result = $this->refereeService->bulkManualCheckIn(
            $director,
            (int) $campId,
            $request->referee_ids
        );

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Update referee jersey number for a specific camp.
     *
     * @param  Request      $request
     * @param  mixed        $campId
     * @param  mixed        $refereeId
     * @return JsonResponse
     */
    public function updateRefereeJourcyNumber(Request $request, $campId, $refereeId): JsonResponse
    {
        $director = auth('api')->user();

        $request->validate([
            'jourcy_number' => 'nullable|string|max:3',
            'jersey_number' => 'nullable|string|max:3',
            'note'          => 'nullable|string',
            'notes'         => 'nullable|string',
        ]);

        $jersey = $request->input('jourcy_number', $request->input('jersey_number'));
        $note   = $request->has('note') ? $request->input('note') : ($request->has('notes') ? $request->input('notes') : null);

        $result = $this->refereeService->updateRefereeJourcyNumber(
            $director,
            (int) $campId,
            (int) $refereeId,
            $jersey,
            $note
        );

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Update referee note for a specific camp.
     *
     * @param  Request      $request
     * @param  mixed        $campId
     * @param  mixed        $refereeId
     * @return JsonResponse
     */
    public function updateRefereeNote(Request $request, $campId, $refereeId): JsonResponse
    {
        $director = auth('api')->user();

        $request->validate([
            'note'  => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $note = $request->input('note', $request->input('notes'));

        $result = $this->refereeService->updateRefereeNote(
            $director,
            (int) $campId,
            (int) $refereeId,
            $note
        );

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get referee note for a specific camp.
     *
     * @param  Request      $request
     * @param  mixed        $campId
     * @param  mixed        $refereeId
     * @return JsonResponse
     */
    public function getRefereeNote(Request $request, $campId, $refereeId): JsonResponse
    {
        $director = auth('api')->user();

        $result = $this->refereeService->getRefereeNote(
            $director,
            (int) $campId,
            (int) $refereeId
        );

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Director: Remove a referee from a camp (delete registration/check-in).
     *
     * @param  Request      $request
     * @param  mixed        $campId
     * @param  mixed        $refereeId
     * @return JsonResponse
     */
    public function removeRefereeFromCamp(Request $request, $campId, $refereeId): JsonResponse
    {
        $director = auth('api')->user();
        $result   = $this->refereeService->removeRefereeFromCamp(
            $director,
            (int) $campId,
            (int) $refereeId
        );

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }
}
