<?php

namespace Modules\Director\Http\Requests\Crew;

use Illuminate\Foundation\Http\FormRequest;

class CreateCrewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                 => 'required|string|max:255',
            'description'          => 'nullable|string|max:500',
            'positions'            => 'nullable|array|max:8',
            'positions.*'          => 'nullable|string|max:255',
            'referee_ids'          => 'nullable|array|max:8',
            'referee_ids.*'        => 'exists:users,id',
            'members'              => 'nullable|array|max:8',
            'members.*.referee_id' => 'required_with:members|exists:users,id',
            'members.*.position'   => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'                 => 'Crew name is required.',
            'name.max'                      => 'Crew name must not exceed 255 characters.',
            'referee_ids.*.exists'          => 'One or more referee IDs are invalid.',
            'members.*.referee_id.exists'   => 'One or more member referee IDs are invalid.',
        ];
    }
}
