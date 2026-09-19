<?php

namespace App\Http\Resources\Api\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileResource extends JsonResource
{
    protected bool $includeRefereeStats;

    public function __construct($resource, bool $includeRefereeStats = false)
    {
        parent::__construct($resource);
        $this->includeRefereeStats = $includeRefereeStats;
    }

    /**
     * Transform the resource into an array.
     * Preserves exact keys returned by UserController::me and updateProfile.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $avatarUrl = $this->avatar ? asset($this->avatar) : asset('default/profile.jpg');

        $data = [
            'id'              => $this->id,
            'first_name'      => $this->first_name,
            'last_name'       => $this->last_name,
            'username'        => $this->username,
            'email'           => $this->email,
            'phone'           => $this->phone,
            'address'         => $this->address,
            'biography'       => $this->biography,
            'avatar'          => $avatarUrl,
            'slug'            => $this->slug,
            'role'            => $this->role,
            'is_phone_show'   => (bool) $this->is_phone_show,
            'is_address_show' => (bool) $this->is_address_show,
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
        ];

        if ($this->includeRefereeStats && $this->role === 'referee') {
            $avgScore10 = $this->evaluations()
                ->where('status', 'submitted')
                ->whereNotNull('average_score')
                ->avg('average_score');

            $rating5 = $avgScore10 ? round($avgScore10 / 2, 1) : 0;

            $data['referee'] = [
                'checkin_camp' => $this->refereeCheckins()->count(),
                'total_game'   => $this->evaluations()
                    ->where('status', 'submitted')
                    ->count(),
                'rating'       => $rating5,
            ];
        }

        return $data;
    }
}
