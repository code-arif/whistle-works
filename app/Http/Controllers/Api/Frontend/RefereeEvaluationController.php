<?php

namespace App\Http\Controllers\Api\Frontend;

use Exception;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Models\RecommendedLevel;
use App\Models\RefereeEvaluation;
use Modules\Director\Models\Camp;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\CampEvaluatorRegistration;
use App\Http\Requests\RefereeEvaluationRequest;
use Modules\Director\Models\CampRefereeCheckin;
use App\Http\Resources\RefereeEvaluationResource;
use App\Http\Resources\RefereeEvaluationListResource;
use Modules\Director\Transformers\Referee\CheckedInRefereeResource;

class RefereeEvaluationController extends Controller
{
    use ApiResponse;

    /**
     * Create or update referee evaluation
     */
    public function storeOrUpdate(RefereeEvaluationRequest $request)
    {
        $user = auth('api')->user();
        $validated = $request->validated();

        // Check if user is evaluator or director
        if (!$user->hasAnyRole(['evaluator', 'director'])) {
            return $this->error([], 'Only evaluators and directors can create evaluations.', 403);
        }

        // Check if camp exists
        $camp = Camp::find($validated['camp_id']);
        if (!$camp) {
            return $this->error([], 'Camp not found.', 404);
        }

        // Check if evaluator is registered and approved for this camp
        if (!RefereeEvaluation::canEvaluateInCamp($user, $camp->id)) {
            if ($user->hasRole('director')) {
                return $this->error([], 'You can only evaluate referees in your own camps.', 403);
            } else {
                return $this->error([], 'You must be registered and approved for this camp to evaluate referees.', 403);
            }
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

        // Verify referee has checked in to this camp
        // $checkin = CampRefereeCheckin::where('camp_id', $camp->id)
        //     ->where('referee_id', $referee->id)
        //     ->where('registration_status', 'registered')
        //     ->first();

        // if (!$checkin) {
        //     return $this->error([], 'Referee has not checked in to this camp.', 400);
        // }

        $checkin = CampRefereeCheckin::where('camp_id', $camp->id)
            ->where('referee_id', $referee->id)
            ->first();

        if (!$checkin) {
            return $this->error([], 'Referee has not checked in to this camp.', 400);
        }

        DB::beginTransaction();
        try {
            // Find existing evaluation or create new one
            $evaluation = RefereeEvaluation::updateOrCreate(
                [
                    'referee_id' => $validated['referee_id'],
                    'evaluator_id' => $user->id,
                    'camp_id' => $validated['camp_id'],
                    'game_slot_id' => $validated['game_slot_id'] ?? null,
                ],
                [
                    'call_accuracy' => $validated['call_accuracy'] ?? null,
                    'communication_skills' => $validated['communication_skills'] ?? null,
                    'consistency_of_calls' => $validated['consistency_of_calls'] ?? null,
                    'court_position_mechanics' => $validated['court_position_mechanics'] ?? null,
                    'fitness_mobility' => $validated['fitness_mobility'] ?? null,
                    'game_awareness' => $validated['game_awareness'] ?? null,
                    'private_comments' => $validated['private_comments'] ?? null,
                    'referee_feedback' => $validated['referee_feedback'] ?? null,
                    'status' => $validated['status'] ?? 'draft',
                    'submitted_at' => ($validated['status'] ?? 'draft') === 'submitted' ? now() : null,
                ]
            );

            // Handle single recommended level
            if (isset($validated['recommended_level'])) {
                // Delete old level and insert new one
                $evaluation->recommendedLevels()->delete();

                RecommendedLevel::create([
                    'evaluation_id' => $evaluation->id,
                    'level' => $validated['recommended_level'],
                ]);
            }

            DB::commit();

            $message = $evaluation->wasRecentlyCreated
                ? 'Evaluation created successfully.'
                : 'Evaluation updated successfully.';

            return $this->success(
                $message,
                new RefereeEvaluationResource($evaluation->load(['referee', 'evaluator', 'camp', 'gameSlot', 'recommendedLevels'])),
                $evaluation->wasRecentlyCreated ? 201 : 200
            );
        } catch (Exception $e) {
            DB::rollBack();
            return $this->error([], 'Failed to save evaluation: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get evaluations by camp (for directors/evaluators)
     */
    public function getEvaluationsByCamp(Request $request, $campId)
    {
        $user = auth('api')->user();

        if (!$user->hasAnyRole(['director', 'evaluator'])) {
            return $this->error('Unauthorized access.', null, 403);
        }

        $camp = Camp::find($campId);
        if (!$camp) {
            return $this->error([], 'Camp not found.', 404);
        }

        if ($user->hasRole('director') && $camp->director_id !== $user->id) {
            return $this->error([], 'You can only view evaluations from your own camps.', 403);
        }

        if ($user->hasRole('evaluator') && !$user->hasRole('director')) {
            $registration = CampEvaluatorRegistration::where('camp_id', $campId)
                ->where('evaluator_id', $user->id)
                ->where('status', 'approved')
                ->first();

            if (!$registration) {
                return $this->error([], 'You must be registered and approved for this camp.', 403);
            }

            if (!$registration->can_view_own_evaluations) {
                return $this->error([], 'You do not have permission to view evaluations for this camp. Contact the director.', 403);
            }
        }

        $query = RefereeEvaluation::with(['referee', 'evaluator', 'gameSlot', 'recommendedLevels'])
            ->forCamp($campId);

        if ($request->has('referee_id')) {
            $query->forReferee($request->referee_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($user->hasRole('evaluator') && !$user->hasRole('director')) {
            $query->where('evaluator_id', $user->id);
        }

        $evaluations = $query->orderByDesc('average_score')->get();

        return $this->success(
            'Evaluations retrieved successfully.',
            [
                'evaluations' => RefereeEvaluationListResource::collection($evaluations),
                // 'pagination' => [
                //     'total'        => $evaluations->total(),
                //     'per_page'     => $evaluations->perPage(),
                //     'current_page' => $evaluations->currentPage(),
                //     'last_page'    => $evaluations->lastPage(),
                // ],
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

        if ($user->hasRole('evaluator') && !$user->hasRole('director')) {
            if ($request->has('camp_id')) {
                $registration = CampEvaluatorRegistration::where('camp_id', $request->camp_id)
                    ->where('evaluator_id', $user->id)
                    ->where('status', 'approved')
                    ->first();

                if (!$registration || !$registration->can_view_own_evaluations) {
                    return $this->error([], 'You do not have permission to view evaluations for this camp. Contact the director.', 403);
                }
            } else {
                $hasPermission = CampEvaluatorRegistration::where('evaluator_id', $user->id)
                    ->where('status', 'approved')
                    ->where('can_view_own_evaluations', true)
                    ->exists();

                if (!$hasPermission) {
                    return $this->error([], 'You do not have permission to view any evaluations. Contact camp directors.', 403);
                }
            }
        }

        $allowedCamps = collect();

        $query = RefereeEvaluation::with(['referee', 'camp', 'gameSlot', 'recommendedLevels'])
            ->byEvaluator($user->id);

        if ($request->has('camp_id')) {
            $query->where('camp_id', $request->camp_id);
        }

        if ($user->hasRole('evaluator') && !$user->hasRole('director')) {
            $allowedCamps = CampEvaluatorRegistration::where('evaluator_id', $user->id)
                ->where('status', 'approved')
                ->where('can_view_own_evaluations', true)
                ->pluck('camp_id');

            $query->whereIn('camp_id', $allowedCamps);
        }

        $evaluations = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        // Overall level summary
        $summaryQuery = RefereeEvaluation::with(['recommendedLevels'])
            ->byEvaluator($user->id)
            ->when($request->has('camp_id'), fn($q) => $q->where('camp_id', $request->camp_id))
            ->when(
                $user->hasRole('evaluator') && !$user->hasRole('director') && $allowedCamps->isNotEmpty(),
                fn($q) => $q->whereIn('camp_id', $allowedCamps)
            )
            ->has('recommendedLevels')
            ->get();

        $allLevels = ['NCAA D1', 'NCAA D2', 'NAIA', 'JUCO', 'HS', 'JH/ELEM'];
        $overallSummary = [];

        foreach ($allLevels as $level) {
            $count = RecommendedLevel::whereIn(
                'evaluation_id',
                $summaryQuery->pluck('id')
            )->where('level', $level)->count();

            if ($count > 0) {
                $overallSummary[] = [
                    'level' => $level,
                    'count' => $count,
                ];
            }
        }

        // Sort by count descending
        usort($overallSummary, fn($a, $b) => $b['count'] <=> $a['count']);

        return $this->success(
            'Your evaluations retrieved successfully.',
            [
                'evaluations' => RefereeEvaluationListResource::collection($evaluations),
                'overall_level_summary' => $overallSummary,
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
     * Get all checked-in referees for a camp that the evaluator can evaluate
     */
    public function getAllRegisteredInReferees($campId)
    {
        $user = auth('api')->user();

        // Check if camp exists
        $camp = Camp::find($campId);
        if (!$camp) {
            return $this->error('Camp not found.', null, 404);
        }

        // **NEW: Check if user can evaluate in this camp**
        if (!RefereeEvaluation::canEvaluateInCamp($user, $campId)) {
            if ($user->hasRole('director')) {
                return $this->error([], 'You can only view referees from your own camps.', 403);
            } else {
                return $this->error([], 'You must be registered and approved for this camp.', 403);
            }
        }

        $perPage = request()->get('per_page', 15);

        $checkedInReferees = CampRefereeCheckin::where('camp_id', $campId)
            ->where('registration_status', 'registered')
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

    /**
     * Get all referees for a camp that the evaluator can evaluate
     */
    public function getAllReferees($campId)
    {
        $user = auth('api')->user();

        // Check if camp exists
        $camp = Camp::find($campId);
        if (!$camp) {
            return $this->error('Camp not found.', null, 404);
        }

        // **NEW: Check if user can evaluate in this camp**
        if (!RefereeEvaluation::canEvaluateInCamp($user, $campId)) {
            if ($user->hasRole('director')) {
                return $this->error([], 'You can only view referees from your own camps.', 403);
            } else {
                return $this->error([], 'You must be registered and approved for this camp.', 403);
            }
        }

        $checkedInReferees = CampRefereeCheckin::where('camp_id', $campId)
            ->with('referee')
            ->get();

        return $this->success('Registered referees fetched successfully.', [
            'total' => $checkedInReferees->count(),
            'referees' => CheckedInRefereeResource::collection($checkedInReferees),
        ], 200);
    }
}
