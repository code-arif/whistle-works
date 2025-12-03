<?php

namespace Modules\Director\Models;

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

   
}
