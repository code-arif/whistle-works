<?php

namespace App\Services\Admin;

use App\Models\CampPayment;
use App\Models\CampPaymentAttempt;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Director\Models\Camp;

class DashboardService
{
    public const CACHE_KEY = 'admin_v2_dashboard_metrics';
    public const CACHE_TTL = 300; // 5 minutes cache for blazing fast performance

    /**
     * Get compiled dashboard metrics with caching.
     */
    public function getDashboardMetrics(bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            $this->bustCache();
        }

        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return [
                'userStats'       => $this->getUserStats(),
                'revenueStats'    => $this->getRevenueStats(),
                'campStats'       => $this->getCampStats(),
                'gameSlotStats'   => $this->getGameSlotStats(),
                'paymentStats'    => $this->getPaymentStats(),
                'monthlyRevenue'  => $this->getMonthlyRevenue(12),
                'userGrowth'      => $this->getUserGrowth(6),
                'topCamps'        => $this->getTopCampsByRevenue(6),
                'recentPayments'  => $this->getRecentPayments(8),
                'dailyActivities' => $this->getDailyActivities(),
                'systemInfo'      => [
                    'php_version'     => PHP_VERSION,
                    'laravel_version' => app()->version(),
                    'cache_driver'    => config('cache.default'),
                    'last_cached_at'  => Carbon::now()->toIso8601String(),
                ],
            ];
        });
    }

    /**
     * Invalidate dashboard metrics cache.
     */
    public function bustCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * User Statistics.
     */
    public function getUserStats(): array
    {
        $now       = Carbon::now();
        $lastMonth = $now->copy()->subMonth();

        $total    = User::count();
        $active   = User::where('status', 'active')->count();
        $inactive = User::where('status', 'inactive')->count();
        $deleted  = User::onlyTrashed()->count();

        $newThisMonth = User::whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->count();

        $newLastMonth = User::whereMonth('created_at', $lastMonth->month)
            ->whereYear('created_at', $lastMonth->year)
            ->count();

        $growth = $newLastMonth > 0
            ? round((($newThisMonth - $newLastMonth) / $newLastMonth) * 100, 1)
            : 0;

        $roles = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_type', 'App\Models\User')
            ->select('roles.name', DB::raw('COUNT(*) as count'))
            ->groupBy('roles.name')
            ->pluck('count', 'name')
            ->toArray();

        return [
            'total'        => $total,
            'active'       => $active,
            'inactive'     => $inactive,
            'deleted'      => $deleted,
            'newThisMonth' => $newThisMonth,
            'growth'       => $growth,
            'directors'    => (int) ($roles['director'] ?? 0),
            'referees'     => (int) ($roles['referee'] ?? 0),
            'evaluators'   => (int) ($roles['evaluator'] ?? 0),
        ];
    }

    /**
     * Revenue Statistics.
     */
    public function getRevenueStats(): array
    {
        $now       = Carbon::now();
        $lastMonth = $now->copy()->subMonth();

        $totalRevenue        = (float) CampPayment::where('status', 'succeeded')->sum('amount');
        $totalAdminFees      = (float) CampPayment::where('status', 'succeeded')->sum('admin_fee');
        $totalDirectorAmount = (float) CampPayment::where('status', 'succeeded')->sum('director_amount');
        $totalDiscountGiven  = (float) CampPayment::where('status', 'succeeded')->sum('discount_amount');
        $totalTransactions   = CampPayment::where('status', 'succeeded')->count();

        $monthlyRevenue = (float) CampPayment::where('status', 'succeeded')
            ->whereMonth('paid_at', $now->month)
            ->whereYear('paid_at', $now->year)
            ->sum('amount');

        $lastMonthRevenue = (float) CampPayment::where('status', 'succeeded')
            ->whereMonth('paid_at', $lastMonth->month)
            ->whereYear('paid_at', $lastMonth->year)
            ->sum('amount');

        $revenueGrowth = $lastMonthRevenue > 0
            ? round((($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : 0;

        $avgTransactionValue = $totalTransactions > 0
            ? round($totalRevenue / $totalTransactions, 2)
            : 0;

        return [
            'totalRevenue'        => $totalRevenue,
            'totalAdminFees'      => $totalAdminFees,
            'totalDirectorAmount' => $totalDirectorAmount,
            'totalDiscountGiven'  => $totalDiscountGiven,
            'totalTransactions'   => $totalTransactions,
            'monthlyRevenue'      => $monthlyRevenue,
            'revenueGrowth'       => $revenueGrowth,
            'avgTransactionValue' => $avgTransactionValue,
        ];
    }

    /**
     * Camp Statistics.
     */
    public function getCampStats(): array
    {
        $now = Carbon::now();

        $total     = Camp::count();
        $active    = Camp::where('status', 'active')->count();
        $upcoming  = Camp::where('start_date', '>', $now->toDateString())->count();
        $ongoing   = Camp::where('start_date', '<=', $now->toDateString())
            ->where('end_date', '>=', $now->toDateString())
            ->count();
        $completed = Camp::where('end_date', '<', $now->toDateString())->count();

        $totalGameSlots = DB::table('game_slots')->count();
        $totalCourts    = (int) DB::table('schedule_locations')->sum('court_count');

        return [
            'total'          => $total,
            'active'         => $active,
            'upcoming'       => $upcoming,
            'ongoing'        => $ongoing,
            'completed'      => $completed,
            'totalGameSlots' => $totalGameSlots,
            'totalCourts'    => $totalCourts,
        ];
    }

    /**
     * Game Slot Statistics.
     */
    public function getGameSlotStats(): array
    {
        $now = Carbon::now();

        $total     = DB::table('game_slots')->count();
        $assigned  = DB::table('game_slots')->where('status', 'assigned')->count();
        $completed = DB::table('game_slots')->where('status', 'completed')->count();
        $available = DB::table('game_slots')->where('status', 'available')->count();

        $todaySlots = DB::table('game_slots')->whereDate('game_date', $now->toDateString())->count();
        $todayAssigned = DB::table('game_slots')
            ->whereDate('game_date', $now->toDateString())
            ->where('status', 'assigned')
            ->count();

        $utilizationRate = $total > 0
            ? round((($assigned + $completed) / $total) * 100, 1)
            : 0;

        return [
            'total'           => $total,
            'available'       => $available,
            'assigned'        => $assigned,
            'completed'       => $completed,
            'todaySlots'      => $todaySlots,
            'todayAssigned'   => $todayAssigned,
            'utilizationRate' => $utilizationRate,
        ];
    }

    /**
     * Payment Attempt Statistics.
     */
    public function getPaymentStats(): array
    {
        $total   = CampPaymentAttempt::count();
        $success = CampPaymentAttempt::where('status', 'completed')->count();
        $rate    = $total > 0 ? round(($success / $total) * 100, 1) : 0;

        return [
            'total'   => $total,
            'success' => $success,
            'rate'    => $rate,
        ];
    }

    /**
     * Monthly Revenue Trend.
     */
    public function getMonthlyRevenue(int $months = 12): array
    {
        $raw = CampPayment::where('status', 'succeeded')
            ->where('paid_at', '>=', now()->subMonths($months))
            ->select(
                DB::raw("DATE_FORMAT(paid_at, '%Y-%m') as month"),
                DB::raw("COALESCE(SUM(amount), 0) as revenue"),
                DB::raw("COALESCE(SUM(admin_fee), 0) as fees"),
                DB::raw("COUNT(*) as transactions")
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $labels       = [];
        $revenue      = [];
        $fees         = [];
        $transactions = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $m = now()->subMonths($i)->format('Y-m');
            $labels[] = Carbon::createFromFormat('Y-m', $m)->format('M Y');
            $found = $raw->firstWhere('month', $m);
            $revenue[]      = $found ? (float) $found->revenue : 0.0;
            $fees[]         = $found ? (float) $found->fees : 0.0;
            $transactions[] = $found ? (int) $found->transactions : 0;
        }

        return [
            'labels'       => $labels,
            'revenue'      => $revenue,
            'fees'         => $fees,
            'transactions' => $transactions,
        ];
    }

    /**
     * User Growth Trend.
     */
    public function getUserGrowth(int $months = 6): array
    {
        $raw = User::select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subMonths($months))
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $labels = [];
        $data   = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $m = now()->subMonths($i)->format('Y-m');
            $labels[] = Carbon::createFromFormat('Y-m', $m)->format('M Y');
            $data[] = (int) (isset($raw[$m]) ? $raw[$m]->count : 0);
        }

        return [
            'labels' => $labels,
            'data'   => $data,
        ];
    }

    /**
     * Top Camps by Succeeded Revenue.
     */
    public function getTopCampsByRevenue(int $limit = 6): array
    {
        return Camp::select(
                'camps.id',
                'camps.camp_name',
                'camps.location',
                'camps.price',
                'camps.status',
                'camps.sports_type_name',
                DB::raw('COALESCE(SUM(camp_payments.amount), 0) as total_revenue'),
                DB::raw('COUNT(camp_payments.id) as payment_count')
            )
            ->leftJoin('camp_payments', function ($join) {
                $join->on('camps.id', '=', 'camp_payments.camp_id')
                    ->where('camp_payments.status', 'succeeded');
            })
            ->groupBy(
                'camps.id', 'camps.camp_name', 'camps.location',
                'camps.price', 'camps.status', 'camps.sports_type_name'
            )
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Recent Completed Payments.
     */
    public function getRecentPayments(int $limit = 8): array
    {
        return CampPayment::with([
                'camp:id,camp_name,location',
                'referee:id,first_name,last_name,email',
            ])
            ->where('status', 'succeeded')
            ->latest('paid_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Daily Activities Timeline.
     */
    public function getDailyActivities(): array
    {
        $activities = [];

        $recentCamps = Camp::latest()->limit(3)->get(['id', 'camp_name', 'created_at']);
        foreach ($recentCamps as $camp) {
            $activities[] = [
                'id'          => 'camp-' . $camp->id,
                'type'        => 'camp',
                'title'       => 'New Camp Created',
                'description' => $camp->camp_name,
                'time'        => Carbon::parse($camp->created_at)->diffForHumans(),
                'created_at'  => $camp->created_at,
            ];
        }

        $recentPayments = CampPayment::with('camp:id,camp_name')
            ->where('status', 'succeeded')
            ->latest('paid_at')
            ->limit(4)
            ->get();

        foreach ($recentPayments as $payment) {
            $activities[] = [
                'id'          => 'payment-' . $payment->id,
                'type'        => 'payment',
                'title'       => 'Payment Succeeded',
                'description' => ($payment->camp?->camp_name ?? 'Camp') . ' — $' . number_format((float) $payment->amount, 2),
                'time'        => $payment->paid_at ? Carbon::parse($payment->paid_at)->diffForHumans() : '',
                'created_at'  => $payment->paid_at ?? $payment->created_at,
            ];
        }

        usort($activities, function ($a, $b) {
            return strtotime((string) $b['created_at']) - strtotime((string) $a['created_at']);
        });

        return array_slice($activities, 0, 6);
    }
}
