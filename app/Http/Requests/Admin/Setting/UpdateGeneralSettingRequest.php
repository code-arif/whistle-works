<?php

namespace App\Http\Requests\Admin\Setting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGeneralSettingRequest extends FormRequest
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
            'name'        => ['nullable', 'string', 'max:100'],
            'title'       => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'keywords'    => ['nullable', 'string', 'max:255'],
            'author'      => ['nullable', 'string', 'max:100'],
            'phone'       => ['nullable', 'string', 'max:30'],
            'email'       => ['nullable', 'email', 'max:100'],
            'address'     => ['nullable', 'string', 'max:255'],
            'copyright'   => ['nullable', 'string', 'max:255'],
            'logo'        => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:5120'],
            'logo_width'  => ['nullable', 'numeric', 'min:10', 'max:1000'],
            'logo_height' => ['nullable', 'numeric', 'min:10', 'max:1000'],
            'favicon'     => ['nullable', 'image', 'mimes:png,jpg,jpeg,ico,webp', 'max:2048'],
            'thumbnail'   => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
        ];
    }
}
