<?php

namespace Modules\Director\app\Http\Controllers\Api\Ai;

use App\Http\Controllers\Controller;
use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Director\app\Http\Requests\Ai\AiChatRequest;
use Modules\Director\app\Services\Ai\AiEngineService;

class AiChatController extends Controller
{
    use ApiResponse;

    protected AiEngineService $aiEngineService;

    public function __construct(AiEngineService $aiEngineService)
    {
        $this->aiEngineService = $aiEngineService;
    }

    /**
     * Process an AI Chat query with multi-turn context and function calling tools.
     * POST /api/v1/director/ai/chat
     */
    public function chat(AiChatRequest $request): JsonResponse
    {
        $user = auth('api')->user() ?? auth()->user();
        if (!$user) {
            return $this->error([], 'Unauthenticated.', 401);
        }

        try {
            $response = $this->aiEngineService->processUserMessage(
                $user,
                $request->input('message'),
                $request->input('session_uuid')
            );

            return $this->success('AI response generated successfully.', $response, 200);
        } catch (\Throwable $e) {
            return $this->error([
                'exception' => config('app.debug') ? $e->getMessage() : null,
            ], 'Failed to process AI query: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Start a fresh conversation session with a new UUID.
     * POST /api/v1/director/ai/session/reset
     */
    public function resetSession(Request $request): JsonResponse
    {
        $user = auth('api')->user() ?? auth()->user();
        if (!$user) {
            return $this->error([], 'Unauthenticated.', 401);
        }

        $session = AiChatSession::create([
            'user_id' => $user->id,
            'title' => 'New Conversation',
        ]);

        return $this->success('New conversation session created successfully.', [
            'session_uuid' => $session->session_uuid,
            'session_title' => $session->title,
            'created_at' => $session->created_at->toIso8601String(),
        ], 201);
    }

    /**
     * Get current user AI quota, usage metrics, and subscription tier.
     * GET /api/v1/director/ai/quota
     */
    public function getQuota(Request $request): JsonResponse
    {
        $user = auth('api')->user() ?? auth()->user();
        if (!$user) {
            return $this->error([], 'Unauthenticated.', 401);
        }

        $quota = $user->getOrCreateAiQuota();

        return $this->success('AI Quota details retrieved.', [
            'plan_tier' => $quota->plan_tier,
            'monthly_query_limit' => $quota->monthly_query_limit,
            'queries_used_this_month' => $quota->queries_used_this_month,
            'remaining_queries' => $quota->remaining_queries,
            'tokens_used_this_month' => $quota->tokens_used_this_month,
            'quota_resets_at' => $quota->quota_resets_at ? $quota->quota_resets_at->toIso8601String() : null,
            'is_blocked' => $quota->is_blocked,
        ], 200);
    }

    /**
     * Retrieve chronological message history for a specific chat session.
     * GET /api/v1/director/ai/session/{session_uuid}/history
     */
    public function getSessionHistory(Request $request, string $sessionUuid): JsonResponse
    {
        $user = auth('api')->user() ?? auth()->user();
        if (!$user) {
            return $this->error([], 'Unauthenticated.', 401);
        }

        $session = AiChatSession::where('session_uuid', $sessionUuid)
            ->where('user_id', $user->id)
            ->first();

        if (!$session) {
            return $this->error([], 'Chat session not found.', 404);
        }

        $messages = AiChatMessage::where('session_id', $session->id)
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'role' => $msg->role,
                    'content' => $msg->content,
                    'widget_type' => $msg->widget_type,
                    'widget_payload' => $msg->widget_payload,
                    'tokens_used' => $msg->tokens_used,
                    'tools_called' => $msg->tools_called,
                    'created_at' => $msg->created_at->toIso8601String(),
                ];
            });

        return $this->success('Session history retrieved.', [
            'session_uuid' => $session->session_uuid,
            'title' => $session->title,
            'total_tokens' => $session->total_tokens,
            'last_interaction_at' => $session->last_interaction_at ? $session->last_interaction_at->toIso8601String() : null,
            'messages' => $messages,
        ], 200);
    }
}
