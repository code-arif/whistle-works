<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecommendedLevel extends Model
{
    protected $fillable = [
        'evaluation_id',
        'sports_type_level_id',
        'level',
    ];

    // reverse relation with evaluation table
    public function evaluation()
    {
        return $this->belongsTo(RefereeEvaluation::class, 'evaluation_id');
    }

    // relation with sports_type_levels master table
    public function sportsTypeLevel()
    {
        return $this->belongsTo(SportsTypeLevel::class, 'sports_type_level_id');
    }
}
