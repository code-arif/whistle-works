<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Director\Models\Camp;

class SportsType extends Model
{
    protected $fillable = [
        'sports_name',
        'icon',
        'sports_fee',
        'status',
    ];

    protected $hidden = ['created_at','updated_at','icon'];

    /**
     * Get the camps associated with this sports type.
     */
    public function camps()
    {
        return $this->hasMany(Camp::class, 'sports_type_id');
    }
}
