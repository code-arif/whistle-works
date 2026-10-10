<?php

namespace Modules\Director\app\Http\Controllers\Api\Ai;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Director\app\Services\Ai\AiDirectoryService;
use Throwable;

class AiDirectoryController extends Controller
{
    use ApiResponse;

    protected AiDirectoryService $directoryService;

    public function __construct(AiDirectoryService $directoryService)
    {
        $this->directoryService = $directoryService;
    }

    /**
     * Retrieve paginated camps directory for AI Coach mention tagging.
     * GET /api/v1/director/ai/camps
     */
    public function getCamps(Request $request): JsonResponse
    {
        $user = auth('api')->user() ?? auth()->user();
        if (!$user) {
            return $this->error([], 'Unauthenticated.', 401);
        }

        try {
            $result = $this->directoryService->getCamps($user, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'AI camps retrieved successfully.',
                'data' => $result['data'],
                'pagination' => $result['pagination'],
                'code' => 200,
            ], 200);
        } catch (Throwable $e) {
            return $this->error([
                'exception' => config('app.debug') ? $e->getMessage() : null,
            ], 'Failed to retrieve AI camps: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Retrieve paginated participants (referees/evaluators) for AI Coach mention tagging.
     * Supports ?camp_id=X, ?role=referee|evaluator, ?search=..., ?page=X, ?per_page=X
     * GET /api/v1/director/ai/participants
     */
    public function getParticipants(Request $request): JsonResponse
    {
        $user = auth('api')->user() ?? auth()->user();
        if (!$user) {
            return $this->error([], 'Unauthenticated.', 401);
        }

        try {
            $result = $this->directoryService->getParticipants($user, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'AI participants retrieved successfully.',
                'data' => $result['data'],
                'pagination' => $result['pagination'],
                'code' => 200,
            ], 200);
        } catch (Throwable $e) {
            return $this->error([
                'exception' => config('app.debug') ? $e->getMessage() : null,
            ], 'Failed to retrieve AI participants: ' . $e->getMessage(), 500);
        }
    }
}
