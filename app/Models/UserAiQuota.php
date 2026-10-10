<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAiQuota extends Model
{
    use HasFactory;

    protected $table = 'user_ai_quotas';

    protected $fillable = [
        'user_id',
        'plan_tier',
        'monthly_query_limit',
        'queries_used_this_month',
        'tokens_used_this_month',
        'is_blocked',
        'block_reason',
        'quota_resets_at',
    ];

    protected $casts = [
        'is_blocked' => 'boolean',
        'quota_resets_at' => 'datetime',
        'monthly_query_limit' => 'integer',
        'queries_used_this_month' => 'integer',
        'tokens_used_this_month' => 'integer',
    ];

    /**
     * Get the user that owns this quota.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check and reset monthly quota if billing period / month has lapsed.
     */
    public function checkAndResetMonthlyQuota(): void
    {
        $now = Carbon::now();
        if (!$this->quota_resets_at || $now->greaterThanOrEqualTo($this->quota_resets_at)) {
            $this->queries_used_this_month = 0;
            $this->tokens_used_this_month = 0;
            $this->quota_resets_at = $now->copy()->addMonth()->startOfDay();
            $this->save();
        }
    }

    /**
     * Check if the user is allowed to perform AI queries.
     */
    public function canPerformQuery(): bool
    {
        if ($this->is_blocked) {
            return false;
        }

        $this->checkAndResetMonthlyQuota();

        return $this->queries_used_this_month < $this->monthly_query_limit;
    }

    /**
     * Increment usage counter after query completion.
     */
    public function recordUsage(int $tokens = 0): void
    {
        $this->checkAndResetMonthlyQuota();
        $this->increment('queries_used_this_month', 1);
        if ($tokens > 0) {
            $this->increment('tokens_used_this_month', $tokens);
        }
    }

    /**
     * Get remaining queries attribute.
     */
    public function getRemainingQueriesAttribute(): int
    {
        $remaining = $this->monthly_query_limit - $this->queries_used_this_month;
        return max(0, $remaining);
    }
}
