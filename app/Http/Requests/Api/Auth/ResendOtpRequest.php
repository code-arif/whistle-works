<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ResendOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'   => 'required|email|exists:users,email',
            'purpose' => 'nullable|string|in:registration,password_reset,verification,login',
        ];
    }
}
