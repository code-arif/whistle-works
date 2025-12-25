<?php

namespace App\Http\Controllers\Api\Frontend;

use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Models\RefereeEvaluation;
use Modules\Director\Models\Camp;
use App\Http\Controllers\Controller;
use App\Http\Requests\RefereeEvaluationRequest;
use Modules\Director\Models\CampRefereeCheckin;
use App\Http\Resources\RefereeEvaluationResource;
use Modules\Director\Transformers\Referee\CheckedInRefereeResource;

class RefereeEvaluationController extends Controller
{
    use ApiResponse;

    /**
     * Create a new referee evaluation
     */
    public function store(RefereeEvaluationRequest $request)
    {
        $user = auth('api')->user();
        $validated = $request->validated();

        // Check if user is evaluator or director
        if (!$user->hasAnyRole(['evaluator', 'director'])) {
            return $this->error([], 'Only evaluators and directors can create evaluations.', 403);
        }

        // Check if referee exists and has checked in to the camp
        $referee = User::find($validated['referee_id']);
        if (!$referee || !$referee->hasRole('referee')) {
            return $this->error([], 'Invalid referee selected.', 404);
        }

        // Prevent self-evaluation
        if ($referee->id === $user->id) {
            return $this->error([], 'You cannot evaluate yourself.', 403);
        }

        // Check if camp exists
        $camp = Camp::find($validated['camp_id']);
        if (!$camp) {
            return $this->error([], 'Camp not found.', 404);
        }

        // Verify referee has checked in to this camp
        $checkin = CampRefereeCheckin::where('camp_id', $camp->id)
            ->where('referee_id', $referee->id)
            ->first();

        if (!$checkin) {
            return $this->error([], 'Referee has not checked in to this camp.', 400);
        }

        // Check for duplicate evaluation
        $existingEvaluation = RefereeEvaluation::where('referee_id', $validated['referee_id'])
            ->where('evaluator_id', $user->id)
            ->where('camp_id', $validated['camp_id'])
            ->where('game_slot_id', $validated['game_slot_id'] ?? null)
            ->first();

        if ($existingEvaluation) {
            return $this->error([], 'You have already evaluated this referee for this camp.', 409);
        }

        // Create evaluation
        $evaluation = RefereeEvaluation::create([
            'referee_id' => $validated['referee_id'],
            'evaluator_id' => $user->id,
            'camp_id' => $validated['camp_id'],
            'game_slot_id' => $validated['game_slot_id'] ?? null,
            'call_accuracy' => $validated['call_accuracy'] ?? null,
            'communication_skills' => $validated['communication_skills'] ?? null,
            'consistency_of_calls' => $validated['consistency_of_calls'] ?? null,
            'court_position_mechanics' => $validated['court_position_mechanics'] ?? null,
            'fitness_mobility' => $validated['fitness_mobility'] ?? null,
            'game_awareness' => $validated['game_awareness'] ?? null,
            'private_comments' => $validated['private_comments'] ?? null,
            'referee_feedback' => $validated['referee_feedback'] ?? null,
            'recommended_highest_level' => $validated['recommended_highest_level'] ?? null,
            'status' => $validated['status'] ?? 'draft',
            'submitted_at' => ($validated['status'] ?? 'draft') === 'submitted' ? now() : null,
        ]);

        return $this->success(
            'Evaluation created successfully.',
            new RefereeEvaluationResource($evaluation->load(['referee', 'evaluator', 'camp', 'gameSlot'])),
            201
        );
    }

    /**
     * Update an existing evaluation
     */
    public function update(RefereeEvaluationRequest $request, $id)
    {
        $user = auth('api')->user();

        $evaluation = RefereeEvaluation::find($id);
        if (!$evaluation) {
            return $this->error([], 'Evaluation not found.', 404);
        }

        // Check permission to edit
        if (!$evaluation->canBeEditedBy($user)) {
            return $this->error([], 'You do not have permission to edit this evaluation.', 403);
        }

        $validated = $request->validated();

        // Update evaluation
        $evaluation->update([
            'call_accuracy' => $validated['call_accuracy'] ?? $evaluation->call_accuracy,
            'communication_skills' => $validated['communication_skills'] ?? $evaluation->communication_skills,
            'consistency_of_calls' => $validated['consistency_of_calls'] ?? $evaluation->consistency_of_calls,
            'court_position_mechanics' => $validated['court_position_mechanics'] ?? $evaluation->court_position_mechanics,
            'fitness_mobility' => $validated['fitness_mobility'] ?? $evaluation->fitness_mobility,
            'game_awareness' => $validated['game_awareness'] ?? $evaluation->game_awareness,
            'private_comments' => $validated['private_comments'] ?? $evaluation->private_comments,
            'referee_feedback' => $validated['referee_feedback'] ?? $evaluation->referee_feedback,
            'recommended_highest_level' => $validated['recommended_highest_level'] ?? $evaluation->recommended_highest_level,
            'status' => $validated['status'] ?? $evaluation->status,
            'submitted_at' => ($validated['status'] ?? $evaluation->status) === 'submitted' && !$evaluation->submitted_at
                ? now()
                : $evaluation->submitted_at,
        ]);

        return $this->success(
            'Evaluation updated successfully.',
            new RefereeEvaluationResource($evaluation->fresh()->load(['referee', 'evaluator', 'camp', 'gameSlot']))
        );
    }

    /**
     * Get evaluations by camp (for directors/evaluators)
     */
    public function getEvaluationsByCamp(Request $request, $campId)
    {
        $user = auth('api')->user();

        // Only directors and evaluators can view camp evaluations
        if (!$user->hasAnyRole(['director', 'evaluator'])) {
            return $this->error('Unauthorized access.', null, 403);
        }

        $camp = Camp::find($campId);
        if (!$camp) {
            return $this->error([], 'Camp not found.', 404);
        }

        $query = RefereeEvaluation::with(['referee', 'evaluator', 'gameSlot'])
            ->forCamp($campId);

        // Filter by referee if provided
        if ($request->has('referee_id')) {
            $query->forReferee($request->referee_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $evaluations = $query->orderByDesc('average_score')
            ->paginate($request->get('per_page', 15));

        return $this->success(
            'Evaluations retrieved successfully.',
            [
                'evaluations' => RefereeEvaluationResource::collection($evaluations),
                'pagination' => [
                    'total'        => $evaluations->total(),
                    'per_page'     => $evaluations->perPage(),
                    'current_page' => $evaluations->currentPage(),
                    'last_page'    => $evaluations->lastPage(),
                ],
            ]
        );
    }


    /**
     * Get evaluations created by the authenticated evaluator
     */
    public function getMyEvaluations(Request $request)
    {
        $user = auth('api')->user();

        if (!$user->hasAnyRole(['evaluator', 'director'])) {
            return $this->error([], 'Only evaluators and directors can access this.', 403);
        }

        $evaluations = RefereeEvaluation::with(['referee', 'camp', 'gameSlot'])
            ->byEvaluator($user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->success(
            'Your evaluations retrieved successfully.',
            [
                'evaluations' => RefereeEvaluationResource::collection($evaluations),
                'pagination' => [
                    'total'        => $evaluations->total(),
                    'per_page'     => $evaluations->perPage(),
                    'current_page' => $evaluations->currentPage(),
                    'last_page'    => $evaluations->lastPage(),
                ],
            ]
        );
    }

    /**
     * Get a single evaluation by ID
     */
    public function show($id)
    {
        $user = auth('api')->user();

        $evaluation = RefereeEvaluation::with(['referee', 'evaluator', 'camp', 'gameSlot'])->find($id);

        if (!$evaluation) {
            return $this->error([], 'Evaluation not found.', 404);
        }

        // Check if user can view this evaluation
        if (!$evaluation->canBeViewedBy($user)) {
            return $this->error([], 'You do not have permission to view this evaluation.', 403);
        }

        return $this->success(
            'Evaluation retrieved successfully.',
            new RefereeEvaluationResource($evaluation)
        );
    }

    /**
     * Delete an evaluation (only by creator or director)
     */
    public function destroy($id)
    {
        $user = auth('api')->user();

        $evaluation = RefereeEvaluation::find($id);
        if (!$evaluation) {
            return $this->error([], 'Evaluation not found.', 404);
        }

        if (!$evaluation->canBeEditedBy($user)) {
            return $this->error([], 'You do not have permission to delete this evaluation.', 403);
        }

        $evaluation->delete();

        return $this->success('Evaluation deleted successfully.', [], 200);
    }


    /**
     * Get all evaluations for a specific referee (for referee's own view)
     */
    public function getRefereeEvaluations(Request $request)
    {
        $user = auth('api')->user();

        // Only referees can access their own evaluations
        if (!$user->hasRole('referee')) {
            return $this->error([], 'This endpoint is only for referees.', 403);
        }

        $evaluations = RefereeEvaluation::with(['evaluator', 'camp', 'gameSlot'])
            ->forReferee($user->id)
            ->submitted()
            ->orderBy('submitted_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->success(
            'Evaluations retrieved successfully.',
            [
                'evaluations' => RefereeEvaluationResource::collection($evaluations),
                'pagination' => [
                    'total'        => $evaluations->total(),
                    'per_page'     => $evaluations->perPage(),
                    'current_page' => $evaluations->currentPage(),
                    'last_page'    => $evaluations->lastPage(),
                ],
            ]
        );
    }


    /**
     * Get referee statistics (average scores, count, etc.)
     */
    public function getRefereeStats($refereeId)
    {
        $user = auth('api')->user();

        // Only directors, evaluators, and the referee themselves can view stats
        if (!$user->hasAnyRole(['director', 'evaluator']) && $user->id != $refereeId) {
            return $this->error('Unauthorized access.', null, 403);
        }

        $referee = User::find($refereeId);
        if (!$referee || !$referee->hasRole('referee')) {
            return $this->error('Referee not found.', null, 404);
        }

        $evaluations = RefereeEvaluation::forReferee($refereeId)->submitted()->get();

        if ($evaluations->isEmpty()) {
            return $this->success('No evaluations found for this referee.', [
                'referee' => [
                    'id' => $referee->id,
                    'name' => $referee->first_name . ' ' . $referee->last_name,
                ],
                'total_evaluations' => 0,
                'averages' => null,
            ]);
        }

        $stats = [
            'referee' => [
                'id' => $referee->id,
                'name' => $referee->first_name . ' ' . $referee->last_name,
                'email' => $referee->email,
            ],
            'total_evaluations' => $evaluations->count(),
            'averages' => [
                'call_accuracy' => round($evaluations->avg('call_accuracy'), 2),
                'communication_skills' => round($evaluations->avg('communication_skills'), 2),
                'consistency_of_calls' => round($evaluations->avg('consistency_of_calls'), 2),
                'court_position_mechanics' => round($evaluations->avg('court_position_mechanics'), 2),
                'fitness_mobility' => round($evaluations->avg('fitness_mobility'), 2),
                'game_awareness' => round($evaluations->avg('game_awareness'), 2),
                'total_score' => round($evaluations->avg('total_score'), 2),
            ],
            'overall_percentage' => round(($evaluations->avg('total_score') / 60) * 100, 2),
        ];

        return $this->success('Referee statistics retrieved successfully.', $stats);
    }

    /**
     * Get all checked-in referees for a camp
     */
    public function getAllRegisteredInReferees($campId)
    {
        $user = auth('api')->user();

        // Verify camp ownership
        $camp = Camp::where('id', $campId)->first();

        if (!$camp) {
            return $this->error('Camp not found.', null, 404);
        }

        $perPage = request()->get('per_page', 15); // default 15

        $checkedInReferees = CampRefereeCheckin::where('camp_id', $campId)
            ->with('referee')
            ->paginate($perPage);

        return $this->success('Registered referees fetched successfully.', [
            'total' => $checkedInReferees->total(),
            'referees' => CheckedInRefereeResource::collection($checkedInReferees),
            'pagination' => [
                'total'         => $checkedInReferees->total(),
                'per_page'      => $checkedInReferees->perPage(),
                'current_page'  => $checkedInReferees->currentPage(),
                'last_page'     => $checkedInReferees->lastPage(),
            ],
        ], 200);
    }
}
