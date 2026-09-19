<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Camp\StoreCampRequest;
use App\Http\Requests\Admin\Camp\UpdateCampRequest;
use App\Services\Admin\CampService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Director\Models\Camp;

class CampController extends Controller
{
    public function __construct(
        protected CampService $service
    ) {}

    /**
     * Display a paginated, searchable, and filterable listing of Camps.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('Camps/Index', [
            'camps'            => $this->service->getCamps($request),
            'filters'          => $this->service->getFilters($request),
            'metrics'          => $this->service->getMetrics(),
            'directors'        => $this->service->getDirectors(),
            'sportsTypes'      => $this->service->getSportsTypes(),
            'googleMapsApiKey' => config('services.google_maps.api_key') ?: env('GOOGLE_MAPS_API_KEY', ''),
        ]);
    }

    /**
     * Store a newly created Camp.
     */
    public function store(StoreCampRequest $request): RedirectResponse
    {
        try {
            $this->service->createCamp($request->validated(), $request->file('camp_logo'));

            return redirect()->back()->with('success', 'Camp created successfully!');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to create camp: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing Camp.
     */
    public function update(UpdateCampRequest $request, int|string $id): RedirectResponse
    {
        try {
            $camp = Camp::findOrFail($id);
            $this->service->updateCamp($camp, $request->validated(), $request->file('camp_logo'));

            return redirect()->back()->with('success', 'Camp updated successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to update camp: ' . $e->getMessage());
        }
    }

    /**
     * Toggle status between active and inactive.
     */
    public function toggleStatus(int|string $id): RedirectResponse
    {
        try {
            $camp   = Camp::findOrFail($id);
            $status = $this->service->toggleStatus($camp);

            return redirect()->back()->with('success', "Camp status updated to {$status}.");
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to toggle status: ' . $e->getMessage());
        }
    }

    /**
     * Delete a Camp and clean up files.
     */
    public function destroy(int|string $id): RedirectResponse
    {
        try {
            $camp = Camp::findOrFail($id);
            $this->service->deleteCamp($camp);

            return redirect()->back()->with('success', 'Camp deleted successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete camp: ' . $e->getMessage());
        }
    }
}
