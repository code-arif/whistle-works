<?php

namespace App\Http\Requests\Api\DirectorCampManage;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RemoveAssistantDirectorPermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assistant_director_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
            'camp_id' => [
                'required',
                'integer',
                'exists:camps,id',
            ],
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
