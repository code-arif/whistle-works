<?php

namespace App\Http\Resources\Evaluator;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampForEvaluatorResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'director_id' => $this->director_id,
            'sports_type_name' => $this->sports_type_name,
            'camp_name' => $this->camp_name,
            'camp_logo' => $this->camp_logo ? asset($this->camp_logo) : null,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'price' => $this->price,
            'status' => $this->status,
        ];
    }
}
