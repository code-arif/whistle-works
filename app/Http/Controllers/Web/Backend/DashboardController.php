<?php

namespace App\Http\Controllers\Web\Backend;

use App\Models\CampPayment;
use App\Models\CampPaymentAttempt;
use App\Models\Coupon;
use App\Models\User;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Director\Models\Camp;
use Modules\Director\Models\CampRefereeCheckin;
use Modules\Director\Models\Crew;
use Modules\Director\Models\GameSlotAssignment;
use Modules\Director\Models\Schedule;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard with comprehensive business intelligence.
     */
    public function index()
    {
        try {
            $data = [
                'userStats'          => $this->getUserStats(),
                'revenueStats'       => $this->getRevenueStats(),
                'campStats'          => $this->getCampStats(),
                'gameSlotStats'      => $this->getGameSlotStats(),
                'scheduleStats'      => $this->getScheduleStats(),
                'crewStats'          => $this->getCrewStats(),
                'sportsStats'        => $this->getSportsStats(),
                'paymentStats'       => $this->getPaymentStats(),
                'couponStats'        => $this->getCouponStats(),
                'refereeStats'       => $this->getRefereeStats(),
                'monthlyRevenue'     => $this->getMonthlyRevenue(12),
                'monthlyCamps'       => $this->getMonthlyCamps(),
                'topCamps'           => $this->getTopCampsByRevenue(10),
                'recentPayments'     => $this->getRecentPayments(10),
                'dailyActivities'    => $this->getDailyActivities(),
                'userGrowth'         => $this->getUserGrowth(6),
                'paymentSuccessRate' => $this->getPaymentSuccessRate(),
            ];

            return view('backend.layouts.dashboard', $data);
        } catch (Exception $e) {
            Log::error('Dashboard Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('t-error', 'Dashboard loading failed: ' . $e->getMessage());
        }
    }

    // ──────────────────────────────────────────────
    //  USER STATISTICS
    // ──────────────────────────────────────────────

    private function getUserStats(): array
    {
        $now = Carbon::now();
        $lastMonth = $now->copy()->subMonth();

        $total = User::count();
        $active = User::where('status', 'active')->count();
        $inactive = User::where('status', 'inactive')->count();
        $deleted = User::onlyTrashed()->count();

        $newThisMonth = User::whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->count();

        $newLastMonth = User::whereMonth('created_at', $lastMonth->month)
            ->whereYear('created_at', $lastMonth->year)
            ->count();

        $growth = $newLastMonth > 0
            ? round((($newThisMonth - $newLastMonth) / $newLastMonth) * 100, 1)
            : 0;

        // Role distribution
        $roles = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_type', 'App\Models\User')
            ->select('roles.name', DB::raw('COUNT(*) as count'))
            ->groupBy('roles.name')
            ->pluck('count', 'name')
            ->toArray();

        $directors  = (int) ($roles['director'] ?? 0);
        $referees   = (int) ($roles['referee'] ?? 0);
        $evaluators = (int) ($roles['evaluator'] ?? 0);

        // Count admins as users with NO specific role (not director/referee/evaluator)
        $roleUserIds = DB::table('model_has_roles')
            ->where('model_type', 'App\\Models\\User')
            ->distinct()
            ->pluck('model_id')
            ->toArray();
        $admins = User::whereNotIn('id', $roleUserIds)->count();

        return compact(
            'total', 'active', 'inactive', 'deleted',
            'newThisMonth', 'newLastMonth', 'growth',
            'directors', 'referees', 'evaluators', 'admins'
        );
    }

    // ──────────────────────────────────────────────
    //  REVENUE & FINANCIAL STATISTICS
    // ──────────────────────────────────────────────

    private function getRevenueStats(): array
    {
        $now = Carbon::now();
        $lastMonth = $now->copy()->subMonth();
        $campPayment = CampPayment::where('status', 'succeeded');

        // Aggregate from successful payments
        $totalRevenue = $campPayment->sum('amount');
        $totalAdminFees = $campPayment->sum('admin_fee');
        $totalDirectorAmount = $campPayment->sum('director_amount');
        $totalDiscountGiven = $campPayment->sum('discount_amount');
        $totalTransactions = $campPayment->count();

        // Monthly
        $monthlyRevenue = $campPayment
            ->whereMonth('paid_at', $now->month)
            ->whereYear('paid_at', $now->year)
            ->sum('amount');

        $monthlyAdminFees = $campPayment
            ->whereMonth('paid_at', $now->month)
            ->whereYear('paid_at', $now->year)
            ->sum('admin_fee');

        $monthlyTransactions = $campPayment
            ->whereMonth('paid_at', $now->month)
            ->whereYear('paid_at', $now->year)
            ->count();

        // Last month
        $lastMonthRevenue = $campPayment
            ->whereMonth('paid_at', $lastMonth->month)
            ->whereYear('paid_at', $lastMonth->year)
            ->sum('amount');

        $revenueGrowth = $lastMonthRevenue > 0
            ? round((($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : 0;

        // Average transaction value
        $avgTransactionValue = $totalTransactions > 0
            ? round($totalRevenue / $totalTransactions, 2)
            : 0;

        return compact(
            'totalRevenue', 'totalAdminFees', 'totalDirectorAmount',
            'totalDiscountGiven', 'totalTransactions',
            'monthlyRevenue', 'monthlyAdminFees', 'monthlyTransactions',
            'lastMonthRevenue', 'revenueGrowth', 'avgTransactionValue'
        );
    }

    // ──────────────────────────────────────────────
    //  CAMP STATISTICS
    // ──────────────────────────────────────────────

    private function getCampStats(): array
    {
        $now = Carbon::now();
        $lastMonth = $now->copy()->subMonth();

        $total    = Camp::count();
        $active   = Camp::where('status', 'active')->count();
        $inactive = Camp::where('status', 'inactive')->count();

        $upcoming = Camp::where('start_date', '>', $now->toDateString())->count();
        $ongoing  = Camp::where('start_date', '<=', $now->toDateString())
            ->where('end_date', '>=', $now->toDateString())
            ->count();
        $completed = Camp::where('end_date', '<', $now->toDateString())->count();

        $thisMonth = Camp::whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->count();

        $lastMonthCount = Camp::whereMonth('created_at', $lastMonth->month)
            ->whereYear('created_at', $lastMonth->year)
            ->count();

        $growth = $lastMonthCount > 0
            ? round((($thisMonth - $lastMonthCount) / $lastMonthCount) * 100, 1)
            : 0;

        // Total camp capacity (sum of game slots)
        $totalGameSlots = DB::table('game_slots')->count();
        $totalCourts    = DB::table('schedule_locations')->sum('court_count');

        return compact(
            'total', 'active', 'inactive',
            'upcoming', 'ongoing', 'completed',
            'thisMonth', 'lastMonthCount', 'growth',
            'totalGameSlots', 'totalCourts'
        );
    }

    // ──────────────────────────────────────────────
    //  GAME SLOT STATISTICS
    // ──────────────────────────────────────────────

    private function getGameSlotStats(): array
    {
        $now = Carbon::now();

        $total     = DB::table('game_slots')->count();
        $available = DB::table('game_slots')->where('status', 'available')->count();
        $assigned  = DB::table('game_slots')->where('status', 'assigned')->count();
        $completed = DB::table('game_slots')->where('status', 'completed')->count();
        $blocked   = DB::table('game_slots')->where('is_block', true)->count();

        // Total assignments
        $totalAssignments = GameSlotAssignment::count();
        $autoAssignments  = GameSlotAssignment::where('is_auto_assigned', true)->count();
        $manualAssignments = $totalAssignments - $autoAssignments;

        // Today's slots
        $todaySlots = DB::table('game_slots')->whereDate('game_date', $now->toDateString())->count();
        $todayAssigned = DB::table('game_slots')
            ->whereDate('game_date', $now->toDateString())
            ->where('status', 'assigned')
            ->count();

        // Utilization rate
        $utilizationRate = $total > 0
            ? round((($assigned + $completed) / $total) * 100, 1)
            : 0;

        return compact(
            'total', 'available', 'assigned', 'completed', 'blocked',
            'totalAssignments', 'autoAssignments', 'manualAssignments',
            'todaySlots', 'todayAssigned', 'utilizationRate'
        );
    }

    // ──────────────────────────────────────────────
    //  SCHEDULE STATISTICS
    // ──────────────────────────────────────────────

    private function getScheduleStats(): array
    {
        $total     = Schedule::count();
        $published = Schedule::where('status', 'published')->count();
        $draft     = Schedule::where('status', 'draft')->count();

        $avgGameDuration = Schedule::where('status', 'published')->avg('game_duration');

        return compact('total', 'published', 'draft', 'avgGameDuration');
    }

    // ──────────────────────────────────────────────
    //  CREW STATISTICS
    // ──────────────────────────────────────────────

    private function getCrewStats(): array
    {
        $total   = Crew::count();
        $active  = Crew::where('status', 'active')->count();

        $totalMembers = DB::table('crew_members')->count();
        $avgMembers   = $total > 0 ? round($totalMembers / $total, 1) : 0;

        // Top 5 largest crews
        $largestCrews = Crew::select('crews.*', DB::raw('COUNT(crew_members.id) as members_count'))
            ->leftJoin('crew_members', 'crews.id', '=', 'crew_members.crew_id')
            ->groupBy('crews.id', 'crews.camp_id', 'crews.name', 'crews.description', 'crews.status', 'crews.created_at', 'crews.updated_at')
            ->orderByDesc('members_count')
            ->limit(5)
            ->get();

        return compact('total', 'active', 'totalMembers', 'avgMembers', 'largestCrews');
    }

    // ──────────────────────────────────────────────
    //  SPORTS TYPE STATISTICS
    // ──────────────────────────────────────────────

    private function getSportsStats(): array
    {
        $total   = DB::table('sports_types')->count();
        $active  = DB::table('sports_types')->where('status', 'active')->count();
        $inactive = DB::table('sports_types')->where('status', 'inactive')->count();

        $campsBySport = DB::table('camps')
            ->select('sports_type_name', DB::raw('count(*) as total'))
            ->whereNotNull('sports_type_name')
            ->groupBy('sports_type_name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();


        return compact('total', 'active', 'inactive', 'campsBySport');
    }

    // ──────────────────────────────────────────────
    //  PAYMENT STATISTICS
    // ──────────────────────────────────────────────

    private function getPaymentStats(): array
    {
        $totalAttempts     = CampPaymentAttempt::count();
        $pendingAttempts   = CampPaymentAttempt::where('status', 'pending')->count();
        $completedAttempts = CampPaymentAttempt::where('status', 'completed')->count();
        $failedAttempts    = CampPaymentAttempt::where('status', 'failed')->count();
        $cancelledAttempts = CampPaymentAttempt::where('status', 'cancelled')->count();

        return compact(
            'totalAttempts', 'pendingAttempts', 'completedAttempts',
            'failedAttempts', 'cancelledAttempts'
        );
    }

    // ──────────────────────────────────────────────
    //  COUPON STATISTICS
    // ──────────────────────────────────────────────

    private function getCouponStats(): array
    {
        $total     = Coupon::count();
        $active    = Coupon::where('status', 'active')->count();
        $inactive  = Coupon::where('status', 'inactive')->count();
        $totalUsed = Coupon::sum('used_count');

        $totalDiscountGiven = CampPayment::where('status', 'succeeded')
            ->where('discount_amount', '>', 0)
            ->sum('discount_amount');

        $couponsUsed = CampPayment::where('status', 'succeeded')
            ->whereNotNull('coupon_id')
            ->count();

        return compact('total', 'active', 'inactive', 'totalUsed', 'totalDiscountGiven', 'couponsUsed');
    }

    // ──────────────────────────────────────────────
    //  REFEREE STATISTICS
    // ──────────────────────────────────────────────

    private function getRefereeStats(): array
    {
        $totalRegistrations = CampRefereeCheckin::count();
        $checkedIn          = CampRefereeCheckin::where('registration_status', 'checked_in')->count();
        $registered         = CampRefereeCheckin::where('registration_status', 'registered')->count();

        return compact('totalRegistrations', 'checkedIn', 'registered');
    }

    // ──────────────────────────────────────────────
    //  MONTHLY REVENUE CHART (12 months)
    // ──────────────────────────────────────────────

    private function getMonthlyRevenue(int $months = 12): array
    {
        $raw = CampPayment::where('status', 'succeeded')
            ->where('paid_at', '>=', now()->subMonths($months))
            ->select(
                DB::raw("DATE_FORMAT(paid_at, '%Y-%m') as month"),
                DB::raw("COALESCE(SUM(amount), 0) as revenue"),
                DB::raw("COALESCE(SUM(admin_fee), 0) as fees"),
                DB::raw("COALESCE(SUM(director_amount), 0) as director_share"),
                DB::raw("COALESCE(SUM(discount_amount), 0) as discounts"),
                DB::raw("COUNT(*) as transactions")
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $labels      = [];
        $revenue     = [];
        $fees        = [];
        $directorPay = [];
        $discounts   = [];
        $transactions = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $m = now()->subMonths($i)->format('Y-m');
            $labels[] = Carbon::createFromFormat('Y-m', $m)->format('M Y');
            $found = $raw->firstWhere('month', $m);
            $revenue[]     = $found ? (float) $found->revenue : 0;
            $fees[]        = $found ? (float) $found->fees : 0;
            $directorPay[] = $found ? (float) $found->director_share : 0;
            $discounts[]   = $found ? (float) $found->discounts : 0;
            $transactions[] = $found ? (int) $found->transactions : 0;
        }

        return compact('labels', 'revenue', 'fees', 'directorPay', 'discounts', 'transactions');
    }

    // ──────────────────────────────────────────────
    //  MONTHLY CAMPS CHART
    // ──────────────────────────────────────────────

    private function getMonthlyCamps(): array
    {
        $raw = Camp::select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(*) as count')
            )
            ->whereYear('created_at', Carbon::now()->year)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('count', 'month')
            ->toArray();

        $monthly = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthly[] = $raw[$m] ?? 0;
        }

        return $monthly;
    }

    // ──────────────────────────────────────────────
    //  TOP CAMPS BY REVENUE
    // ──────────────────────────────────────────────

    private function getTopCampsByRevenue(int $limit = 10): array
    {
        return Camp::select(
                'camps.id',
                'camps.camp_name',
                'camps.location',
                'camps.price',
                'camps.status',
                'camps.sports_type_name',
                DB::raw('COALESCE(SUM(camp_payments.amount), 0) as total_revenue'),
                DB::raw('COALESCE(SUM(camp_payments.admin_fee), 0) as total_fees'),
                DB::raw('COALESCE(SUM(camp_payments.director_amount), 0) as total_director_pay'),
                DB::raw('COUNT(camp_payments.id) as payment_count'),
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

    // ──────────────────────────────────────────────
    //  RECENT PAYMENTS
    // ──────────────────────────────────────────────

    private function getRecentPayments(int $limit = 10): array
    {
        return CampPayment::with(['camp', 'referee'])
            ->where('status', 'succeeded')
            ->latest('paid_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    // ──────────────────────────────────────────────
    //  USER GROWTH TREND
    // ──────────────────────────────────────────────

    private function getUserGrowth(int $months = 6): array
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

        return compact('labels', 'data');
    }

    // ──────────────────────────────────────────────
    //  PAYMENT SUCCESS RATE
    // ──────────────────────────────────────────────

    private function getPaymentSuccessRate(): array
    {
        $total   = CampPaymentAttempt::count();
        $success = CampPaymentAttempt::where('status', 'completed')->count();
        $rate    = $total > 0 ? round(($success / $total) * 100, 1) : 0;

        return compact('total', 'success', 'rate');
    }

    // ──────────────────────────────────────────────
    //  DAILY ACTIVITIES TIMELINE
    // ──────────────────────────────────────────────

    private function getDailyActivities(): array
    {
        $activities = [];

        // Recent camp creations
        $recentCamps = Camp::latest()->limit(3)->get(['camp_name', 'created_at']);
        foreach ($recentCamps as $camp) {
            $activities[] = [
                'type'        => 'camp',
                'icon'        => 'fe fe-campground',
                'color'       => 'primary',
                'title'       => 'New Camp Created',
                'description' => $camp->camp_name,
                'time'        => Carbon::parse($camp->created_at)->diffForHumans(),
                'created_at'  => $camp->created_at,
            ];
        }

        // Recent successful payments
        $recentPayments = CampPayment::with('camp')
            ->where('status', 'succeeded')
            ->latest('paid_at')
            ->limit(3)
            ->get();
        foreach ($recentPayments as $payment) {
            $activities[] = [
                'type'        => 'payment',
                'icon'        => 'fe fe-credit-card',
                'color'       => 'success',
                'title'       => 'Payment Received',
                'description' => ($payment->camp?->camp_name ?? 'Camp') . ' — $' . number_format($payment->amount, 2),
                'time'        => $payment->paid_at ? Carbon::parse($payment->paid_at)->diffForHumans() : '',
                'created_at'  => $payment->paid_at ?? $payment->created_at,
            ];
        }

        // Recent schedule publications
        $recentSchedules = Schedule::where('status', 'published')
            ->latest('updated_at')
            ->limit(2)
            ->get();
        foreach ($recentSchedules as $schedule) {
            $activities[] = [
                'type'        => 'schedule',
                'icon'        => 'fe fe-calendar',
                'color'       => 'info',
                'title'       => 'Schedule Published',
                'description' => 'Schedule #' . $schedule->id . ' published',
                'time'        => Carbon::parse($schedule->updated_at)->diffForHumans(),
                'created_at'  => $schedule->updated_at,
            ];
        }

        // Sort by time, newest first
        usort($activities, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return array_slice($activities, 0, 8);
    }
}
