<?php

namespace App\Services\Admin;

use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Models\AiSetting;
use App\Models\User;
use App\Models\UserAiQuota;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AiGovernanceService
{
    /**
     * Fetch high-level AI usage metrics, chart data, and provider configurations.
     */
    public function getOverviewMetrics(): array
    {
        $totalMonthlyQueries = (int) UserAiQuota::sum('queries_used_this_month');
        $totalMonthlyTokens = (int) UserAiQuota::sum('tokens_used_this_month');
        $totalAiUsers = UserAiQuota::count();
        $blockedUsersCount = UserAiQuota::where('is_blocked', true)->count();
        $activeThisMonth = UserAiQuota::where('queries_used_this_month', '>', 0)->count();

        // 14-day chronological usage trend
        $startDate = Carbon::now()->subDays(13)->startOfDay();
        $dailyTrends = AiChatMessage::where('created_at', '>=', $startDate)
            ->where('role', 'assistant')
            ->select([
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as query_count'),
                DB::raw('SUM(tokens_used) as total_tokens'),
            ])
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->keyBy('date');

        $chartLabels = [];
        $queryCounts = [];
        $tokenCounts = [];

        for ($i = 13; $i >= 0; $i--) {
            $dateStr = Carbon::now()->subDays($i)->format('Y-m-d');
            $chartLabels[] = Carbon::now()->subDays($i)->format('M d');
            $record = $dailyTrends->get($dateStr);
            $queryCounts[] = $record ? (int) $record->query_count : 0;
            $tokenCounts[] = $record ? (int) $record->total_tokens : 0;
        }

        // Top 5 active AI users
        $topUsers = UserAiQuota::with('user')
            ->where('queries_used_this_month', '>', 0)
            ->orderBy('queries_used_this_month', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($quota) {
                return [
                    'user_id' => $quota->user_id,
                    'name' => $quota->user ? trim("{$quota->user->first_name} {$quota->user->last_name}") : 'Unknown',
                    'email' => $quota->user ? $quota->user->email : 'N/A',
                    'queries_used' => $quota->queries_used_this_month,
                    'tokens_used' => $quota->tokens_used_this_month,
                    'plan_tier' => $quota->plan_tier,
                    'is_blocked' => $quota->is_blocked,
                ];
            });

        return [
            'metrics' => [
                'total_queries_month' => $totalMonthlyQueries,
                'total_tokens_month' => $totalMonthlyTokens,
                'total_ai_users' => $totalAiUsers,
                'active_users_month' => $activeThisMonth,
                'blocked_users_count' => $blockedUsersCount,
                'estimated_cost_usd' => round(($totalMonthlyTokens / 1000000) * 5.00, 3), // Approx $5/1M tokens avg
            ],
            'chart_data' => [
                'labels' => $chartLabels,
                'queries' => $queryCounts,
                'tokens' => $tokenCounts,
            ],
            'top_users' => $topUsers,
        ];
    }

    /**
     * Get all AI settings with masked API keys.
     */
    public function getAiSettings(): array
    {
        $provider = AiSetting::getValue('ai_provider', 'openai');
        $openAiKey = AiSetting::getValue('openai_api_key', '');
        $geminiKey = AiSetting::getValue('gemini_api_key', '');

        return [
            'ai_provider' => $provider,
            'openai_api_key' => !empty($openAiKey) ? $this->maskSecret($openAiKey) : '',
            'has_openai_key' => !empty($openAiKey),
            'openai_model' => AiSetting::getValue('openai_model', 'gpt-4o'),
            'gemini_api_key' => !empty($geminiKey) ? $this->maskSecret($geminiKey) : '',
            'has_gemini_key' => !empty($geminiKey),
            'gemini_model' => AiSetting::getValue('gemini_model', 'gemini-1.5-pro'),
            'default_monthly_quota' => (int) AiSetting::getValue('default_monthly_quota', 50),
            'enable_ai_coach' => AiSetting::getValue('enable_ai_coach', '1') === '1',
            'ai_system_prompt_override' => AiSetting::getValue('ai_system_prompt_override', ''),
        ];
    }

    /**
     * Update AI configuration settings.
     */
    public function updateAiSettings(array $data): void
    {
        if (isset($data['ai_provider'])) {
            AiSetting::setValue('ai_provider', $data['ai_provider'], false);
        }

        if (isset($data['openai_model'])) {
            AiSetting::setValue('openai_model', $data['openai_model'], false);
        }

        if (isset($data['gemini_model'])) {
            AiSetting::setValue('gemini_model', $data['gemini_model'], false);
        }

        if (isset($data['default_monthly_quota'])) {
            AiSetting::setValue('default_monthly_quota', (string) $data['default_monthly_quota'], false);
        }

        if (isset($data['enable_ai_coach'])) {
            AiSetting::setValue('enable_ai_coach', $data['enable_ai_coach'] ? '1' : '0', false);
        }

        if (isset($data['ai_system_prompt_override'])) {
            AiSetting::setValue('ai_system_prompt_override', $data['ai_system_prompt_override'], false);
        }

        // Only update API keys if a new unmasked key was provided
        if (!empty($data['openai_api_key']) && !str_starts_with($data['openai_api_key'], '***')) {
            AiSetting::setValue('openai_api_key', $data['openai_api_key'], true);
        }

        if (!empty($data['gemini_api_key']) && !str_starts_with($data['gemini_api_key'], '***')) {
            AiSetting::setValue('gemini_api_key', $data['gemini_api_key'], true);
        }
    }

    /**
     * Fetch paginated list of user AI quotas with filtering.
     */
    public function getUserQuotas(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = UserAiQuota::with('user');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('username', 'LIKE', "%{$search}%");
            });
        }

        if (isset($filters['is_blocked']) && $filters['is_blocked'] !== '') {
            $query->where('is_blocked', (bool) $filters['is_blocked']);
        }

        if (!empty($filters['plan_tier'])) {
            $query->where('plan_tier', $filters['plan_tier']);
        }

        $sortBy = $filters['sort_by'] ?? 'queries_used_this_month';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        return $query->orderBy($sortBy, $sortDir)->paginate($perPage);
    }

    /**
     * Update custom quota and plan tier for a specific user.
     */
    public function updateUserQuota(int $userId, array $data): UserAiQuota
    {
        $user = User::findOrFail($userId);
        $quota = $user->getOrCreateAiQuota();

        if (isset($data['monthly_query_limit'])) {
            $quota->monthly_query_limit = (int) $data['monthly_query_limit'];
        }

        if (isset($data['plan_tier'])) {
            $quota->plan_tier = $data['plan_tier'];
        }

        if (isset($data['queries_used_this_month'])) {
            $quota->queries_used_this_month = (int) $data['queries_used_this_month'];
        }

        if (isset($data['reset_usage']) && $data['reset_usage']) {
            $quota->queries_used_this_month = 0;
            $quota->tokens_used_this_month = 0;
            $quota->quota_resets_at = Carbon::now()->addMonth()->startOfDay();
        }

        $quota->save();

        return $quota;
    }

    /**
     * Block or unblock a user from AI access with a reason.
     */
    public function toggleUserBlock(int $userId, bool $isBlocked, ?string $reason = null): UserAiQuota
    {
        $user = User::findOrFail($userId);
        $quota = $user->getOrCreateAiQuota();

        $quota->is_blocked = $isBlocked;
        $quota->block_reason = $isBlocked ? ($reason ?: 'Access restricted by administrator.') : null;
        $quota->save();

        return $quota;
    }

    /**
     * Get paginated audit logs / conversation history for a user.
     */
    public function getUserAiHistory(int $userId, int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        $query = AiChatMessage::with('session')
            ->where('user_id', $userId);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('content', 'LIKE', "%{$search}%");
        }

        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $sortDir = $filters['sort_dir'] ?? 'desc';

        return $query->orderBy('id', $sortDir)->paginate($perPage)->withQueryString();
    }

    /**
     * Fetch daily usage trend (queries & tokens) for a specific user over a timeframe.
     */
    public function getUserUsageTrend(int $userId, int $days = 14): array
    {
        $startDate = Carbon::now()->subDays($days - 1)->startOfDay();
        $dailyTrends = AiChatMessage::where('user_id', $userId)
            ->where('created_at', '>=', $startDate)
            ->where('role', 'assistant')
            ->select([
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as query_count'),
                DB::raw('SUM(tokens_used) as total_tokens'),
            ])
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->keyBy('date');

        $labels = [];
        $queries = [];
        $tokens = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $dateStr = Carbon::now()->subDays($i)->format('Y-m-d');
            $labels[] = Carbon::now()->subDays($i)->format('M d');
            $record = $dailyTrends->get($dateStr);
            $queries[] = $record ? (int) $record->query_count : 0;
            $tokens[] = $record ? (int) $record->total_tokens : 0;
        }

        $user = User::find($userId);
        $quota = $user ? $user->getOrCreateAiQuota() : null;

        return [
            'labels' => $labels,
            'queries' => $queries,
            'tokens' => $tokens,
            'total_queries_period' => array_sum($queries),
            'total_tokens_period' => array_sum($tokens),
            'monthly_queries' => $quota ? $quota->queries_used_this_month : 0,
            'monthly_limit' => $quota ? $quota->monthly_query_limit : 50,
            'monthly_tokens' => $quota ? $quota->tokens_used_this_month : 0,
            'user' => $user ? [
                'id' => $user->id,
                'name' => trim("{$user->first_name} {$user->last_name}"),
                'email' => $user->email,
                'plan_tier' => $quota ? $quota->plan_tier : 'free',
            ] : null,
        ];
    }

    /**
     * Mask sensitive API keys for safe UI presentation.
     */
    protected function maskSecret(string $secret): string
    {
        $length = strlen($secret);
        if ($length <= 8) {
            return '********';
        }
        return substr($secret, 0, 4) . '***' . substr($secret, -4);
    }
}
