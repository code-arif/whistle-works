<?php

namespace App\Http\Requests\Api\Auth\V2;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class V2RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'email'      => 'required|string|email|max:150|unique:users,email',
            'phone'      => 'required|string|max:150|unique:users,phone',
            'address'    => 'required|string',
            'password'   => 'required|string|min:6|confirmed',
            'agree'      => 'required|in:true',
            'role'       => 'required',
            'biography'  => 'nullable|string|max:2500',
        ];
    }

    /**
     * Preserve exact original V2RegisterController validation payload
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status'  => false,
            'message' => 'Validation error',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
