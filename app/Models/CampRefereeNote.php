<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Director\Models\Camp;

class CampRefereeNote extends Model
{
    protected $table = 'camp_referee_notes';

    protected $fillable = [
        'camp_id',
        'referee_id',
        'note',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the camp that owns the referee note
     */
    public function camp(): BelongsTo
    {
        return $this->belongsTo(Camp::class);
    }

    /**
     * Get the referee that owns the note
     */
    public function referee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referee_id');
    }

    /**
     * Scope to get note for a specific camp and referee
     */
    public function scopeForCampAndReferee($query, $campId, $refereeId)
    {
        return $query->where('camp_id', $campId)
            ->where('referee_id', $refereeId);
    }
}
