<?php

namespace App\Http\Controllers\Web\Backend\Monitor;

use App\Models\Coupon;
use App\Models\CampPayment;
use App\Models\CampPaymentAttempt;
use App\Http\Controllers\Controller;
use Modules\Director\Models\Camp;
use Modules\Director\Models\CampRefereeCheckin;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\DB;

class PaymentMonitorController extends Controller
{
    /**
     * Display the payment monitoring dashboard.
     */
    public function index(Request $request)
    {
        if ($request->ajax() && $request->has('type')) {
            $type = $request->type;

            if ($type === 'payments') {
                return $this->paymentsDataTable($request);
            }
            if ($type === 'attempts') {
                return $this->attemptsDataTable($request);
            }
            if ($type === 'registrations') {
                return $this->registrationsDataTable($request);
            }
            if ($type === 'coupons') {
                return $this->couponsDataTable($request);
            }
        }

        // Stats
        $stats = $this->getStats();

        // Revenue Chart Data
        $chartMonths = (int) ($request->input('months', 12));
        $chartMonths = max(6, min(24, $chartMonths)); // clamp between 6 and 24
        $revenueChart = $this->getRevenueChartData($chartMonths);

        // Top Coupons
        $topCoupons = Coupon::where('used_count', '>', 0)
            ->orderBy('used_count', 'desc')
            ->take(5)
            ->get();

        // AJAX: return chart data as JSON for inline updates
        if ($request->ajax() && $request->has('type') && $request->type === 'chart') {
            return response()->json($revenueChart);
        }

        // AJAX: return overview data (stats + topCoupons) for refresh
        if ($request->ajax() && $request->has('type') && $request->type === 'overview') {
            $topCouponsArr = $topCoupons->map(fn($c) => [
                'code'       => $c->code,
                'used_count' => $c->used_count,
            ]);
            return response()->json([
                'stats'      => $stats,
                'topCoupons' => $topCouponsArr,
            ]);
        }

        return view('backend.layouts.monitor.index', compact(
            'stats',
            'revenueChart',
            'topCoupons'
        ));
    }

    /**
     * Compute dashboard statistics.
     */
    private function getStats(): array
    {
        $totalRevenue = CampPayment::where('status', 'succeeded')->sum('amount');
        $totalAdminFees = CampPayment::where('status', 'succeeded')->sum('admin_fee');
        $totalDirectorAmount = CampPayment::where('status', 'succeeded')->sum('director_amount');
        $totalDiscountGiven = CampPayment::where('status', 'succeeded')->sum('discount_amount');

        $transactionCount = CampPayment::where('status', 'succeeded')->count();
        $pendingAttempts = CampPaymentAttempt::where('status', 'pending')->count();
        $failedAttempts = CampPaymentAttempt::where('status', 'failed')->count();

        $activeCoupons = Coupon::where('status', 'active')->count();
        $totalRegistrations = CampRefereeCheckin::count();
        $totalCheckedIn = CampRefereeCheckin::where('registration_status', 'checked_in')->count();

        $totalCamps = Camp::count();
        $activeCamps = Camp::where('status', 'active')->count();

        return compact(
            'totalRevenue',
            'totalAdminFees',
            'totalDirectorAmount',
            'totalDiscountGiven',
            'transactionCount',
            'pendingAttempts',
            'failedAttempts',
            'activeCoupons',
            'totalRegistrations',
            'totalCheckedIn',
            'totalCamps',
            'activeCamps'
        );
    }

    /**
     * Get revenue chart data grouped by month.
     *
     * @param int $months Number of months to look back (default 12).
     */
    private function getRevenueChartData(int $months = 12): array
    {
        $raw = CampPayment::where('status', 'succeeded')
            ->where('paid_at', '>=', now()->subMonths($months))
            ->select(
                DB::raw("DATE_FORMAT(paid_at, '%Y-%m') as month"),
                DB::raw("SUM(amount) as revenue"),
                DB::raw("SUM(admin_fee) as fees"),
                DB::raw("SUM(director_amount) as director_share"),
                DB::raw("COUNT(*) as transactions")
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $monthLabels = [];
        $revenue = [];
        $fees = [];
        $transactions = [];

        // Pad with zeros for months with no data
        for ($i = $months - 1; $i >= 0; $i--) {
            $m = now()->subMonths($i)->format('Y-m');
            $monthLabels[] = $m;
            $found = $raw->firstWhere('month', $m);
            $revenue[] = $found ? (float) $found->revenue : 0;
            $fees[] = $found ? (float) $found->fees : 0;
            $transactions[] = $found ? (int) $found->transactions : 0;
        }

        return compact('monthLabels', 'revenue', 'fees', 'transactions');
    }

    /**
     * DataTable: Successful Payments
     */
    private function paymentsDataTable(Request $request)
    {
        $query = CampPayment::with(['camp', 'referee', 'coupon'])
            ->where('status', 'succeeded')
            ->orderBy('paid_at', 'desc');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('camp', fn($d) => $d->camp?->camp_name ?? 'N/A')
            ->filterColumn('camp', function ($query, $keyword) {
                $query->whereHas('camp', fn($q) => $q->where('camp_name', 'like', "%{$keyword}%"));
            })
            ->addColumn('referee', fn($d) => $d->referee?->first_name . ' ' . $d->referee?->last_name)
            ->filterColumn('referee', function ($query, $keyword) {
                $query->whereHas('referee', fn($q) => $q->where('first_name', 'like', "%{$keyword}%")
                    ->orWhere('last_name', 'like', "%{$keyword}%"));
            })
            ->addColumn('amount', fn($d) => '$' . number_format($d->amount, 2))
            ->addColumn('admin_fee', fn($d) => '$' . number_format($d->admin_fee, 2))
            ->addColumn('director_amount', fn($d) => '$' . number_format($d->director_amount, 2))
            ->addColumn('discount', fn($d) => $d->discount_amount > 0 ? '$' . number_format($d->discount_amount, 2) : '-')
            ->addColumn('coupon', fn($d) => $d->coupon?->code ?? '-')
            ->filterColumn('coupon', function ($query, $keyword) {
                $query->whereHas('coupon', fn($q) => $q->where('code', 'like', "%{$keyword}%"));
            })
            ->addColumn('paid_at', fn($d) => $d->paid_at?->format('Y-m-d H:i') ?? '-')
            ->rawColumns([])
            ->make();
    }

    /**
     * DataTable: Payment Attempts (all statuses)
     */
    private function attemptsDataTable(Request $request)
    {
        $query = CampPaymentAttempt::with(['camp', 'referee', 'coupon'])
            ->orderBy('created_at', 'desc');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('camp', fn($d) => $d->camp?->camp_name ?? 'N/A')
            ->filterColumn('camp', function ($query, $keyword) {
                $query->whereHas('camp', fn($q) => $q->where('camp_name', 'like', "%{$keyword}%"));
            })
            ->addColumn('referee', fn($d) => $d->referee?->first_name . ' ' . $d->referee?->last_name)
            ->filterColumn('referee', function ($query, $keyword) {
                $query->whereHas('referee', fn($q) => $q->where('first_name', 'like', "%{$keyword}%")
                    ->orWhere('last_name', 'like', "%{$keyword}%"));
            })
            ->addColumn('amount', fn($d) => '$' . number_format($d->amount, 2))
            ->addColumn('status', function ($d) {
                $map = [
                    'pending'   => 'badge bg-warning',
                    'completed' => 'badge bg-success',
                    'failed'    => 'badge bg-danger',
                    'cancelled' => 'badge bg-secondary',
                ];
                $class = $map[$d->status] ?? 'badge bg-light';
                return '<span class="' . $class . '">' . ucfirst($d->status) . '</span>';
            })
            ->addColumn('coupon', fn($d) => $d->coupon?->code ?? '-')
            ->filterColumn('coupon', function ($query, $keyword) {
                $query->whereHas('coupon', fn($q) => $q->where('code', 'like', "%{$keyword}%"));
            })
            ->addColumn('created_at', fn($d) => $d->created_at->format('Y-m-d H:i'))
            ->rawColumns(['status'])
            ->make();
    }

    /**
     * DataTable: Referee Registrations / Check-ins
     */
    private function registrationsDataTable(Request $request)
    {
        $query = CampRefereeCheckin::with(['camp', 'referee'])
            ->orderBy('created_at', 'desc');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('camp', fn($d) => $d->camp?->camp_name ?? 'N/A')
            ->filterColumn('camp', function ($query, $keyword) {
                $query->whereHas('camp', fn($q) => $q->where('camp_name', 'like', "%{$keyword}%"));
            })
            ->addColumn('referee', fn($d) => $d->referee?->first_name . ' ' . $d->referee?->last_name)
            ->filterColumn('referee', function ($query, $keyword) {
                $query->whereHas('referee', fn($q) => $q->where('first_name', 'like', "%{$keyword}%")
                    ->orWhere('last_name', 'like', "%{$keyword}%"));
            })
            ->addColumn('status', function ($d) {
                $class = $d->registration_status === 'checked_in' ? 'badge bg-success' : 'badge bg-info';
                return '<span class="' . $class . '">' . ucfirst(str_replace('_', ' ', $d->registration_status)) . '</span>';
            })
            ->addColumn('registered_at', fn($d) => $d->registered_at?->format('Y-m-d H:i') ?? '-')
            ->addColumn('checked_in_at', fn($d) => $d->checked_in_at?->format('Y-m-d H:i') ?? '-')
            ->rawColumns(['status'])
            ->make();
    }

    /**
     * DataTable: Coupon Usage
     */
    private function couponsDataTable(Request $request)
    {
        // Coupons with usage stats, including revenue from payments using them
        $query = Coupon::query()
            ->withSum(['campPayments as revenue_generated' => fn($q) => $q->where('status', 'succeeded')], 'amount')
            ->orderBy('used_count', 'desc');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('code', fn($d) => '<strong>' . e($d->code) . '</strong>')
            ->addColumn('type', fn($d) => $d->type === 'percentage' ? $d->discount_value . '%' : '$' . number_format($d->discount_value, 2))
            ->filterColumn('type', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('type', 'like', "%{$keyword}%")
                      ->orWhere('discount_value', 'like', "%{$keyword}%");
                });
            })
            ->addColumn('usage', fn($d) => $d->used_count . ' / ' . ($d->max_uses ?? '∞'))
            ->filterColumn('usage', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('used_count', 'like', "%{$keyword}%")
                      ->orWhere('max_uses', 'like', "%{$keyword}%");
                });
            })
            ->addColumn('revenue', fn($d) => '$' . number_format($d->revenue_generated ?? 0, 2))
            ->filterColumn('revenue', function ($query, $keyword) {
                $query->whereHas('campPayments', fn($q) => $q->where('amount', 'like', "%{$keyword}%")->where('status', 'succeeded'));
            })
            ->addColumn('status', function ($d) {
                $class = $d->status === 'active' ? 'badge bg-success' : 'badge bg-secondary';
                return '<span class="' . $class . '">' . ucfirst($d->status) . '</span>';
            })
            ->addColumn('expires', fn($d) => $d->expires_at ? $d->expires_at->format('Y-m-d') : 'Never')
            ->rawColumns(['code', 'status'])
            ->make();
    }
}
