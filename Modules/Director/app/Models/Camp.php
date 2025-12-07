<?php

namespace Modules\Director\Models;

use App\Models\SportsType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Director\Database\Factories\CampFactory;

class Camp extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['director_id', 'sports_type_id', 'sports_type_name', 'camp_name', 'camp_logo', 'location', 'start_date', 'end_date', 'camp_details', 'price', 'status'];


    /**
     * Get the sports type associated with the camp.
     */
    public function sportsType()
    {
        return $this->belongsTo(SportsType::class, 'sports_type_id');
    }
}
