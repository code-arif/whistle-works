<?php

namespace Modules\Director\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Director\Database\Factories\GameSlotFactory;

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

    public function crew(): BelongsTo
    {
        return $this->belongsTo(Crew::class);
    }

    /**
     * Check if assigned by crew
     */
    public function isCrewAssignment(): bool
    {
        return $this->assignment_mode === 'crew' && $this->crew_id !== null;
    }

    /**
     * Check if assigned individually
     */
    public function isIndividualAssignment(): bool
    {
        return $this->assignment_mode === 'individual';
    }
}
