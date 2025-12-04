<?php

namespace Modules\Director\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CampCreateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'sports_type_id'   => 'required|exists:sports_types,id',
            'camp_name'        => 'required|string|max:255',
            'sports_type_name' => 'nullable|string|max:255',
            'start_date'       => 'required|date',
            'end_date'         => 'required|date|after_or_equal:start_date',
            'location'         => 'required|string|max:255',
            'camp_details'     => 'nullable|string',
            'camp_logo'        => 'nullable|file|image|max:2048',
            'price'            => 'required|numeric|min:0',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
