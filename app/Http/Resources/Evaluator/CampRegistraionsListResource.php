<?php

namespace App\Http\Resources\Evaluator;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampRegistraionsListResource extends JsonResource
{
    public function toArray($request)
    {
        $showContactDetails = in_array($this->status, ['approved', 'pending'])
            || $request->is('*approved*')
            || $request->is('*pending*');

        return [
            'id' => $this->id,
            'status' => $this->status,
            'registration_note' => $this->registration_note,
            'rejection_reason' => $this->rejection_reason,
            'registered_at' => $this->registered_at,
            'approved_at' => $this->approved_at,
            'rejected_at' => $this->rejected_at,

            // evaluator nested – limited fields only
            'evaluator' => [
                'id' => $this->evaluator?->id,
                'name' => $this->evaluator ? trim(($this->evaluator->first_name ?? '') . ' ' . ($this->evaluator->last_name ?? '')) : null,
                'email' => $this->evaluator?->email,
                'phone' => ($showContactDetails || $this->evaluator?->is_phone_show) ? $this->evaluator?->phone : null,
                'address' => ($showContactDetails || $this->evaluator?->is_address_show) ? $this->evaluator?->address : null,
                'avatar' => $this->evaluator?->avatar ? asset('' . $this->evaluator->avatar) : asset('default/profile.jpg'),
            ],
        ];
    }
}
