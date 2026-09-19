<?php

namespace App\Http\Requests\Admin\Setting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIntegrationsRequest extends FormRequest
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
            'google_client_id'     => ['nullable', 'string'],
            'google_client_secret' => ['nullable', 'string'],
            'google_redirect_uri'  => ['nullable', 'string'],
            'google_maps_api_key'  => ['nullable', 'string'],
            'twilio_sid'           => ['nullable', 'string'],
            'twilio_token'         => ['nullable', 'string'],
            'twilio_from'          => ['nullable', 'string'],
        ];
    }
}
