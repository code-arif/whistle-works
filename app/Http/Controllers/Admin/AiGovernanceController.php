<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Ai\UpdateAiSettingsRequest;
use App\Models\AiChatMessage;
use App\Models\User;
use App\Services\Admin\AiGovernanceService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AiGovernanceController extends Controller
{
    protected AiGovernanceService $service;

    public function __construct(AiGovernanceService $service)
    {
        $this->service = $service;
    }

    /**
     * Display the AI Coach Governance & Usage Dashboard in Admin V2.
     */
    public function index(Request $request): Response|JsonResponse
    {
        $metrics = $this->service->getOverviewMetrics();
        $settings = $this->service->getAiSettings();
        $filters = $request->only(['search', 'is_blocked', 'plan_tier', 'sort_by', 'sort_dir']);
        $quotas = $this->service->getUserQuotas($filters);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'metrics' => $metrics,
                    'settings' => $settings,
                    'quotas' => $quotas,
                ],
            ]);
        }

        return Inertia::render('AiGovernance/Index', [
            'metrics' => $metrics,
            'settings' => $settings,
            'quotas' => $quotas,
            'filters' => $filters,
        ]);
    }

    /**
     * Update AI provider API keys, model selections, and global default quotas.
     */
    public function updateSettings(UpdateAiSettingsRequest $request): RedirectResponse|JsonResponse
    {
        try {
            $this->service->updateAiSettings($request->validated());

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'AI Coach settings and API credentials updated successfully.',
                ]);
            }

            return redirect()->back()->with('t-success', 'AI Coach settings and API credentials updated successfully.');
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update AI settings: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('t-error', 'Failed to update AI settings: ' . $e->getMessage());
        }
    }

    /**
     * Update quota limits, reset counts, or change plan tier for a specific user.
     */
    public function updateUserQuota(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $request->validate([
            'monthly_query_limit' => ['nullable', 'integer', 'min:0', 'max:50000'],
            'plan_tier' => ['nullable', 'string', 'in:free,pro,enterprise'],
            'queries_used_this_month' => ['nullable', 'integer', 'min:0'],
            'reset_usage' => ['nullable', 'boolean'],
        ]);

        try {
            $quota = $this->service->updateUserQuota($id, $request->all());

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User AI quota updated successfully.',
                    'data' => $quota,
                ]);
            }

            return redirect()->back()->with('t-success', 'User AI quota updated successfully.');
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update user quota: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('t-error', 'Failed to update user quota: ' . $e->getMessage());
        }
    }

    /**
     * Block or unblock a specific user from accessing AI features.
     */
    public function toggleBlock(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $request->validate([
            'is_blocked' => ['required', 'boolean'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $quota = $this->service->toggleUserBlock(
                $id,
                $request->boolean('is_blocked'),
                $request->input('reason')
            );

            $msg = $quota->is_blocked ? 'User AI access blocked.' : 'User AI access restored.';

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'data' => $quota,
                ]);
            }

            return redirect()->back()->with('t-success', $msg);
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to change user block status: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('t-error', 'Failed to change user block status: ' . $e->getMessage());
        }
    }

    /**
     * Display dedicated AI Conversation History & Audit Trail for a specific user.
     */
    public function userHistory(Request $request, int $id): Response|JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            $quota = $user->getOrCreateAiQuota();
            $filters = $request->only(['search', 'role', 'per_page', 'date_from', 'date_to', 'sort_dir']);
            $perPage = (int) $request->input('per_page', 20);
            $history = $this->service->getUserAiHistory($id, $perPage, $filters);

            // User all-time AI statistics
            $totalQueriesAllTime = AiChatMessage::where('user_id', $id)->where('role', 'user')->count();
            $totalTokensAllTime = (int) AiChatMessage::where('user_id', $id)->sum('tokens_used');
            $totalToolsUsed = AiChatMessage::where('user_id', $id)->whereNotNull('tools_called')->count();

            $stats = [
                'total_queries_all_time' => $totalQueriesAllTime,
                'total_tokens_all_time' => $totalTokensAllTime,
                'total_tools_used' => $totalToolsUsed,
                'monthly_queries' => $quota->queries_used_this_month,
                'monthly_limit' => $quota->monthly_query_limit,
                'monthly_tokens' => $quota->tokens_used_this_month,
            ];

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $history,
                    'user' => $user,
                    'quota' => $quota,
                    'stats' => $stats,
                ]);
            }

            return Inertia::render('AiGovernance/History', [
                'targetUser' => $user,
                'quota' => $quota,
                'history' => $history,
                'stats' => $stats,
                'filters' => $filters,
            ]);
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch user AI history: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->route('admin.ai.index')->with('t-error', 'Failed to fetch user AI history: ' . $e->getMessage());
        }
    }

    /**
     * Get JSON endpoint for daily usage trend (queries & tokens) for a user.
     */
    public function userUsageTrend(Request $request, int $id): JsonResponse
    {
        try {
            $days = (int) $request->input('days', 14);
            $trend = $this->service->getUserUsageTrend($id, $days);

            return response()->json([
                'success' => true,
                'data' => $trend,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user usage trend: ' . $e->getMessage(),
            ], 500);
        }
    }
}
