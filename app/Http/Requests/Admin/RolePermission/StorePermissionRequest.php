<?php

namespace App\Http\Requests\Admin\RolePermission;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePermissionRequest extends FormRequest
{
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:60',
                'regex:/^[a-zA-Z0-9_\-\s]+$/',
                Rule::unique('permissions', 'name')->where('guard_name', 'web'),
            ],
            'module' => ['nullable', 'string', 'max:60'],
        ];
    }
}
