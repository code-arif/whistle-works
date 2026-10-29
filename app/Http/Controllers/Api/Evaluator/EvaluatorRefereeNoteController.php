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
}
