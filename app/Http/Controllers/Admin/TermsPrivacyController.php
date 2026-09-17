<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\TermsPrivacyService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TermsPrivacyController extends Controller
{
    public function __construct(
        protected TermsPrivacyService $service
    ) {}

    /**
     * Display the Terms & Privacy management page.
     */
    public function index(Request $request): Response
    {
        $data = $this->service->getTermsAndPrivacyData();
        $activeTab = $request->query('tab', 'privacy');
        if (!in_array($activeTab, ['privacy', 'terms'])) {
            $activeTab = 'privacy';
        }

        return Inertia::render('TermsPrivacy/Index', [
            'documents' => $data,
            'activeTab' => $activeTab,
        ]);
    }

    /**
     * Update Privacy Policy or Terms & Conditions content.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type'        => ['required', 'string', 'in:privacy,terms'],
            'description' => ['nullable', 'string'],
        ]);

        try {
            $this->service->updateContent($validated['type'], $validated['description'] ?? '');

            $label = $validated['type'] === 'privacy' ? 'Privacy Policy' : 'Terms & Conditions';
            return redirect()->back()->with('t-success', "{$label} updated successfully.");
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to update content: ' . $e->getMessage());
        }
    }
}
