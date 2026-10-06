<?php

namespace App\Http\Requests\Admin\Camp;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCampRequest extends FormRequest
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
            'director_id'    => ['required', 'exists:users,id'],
            'sports_type_id' => ['required', 'exists:sports_types,id'],
            'camp_name'      => ['required', 'string', 'max:255'],
            'location'       => ['required', 'string', 'max:255'],
            'address'        => ['nullable', 'string', 'max:255'],
            'latitude'       => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'      => ['nullable', 'numeric', 'between:-180,180'],
            'start_date'     => ['required', 'date'],
            'end_date'       => ['required', 'date', 'after_or_equal:start_date'],
            'camp_details'   => ['nullable', 'string'],
            'price'          => ['required', 'numeric', 'min:0'],
            'camp_logo'      => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:5120'],
            'status'         => ['nullable', 'in:active,inactive'],
        ];
    }
}
