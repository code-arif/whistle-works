<?php

namespace Modules\Director\Http\Requests\Crew;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCrewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                 => 'string|max:255',
            'description'          => 'nullable|string|max:500',
            'status'               => 'sometimes|in:active,inactive',
            'referee_ids'          => 'nullable|array|max:8',
            'referee_ids.*'        => 'exists:users,id',
            'positions'            => 'nullable|array|max:8',
            'positions.*'          => 'nullable|string|max:255',
            'members'              => 'nullable|array|max:8',
            'members.*.referee_id' => 'required_with:members|exists:users,id',
            'members.*.position'   => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'name.max'                      => 'Crew name must not exceed 255 characters.',
            'status.in'                     => 'Status must be either active or inactive.',
            'referee_ids.*.exists'          => 'One or more referee IDs are invalid.',
            'members.*.referee_id.exists'   => 'One or more member referee IDs are invalid.',
        ];
    }
}
