@extends('backend.app', ['title' => 'User Details'])

@section('content')
    <div class="app-content main-content mt-0">
        <div class="side-app" style="margin-bottom: 80px">
            <div class="main-container container-fluid">

                <!-- PAGE HEADER -->
                <div class="page-header d-flex flex-wrap align-items-center justify-content-between">
                    <div>
                        <h1 class="page-title">User Details</h1>
                        <p class="text-muted mb-0" style="font-size: 13px;">View detailed user profile and role-specific data</p>
                    </div>
                    <ol class="breadcrumb mb-0 py-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.users.manage.index') }}">Users</a></li>
                        <li class="breadcrumb-item active" aria-current="page">View</li>
                    </ol>
                </div>

                @php
                    $printFullName = trim($user->first_name . ' ' . $user->last_name);
                @endphp
                <!-- PRINT HEADER (hidden on screen, visible in print) -->
                <div class="print-header">
                    <div class="print-header-title">User Details Report</div>
                    <div class="print-header-subtitle">{{ $printFullName }} — {{ $user->email }}</div>
                    <div class="print-header-date">Generated: {{ now()->format('d M Y, h:i A') }}</div>
                </div>

                <!-- PROFILE HEADER CARD -->
                <div class="row">
                    <div class="col-12">
                        <div class="card overflow-hidden">
                            <div class="profile-cover"></div>
                            <div class="card-body position-relative" style="padding-top: 70px;">
                                <div class="row align-items-end">
                                    <div class="col-md-7">
                                        <div class="d-flex align-items-end gap-4">
                                            <div class="profile-avatar-wrapper">
                                                @php
                                                    $fullName = trim($user->first_name . ' ' . $user->last_name);
                                                    $avatar = $user?->avatar
                                                        ? asset($user?->avatar)
                                                        : 'https://ui-avatars.com/api/?name=' .
                                                            urlencode($fullName) .
                                                            '&size=120&background=00AEEF&color=fff';
                                                @endphp
                                                <img src="{{ $avatar }}" class="profile-avatar"
                                                    alt="{{ $fullName }}">
                                                @if ($user->trashed())
                                                    <span class="status-indicator" style="background:#6c757d;">
                                                        <i class="fe fe-trash-2" style="font-size:8px;position:absolute;top:2px;left:3px;color:#fff;"></i>
                                                    </span>
                                                @elseif ($user->status == 'active')
                                                    <span class="status-indicator bg-success"></span>
                                                @else
                                                    <span class="status-indicator bg-danger"></span>
                                                @endif
                                            </div>
                                            <div class="pb-2">
                                                <h3 class="mb-1 fw-bold">{{ $fullName ?: 'N/A' }}</h3>
                                                <p class="text-muted mb-2 d-flex align-items-center flex-wrap gap-2">
                                                    <span class="d-inline-flex align-items-center">
                                                        <i class="fe fe-at me-1"></i>
                                                        {{ $user->username }}
                                                    </span>

                                                    <span class="mx-1">|</span>

                                                    <span class="d-inline-flex align-items-center">
                                                        <i class="fe fe-mail me-1"></i>
                                                        {{ $user->email }}
                                                    </span>
                                                </p>
                                                <div class="d-flex flex-wrap gap-2">
                                                    @forelse ($user->roles as $role)
                                                        @php
                                                            $colors = [
                                                                'Admin' => 'danger',
                                                                'Director' => 'primary',
                                                                'Referee' => 'success',
                                                                'Evaluator' => 'info',
                                                            ];
                                                            $color = $colors[$role->name] ?? 'secondary';
                                                        @endphp
                                                       <span class="badge bg-{{ $color }} fs-12 px-3 py-2 rounded-pill d-inline-flex align-items-center gap-1">
                                                            <i class="fe fe-award" style="font-size: 11px; line-height: 1;"></i>
                                                            <span>{{ $role->name }}</span>
                                                        </span>
                                                    @empty
                                                        <span class="badge bg-secondary fs-12 px-3 py-2 rounded-pill">No
                                                            Role</span>
                                                    @endforelse
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-5 text-md-end mt-3 mt-md-0">
                                        <div class="btn-group btn-group-sm flex-wrap" role="group">
                                            <button onclick="printUserDetails()"
                                                class="btn btn-outline-primary d-inline-flex align-items-center gap-1">
                                                <i class="fe fe-printer"></i>
                                                <span>Print / PDF</span>
                                            </button>

                                            <a href="{{ route('admin.users.manage.index') }}"
                                                class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
                                                <i class="fe fe-arrow-left"></i>
                                                <span>Back</span>
                                            </a>

                                            @if ($user->trashed())
                                                <span class="btn btn-outline-dark disabled d-inline-flex align-items-center gap-1">
                                                    <i class="fe fe-trash-2"></i>
                                                    <span>Deleted</span>
                                                </span>
                                            @elseif ($user->status == 'active')
                                                <button onclick="showStatusChangeAlert({{ $user->id }})"
                                                    class="btn btn-outline-warning d-inline-flex align-items-center gap-1">
                                                    <i class="fe fe-lock"></i>
                                                    <span>Deactivate</span>
                                                </button>
                                            @else
                                                <button onclick="showStatusChangeAlert({{ $user->id }})"
                                                    class="btn btn-outline-success d-inline-flex align-items-center gap-1">
                                                    <i class="fe fe-unlock"></i>
                                                    <span>Activate</span>
                                                </button>
                                            @endif

                                            <button onclick="showDeleteConfirm({{ $user->id }})"
                                                class="btn btn-outline-danger d-inline-flex align-items-center gap-1">
                                                <i class="fe fe-trash-2"></i>
                                                <span>Delete</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ROLE-BASED STATS ROW -->
                @if ($roleName === 'Director')
                    @php
                        $dStats =
                            $directorStats ?:
                            (object) [
                                'total_payments' => 0,
                                'net_revenue' => 0,
                                'admin_fees' => 0,
                            ];
                    @endphp
                    <div class="row mb-4 g-3">
                        <div class="col-xl-3 col-lg-6 col-md-6">
                            <div class="stats-card">
                                <div class="stats-icon bg-primary-subtle text-primary">
                                    <i class="fe fe-dollar-sign"></i>
                                </div>
                                <div class="stats-detail">
                                    <div class="stats-value">${{ number_format($dStats->net_revenue, 2) }}</div>
                                    <div class="stats-label">Net Revenue</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6 col-md-6">
                            <div class="stats-card">
                                <div class="stats-icon bg-success-subtle text-success">
                                    <i class="fe fe-credit-card"></i>
                                </div>
                                <div class="stats-detail">
                                    <div class="stats-value">{{ $dStats->total_payments }}</div>
                                    <div class="stats-label">Successful Payments</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6 col-md-6">
                            <div class="stats-card">
                                <div class="stats-icon bg-info-subtle text-info">
                                    <i class="fe fe-grid"></i>
                                </div>
                                <div class="stats-detail">
                                    <div class="stats-value">{{ $directorCamps->count() }}</div>
                                    <div class="stats-label">Camps Managed</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6 col-md-6">
                            <div class="stats-card">
                                <div class="stats-icon bg-warning-subtle text-warning">
                                    <i class="fe fe-percent"></i>
                                </div>
                                <div class="stats-detail">
                                    <div class="stats-value">${{ number_format($dStats->admin_fees, 2) }}</div>
                                    <div class="stats-label">Admin Fees Collected</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @elseif ($roleName === 'Referee')
                    <div class="row mb-4 g-3">
                        <div class="col-xl-3 col-lg-6 col-md-6">
                            <div class="stats-card">
                                <div class="stats-icon bg-primary-subtle text-primary">
                                    <i class="fe fe-map-pin"></i>
                                </div>
                                <div class="stats-detail">
                                    <div class="stats-value">{{ $refereeCamps->count() }}</div>
                                    <div class="stats-label">Assigned Camps</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6 col-md-6">
                            <div class="stats-card">
                                <div class="stats-icon bg-success-subtle text-success">
                                    <i class="fe fe-check-circle"></i>
                                </div>
                                <div class="stats-detail">
                                    <div class="stats-value">
                                        {{ $refereeCheckins->where('registration_status', 'checked_in')->count() }}</div>
                                    <div class="stats-label">Check-ins</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6 col-md-6">
                            <div class="stats-card">
                                <div class="stats-icon bg-info-subtle text-info">
                                    <i class="fe fe-dollar-sign"></i>
                                </div>
                                <div class="stats-detail">
                                    <div class="stats-value">{{ $refereePayments->count() }}</div>
                                    <div class="stats-label">Payments Made</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6 col-md-6">
                            <div class="stats-card">
                                <div class="stats-icon bg-warning-subtle text-warning">
                                    <i class="fe fe-star"></i>
                                </div>
                                <div class="stats-detail">
                                    <div class="stats-value">
                                        @php
                                            $avgScore = $refereeEvaluations->avg('average_score');
                                        @endphp
                                        {{ $avgScore ? number_format($avgScore, 1) : '—' }}
                                    </div>
                                    <div class="stats-label">Avg Evaluation Score</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @elseif ($roleName === 'Evaluator')
                    <div class="row mb-4 g-3">
                        <div class="col-xl-4 col-lg-4 col-md-6">
                            <div class="stats-card">
                                <div class="stats-icon bg-primary-subtle text-primary">
                                    <i class="fe fe-check-circle"></i>
                                </div>
                                <div class="stats-detail">
                                    <div class="stats-value">
                                        {{ $evaluatorRegistrations->where('status', 'approved')->count() }}</div>
                                    <div class="stats-label">Approved Registrations</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-lg-4 col-md-6">
                            <div class="stats-card">
                                <div class="stats-icon bg-success-subtle text-success">
                                    <i class="fe fe-file-text"></i>
                                </div>
                                <div class="stats-detail">
                                    <div class="stats-value">{{ $evaluatorEvaluations->count() }}</div>
                                    <div class="stats-label">Evaluations Done</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-lg-4 col-md-6">
                            <div class="stats-card">
                                <div class="stats-icon bg-info-subtle text-info">
                                    <i class="fe fe-send"></i>
                                </div>
                                <div class="stats-detail">
                                    <div class="stats-value">
                                        {{ $evaluatorEvaluations->where('status', 'submitted')->count() }}</div>
                                    <div class="stats-label">Submitted</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="row mb-4 g-3">
                        <div class="col-xl-3 col-lg-6 col-md-6">
                            <div class="stats-card">
                                <div class="stats-icon bg-primary-subtle text-primary">
                                    <i class="fe fe-user-check"></i>
                                </div>
                                <div class="stats-detail">
                                    <div class="stats-value">
                                        @if ($user->trashed())
                                            <span class="badge bg-dark"><i class="fe fe-trash-2 me-1"></i>Deleted</span>
                                        @else
                                            {{ $user->status == 'active' ? 'Active' : 'Inactive' }}
                                        @endif
                                    </div>
                                    <div class="stats-label">Account Status</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6 col-md-6">
                            <div class="stats-card">
                                <div class="stats-icon bg-info-subtle text-info">
                                    <i class="fe fe-clock"></i>
                                </div>
                                <div class="stats-detail">
                                    <div class="stats-value">
                                        @if ($user->last_activity_at)
                                            {{ $user->last_activity_at->diffForHumans() }}
                                        @else
                                            Never
                                        @endif
                                    </div>
                                    <div class="stats-label">Last Activity</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6 col-md-6">
                            <div class="stats-card">
                                <div class="stats-icon bg-success-subtle text-success">
                                    <i class="fe fe-calendar"></i>
                                </div>
                                <div class="stats-detail">
                                    <div class="stats-value">{{ $user->created_at->format('M d, Y') }}</div>
                                    <div class="stats-label">Member Since</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6 col-md-6">
                            <div class="stats-card">
                                <div class="stats-icon bg-secondary-subtle text-secondary">
                                    <i class="fe fe-shield"></i>
                                </div>
                                <div class="stats-detail">
                                    <div class="stats-value">{{ $user->roles->pluck('name')->implode(', ') ?: '—' }}</div>
                                    <div class="stats-label">Primary Role</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- MAIN CONTENT: TWO COLUMNS -->
                <div class="row">
                    <!-- LEFT: Personal & Account Info -->
                    <div class="col-xl-4 col-lg-5">
                        <!-- Basic Information -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fe fe-user me-2 text-muted"></i>Basic Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="info-row">
                                    <span class="info-label">Full Name</span>
                                    <span
                                        class="info-value">{{ $user->first_name . ' ' . $user->last_name ?: 'N/A' }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Username</span>
                                    <span class="info-value">{{ $user->username }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Gender</span>
                                    <span class="info-value">{{ ucfirst($user->profile?->gender) ?: 'N/A' }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Date of Birth</span>
                                    <span
                                        class="info-value">{{ $user->profile?->dob ? $user->profile->dob->format('d M Y') : 'N/A' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fe fe-phone me-2 text-muted"></i>Contact Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="info-row">
                                    <span class="info-label">Email</span>
                                    <span class="info-value text-break">{{ $user->email }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Phone</span>
                                    <span class="info-value">{{ $user->phone ?: 'N/A' }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">SMS Notifications</span>
                                    <span class="info-value">
                                        @if ($user->receive_sms_notifications)
                                            <span class="badge bg-success">Enabled</span>
                                        @else
                                            <span class="badge bg-secondary">Disabled</span>
                                        @endif
                                    </span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Address</span>
                                    <span class="info-value">{{ $user->address ?: 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT: Account & Role-Specific -->
                    <div class="col-xl-8 col-lg-7">
                        <!-- Account Details -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fe fe-settings me-2 text-muted"></i>Account Details
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <div class="detail-label">Account Status</div>
                                        <div class="mt-1">
                                            @if ($user->trashed())
                                                <span class="badge bg-dark fs-13 px-3 py-2">
                                                    <i class="fe fe-trash-2 me-1"></i> Deleted
                                                </span>
                                            @elseif ($user->status == 'active')
                                                <span class="badge bg-success fs-13 px-3 py-2">
                                                    <i class="fe fe-check-circle me-1 fs-11"></i> Active
                                                </span>
                                            @else
                                                <span class="badge bg-danger fs-13 px-3 py-2">
                                                    <i class="fe fe-x-circle me-1" style="font-size: 11px;"></i>
                                                    Inactive
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="detail-label">Email Verified</div>
                                        <div class="mt-1">
                                            @if ($user->email_verified_at)
                                                <span class="badge bg-success fs-13 px-3 py-2">
                                                    <i class="fe fe-check me-1" style="font-size: 11px;"></i>
                                                    Verified
                                                </span>
                                            @else
                                                <span class="badge bg-warning fs-13 px-3 py-2">
                                                    <i class="fe fe-alert-triangle me-1" style="font-size: 11px;"></i>
                                                    Not Verified
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="detail-label">SMS Notifications</div>
                                        <div class="mt-1">
                                            @if ($user->receive_sms_notifications)
                                                <span class="badge bg-info fs-13 p-3">
                                                    <i class="fe fe-message-square me-1"></i>Enabled
                                                </span>
                                            @else
                                                <span class="badge bg-secondary fs-13 px-3 py-2">Disabled</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="detail-label">Last Activity</div>
                                        <div class="mt-1 fw-semibold small">
                                            @if ($user->last_activity_at)
                                                {{ $user->last_activity_at->format('d M Y, h:i A') }}
                                                <br><span
                                                    class="text-muted">({{ $user->last_activity_at->diffForHumans() }})</span>
                                            @else
                                                <span class="text-muted">Never logged in</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="detail-label">Account Created</div>
                                        <div class="mt-1 fw-semibold small">
                                            {{ $user->created_at->format('d M Y, h:i A') }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="detail-label">Last Updated</div>
                                        <div class="mt-1 fw-semibold small">
                                            {{ $user->updated_at->format('d M Y, h:i A') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment / Stripe Information -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fe fe-credit-card me-2 text-muted"></i>Payment Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <div class="detail-label">Stripe Customer ID</div>
                                        <div class="mt-1">
                                            @if ($user->stripe_customer_id)
                                                <code class="small">{{ $user->stripe_customer_id }}</code>
                                            @else
                                                <span class="text-muted small">Not connected</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="detail-label">Stripe Account ID</div>
                                        <div class="mt-1">
                                            @if ($user->stripe_account_id)
                                                <code class="small">{{ $user->stripe_account_id }}</code>
                                            @else
                                                <span class="text-muted small">Not connected</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="detail-label">Stripe Onboarded</div>
                                        <div class="mt-1">
                                            @if ($user->stripe_onboarded_at)
                                                <span class="badge bg-success">
                                                    <i
                                                        class="fe fe-check me-1"></i>{{ $user->stripe_onboarded_at->format('d M Y') }}
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">Not onboarded</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Roles & Permissions -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fe fe-shield me-2 text-muted"></i>Roles & Permissions
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="detail-label mb-2">Assigned Roles</div>
                                    <div class="d-flex flex-wrap gap-2">
                                        @forelse ($user->roles as $role)
                                            @php
                                                $colors = [
                                                    'Admin' => 'danger',
                                                    'Director' => 'primary',
                                                    'Referee' => 'success',
                                                    'Evaluator' => 'info',
                                                ];
                                                $color = $colors[$role->name] ?? 'secondary';
                                            @endphp
                                            <span class="badge bg-{{ $color }} fs-13 px-3 py-2">
                                                <i class="fe fe-award me-1" style="font-size: 11px;"></i>
                                                {{ $role->name }}
                                            </span>
                                        @empty
                                            <span class="text-muted small">No roles assigned</span>
                                        @endforelse
                                    </div>
                                </div>
                                @if ($user->roles->isNotEmpty())
                                    <div>
                                        <div class="detail-label mb-2">Permissions
                                            ({{ $user->getAllPermissions()->count() }})</div>
                                        <div class="d-flex flex-wrap gap-1">
                                            @php
                                                $permissions = $user->getAllPermissions();
                                            @endphp
                                            @forelse($permissions as $permission)
                                                <span
                                                    class="badge bg-light text-dark border small px-2 py-1">{{ $permission->name }}</span>
                                            @empty
                                                <span class="text-muted small">No specific permissions</span>
                                            @endforelse
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- ======================== -->
                        <!-- ROLE-SPECIFIC SECTIONS   -->
                        <!-- ======================== -->

                        {{-- ── DIRECTOR: Revenue Overview ── --}}
                        @if ($roleName === 'Director')
                            @php
                                $revStats =
                                    $directorStats ?:
                                    (object) [
                                        'total_payments' => 0,
                                        'gross_revenue' => 0,
                                        'net_revenue' => 0,
                                        'admin_fees' => 0,
                                        'discounts_given' => 0,
                                    ];
                            @endphp
                            <div class="card mb-3">
                                <div class="card-header d-flex align-items-center justify-content-between">
                                    <h5 class="card-title mb-0">
                                        <i class="fe fe-bar-chart-2 me-2 text-muted"></i>Revenue Overview
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3 mb-3">
                                        <div class="col-6 col-md-3 text-center">
                                            <div class="fw-bold fs-5 text-primary">
                                                ${{ number_format($revStats->gross_revenue, 2) }}</div>
                                            <div class="text-muted small">Gross Revenue</div>
                                        </div>
                                        <div class="col-6 col-md-3 text-center">
                                            <div class="fw-bold fs-5 text-success">
                                                ${{ number_format($revStats->net_revenue, 2) }}</div>
                                            <div class="text-muted small">Net Revenue</div>
                                        </div>
                                        <div class="col-6 col-md-3 text-center">
                                            <div class="fw-bold fs-5 text-danger">
                                                ${{ number_format($revStats->admin_fees, 2) }}</div>
                                            <div class="text-muted small">Admin Fees</div>
                                        </div>
                                        <div class="col-6 col-md-3 text-center">
                                            <div class="fw-bold fs-5 text-warning">
                                                ${{ number_format($revStats->discounts_given, 2) }}</div>
                                            <div class="text-muted small">Discounts Given</div>
                                        </div>
                                    </div>

                                    @if ($directorRevenue->isNotEmpty())
                                        <div class="mt-3">
                                            {{-- Month Range Toggle --}}
                                            <div
                                                class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                                                <div class="detail-label">Monthly Revenue Breakdown</div>
                                                <div class="btn-group btn-group-sm month-toggle" role="group"
                                                    aria-label="Month range">
                                                    <button type="button" class="btn btn-outline-primary active"
                                                        data-months="6">6 Months</button>
                                                    <button type="button" class="btn btn-outline-primary"
                                                        data-months="12">12 Months</button>
                                                </div>
                                            </div>

                                            {{-- Legend --}}
                                            <div class="d-flex gap-3 mb-3 flex-wrap">
                                                <span class="small text-muted d-flex align-items-center gap-1">
                                                    <span class="legend-dot" style="background:#00AEEF;"></span> Net
                                                    Revenue
                                                </span>
                                                <span class="small text-muted d-flex align-items-center gap-1">
                                                    <span class="legend-dot" style="background:#10b981;"></span> Gross
                                                    Revenue
                                                </span>
                                                <span class="small text-muted d-flex align-items-center gap-1">
                                                    <span class="legend-dot" style="background:#f59e0b;"></span> Admin
                                                    Fees
                                                </span>
                                                <span class="small text-muted d-flex align-items-center gap-1">
                                                    <span class="legend-dot" style="background:#ef4444;"></span> Discounts
                                                </span>
                                                <span class="small text-muted d-flex align-items-center gap-1 ms-auto">
                                                    <i class="fe fe-activity me-1"></i>
                                                    <span class="txn-legend">Transactions</span>
                                                </span>
                                            </div>

                                            {{-- Chart --}}
                                            <div class="chart-wrapper">
                                                <canvas id="directorRevenueChart" height="240"></canvas>
                                            </div>

                                            {{-- Monthly Data Table --}}
                                            <div class="mt-3">
                                                <div class="table-responsive" style="max-height:260px;">
                                                    <table class="table table-sm table-hover mb-0 fs-12"
                                                        id="monthlyRevenueTable">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>Month</th>
                                                                <th class="text-end">Gross</th>
                                                                <th class="text-end">Admin Fee</th>
                                                                <th class="text-end">Discount</th>
                                                                <th class="text-end">Net Revenue</th>
                                                                <th class="text-center">Txns</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($directorRevenue as $rev)
                                                                <tr data-month="{{ $rev->month }}">
                                                                    <td class="fw-medium">
                                                                        @php
                                                                            $parts = explode('-', $rev->month);
                                                                            $monthName = date(
                                                                                'M',
                                                                                mktime(0, 0, 0, (int) $parts[1], 1),
                                                                            );
                                                                        @endphp
                                                                        {{ $monthName }} {{ $parts[0] }}
                                                                    </td>
                                                                    <td class="text-end">
                                                                        ${{ number_format($rev->gross_revenue, 2) }}</td>
                                                                    <td class="text-end text-warning">
                                                                        ${{ number_format($rev->admin_fees, 2) }}</td>
                                                                    <td class="text-end text-danger">
                                                                        ${{ number_format($rev->discounts, 2) }}</td>
                                                                    <td class="text-end fw-semibold text-primary">
                                                                        ${{ number_format($rev->net_revenue, 2) }}</td>
                                                                    <td class="text-center">
                                                                        <span
                                                                            class="badge bg-info-subtle text-info rounded-pill px-2">{{ $rev->transactions }}</span>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="mt-3">
                                            <div class="detail-label mb-3">Monthly Revenue Breakdown</div>
                                            <div class="text-center py-5 text-muted">
                                                <i class="fe fe-bar-chart-2 fs-3 mb-2 d-block" style="opacity:0.4;"></i>
                                                <span>No revenue data yet. The chart will populate once camp payments are
                                                    processed.</span>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- ── Year-over-Year Comparison ── --}}
                                    @if ($yearlyComparison->isNotEmpty())
                                        @php
                                            $currentYear = now()->year;
                                            $prevYear = $currentYear - 1;
                                            $years = $yearlyComparison->pluck('year')->unique()->sort()->values();
                                        @endphp
                                        <hr class="my-4">
                                        <div class="mt-3">
                                            <div
                                                class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                                                <div class="detail-label">Year-over-Year Comparison</div>
                                                <div class="btn-group btn-group-sm yoy-toggle" role="group"
                                                    aria-label="Metric">
                                                    <button type="button" class="btn btn-outline-primary active"
                                                        data-metric="net">Net Revenue</button>
                                                    <button type="button" class="btn btn-outline-primary"
                                                        data-metric="gross">Gross Revenue</button>
                                                    <button type="button" class="btn btn-outline-primary"
                                                        data-metric="txns">Transactions</button>
                                                </div>
                                            </div>

                                            <div class="row g-3 mb-3">
                                                @foreach ($years as $yr)
                                                    @php
                                                        $yrGross = $yearlyComparison
                                                            ->where('year', $yr)
                                                            ->sum('gross_revenue');
                                                        $yrNet = $yearlyComparison
                                                            ->where('year', $yr)
                                                            ->sum('net_revenue');
                                                        $yrFees = $yearlyComparison
                                                            ->where('year', $yr)
                                                            ->sum('admin_fees');
                                                        $yrTxns = $yearlyComparison
                                                            ->where('year', $yr)
                                                            ->sum('transactions');
                                                        $yearLabel = $yr == $currentYear ? 'Current Year' : $yr;
                                                    @endphp
                                                    <div class="col-6 col-md-3">
                                                        <div
                                                            class="stats-card py-2 px-3 {{ $yr == $currentYear ? 'border-primary' : '' }}">
                                                            <div class="w-100 text-center">
                                                                <div class="fw-semibold fs-13 mb-2">
                                                                    @if ($yr == $currentYear)
                                                                        <span
                                                                            class="badge bg-primary rounded-pill">{{ $yr }}</span>
                                                                        <span
                                                                            class="text-muted small d-block">(Current)</span>
                                                                    @else
                                                                        <span
                                                                            class="text-muted">{{ $yr }}</span>
                                                                    @endif
                                                                </div>
                                                                <div class="fw-bold fs-5 text-primary">
                                                                    ${{ number_format($yrNet, 2) }}</div>
                                                                <div class="text-muted small">Net Revenue</div>
                                                                <div class="mt-1 small">
                                                                    <span
                                                                        class="text-success fw-medium">${{ number_format($yrGross, 2) }}</span>
                                                                    <span class="text-muted"> gross</span>
                                                                </div>
                                                                <div class="small">
                                                                    <span class="fw-medium">{{ $yrTxns }}</span>
                                                                    <span class="text-muted"> txns</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>

                                            <div class="d-flex gap-3 mb-3 flex-wrap">
                                                @foreach ($years as $yr)
                                                    <span class="small text-muted d-flex align-items-center gap-1">
                                                        <span class="legend-dot"
                                                            style="background:{{ $yr == $currentYear ? '#00AEEF' : '#94a3b8' }};"></span>
                                                        {{ $yr }}
                                                    </span>
                                                @endforeach
                                            </div>

                                            <div class="chart-wrapper">
                                                <canvas id="yearlyComparisonChart" height="240"></canvas>
                                            </div>

                                            {{-- Yearly Data Table --}}
                                            <div class="mt-3">
                                                <div class="table-responsive" style="max-height:260px;">
                                                    <table class="table table-sm table-hover mb-0 fs-12"
                                                        id="yearlyComparisonTable">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>Month</th>
                                                                @foreach ($years as $yr)
                                                                    <th class="text-end">{{ $yr }} Gross</th>
                                                                    <th class="text-end">{{ $yr }} Net</th>
                                                                @endforeach
                                                                <th class="text-center">Δ %</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @php
                                                                $monthNames = [
                                                                    'Jan',
                                                                    'Feb',
                                                                    'Mar',
                                                                    'Apr',
                                                                    'May',
                                                                    'Jun',
                                                                    'Jul',
                                                                    'Aug',
                                                                    'Sep',
                                                                    'Oct',
                                                                    'Nov',
                                                                    'Dec',
                                                                ];
                                                            @endphp
                                                            @foreach ($monthNames as $idx => $mName)
                                                                @php
                                                                    $monthNum = $idx + 1;
                                                                    $currentData = $yearlyComparison
                                                                        ->where('year', $currentYear)
                                                                        ->where('month_num', $monthNum)
                                                                        ->first();
                                                                    $prevData = $yearlyComparison
                                                                        ->where('year', $prevYear)
                                                                        ->where('month_num', $monthNum)
                                                                        ->first();
                                                                    $currentNet = $currentData
                                                                        ? (float) $currentData->net_revenue
                                                                        : 0;
                                                                    $prevNet = $prevData
                                                                        ? (float) $prevData->net_revenue
                                                                        : 0;
                                                                    $hasData = $currentData || $prevData;
                                                                @endphp
                                                                @if ($hasData)
                                                                    <tr>
                                                                        <td class="fw-medium">{{ $mName }}</td>
                                                                        @foreach ($years as $yr)
                                                                            @php
                                                                                $yd = $yearlyComparison
                                                                                    ->where('year', $yr)
                                                                                    ->where('month_num', $monthNum)
                                                                                    ->first();
                                                                                $yg = $yd ? $yd->gross_revenue : 0;
                                                                                $yn = $yd ? $yd->net_revenue : 0;
                                                                            @endphp
                                                                            <td class="text-end small">
                                                                                ${{ number_format($yg, 2) }}</td>
                                                                            <td class="text-end">
                                                                                <span
                                                                                    class="fw-medium text-primary">${{ number_format($yn, 2) }}</span>
                                                                            </td>
                                                                        @endforeach
                                                                        <td class="text-center">
                                                                            @if ($prevNet > 0)
                                                                                @php $pctChange = round((($currentNet - $prevNet) / $prevNet) * 100, 1); @endphp
                                                                                <span
                                                                                    class="badge bg-{{ $pctChange >= 0 ? 'success' : 'danger' }}-subtle text-{{ $pctChange >= 0 ? 'success' : 'danger' }} rounded-pill">
                                                                                    {{ $pctChange >= 0 ? '+' : '' }}{{ $pctChange }}%
                                                                                </span>
                                                                            @elseif ($currentNet > 0)
                                                                                <span
                                                                                    class="badge bg-success-subtle text-success rounded-pill">New</span>
                                                                            @else
                                                                                <span class="text-muted">—</span>
                                                                            @endif
                                                                        </td>
                                                                    </tr>
                                                                @endif
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- ── DIRECTOR: Managed Camps ── --}}
                        @if ($roleName === 'Director' && $directorCamps->isNotEmpty())
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fe fe-grid me-2 text-muted"></i>Managed Camps
                                        ({{ $directorCamps->count() }})
                                    </h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover table-sm mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Camp Name</th>
                                                    <th>Sport</th>
                                                    <th>Price</th>
                                                    <th>Dates</th>
                                                    <th>Status</th>
                                                    <th class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($directorCamps as $camp)
                                                    <tr>
                                                        <td class="fw-semibold">
                                                            <a href="{{ route('admin.camps.show', $camp->id) }}"
                                                                class="text-decoration-none">
                                                                {{ $camp->camp_name }}
                                                            </a>
                                                        </td>
                                                        <td>{{ $camp->sportsType?->sports_name ?? '—' }}</td>
                                                        <td>${{ number_format($camp->price, 2) }}</td>
                                                        <td class="small">
                                                            @if ($camp->start_date && $camp->end_date)
                                                                {{ $camp->start_date->format('M d') }} -
                                                                {{ $camp->end_date->format('M d, Y') }}
                                                            @else
                                                                <span class="text-muted">—</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if ($camp->status == 'active')
                                                                <span class="badge bg-success">Active</span>
                                                            @else
                                                                <span class="badge bg-secondary">Inactive</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            <a href="{{ route('admin.camps.show', $camp->id) }}"
                                                                class="btn btn-sm btn-outline-primary" title="View Camp">
                                                                <i class="fe fe-eye"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- ── REFEREE: Assigned Camps & Jersey Numbers ── --}}
                        @if ($roleName === 'Referee' && $refereeCamps->isNotEmpty())
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fe fe-map-pin me-2 text-muted"></i>Assigned Camps & Jersey Numbers
                                    </h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover table-sm mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Camp Name</th>
                                                    <th>Jersey #</th>
                                                    <th>Location</th>
                                                    <th>Dates</th>
                                                    <th>Status</th>
                                                    <th class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $jerseyNumbers = $user->refereeCamps->keyBy('id');
                                                @endphp
                                                @foreach ($refereeCamps as $camp)
                                                    @php $jersey = $jerseyNumbers->get($camp->id)?->pivot?->jersey_number; @endphp
                                                    <tr>
                                                        <td class="fw-semibold">
                                                            <a href="{{ route('admin.camps.show', $camp->id) }}"
                                                                class="text-decoration-none">
                                                                {{ $camp->camp_name }}
                                                            </a>
                                                        </td>
                                                        <td>
                                                            @if ($jersey)
                                                                <span
                                                                    class="badge bg-primary rounded-pill">#{{ $jersey }}</span>
                                                            @else
                                                                <span class="text-muted">—</span>
                                                            @endif
                                                        </td>
                                                        <td class="small">{{ $camp->location ?: '—' }}</td>
                                                        <td class="small">
                                                            @if ($camp->start_date && $camp->end_date)
                                                                {{ $camp->start_date->format('M d') }} -
                                                                {{ $camp->end_date->format('M d, Y') }}
                                                            @else
                                                                <span class="text-muted">—</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if ($camp->status == 'active')
                                                                <span class="badge bg-success">Active</span>
                                                            @else
                                                                <span class="badge bg-secondary">Inactive</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            <a href="{{ route('admin.camps.show', $camp->id) }}"
                                                                class="btn btn-sm btn-outline-primary" title="View Camp">
                                                                <i class="fe fe-eye"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- ── REFEREE: Check-in History ── --}}
                        @if ($roleName === 'Referee' && $refereeCheckins->isNotEmpty())
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fe fe-check-circle me-2 text-muted"></i>Check-in History
                                        ({{ $refereeCheckins->count() }})
                                    </h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover table-sm mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Camp</th>
                                                    <th>Status</th>
                                                    <th>Registered At</th>
                                                    <th>Checked In At</th>
                                                    <th>Checked In By</th>
                                                    <th class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($refereeCheckins as $checkin)
                                                    <tr>
                                                        <td class="fw-semibold">
                                                            @if ($checkin->camp)
                                                                <a href="{{ route('admin.camps.show', $checkin->camp_id) }}"
                                                                    class="text-decoration-none">
                                                                    {{ $checkin->camp->camp_name }}
                                                                </a>
                                                            @else
                                                                <span class="text-muted">—</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if ($checkin->registration_status == 'checked_in')
                                                                <span class="badge bg-success">Checked In</span>
                                                            @else
                                                                <span class="badge bg-warning">Registered</span>
                                                            @endif
                                                        </td>
                                                        <td class="small">
                                                            {{ $checkin->registered_at ? date('d M Y, h:i A', strtotime($checkin->registered_at)) : '—' }}
                                                        </td>
                                                        <td class="small">
                                                            {{ $checkin->checked_in_at ? date('d M Y, h:i A', strtotime($checkin->checked_in_at)) : '—' }}
                                                        </td>
                                                        <td>
                                                            @if ($checkin->checked_in_by == 'self')
                                                                <span class="badge bg-info">Self</span>
                                                            @elseif ($checkin->checked_in_by == 'director')
                                                                <span class="badge bg-primary">Director</span>
                                                            @else
                                                                <span class="text-muted">—</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            @if ($checkin->camp)
                                                                <a href="{{ route('admin.camps.show', $checkin->camp_id) }}"
                                                                    class="btn btn-sm btn-outline-primary"
                                                                    title="View Camp">
                                                                    <i class="fe fe-eye"></i>
                                                                </a>
                                                            @else
                                                                <span class="text-muted">—</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- ── REFEREE: Payment History ── --}}
                        @if ($roleName === 'Referee' && $refereePayments->isNotEmpty())
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fe fe-credit-card me-2 text-muted"></i>Payment History
                                        ({{ $refereePayments->count() }})
                                    </h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover table-sm mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Camp</th>
                                                    <th>Amount</th>
                                                    <th>Admin Fee</th>
                                                    <th>Discount</th>
                                                    <th>Status</th>
                                                    <th>Paid At</th>
                                                    <th class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($refereePayments as $payment)
                                                    <tr>
                                                        <td class="fw-semibold">
                                                            @if ($payment->camp)
                                                                <a href="{{ route('admin.camps.show', $payment->camp_id) }}"
                                                                    class="text-decoration-none">
                                                                    {{ $payment->camp->camp_name }}
                                                                </a>
                                                            @else
                                                                <span class="text-muted">—</span>
                                                            @endif
                                                        </td>
                                                        <td>${{ number_format($payment->amount, 2) }}</td>
                                                        <td class="text-muted small">
                                                            ${{ number_format($payment->admin_fee ?? 0, 2) }}</td>
                                                        <td class="text-muted small">
                                                            ${{ number_format($payment->discount_amount ?? 0, 2) }}</td>
                                                        <td>
                                                            @if ($payment->status == 'succeeded')
                                                                <span class="badge bg-success">Succeeded</span>
                                                            @elseif ($payment->status == 'refunded')
                                                                <span class="badge bg-danger">Refunded</span>
                                                            @else
                                                                <span
                                                                    class="badge bg-warning">{{ ucfirst($payment->status) }}</span>
                                                            @endif
                                                        </td>
                                                        <td class="small">
                                                            {{ $payment->paid_at ? date('d M Y', strtotime($payment->paid_at)) : '—' }}
                                                        </td>
                                                        <td class="text-center">
                                                            @if ($payment->camp)
                                                                <a href="{{ route('admin.camps.show', $payment->camp_id) }}"
                                                                    class="btn btn-sm btn-outline-primary"
                                                                    title="View Camp">
                                                                    <i class="fe fe-eye"></i>
                                                                </a>
                                                            @else
                                                                <span class="text-muted">—</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- ── REFEREE: Evaluations Received ── --}}
                        @if ($roleName === 'Referee' && $refereeEvaluations->isNotEmpty())
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fe fe-star me-2 text-muted"></i>Evaluations Received
                                        ({{ $refereeEvaluations->count() }})
                                    </h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover table-sm mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Evaluator</th>
                                                    <th>Avg Score</th>
                                                    <th>Status</th>
                                                    <th>Submitted</th>
                                                    <th class="text-truncate" style="max-width:180px;">Feedback</th>
                                                    <th class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($refereeEvaluations as $eval)
                                                    <tr>
                                                        <td>
                                                            <a href="{{ route('admin.users.manage.show', $eval->evaluator_id) }}"
                                                                class="text-decoration-none">
                                                                {{ $eval->evaluator?->first_name . ' ' . $eval->evaluator?->last_name ?: '—' }}
                                                            </a>
                                                        </td>
                                                        <td class="fw-semibold">
                                                            {{ $eval->average_score ? number_format($eval->average_score, 1) . ' / 10' : '—' }}
                                                        </td>
                                                        <td>
                                                            @if ($eval->status == 'submitted')
                                                                <span class="badge bg-success">Submitted</span>
                                                            @else
                                                                <span class="badge bg-secondary">Draft</span>
                                                            @endif
                                                        </td>
                                                        <td class="small">
                                                            {{ $eval->submitted_at ? date('d M Y', strtotime($eval->submitted_at)) : '—' }}
                                                        </td>
                                                        <td class="small text-muted text-truncate"
                                                            style="max-width:180px;">
                                                            {{ $eval->referee_feedback ?: '—' }}
                                                        </td>
                                                        <td class="text-center">
                                                            @if ($eval->camp)
                                                                <a href="{{ route('admin.camps.show', $eval->camp_id) }}"
                                                                    class="btn btn-sm btn-outline-primary"
                                                                    title="View Camp">
                                                                    <i class="fe fe-eye"></i>
                                                                </a>
                                                            @else
                                                                <span class="text-muted">—</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- ── REFEREE: Crew Memberships ── --}}
                        @if ($roleName === 'Referee' && $crewMemberships->isNotEmpty())
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fe fe-users me-2 text-muted"></i>Crew Memberships
                                        ({{ $crewMemberships->count() }})
                                    </h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover table-sm mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Crew Name</th>
                                                    <th>Camp</th>
                                                    <th>Joined At</th>
                                                    <th class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($crewMemberships as $membership)
                                                    <tr>
                                                        <td class="fw-semibold">{{ $membership->crew?->name ?? '—' }}
                                                        </td>
                                                        <td>
                                                            @if ($membership->crew?->camp)
                                                                <a href="{{ route('admin.camps.show', $membership->crew->camp_id) }}"
                                                                    class="text-decoration-none">
                                                                    {{ $membership->crew->camp->camp_name }}
                                                                </a>
                                                            @else
                                                                <span class="text-muted">—</span>
                                                            @endif
                                                        </td>
                                                        <td class="small">
                                                            {{ $membership->joined_at ? date('d M Y', strtotime($membership->joined_at)) : '—' }}
                                                        </td>
                                                        <td class="text-center">
                                                            @if ($membership->crew?->camp)
                                                                <a href="{{ route('admin.camps.show', $membership->crew->camp_id) }}"
                                                                    class="btn btn-sm btn-outline-primary"
                                                                    title="View Camp">
                                                                    <i class="fe fe-eye"></i>
                                                                </a>
                                                            @else
                                                                <span class="text-muted">—</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- ── EVALUATOR: Camp Registrations ── --}}
                        @if ($roleName === 'Evaluator' && $evaluatorRegistrations->isNotEmpty())
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fe fe-send me-2 text-muted"></i>Camp Registrations
                                        ({{ $evaluatorRegistrations->count() }})
                                    </h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover table-sm mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Camp</th>
                                                    <th>Status</th>
                                                    <th>Registered At</th>
                                                    <th>Approved At</th>
                                                    <th>Note</th>
                                                    <th class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($evaluatorRegistrations as $reg)
                                                    <tr>
                                                        <td class="fw-semibold">
                                                            @if ($reg->camp)
                                                                <a href="{{ route('admin.camps.show', $reg->camp_id) }}"
                                                                    class="text-decoration-none">
                                                                    {{ $reg->camp->camp_name }}
                                                                </a>
                                                            @else
                                                                <span class="text-muted">—</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if ($reg->status == 'approved')
                                                                <span class="badge bg-success">Approved</span>
                                                            @elseif ($reg->status == 'rejected')
                                                                <span class="badge bg-danger">Rejected</span>
                                                            @else
                                                                <span class="badge bg-warning">Pending</span>
                                                            @endif
                                                        </td>
                                                        <td class="small">
                                                            {{ $reg->registered_at ? date('d M Y', strtotime($reg->registered_at)) : '—' }}
                                                        </td>
                                                        <td class="small">
                                                            {{ $reg->approved_at ? date('d M Y', strtotime($reg->approved_at)) : '—' }}
                                                        </td>
                                                        <td class="small text-muted text-truncate"
                                                            style="max-width:150px;">{{ $reg->registration_note ?: '—' }}
                                                        </td>
                                                        <td class="text-center">
                                                            @if ($reg->camp)
                                                                <a href="{{ route('admin.camps.show', $reg->camp_id) }}"
                                                                    class="btn btn-sm btn-outline-primary"
                                                                    title="View Camp">
                                                                    <i class="fe fe-eye"></i>
                                                                </a>
                                                            @else
                                                                <span class="text-muted">—</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- ── EVALUATOR: Evaluations Conducted ── --}}
                        @if ($roleName === 'Evaluator' && $evaluatorEvaluations->isNotEmpty())
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fe fe-file-text me-2 text-muted"></i>Evaluations Conducted
                                        ({{ $evaluatorEvaluations->count() }})
                                    </h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover table-sm mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Referee</th>
                                                    <th>Camp</th>
                                                    <th>Avg Score</th>
                                                    <th>Status</th>
                                                    <th>Submitted</th>
                                                    <th class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($evaluatorEvaluations as $eval)
                                                    <tr>
                                                        <td>
                                                            <a href="{{ route('admin.users.manage.show', $eval->referee_id) }}"
                                                                class="text-decoration-none">
                                                                {{ $eval->referee?->first_name . ' ' . $eval->referee?->last_name ?: '—' }}
                                                            </a>
                                                        </td>
                                                        <td>
                                                            @if ($eval->camp)
                                                                <a href="{{ route('admin.camps.show', $eval->camp_id) }}"
                                                                    class="text-decoration-none">
                                                                    {{ $eval->camp->camp_name }}
                                                                </a>
                                                            @else
                                                                <span class="text-muted">—</span>
                                                            @endif
                                                        </td>
                                                        <td class="fw-semibold">
                                                            {{ $eval->average_score ? number_format($eval->average_score, 1) . ' / 10' : '—' }}
                                                        </td>
                                                        <td>
                                                            @if ($eval->status == 'submitted')
                                                                <span class="badge bg-success">Submitted</span>
                                                            @else
                                                                <span class="badge bg-secondary">Draft</span>
                                                            @endif
                                                        </td>
                                                        <td class="small">
                                                            {{ $eval->submitted_at ? date('d M Y', strtotime($eval->submitted_at)) : '—' }}
                                                        </td>
                                                        <td class="text-center">
                                                            @if ($eval->camp)
                                                                <a href="{{ route('admin.camps.show', $eval->camp_id) }}"
                                                                    class="btn btn-sm btn-outline-primary"
                                                                    title="View Camp">
                                                                    <i class="fe fe-eye"></i>
                                                                </a>
                                                                <a href="{{ route('admin.users.manage.show', $eval->referee_id) }}"
                                                                    class="btn btn-sm btn-outline-info ms-1"
                                                                    title="View Referee">
                                                                    <i class="fe fe-user"></i>
                                                                </a>
                                                            @else
                                                                <span class="text-muted">—</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- ── Biography ── --}}
                        @if ($user->biography)
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fe fe-file-text me-2 text-muted"></i>Biography
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <p class="mb-0">{{ $user->biography }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        /* Profile Cover */
        .profile-cover {
            height: 120px;
            background:
                linear-gradient(rgba(0, 0, 0, 0.25), rgba(0, 0, 0, 0.25)),
                url('{{ asset('default/profile-background.jpg') }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            border-radius: 6px 6px 0 0;
        }

        .profile-avatar-wrapper {
            position: relative;
            flex-shrink: 0;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #fff;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.12);
        }

        .status-indicator {
            position: absolute;
            bottom: 6px;
            right: 6px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 2.5px solid #fff;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.15);
        }

        /* Stats Cards */
        .stats-card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            /* box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06); */
            border: 1px solid #f0f0f0;
            transition: box-shadow 0.2s, transform 0.2s;
        }

        .stats-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }

        .stats-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .stats-value {
            font-size: 22px;
            font-weight: 700;
            line-height: 1.2;
            color: #1e293b;
        }

        .stats-label {
            font-size: 13px;
            color: #64748b;
            margin-top: 2px;
        }

        /* Info Rows */
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            padding: 8px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-size: 13px;
            color: #64748b;
            flex-shrink: 0;
            margin-right: 12px;
        }

        .info-value {
            font-size: 14px;
            font-weight: 500;
            color: #1e293b;
            text-align: right;
            word-break: break-word;
        }

        /* Detail Labels */
        .detail-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #94a3b8;
        }

        /* Card Header */
        .card-header {
            background: transparent;
            border-bottom: 1px solid #f1f5f9;
            padding: 14px 20px;
        }

        .card-title {
            font-size: 15px;
            font-weight: 600;
            color: #1e293b;
        }

        /* Table Adjustments */
        .table> :not(caption)>*>* {
            padding: 10px 12px;
            vertical-align: middle;
            font-size: 13px;
        }

        .table-light th {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #64748b;
            border-bottom: 1px solid #e2e8f0 !important;
        }

        /* Badges */
        .badge {
            font-weight: 500;
        }

        /* Misc */
        .bg-primary-subtle {
            background: #e8f4fd;
        }

        .bg-success-subtle {
            background: #e6f9ed;
        }

        .bg-info-subtle {
            background: #e5f6fd;
        }

        .bg-warning-subtle {
            background: #fef6e6;
        }

        .bg-danger-subtle {
            background: #fde8e8;
        }

        .bg-secondary-subtle {
            background: #f1f3f5;
        }

        .text-primary {
            color: #00AEEF !important;
        }

        .text-success {
            color: #10b981 !important;
        }

        .text-info {
            color: #0ea5e9 !important;
        }

        .text-warning {
            color: #f59e0b !important;
        }

        .text-danger {
            color: #ef4444 !important;
        }

        .text-secondary {
            color: #64748b !important;
        }

        .rounded-pill {
            border-radius: 50rem !important;
        }

        .fs-12 {
            font-size: 12px !important;
        }

        .fs-13 {
            font-size: 13px !important;
        }

        /* Print Styles */
        @media print {
            body {
                background: #fff !important;
                font-size: 12px;
                color: #1e293b;
            }

            /* Hide all non-content UI */
            .app-sidebar,
            .app-header,
            .page-header,
            .breadcrumb,
            .footer,
            .btn-group,
            .no-print {
                display: none !important;
            }

            /* Reset page layout */
            .app-content,
            .side-app,
            .main-container {
                margin: 0 !important;
                padding: 0 !important;
                max-width: 100% !important;
            }

            .row {
                display: block !important;
            }

            .col-xl-4,
            .col-xl-8,
            .col-lg-5,
            .col-lg-7,
            .col-md-6,
            .col-md-4,
            .col-md-3,
            .col-12 {
                width: 100% !important;
                max-width: 100% !important;
                flex: none !important;
                padding: 0 !important;
            }

            /* Cards — clean, no shadow, no border */
            .card {
                border: 1px solid #e2e8f0 !important;
                box-shadow: none !important;
                border-radius: 4px !important;
                margin-bottom: 12px !important;
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .card-header {
                padding: 8px 12px !important;
                border-bottom: 1px solid #e2e8f0 !important;
            }

            .card-body {
                padding: 10px 12px !important;
            }

            .card-title {
                font-size: 13px !important;
            }

            /* Profile image */
            .profile-cover {
                height: 80px !important;
                border-radius: 4px 4px 0 0 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .profile-avatar {
                width: 72px !important;
                height: 72px !important;
                border-width: 3px !important;
            }

            .status-indicator {
                display: none !important;
            }

            /* Stats cards — horizontal row */
            .stats-card {
                box-shadow: none !important;
                border: 1px solid #e2e8f0 !important;
                padding: 10px 14px !important;
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .stats-icon {
                width: 36px !important;
                height: 36px !important;
                font-size: 16px !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .stats-value {
                font-size: 18px !important;
            }

            .stats-label {
                font-size: 11px !important;
            }

            /* Tables */
            .table {
                font-size: 10px !important;
            }

            .table> :not(caption)>*>* {
                padding: 5px 8px !important;
            }

            .table-light th {
                font-size: 10px !important;
                background: #f8fafc !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            /* Badges */
            .badge {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            /* Chart */
            .chart-wrapper {
                min-height: 160px !important;
            }

            canvas {
                max-height: 160px !important;
                background: #fff !important;
            }

            /* Info rows */
            .info-row {
                padding: 4px 0 !important;
            }

            .info-label {
                font-size: 11px !important;
            }

            .info-value {
                font-size: 12px !important;
            }

            .detail-label {
                font-size: 10px !important;
            }

            /* Print-only header */
            .print-header {
                display: block !important;
            }

            /* Hide g-3 gutters causing overflow */
            .g-3 {
                margin: 0 !important;
            }

            .g-3>[class*="col-"] {
                padding: 4px !important;
            }

            /* Card body p-0 (tables) gets some padding */
            .card-body.p-0 {
                padding: 0 !important;
            }

            /* Print header */
            .print-header {
                display: block !important;
                text-align: center;
                margin-bottom: 20px;
                padding-bottom: 12px;
                border-bottom: 2px solid #1e293b;
            }

            .print-header-title {
                font-size: 18px;
                font-weight: 700;
                color: #1e293b;
            }

            .print-header-subtitle {
                font-size: 13px;
                color: #475569;
                margin-top: 4px;
            }

            .print-header-date {
                font-size: 10px;
                color: #94a3b8;
                margin-top: 2px;
            }
        }

        .print-header {
            display: none;
        }

        /* ── Chart ── */
        .chart-wrapper {
            position: relative;
            width: 100%;
            min-height: 220px;
            padding: 8px 0;
        }

        .legend-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 2px;
            margin-right: 4px;
            vertical-align: middle;
        }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .profile-avatar {
                width: 80px;
                height: 80px;
            }

            .stats-value {
                font-size: 18px;
            }

            .stats-icon {
                width: 40px;
                height: 40px;
                font-size: 16px;
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        @if ($roleName === 'Director' && $directorRevenue->isNotEmpty())
            (function() {
                var ctx = document.getElementById('directorRevenueChart');
                if (!ctx) return;

                // Prepare data from server
                var allData = {!! json_encode(
                    $directorRevenue->map(function ($rev) {
                            $parts = explode('-', $rev->month);
                            $monthName = date('M', mktime(0, 0, 0, (int) $parts[1], 1));
                            return [
                                'label' => $monthName . " '" . $parts[0][2] . $parts[0][3],
                                'month' => $rev->month,
                                'gross' => (float) $rev->gross_revenue,
                                'net' => (float) $rev->net_revenue,
                                'fees' => (float) $rev->admin_fees,
                                'discounts' => (float) $rev->discounts,
                                'txns' => (int) $rev->transactions,
                            ];
                        })->values(),
                ) !!};

                // Show latest 6 months by default
                var displayCount = 6;
                var chartInstance = null;

                function getSubset(count) {
                    return allData.slice(0, count).reverse(); // Show oldest → newest
                }

                function formatTooltipValue(val) {
                    return '$' + val.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }

                function renderChart(count) {
                    var subset = getSubset(count);
                    var labels = subset.map(function(d) {
                        return d.label;
                    });
                    var grossData = subset.map(function(d) {
                        return d.gross;
                    });
                    var netData = subset.map(function(d) {
                        return d.net;
                    });
                    var feeData = subset.map(function(d) {
                        return d.fees;
                    });
                    var discData = subset.map(function(d) {
                        return d.discounts;
                    });
                    var txnData = subset.map(function(d) {
                        return d.txns;
                    });

                    if (chartInstance) {
                        chartInstance.destroy();
                    }

                    chartInstance = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [{
                                    label: 'Gross Revenue',
                                    data: grossData,
                                    backgroundColor: 'rgba(16, 185, 129, 0.7)',
                                    borderColor: '#10b981',
                                    borderWidth: 1,
                                    borderRadius: 3,
                                    borderSkipped: false,
                                    order: 2,
                                    yAxisID: 'y',
                                },
                                {
                                    label: 'Admin Fees',
                                    data: feeData,
                                    backgroundColor: 'rgba(245, 158, 11, 0.7)',
                                    borderColor: '#f59e0b',
                                    borderWidth: 1,
                                    borderRadius: 3,
                                    borderSkipped: false,
                                    order: 2,
                                    yAxisID: 'y',
                                },
                                {
                                    label: 'Discounts',
                                    data: discData,
                                    backgroundColor: 'rgba(239, 68, 68, 0.6)',
                                    borderColor: '#ef4444',
                                    borderWidth: 1,
                                    borderRadius: 3,
                                    borderSkipped: false,
                                    order: 2,
                                    yAxisID: 'y',
                                },
                                {
                                    label: 'Net Revenue',
                                    data: netData,
                                    backgroundColor: 'rgba(0, 174, 239, 0.85)',
                                    borderColor: '#00AEEF',
                                    borderWidth: 2,
                                    borderRadius: 4,
                                    borderSkipped: false,
                                    order: 2,
                                    yAxisID: 'y',
                                },
                                {
                                    label: 'Transactions',
                                    data: txnData,
                                    type: 'line',
                                    borderColor: '#8b5cf6',
                                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                                    pointBackgroundColor: '#8b5cf6',
                                    pointBorderColor: '#fff',
                                    pointBorderWidth: 2,
                                    pointRadius: 4,
                                    pointHoverRadius: 6,
                                    borderWidth: 2,
                                    tension: 0.3,
                                    fill: false,
                                    order: 1,
                                    yAxisID: 'y1',
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                mode: 'index',
                                intersect: false,
                            },
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    backgroundColor: '#1e293b',
                                    titleColor: '#f1f5f9',
                                    bodyColor: '#e2e8f0',
                                    titleFont: {
                                        size: 13,
                                        weight: '600'
                                    },
                                    bodyFont: {
                                        size: 12
                                    },
                                    padding: 12,
                                    cornerRadius: 8,
                                    boxPadding: 4,
                                    callbacks: {
                                        label: function(context) {
                                            var label = context.dataset.label || '';
                                            var val = context.parsed.y;
                                            if (context.dataset.label === 'Transactions') {
                                                return label + ': ' + val;
                                            }
                                            return label + ': ' + formatTooltipValue(val);
                                        },
                                        afterBody: function(items) {
                                            // Show net revenue summary
                                            var net = items.find(function(i) {
                                                return i.dataset.label === 'Net Revenue';
                                            });
                                            if (net) {
                                                return 'Net: ' + formatTooltipValue(net.parsed.y);
                                            }
                                            return '';
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    position: 'left',
                                    grid: {
                                        color: 'rgba(0,0,0,0.06)',
                                        drawBorder: false,
                                    },
                                    ticks: {
                                        callback: function(value) {
                                            if (value >= 1000) {
                                                return '$' + (value / 1000).toFixed(0) + 'k';
                                            }
                                            return '$' + value.toFixed(0);
                                        },
                                        font: {
                                            size: 11
                                        },
                                        color: '#94a3b8'
                                    }
                                },
                                y1: {
                                    beginAtZero: true,
                                    position: 'right',
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        precision: 0,
                                        font: {
                                            size: 11
                                        },
                                        color: '#a78bfa'
                                    }
                                },
                                x: {
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        font: {
                                            size: 11
                                        },
                                        color: '#94a3b8'
                                    }
                                }
                            },
                            animation: {
                                duration: 600,
                                easing: 'easeOutQuart'
                            }
                        }
                    });
                }

                // Initial render
                renderChart(displayCount);

                // Month toggle
                document.querySelectorAll('.month-toggle .btn').forEach(function(btn) {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        var parent = this.closest('.month-toggle');
                        parent.querySelectorAll('.btn').forEach(function(b) {
                            b.classList.remove('active');
                        });
                        this.classList.add('active');
                        var months = parseInt(this.dataset.months, 10);
                        if (months > allData.length) {
                            months = allData.length;
                        }
                        renderChart(months);

                        // Highlight table rows
                        document.querySelectorAll('#monthlyRevenueTable tbody tr').forEach(function(
                            row) {
                            var monthVal = row.dataset.month;
                            var inRange = allData.slice(0, months).some(function(d) {
                                return d.month === monthVal;
                            });
                            row.style.display = inRange ? '' : 'none';
                        });
                    });
                });




                @if ($roleName === 'Director' && $yearlyComparison->isNotEmpty())
                    (function() {
                        var yoyCtx = document.getElementById('yearlyComparisonChart');
                        if (!yoyCtx) return;

                        var yoyData = {!! json_encode(
                            $yearlyComparison->map(function ($r) {
                                    return [
                                        'year' => (int) $r->year,
                                        'month_num' => (int) $r->month_num,
                                        'gross' => (float) $r->gross_revenue,
                                        'net' => (float) $r->net_revenue,
                                        'fees' => (float) $r->admin_fees,
                                        'discounts' => (float) $r->discounts,
                                        'txns' => (int) $r->transactions,
                                    ];
                                })->values(),
                        ) !!};

                        var currentYear = {{ now()->year }};
                        var prevYear = currentYear - 1;
                        var monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct',
                            'Nov', 'Dec'
                        ];

                        var yoyChartInstance = null;
                        var currentMetric = 'net';

                        function formatMoney(v) {
                            return '$' + v.toLocaleString('en-US', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        }

                        function getYoySubset(metric) {
                            var labels = [];
                            var currentData = [];
                            var prevData = [];
                            var nowMonth = (new Date()).getMonth() + 1;

                            for (var m = 0; m < 12; m++) {
                                var monthNum = m + 1;
                                var current = yoyData.filter(function(d) {
                                    return d.year === currentYear && d.month_num === monthNum;
                                });
                                var prev = yoyData.filter(function(d) {
                                    return d.year === prevYear && d.month_num === monthNum;
                                });

                                var cVal = 0,
                                    pVal = 0;
                                if (current.length > 0) {
                                    if (metric === 'gross') cVal = current[0].gross;
                                    else if (metric === 'txns') cVal = current[0].txns;
                                    else cVal = current[0].net;
                                }
                                if (prev.length > 0) {
                                    if (metric === 'gross') pVal = prev[0].gross;
                                    else if (metric === 'txns') pVal = prev[0].txns;
                                    else pVal = prev[0].net;
                                }

                                if (cVal > 0 || pVal > 0 || monthNum <= nowMonth) {
                                    labels.push(monthNames[m]);
                                    currentData.push(cVal);
                                    prevData.push(pVal);
                                }
                            }

                            return {
                                labels: labels,
                                currentData: currentData,
                                prevData: prevData
                            };
                        }

                        function renderYoyChart(metric) {
                            var subset = getYoySubset(metric);
                            if (yoyChartInstance) yoyChartInstance.destroy();

                            yoyChartInstance = new Chart(yoyCtx, {
                                type: 'bar',
                                data: {
                                    labels: subset.labels,
                                    datasets: [{
                                            label: String(prevYear),
                                            data: subset.prevData,
                                            backgroundColor: 'rgba(148, 163, 184, 0.7)',
                                            borderColor: '#94a3b8',
                                            borderWidth: 1,
                                            borderRadius: 3,
                                            borderSkipped: false,
                                        },
                                        {
                                            label: String(currentYear),
                                            data: subset.currentData,
                                            backgroundColor: 'rgba(0, 174, 239, 0.85)',
                                            borderColor: '#00AEEF',
                                            borderWidth: 2,
                                            borderRadius: 3,
                                            borderSkipped: false,
                                        }
                                    ]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    interaction: {
                                        mode: 'index',
                                        intersect: false
                                    },
                                    plugins: {
                                        legend: {
                                            display: false
                                        },
                                        tooltip: {
                                            backgroundColor: '#1e293b',
                                            titleColor: '#f1f5f9',
                                            bodyColor: '#e2e8f0',
                                            titleFont: {
                                                size: 13,
                                                weight: '600'
                                            },
                                            bodyFont: {
                                                size: 12
                                            },
                                            padding: 12,
                                            cornerRadius: 8,
                                            callbacks: {
                                                label: function(context) {
                                                    var label = context.dataset.label || '';
                                                    var val = context.parsed.y;
                                                    if (metric === 'txns') return label + ': ' + val;
                                                    return label + ': ' + formatMoney(val);
                                                },
                                                footer: function(items) {
                                                    if (items.length === 2) {
                                                        var prev = items[0].parsed.y;
                                                        var curr = items[1].parsed.y;
                                                        if (prev > 0) {
                                                            var pct = ((curr - prev) / prev * 100)
                                                                .toFixed(1);
                                                            return 'Change: ' + (pct >= 0 ? '+' : '') +
                                                                pct + '%';
                                                        } else if (curr > 0) {
                                                            return 'New revenue this year';
                                                        }
                                                    }
                                                    return '';
                                                }
                                            }
                                        }
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            grid: {
                                                color: 'rgba(0,0,0,0.06)',
                                                drawBorder: false
                                            },
                                            ticks: {
                                                callback: function(value) {
                                                    if (metric === 'txns') return value.toFixed(0);
                                                    if (value >= 1000) return '$' + (value / 1000)
                                                        .toFixed(0) + 'k';
                                                    return '$' + value.toFixed(0);
                                                },
                                                font: {
                                                    size: 11
                                                },
                                                color: '#94a3b8'
                                            }
                                        },
                                        x: {
                                            grid: {
                                                display: false
                                            },
                                            ticks: {
                                                font: {
                                                    size: 11
                                                },
                                                color: '#94a3b8'
                                            }
                                        }
                                    },
                                    animation: {
                                        duration: 500,
                                        easing: 'easeOutQuart'
                                    }
                                }
                            });
                        }

                        renderYoyChart(currentMetric);

                        document.querySelectorAll('.yoy-toggle .btn').forEach(function(btn) {
                            btn.addEventListener('click', function(e) {
                                e.preventDefault();
                                var parent = this.closest('.yoy-toggle');
                                parent.querySelectorAll('.btn').forEach(function(b) {
                                    b.classList.remove('active');
                                });
                                this.classList.add('active');
                                currentMetric = this.dataset.metric;
                                renderYoyChart(currentMetric);
                            });
                        });
                    })();
                @endif

            })();
        @endif

        // Print / PDF
        function printUserDetails() {
            window.print();
        }

        // Copy to Clipboard
        document.querySelectorAll('.copy-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                try {
                    const text = this.dataset.clipboardText;
                    if (text) {
                        await navigator.clipboard.writeText(text);
                        toastr.success('Copied to clipboard!');
                    }
                } catch (error) {
                    toastr.error('Failed to copy!');
                }
            });
        });

        // Status Change
        function showStatusChangeAlert(id) {
            event.preventDefault();
            Swal.fire({
                title: 'Are you sure?',
                text: 'You want to change the user status?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, change it!',
                cancelButtonText: 'Cancel',
            }).then((result) => {
                if (result.isConfirmed) {
                    statusChange(id);
                }
            });
        }

        function statusChange(id) {
            NProgress.start();
            let url = "{{ route('admin.users.manage.status', ':id') }}";
            $.ajax({
                type: "POST",
                url: url.replace(':id', id),
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(resp) {
                    NProgress.done();
                    if (resp.success) {
                        toastr.success(resp.message);
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        toastr.error(resp.message);
                    }
                },
                error: function(error) {
                    NProgress.done();
                    toastr.error(error.responseJSON?.message || 'Something went wrong!');
                }
            });
        }

        // Delete User
        function showDeleteConfirm(id) {
            event.preventDefault();
            Swal.fire({
                title: 'Are you sure?',
                text: 'This user will be deleted permanently!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    deleteUser(id);
                }
            });
        }

        function deleteUser(id) {
            NProgress.start();
            let url = "{{ route('admin.users.manage.destroy', ':id') }}";
            let csrfToken = '{{ csrf_token() }}';
            $.ajax({
                type: "DELETE",
                url: url.replace(':id', id),
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                success: function(resp) {
                    NProgress.done();
                    if (resp.success) {
                        toastr.success(resp.message);
                        setTimeout(() => {
                            window.location.href = "{{ route('admin.users.manage.index') }}";
                        }, 1000);
                    } else {
                        toastr.error(resp.message);
                    }
                },
                error: function(error) {
                    NProgress.done();
                    toastr.error(error.responseJSON?.message || 'Failed to delete user!');
                }
            });
        }
    </script>
@endpush
