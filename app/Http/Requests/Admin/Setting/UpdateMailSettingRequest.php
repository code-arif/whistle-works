<?php

namespace App\Http\Requests\Admin\Setting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMailSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mail_mailer'       => ['required', 'string'],
            'mail_host'         => ['required', 'string'],
            'mail_port'         => ['required', 'numeric'],
            'mail_username'     => ['nullable', 'string'],
            'mail_password'     => ['nullable', 'string'],
            'mail_encryption'   => ['nullable', 'string'],
            'mail_from_address' => ['required', 'email'],
            'mail_from_name'    => ['required', 'string', 'max:100'],
        ];
    }
}
