<?php

namespace App\Services\Admin;

use App\Models\CampEvaluatorRegistration;
use App\Models\CampPayment;
use App\Models\RefereeEvaluation;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Director\Models\Camp;
use Modules\Director\Models\CampRefereeCheckin;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserService
{
    /**
     * Cache TTL in seconds for metrics & stats.
     */
    protected const METRICS_CACHE_TTL = 60;
    protected const DIRECTOR_STATS_CACHE_TTL = 120;

    /**
     * Get paginated, filtered, and transformed list of users.
     */
    public function getPaginatedUsers(array $filters): LengthAwarePaginator
    {
        $search    = $filters['search'] ?? null;
        $role      = $filters['role'] ?? 'all';
        $status    = $filters['status'] ?? null;
        $sortBy    = $filters['sort_by'] ?? 'id';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $perPage   = (int) ($filters['per_page'] ?? 10);
        $dateFrom  = $filters['date_from'] ?? null;
        $dateTo    = $filters['date_to'] ?? null;

        // Whitelist sorting columns
        $allowedSorts = ['id', 'first_name', 'email', 'status', 'created_at', 'last_activity_at'];
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'id';
        }
        $sortOrder = strtolower($sortOrder) === 'asc' ? 'asc' : 'desc';

        // Base Query (Active or Trashed)
        $isTrashView = ($role === 'trashed');
        $query = $isTrashView ? User::onlyTrashed() : User::query();

        // Eager load Spatie roles
        $query->with(['roles:id,name']);

        // Search Filter (Full name, Username, Email, Phone)
        if (!empty($search)) {
            $term = '%' . trim($search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where(DB::raw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, ''))"), 'like', $term)
                  ->orWhere('username', 'like', $term)
                  ->orWhere('email', 'like', $term)
                  ->orWhere('phone', 'like', $term);
            });
        }

        // Role Filter
        if (!in_array($role, ['all', 'trashed'], true) && !empty($role)) {
            $query->whereHas('roles', fn($q) => $q->where('name', $role));
        }

        // Status Filter
        if (!empty($status) && in_array($status, ['active', 'inactive'], true)) {
            $query->where('status', $status);
        }

        // Date Range Filters
        if (!empty($dateFrom)) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if (!empty($dateTo)) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // Order
        $query->orderBy($sortBy, $sortOrder);

        // Preload director stats if needed
        $directorStats = [];
        if ($role === 'director' || $role === 'all') {
            $directorStats = $this->getDirectorSummaryStats();
        }

        // Paginate & Transform
        return $query->paginate($perPage)
            ->withQueryString()
            ->through(fn($item) => $this->transformUserRow($item, $directorStats));
    }

    /**
     * Transform a single user instance into a clean UI array.
     */
    protected function transformUserRow(User $item, $directorStats = []): array
    {
        $fullName = trim(($item->first_name ?? '') . ' ' . ($item->last_name ?? ''));
        $avatar = $item->avatar
            ? (filter_var($item->avatar, FILTER_VALIDATE_URL) ? $item->avatar : asset($item->avatar))
            : 'https://ui-avatars.com/api/?name=' . urlencode($fullName ?: 'User') . '&background=6366F1&color=fff';

        $roles = $item->roles->map(fn($r) => [
            'id'   => $r->id,
            'name' => $r->name,
        ]);

        $primaryRole = $roles->first()['name'] ?? 'User';

        // Activity metrics
        $lastActive = 'Never';
        $isOnline = false;
        if ($item->last_activity_at) {
            $lastActive = Carbon::parse($item->last_activity_at)->diffForHumans();
            $isOnline = Carbon::parse($item->last_activity_at)->gt(now()->subMinutes(15));
        }

        // Director specific statistics
        $directorData = null;
        if ($primaryRole === 'director' || $roles->contains('name', 'director')) {
            $dStat = $directorStats[$item->id] ?? null;
            $directorData = [
                'camps_count'   => (int) ($dStat->camp_count ?? 0),
                'total_revenue' => (float) ($dStat->total_revenue ?? 0),
                'stripe_linked' => !empty($item->stripe_account_id),
            ];
        }

        return [
            'id'            => $item->id,
            'full_name'     => $fullName ?: ($item->username ?? 'Unknown'),
            'first_name'    => $item->first_name,
            'last_name'     => $item->last_name,
            'username'      => $item->username,
            'email'         => $item->email,
            'phone'         => $item->phone,
            'avatar'        => $avatar,
            'status'        => $item->status ?? 'inactive',
            'roles'         => $roles,
            'primary_role'  => $primaryRole,
            'last_activity' => $lastActive,
            'is_online'     => $isOnline,
            'created_at'    => $item->created_at ? $item->created_at->format('M d, Y') : 'N/A',
            'deleted_at'    => $item->deleted_at ? $item->deleted_at->format('M d, Y, h:i A') : null,
            'is_trashed'    => $item->trashed(),
            'director_data' => $directorData,
        ];
    }

    /**
     * Get aggregate KPI metrics for users with caching.
     */
    public function getKpiMetrics(): array
    {
        return Cache::remember('v2_users_kpi_metrics', self::METRICS_CACHE_TTL, function () {
            $roleCounts = DB::table('model_has_roles')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->join('users', 'model_has_roles.model_id', '=', 'users.id')
                ->where('model_has_roles.model_type', User::class)
                ->whereNull('users.deleted_at')
                ->select('roles.name', DB::raw('COUNT(*) as count'))
                ->groupBy('roles.name')
                ->pluck('count', 'name');

            return [
                'total'      => User::count(),
                'active'     => User::where('status', 'active')->count(),
                'directors'  => $roleCounts['director'] ?? $roleCounts['Director'] ?? 0,
                'referees'   => $roleCounts['referee'] ?? $roleCounts['Referee'] ?? 0,
                'evaluators' => $roleCounts['evaluator'] ?? $roleCounts['Evaluator'] ?? 0,
                'trashed'    => User::onlyTrashed()->count(),
            ];
        });
    }

    /**
     * Get director summary stats (camps count & revenue).
     */
    protected function getDirectorSummaryStats()
    {
        return Cache::remember('v2_director_summary_stats', self::DIRECTOR_STATS_CACHE_TTL, function () {
            return CampPayment::where('camp_payments.status', 'succeeded')
                ->join('camps', 'camps.id', '=', 'camp_payments.camp_id')
                ->selectRaw('camps.director_id, SUM(camp_payments.director_amount) as total_revenue, COUNT(DISTINCT camps.id) as camp_count')
                ->groupBy('camps.director_id')
                ->get()
                ->keyBy('director_id');
        });
    }

    /**
     * Get full user details and role-specific analytics for profile show page.
     */
    public function getUserProfileData(int $id): array
    {
        $user = User::withTrashed()->with(['roles', 'permissions', 'profile'])->findOrFail($id);

        $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        $avatar = $user->avatar
            ? (filter_var($user->avatar, FILTER_VALIDATE_URL) ? $user->avatar : asset($user->avatar))
            : 'https://ui-avatars.com/api/?name=' . urlencode($fullName ?: 'User') . '&background=6366F1&color=fff';

        $roleName = $user->roles->first()?->name ?? 'User';
        $normalizedRole = strtolower($roleName);

        // Director Details
        $directorStats = null;
        $directorCamps = [];
        $directorRevenue = [];

        if ($normalizedRole === 'director') {
            $directorCamps = Camp::where('director_id', $user->id)
                ->with('sportsType:id,sports_name,icon')
                ->select('id', 'camp_name', 'sports_type_id', 'price', 'status', 'start_date', 'end_date', 'location', 'created_at')
                ->latest()
                ->get()
                ->map(fn($c) => [
                    'id'               => $c->id,
                    'camp_name'        => $c->camp_name,
                    'sports_type_name' => $c->sportsType->sports_name ?? 'N/A',
                    'price'            => (float) $c->price,
                    'status'           => $c->status,
                    'start_date'       => $c->start_date ? Carbon::parse($c->start_date)->format('M d, Y') : 'TBD',
                    'end_date'         => $c->end_date ? Carbon::parse($c->end_date)->format('M d, Y') : 'TBD',
                    'location'         => $c->location,
                ]);

            $directorStatsRaw = CampPayment::whereHas('camp', fn($q) => $q->where('director_id', $user->id))
                ->selectRaw('
                    COUNT(*) as total_payments,
                    SUM(amount) as gross_revenue,
                    SUM(director_amount) as net_revenue,
                    SUM(admin_fee) as admin_fees,
                    SUM(discount_amount) as discounts_given
                ')
                ->where('status', 'succeeded')
                ->first();

            $directorStats = [
                'total_payments'  => (int) ($directorStatsRaw->total_payments ?? 0),
                'gross_revenue'   => (float) ($directorStatsRaw->gross_revenue ?? 0),
                'net_revenue'     => (float) ($directorStatsRaw->net_revenue ?? 0),
                'admin_fees'      => (float) ($directorStatsRaw->admin_fees ?? 0),
                'discounts_given' => (float) ($directorStatsRaw->discounts_given ?? 0),
            ];

            $directorRevenue = CampPayment::whereHas('camp', fn($q) => $q->where('director_id', $user->id))
                ->where('status', 'succeeded')
                ->selectRaw('
                    DATE_FORMAT(paid_at, "%Y-%m") as month,
                    SUM(amount) as gross_revenue,
                    SUM(director_amount) as net_revenue,
                    COUNT(*) as transactions
                ')
                ->groupBy('month')
                ->orderBy('month', 'desc')
                ->limit(12)
                ->get();
        }

        // Referee Details
        $refereeCamps = [];
        $refereeCheckins = [];
        $refereePayments = [];
        $refereeEvaluations = [];

        if ($normalizedRole === 'referee') {
            $user->load('refereeCamps');
            $refereeCamps = $user->refereeCamps->map(fn($c) => [
                'id'            => $c->id,
                'camp_name'     => $c->camp_name,
                'jersey_number' => $c->pivot->jersey_number ?? '—',
                'start_date'    => $c->start_date ? Carbon::parse($c->start_date)->format('M d, Y') : 'TBD',
                'end_date'      => $c->end_date ? Carbon::parse($c->end_date)->format('M d, Y') : 'TBD',
                'location'      => $c->location,
                'status'        => $c->status,
            ]);

            $refereeCheckins = CampRefereeCheckin::where('referee_id', $user->id)
                ->with('camp:id,camp_name')
                ->latest()
                ->limit(20)
                ->get()
                ->map(fn($ck) => [
                    'id'                  => $ck->id,
                    'camp_name'           => $ck->camp->camp_name ?? 'N/A',
                    'registration_status' => $ck->registration_status ?? 'pending',
                    'checked_in_at'       => $ck->created_at ? $ck->created_at->format('M d, Y, h:i A') : 'N/A',
                ]);

            $refereePayments = CampPayment::where('referee_id', $user->id)
                ->with('camp:id,camp_name')
                ->latest()
                ->limit(20)
                ->get()
                ->map(fn($p) => [
                    'id'        => $p->id,
                    'camp_name' => $p->camp->camp_name ?? 'N/A',
                    'amount'    => (float) $p->amount,
                    'status'    => $p->status,
                    'paid_at'   => $p->paid_at ? Carbon::parse($p->paid_at)->format('M d, Y') : 'N/A',
                ]);

            $refereeEvaluations = RefereeEvaluation::where('referee_id', $user->id)
                ->with(['evaluator:id,first_name,last_name', 'camp:id,camp_name'])
                ->latest()
                ->limit(20)
                ->get()
                ->map(fn($e) => [
                    'id'             => $e->id,
                    'evaluator_name' => trim(($e->evaluator->first_name ?? '') . ' ' . ($e->evaluator->last_name ?? '')),
                    'camp_name'      => $e->camp->camp_name ?? 'N/A',
                    'rating'         => $e->overall_rating ?? $e->rating ?? 'N/A',
                    'created_at'     => $e->created_at ? $e->created_at->format('M d, Y') : 'N/A',
                ]);
        }

        // Evaluator Details
        $evaluatorRegistrations = [];
        $evaluatorEvaluations = [];

        if ($normalizedRole === 'evaluator') {
            $evaluatorRegistrations = CampEvaluatorRegistration::where('evaluator_id', $user->id)
                ->with('camp:id,camp_name,start_date,end_date')
                ->latest()
                ->get()
                ->map(fn($er) => [
                    'id'         => $er->id,
                    'camp_name'  => $er->camp->camp_name ?? 'N/A',
                    'start_date' => $er->camp?->start_date ? Carbon::parse($er->camp->start_date)->format('M d, Y') : 'TBD',
                    'end_date'   => $er->camp?->end_date ? Carbon::parse($er->camp->end_date)->format('M d, Y') : 'TBD',
                ]);

            $evaluatorEvaluations = RefereeEvaluation::where('evaluator_id', $user->id)
                ->with(['referee:id,first_name,last_name', 'camp:id,camp_name'])
                ->latest()
                ->limit(20)
                ->get()
                ->map(fn($ee) => [
                    'id'           => $ee->id,
                    'referee_name' => trim(($ee->referee->first_name ?? '') . ' ' . ($ee->referee->last_name ?? '')),
                    'camp_name'    => $ee->camp->camp_name ?? 'N/A',
                    'created_at'   => $ee->created_at ? $ee->created_at->format('M d, Y') : 'N/A',
                ]);
        }

        return [
            'user' => [
                'id'                => $user->id,
                'first_name'        => $user->first_name,
                'last_name'         => $user->last_name,
                'full_name'         => $fullName ?: ($user->username ?? 'User'),
                'username'          => $user->username,
                'email'             => $user->email,
                'phone'             => $user->phone,
                'address'           => $user->address,
                'biography'         => $user->biography,
                'avatar'            => $avatar,
                'status'            => $user->status ?? 'inactive',
                'primary_role'      => $roleName,
                'roles'             => $user->roles->pluck('name'),
                'permissions'       => $user->permissions->pluck('name'),
                'stripe_account_id' => $user->stripe_account_id,
                'stripe_linked'     => !empty($user->stripe_account_id),
                'last_activity_at'  => $user->last_activity_at ? Carbon::parse($user->last_activity_at)->format('M d, Y, h:i A') : 'Never',
                'created_at'        => $user->created_at ? $user->created_at->format('M d, Y, h:i A') : 'N/A',
                'is_trashed'        => $user->trashed(),
                'deleted_at'        => $user->deleted_at ? $user->deleted_at->format('M d, Y, h:i A') : null,
            ],
            'directorData' => [
                'stats'   => $directorStats,
                'camps'   => $directorCamps,
                'revenue' => $directorRevenue,
            ],
            'refereeData' => [
                'camps'       => $refereeCamps,
                'checkins'    => $refereeCheckins,
                'payments'    => $refereePayments,
                'evaluations' => $refereeEvaluations,
            ],
            'evaluatorData' => [
                'registrations' => $evaluatorRegistrations,
                'evaluations'   => $evaluatorEvaluations,
            ],
        ];
    }

    /**
     * Toggle status between active and inactive.
     */
    public function toggleStatus(int $id, int $currentAuthId): User
    {
        $user = User::withTrashed()->findOrFail($id);

        if ($user->id === $currentAuthId) {
            throw new Exception('You cannot change your own account status!');
        }

        $user->status = ($user->status === 'active') ? 'inactive' : 'active';
        $user->save();

        Cache::forget('v2_users_kpi_metrics');

        return $user;
    }

    /**
     * Soft delete user.
     */
    public function deleteUser(int $id, int $currentAuthId): void
    {
        $user = User::withTrashed()->findOrFail($id);

        if ($user->id === $currentAuthId) {
            throw new Exception('You cannot delete your own account!');
        }

        if ($user->trashed()) {
            throw new Exception('This user is already in trash!');
        }

        $user->delete();
        Cache::forget('v2_users_kpi_metrics');
    }

    /**
     * Restore soft-deleted user.
     */
    public function restoreUser(int $id): void
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $user->restore();

        Cache::forget('v2_users_kpi_metrics');
    }

    /**
     * Permanently delete user.
     */
    public function forceDeleteUser(int $id, int $currentAuthId): void
    {
        $user = User::onlyTrashed()->findOrFail($id);

        if ($user->id === $currentAuthId) {
            throw new Exception('You cannot permanently delete your own account!');
        }

        $user->forceDelete();
        Cache::forget('v2_users_kpi_metrics');
    }

    /**
     * Stream CSV export of users.
     */
    public function exportCsv(array $filters): StreamedResponse
    {
        $role   = $filters['role'] ?? 'all';
        $status = $filters['status'] ?? null;
        $search = $filters['search'] ?? null;

        $query = User::query()->with(['roles:id,name'])->orderBy('id', 'desc');

        if ($role !== 'all' && !empty($role)) {
            $query->whereHas('roles', fn($q) => $q->where('name', $role));
        }
        if (!empty($status)) {
            $query->where('status', $status);
        }
        if (!empty($search)) {
            $term = '%' . trim($search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where(DB::raw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, ''))"), 'like', $term)
                  ->orWhere('username', 'like', $term)
                  ->orWhere('email', 'like', $term);
            });
        }

        $users = $query->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="users_export_' . date('Y_m_d_His') . '.csv"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($users) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Full Name', 'Username', 'Email', 'Phone', 'Role', 'Status', 'Joined Date']);

            foreach ($users as $u) {
                $fullName = trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''));
                fputcsv($file, [
                    $u->id,
                    $fullName ?: ($u->username ?? 'N/A'),
                    $u->username ?? 'N/A',
                    $u->email,
                    $u->phone ?? 'N/A',
                    $u->roles->pluck('name')->implode(', ') ?: 'User',
                    $u->status ?? 'inactive',
                    $u->created_at ? $u->created_at->format('Y-m-d H:i:s') : 'N/A',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
