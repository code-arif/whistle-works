<?php

namespace App\Services\Api\Settings;

use App\Models\Setting;

class SettingsService
{
    /**
     * Get global application settings.
     *
     * @return array
     */
    public function getSettings(): array
    {
        $settings = Setting::first();

        return [
            'settings' => $settings,
        ];
    }
}
