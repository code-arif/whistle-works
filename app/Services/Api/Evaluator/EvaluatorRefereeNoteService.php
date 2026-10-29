<?php

namespace App\Services\Api\Evaluator;

use App\Models\EvaluatorRefereeNote;
use App\Models\RefereeEvaluation;
use App\Models\User;
use Modules\Director\Models\Camp;
use Modules\Director\Models\GameSlot;

class EvaluatorRefereeNoteService
{
    /**
     * Store or update an evaluator note for a referee in a camp / game slot.
     */
    public function saveOrUpdateNote(User $evaluator, array $data): array
    {
        $campId = $data['camp_id'] ?? null;
        $refereeId = $data['referee_id'] ?? null;
        $gameSlotId = $data['game_slot_id'] ?? null;
        $noteText = $data['note'] ?? '';

        $validation = $this->validateContext($evaluator, $campId, $refereeId, $gameSlotId);
        if (! $validation['success']) {
            return $validation;
        }

        $note = EvaluatorRefereeNote::updateOrCreate(
            [
                'evaluator_id' => $evaluator->id,
                'camp_id' => $campId,
                'referee_id' => $refereeId,
                'game_slot_id' => $gameSlotId,
            ],
            [
                'note' => $noteText,
            ]
        );

        $note->load(['referee', 'gameSlot', 'camp']);

        return [
            'success' => true,
            'code' => 200,
            'message' => 'Note saved successfully.',
            'data' => $this->formatNote($note),
        ];
    }

    /**
     * Update an existing note by its ID.
     */
    public function updateNoteById(User $evaluator, $noteId, string $noteText): array
    {
        $note = EvaluatorRefereeNote::with(['referee', 'gameSlot', 'camp'])->find($noteId);

        if (! $note) {
            return [
                'success' => false,
                'code' => 404,
                'message' => 'Note not found.',
                'data' => [],
            ];
        }

        if ($note->evaluator_id !== $evaluator->id) {
            return [
                'success' => false,
                'code' => 403,
                'message' => 'You do not have permission to edit this note.',
                'data' => [],
            ];
        }

        $note->note = $noteText;
        $note->save();

        return [
            'success' => true,
            'code' => 200,
            'message' => 'Note updated successfully.',
            'data' => $this->formatNote($note),
        ];
    }

    /**
     * Delete a note by its ID.
     */
    public function deleteNoteById(User $evaluator, $noteId): array
    {
        $note = EvaluatorRefereeNote::find($noteId);

        if (! $note) {
            return [
                'success' => false,
                'code' => 404,
                'message' => 'Note not found.',
                'data' => [],
            ];
        }

        if ($note->evaluator_id !== $evaluator->id) {
            return [
                'success' => false,
                'code' => 403,
                'message' => 'You do not have permission to delete this note.',
                'data' => [],
            ];
        }

        $note->delete();

        return [
            'success' => true,
            'code' => 200,
            'message' => 'Note deleted successfully.',
            'data' => [],
        ];
    }

    /**
     * Delete a note by its context (camp, referee, slot).
     */
    public function deleteNoteByContext(User $evaluator, $campId, $refereeId, $gameSlotId = null): array
    {
        $note = EvaluatorRefereeNote::where('evaluator_id', $evaluator->id)
            ->where('camp_id', $campId)
            ->where('referee_id', $refereeId)
            ->where('game_slot_id', $gameSlotId)
            ->first();

        if (! $note) {
            return [
                'success' => false,
                'code' => 404,
                'message' => 'Note not found.',
                'data' => [],
            ];
        }

        $note->delete();

        return [
            'success' => true,
            'code' => 200,
            'message' => 'Note deleted successfully.',
            'data' => [],
        ];
    }

    /**
     * Validate permissions, camp, referee, and game slot context.
     */
    protected function validateContext(User $evaluator, $campId, $refereeId, $gameSlotId = null): array
    {
        if (! $evaluator->hasAnyRole(['evaluator', 'director'])) {
            return [
                'success' => false,
                'code' => 403,
                'message' => 'Only evaluators can create evaluation notes.',
                'data' => [],
            ];
        }

        $camp = Camp::find($campId);
        if (! $camp) {
            return [
                'success' => false,
                'code' => 404,
                'message' => 'Camp not found.',
                'data' => [],
            ];
        }

        if (! RefereeEvaluation::canEvaluateInCamp($evaluator, $camp->id)) {
            if ($evaluator->hasRole('director')) {
                return [
                    'success' => false,
                    'code' => 403,
                    'message' => 'You can only take notes for referees in your own camps.',
                    'data' => [],
                ];
            }

            return [
                'success' => false,
                'code' => 403,
                'message' => 'You must be registered and approved for this camp to write notes.',
                'data' => [],
            ];
        }

        if ($refereeId) {
            $referee = User::find($refereeId);
            if (! $referee || ! $referee->hasRole('referee')) {
                return [
                    'success' => false,
                    'code' => 404,
                    'message' => 'Invalid referee selected.',
                    'data' => [],
                ];
            }
        }

        if ($gameSlotId) {
            $gameSlot = GameSlot::with('schedule')->find($gameSlotId);
            if (! $gameSlot || ($gameSlot->schedule && $gameSlot->schedule->camp_id != $campId)) {
                return [
                    'success' => false,
                    'code' => 404,
                    'message' => 'Invalid game slot for this camp.',
                    'data' => [],
                ];
            }
        }

        return ['success' => true];
    }

    /**
     * Helper to format note output consistently.
     */
    protected function formatNote(EvaluatorRefereeNote $note): array
    {
        $gameSlot = $note->gameSlot;
        $referee = $note->referee;

        return [
            'id' => $note->id,
            'camp_id' => $note->camp_id,
            'evaluator_id' => $note->evaluator_id,
            'referee_id' => $note->referee_id,
            'game_slot_id' => $note->game_slot_id,
            'note' => $note->note,
            'created_at' => $note->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $note->updated_at?->format('Y-m-d H:i:s'),
            'referee' => $referee ? [
                'id' => $referee->id,
                'name' => trim(($referee->first_name ?? '').' '.($referee->last_name ?? '')),
                'email' => $referee->email,
                'avatar' => $referee->avatar ? asset($referee->avatar) : asset('default/profile.jpg'),
            ] : null,
            'game_slot' => $gameSlot ? [
                'id' => $gameSlot->id,
                'game_date' => $gameSlot->game_date ? (is_string($gameSlot->game_date) ? $gameSlot->game_date : $gameSlot->game_date->toDateString()) : null,
                'court_name' => $gameSlot->court_name,
                'start_time' => $gameSlot->start_time,
                'end_time' => $gameSlot->end_time,
                'time' => $gameSlot->start_time.' - '.$gameSlot->end_time,
            ] : null,
        ];
    }
}
