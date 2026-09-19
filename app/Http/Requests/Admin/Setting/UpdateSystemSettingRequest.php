<?php

namespace App\Http\Requests\Admin\Setting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemSettingRequest extends FormRequest
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
            'app_name'              => ['nullable', 'string', 'max:100'],
            'app_url'               => ['nullable', 'url'],
            'frontend_url'          => ['nullable', 'url'],
            'app_debug'             => ['nullable', 'boolean'],
            'access'                => ['nullable', 'boolean'],
            'mail_enabled'          => ['nullable', 'boolean'],
            'sms_enabled'           => ['nullable', 'boolean'],
            'session_http_only'     => ['nullable', 'boolean'],
            'session_secure_cookie' => ['nullable', 'boolean'],
            'session_same_site'     => ['nullable', 'string', 'in:lax,strict,none'],
        ];
    }
}
