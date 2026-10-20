<?php

namespace Modules\Director\Transformers\Referee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class AvailableRefereeResource extends JsonResource
{
    protected $jerseyNumbers;
    protected $refereeNotes;

    public function __construct($resource, $jerseyNumbers = [], $refereeNotes = [])
    {
        parent::__construct($resource);
        $this->jerseyNumbers = $jerseyNumbers;
        $this->refereeNotes  = $refereeNotes;
    }

    public function toArray($request)
    {
        return [
            'id'            => $this->id,
            'name'          => trim($this->first_name . ' ' . $this->last_name),
            'email'         => $this->email,
            'phone'         => $this->phone,
            'avatar'        => $this->avatar
                ? asset($this->avatar)
                : asset('default/profile.jpg'),
            'address'       => $this->address,
            'jersey_number' => $this->jerseyNumbers[$this->id] ?? null,
            'jourcy_number' => $this->jerseyNumbers[$this->id] ?? null,
            'note'          => $this->refereeNotes[$this->id] ?? null,
            'notes'         => $this->refereeNotes[$this->id] ?? null,
        ];
    }
}
