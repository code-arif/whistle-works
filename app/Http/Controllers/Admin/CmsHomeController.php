<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\CmsHomeService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CmsHomeController extends Controller
{
    public function __construct(
        protected CmsHomeService $service
    ) {}

    /**
     * Display the Home Page CMS management screen.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('CMS/Home/Index', [
            'data' => $this->service->getHomePageData(),
            'activeTab' => $request->query('tab', 'hero'),
        ]);
    }

    /**
     * Update Hero Section.
     */
    public function updateHero(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        try {
            $this->service->updateHero($validated);
            return redirect()->back()->with('t-success', 'Hero section updated successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to update hero section: ' . $e->getMessage());
        }
    }

    /**
     * Update Training Camp Section.
     */
    public function updateTrainingCamp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        try {
            $this->service->updateTrainingCamp($validated);
            return redirect()->back()->with('t-success', 'Training Camp section updated successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to update Training Camp section: ' . $e->getMessage());
        }
    }

    /**
     * Update Operations Section.
     */
    public function updateOperations(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        try {
            $this->service->updateOperations($validated);
            return redirect()->back()->with('t-success', 'Operations section updated successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to update Operations section: ' . $e->getMessage());
        }
    }

    /**
     * Update Partner Header.
     */
    public function updatePartnerHeader(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        try {
            $this->service->updatePartnerHeader($validated);
            return redirect()->back()->with('t-success', 'Partner section header updated successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to update Partner header: ' . $e->getMessage());
        }
    }

    /**
     * Store new Partner / Slider logo.
     */
    public function storeSlider(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:5120'],
            'link' => ['nullable', 'url', 'max:255'],
            'status' => ['nullable', 'boolean'],
        ]);

        try {
            $this->service->storeSlider($validated, $request->file('image'));
            return redirect()->back()->with('t-success', 'Partner logo added successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to add partner logo: ' . $e->getMessage());
        }
    }

    /**
     * Update Partner / Slider logo.
     */
    public function updateSlider(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:5120'],
            'link' => ['nullable', 'url', 'max:255'],
            'status' => ['nullable', 'boolean'],
        ]);

        try {
            $this->service->updateSlider($id, $validated, $request->file('image'));
            return redirect()->back()->with('t-success', 'Partner logo updated successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to update partner logo: ' . $e->getMessage());
        }
    }

    /**
     * Toggle Slider Status.
     */
    public function toggleSliderStatus(int $id): RedirectResponse
    {
        try {
            $this->service->toggleSliderStatus($id);
            return redirect()->back()->with('t-success', 'Partner status updated.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to toggle status: ' . $e->getMessage());
        }
    }

    /**
     * Destroy Partner / Slider logo.
     */
    public function destroySlider(int $id): RedirectResponse
    {
        try {
            $this->service->destroySlider($id);
            return redirect()->back()->with('t-success', 'Partner logo removed.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to remove partner logo: ' . $e->getMessage());
        }
    }

    /**
     * Update Feature Section Header.
     */
    public function updateFeatureHeader(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        try {
            $this->service->updateFeatureHeader($validated);
            return redirect()->back()->with('t-success', 'Features header updated successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to update Features header: ' . $e->getMessage());
        }
    }

    /**
     * Store Feature Card.
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
     * Update Feature Card.
     */
    public function updateFeatureCard(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
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
     * Destroy Feature Card.
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
     * Update Testimonial Header.
     */
    public function updateTestimonialHeader(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        try {
            $this->service->updateTestimonialHeader($validated);
            return redirect()->back()->with('t-success', 'Testimonials header updated successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to update Testimonials header: ' . $e->getMessage());
        }
    }

    /**
     * Store Testimonial Review.
     */
    public function storeTestimonialCard(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'author_name' => ['required', 'string', 'max:100'],
            'designation' => ['nullable', 'string', 'max:100'],
            'review_text' => ['nullable', 'string'],
            'author_avatar' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
        ]);

        try {
            $this->service->storeTestimonialCard($validated, $request->file('author_avatar'));
            return redirect()->back()->with('t-success', 'Testimonial added successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to create testimonial: ' . $e->getMessage());
        }
    }

    /**
     * Update Testimonial Review.
     */
    public function updateTestimonialCard(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'author_name' => ['required', 'string', 'max:100'],
            'designation' => ['nullable', 'string', 'max:100'],
            'review_text' => ['nullable', 'string'],
            'author_avatar' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
        ]);

        try {
            $this->service->updateTestimonialCard($id, $validated, $request->file('author_avatar'));
            return redirect()->back()->with('t-success', 'Testimonial updated successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to update testimonial: ' . $e->getMessage());
        }
    }

    /**
     * Destroy Testimonial Review.
     */
    public function destroyTestimonialCard(int $id): RedirectResponse
    {
        try {
            $this->service->destroyTestimonialCard($id);
            return redirect()->back()->with('t-success', 'Testimonial removed.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to delete testimonial: ' . $e->getMessage());
        }
    }
}
