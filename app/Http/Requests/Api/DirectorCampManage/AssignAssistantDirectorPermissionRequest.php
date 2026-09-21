<?php

namespace App\Http\Requests\Api\DirectorCampManage;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

class AssignAssistantDirectorPermissionRequest extends FormRequest
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
                'not_in:' . Auth::id(),
            ],
            'camp_id' => [
                'required',
                'integer',
                'exists:camps,id',
            ],
            'permissions'              => ['sometimes', 'array'],
            'build_schedule'           => ['sometimes', 'boolean'],
            'assign_referees'          => ['sometimes', 'boolean'],
            'publish_camp'             => ['sometimes', 'boolean'],
            'manage_ranking_reports'   => ['sometimes', 'boolean'],
            'manage_roster_referees'   => ['sometimes', 'boolean'],
            'manage_roster_evaluators' => ['sometimes', 'boolean'],
            'manage_roster_crews'      => ['sometimes', 'boolean'],
            'manage_announcements'     => ['sometimes', 'boolean'],
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
