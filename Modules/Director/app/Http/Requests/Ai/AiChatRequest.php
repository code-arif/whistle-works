<?php

namespace Modules\Director\app\Http\Requests\Ai;

use Illuminate\Foundation\Http\FormRequest;

class AiChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:1', 'max:4000'],
            'session_uuid' => ['nullable', 'string', 'max:64'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'A message or prompt is required.',
            'message.max' => 'Your message is too long (max 4,000 characters).',
        ];
    }
}
