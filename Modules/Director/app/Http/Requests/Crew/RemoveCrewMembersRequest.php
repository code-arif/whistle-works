<?php

namespace Modules\Director\Http\Requests\Crew;

use Illuminate\Foundation\Http\FormRequest;

class RemoveCrewMembersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'referee_ids'   => 'required|array|min:1',
            'referee_ids.*' => 'exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'referee_ids.required' => 'Please provide at least one referee ID to remove.',
            'referee_ids.min'      => 'At least one referee ID is required.',
            'referee_ids.*.exists' => 'One or more referee IDs are invalid.',
        ];
    }
}
