<?php

namespace Modules\Director\Transformers\CourtAssign;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameSlotRefereeResource extends JsonResource
{
    protected $jerseyNumbers;
    protected $refereeNotes;

    public function __construct($resource, $jerseyNumbers = [], $refereeNotes = [])
    {
        parent::__construct($resource);
        $this->jerseyNumbers = $jerseyNumbers;
        $this->refereeNotes  = $refereeNotes;
    }

    public static function collectionWithExtra($resource, $jerseyNumbers = [], $refereeNotes = [])
    {
        return $resource->map(function ($item) use ($jerseyNumbers, $refereeNotes) {
            return new self($item, $jerseyNumbers, $refereeNotes);
        });
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $referee   = $this->assignable;
        $refereeId = $this->assignable_id;
        $jersey    = $this->jerseyNumbers[$refereeId] ?? null;

        return [
            'assignment_id' => $this->id,
            'position'      => $this->position,
            'referee_id'    => $refereeId,
            'name'          => $referee ? ($referee->full_name ?? trim(($referee->first_name ?? '') . ' ' . ($referee->last_name ?? ''))) : null,
            'email'         => $referee?->email,
            'avatar'        => $referee?->avatar ? asset($referee->avatar) : asset('default/profile.jpg'),
            'jersey_number' => $jersey,
            'jourcy_number' => $jersey,
            'note'          => $this->refereeNotes[$refereeId] ?? null,
            'notes'         => $this->refereeNotes[$refereeId] ?? null,
        ];
    }
}
