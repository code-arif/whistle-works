<?php

namespace Modules\Director\Transformers\Referee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class AvailableRefereeResource extends JsonResource
{
    protected $jerseyNumbers;

    public function __construct($resource, $jerseyNumbers = [])
    {
        parent::__construct($resource);
        $this->jerseyNumbers = $jerseyNumbers;
    }

    public function toArray($request)
    {
        return [
            'id'     => $this->id,
            'name'   => trim($this->first_name . ' ' . $this->last_name),
            'email'  => $this->email,
            'phone'  => $this->phone,
            'avatar' => $this->avatar
                ? asset($this->avatar)
                : asset('default/profile.jpg'),
            'address' => $this->address,
            'jersey_number' => $this->jerseyNumbers[$this->id] ?? null,
        ];
    }
}
