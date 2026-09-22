<?php

namespace Modules\Director\Http\Requests\Crew;

use Illuminate\Foundation\Http\FormRequest;

class AddCrewMembersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'referee_ids'          => 'nullable|array|min:1|max:8',
            'referee_ids.*'        => 'exists:users,id',
            'positions'            => 'nullable|array|max:8',
            'positions.*'          => 'nullable|string|max:255',
            'members'              => 'nullable|array|min:1|max:8',
            'members.*.referee_id' => 'required_with:members|exists:users,id',
            'members.*.position'   => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'referee_ids.min'               => 'At least one referee must be provided.',
            'referee_ids.max'               => 'Cannot add more than 8 referees at once.',
            'referee_ids.*.exists'          => 'One or more referee IDs are invalid.',
            'members.*.referee_id.exists'   => 'One or more member referee IDs are invalid.',
        ];
    }
}
