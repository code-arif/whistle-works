<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Setting\SendTestMailRequest;
use App\Http\Requests\Admin\Setting\UpdateGeneralSettingRequest;
use App\Http\Requests\Admin\Setting\UpdateIntegrationsRequest;
use App\Http\Requests\Admin\Setting\UpdateMailSettingRequest;
use App\Http\Requests\Admin\Setting\UpdateStripeSettingRequest;
use App\Http\Requests\Admin\Setting\UpdateSystemSettingRequest;
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
        $settings  = $this->service->getAllSettings();
        $activeTab = $request->query('tab', 'general');

        return Inertia::render('Settings/Index', [
            'settings'  => $settings,
            'activeTab' => $activeTab,
        ]);
    }

    /**
     * Update general branding and contact settings.
     */
    public function updateGeneral(UpdateGeneralSettingRequest $request): RedirectResponse
    {
        try {
            $this->service->updateGeneral($request->validated(), $request);

            return redirect()->back()->with('t-success', 'General branding and site settings updated successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to update general settings: ' . $e->getMessage());
        }
    }

    /**
     * Update Stripe payment configurations.
     */
    public function updateStripe(UpdateStripeSettingRequest $request): RedirectResponse
    {
        try {
            $this->service->updateStripe($request->validated());

            return redirect()->back()->with('t-success', 'Stripe payment settings saved successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to update Stripe settings: ' . $e->getMessage());
        }
    }

    /**
     * Update Mail & SMTP configuration.
     */
    public function updateMail(UpdateMailSettingRequest $request): RedirectResponse
    {
        try {
            $this->service->updateMail($request->validated());

            return redirect()->back()->with('t-success', 'SMTP mail settings updated successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to update mail settings: ' . $e->getMessage());
        }
    }

    /**
     * Send test verification email.
     */
    public function sendTestMail(SendTestMailRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();
            $this->service->sendTestMail($validated['receiver'], $validated['subject'], $validated['content']);

            return redirect()->back()->with('t-success', 'Test email dispatched successfully to ' . $validated['receiver']);
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to send test email: ' . $e->getMessage());
        }
    }

    /**
     * Update Third-Party Integrations.
     */
    public function updateIntegrations(UpdateIntegrationsRequest $request): RedirectResponse
    {
        try {
            $this->service->updateIntegrations($request->validated());

            return redirect()->back()->with('t-success', 'Integration credentials updated successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to update integrations: ' . $e->getMessage());
        }
    }

    /**
     * Update System & App Environment settings.
     */
    public function updateSystem(UpdateSystemSettingRequest $request): RedirectResponse
    {
        try {
            $this->service->updateSystem($request->validated());

            return redirect()->back()->with('t-success', 'System environment settings updated successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to update system settings: ' . $e->getMessage());
        }
    }
}
