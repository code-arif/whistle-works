<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SportsTypeLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'sports_type_id',
        'name',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * Parent sports type relation.
     */
    public function sportsType(): BelongsTo
    {
        return $this->belongsTo(SportsType::class, 'sports_type_id');
    }

    /**
     * Scope for active levels.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope ordered by hierarchy.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }
}
