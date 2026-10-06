<?php

namespace App\Http\Requests\Api\Announcement;

use App\Traits\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreAnnouncementRequest extends FormRequest
{
    use ApiResponse;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'camp_id'           => 'required|integer|exists:camps,id',
            'subject'           => 'required|string|max:255',
            'message'           => 'required|string',
            'announcement_to'   => 'required|in:all,referees,evaluators,specific',
            'specific_user_ids' => 'required_if:announcement_to,specific|array',
            'specific_user_ids.*' => 'integer',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->error($validator->errors(), 'Validation failed.', 422)
        );
    }
}
