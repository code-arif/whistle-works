<?php

namespace Modules\Director\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    protected $guarded = [];

    public function camp()
    {
        return $this->belongsTo(Camp::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(ScheduleLocation::class);
    }

    public function timeRanges(): HasMany
    {
        return $this->hasMany(ScheduleTimeRange::class);
    }

    public function gameSlots(): HasMany
    {
        return $this->hasMany(GameSlot::class);
    }
}

class ScheduleLocation extends Model
{
    protected $guarded = [];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function gameSlots(): HasMany
    {
        return $this->hasMany(GameSlot::class);
    }
}

class ScheduleTimeRange extends Model
{
    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }
}

class GameSlot extends Model
{
    protected $guarded = [];

    protected $casts = [
        'game_date' => 'date',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(ScheduleLocation::class, 'schedule_location_id');
    }

    public function refereeAssignments(): HasMany
    {
        return $this->hasMany(RefereeAssignment::class);
    }
}

class RefereeAssignment extends Model
{
    protected $guarded = [];

    public function gameSlot(): BelongsTo
    {
        return $this->belongsTo(GameSlot::class);
    }

    public function referee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referee_id');
    }
}

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
