<?php

namespace App\Services\Admin;

use App\Helpers\Helper;
use App\Mail\TestMail;
use App\Models\Setting;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SettingService
{
    /**
     * Retrieve all system, integration, and platform settings formatted for V2 Admin.
     */
    public function getAllSettings(): array
    {
        $setting = Setting::firstOrCreate(['id' => 1]);

        return [
            'general' => [
                'name'        => $setting->name ?? env('APP_NAME', 'Whistle Works'),
                'title'       => $setting->title ?? '',
                'description' => $setting->description ?? '',
                'keywords'    => $setting->keywords ?? '',
                'author'      => $setting->author ?? '',
                'phone'       => $setting->phone ?? '',
                'email'       => $setting->email ?? '',
                'address'     => $setting->address ?? '',
                'copyright'   => $setting->copyright ?? '',
                'logo'        => $setting->logo ? (str_starts_with($setting->logo, 'http') ? $setting->logo : asset($setting->logo)) : null,
                'logo_width'  => $setting->logo_width ?? 180,
                'logo_height' => $setting->logo_height ?? 50,
                'favicon'     => $setting->favicon ? (str_starts_with($setting->favicon, 'http') ? $setting->favicon : asset($setting->favicon)) : null,
                'thumbnail'   => $setting->thumbnail ? (str_starts_with($setting->thumbnail, 'http') ? $setting->thumbnail : asset($setting->thumbnail)) : null,
            ],
            'stripe' => [
                'stripe_key'            => env('STRIPE_KEY', ''),
                'stripe_secret'         => env('STRIPE_SECRET', ''),
                'stripe_webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),
                'webhook_url'           => url('/api/stripe/webhook'),
            ],
            'mail' => [
                'mail_mailer'       => env('MAIL_MAILER', 'smtp'),
                'mail_host'         => env('MAIL_HOST', ''),
                'mail_port'         => env('MAIL_PORT', 587),
                'mail_username'     => env('MAIL_USERNAME', ''),
                'mail_password'     => env('MAIL_PASSWORD', ''),
                'mail_encryption'   => env('MAIL_ENCRYPTION', 'tls'),
                'mail_from_address' => env('MAIL_FROM_ADDRESS', ''),
                'mail_from_name'    => env('MAIL_FROM_NAME', env('APP_NAME', 'Whistle Works')),
            ],
            'integrations' => [
                'google_client_id'     => env('GOOGLE_CLIENT_ID', ''),
                'google_client_secret' => env('GOOGLE_CLIENT_SECRET', ''),
                'google_redirect_uri'  => env('GOOGLE_REDIRECT_URI', ''),
                'google_maps_api_key'  => env('GOOGLE_MAPS_API_KEY', ''),
                'twilio_sid'           => env('TWILIO_SID', ''),
                'twilio_token'         => env('TWILIO_TOKEN', ''),
                'twilio_from'          => env('TWILIO_FROM', ''),
            ],
            'system' => [
                'app_name'         => env('APP_NAME', 'Whistle Works'),
                'app_url'          => env('APP_URL', config('app.url')),
                'frontend_url'     => env('FRONTEND', config('app.frontend_url', 'https://whistleworks.org')),
                'app_env'          => env('APP_ENV', 'local'),
                'app_debug'        => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN),
                'access'           => filter_var(env('ACCESS', true), FILTER_VALIDATE_BOOLEAN),
                'mail_enabled'          => env('MAIL', 'on') === 'on',
                'sms_enabled'           => env('SMS', 'off') === 'on',
                'session_http_only'     => filter_var(env('SESSION_HTTP_ONLY', true), FILTER_VALIDATE_BOOLEAN),
                'session_secure_cookie' => filter_var(env('SESSION_SECURE_COOKIE', false), FILTER_VALIDATE_BOOLEAN),
                'session_same_site'     => env('SESSION_SAME_SITE', 'lax'),
            ],
        ];
    }

    /**
     * Update General & Branding Settings.
     */
    public function updateGeneral(array $data, Request $request): Setting
    {
        $setting = Setting::firstOrCreate(['id' => 1]);

        if ($request->hasFile('logo')) {
            if ($setting->logo && file_exists(public_path($setting->logo))) {
                Helper::fileDelete(public_path($setting->logo));
            }
            $data['logo'] = Helper::fileUpload($request->file('logo'), 'settings');
        }

        if ($request->hasFile('favicon')) {
            if ($setting->favicon && file_exists(public_path($setting->favicon))) {
                Helper::fileDelete(public_path($setting->favicon));
            }
            $data['favicon'] = Helper::fileUpload($request->file('favicon'), 'settings');
        }

        if ($request->hasFile('thumbnail')) {
            if ($setting->thumbnail && file_exists(public_path($setting->thumbnail))) {
                Helper::fileDelete(public_path($setting->thumbnail));
            }
            $data['thumbnail'] = Helper::fileUpload($request->file('thumbnail'), 'settings');
        }

        $setting->update($data);

        // Sync APP_NAME in .env if provided
        if (!empty($data['name'])) {
            $this->updateEnv(['APP_NAME' => $data['name']]);
        }

        return $setting;
    }

    /**
     * Update Stripe & Payment credentials.
     */
    public function updateStripe(array $data): void
    {
        $keys = [
            'STRIPE_KEY'            => $data['stripe_key'] ?? '',
            'STRIPE_SECRET'         => $data['stripe_secret'] ?? '',
            'STRIPE_WEBHOOK_SECRET' => $data['stripe_webhook_secret'] ?? '',
        ];

        $this->updateEnv($keys);
    }

    /**
     * Update Mail & SMTP configuration.
     */
    public function updateMail(array $data): void
    {
        $keys = [
            'MAIL_MAILER'       => $data['mail_mailer'] ?? 'smtp',
            'MAIL_HOST'         => $data['mail_host'] ?? '',
            'MAIL_PORT'         => $data['mail_port'] ?? 587,
            'MAIL_USERNAME'     => $data['mail_username'] ?? '',
            'MAIL_PASSWORD'     => $data['mail_password'] ?? '',
            'MAIL_ENCRYPTION'   => $data['mail_encryption'] ?? 'tls',
            'MAIL_FROM_ADDRESS' => $data['mail_from_address'] ?? '',
            'MAIL_FROM_NAME'    => $data['mail_from_name'] ?? env('APP_NAME', 'Whistle Works'),
        ];

        $this->updateEnv($keys);
    }

    /**
     * Send a test verification email via configured SMTP.
     */
    public function sendTestMail(string $receiver, string $subject, string $content): void
    {
        Mail::to($receiver)->send(new TestMail($subject, $content));
    }

    /**
     * Update Third-Party Integrations.
     */
    public function updateIntegrations(array $data): void
    {
        $keys = [
            'GOOGLE_CLIENT_ID'     => $data['google_client_id'] ?? '',
            'GOOGLE_CLIENT_SECRET' => $data['google_client_secret'] ?? '',
            'GOOGLE_REDIRECT_URI'  => $data['google_redirect_uri'] ?? '',
            'GOOGLE_MAPS_API_KEY'  => $data['google_maps_api_key'] ?? '',
            'TWILIO_SID'           => $data['twilio_sid'] ?? '',
            'TWILIO_TOKEN'         => $data['twilio_token'] ?? '',
            'TWILIO_FROM'          => $data['twilio_from'] ?? '',
        ];

        $this->updateEnv($keys);
    }

    /**
     * Update System & App Environment settings.
     */
    public function updateSystem(array $data): void
    {
        $keys = [];

        if (isset($data['app_name'])) {
            $keys['APP_NAME'] = $data['app_name'];
        }
        if (isset($data['app_url'])) {
            $keys['APP_URL'] = $data['app_url'];
        }
        if (isset($data['frontend_url'])) {
            $keys['FRONTEND'] = $data['frontend_url'];
        }
        if (isset($data['app_debug'])) {
            $keys['APP_DEBUG'] = $data['app_debug'] ? 'true' : 'false';
        }
        if (isset($data['access'])) {
            $keys['ACCESS'] = $data['access'] ? 'true' : 'false';
        }
        if (isset($data['mail_enabled'])) {
            $keys['MAIL'] = $data['mail_enabled'] ? 'on' : 'off';
        }
        if (isset($data['sms_enabled'])) {
            $keys['SMS'] = $data['sms_enabled'] ? 'on' : 'off';
        }
        if (isset($data['session_http_only'])) {
            $keys['SESSION_HTTP_ONLY'] = $data['session_http_only'] ? 'true' : 'false';
        }
        if (isset($data['session_secure_cookie'])) {
            $keys['SESSION_SECURE_COOKIE'] = $data['session_secure_cookie'] ? 'true' : 'false';
        }
        if (isset($data['session_same_site'])) {
            $keys['SESSION_SAME_SITE'] = $data['session_same_site'];
        }

        $this->updateEnv($keys);
    }

    /**
     * Safely update environment keys in .env file with regex and automated cache flush.
     */
    protected function updateEnv(array $data): void
    {
        $envPath = base_path('.env');
        if (!File::exists($envPath)) {
            return;
        }

        $envContent = File::get($envPath);

        foreach ($data as $key => $value) {
            $value = trim((string) $value);

            // Quote value if it contains whitespace or special symbols
            if (preg_match('/\s/', $value) && !str_starts_with($value, '"') && !str_ends_with($value, '"')) {
                $formattedValue = '"' . addcslashes($value, '"') . '"';
            } else {
                $formattedValue = $value;
            }

            $pattern = "/^{$key}=.*$/m";

            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, "{$key}={$formattedValue}", $envContent);
            } else {
                $envContent .= "\n{$key}={$formattedValue}";
            }
        }

        File::put($envPath, $envContent);

        // Clear cached config so changes take effect immediately
        try {
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
        } catch (Exception $e) {
            Log::warning('Config/Cache clear failed after env update: ' . $e->getMessage());
        }
    }
}
