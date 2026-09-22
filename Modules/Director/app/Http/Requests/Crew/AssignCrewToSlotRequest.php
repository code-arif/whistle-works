<?php

namespace Modules\Director\Http\Requests\Crew;

use Illuminate\Foundation\Http\FormRequest;

class AssignCrewToSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'crew_id' => 'required|exists:crews,id',
        ];
    }

    public function messages(): array
    {
        return [
            'crew_id.required' => 'A crew ID is required.',
            'crew_id.exists'   => 'The selected crew does not exist.',
        ];
    }
}
