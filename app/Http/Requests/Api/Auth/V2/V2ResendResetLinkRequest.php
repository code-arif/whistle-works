<?php

namespace App\Http\Requests\Api\Auth\V2;

use Illuminate\Foundation\Http\FormRequest;

class V2ResendResetLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email|exists:users,email',
        ];
    }
}
