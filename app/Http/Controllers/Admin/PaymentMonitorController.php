<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\PaymentMonitorService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentMonitorController extends Controller
{
    public function __construct(
        protected PaymentMonitorService $service
    ) {}

    /**
     * Display the V2 Payment Monitor Dashboard.
     */
    public function index(Request $request): Response
    {
        $activeTab = $request->input('tab', 'payments');
        if (!in_array($activeTab, ['payments', 'attempts', 'registrations', 'coupons'], true)) {
            $activeTab = 'payments';
        }

        $chartMonths = (int) $request->input('months', 12);
        $stats = $this->service->getStats();
        $revenueChart = $this->service->getRevenueChartData($chartMonths);
        $topCoupons = $this->service->getTopCoupons(5);

        $tableData = match ($activeTab) {
            'attempts'      => $this->service->getAttempts($request),
            'registrations' => $this->service->getRegistrations($request),
            'coupons'       => $this->service->getCoupons($request),
            default         => $this->service->getPayments($request),
        };

        return Inertia::render('Monitor/Payments/Index', [
            'stats'        => $stats,
            'revenueChart' => $revenueChart,
            'topCoupons'   => $topCoupons,
            'activeTab'    => $activeTab,
            'tableData'    => $tableData,
            'filters'      => $request->only(['search', 'status', 'months', 'tab', 'per_page']),
        ]);
    }

    /**
     * Cache-burst endpoint to force refresh metrics and stats.
     */
    public function refresh(Request $request)
    {
        $this->service->clearStatsCache();
        $stats = $this->service->getStats();
        $topCoupons = $this->service->getTopCoupons(5);

        if ($request->wantsJson()) {
            return response()->json([
                'status'     => 'success',
                'message'    => 'Payment metrics cache refreshed successfully.',
                'stats'      => $stats,
                'topCoupons' => $topCoupons,
            ]);
        }

        return redirect()->back()->with('t-success', 'Payment monitor stats refreshed successfully.');
    }
}
