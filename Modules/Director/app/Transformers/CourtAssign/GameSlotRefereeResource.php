<?php

namespace Modules\Director\Transformers\CourtAssign;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameSlotRefereeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $referee = $this->assignable;

        return [
            'assignment_id' => $this->id,
            'position'      => $this->position,
            'referee_id'    => $this->assignable_id,
            'name'          => $referee ? ($referee->full_name ?? trim(($referee->first_name ?? '') . ' ' . ($referee->last_name ?? ''))) : null,
            'email'         => $referee?->email,
            'avatar'        => $referee?->avatar ? asset($referee->avatar) : asset('default/profile.jpg'),
        ];
    }
}
