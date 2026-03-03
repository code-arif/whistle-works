<?php

namespace Modules\Director\Transformers\Referee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckedInRefereeResource extends JsonResource
{
    protected $jerseyNumbers;

    public function __construct($resource, $jerseyNumbers = [])
    {
        parent::__construct($resource);
        $this->jerseyNumbers = $jerseyNumbers;
    }

    public static function collectionWithJersey($resource, $jerseyNumbers)
    {
        return $resource->getCollection()->map(function ($item) use ($jerseyNumbers) {
            return new self($item, $jerseyNumbers);
        });
    }

    public function toArray($request)
    {
        return [
            'id'     => $this->id,
            'referee_id' => $this->referee->id,
            'name'   => $this->referee->first_name . ' ' . $this->referee->last_name,
            'email'  => $this->referee->email,
            'phone'  => $this->referee->phone,
            'address'  => $this->referee->address,
            'avatar' => $this->referee->avatar
                ? asset($this->referee->avatar)
                : asset('default/profile.jpg'),

            // correct jersey number
            'jersey_number' => $this->jerseyNumbers[$this->referee->id] ?? null,
        ];
    }
}
