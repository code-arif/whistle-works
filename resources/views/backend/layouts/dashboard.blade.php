@extends('backend.app', ['title' => 'Dashboard'])

@section('content')
    <!--app-content open-->
    <div class="app-content main-content mt-0">
        <div class="side-app" style="margin-bottom: 80px">

            <!-- CONTAINER -->
            <div class="main-container container-fluid">

                <!-- PAGE-HEADER -->
                <div class="page-header d-flex flex-wrap align-items-center justify-content-between">
                    <div>
                        <h1 class="page-title fw-bold">Dashboard</h1>
                        <p class="text-muted mb-0" style="font-size: 13px;">
                            <i class="fe fe-clock me-1"></i>
                            {{ now()->format('l, F d, Y') }}
                            &mdash; Complete business overview at a glance
                        </p>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <span class="status-badge status-badge-online">
                            <span class="status-dot"></span>
                            <span class="status-text">System Online</span>
                        </span>
                        <span class="status-badge status-badge-time" id="liveClock">
                            <i class="fe fe-clock me-1"></i>
                            {{ now()->format('h:i A') }}
                        </span>
                    </div>
                </div>
                <!-- PAGE-HEADER END -->

                <!-- ============================================================ -->
                <!--  ROW 1: FINANCIAL KPI CARDS (Top)                              -->
                <!-- ============================================================ -->
                <div class="row">
                    {{-- Total Revenue --}}
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                        <div class="card financial-card">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <p class="mb-1 text-muted fs-13">Total Revenue</p>
                                        <h2 class="mb-0 fw-bold text-success">
                                            ${{ number_format($revenueStats['totalRevenue'], 2) }}</h2>
                                        <div class="mt-2 d-flex align-items-center gap-2">
                                            <span
                                                class="badge {{ $revenueStats['revenueGrowth'] >= 0 ? 'bg-success-transparent text-success' : 'bg-danger-transparent text-danger' }} d-inline-flex align-items-center gap-1 px-2 py-1 fs-11">
                                                <i
                                                    class="fe fe-{{ $revenueStats['revenueGrowth'] >= 0 ? 'trending-up' : 'trending-down' }}"></i>
                                                <span>{{ abs($revenueStats['revenueGrowth']) }}%</span>
                                            </span>
                                            <span class="text-muted fs-11">vs last month</span>
                                        </div>
                                    </div>
                                    <div class="icon-box bg-success-transparent">
                                        <i class="fe fe-dollar-sign text-success fs-22"></i>
                                    </div>
                                </div>
                                <div class="mt-3 d-flex justify-content-between text-center small">
                                    <div><span
                                            class="text-muted">Month</span><br><strong>${{ number_format($revenueStats['monthlyRevenue'], 2) }}</strong>
                                    </div>
                                    <div><span
                                            class="text-muted">Fees</span><br><strong>${{ number_format($revenueStats['totalAdminFees'], 2) }}</strong>
                                    </div>
                                    <div><span
                                            class="text-muted">Avg</span><br><strong>${{ number_format($revenueStats['avgTransactionValue'], 2) }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Profit (Admin Fees) --}}
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                        <div class="card financial-card">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <p class="mb-1 text-muted fs-13">Platform Profit (Admin Fees)</p>
                                        <h2 class="mb-0 fw-bold text-primary">
                                            ${{ number_format($revenueStats['totalAdminFees'], 2) }}</h2>
                                        <div class="mt-2 d-flex align-items-center gap-2">
                                            <span
                                                class="badge bg-primary-transparent text-primary d-inline-flex align-items-center gap-1 px-2 py-1 fs-11">
                                                <i class="fe fe-percent lh-1"></i>
                                                <span>
                                                    {{ $revenueStats['totalRevenue'] > 0 ? round(($revenueStats['totalAdminFees'] / $revenueStats['totalRevenue']) * 100, 1) : 0 }}%
                                                </span>
                                            </span>
                                            <span class="text-muted fs-11">of total revenue</span>
                                        </div>
                                    </div>
                                    <div class="icon-box bg-primary-transparent">
                                        <i class="fe fe-bar-chart-2 text-primary fs-22"></i>
                                    </div>
                                </div>
                                <div class="mt-3 d-flex justify-content-between text-center small">
                                    <div><span class="text-muted">This
                                            Month</span><br><strong>${{ number_format($revenueStats['monthlyAdminFees'], 2) }}</strong>
                                    </div>
                                    <div><span class="text-muted">Paid to
                                            Directors</span><br><strong>${{ number_format($revenueStats['totalDirectorAmount'], 2) }}</strong>
                                    </div>
                                    <div><span
                                            class="text-muted">Discounts</span><br><strong>${{ number_format($revenueStats['totalDiscountGiven'], 2) }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Transactions --}}
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                        <div class="card financial-card">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <p class="mb-1 text-muted fs-13">Transactions</p>
                                        <h2 class="mb-0 fw-bold text-warning">
                                            {{ number_format($revenueStats['totalTransactions']) }}</h2>
                                        <div class="mt-2 d-flex align-items-center gap-2">
                                            <span
                                                class="badge bg-warning-transparent text-warning d-inline-flex align-items-center gap-1 px-2 py-1 fs-11">
                                                <i class="fe fe-check-circle lh-1"></i>
                                                <span>{{ $paymentSuccessRate['rate'] }}% Success</span>
                                            </span>
                                            <span class="text-muted fs-11">{{ $paymentStats['totalAttempts'] }} total
                                                attempts</span>
                                        </div>
                                    </div>
                                    <div class="icon-box bg-warning-transparent">
                                        <i class="fe fe-credit-card text-warning fs-22"></i>
                                    </div>
                                </div>
                                <div class="mt-3 d-flex justify-content-between text-center small">
                                    <div><span class="text-muted">This
                                            Month</span><br><strong>{{ number_format($revenueStats['monthlyTransactions']) }}</strong>
                                    </div>
                                    <div><span class="text-muted">Failed</span><br><strong
                                            class="text-danger">{{ number_format($paymentStats['failedAttempts']) }}</strong>
                                    </div>
                                    <div><span class="text-muted">Pending</span><br><strong
                                            class="text-warning">{{ number_format($paymentStats['pendingAttempts']) }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Coupons / Discounts --}}
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                        <div class="card financial-card">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <p class="mb-1 text-muted fs-13">Coupons &amp; Discounts</p>
                                        <h2 class="mb-0 fw-bold text-info">
                                            ${{ number_format($couponStats['totalDiscountGiven'], 2) }}</h2>
                                        <div class="mt-2 d-flex align-items-center gap-2">
                                            <span
                                                class="badge bg-info-transparent text-info d-inline-flex align-items-center gap-1 px-2 py-1 fs-11">
                                                <i class="fe fe-tag lh-1"></i>
                                                <span>{{ number_format($couponStats['totalUsed']) }} uses</span>
                                            </span>
                                            <span class="text-muted fs-11">{{ $couponStats['active'] }} active
                                                coupons</span>
                                        </div>
                                    </div>
                                    <div class="icon-box bg-info-transparent">
                                        <i class="fe fe-tag text-info fs-22"></i>
                                    </div>
                                </div>
                                <div class="mt-3 d-flex justify-content-between text-center small">
                                    <div><span class="text-muted">Total
                                            Coupons</span><br><strong>{{ number_format($couponStats['total']) }}</strong>
                                    </div>
                                    <div><span class="text-muted">Active</span><br><strong
                                            class="text-success">{{ number_format($couponStats['active']) }}</strong></div>
                                    <div><span class="text-muted">Orders w/
                                            Coupon</span><br><strong>{{ number_format($couponStats['couponsUsed']) }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- END ROW 1 -->

                <!-- ============================================================ -->
                <!--  ROW 2: OPERATIONAL KPIs                                     -->
                <!-- ============================================================ -->
                <div class="row">
                    {{-- Users --}}
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                        <div class="card operational-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="icon-box me-3 bg-primary-transparent">
                                        <i class="fe fe-users text-primary fs-20"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h3 class="mb-0 fw-bold">{{ number_format($userStats['total']) }}</h3>
                                        <p class="mb-0 text-muted fs-13">Total Users</p>
                                    </div>
                                    <div class="text-end">
                                        <span
                                            class="badge {{ $userStats['growth'] >= 0 ? 'bg-success-transparent text-success' : 'bg-danger-transparent text-danger' }} d-inline-flex align-items-center gap-1 px-2 py-1 fw-normal fs-11">
                                            <i
                                                class="fe fe-{{ $userStats['growth'] >= 0 ? 'trending-up' : 'trending-down' }} lh-1"></i>
                                            <span>{{ abs($userStats['growth']) }}%</span>
                                        </span>
                                    </div>
                                </div>
                                <div class="mt-3 d-flex justify-content-between small text-center">
                                    <div><span
                                            class="text-muted fs-11">Directors</span><br><strong>{{ number_format($userStats['directors']) }}</strong>
                                    </div>
                                    <div><span
                                            class="text-muted fs-11">Referees</span><br><strong>{{ number_format($userStats['referees']) }}</strong>
                                    </div>
                                    <div><span
                                            class="text-muted fs-11">Evaluators</span><br><strong>{{ number_format($userStats['evaluators']) }}</strong>
                                    </div>
                                    <div><span class="text-muted fs-11">Active</span><br><strong
                                            class="text-success">{{ number_format($userStats['active']) }}</strong></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Camps --}}
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                        <div class="card operational-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="icon-box me-3 bg-secondary-transparent">
                                        <i class="fe fe-flag text-secondary fs-20"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h3 class="mb-0 fw-bold">{{ number_format($campStats['total']) }}</h3>
                                        <p class="mb-0 text-muted fs-13">Total Camps</p>
                                    </div>
                                    <div class="text-end">
                                        <span
                                            class="badge {{ $campStats['growth'] >= 0 ? 'bg-success-transparent text-success' : 'bg-danger-transparent text-danger' }} d-inline-flex align-items-center gap-1 px-2 py-1 fw-normal fs-11">
                                            <i
                                                class="fe fe-{{ $campStats['growth'] >= 0 ? 'trending-up' : 'trending-down' }} lh-1"></i>
                                            <span>{{ abs($campStats['growth']) }}%</span>
                                        </span>
                                    </div>
                                </div>
                                <div class="mt-3 d-flex justify-content-between small text-center">
                                    <div><span class="text-muted fs-11">Active</span><br><strong
                                            class="text-success">{{ number_format($campStats['active']) }}</strong></div>
                                    <div><span class="text-muted fs-11">Upcoming</span><br><strong
                                            class="text-primary">{{ number_format($campStats['upcoming']) }}</strong>
                                    </div>
                                    <div><span class="text-muted fs-11">Ongoing</span><br><strong
                                            class="text-warning">{{ number_format($campStats['ongoing']) }}</strong></div>
                                    <div><span class="text-muted fs-11">Completed</span><br><strong
                                            class="text-secondary">{{ number_format($campStats['completed']) }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Game Slots --}}
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                        <div class="card operational-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="icon-box me-3 bg-info-transparent">
                                        <i class="fe fe-grid text-info fs-20"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h3 class="mb-0 fw-bold">{{ number_format($gameSlotStats['total']) }}</h3>
                                        <p class="mb-0 text-muted fs-13">Game Slots</p>
                                    </div>
                                    <div class="text-end">
                                        <span
                                            class="badge bg-info-transparent text-info d-inline-flex align-items-center gap-1 px-2 py-1 fw-normal fs-11">
                                            <i class="fe fe-percent lh-1"></i>
                                            <span>{{ $gameSlotStats['utilizationRate'] }}% utilized</span>
                                        </span>
                                    </div>
                                </div>
                                <div class="mt-3 d-flex justify-content-between small text-center">
                                    <div><span class="text-muted fs-11">Available</span><br><strong
                                            class="text-success">{{ number_format($gameSlotStats['available']) }}</strong>
                                    </div>
                                    <div><span class="text-muted fs-11">Assigned</span><br><strong
                                            class="text-primary">{{ number_format($gameSlotStats['assigned']) }}</strong>
                                    </div>
                                    <div><span
                                            class="text-muted fs-11">Completed</span><br><strong>{{ number_format($gameSlotStats['completed']) }}</strong>
                                    </div>
                                    <div><span class="text-muted fs-11">Blocked</span><br><strong
                                            class="text-danger">{{ number_format($gameSlotStats['blocked']) }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Referees & Schedules --}}
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                        <div class="card operational-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="icon-box me-3 bg-success-transparent">
                                        <i class="fe fe-user-check text-success fs-20"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h3 class="mb-0 fw-bold">{{ number_format($refereeStats['totalRegistrations']) }}
                                        </h3>
                                        <p class="mb-0 text-muted fs-13">Referee Registrations</p>
                                    </div>
                                    <div class="text-end">
                                        <span
                                            class="badge bg-success-transparent text-success d-inline-flex align-items-center gap-1 px-2 py-1 fw-normal fs-11">
                                            <i class="fe fe-check lh-1"></i>
                                            <span>{{ $refereeStats['checkedIn'] }} checked in</span>
                                        </span>
                                    </div>
                                </div>
                                <div class="mt-3 d-flex justify-content-between small text-center">
                                    <div><span
                                            class="text-muted fs-11">Schedules</span><br><strong>{{ number_format($scheduleStats['total']) }}</strong>
                                    </div>
                                    <div><span class="text-muted fs-11">Published</span><br><strong
                                            class="text-success">{{ number_format($scheduleStats['published']) }}</strong>
                                    </div>
                                    <div><span class="text-muted fs-11">Draft</span><br><strong
                                            class="text-warning">{{ number_format($scheduleStats['draft']) }}</strong>
                                    </div>
                                    <div><span
                                            class="text-muted fs-11">Crews</span><br><strong>{{ number_format($crewStats['total']) }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- END ROW 2 -->

                <!-- ============================================================ -->
                <!--  ROW 3: CHARTS - Revenue & Camps                             -->
                <!-- ============================================================ -->
                <div class="row mb-4">
                    {{-- Revenue Trend Chart --}}
                    <div class="col-xl-8 col-lg-12 col-md-12">
                        <div class="card chart-card h-100 w-100">
                            <div class="card-header border-bottom d-flex align-items-center">
                                <h4 class="card-title fw-semibold mb-0">
                                    <i class="fe fe-trending-up me-2 text-success"></i>Revenue Trend
                                </h4>
                                <div class="ms-auto d-flex gap-3">
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="d-inline-block rounded-circle"
                                            style="width:10px;height:10px;background:#0d6efd;"></span>
                                        <span class="text-muted fs-11">Revenue</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="d-inline-block rounded-circle"
                                            style="width:10px;height:10px;background:#20c997;"></span>
                                        <span class="text-muted fs-11">Admin Fee</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="d-inline-block rounded-circle"
                                            style="width:10px;height:10px;background:#dc3545;"></span>
                                        <span class="text-muted fs-11">Discounts</span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="chart-container" style="position:relative;height:290px;">
                                    <canvas id="revenueChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Camp Status & Sports Distribution --}}
                    <div class="col-xl-4 col-lg-12 col-md-12">
                        <div class="card chart-card h-100 w-100">
                            <div class="card-header border-bottom">
                                <h4 class="card-title fw-semibold mb-0">
                                    <i class="fe fe-pie-chart me-2 text-primary"></i>Camp Status
                                </h4>
                            </div>
                            <div class="card-body">
                                <div class="chart-container" style="position:relative;height:200px;margin-bottom:10px;">
                                    <canvas id="campStatusChart"></canvas>
                                </div>
                                <div class="d-flex justify-content-center gap-3 flex-wrap small">
                                    <div><span class="d-inline-block rounded-circle me-1"
                                            style="width:10px;height:10px;background:#0d6efd;"></span> Upcoming
                                        <strong>{{ number_format($campStats['upcoming']) }}</strong>
                                    </div>
                                    <div><span class="d-inline-block rounded-circle me-1"
                                            style="width:10px;height:10px;background:#ffc107;"></span> Ongoing
                                        <strong>{{ number_format($campStats['ongoing']) }}</strong>
                                    </div>
                                    <div><span class="d-inline-block rounded-circle me-1"
                                            style="width:10px;height:10px;background:#6c757d;"></span> Completed
                                        <strong>{{ number_format($campStats['completed']) }}</strong>
                                    </div>
                                </div>
                                <hr>
                                <h5 class="fw-semibold fs-14 mb-3">
                                    <i class="fe fe-baseball me-2 text-info"></i>Sports Distribution
                                </h5>
                                @forelse($sportsStats['campsBySport'] as $sport)
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <small class="text-muted">{{ $sport->sports_type_name }}</small>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress" style="width:100px;height:6px;">
                                                @php
                                                    $maxCount = max($sportsStats['campsBySport']->max('total') ?? 0, 1);
                                                    $pct = ($sport->total / $maxCount) * 100;
                                                @endphp
                                                <div class="progress-bar bg-info" role="progressbar"
                                                    style="width:{{ $pct }}%"></div>
                                            </div>
                                            <strong class="fs-12">{{ $sport->total }}</strong>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-muted text-center fs-13">No sports data</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
                <!-- END ROW 3 -->

                <!-- ============================================================ -->
                <!--  ROW 4: USER GROWTH & CAMPS MONTHLY                          -->
                <!-- ============================================================ -->
                <div class="row">
                    {{-- Monthly Camps Created --}}
                    <div class="col-xl-6 col-lg-12 col-md-12">
                        <div class="card chart-card">
                            <div class="card-header border-bottom">
                                <h4 class="card-title fw-semibold mb-0">
                                    <i class="fe fe-bar-chart me-2 text-secondary"></i>Monthly Camps Created
                                    <span class="text-muted fs-12 fw-normal ms-2">{{ date('Y') }}</span>
                                </h4>
                            </div>
                            <div class="card-body">
                                <div class="chart-container" style="position:relative;height:240px;">
                                    <canvas id="monthlyCampsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- User Growth Trend --}}
                    <div class="col-xl-6 col-lg-12 col-md-12">
                        <div class="card chart-card">
                            <div class="card-header border-bottom">
                                <h4 class="card-title fw-semibold mb-0">
                                    <i class="fe fe-users me-2 text-primary"></i>User Growth Trend
                                </h4>
                            </div>
                            <div class="card-body">
                                <div class="chart-container" style="position:relative;height:240px;">
                                    <canvas id="userGrowthChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- END ROW 4 -->

                <!-- ============================================================ -->
                <!--  ROW 5: TOP CAMPS & RECENT PAYMENTS                          -->
                <!-- ============================================================ -->
                <div class="row mb-4">
                    {{-- Top Camps by Revenue --}}
                    <div class="col-xl-6 col-lg-12 col-md-12">
                        <div class="card h-100 w-100">
                            <div class="card-header border-bottom d-flex align-items-center">
                                <h4 class="card-title fw-semibold mb-0">
                                    <i class="fe fe-award me-2 text-warning"></i>Top Camps by Revenue
                                </h4>
                                <a href="{{ route('admin.camps.index') }}"
                                    class="ms-auto d-inline-flex align-items-center text-primary fs-12 text-decoration-none">
                                    <span>View All Camps</span>
                                    <i class="fe fe-arrow-right ms-1 lh-1"></i>
                                </a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="border-bottom-0 fs-12">#</th>
                                                <th class="border-bottom-0 fs-12">Camp</th>
                                                <th class="border-bottom-0 fs-12">Sport</th>
                                                <th class="border-bottom-0 fs-12 text-end">Revenue</th>
                                                <th class="border-bottom-0 fs-12 text-end">Fees</th>
                                                <th class="border-bottom-0 fs-12 text-end">Payments</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($topCamps as $i => $camp)
                                                <tr>
                                                    <td class="fs-12">{{ $i + 1 }}</td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="me-2">
                                                                <div class="avatar-xs rounded bg-{{ ['primary', 'success', 'warning', 'info', 'secondary'][$i % 5] }}-transparent d-flex align-items-center justify-content-center fw-bold text-{{ ['primary', 'success', 'warning', 'info', 'secondary'][$i % 5] }}"
                                                                    style="width:32px;height:32px;">
                                                                    {{ strtoupper(substr($camp['camp_name'] ?? 'C', 0, 1)) }}
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <div class="fw-semibold fs-13 text-truncate"
                                                                    style="max-width:140px;">
                                                                    {{ $camp['camp_name'] ?? 'N/A' }}</div>
                                                                <small
                                                                    class="text-muted">{{ Str::limit($camp['location'] ?? '', 18) }}</small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><span
                                                            class="badge bg-info-transparent text-info fs-11">{{ $camp['sports_type_name'] ?? 'N/A' }}</span>
                                                    </td>
                                                    <td class="text-end fw-semibold text-success">
                                                        ${{ number_format($camp['total_revenue'] ?? 0, 2) }}</td>
                                                    <td class="text-end text-primary">
                                                        ${{ number_format($camp['total_fees'] ?? 0, 2) }}</td>
                                                    <td class="text-end">{{ $camp['payment_count'] ?? 0 }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted py-4">
                                                        <i class="fe fe-inbox fs-20 d-block mb-2"></i>
                                                        No payment data yet
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Recent Payments --}}
                    <div class="col-xl-6 col-lg-12 col-md-12">
                        <div class="card h-100 w-100">
                            <div class="card-header border-bottom d-flex align-items-center">
                                <h4 class="card-title fw-semibold mb-0">
                                    <i class="fe fe-clock me-2 text-success"></i>Recent Payments
                                </h4>
                                <a href="{{ route('admin.monitor.index') }}"
                                    class="ms-auto d-inline-flex align-items-center text-primary fs-12 text-decoration-none">
                                    <span>Payment Monitor</span>
                                    <i class="fe fe-arrow-right ms-1 lh-1"></i>
                                </a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="border-bottom-0 fs-12">Camp</th>
                                                <th class="border-bottom-0 fs-12">Referee</th>
                                                <th class="border-bottom-0 fs-12 text-end">Amount</th>
                                                <th class="border-bottom-0 fs-12 text-end">Fee</th>
                                                <th class="border-bottom-0 fs-12 text-end">Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($recentPayments as $payment)
                                                <tr>
                                                    <td class="fs-13">
                                                        {{ Str::limit($payment['camp']['camp_name'] ?? 'N/A', 20) }}</td>
                                                    <td class="fs-13">
                                                        @if ($payment['referee'])
                                                            {{ $payment['referee']['first_name'] ?? '' }}
                                                            {{ $payment['referee']['last_name'] ?? '' }}
                                                        @else
                                                            <span class="text-muted">N/A</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-end fw-semibold">
                                                        ${{ number_format($payment['amount'] ?? 0, 2) }}</td>
                                                    <td class="text-end text-primary">
                                                        ${{ number_format($payment['admin_fee'] ?? 0, 2) }}</td>
                                                    <td class="text-end text-muted fs-12">
                                                        {{ isset($payment['paid_at']) ? \Carbon\Carbon::parse($payment['paid_at'])->format('M d, H:i') : '-' }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-4">
                                                        <i class="fe fe-credit-card fs-20 d-block mb-2"></i>
                                                        No payments yet
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- END ROW 5 -->

                <!-- ============================================================ -->
                <!--  ROW 6: GAME SLOT STATUS & RECENT ACTIVITY                   -->
                <!-- ============================================================ -->
                <div class="row mb-4">
                    {{-- Game Slot Status Breakdown --}}
                    {{-- <div class="col-xl-4 col-lg-6 col-md-12">
                        <div class="card">
                            <div class="card-header border-bottom">
                                <h4 class="card-title fw-semibold mb-0">
                                    <i class="fe fe-layers me-2 text-info"></i>Game Slot Status
                                </h4>
                            </div>
                            <div class="card-body">
                                <div class="chart-container" style="position:relative;height:230px;">
                                    <canvas id="gameSlotChart"></canvas>
                                </div>
                                <div class="mt-3 d-flex justify-content-around small text-center">
                                    <div>
                                        <span class="d-inline-block rounded-circle me-1"
                                            style="width:10px;height:10px;background:#198754;"></span>
                                        Available <br><strong>{{ number_format($gameSlotStats['available']) }}</strong>
                                    </div>
                                    <div>
                                        <span class="d-inline-block rounded-circle me-1"
                                            style="width:10px;height:10px;background:#0d6efd;"></span>
                                        Assigned <br><strong>{{ number_format($gameSlotStats['assigned']) }}</strong>
                                    </div>
                                    <div>
                                        <span class="d-inline-block rounded-circle me-1"
                                            style="width:10px;height:10px;background:#6c757d;"></span>
                                        Completed <br><strong>{{ number_format($gameSlotStats['completed']) }}</strong>
                                    </div>
                                    <div>
                                        <span class="d-inline-block rounded-circle me-1"
                                            style="width:10px;height:10px;background:#dc3545;"></span>
                                        Blocked <br><strong>{{ number_format($gameSlotStats['blocked']) }}</strong>
                                    </div>
                                </div>
                                <hr class="my-3">
                                <div class="d-flex justify-content-between small">
                                    <span class="text-muted">
                                        <i class="fe fe-activity me-1"></i>Assignments
                                    </span>
                                    <span>
                                        <strong>{{ number_format($gameSlotStats['totalAssignments']) }}</strong>
                                        <span class="text-muted ms-2">
                                            ({{ $gameSlotStats['autoAssignments'] }} auto /
                                            {{ $gameSlotStats['manualAssignments'] }} manual)
                                        </span>
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between small mt-1">
                                    <span class="text-muted">
                                        <i class="fe fe-calendar me-1"></i>Today's Slots
                                    </span>
                                    <span>
                                        <strong>{{ number_format($gameSlotStats['todaySlots']) }}</strong>
                                        <span class="text-success ms-2">({{ $gameSlotStats['todayAssigned'] }}
                                            assigned)</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div> --}}

                    {{-- Crews & Assignments Summary --}}
                    <div class="col-xl-3 col-lg-6 col-md-12">
                        <div class="card h-100 w-100">
                            <div class="card-header border-bottom">
                                <h4 class="card-title fw-semibold mb-0">
                                    <i class="fe fe-users me-2 text-secondary"></i>Crews &amp; Teams
                                </h4>
                            </div>
                            <div class="card-body">
                                <div class="d-flex gap-4 mb-3">
                                    <div class="text-center flex-fill p-3 rounded-1 bg-secondary-transparent">
                                        <h3 class="mb-0 fw-bold">{{ number_format($crewStats['total']) }}</h3>
                                        <small class="text-muted">Total Crews</small>
                                    </div>
                                    <div class="text-center flex-fill p-3 rounded-1 bg-success-transparent">
                                        <h3 class="mb-0 fw-bold">{{ number_format($crewStats['totalMembers']) }}</h3>
                                        <small class="text-muted">Total Members</small>
                                    </div>
                                    <div class="text-center flex-fill p-3 rounded-1 bg-info-transparent">
                                        <h3 class="mb-0 fw-bold">{{ $crewStats['avgMembers'] }}</h3>
                                        <small class="text-muted">Avg / Crew</small>
                                    </div>
                                </div>
                                <h6 class="fw-semibold fs-13 mb-2">Largest Crews</h6>
                                @forelse($crewStats['largestCrews'] as $crew)
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="d-inline-block rounded-circle bg-primary-transparent p-2">
                                                <i class="fe fe-users text-primary fs-12"></i>
                                            </span>
                                            <span class="fs-13">{{ $crew->name }}</span>
                                        </div>
                                        <span class="badge bg-primary-transparent text-primary">{{ $crew->members_count }}
                                            members</span>
                                    </div>
                                @empty
                                    <p class="text-muted text-center fs-13">No crews created</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    {{-- Recent Activity Timeline --}}
                    <div class="col-xl-3 col-lg-6 col-md-12">
                        <div class="card h-100 w-100">
                            <div class="card-header border-bottom">
                                <h4 class="card-title fw-semibold mb-0">
                                    <i class="fe fe-activity me-2 text-warning"></i>Recent Activity
                                </h4>
                            </div>
                            <div class="card-body p-0">
                                <div class="activity-timeline p-3">
                                    @forelse($dailyActivities as $activity)
                                        <div class="d-flex align-items-start mb-3 activity-item">
                                            <div class="me-3 position-relative">
                                                <div class="activity-dot bg-{{ $activity['color'] }}"></div>
                                                @if (!$loop->last)
                                                    <div class="activity-line"></div>
                                                @endif
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between">
                                                    <h6 class="mb-0 fs-13 fw-semibold">{{ $activity['title'] }}</h6>
                                                    <small class="text-muted">{{ $activity['time'] }}</small>
                                                </div>
                                                <p class="mb-0 text-muted fs-12">{{ $activity['description'] }}</p>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="text-center text-muted py-4">
                                            <i class="fe fe-inbox fs-24 d-block mb-2"></i>
                                            No recent activity
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Schedules Overview --}}
                    <div class="col-xl-3 col-lg-12 col-md-12">
                        <div class="card h-100 w-100">
                            <div class="card-header border-bottom">
                                <h4 class="card-title fw-semibold mb-0">
                                    <i class="fe fe-calendar me-2 text-primary"></i>Schedules Overview
                                </h4>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-4 text-center">
                                        <div class="p-3 rounded-1 bg-primary-transparent">
                                            <h3 class="mb-0 fw-bold text-primary">
                                                {{ number_format($scheduleStats['total']) }}</h3>
                                            <small class="text-muted">Total</small>
                                        </div>
                                    </div>
                                    <div class="col-4 text-center">
                                        <div class="p-3 rounded-1 bg-success-transparent">
                                            <h3 class="mb-0 fw-bold text-success">
                                                {{ number_format($scheduleStats['published']) }}</h3>
                                            <small class="text-muted">Published</small>
                                        </div>
                                    </div>
                                    <div class="col-4 text-center">
                                        <div class="p-3 rounded-1 bg-warning-transparent">
                                            <h3 class="mb-0 fw-bold text-warning">
                                                {{ number_format($scheduleStats['draft']) }}</h3>
                                            <small class="text-muted">Draft</small>
                                        </div>
                                    </div>
                                </div>
                                @if ($scheduleStats['avgGameDuration'])
                                    <div class="mt-3 d-flex align-items-center gap-2">
                                        <i class="fe fe-clock text-muted"></i>
                                        <span class="text-muted fs-13">Average game duration:</span>
                                        <strong>{{ round($scheduleStats['avgGameDuration']) }} minutes</strong>
                                    </div>
                                @endif
                                <div class="mt-3">
                                    <div class="progress" style="height:12px;">
                                        @php $pubPct = $scheduleStats['total'] > 0 ? ($scheduleStats['published'] / $scheduleStats['total']) * 100 : 0; @endphp
                                        <div class="progress-bar bg-success" role="progressbar"
                                            style="width:{{ $pubPct }}%">
                                            {{ $pubPct > 0 ? round($pubPct) . '%' : '' }}</div>
                                    </div>
                                    <small class="text-muted">{{ round($pubPct) }}% of schedules published</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Stats Summary Grid --}}
                    <div class="col-xl-3 col-lg-12 col-md-12">
                        <div class="card h-100 w-100">
                            <div class="card-header border-bottom">
                                <h4 class="card-title fw-semibold mb-0">
                                    <i class="fe fe-info me-2 text-info"></i>Platform Summary
                                </h4>
                            </div>
                            <div class="card-body">
                                <div class="row g-2">
                                    <div class="col-6 col-md-4">
                                        <div class="p-2 summary-item">
                                            <small class="text-muted d-block">Sports Types</small>
                                            <strong>{{ number_format($sportsStats['total']) }}</strong>
                                            <small class="text-success d-block">({{ $sportsStats['active'] }}
                                                active)</small>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="p-2 summary-item">
                                            <small class="text-muted d-block">Total Courts</small>
                                            <strong>{{ number_format($campStats['totalCourts']) }}</strong>
                                            <small class="text-muted d-block">across all camps</small>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="p-2 summary-item">
                                            <small class="text-muted d-block">Avg Transaction</small>
                                            <strong>${{ number_format($revenueStats['avgTransactionValue'], 2) }}</strong>
                                            <small class="text-muted d-block">per payment</small>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="p-2 summary-item">
                                            <small class="text-muted d-block">Payment Attempts</small>
                                            <strong>{{ number_format($paymentStats['totalAttempts']) }}</strong>
                                            <small class="text-success d-block">{{ $paymentSuccessRate['success'] }}
                                                succeeded</small>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="p-2 summary-item">
                                            <small class="text-muted d-block">Referees Checked In</small>
                                            <strong>{{ number_format($refereeStats['checkedIn']) }}</strong>
                                            <small class="text-muted d-block">of
                                                {{ number_format($refereeStats['totalRegistrations']) }}</small>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="p-2 summary-item">
                                            <small class="text-muted d-block">Deleted Users</small>
                                            <strong>{{ number_format($userStats['deleted']) }}</strong>
                                            <small class="text-muted d-block">in trash</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- END ROW 6 -->
            </div>
            <!-- CONTAINER CLOSED -->
        </div>
    </div>
    <!--app-content close-->
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        // Chart defaults
        Chart.defaults.font.family = "'Inter', 'Segoe UI', sans-serif";
        Chart.defaults.font.size = 11;

        // ============================================================
        //  1. Revenue Trend (Stacked Bar + Line)
        // ============================================================
        const revenueCtx = document.getElementById('revenueChart')?.getContext('2d');
        if (revenueCtx) {
            new Chart(revenueCtx, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($monthlyRevenue['labels']) !!},
                    datasets: [{
                            label: 'Revenue',
                            data: {!! json_encode($monthlyRevenue['revenue']) !!},
                            backgroundColor: 'rgba(13, 110, 253, 0.7)',
                            borderColor: '#0d6efd',
                            borderWidth: 1,
                            borderRadius: 3,
                            order: 2,
                        },
                        {
                            label: 'Admin Fee',
                            data: {!! json_encode($monthlyRevenue['fees']) !!},
                            backgroundColor: 'rgba(32, 201, 151, 0.6)',
                            borderColor: '#20c997',
                            borderWidth: 1,
                            borderRadius: 3,
                            order: 2,
                        },
                        {
                            label: 'Discounts',
                            data: {!! json_encode($monthlyRevenue['discounts']) !!},
                            backgroundColor: 'rgba(220, 53, 69, 0.3)',
                            borderColor: '#dc3545',
                            borderWidth: 1,
                            borderRadius: 3,
                            order: 2,
                        },
                        {
                            label: 'Transactions',
                            data: {!! json_encode($monthlyRevenue['transactions']) !!},
                            type: 'line',
                            borderColor: '#ffc107',
                            backgroundColor: 'rgba(255, 193, 7, 0.1)',
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#ffc107',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            yAxisID: 'y1',
                            order: 1,
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
                            display: false,
                        },
                        tooltip: {
                            callbacks: {
                                label: function(ctx) {
                                    if (ctx.dataset.label === 'Transactions') {
                                        return ctx.dataset.label + ': ' + ctx.raw;
                                    }
                                    return ctx.dataset.label + ': $' + Number(ctx.raw).toLocaleString('en-US', {
                                        minimumFractionDigits: 2
                                    });
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0,0,0,0.05)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        },
                        y1: {
                            beginAtZero: true,
                            position: 'right',
                            grid: {
                                display: false
                            },
                            ticks: {
                                stepSize: 1,
                                callback: function(value) {
                                    return value;
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        // ============================================================
        //  2. Camp Status (Doughnut)
        // ============================================================
        const campStatusCtx = document.getElementById('campStatusChart')?.getContext('2d');
        if (campStatusCtx) {
            new Chart(campStatusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Upcoming', 'Ongoing', 'Completed'],
                    datasets: [{
                        data: [
                            {{ $campStats['upcoming'] }},
                            {{ $campStats['ongoing'] }},
                            {{ $campStats['completed'] }}
                        ],
                        backgroundColor: ['#0d6efd', '#ffc107', '#6c757d'],
                        borderWidth: 2,
                        borderColor: '#fff',
                        hoverOffset: 8,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: {
                            display: false
                        },
                    }
                }
            });
        }

        // ============================================================
        //  3. Game Slot Status (Doughnut)
        // ============================================================
        const gameSlotCtx = document.getElementById('gameSlotChart')?.getContext('2d');
        if (gameSlotCtx) {
            new Chart(gameSlotCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Available', 'Assigned', 'Completed', 'Blocked'],
                    datasets: [{
                        data: [
                            {{ $gameSlotStats['available'] }},
                            {{ $gameSlotStats['assigned'] }},
                            {{ $gameSlotStats['completed'] }},
                            {{ $gameSlotStats['blocked'] }}
                        ],
                        backgroundColor: ['#198754', '#0d6efd', '#6c757d', '#dc3545'],
                        borderWidth: 2,
                        borderColor: '#fff',
                        hoverOffset: 8,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '60%',
                    plugins: {
                        legend: {
                            display: false
                        },
                    }
                }
            });
        }

        // ============================================================
        //  4. Monthly Camps (Bar)
        // ============================================================
        const monthlyCampsCtx = document.getElementById('monthlyCampsChart')?.getContext('2d');
        if (monthlyCampsCtx) {
            new Chart(monthlyCampsCtx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [{
                        label: 'Camps Created',
                        data: {!! json_encode($monthlyCamps) !!},
                        backgroundColor: 'rgba(108, 117, 125, 0.7)',
                        borderColor: '#6c757d',
                        borderWidth: 1,
                        borderRadius: 4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        // ============================================================
        //  5. User Growth (Line)
        // ============================================================
        const userGrowthCtx = document.getElementById('userGrowthChart')?.getContext('2d');
        if (userGrowthCtx) {
            new Chart(userGrowthCtx, {
                type: 'line',
                data: {
                    labels: {!! json_encode($userGrowth['labels']) !!},
                    datasets: [{
                        label: 'New Users',
                        data: {!! json_encode($userGrowth['data']) !!},
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13, 110, 253, 0.08)',
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#0d6efd',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        // ============================================================
        //  Live Clock
        // ============================================================
        function updateClock() {
            const now = new Date();
            const time = now.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });
            const el = document.getElementById('liveClock');
            if (el) el.innerHTML = '<i class="fe fe-clock me-1"></i>' + time;
        }
        updateClock(); // Call immediately
        setInterval(updateClock, 30000);
    </script>
@endpush

@push('styles')
    <style>
        /* =========================================================
                           DASHBOARD STYLES
                           ========================================================= */

        /* ---- Card Enhancements ---- */
        .card {
            border-radius: 8px;
            border: 1px solid rgba(0, 0, 0, 0.04);
            transition: all 0.25s ease;
        }

        .card:hover {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        }

        .financial-card .card-body {
            padding: 1.25rem 1rem;
        }

        .financial-card:hover {
            transform: translateY(-2px);
        }

        .operational-card .card-body {
            padding: 1rem 1rem;
        }

        .chart-card .card-body {
            padding: 1rem 1rem 1.25rem;
        }

        /* ---- Icon Box ---- */
        .icon-box {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        /* ---- Badge backgrounds (using theme colors) ---- */
        .bg-success-transparent {
            background-color: rgba(25, 135, 84, 0.1);
        }

        .bg-danger-transparent {
            background-color: rgba(220, 53, 69, 0.1);
        }

        .bg-primary-transparent {
            background-color: rgba(13, 110, 253, 0.1);
        }

        .bg-warning-transparent {
            background-color: rgba(255, 193, 7, 0.1);
        }

        .bg-info-transparent {
            background-color: rgba(13, 202, 240, 0.1);
        }

        .bg-secondary-transparent {
            background-color: rgba(108, 117, 125, 0.1);
        }

        .bg-purple-transparent {
            background-color: rgba(102, 16, 242, 0.1);
        }

        .text-success-transparent {
            color: #198754;
        }

        .text-danger-transparent {
            color: #dc3545;
        }

        /* ---- Activity Timeline ---- */
        .activity-item {
            position: relative;
            padding-left: 4px;
        }

        .activity-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 2px solid #fff;
            box-shadow: 0 0 0 2px currentColor;
            position: relative;
            z-index: 1;
        }

        .activity-dot.bg-primary {
            box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.3);
        }

        .activity-dot.bg-success {
            box-shadow: 0 0 0 2px rgba(25, 135, 84, 0.3);
        }

        .activity-dot.bg-info {
            box-shadow: 0 0 0 2px rgba(13, 202, 240, 0.3);
        }

        .activity-dot.bg-warning {
            box-shadow: 0 0 0 2px rgba(255, 193, 7, 0.3);
        }

        .activity-dot.bg-secondary {
            box-shadow: 0 0 0 2px rgba(108, 117, 125, 0.3);
        }

        .activity-dot.bg-danger {
            box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.3);
        }

        .activity-line {
            position: absolute;
            top: 16px;
            left: 5.5px;
            width: 2px;
            height: calc(100% + 4px);
            background: rgba(0, 0, 0, 0.06);
        }

        /* ---- Summary Items ---- */
        .summary-item {
            border-radius: 6px;
            background: #f8f9fa;
            transition: background 0.2s;
        }

        .summary-item:hover {
            background: #f0f1f3;
        }

        /* ---- Table Enhancements ---- */
        .table> :not(caption)>*>* {
            padding: 0.65rem 0.75rem;
            vertical-align: middle;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.03);
        }

        /* ---- Avatar mini ---- */
        .avatar-xs {
            border-radius: 8px;
            font-size: 14px;
        }

        /* ---- Chart Container ---- */
        .chart-container canvas {
            max-width: 100%;
            height: 100% !important;
            width: 100% !important;
        }

        /* ---- Progress bar inside ---- */
        .progress {
            background-color: rgba(0, 0, 0, 0.06);
            border-radius: 10px;
        }

        /* ---- Badge padding fix ---- */
        .badge.px-3 {
            font-weight: 500;
        }

        /* ---- Status Badges (System Online & Live Clock) ---- */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 16px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.3px;
            transition: all 0.3s ease;
            cursor: default;
            user-select: none;
            border: 1px solid transparent;
        }

        .status-badge-online {
            background: linear-gradient(135deg, rgba(25, 135, 84, 0.12), rgba(25, 135, 84, 0.05));
            color: #198754;
            border-color: rgba(25, 135, 84, 0.2);
            box-shadow: 0 2px 8px rgba(25, 135, 84, 0.1);
        }

        .status-badge-online:hover {
            background: linear-gradient(135deg, rgba(25, 135, 84, 0.18), rgba(25, 135, 84, 0.08));
            box-shadow: 0 4px 14px rgba(25, 135, 84, 0.18);
            transform: translateY(-1px);
        }

        .status-badge-time {
            background: linear-gradient(135deg, rgba(13, 202, 240, 0.1), rgba(13, 202, 240, 0.04));
            color: #0dcaf0;
            border-color: rgba(13, 202, 240, 0.2);
            box-shadow: 0 2px 8px rgba(13, 202, 240, 0.08);
        }

        .status-badge-time:hover {
            background: linear-gradient(135deg, rgba(13, 202, 240, 0.16), rgba(13, 202, 240, 0.06));
            box-shadow: 0 4px 14px rgba(13, 202, 240, 0.15);
            transform: translateY(-1px);
        }

        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #198754;
            display: inline-block;
            animation: status-pulse 2s ease-in-out infinite;
            box-shadow: 0 0 0 2px rgba(25, 135, 84, 0.15);
        }

        @keyframes status-pulse {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
                box-shadow: 0 0 0 2px rgba(25, 135, 84, 0.15);
            }

            50% {
                opacity: 0.8;
                transform: scale(1.15);
                box-shadow: 0 0 0 4px rgba(25, 135, 84, 0.08);
            }
        }

        .status-text {
            position: relative;
        }

        .status-text::after {
            content: '';
            position: absolute;
            right: -4px;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: currentColor;
            opacity: 0.3;
        }
    </style>
@endpush
