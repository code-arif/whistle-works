<?php

namespace App\Http\Controllers\Api\Settings;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Services\Api\Settings\SettingsService;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    protected SettingsService $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * Get global settings.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $data = $this->settingsService->getSettings();

        return Helper::jsonResponse(true, 'About Page', 200, $data);
    }
}
