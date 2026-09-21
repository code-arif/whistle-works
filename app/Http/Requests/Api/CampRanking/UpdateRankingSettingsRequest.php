<?php

namespace App\Http\Requests\Api\CampRanking;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateRankingSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'publish_for_evaluators' => 'sometimes|boolean',
            'hide_evaluator_name'    => 'sometimes|boolean',
            'hide_ranking_numbers'   => 'sometimes|boolean',
            'publish_for_referees'   => 'sometimes|boolean',
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
