<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SportsType\StoreSportsTypeRequest;
use App\Http\Requests\Admin\SportsType\UpdateSportsTypeRequest;
use App\Models\SportsType;
use App\Services\Admin\SportsTypeService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SportsTypeController extends Controller
{
    public function __construct(
        protected SportsTypeService $service
    ) {}

    /**
     * Display a paginated, searchable, and sortable listing of Sports Types.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('SportsType/Index', [
            'sportsTypes' => $this->service->getSportsTypes($request),
            'filters'     => $this->service->getFilters($request),
            'metrics'     => $this->service->getMetrics(),
        ]);
    }

    /**
     * Store a newly created Sports Type.
     */
    public function store(StoreSportsTypeRequest $request): RedirectResponse
    {
        try {
            $this->service->createSportsType($request->validated(), $request->file('icon'));

            return redirect()->back()->with('success', 'Sports type created successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to create sports type: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing Sports Type.
     */
    public function update(UpdateSportsTypeRequest $request, int|string $id): RedirectResponse
    {
        try {
            $sportsType = SportsType::findOrFail($id);
            $this->service->updateSportsType($sportsType, $request->validated(), $request->file('icon'));

            return redirect()->back()->with('success', 'Sports type updated successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to update sports type: ' . $e->getMessage());
        }
    }

    /**
     * Toggle status between active and inactive.
     */
    public function toggleStatus(int|string $id): RedirectResponse
    {
        try {
            $sportsType = SportsType::findOrFail($id);
            $status     = $this->service->toggleStatus($sportsType);

            return redirect()->back()->with('success', "Status updated to {$status}.");
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to toggle status: ' . $e->getMessage());
        }
    }

    /**
     * Delete a Sports Type with foreign key check.
     */
    public function destroy(int|string $id): RedirectResponse
    {
        try {
            $sportsType = SportsType::findOrFail($id);
            $result     = $this->service->deleteSportsType($sportsType);

            if (!$result['success']) {
                return redirect()->back()->with('error', $result['message']);
            }

            return redirect()->back()->with('success', $result['message']);
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete sports type: ' . $e->getMessage());
        }
    }
}
