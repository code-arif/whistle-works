<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name'      => 'nullable|string|max:100',
            'last_name'       => 'nullable|string|max:100',
            'biography'       => 'nullable|string|max:2500',
            'phone'           => 'nullable|string|max:150',
            'address'         => 'nullable|string',
            'is_phone_show'   => 'nullable',
            'is_address_show' => 'nullable',
        ];
    }
}
