<?php

namespace Modules\Director\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GameSlotAssignmentPosition extends Model
{
    use HasFactory;

    protected $fillable = [
        'camp_id',
        'game_slot_id',
        'game_slot_assignment_id',
        'position',
    ];

    public function camp(): BelongsTo
    {
        return $this->belongsTo(Camp::class, 'camp_id');
    }

    public function gameSlot(): BelongsTo
    {
        return $this->belongsTo(GameSlot::class, 'game_slot_id');
    }

    public function gameSlotAssignment(): BelongsTo
    {
        return $this->belongsTo(GameSlotAssignment::class, 'game_slot_assignment_id');
    }
}
