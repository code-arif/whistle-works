<?php

namespace App\Models;

use Modules\Director\Models\Camp;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'discount_value',
        'max_uses',
        'used_count',
        'camp_id',
        'director_id',
        'expires_at',
        'status',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'max_uses' => 'integer',
        'used_count' => 'integer',
        'expires_at' => 'datetime',
    ];

    /**
     * The camp this coupon is scoped to (null = all camps).
     */
    public function camp(): BelongsTo
    {
        return $this->belongsTo(Camp::class);
    }

    /**
     * The director this coupon is scoped to (null = all directors).
     */
    public function director(): BelongsTo
    {
        return $this->belongsTo(User::class, 'director_id');
    }

    /**
     * The referees this coupon is scoped to (empty = all referees).
     */
    public function referees()
    {
        return $this->belongsToMany(User::class, 'coupon_referees', 'coupon_id', 'referee_id')
            ->withTimestamps();
    }

    /**
     * Payments where this coupon was applied.
     */
    public function campPayments(): HasMany
    {
        return $this->hasMany(CampPayment::class);
    }

    /**
     * Track per-referee usage records.
     */
    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    // ─────────────────────────────────────────────────────────────
    // Validation Methods
    // ─────────────────────────────────────────────────────────────

    /**
     * Check if the coupon is globally valid (status, expiry, max_uses).
     */
    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    /**
     * Check if the coupon is valid for a specific camp.
     */
    public function isValidForCamp($campId): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        // If scoped to a specific camp, it must match
        if ($this->camp_id !== null && (int)$this->camp_id !== (int)$campId) {
            return false;
        }

        return true;
    }

    /**
     * Check if the coupon is valid for a specific referee.
     * If no referees are assigned (empty pivot), it's valid for all.
     * If referees are assigned, the referee must be in the list.
     */
    public function isValidForReferee($refereeId): bool
    {
        // If referees are already loaded, check the collection
        if ($this->relationLoaded('referees')) {
            // No referees assigned = valid for all
            if ($this->referees->isEmpty()) {
                return true;
            }
            return $this->referees->contains('id', $refereeId);
        }

        // Not loaded — query through the pivot
        // If no referees are assigned to this coupon at all, it's valid for everyone
        if (!$this->referees()->exists()) {
            return true;
        }

        // Referees are assigned — check if this specific referee is in the pivot
        return $this->referees()->where('id', $refereeId)->exists();
    }

    /**
     * Check if the referee has already used this coupon (one-time per referee).
     */
    public function isAlreadyUsedByReferee($refereeId): bool
    {
        return $this->usages()
            ->where('referee_id', $refereeId)
            ->exists();
    }

    /**
     * Full validation: check if coupon can be used by a referee for a specific camp.
     */
    public function isValidFor($refereeId, $campId): bool
    {
        if (!$this->isValidForCamp($campId)) {
            return false;
        }

        if (!$this->isValidForReferee($refereeId)) {
            return false;
        }

        // One-time per referee check
        if ($this->isAlreadyUsedByReferee($refereeId)) {
            return false;
        }

        return true;
    }

    /**
     * Record that this coupon was used by a referee for a camp.
     */
    public function recordUsage($refereeId, $campId, $paymentId = null): CouponUsage
    {
        $usage = $this->usages()->create([
            'referee_id' => $refereeId,
            'camp_id'    => $campId,
            'payment_id' => $paymentId,
            'used_at'    => now(),
        ]);

        $this->increment('used_count');

        return $usage;
    }
}
