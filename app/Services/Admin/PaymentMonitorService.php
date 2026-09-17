<?php

namespace App\Services\Admin;

use App\Models\CampPayment;
use App\Models\CampPaymentAttempt;
use App\Models\Coupon;
use Modules\Director\Models\Camp;
use Modules\Director\Models\CampRefereeCheckin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PaymentMonitorService
{
    /**
     * Cache key for dashboard KPI stats.
     */
    public const STATS_CACHE_KEY = 'admin_v2_payment_monitor_stats';

    /**
     * Compute or retrieve cached dashboard statistics.
     */
    public function getStats(): array
    {
        return Cache::remember(self::STATS_CACHE_KEY, 300, function () {
            $totalRevenue = (float) CampPayment::where('status', 'succeeded')->sum('amount');
            $totalAdminFees = (float) CampPayment::where('status', 'succeeded')->sum('admin_fee');
            $totalDirectorAmount = (float) CampPayment::where('status', 'succeeded')->sum('director_amount');
            $totalDiscountGiven = (float) CampPayment::where('status', 'succeeded')->sum('discount_amount');

            $transactionCount = CampPayment::where('status', 'succeeded')->count();
            $pendingAttempts = CampPaymentAttempt::where('status', 'pending')->count();
            $failedAttempts = CampPaymentAttempt::where('status', 'failed')->count();

            $activeCoupons = Coupon::where('status', 'active')->count();
            $totalRegistrations = CampRefereeCheckin::count();
            $totalCheckedIn = CampRefereeCheckin::where('registration_status', 'checked_in')->count();

            $totalCamps = Camp::count();
            $activeCamps = Camp::where('status', 'active')->count();

            return [
                'totalRevenue'        => $totalRevenue,
                'totalAdminFees'     => $totalAdminFees,
                'totalDirectorAmount'=> $totalDirectorAmount,
                'totalDiscountGiven' => $totalDiscountGiven,
                'transactionCount'   => $transactionCount,
                'pendingAttempts'    => $pendingAttempts,
                'failedAttempts'     => $failedAttempts,
                'activeCoupons'      => $activeCoupons,
                'totalRegistrations' => $totalRegistrations,
                'totalCheckedIn'     => $totalCheckedIn,
                'totalCamps'         => $totalCamps,
                'activeCamps'        => $activeCamps,
            ];
        });
    }

    /**
     * Clear metric cache.
     */
    public function clearStatsCache(): void
    {
        Cache::forget(self::STATS_CACHE_KEY);
    }

    /**
     * Get revenue chart data grouped by month.
     *
     * @param int $months Number of months to look back (6, 12, or 24).
     */
    public function getRevenueChartData(int $months = 12): array
    {
        $months = max(6, min(24, $months));

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

        // Pad with zeros for months without transaction records
        for ($i = $months - 1; $i >= 0; $i--) {
            $m = now()->subMonths($i)->format('Y-m');
            $monthLabels[] = $m;
            $found = $raw->firstWhere('month', $m);
            $revenue[] = $found ? (float) $found->revenue : 0.0;
            $fees[] = $found ? (float) $found->fees : 0.0;
            $transactions[] = $found ? (int) $found->transactions : 0;
        }

        return [
            'monthLabels'  => $monthLabels,
            'revenue'      => $revenue,
            'fees'         => $fees,
            'transactions' => $transactions,
            'selectedMonths' => $months,
        ];
    }

    /**
     * Get top used coupons for summary widget.
     */
    public function getTopCoupons(int $limit = 5): array
    {
        return Coupon::where('used_count', '>', 0)
            ->orderBy('used_count', 'desc')
            ->take($limit)
            ->get(['id', 'code', 'type', 'discount_value', 'used_count'])
            ->toArray();
    }

    /**
     * Paginated Successful Payments
     */
    public function getPayments(Request $request): LengthAwarePaginator
    {
        $search = trim((string) $request->input('search', ''));
        $perPage = max(10, min(100, (int) $request->input('per_page', 15)));

        $query = CampPayment::with(['camp:id,camp_name', 'referee:id,first_name,last_name,email', 'coupon:id,code'])
            ->where('status', 'succeeded');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereHas('camp', fn($cq) => $cq->where('camp_name', 'like', "%{$search}%"))
                  ->orWhereHas('referee', fn($rq) => $rq->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%"))
                  ->orWhereHas('coupon', fn($coq) => $coq->where('code', 'like', "%{$search}%"))
                  ->orWhere('stripe_payment_id', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('paid_at', 'desc')->paginate($perPage)->withQueryString();
    }

    /**
     * Paginated Payment Attempts
     */
    public function getAttempts(Request $request): LengthAwarePaginator
    {
        $search = trim((string) $request->input('search', ''));
        $status = trim((string) $request->input('status', ''));
        $perPage = max(10, min(100, (int) $request->input('per_page', 15)));

        $query = CampPaymentAttempt::with(['camp:id,camp_name', 'referee:id,first_name,last_name,email', 'coupon:id,code']);

        if ($status !== '' && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereHas('camp', fn($cq) => $cq->where('camp_name', 'like', "%{$search}%"))
                  ->orWhereHas('referee', fn($rq) => $rq->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%"))
                  ->orWhereHas('coupon', fn($coq) => $coq->where('code', 'like', "%{$search}%"));
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();
    }

    /**
     * Paginated Referee Registrations / Check-ins
     */
    public function getRegistrations(Request $request): LengthAwarePaginator
    {
        $search = trim((string) $request->input('search', ''));
        $status = trim((string) $request->input('status', ''));
        $perPage = max(10, min(100, (int) $request->input('per_page', 15)));

        $query = CampRefereeCheckin::with(['camp:id,camp_name', 'referee:id,first_name,last_name,email']);

        if ($status !== '' && $status !== 'all') {
            $query->where('registration_status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereHas('camp', fn($cq) => $cq->where('camp_name', 'like', "%{$search}%"))
                  ->orWhereHas('referee', fn($rq) => $rq->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();
    }

    /**
     * Paginated Coupon Usage Log
     */
    public function getCoupons(Request $request): LengthAwarePaginator
    {
        $search = trim((string) $request->input('search', ''));
        $status = trim((string) $request->input('status', ''));
        $perPage = max(10, min(100, (int) $request->input('per_page', 15)));

        $query = Coupon::query()
            ->withSum(['campPayments as revenue_generated' => fn($q) => $q->where('status', 'succeeded')], 'amount');

        if ($status !== '' && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('used_count', 'desc')->paginate($perPage)->withQueryString();
    }
}
