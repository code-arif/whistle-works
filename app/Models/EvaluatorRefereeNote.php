<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Director\Models\Camp;
use Modules\Director\Models\GameSlot;

class EvaluatorRefereeNote extends Model
{
    use HasFactory;

    protected $table = 'evaluator_referee_notes';

    protected $fillable = [
        'evaluator_id',
        'camp_id',
        'referee_id',
        'game_slot_id',
        'note',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The evaluator who created the note.
     */
    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    /**
     * The camp the note belongs to.
     */
    public function camp(): BelongsTo
    {
        return $this->belongsTo(Camp::class, 'camp_id');
    }

    /**
     * The referee being noted.
     */
    public function referee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referee_id');
    }

    /**
     * The specific game slot this note relates to.
     */
    public function gameSlot(): BelongsTo
    {
        return $this->belongsTo(GameSlot::class, 'game_slot_id');
    }

    /**
     * Scope to filter notes by evaluator.
     */
    public function scopeForEvaluator($query, $evaluatorId)
    {
        return $query->where('evaluator_id', $evaluatorId);
    }

    /**
     * Scope to filter notes by camp.
     */
    public function scopeForCamp($query, $campId)
    {
        return $query->where('camp_id', $campId);
    }

    /**
     * Scope to filter notes by referee.
     */
    public function scopeForReferee($query, $refereeId)
    {
        return $query->where('referee_id', $refereeId);
    }

    /**
     * Scope to filter notes by game slot.
     */
    public function scopeForGameSlot($query, $gameSlotId)
    {
        return $query->where('game_slot_id', $gameSlotId);
    }
}
