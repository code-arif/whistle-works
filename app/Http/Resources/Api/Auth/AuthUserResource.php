<?php

namespace App\Http\Resources\Api\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * Preserves exact keys returned by LoginController.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'email'      => $this->email,
            'username'   => $this->username,
            'name'       => $this->name,
            'first_name' => $this->first_name,
            'last_name'  => $this->last_name,
            'avatar'     => $this->avatar,
            'address'    => $this->address,
            'status'     => $this->status,
            'role'       => $this->role ?? null,
            'biography'  => $this->biography,
        ];
    }
}
