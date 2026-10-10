<?php

namespace App\Http\Middleware;

use App\Helpers\Helper;
use App\Models\AiSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAiUsageQuota
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Check if AI Coach is globally enabled
        $isEnabled = AiSetting::getValue('enable_ai_coach', '1');
        if ($isEnabled === '0' || $isEnabled === false) {
            return Helper::jsonErrorResponse('AI Coach and Analytics Engine is currently disabled by administrator.', 503);
        }

        // 2. Resolve authenticated user
        $user = auth('api')->user() ?? auth()->user();
        if (!$user) {
            return Helper::jsonErrorResponse('Unauthenticated.', 401);
        }

        // 3. Resolve or initialize Quota
        $quota = $user->getOrCreateAiQuota();

        // 4. Check if blocked
        if ($quota->is_blocked) {
            $reason = $quota->block_reason ?: 'Access restricted by administrator.';
            return Helper::jsonErrorResponse("Your AI access has been restricted: {$reason}", 403);
        }

        // 5. Check if query limit reached
        if (!$quota->canPerformQuery()) {
            return Helper::jsonErrorResponse(
                "You have reached your monthly AI query limit ({$quota->monthly_query_limit} queries). Reset date: " .
                ($quota->quota_resets_at ? $quota->quota_resets_at->format('Y-m-d') : 'next billing cycle') .
                ". Please upgrade your subscription or contact support.",
                429,
                [
                    'monthly_limit' => $quota->monthly_query_limit,
                    'used_this_month' => $quota->queries_used_this_month,
                    'quota_resets_at' => $quota->quota_resets_at,
                    'plan_tier' => $quota->plan_tier,
                ]
            );
        }

        return $next($request);
    }
}
