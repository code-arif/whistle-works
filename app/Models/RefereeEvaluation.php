<?php

namespace App\Models;

use Modules\Director\Models\Camp;
use Modules\Director\Models\GameSlot;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RefereeEvaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'referee_id',
        'evaluator_id',
        'camp_id',
        'game_slot_id',
        'call_accuracy',
        'communication_skills',
        'consistency_of_calls',
        'court_position_mechanics',
        'fitness_mobility',
        'game_awareness',
        'total_score',
        'average_score',
        'private_comments',
        'referee_feedback',
        'recommended_highest_level',
        'status',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'total_score' => 'decimal:2',
    ];

    protected $hidden = [
        'private_comments', // Hidden by default, shown conditionally
    ];

    // Relationships
    public function referee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referee_id');
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function camp(): BelongsTo
    {
        return $this->belongsTo(Camp::class);
    }

    public function gameSlot(): BelongsTo
    {
        return $this->belongsTo(GameSlot::class);
    }

    // Auto calculate total score and average score before saving
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($evaluation) {
            // Calculate total score
            $evaluation->total_score = collect([
                $evaluation->call_accuracy,
                $evaluation->communication_skills,
                $evaluation->consistency_of_calls,
                $evaluation->court_position_mechanics,
                $evaluation->fitness_mobility,
                $evaluation->game_awareness,
            ])->filter()->sum();

            // Calculate average score
            if ($evaluation->total_score > 0) {
                $evaluation->average_score = round($evaluation->total_score / 6, 2);
            } else {
                $evaluation->average_score = 0;
            }
        });
    }

    // Scopes
    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeForReferee($query, $refereeId)
    {
        return $query->where('referee_id', $refereeId);
    }

    public function scopeForCamp($query, $campId)
    {
        return $query->where('camp_id', $campId);
    }

    public function scopeByEvaluator($query, $evaluatorId)
    {
        return $query->where('evaluator_id', $evaluatorId);
    }

    // Helper method
    public function canBeViewedBy(User $user): bool
    {
        // Referee can view their own evaluations
        if ($user->id === $this->referee_id) {
            return true;
        }

        // Evaluator who created it can view
        if ($user->id === $this->evaluator_id) {
            return true;
        }

        // Directors can view all evaluations
        if ($user->hasRole('director')) {
            return true;
        }

        return false;
    }

    public function canBeEditedBy(User $user): bool
    {
        // Only evaluator who created it can edit
        if ($user->id === $this->evaluator_id) {
            return true;
        }

        // Directors can edit all evaluations
        if ($user->hasRole('director')) {
            return true;
        }

        return false;
    }
}
