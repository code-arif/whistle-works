<?php

namespace App\Http\Requests\Admin\SportsType;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSportsTypeRequest extends FormRequest
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
            'sports_name' => ['required', 'string', 'max:250'],
            'sports_fee'  => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'icon'        => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:5120'],
            'status'      => ['nullable', 'in:active,inactive'],
        ];
    }
}
