<?php

namespace App\Models;

use Modules\Director\Models\Camp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampPaymentAttempt extends Model
{
    protected $fillable = [
        'camp_id',
        'referee_id',
        'stripe_session_id',
        'amount',
        'status',
        'expires_at',
        'completed_at',
        'coupon_id',
        'discount_amount',
        'admin_fee',
        'director_amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'admin_fee' => 'decimal:2',
        'director_amount' => 'decimal:2',
        'expires_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function camp(): BelongsTo
    {
        return $this->belongsTo(Camp::class);
    }

    public function referee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referee_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending' && !$this->isExpired();
    }
}
