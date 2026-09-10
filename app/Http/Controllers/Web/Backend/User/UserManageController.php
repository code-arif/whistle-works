<?php

namespace App\Http\Controllers\Web\Backend\User;

use App\Http\Controllers\Controller;
use App\Models\CampEvaluatorRegistration;
use App\Models\CampPayment;
use App\Models\RefereeEvaluation;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Director\Models\Camp;
use Modules\Director\Models\CampRefereeCheckin;
use Modules\Director\Models\CrewMember;
use Yajra\DataTables\Facades\DataTables;

class UserManageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax() && $request->has('type')) {
            $type = $request->type;

            if ($type === 'all') {
                return $this->allDataTable($request);
            }
            if ($type === 'directors') {
                return $this->directorsDataTable($request);
            }
            if ($type === 'referees') {
                return $this->refereesDataTable($request);
            }
            if ($type === 'evaluators') {
                return $this->evaluatorsDataTable($request);
            }
        }

        // Get role counts for filter
        $roleCounts = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->join('users', 'model_has_roles.model_id', '=', 'users.id')
            ->where('model_has_roles.model_type', User::class)
            ->whereNull('users.deleted_at')
            ->select('roles.name', DB::raw('COUNT(*) as count'))
            ->groupBy('roles.name')
            ->pluck('count', 'name');

        return view("backend.layouts.users.index", compact('roleCounts'));
    }

    /**
     * Shared user columns (full_name, contact, last_active, status, action)
     */
    private function baseUserColumns($dataTable, $extraRaw = [])
    {
        return $dataTable
            ->addIndexColumn()
            ->filterColumn('first_name', function ($query, $keyword) {
                $query->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', "%{$keyword}%");
            })
            ->addColumn('full_name', function ($data) {
                $fullName = trim($data->first_name . ' ' . $data->last_name);
                $avatar = $data->avatar
                    ? asset($data->avatar)
                    : 'https://ui-avatars.com/api/?name=' . urlencode($fullName) . '&background=random';
                $showUrl = route('admin.users.manage.show', $data->id);

                return '<div class="d-flex align-items-center">
                            <a href="' . $showUrl . '" class="text-decoration-none d-flex align-items-center user-profile-link" data-tooltip="View Profile">
                                <img src="' . $avatar . '" alt="avatar" class="rounded-circle me-2" width="40" height="40" style="object-fit: cover;">
                                <div>
                                    <div class="fw-semibold text-dark user-name-link">' . e($fullName) . '</div>
                                    <small class="text-muted">' . e($data->username) . '</small>
                                </div>
                            </a>
                        </div>';
            })
            ->addColumn('contact', function ($data) {
                $email = '<div class="text-break"><i class="fe fe-mail me-1"></i>' . e($data->email) . '</div>';
                $phone = $data->phone
                    ? '<div class="text-muted mt-1"><i class="fe fe-phone me-1"></i>' . e($data->phone) . '</div>'
                    : '';
                return $email . $phone;
            })
            ->addColumn('last_active', function ($data) {
                if ($data->last_activity_at) {
                    $diff = $data->last_activity_at->diffForHumans();
                    $isRecent = $data->last_activity_at->gt(now()->subMinutes(15));
                    $indicator = $isRecent
                        ? '<span class="badge badge-dot bg-success me-1"></span>'
                        : '<span class="badge badge-dot bg-secondary me-1"></span>';
                    return $indicator . '<small>' . e($diff) . '</small>';
                }
                return '<small class="text-muted">Never</small>';
            })
            ->addColumn('status', function ($data) {
                // Show deleted badge for soft-deleted users
                if ($data->trashed()) {
                    return '<span class="badge bg-dark fs-12 px-3 py-2 rounded-pill">
                                <i class="fe fe-trash-2 me-1"></i> Deleted
                            </span>';
                }

                $backgroundColor = $data->status == "active" ? '#00AEEF' : '#ccc';
                $sliderTranslateX = $data->status == "active" ? '26px' : '2px';
                $sliderStyles = "position: absolute; top: 2px; left: 2px; width: 20px; height: 20px; background-color: white; border-radius: 50%; transition: transform 0.3s ease; transform: translateX($sliderTranslateX);";

                $status = '<div class="form-check form-switch" style="margin-left:40px; position: relative; width: 50px; height: 24px; background-color: ' . $backgroundColor . '; border-radius: 12px; transition: background-color 0.3s ease; cursor: pointer;">';
                $status .= '<input onclick="showStatusChangeAlert(' . $data->id . ')" type="checkbox" class="form-check-input" id="customSwitch' . $data->id . '" getAreaid="' . $data->id . '" name="status" style="position: absolute; width: 100%; height: 100%; opacity: 0; z-index: 2; cursor: pointer;"' . ($data->status == 'active' ? ' checked' : '') . '>';
                $status .= '<span style="' . $sliderStyles . '"></span>';
                $status .= '<label for="customSwitch' . $data->id . '" class="form-check-label" style="margin-left: 10px;"></label>';
                $status .= '</div>';

                return $status;
            })
            ->addColumn('action', function ($data) {
                return '<div class="btn-group btn-group-sm" role="group" aria-label="Basic example">
                            <a href="#" type="button" onclick="goToView(' . $data->id . ')" class="btn btn-success fs-14 text-white" title="View Details">
                                <i class="fe fe-eye"></i>
                            </a>
                            <a href="#" type="button" onclick="showDeleteConfirm(' . $data->id . ')" class="btn btn-danger fs-14 text-white" title="Delete User">
                                <i class="fe fe-trash"></i>
                            </a>
                        </div>';
            })
            ->rawColumns(array_merge(
                ['full_name', 'contact', 'last_active', 'status', 'action'],
                $extraRaw
            ));
    }

    /**
     * Build base user query with common filters
     */
    private function buildUserQuery(Request $request, string $role = '')
    {
        $query = User::query()
            ->select([
                'users.id',
                'users.first_name',
                'users.last_name',
                'users.username',
                'users.email',
                'users.phone',
                'users.avatar',
                'users.status',
                'users.created_at',
                'users.last_activity_at',
                'users.stripe_account_id',
            ])
            ->orderBy('users.id', 'desc');

        if ($role) {
            $query->whereHas('roles', fn($q) => $q->where('name', $role));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        return $query;
    }

    /**
     * DataTable: All Users
     */
    private function allDataTable(Request $request)
    {
        $query = $this->buildUserQuery($request)
            ->with(['roles:id,name']);

        $dt = DataTables::eloquent($query);

        $dt = $this->baseUserColumns($dt, ['roles']);

        $dt->addColumn('roles', function ($data) {
            if ($data->roles->isEmpty()) {
                return '<span class="badge bg-secondary">No Role</span>';
            }
            $badges = '';
            $colors = [
                'admin'     => 'danger',
                'director'  => 'primary',
                'referee'   => 'success',
                'evaluator' => 'info',
            ];
            foreach ($data->roles as $role) {
                $color = $colors[$role->name] ?? 'secondary';
                $badges .= '<span class="badge bg-' . $color . ' me-1">' . e($role->name) . '</span>';
            }
            return $badges;
        });

        return $dt->make(true);
    }

    /**
     * DataTable: Directors
     */
    private function directorsDataTable(Request $request)
    {
        // Pre-compute director revenues & camp counts
        $directorStats = CampPayment::where('camp_payments.status', 'succeeded')
            ->join('camps', 'camps.id', '=', 'camp_payments.camp_id')
            ->selectRaw('camps.director_id, SUM(camp_payments.director_amount) as total_revenue, COUNT(DISTINCT camps.id) as camp_count')
            ->groupBy('camps.director_id')
            ->get()
            ->keyBy('director_id');

        $query = $this->buildUserQuery($request, 'director');

        $dt = DataTables::eloquent($query);
        $dt = $this->baseUserColumns($dt, ['stripe_status', 'total_revenue', 'camp_count']);

        $dt
            ->addColumn('stripe_status', function ($data) {
                if (!$data->stripe_account_id) {
                    return '<span class="badge bg-secondary">Not Connected</span>';
                }
                return '<span class="badge bg-success"><i class="fe fe-check-circle me-1"></i>Connected</span>';
            })
            ->addColumn('total_revenue', function ($data) use ($directorStats) {
                $total = $directorStats[$data->id]->total_revenue ?? 0;
                return '<strong>$' . number_format($total, 2) . '</strong>';
            })
            ->addColumn('camp_count', function ($data) use ($directorStats) {
                $count = $directorStats[$data->id]->camp_count ?? 0;
                return '<span class="badge bg-info">' . $count . ' camps</span>';
            });

        return $dt->make(true);
    }

    /**
     * DataTable: Referees
     */
    private function refereesDataTable(Request $request)
    {
        $query = $this->buildUserQuery($request, 'referee')
            ->with([
                'refereeCamps' => fn($q) => $q->select('camps.id', 'camps.camp_name'),
                'refereeCheckins',
            ])
            ->withCount(['refereeCheckins as checkins_count']);

        $dt = DataTables::eloquent($query);
        $dt = $this->baseUserColumns($dt, ['camps', 'checkins']);

        $dt
            ->addColumn('camps', function ($data) {
                $camps = $data->refereeCamps;
                if ($camps->isEmpty()) {
                    return '<span class="text-muted">No camps</span>';
                }
                $items = $camps->map(function ($camp) {
                    $jersey = $camp->pivot->jersey_number ?? '—';
                    return '<div class="d-flex align-items-center gap-2 py-1">
                                <span class="badge bg-primary badge-pill" style="width:28px;">#' . e($jersey) . '</span>
                                <span>' . e($camp->camp_name) . '</span>
                            </div>';
                })->implode('');
                return '<div style="max-height:120px; overflow-y:auto;">' . $items . '</div>';
            })
            ->addColumn('checkins', function ($data) {
                $total = $data->checkins_count ?? 0;
                $checkedIn = $data->refereeCheckins->where('registration_status', 'checked_in')->count();
                $color = $checkedIn > 0 ? 'success' : ($total > 0 ? 'warning' : 'secondary');
                return '<span class="badge bg-' . $color . '">' . $checkedIn . ' / ' . $total . ' checked in</span>';
            });

        return $dt->make(true);
    }

    /**
     * DataTable: Evaluators
     */
    private function evaluatorsDataTable(Request $request)
    {
        $query = $this->buildUserQuery($request, 'evaluator')
            ->withCount(['evaluatorEvaluations as evaluations_count']);

        $dt = DataTables::eloquent($query);
        $dt = $this->baseUserColumns($dt, ['evaluations_count']);

        $dt->addColumn('evaluations_count', function ($data) {
            return '<span class="badge bg-info">' . ($data->evaluations_count ?? 0) . ' done</span>';
        });

        return $dt->make(true);
    }

    /**
     * User Details — role-aware dashboard
     */
    public function show($id)
    {
        $user = User::withTrashed()->with(['roles', 'permissions', 'profile'])->findOrFail($id);

        $roleName = $user->roles->first()?->name ?? '';

        // ── Director data ──
        $directorStats = [];
        $directorCamps = collect();
        $directorRevenue = collect();
        $yearlyComparison = collect();
        if ($roleName === 'Director') {
            $directorCamps = Camp::where('director_id', $user->id)
                ->with('sportsType:id,sports_name')
                ->select('id', 'camp_name', 'sports_type_id', 'price', 'status', 'start_date', 'end_date', 'location', 'created_at')
                ->latest()
                ->get();

            $directorStats = CampPayment::whereHas('camp', fn($q) => $q->where('director_id', $user->id))
                ->selectRaw('
                    COUNT(*) as total_payments,
                    SUM(amount) as gross_revenue,
                    SUM(director_amount) as net_revenue,
                    SUM(admin_fee) as admin_fees,
                    SUM(discount_amount) as discounts_given
                ')
                ->where('status', 'succeeded')
                ->first();

            $directorRevenue = CampPayment::whereHas('camp', fn($q) => $q->where('director_id', $user->id))
                ->where('status', 'succeeded')
                ->selectRaw('
                    DATE_FORMAT(paid_at, "%Y-%m") as month,
                    SUM(amount) as gross_revenue,
                    SUM(director_amount) as net_revenue,
                    SUM(admin_fee) as admin_fees,
                    SUM(discount_amount) as discounts,
                    COUNT(*) as transactions
                ')
                ->groupBy('month')
                ->orderBy('month', 'desc')
                ->limit(12)
                ->get();

            // Year-over-year comparison: last 2 years grouped by year+month
            $yearlyComparison = CampPayment::whereHas('camp', fn($q) => $q->where('director_id', $user->id))
                ->where('status', 'succeeded')
                ->where('paid_at', '>=', now()->subYears(2)->startOfYear())
                ->selectRaw('
                    YEAR(paid_at) as year,
                    MONTH(paid_at) as month_num,
                    DATE_FORMAT(paid_at, "%Y-%m") as month,
                    SUM(amount) as gross_revenue,
                    SUM(director_amount) as net_revenue,
                    SUM(admin_fee) as admin_fees,
                    SUM(discount_amount) as discounts,
                    COUNT(*) as transactions
                ')
                ->groupBy('year', 'month_num', 'month')
                ->orderBy('year')
                ->orderBy('month_num')
                ->get();
        }

        // Referee data
        $refereeCamps = collect();
        $refereeCheckins = collect();
        $refereePayments = collect();
        $refereeEvaluations = collect();
        $crewMemberships = collect();
        if ($roleName === 'Referee') {
            // Eager-load refereeCamps on the user model for jersey number lookup in view
            $user->load('refereeCamps');

            $refereeCamps = Camp::whereHas('referees', fn($q) => $q->where('user_id', $user->id))
                ->select('id', 'camp_name', 'start_date', 'end_date', 'location', 'status')
                ->get();

            $refereeCheckins = CampRefereeCheckin::where('referee_id', $user->id)
                ->with('camp:id,camp_name')
                ->latest()
                ->get();

            $refereePayments = CampPayment::where('referee_id', $user->id)
                ->with('camp:id,camp_name')
                ->latest()
                ->get();

            $refereeEvaluations = RefereeEvaluation::where('referee_id', $user->id)
                ->with(['evaluator:id,first_name,last_name', 'camp:id,camp_name'])
                ->latest()
                ->get();

            $crewMemberships = CrewMember::where('referee_id', $user->id)
                ->with(['crew:id,name,camp_id', 'crew.camp:id,camp_name'])
                ->get();
        }

        // Evaluator data
        $evaluatorRegistrations = collect();
        $evaluatorEvaluations = collect();
        if ($roleName === 'Evaluator') {
            $evaluatorRegistrations = CampEvaluatorRegistration::where('evaluator_id', $user->id)
                ->with('camp:id,camp_name,start_date,end_date')
                ->latest()
                ->get();

            $evaluatorEvaluations = RefereeEvaluation::where('evaluator_id', $user->id)
                ->with(['referee:id,first_name,last_name', 'camp:id,camp_name'])
                ->latest()
                ->get();
        }

        return view('backend.layouts.users.show', compact(
            'user',
            'roleName',
            'directorStats',
            'directorCamps',
            'directorRevenue',
            'yearlyComparison',
            'refereeCamps',
            'refereeCheckins',
            'refereePayments',
            'refereeEvaluations',
            'crewMemberships',
            'evaluatorRegistrations',
            'evaluatorEvaluations'
        ));
    }

    /**
     * Delete User
     */
    public function destroy($id)
    {
        try {
            $user = User::withTrashed()->findOrFail($id);

            // Prevent self-deletion
            if ($user->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot delete your own account!'
                ], 403);
            }

            if ($user->trashed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This user is already deleted!'
                ], 409);
            }

            // Soft delete
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully!'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Trash — list of soft-deleted users
     */
    public function trash(Request $request)
    {
        if ($request->ajax()) {
            $query = User::onlyTrashed()
                ->select([
                    'users.id',
                    'users.first_name',
                    'users.last_name',
                    'users.username',
                    'users.email',
                    'users.phone',
                    'users.avatar',
                    'users.status',
                    'users.created_at',
                    'users.deleted_at',
                ])
                ->with(['roles:id,name'])
                ->orderBy('users.deleted_at', 'desc');

            $dt = DataTables::eloquent($query)
                ->addIndexColumn()
                ->filterColumn('first_name', function ($q, $keyword) {
                    $q->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', "%{$keyword}%");
                })
                ->addColumn('full_name', function ($data) {
                    $fullName = trim($data->first_name . ' ' . $data->last_name);
                    $avatar = $data->avatar
                        ? asset($data->avatar)
                        : 'https://ui-avatars.com/api/?name=' . urlencode($fullName) . '&background=random';
                    $showUrl = route('admin.users.manage.show', $data->id);

                    return '<div class="d-flex align-items-center">
                                <a href="' . $showUrl . '" class="text-decoration-none d-flex align-items-center" title="View user details">
                                    <img src="' . $avatar . '" alt="avatar" class="rounded-circle me-2" width="40" height="40" style="object-fit: cover;">
                                    <div>
                                        <div class="fw-semibold text-dark user-name-link">' . e($fullName) . '</div>
                                        <small class="text-muted">' . e($data->username) . '</small>
                                    </div>
                                </a>
                            </div>';
                })
                ->addColumn('contact', function ($data) {
                    $email = '<div class="text-break"><i class="fe fe-mail me-1"></i>' . e($data->email) . '</div>';
                    $phone = $data->phone
                        ? '<div class="text-muted mt-1"><i class="fe fe-phone me-1"></i>' . e($data->phone) . '</div>'
                        : '';
                    return $email . $phone;
                })
                ->addColumn('roles', function ($data) {
                    if ($data->roles->isEmpty()) {
                        return '<span class="badge bg-secondary">No Role</span>';
                    }
                    $badges = '';
                    $colors = ['admin' => 'danger', 'director' => 'primary', 'referee' => 'success', 'evaluator' => 'info'];
                    foreach ($data->roles as $role) {
                        $color = $colors[$role->name] ?? 'secondary';
                        $badges .= '<span class="badge bg-' . $color . ' me-1">' . e($role->name) . '</span>';
                    }
                    return $badges;
                })
                ->addColumn('deleted_at', function ($data) {
                    return '<small>' . e($data->deleted_at->format('d M Y, h:i A')) . '</small>';
                })
                ->addColumn('action', function ($data) {
                    return '<div class="btn-group btn-group-sm" role="group">
                                <button type="button" onclick="showRestoreAlert(' . $data->id . ')" class="btn btn-success fs-14 text-white" title="Restore User">
                                    <i class="fe fe-refresh-cw"></i>
                                </button>
                                <button type="button" onclick="showForceDeleteAlert(' . $data->id . ')" class="btn btn-danger fs-14 text-white" title="Permanently Delete">
                                    <i class="fe fe-trash-2"></i>
                                </button>
                            </div>';
                })
                ->rawColumns(['full_name', 'contact', 'roles', 'action']);

            return $dt->make(true);
        }

        $trashCount = User::onlyTrashed()->count();

        return view('backend.layouts.users.trash', compact('trashCount'));
    }

    /**
     * Restore a soft-deleted user
     */
    public function restore($id)
    {
        try {
            $user = User::onlyTrashed()->findOrFail($id);
            $user->restore();

            return response()->json([
                'success' => true,
                'message' => 'User restored successfully!'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to restore user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Permanently delete a soft-deleted user
     */
    public function forceDelete($id)
    {
        try {
            $user = User::onlyTrashed()->findOrFail($id);

            // Prevent self-permanent-deletion
            if ($user->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot permanently delete your own account!'
                ], 403);
            }

            $user->forceDelete();

            return response()->json([
                'success' => true,
                'message' => 'User permanently deleted!'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to permanently delete user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle User Status
     */
    public function status($id)
    {
        try {
            $user = User::withTrashed()->findOrFail($id);

            // Prevent self-deactivation
            if ($user->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot change your own status!'
                ], 403);
            }

            $user->status = $user->status === 'active' ? 'inactive' : 'active';
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'User status updated successfully!'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export users to CSV or Excel
     */
    public function export(Request $request)
    {
        try {
            $request->validate([
                'format' => 'required|in:csv,excel',
                'fields' => 'required|array',
                'fields.*' => 'in:name,username,email,phone,roles,status,created_at'
            ]);

            // Build query with filters
            $query = User::query()
                ->with(['roles:id,name'])
                ->orderBy('id', 'desc');

            // Apply filters if requested
            if ($request->filled('role')) {
                $query->whereHas('roles', function ($q) use ($request) {
                    $q->where('name', $request->role);
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $users = $query->get();

            if ($users->isEmpty()) {
                return response()->json(['message' => 'No users found to export'], 404);
            }

            // Prepare data based on selected fields
            $exportData = [];
            $fields = $request->fields;

            // Add header row
            $headers = [];
            foreach ($fields as $field) {
                $headers[] = ucfirst(str_replace('_', ' ', $field));
            }
            $exportData[] = $headers;

            // Add data rows
            foreach ($users as $user) {
                $row = [];
                foreach ($fields as $field) {
                    switch ($field) {
                        case 'name':
                            $row[] = trim($user->first_name . ' ' . $user->last_name);
                            break;
                        case 'username':
                            $row[] = $user->username;
                            break;
                        case 'email':
                            $row[] = $user->email;
                            break;
                        case 'phone':
                            $row[] = $user->phone ?? 'N/A';
                            break;
                        case 'roles':
                            $row[] = $user->roles->pluck('name')->implode(', ') ?: 'No Role';
                            break;
                        case 'status':
                            $row[] = ucfirst($user->status);
                            break;
                        case 'created_at':
                            $row[] = $user->created_at;
                            break;
                    }
                }
                $exportData[] = $row;
            }

            // Generate file based on format
            if ($request->format === 'csv') {
                return $this->generateCSV($exportData);
            } else {
                return $this->generateExcel($exportData);
            }
        } catch (\Exception $e) {
            Log::error('Export Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to export users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate CSV file
     */
    private function generateCSV($data)
    {
        $filename = 'users_export_' . date('Y-m-d_His') . '.csv';

        $handle = fopen('php://temp', 'r+');

        foreach ($data as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Generate Excel file using PhpSpreadsheet
     */
    private function generateExcel($data)
    {
        $filename = 'users_export_' . date('Y-m-d_His') . '.xlsx';

        // Check if PhpSpreadsheet is available
        if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            // Fallback to CSV if PhpSpreadsheet not installed
            return $this->generateCSV($data);
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers style
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '007bff']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ]
        ];

        // Populate data
        $rowNumber = 1;
        foreach ($data as $index => $row) {
            $columnLetter = 'A';
            foreach ($row as $cell) {
                $sheet->setCellValue($columnLetter . $rowNumber, $cell);

                // Apply header style to first row
                if ($index === 0) {
                    $sheet->getStyle($columnLetter . $rowNumber)->applyFromArray($headerStyle);
                    $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
                }

                $columnLetter++;
            }
            $rowNumber++;
        }

        // Add borders
        $lastColumn = chr(64 + count($data[0]));
        $lastRow = count($data);
        $sheet->getStyle('A1:' . $lastColumn . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC']
                ]
            ]
        ]);

        // Create writer
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        // Save to output
        $temp_file = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($temp_file);

        return response()->download($temp_file, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ])->deleteFileAfterSend(true);
    }
}
