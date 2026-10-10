<?php

namespace App\Http\Requests\Admin\Ai;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAiSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ai_provider' => ['nullable', 'string', 'in:openai,gemini'],
            'openai_api_key' => ['nullable', 'string'],
            'openai_model' => ['nullable', 'string'],
            'gemini_api_key' => ['nullable', 'string'],
            'gemini_model' => ['nullable', 'string'],
            'default_monthly_quota' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'enable_ai_coach' => ['nullable', 'boolean'],
            'ai_system_prompt_override' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
