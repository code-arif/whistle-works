<?php

namespace Modules\Director\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
// use Modules\Director\Database\Factories\CampRefereeCheckinFactory;

class CampRefereeCheckin extends Model
{
    protected $guarded = [];

    protected $casts = [
        'checked_in_at' => 'datetime',
    ];

    public function camp(): BelongsTo
    {
        return $this->belongsTo(Camp::class);
    }

    public function referee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referee_id');
    }
}
