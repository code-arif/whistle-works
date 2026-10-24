<?php

namespace App\Http\Controllers\Api\Evaluator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Evaluator\SaveEvaluatorRefereeNoteRequest;
use App\Services\Api\Evaluator\EvaluatorRefereeNoteService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EvaluatorRefereeNoteController extends Controller
{
    use ApiResponse;

    protected EvaluatorRefereeNoteService $service;

    public function __construct(EvaluatorRefereeNoteService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * Store an evaluator's note for a referee in a camp/game slot.
     */
    public function store(SaveEvaluatorRefereeNoteRequest $request): JsonResponse
    {
        $user = auth('api')->user();
        if (! $user) {
            return $this->error([], 'Unauthenticated.', 401);
        }

        $result = $this->service->saveOrUpdateNote($user, $request->validated());

        if (! $result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Update a note by its ID.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user = auth('api')->user();
        if (! $user) {
            return $this->error([], 'Unauthenticated.', 401);
        }

        $request->validate([
            'note' => ['required', 'string', 'max:5000'],
        ]);

        $result = $this->service->updateNoteById($user, $id, $request->input('note'));

        if (! $result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Show a note by its ID.
     */
    // public function show($id): JsonResponse
    // {
    //     $user = auth('api')->user();
    //     if (! $user) {
    //         return $this->error([], 'Unauthenticated.', 401);
    //     }

    //     $result = $this->service->getNoteById($user, $id);

    //     if (! $result['success']) {
    //         return $this->error($result['data'], $result['message'], $result['code']);
    //     }

    //     return $this->success($result['message'], $result['data'], $result['code']);
    // }

    /**
     * Delete a note by its ID.
     */
    public function destroy($id): JsonResponse
    {
        $user = auth('api')->user();
        if (! $user) {
            return $this->error([], 'Unauthenticated.', 401);
        }

        $result = $this->service->deleteNoteById($user, $id);

        if (! $result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Find a note by query parameters (camp_id, referee_id, game_slot_id).
     */
    // public function getNote(Request $request): JsonResponse
    // {
    //     $user = auth('api')->user();
    //     if (! $user) {
    //         return $this->error([], 'Unauthenticated.', 401);
    //     }

    //     $request->validate([
    //         'camp_id' => ['required', 'integer'],
    //         'referee_id' => ['required', 'integer'],
    //         'game_slot_id' => ['nullable', 'integer'],
    //     ]);

    //     $result = $this->service->getNote(
    //         $user,
    //         $request->input('camp_id'),
    //         $request->input('referee_id'),
    //         $request->input('game_slot_id')
    //     );

    //     if (! $result['success']) {
    //         return $this->error($result['data'], $result['message'], $result['code']);
    //     }

    //     return $this->success($result['message'], $result['data'], $result['code']);
    // }

    /**
     * Get all notes written by this evaluator for a specific referee in a camp.
     */
    public function getNotesByReferee($campId, $refereeId): JsonResponse
    {
        $user = auth('api')->user();
        if (! $user) {
            return $this->error([], 'Unauthenticated.', 401);
        }

        $result = $this->service->getNotesByReferee($user, $campId, $refereeId);

        if (! $result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Save/update note using path parameters (camp/{campId}/slot/{gameSlotId}/referee/{refereeId}).
     */
    // public function saveNoteByPath(Request $request, $campId, $gameSlotId, $refereeId): JsonResponse
    // {
    //     $user = auth('api')->user();
    //     if (! $user) {
    //         return $this->error([], 'Unauthenticated.', 401);
    //     }

    //     $request->validate([
    //         'note' => ['required', 'string', 'max:5000'],
    //     ]);

    //     $data = [
    //         'camp_id' => $campId,
    //         'referee_id' => $refereeId,
    //         'game_slot_id' => $gameSlotId,
    //         'note' => $request->input('note'),
    //     ];

    //     $result = $this->service->saveOrUpdateNote($user, $data);

    //     if (! $result['success']) {
    //         return $this->error($result['data'], $result['message'], $result['code']);
    //     }

    //     return $this->success($result['message'], $result['data'], $result['code']);
    // }

    /**
     * Get note using path parameters.
     */
    // public function getNoteByPath($campId, $gameSlotId, $refereeId): JsonResponse
    // {
    //     $user = auth('api')->user();
    //     if (! $user) {
    //         return $this->error([], 'Unauthenticated.', 401);
    //     }

    //     $result = $this->service->getNote($user, $campId, $refereeId, $gameSlotId);

    //     if (! $result['success']) {
    //         return $this->error($result['data'], $result['message'], $result['code']);
    //     }

    //     return $this->success($result['message'], $result['data'], $result['code']);
    // }

    /**
     * Delete note using path parameters.
     */
    // public function deleteNoteByPath($campId, $gameSlotId, $refereeId): JsonResponse
    // {
    //     $user = auth('api')->user();
    //     if (! $user) {
    //         return $this->error([], 'Unauthenticated.', 401);
    //     }

    //     $result = $this->service->deleteNoteByContext($user, $campId, $refereeId, $gameSlotId);

    //     if (! $result['success']) {
    //         return $this->error($result['data'], $result['message'], $result['code']);
    //     }

    //     return $this->success($result['message'], $result['data'], $result['code']);
    // }
}
