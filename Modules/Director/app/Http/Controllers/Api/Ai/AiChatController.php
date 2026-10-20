<?php

namespace Modules\Director\app\Http\Controllers\Api\Ai;

use App\Http\Controllers\Controller;
use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Director\app\Http\Requests\Ai\AiChatRequest;
use Modules\Director\app\Services\Ai\AiEngineService;
use Throwable;

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
        } catch (Throwable $e) {
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

    /**
     * Retrieve paginated AI chat sessions for the authenticated user.
     * GET /api/v1/director/ai/sessions
     * GET /api/v1/director/ai/session/list
     */
    public function getSessions(Request $request): JsonResponse
    {
        $user = auth('api')->user() ?? auth()->user();
        if (!$user) {
            return $this->error([], 'Unauthenticated.', 401);
        }

        try {
            $perPage = (int) $request->input('per_page', 15);
            if ($perPage < 1) {
                $perPage = 15;
            } elseif ($perPage > 100) {
                $perPage = 100;
            }

            $query = AiChatSession::where('user_id', $user->id)
                ->with(['latestMessage'])
                ->withCount('messages');

            // Optional search by title
            if ($request->filled('search')) {
                $searchTerm = trim((string) $request->input('search'));
                $query->where('title', 'like', "%{$searchTerm}%");
            }

            // Order by most recent interaction first (ChatGPT / Gemini behavior)
            $sessions = $query->orderBy('last_interaction_at', 'desc')
                ->orderBy('id', 'desc')
                ->paginate($perPage);

            $formattedSessions = $sessions->getCollection()->map(function ($session) {
                return [
                    'id' => (int) $session->id,
                    'session_uuid' => (string) $session->session_uuid,
                    'title' => (string) ($session->title ?: 'New Conversation'),
                    'total_tokens' => (int) $session->total_tokens,
                    'messages_count' => (int) ($session->messages_count ?? 0),
                    'last_message' => $session->latestMessage ? [
                        'id' => (int) $session->latestMessage->id,
                        'role' => (string) $session->latestMessage->role,
                        'content' => Str::limit($session->latestMessage->content, 120),
                        'created_at' => $session->latestMessage->created_at ? $session->latestMessage->created_at->toIso8601String() : null,
                    ] : null,
                    'last_interaction_at' => $session->last_interaction_at ? $session->last_interaction_at->toIso8601String() : null,
                    'created_at' => $session->created_at ? $session->created_at->toIso8601String() : null,
                    'updated_at' => $session->updated_at ? $session->updated_at->toIso8601String() : null,
                ];
            })->values()->all();

            $pagination = [
                'total' => $sessions->total(),
                'per_page' => $sessions->perPage(),
                'current_page' => $sessions->currentPage(),
                'last_page' => $sessions->lastPage(),
            ];

            return $this->success('AI chat sessions retrieved successfully.', [
                'sessions' => $formattedSessions,
                'pagination' => $pagination,
            ], 200);
        } catch (Throwable $e) {
            return $this->error([
                'exception' => config('app.debug') ? $e->getMessage() : null,
            ], 'Failed to retrieve AI sessions: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Rename or update an AI chat session title.
     * PATCH /api/v1/director/ai/session/{session_uuid}
     */
    public function updateSession(Request $request, string $sessionUuid): JsonResponse
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

        $title = trim((string) $request->input('title'));
        if (empty($title)) {
            return $this->error([], 'Session title cannot be empty.', 422);
        }

        $session->update([
            'title' => Str::limit($title, 255),
        ]);

        return $this->success('Chat session updated successfully.', [
            'session_uuid' => $session->session_uuid,
            'title' => $session->title,
        ], 200);
    }

    /**
     * Delete an AI chat session and its messages.
     * DELETE /api/v1/director/ai/session/{session_uuid}
     */
    public function deleteSession(Request $request, string $sessionUuid): JsonResponse
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

        $session->delete();

        return $this->success('Chat session deleted successfully.', [
            'session_uuid' => $sessionUuid,
        ], 200);
    }

    /**
     * Download a generated AI export file directly without static server permission issues.
     * GET /api/v1/director/ai/export/download/{filename}
     */
    public function downloadExport(string $filename)
    {
        $cleanFilename = basename($filename);
        $fullPath = storage_path('app/public/ai_exports/' . $cleanFilename);

        if (!file_exists($fullPath)) {
            return response()->json([
                'success' => false,
                'message' => 'The requested export file was not found or has expired.',
            ], 404);
        }

        return response()->download($fullPath, $cleanFilename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }
}
