<?php

namespace App\Models;

use Modules\Director\Models\Camp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampPayment extends Model
{
    protected $fillable = [
        'camp_id',
        'referee_id',
        'payment_attempt_id',
        'stripe_payment_intent_id',
        'stripe_session_id',
        'amount',
        'currency',
        'status',
        'paid_at',
        'metadata',
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
        'paid_at' => 'datetime',
        'metadata' => 'array'
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

    public function paymentAttempt(): BelongsTo
    {
        return $this->belongsTo(CampPaymentAttempt::class, 'payment_attempt_id');
    }
}
