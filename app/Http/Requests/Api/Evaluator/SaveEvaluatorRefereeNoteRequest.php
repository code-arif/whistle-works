<?php

namespace App\Http\Requests\Api\Evaluator;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SaveEvaluatorRefereeNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'camp_id' => ['required', 'integer', 'exists:camps,id'],
            'referee_id' => ['required', 'integer', 'exists:users,id'],
            'game_slot_id' => ['nullable', 'integer', 'exists:game_slots,id'],
            'note' => ['required', 'string', 'max:5000'],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => false,
            'message' => 'Validation failed.',
            'data' => $validator->errors(),
            'code' => 422,
        ], 422));
    }
}
