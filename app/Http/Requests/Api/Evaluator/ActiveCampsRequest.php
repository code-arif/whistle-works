<?php

namespace App\Http\Requests\Api\Evaluator;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ActiveCampsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sports_type_id' => ['nullable', 'integer', 'exists:sports_types,id'],
            'location'       => ['nullable', 'string'],
            'start_date'     => ['nullable', 'date'],
            'end_date'       => ['nullable', 'date'],
            'sort'           => ['nullable', 'string', 'in:newest,oldest,upcoming'],
            'per_page'       => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * Preserve exact original ApiResponse::error validation payload
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status'  => false,
            'message' => 'Validation failed.',
            'data'    => $validator->errors(),
            'code'    => 422,
        ], 422));
    }
}
