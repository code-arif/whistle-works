<?php

namespace Database\Seeders;

use App\Models\AiSetting;
use Illuminate\Database\Seeder;

class AiSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            ['key' => 'ai_provider', 'value' => 'openai', 'encrypt' => false],
            ['key' => 'openai_api_key', 'value' => env('OPENAI_API_KEY', ''), 'encrypt' => true],
            ['key' => 'openai_model', 'value' => 'gpt-4o', 'encrypt' => false],
            ['key' => 'gemini_api_key', 'value' => env('GEMINI_API_KEY', ''), 'encrypt' => true],
            ['key' => 'gemini_model', 'value' => 'gemini-1.5-pro', 'encrypt' => false],
            ['key' => 'default_monthly_quota', 'value' => '50', 'encrypt' => false],
            ['key' => 'ai_system_prompt_override', 'value' => '', 'encrypt' => false],
            ['key' => 'enable_ai_coach', 'value' => '1', 'encrypt' => false],
        ];

        foreach ($settings as $item) {
            AiSetting::setValue($item['key'], $item['value'], $item['encrypt']);
        }
    }
}
