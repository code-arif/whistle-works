<?php

namespace Modules\Director\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Crew extends Model
{
    protected $guarded = [];

    public function camp(): BelongsTo
    {
        return $this->belongsTo(Camp::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'crew_members', 'crew_id', 'referee_id')
            ->withTimestamps()
            ->withPivot('joined_at');
    }

    public function crewMembers(): HasMany
    {
        return $this->hasMany(CrewMember::class);
    }

    public function gameSlots(): HasMany
    {
        return $this->hasMany(GameSlot::class);
    }

    /**
     * Get member count
     */
    public function getMemberCountAttribute()
    {
        return $this->members()->count();
    }

    /**
     * Check if referee is already in this crew
     */
    public function hasMember($refereeId): bool
    {
        return $this->members()->where('users.id', $refereeId)->exists();
    }
}
