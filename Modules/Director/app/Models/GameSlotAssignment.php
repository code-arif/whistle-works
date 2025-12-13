<?php

namespace Modules\Director\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Director\Database\Factories\GameSlotAssignmentFactory;

class GameSlotAssignment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'game_slot_id',
        'assignable_id',
        'assignable_type',
        'assignment_type',
        'position',
        'is_auto_assigned',
        'assigned_at',
    ];

    // Relation: Assignment belongs to a GameSlot
    public function gameSlot(): BelongsTo
    {
        return $this->belongsTo(GameSlot::class, 'game_slot_id');
    }

    // Optional: Jodi directly schedule access lagbe (nested relation er jonno helpful)
    public function schedule()
    {
        return $this->gameSlot->schedule(); // or direct belongsTo if needed
    }

    // Convenient relation for individual referee (User)
    public function referee(): MorphTo
    {
        return $this->morphTo('assignable')->where('assignable_type', User::class ?? User::class);
    }

    // Polymorphic assignable (crew or user)
    public function assignable()
    {
        return $this->morphTo();
    }
}
