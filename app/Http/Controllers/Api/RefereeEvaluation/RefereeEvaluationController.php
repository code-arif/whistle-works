<?php

namespace App\Http\Controllers\Api\RefereeEvaluation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RefereeEvaluation\RefereeEvaluationRequest;
use App\Http\Requests\Api\RefereeEvaluation\UpdateRefereeEvaluationRequest;
use App\Services\Api\RefereeEvaluation\RefereeEvaluationExportService;
use App\Services\Api\RefereeEvaluation\RefereeEvaluationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RefereeEvaluationController extends Controller
{
    use ApiResponse;

    protected RefereeEvaluationService $refereeEvaluationService;

    protected RefereeEvaluationExportService $exportService;

    public function __construct(
        RefereeEvaluationService $refereeEvaluationService,
        RefereeEvaluationExportService $exportService
    ) {
        $this->refereeEvaluationService = $refereeEvaluationService;
        $this->exportService = $exportService;
    }

    /**
     * Create a new referee evaluation.
     */
    public function store(RefereeEvaluationRequest $request): JsonResponse
    {
        $user = auth('api')->user();

        if (! $user) {
            return $this->error([], 'Unauthenticated.', 401);
        }

        $result = $this->refereeEvaluationService->store($user, $request->validated());

        if (! $result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Update an existing referee evaluation.
     *
     * @param  int|string  $id
     */
    public function update(UpdateRefereeEvaluationRequest $request, $id): JsonResponse
    {
        $user = auth('api')->user();

        if (! $user) {
            return $this->error([], 'Unauthenticated.', 401);
        }

        $result = $this->refereeEvaluationService->update($user, $id, $request->validated());

        if (! $result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Alias for store to support 'create' action.
     */
    public function create(RefereeEvaluationRequest $request): JsonResponse
    {
        return $this->store($request);
    }

    /**
     * Backward-compatible alias for store.
     */
    public function storeOrUpdate(RefereeEvaluationRequest $request): JsonResponse
    {
        return $this->store($request);
    }

    /**
     * Get evaluations by camp.
     *
     * @param  int|string  $campId
     */
    public function getEvaluationsByCamp(Request $request, $campId): JsonResponse
    {
        $user = auth('api')->user();

        if (! $user) {
            return $this->error([], 'Unauthenticated.', 401);
        }

        $result = $this->refereeEvaluationService->getEvaluationsByCamp($user, $campId);

        if (! $result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Export referee evaluations by camp as CSV or Excel (Directors & Evaluators).
     *
     * @param  int|string  $campId
     * @return StreamedResponse|JsonResponse
     */
    public function exportEvaluationsByCamp(Request $request, $campId)
    {
        $user = auth('api')->user();

        if (! $user) {
            return $this->error([], 'Unauthenticated.', 401);
        }

        $format = (string) $request->query('format', $request->query('type', 'xlsx'));
        $result = $this->exportService->exportEvaluationsByCamp($user, $campId, $format);

        if (is_array($result) && ! ($result['success'] ?? true)) {
            return $this->error($result['data'] ?? [], $result['message'] ?? 'Failed to export.', $result['code'] ?? 400);
        }

        return $result;
    }

    /**
     * Get evaluations created by the authenticated evaluator.
     *
     * @param  int|string  $campId
     */
    public function getMyEvaluations(Request $request, $campId): JsonResponse
    {
        $user = auth('api')->user();

        if (! $user) {
            return $this->error([], 'Unauthenticated.', 401);
        }

        $perPage = (int) $request->get('per_page', 15);
        $result = $this->refereeEvaluationService->getMyEvaluations($user, $campId, $perPage);

        if (! $result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get a single evaluation by ID.
     *
     * @param  int|string  $id
     */
    public function show($id): JsonResponse
    {
        $user = auth('api')->user();

        if (! $user) {
            return $this->error([], 'Unauthenticated.', 401);
        }

        $result = $this->refereeEvaluationService->show($user, $id);

        if (! $result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Delete an evaluation.
     *
     * @param  int|string  $id
     */
    public function destroy($id): JsonResponse
    {
        $user = auth('api')->user();

        if (! $user) {
            return $this->error([], 'Unauthenticated.', 401);
        }

        $result = $this->refereeEvaluationService->destroy($user, $id);

        if (! $result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get referee statistics.
     *
     * @param  int|string  $refereeId
     */
    public function getRefereeStats($refereeId): JsonResponse
    {
        $user = auth('api')->user();

        if (! $user) {
            return $this->error([], 'Unauthenticated.', 401);
        }

        $result = $this->refereeEvaluationService->getRefereeStats($user, $refereeId);

        if (! $result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get all checked-in referees for a camp (paginated).
     *
     * @param  int|string  $campId
     */
    public function getAllRegisteredInReferees(Request $request, $campId): JsonResponse
    {
        $user = auth('api')->user();

        if (! $user) {
            return $this->error([], 'Unauthenticated.', 401);
        }

        $perPage = (int) $request->get('per_page', 15);
        $result = $this->refereeEvaluationService->getAllRegisteredInReferees($user, $campId, $perPage);

        if (! $result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get all referees for a camp (unpaginated).
     *
     * @param  int|string  $campId
     */
    public function getAllReferees($campId): JsonResponse
    {
        $user = auth('api')->user();

        if (! $user) {
            return $this->error([], 'Unauthenticated.', 401);
        }

        $result = $this->refereeEvaluationService->getAllReferees($user, $campId);

        if (! $result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get evaluation history for a specific referee in a camp.
     *
     * @param  int|string  $campId
     * @param  int|string  $refereeId
     */
    public function getRefereeEvaluationHistory(Request $request, $campId, $refereeId): JsonResponse
    {
        $user = auth('api')->user();

        if (! $user) {
            return $this->error([], 'Unauthenticated.', 401);
        }

        $status = $request->query('status');
        $perPage = (int) $request->get('per_page', 15);
        $result = $this->refereeEvaluationService->getRefereeEvaluationHistory($user, $campId, $refereeId, $status, $perPage);

        if (! $result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get sports types and recommended evaluation levels.
     */
    public function getSportTypes(Request $request): JsonResponse
    {
        $result = $this->refereeEvaluationService->getSportTypes(
            $request->name,
            $request->camp_id,
            $request->sports_type_id,
            $request->query('format')
        );

        return $this->success($result['message'], $result['data'], $result['code']);
    }
}
