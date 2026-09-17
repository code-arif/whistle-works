<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\SettingService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function __construct(
        protected SettingService $service
    ) {}

    /**
     * Display the executive settings hub.
     */
    public function index(Request $request): Response
    {
        $settings = $this->service->getAllSettings();
        $activeTab = $request->query('tab', 'general');

        return Inertia::render('Settings/Index', [
            'settings'  => $settings,
            'activeTab' => $activeTab,
        ]);
    }

    /**
     * Update general branding and contact settings.
     */
    public function updateGeneral(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['nullable', 'string', 'max:100'],
            'title'       => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'keywords'    => ['nullable', 'string', 'max:255'],
            'author'      => ['nullable', 'string', 'max:100'],
            'phone'       => ['nullable', 'string', 'max:30'],
            'email'       => ['nullable', 'email', 'max:100'],
            'address'     => ['nullable', 'string', 'max:255'],
            'copyright'   => ['nullable', 'string', 'max:255'],
            'logo'        => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:5120'],
            'logo_width'  => ['nullable', 'numeric', 'min:10', 'max:1000'],
            'logo_height' => ['nullable', 'numeric', 'min:10', 'max:1000'],
            'favicon'     => ['nullable', 'image', 'mimes:png,jpg,jpeg,ico,webp', 'max:2048'],
            'thumbnail'   => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
        ]);

        try {
            $this->service->updateGeneral($validated, $request);
            return redirect()->back()->with('t-success', 'General branding and site settings updated successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to update general settings: ' . $e->getMessage());
        }
    }

    /**
     * Update Stripe payment configurations.
     */
    public function updateStripe(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'stripe_key'                     => ['nullable', 'string'],
            'stripe_secret'                  => ['nullable', 'string'],
            'stripe_webhook_secret'          => ['nullable', 'string'],
            'stripe_checkout_webhook_secret' => ['nullable', 'string'],
            'stripe_rented_webhook_secret'   => ['nullable', 'string'],
            'stripe_client_id'               => ['nullable', 'string'],
            'stripe_redirect_url'            => ['nullable', 'string'],
        ]);

        try {
            $this->service->updateStripe($validated);
            return redirect()->back()->with('t-success', 'Stripe payment settings saved successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to update Stripe settings: ' . $e->getMessage());
        }
    }

    /**
     * Update Mail & SMTP configuration.
     */
    public function updateMail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mail_mailer'       => ['required', 'string'],
            'mail_host'         => ['required', 'string'],
            'mail_port'         => ['required', 'numeric'],
            'mail_username'     => ['nullable', 'string'],
            'mail_password'     => ['nullable', 'string'],
            'mail_encryption'   => ['nullable', 'string'],
            'mail_from_address' => ['required', 'email'],
            'mail_from_name'    => ['required', 'string', 'max:100'],
        ]);

        try {
            $this->service->updateMail($validated);
            return redirect()->back()->with('t-success', 'SMTP mail settings updated successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to update mail settings: ' . $e->getMessage());
        }
    }

    /**
     * Send test verification email.
     */
    public function sendTestMail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'receiver' => ['required', 'email'],
            'subject'  => ['required', 'string', 'max:100'],
            'content'  => ['required', 'string', 'max:2000'],
        ]);

        try {
            $this->service->sendTestMail($validated['receiver'], $validated['subject'], $validated['content']);
            return redirect()->back()->with('t-success', 'Test email dispatched successfully to ' . $validated['receiver']);
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to send test email: ' . $e->getMessage());
        }
    }

    /**
     * Update Third-Party Integrations.
     */
    public function updateIntegrations(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'google_client_id'     => ['nullable', 'string'],
            'google_client_secret' => ['nullable', 'string'],
            'google_redirect_uri'  => ['nullable', 'string'],
            'firebase_credentials' => ['nullable', 'string'],
            'google_maps_api_key'  => ['nullable', 'string'],
            'recaptcha_site_key'   => ['nullable', 'string'],
            'recaptcha_secret_key' => ['nullable', 'string'],
        ]);

        try {
            $this->service->updateIntegrations($validated);
            return redirect()->back()->with('t-success', 'Integration credentials updated successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to update integrations: ' . $e->getMessage());
        }
    }

    /**
     * Update System & App Environment settings.
     */
    public function updateSystem(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'app_name'         => ['nullable', 'string', 'max:100'],
            'app_url'          => ['nullable', 'url'],
            'app_debug'        => ['nullable', 'boolean'],
            'access'           => ['nullable', 'boolean'],
            'reverb'           => ['nullable', 'boolean'],
            'recaptcha_enable' => ['nullable', 'boolean'],
            'mail_enabled'     => ['nullable', 'boolean'],
            'sms_enabled'      => ['nullable', 'boolean'],
            'pagination'       => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        try {
            $this->service->updateSystem($validated);
            return redirect()->back()->with('t-success', 'System environment settings updated successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to update system settings: ' . $e->getMessage());
        }
    }

    /**
     * Update Digital Signature.
     */
    public function updateSignature(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'signature' => ['required', 'string'],
        ]);

        try {
            $this->service->updateSignature($validated['signature']);
            return redirect()->back()->with('t-success', 'Digital signature saved successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to save digital signature: ' . $e->getMessage());
        }
    }
}
