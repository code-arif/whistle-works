<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $service
    ) {}

    /**
     * Display the Executive V2 Dashboard.
     */
    public function index(Request $request): Response
    {
        try {
            $forceRefresh  = $request->boolean('refresh');
            $dashboardData = $this->service->getDashboardMetrics($forceRefresh);

            return Inertia::render('Dashboard/Index', $dashboardData);
        } catch (Exception $e) {
            Log::error('V2 Dashboard Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return Inertia::render('Dashboard/Index', [
                'error' => 'Unable to load dashboard data: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Bust the cache and reload fresh metrics instantly.
     */
    public function refreshMetrics(): RedirectResponse
    {
        $this->service->bustCache();

        return redirect()->back()->with('t-success', 'Dashboard metrics refreshed successfully.');
    }
}
