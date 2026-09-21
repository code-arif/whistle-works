<?php

namespace App\Http\Requests\Api\CampRanking;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ToggleEvaluatorPermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'can_view_evaluations' => 'required|boolean',
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
