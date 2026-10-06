<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\CmsAboutService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CmsAboutController extends Controller
{
    public function __construct(
        protected CmsAboutService $service
    ) {}

    /**
     * Display the About Page CMS management screen.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('CMS/About/Index', [
            'data' => $this->service->getAboutPageData(),
            'activeTab' => $request->query('tab', 'overview'),
        ]);
    }

    /**
     * Update Page Title section.
     */
    public function updatePageTitle(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        try {
            $this->service->updatePageTitle($validated);
            return redirect()->back()->with('t-success', 'Page title section updated successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to update page title: ' . $e->getMessage());
        }
    }

    /**
     * Update Mission section.
     */
    public function updateMission(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
        ]);

        try {
            $this->service->updateMission($validated);
            return redirect()->back()->with('t-success', 'Mission section updated successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to update mission: ' . $e->getMessage());
        }
    }

    /**
     * Update Key to Excellence section.
     */
    public function updateKeyToExcellence(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
        ]);

        try {
            $this->service->updateKeyToExcellence($validated);
            return redirect()->back()->with('t-success', 'Key to Excellence section updated successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to update Key to Excellence: ' . $e->getMessage());
        }
    }

    /**
     * Update Bottom Description section.
     */
    public function updateBottomDescription(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'description' => ['required', 'string'],
        ]);

        try {
            $this->service->updateBottomDescription($validated);
            return redirect()->back()->with('t-success', 'Bottom description updated successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to update bottom description: ' . $e->getMessage());
        }
    }

    /**
     * Update Getting Started section.
     */
    public function updateGettingStarted(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        try {
            $this->service->updateGettingStarted($validated);
            return redirect()->back()->with('t-success', 'Getting Started section updated successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to update Getting Started section: ' . $e->getMessage());
        }
    }

    /**
     * Update Owner / Founder info.
     */
    public function updateOwnerInfo(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'designation' => ['required', 'string', 'max:255'],
            'experience' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'stat_1_value' => ['nullable', 'string', 'max:50'],
            'stat_1_label' => ['nullable', 'string', 'max:100'],
            'stat_2_value' => ['nullable', 'string', 'max:50'],
            'stat_2_label' => ['nullable', 'string', 'max:100'],
            'stat_3_value' => ['nullable', 'string', 'max:50'],
            'stat_3_label' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $this->service->updateOwnerInfo($validated, $request->file('image'));
            return redirect()->back()->with('t-success', 'Founder & Owner profile updated successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to update founder profile: ' . $e->getMessage());
        }
    }

    /**
     * Store About Feature Card.
     */
    public function storeFeatureCard(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:5120'],
        ]);

        try {
            $this->service->storeFeatureCard($validated, $request->file('image'));
            return redirect()->back()->with('t-success', 'Feature card added successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to create feature card: ' . $e->getMessage());
        }
    }

    /**
     * Update About Feature Card.
     */
    public function updateFeatureCard(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'order' => ['nullable', 'integer'],
            'image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:5120'],
        ]);

        try {
            $this->service->updateFeatureCard($id, $validated, $request->file('image'));
            return redirect()->back()->with('t-success', 'Feature card updated successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to update feature card: ' . $e->getMessage());
        }
    }

    /**
     * Destroy About Feature Card.
     */
    public function destroyFeatureCard(int $id): RedirectResponse
    {
        try {
            $this->service->destroyFeatureCard($id);
            return redirect()->back()->with('t-success', 'Feature card deleted.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to delete feature card: ' . $e->getMessage());
        }
    }

    /**
     * Update Team Header.
     */
    public function updateTeamHeader(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        try {
            $this->service->updateTeamHeader($validated);
            return redirect()->back()->with('t-success', 'Team section header updated successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to update team header: ' . $e->getMessage());
        }
    }

    /**
     * Store Team Member.
     */
    public function storeTeamMember(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'sub_title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
        ]);

        try {
            $this->service->storeTeamMember($validated, $request->file('image'));
            return redirect()->back()->with('t-success', 'Team member added successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to add team member: ' . $e->getMessage());
        }
    }

    /**
     * Update Team Member.
     */
    public function updateTeamMember(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'sub_title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
        ]);

        try {
            $this->service->updateTeamMember($id, $validated, $request->file('image'));
            return redirect()->back()->with('t-success', 'Team member updated successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to update team member: ' . $e->getMessage());
        }
    }

    /**
     * Destroy Team Member.
     */
    public function destroyTeamMember(int $id): RedirectResponse
    {
        try {
            $this->service->destroyTeamMember($id);
            return redirect()->back()->with('t-success', 'Team member removed.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to remove team member: ' . $e->getMessage());
        }
    }
}
