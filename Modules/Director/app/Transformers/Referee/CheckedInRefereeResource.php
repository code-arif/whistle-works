<?php

namespace Modules\Director\Transformers\Referee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckedInRefereeResource extends JsonResource
{
    protected $jerseyNumbers;
    protected $refereeNotes;

    public function __construct($resource, $jerseyNumbers = [], $refereeNotes = [])
    {
        parent::__construct($resource);
        $this->jerseyNumbers = $jerseyNumbers;
        $this->refereeNotes  = $refereeNotes;
    }

    public static function collectionWithJersey($resource, $jerseyNumbers, $refereeNotes = [])
    {
        return $resource->getCollection()->map(function ($item) use ($jerseyNumbers, $refereeNotes) {
            return new self($item, $jerseyNumbers, $refereeNotes);
        });
    }

    public function toArray($request)
    {
        $refereeId = $this->referee->id;
        $jersey = $this->jerseyNumbers[$refereeId] ?? $this->jersey_number ?? null;

        return [
            'id'            => $this->id,
            'referee_id'    => $refereeId,
            'name'          => $this->referee->first_name . ' ' . $this->referee->last_name,
            'email'         => $this->referee->email,
            'phone'         => $this->referee->phone,
            'address'       => $this->referee->address,
            'avatar'        => $this->referee->avatar
                ? asset($this->referee->avatar)
                : asset('default/profile.jpg'),

            'jourcy_number' => $jersey,
            'can_view_evaluations' => (bool) ($this->can_view_evaluations ?? true),
            'note'          => $this->refereeNotes[$refereeId] ?? null,
            'notes'         => $this->refereeNotes[$refereeId] ?? null,
        ];
    }
}
