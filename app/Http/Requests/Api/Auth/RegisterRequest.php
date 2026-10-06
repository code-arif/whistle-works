<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name'                 => 'required|string|max:100',
            'last_name'                  => 'required|string|max:100',
            'email'                      => 'required|string|email|max:150|unique:users',
            'phone'                      => 'required|string|max:150',
            'address'                    => 'required|string',
            'password'                   => 'required|string|min:6|confirmed',
            'agree'                      => 'required|in:true',
            'role'                       => 'required',
            'biography'                  => 'nullable|string|max:2500',
            'receive_sms_notifications'  => 'nullable|boolean',
        ];
    }
}
