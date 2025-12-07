<?php

namespace Modules\Director\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                => $this->id,
            'camp_name'         => $this->camp_name,
            'camp_logo'         => $this->camp_logo ? asset($this->camp_logo) : null,
            'location'          => $this->location,
            'start_date'        => $this->start_date,
            'end_date'          => $this->end_date,
            'camp_details'      => $this->camp_details,
            'price'             => number_format($this->price, 2),
            'sports_type_id'    => $this->sports_type_id,
            'sports_type_name'  => $this->sports_type_name,
            'status'            => $this->status,
            'created_at'        => $this->created_at->format('Y-m-d H:i:s'),
            'sport'             => [
                'id'            => $this->sportsType ? $this->sportsType->id : null,
                'sports_name'  => $this->sportsType ? $this->sportsType->sports_name : null,
                'icon'         => $this->sportsType && $this->sportsType->icon ? asset($this->sportsType->icon) : null,
            ],
        ];
    }
}
