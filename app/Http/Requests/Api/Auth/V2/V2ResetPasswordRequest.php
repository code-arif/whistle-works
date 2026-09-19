<?php

namespace App\Http\Requests\Api\Auth\V2;

use Illuminate\Foundation\Http\FormRequest;

class V2ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'    => 'required|email|exists:users,email',
            'token'    => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ];
    }
}
