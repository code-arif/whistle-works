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

    protected $casts = [
        'is_auto_assigned' => 'boolean',
        'assigned_at' => 'datetime',
    ];

    // Relation: Assignment belongs to a GameSlot
    public function gameSlot(): BelongsTo
    {
        return $this->belongsTo(GameSlot::class, 'game_slot_id');
    }

    // relation with schedule table.
    public function schedule()
    {
        return $this->hasOneThrough(
            Schedule::class,
            GameSlot::class,
            'id',
            'id',
            'game_slot_id',
            'schedule_id'
        );
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

    /**
     * Check if a referee/crew has time conflict with a given slot
     *
     * @param int $assignableId
     * @param string $assignableType
     * @param GameSlot $targetSlot
     * @return bool
     */
    public static function hasTimeConflict($assignableId, $assignableType, GameSlot $targetSlot): bool
    {
        return self::where('assignable_id', $assignableId)
            ->where('assignable_type', $assignableType)
            ->whereHas('gameSlot', function ($query) use ($targetSlot) {
                $query->where('game_date', $targetSlot->game_date)
                    ->where('schedule_id', $targetSlot->schedule_id)
                    ->where('id', '!=', $targetSlot->id) // Exclude the target slot itself
                    ->where(function ($q) use ($targetSlot) {
                        // Check for time overlap
                        $q->where(function ($subQ) use ($targetSlot) {
                            // Case 1: New slot starts during existing slot
                            $subQ->where('start_time', '<=', $targetSlot->start_time)
                                ->where('end_time', '>', $targetSlot->start_time);
                        })
                            ->orWhere(function ($subQ) use ($targetSlot) {
                                // Case 2: New slot ends during existing slot
                                $subQ->where('start_time', '<', $targetSlot->end_time)
                                    ->where('end_time', '>=', $targetSlot->end_time);
                            })
                            ->orWhere(function ($subQ) use ($targetSlot) {
                                // Case 3: New slot completely contains existing slot
                                $subQ->where('start_time', '>=', $targetSlot->start_time)
                                    ->where('end_time', '<=', $targetSlot->end_time);
                            });
                    });
            })
            ->exists();
    }

    /**
     * Get all conflicting slots for a referee/crew
     */
    public static function getConflictingSlots($assignableId, $assignableType, GameSlot $targetSlot)
    {
        return self::where('assignable_id', $assignableId)
            ->where('assignable_type', $assignableType)
            ->with(['gameSlot.location'])
            ->whereHas('gameSlot', function ($query) use ($targetSlot) {
                $query->where('game_date', $targetSlot->game_date)
                    ->where('schedule_id', $targetSlot->schedule_id)
                    ->where('id', '!=', $targetSlot->id)
                    ->where(function ($q) use ($targetSlot) {
                        $q->where(function ($subQ) use ($targetSlot) {
                            $subQ->where('start_time', '<=', $targetSlot->start_time)
                                ->where('end_time', '>', $targetSlot->start_time);
                        })
                            ->orWhere(function ($subQ) use ($targetSlot) {
                                $subQ->where('start_time', '<', $targetSlot->end_time)
                                    ->where('end_time', '>=', $targetSlot->end_time);
                            })
                            ->orWhere(function ($subQ) use ($targetSlot) {
                                $subQ->where('start_time', '>=', $targetSlot->start_time)
                                    ->where('end_time', '<=', $targetSlot->end_time);
                            });
                    });
            })
            ->get();
    }
}
