<?php

namespace Modules\Director\Models;

use App\Models\User;
use App\Models\SportsType;
use App\Models\RefereeEvaluation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Camp extends Model
{
    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the director that owns the camp
     */
    public function director(): BelongsTo
    {
        return $this->belongsTo(User::class, 'director_id');
    }

    /**
     * Get the sports type
     */
    public function sportsType(): BelongsTo
    {
        return $this->belongsTo(SportsType::class);
    }

    /**
     * Get the camp's schedule
     */
    public function schedule(): HasOne
    {
        return $this->hasOne(Schedule::class);
    }

    /**
     * Get checked-in referees
     */
    public function checkedInReferees(): HasMany
    {
        return $this->hasMany(CampRefereeCheckin::class);
    }

    /**
     * Get camp logo URL
     */
    public function getCampLogoUrlAttribute()
    {
        return $this->camp_logo
            ? asset('/' . $this->camp_logo)
            : null;
    }

    /**
     * Check if camp is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if camp has started
     */
    public function hasStarted(): bool
    {
        return now()->gte($this->start_date);
    }

    /**
     * Check if camp has ended
     */
    public function hasEnded(): bool
    {
        return now()->gt($this->end_date);
    }

    /**
     * Get camp duration in days
     */
    public function getDurationAttribute(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }


    // Get all evaluators who evaluated referees in this camp
    public function evaluations()
    {
        return $this->hasMany(RefereeEvaluation::class, 'camp_id');
    }

    // Get unique evaluators who evaluated in this camp
    public function getEvaluatorsAttribute()
    {
        return User::whereIn('id', function ($query) {
            $query->select('evaluator_id')
                ->from('referee_evaluations')
                ->where('camp_id', $this->id)
                ->distinct();
        })->get();
    }


    /**
     * Boot method - Automatically add extra price on camp creation
     */
    // protected static function boot()
    // {
    //     parent::boot();

    //     // Only runs when creating a NEW camp (not on update)
    //     static::creating(function ($camp) {
    //         $extraPrice = config('camp.extra_price', 25); // Default 25 if not set

    //         // Add extra price to the base price
    //         if (isset($camp->price)) {
    //             $camp->price = $camp->price + $extraPrice;
    //         }
    //     });
    // }
}
