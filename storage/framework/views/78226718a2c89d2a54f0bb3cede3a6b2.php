<?php $__env->startSection('content'); ?>
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
                            <?php echo e(now()->format('l, F d, Y')); ?>

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
                            <?php echo e(now()->format('h:i A')); ?>

                        </span>
                    </div>
                </div>
                <!-- PAGE-HEADER END -->

                <!-- ============================================================ -->
                <!--  ROW 1: FINANCIAL KPI CARDS (Top)                              -->
                <!-- ============================================================ -->
                <div class="row">
                    
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                        <div class="card financial-card">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <p class="mb-1 text-muted fs-13">Total Revenue</p>
                                        <h2 class="mb-0 fw-bold text-success">
                                            $<?php echo e(number_format($revenueStats['totalRevenue'], 2)); ?></h2>
                                        <div class="mt-2 d-flex align-items-center gap-2">
                                            <span
                                                class="badge <?php echo e($revenueStats['revenueGrowth'] >= 0 ? 'bg-success-transparent text-success' : 'bg-danger-transparent text-danger'); ?> d-inline-flex align-items-center gap-1 px-2 py-1 fs-11">
                                                <i
                                                    class="fe fe-<?php echo e($revenueStats['revenueGrowth'] >= 0 ? 'trending-up' : 'trending-down'); ?>"></i>
                                                <span><?php echo e(abs($revenueStats['revenueGrowth'])); ?>%</span>
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
                                            class="text-muted">Month</span><br><strong>$<?php echo e(number_format($revenueStats['monthlyRevenue'], 2)); ?></strong>
                                    </div>
                                    <div><span
                                            class="text-muted">Fees</span><br><strong>$<?php echo e(number_format($revenueStats['totalAdminFees'], 2)); ?></strong>
                                    </div>
                                    <div><span
                                            class="text-muted">Avg</span><br><strong>$<?php echo e(number_format($revenueStats['avgTransactionValue'], 2)); ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                        <div class="card financial-card">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <p class="mb-1 text-muted fs-13">Platform Profit (Admin Fees)</p>
                                        <h2 class="mb-0 fw-bold text-primary">
                                            $<?php echo e(number_format($revenueStats['totalAdminFees'], 2)); ?></h2>
                                        <div class="mt-2 d-flex align-items-center gap-2">
                                            <span
                                                class="badge bg-primary-transparent text-primary d-inline-flex align-items-center gap-1 px-2 py-1 fs-11">
                                                <i class="fe fe-percent lh-1"></i>
                                                <span>
                                                    <?php echo e($revenueStats['totalRevenue'] > 0 ? round(($revenueStats['totalAdminFees'] / $revenueStats['totalRevenue']) * 100, 1) : 0); ?>%
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
                                            Month</span><br><strong>$<?php echo e(number_format($revenueStats['monthlyAdminFees'], 2)); ?></strong>
                                    </div>
                                    <div><span class="text-muted">Paid to
                                            Directors</span><br><strong>$<?php echo e(number_format($revenueStats['totalDirectorAmount'], 2)); ?></strong>
                                    </div>
                                    <div><span
                                            class="text-muted">Discounts</span><br><strong>$<?php echo e(number_format($revenueStats['totalDiscountGiven'], 2)); ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                        <div class="card financial-card">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <p class="mb-1 text-muted fs-13">Transactions</p>
                                        <h2 class="mb-0 fw-bold text-warning">
                                            <?php echo e(number_format($revenueStats['totalTransactions'])); ?></h2>
                                        <div class="mt-2 d-flex align-items-center gap-2">
                                            <span
                                                class="badge bg-warning-transparent text-warning d-inline-flex align-items-center gap-1 px-2 py-1 fs-11">
                                                <i class="fe fe-check-circle lh-1"></i>
                                                <span><?php echo e($paymentSuccessRate['rate']); ?>% Success</span>
                                            </span>
                                            <span class="text-muted fs-11"><?php echo e($paymentStats['totalAttempts']); ?> total
                                                attempts</span>
                                        </div>
                                    </div>
                                    <div class="icon-box bg-warning-transparent">
                                        <i class="fe fe-credit-card text-warning fs-22"></i>
                                    </div>
                                </div>
                                <div class="mt-3 d-flex justify-content-between text-center small">
                                    <div><span class="text-muted">This
                                            Month</span><br><strong><?php echo e(number_format($revenueStats['monthlyTransactions'])); ?></strong>
                                    </div>
                                    <div><span class="text-muted">Failed</span><br><strong
                                            class="text-danger"><?php echo e(number_format($paymentStats['failedAttempts'])); ?></strong>
                                    </div>
                                    <div><span class="text-muted">Pending</span><br><strong
                                            class="text-warning"><?php echo e(number_format($paymentStats['pendingAttempts'])); ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                        <div class="card financial-card">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <p class="mb-1 text-muted fs-13">Coupons &amp; Discounts</p>
                                        <h2 class="mb-0 fw-bold text-info">
                                            $<?php echo e(number_format($couponStats['totalDiscountGiven'], 2)); ?></h2>
                                        <div class="mt-2 d-flex align-items-center gap-2">
                                            <span
                                                class="badge bg-info-transparent text-info d-inline-flex align-items-center gap-1 px-2 py-1 fs-11">
                                                <i class="fe fe-tag lh-1"></i>
                                                <span><?php echo e(number_format($couponStats['totalUsed'])); ?> uses</span>
                                            </span>
                                            <span class="text-muted fs-11"><?php echo e($couponStats['active']); ?> active
                                                coupons</span>
                                        </div>
                                    </div>
                                    <div class="icon-box bg-info-transparent">
                                        <i class="fe fe-tag text-info fs-22"></i>
                                    </div>
                                </div>
                                <div class="mt-3 d-flex justify-content-between text-center small">
                                    <div><span class="text-muted">Total
                                            Coupons</span><br><strong><?php echo e(number_format($couponStats['total'])); ?></strong>
                                    </div>
                                    <div><span class="text-muted">Active</span><br><strong
                                            class="text-success"><?php echo e(number_format($couponStats['active'])); ?></strong></div>
                                    <div><span class="text-muted">Orders w/
                                            Coupon</span><br><strong><?php echo e(number_format($couponStats['couponsUsed'])); ?></strong>
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
                    
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                        <div class="card operational-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="icon-box me-3 bg-primary-transparent">
                                        <i class="fe fe-users text-primary fs-20"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h3 class="mb-0 fw-bold"><?php echo e(number_format($userStats['total'])); ?></h3>
                                        <p class="mb-0 text-muted fs-13">Total Users</p>
                                    </div>
                                    <div class="text-end">
                                        <span
                                            class="badge <?php echo e($userStats['growth'] >= 0 ? 'bg-success-transparent text-success' : 'bg-danger-transparent text-danger'); ?> d-inline-flex align-items-center gap-1 px-2 py-1 fw-normal fs-11">
                                            <i
                                                class="fe fe-<?php echo e($userStats['growth'] >= 0 ? 'trending-up' : 'trending-down'); ?> lh-1"></i>
                                            <span><?php echo e(abs($userStats['growth'])); ?>%</span>
                                        </span>
                                    </div>
                                </div>
                                <div class="mt-3 d-flex justify-content-between small text-center">
                                    <div><span
                                            class="text-muted fs-11">Directors</span><br><strong><?php echo e(number_format($userStats['directors'])); ?></strong>
                                    </div>
                                    <div><span
                                            class="text-muted fs-11">Referees</span><br><strong><?php echo e(number_format($userStats['referees'])); ?></strong>
                                    </div>
                                    <div><span
                                            class="text-muted fs-11">Evaluators</span><br><strong><?php echo e(number_format($userStats['evaluators'])); ?></strong>
                                    </div>
                                    <div><span class="text-muted fs-11">Active</span><br><strong
                                            class="text-success"><?php echo e(number_format($userStats['active'])); ?></strong></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                        <div class="card operational-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="icon-box me-3 bg-secondary-transparent">
                                        <i class="fe fe-flag text-secondary fs-20"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h3 class="mb-0 fw-bold"><?php echo e(number_format($campStats['total'])); ?></h3>
                                        <p class="mb-0 text-muted fs-13">Total Camps</p>
                                    </div>
                                    <div class="text-end">
                                        <span
                                            class="badge <?php echo e($campStats['growth'] >= 0 ? 'bg-success-transparent text-success' : 'bg-danger-transparent text-danger'); ?> d-inline-flex align-items-center gap-1 px-2 py-1 fw-normal fs-11">
                                            <i
                                                class="fe fe-<?php echo e($campStats['growth'] >= 0 ? 'trending-up' : 'trending-down'); ?> lh-1"></i>
                                            <span><?php echo e(abs($campStats['growth'])); ?>%</span>
                                        </span>
                                    </div>
                                </div>
                                <div class="mt-3 d-flex justify-content-between small text-center">
                                    <div><span class="text-muted fs-11">Active</span><br><strong
                                            class="text-success"><?php echo e(number_format($campStats['active'])); ?></strong></div>
                                    <div><span class="text-muted fs-11">Upcoming</span><br><strong
                                            class="text-primary"><?php echo e(number_format($campStats['upcoming'])); ?></strong>
                                    </div>
                                    <div><span class="text-muted fs-11">Ongoing</span><br><strong
                                            class="text-warning"><?php echo e(number_format($campStats['ongoing'])); ?></strong></div>
                                    <div><span class="text-muted fs-11">Completed</span><br><strong
                                            class="text-secondary"><?php echo e(number_format($campStats['completed'])); ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                        <div class="card operational-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="icon-box me-3 bg-info-transparent">
                                        <i class="fe fe-grid text-info fs-20"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h3 class="mb-0 fw-bold"><?php echo e(number_format($gameSlotStats['total'])); ?></h3>
                                        <p class="mb-0 text-muted fs-13">Game Slots</p>
                                    </div>
                                    <div class="text-end">
                                        <span
                                            class="badge bg-info-transparent text-info d-inline-flex align-items-center gap-1 px-2 py-1 fw-normal fs-11">
                                            <i class="fe fe-percent lh-1"></i>
                                            <span><?php echo e($gameSlotStats['utilizationRate']); ?>% utilized</span>
                                        </span>
                                    </div>
                                </div>
                                <div class="mt-3 d-flex justify-content-between small text-center">
                                    <div><span class="text-muted fs-11">Available</span><br><strong
                                            class="text-success"><?php echo e(number_format($gameSlotStats['available'])); ?></strong>
                                    </div>
                                    <div><span class="text-muted fs-11">Assigned</span><br><strong
                                            class="text-primary"><?php echo e(number_format($gameSlotStats['assigned'])); ?></strong>
                                    </div>
                                    <div><span
                                            class="text-muted fs-11">Completed</span><br><strong><?php echo e(number_format($gameSlotStats['completed'])); ?></strong>
                                    </div>
                                    <div><span class="text-muted fs-11">Blocked</span><br><strong
                                            class="text-danger"><?php echo e(number_format($gameSlotStats['blocked'])); ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                        <div class="card operational-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="icon-box me-3 bg-success-transparent">
                                        <i class="fe fe-user-check text-success fs-20"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h3 class="mb-0 fw-bold"><?php echo e(number_format($refereeStats['totalRegistrations'])); ?>

                                        </h3>
                                        <p class="mb-0 text-muted fs-13">Referee Registrations</p>
                                    </div>
                                    <div class="text-end">
                                        <span
                                            class="badge bg-success-transparent text-success d-inline-flex align-items-center gap-1 px-2 py-1 fw-normal fs-11">
                                            <i class="fe fe-check lh-1"></i>
                                            <span><?php echo e($refereeStats['checkedIn']); ?> checked in</span>
                                        </span>
                                    </div>
                                </div>
                                <div class="mt-3 d-flex justify-content-between small text-center">
                                    <div><span
                                            class="text-muted fs-11">Schedules</span><br><strong><?php echo e(number_format($scheduleStats['total'])); ?></strong>
                                    </div>
                                    <div><span class="text-muted fs-11">Published</span><br><strong
                                            class="text-success"><?php echo e(number_format($scheduleStats['published'])); ?></strong>
                                    </div>
                                    <div><span class="text-muted fs-11">Draft</span><br><strong
                                            class="text-warning"><?php echo e(number_format($scheduleStats['draft'])); ?></strong>
                                    </div>
                                    <div><span
                                            class="text-muted fs-11">Crews</span><br><strong><?php echo e(number_format($crewStats['total'])); ?></strong>
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
                                        <strong><?php echo e(number_format($campStats['upcoming'])); ?></strong>
                                    </div>
                                    <div><span class="d-inline-block rounded-circle me-1"
                                            style="width:10px;height:10px;background:#ffc107;"></span> Ongoing
                                        <strong><?php echo e(number_format($campStats['ongoing'])); ?></strong>
                                    </div>
                                    <div><span class="d-inline-block rounded-circle me-1"
                                            style="width:10px;height:10px;background:#6c757d;"></span> Completed
                                        <strong><?php echo e(number_format($campStats['completed'])); ?></strong>
                                    </div>
                                </div>
                                <hr>
                                <h5 class="fw-semibold fs-14 mb-3">
                                    <i class="fe fe-baseball me-2 text-info"></i>Sports Distribution
                                </h5>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $sportsStats['campsBySport']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sport): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <small class="text-muted"><?php echo e($sport->sports_type_name); ?></small>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress" style="width:100px;height:6px;">
                                                <?php
                                                    $maxCount = max($sportsStats['campsBySport']->max('total') ?? 0, 1);
                                                    $pct = ($sport->total / $maxCount) * 100;
                                                ?>
                                                <div class="progress-bar bg-info" role="progressbar"
                                                    style="width:<?php echo e($pct); ?>%"></div>
                                            </div>
                                            <strong class="fs-12"><?php echo e($sport->total); ?></strong>
                                        </div>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <p class="text-muted text-center fs-13">No sports data</p>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- END ROW 3 -->

                <!-- ============================================================ -->
                <!--  ROW 4: USER GROWTH & CAMPS MONTHLY                          -->
                <!-- ============================================================ -->
                <div class="row">
                    
                    <div class="col-xl-6 col-lg-12 col-md-12">
                        <div class="card chart-card">
                            <div class="card-header border-bottom">
                                <h4 class="card-title fw-semibold mb-0">
                                    <i class="fe fe-bar-chart me-2 text-secondary"></i>Monthly Camps Created
                                    <span class="text-muted fs-12 fw-normal ms-2"><?php echo e(date('Y')); ?></span>
                                </h4>
                            </div>
                            <div class="card-body">
                                <div class="chart-container" style="position:relative;height:240px;">
                                    <canvas id="monthlyCampsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    
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
                    
                    <div class="col-xl-6 col-lg-12 col-md-12">
                        <div class="card h-100 w-100">
                            <div class="card-header border-bottom d-flex align-items-center">
                                <h4 class="card-title fw-semibold mb-0">
                                    <i class="fe fe-award me-2 text-warning"></i>Top Camps by Revenue
                                </h4>
                                <a href="<?php echo e(route('admin.camps.index')); ?>"
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
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $topCamps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $camp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                                <tr>
                                                    <td class="fs-12"><?php echo e($i + 1); ?></td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="me-2">
                                                                <div class="avatar-xs rounded bg-<?php echo e(['primary', 'success', 'warning', 'info', 'secondary'][$i % 5]); ?>-transparent d-flex align-items-center justify-content-center fw-bold text-<?php echo e(['primary', 'success', 'warning', 'info', 'secondary'][$i % 5]); ?>"
                                                                    style="width:32px;height:32px;">
                                                                    <?php echo e(strtoupper(substr($camp['camp_name'] ?? 'C', 0, 1))); ?>

                                                                </div>
                                                            </div>
                                                            <div>
                                                                <div class="fw-semibold fs-13 text-truncate"
                                                                    style="max-width:140px;">
                                                                    <?php echo e($camp['camp_name'] ?? 'N/A'); ?></div>
                                                                <small
                                                                    class="text-muted"><?php echo e(Str::limit($camp['location'] ?? '', 18)); ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><span
                                                            class="badge bg-info-transparent text-info fs-11"><?php echo e($camp['sports_type_name'] ?? 'N/A'); ?></span>
                                                    </td>
                                                    <td class="text-end fw-semibold text-success">
                                                        $<?php echo e(number_format($camp['total_revenue'] ?? 0, 2)); ?></td>
                                                    <td class="text-end text-primary">
                                                        $<?php echo e(number_format($camp['total_fees'] ?? 0, 2)); ?></td>
                                                    <td class="text-end"><?php echo e($camp['payment_count'] ?? 0); ?></td>
                                                </tr>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted py-4">
                                                        <i class="fe fe-inbox fs-20 d-block mb-2"></i>
                                                        No payment data yet
                                                    </td>
                                                </tr>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    
                    <div class="col-xl-6 col-lg-12 col-md-12">
                        <div class="card h-100 w-100">
                            <div class="card-header border-bottom d-flex align-items-center">
                                <h4 class="card-title fw-semibold mb-0">
                                    <i class="fe fe-clock me-2 text-success"></i>Recent Payments
                                </h4>
                                <a href="<?php echo e(route('admin.monitor.index')); ?>"
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
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $recentPayments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                                <tr>
                                                    <td class="fs-13">
                                                        <?php echo e(Str::limit($payment['camp']['camp_name'] ?? 'N/A', 20)); ?></td>
                                                    <td class="fs-13">
                                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($payment['referee']): ?>
                                                            <?php echo e($payment['referee']['first_name'] ?? ''); ?>

                                                            <?php echo e($payment['referee']['last_name'] ?? ''); ?>

                                                        <?php else: ?>
                                                            <span class="text-muted">N/A</span>
                                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                    </td>
                                                    <td class="text-end fw-semibold">
                                                        $<?php echo e(number_format($payment['amount'] ?? 0, 2)); ?></td>
                                                    <td class="text-end text-primary">
                                                        $<?php echo e(number_format($payment['admin_fee'] ?? 0, 2)); ?></td>
                                                    <td class="text-end text-muted fs-12">
                                                        <?php echo e(isset($payment['paid_at']) ? \Carbon\Carbon::parse($payment['paid_at'])->format('M d, H:i') : '-'); ?>

                                                    </td>
                                                </tr>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-4">
                                                        <i class="fe fe-credit-card fs-20 d-block mb-2"></i>
                                                        No payments yet
                                                    </td>
                                                </tr>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
                                        <h3 class="mb-0 fw-bold"><?php echo e(number_format($crewStats['total'])); ?></h3>
                                        <small class="text-muted">Total Crews</small>
                                    </div>
                                    <div class="text-center flex-fill p-3 rounded-1 bg-success-transparent">
                                        <h3 class="mb-0 fw-bold"><?php echo e(number_format($crewStats['totalMembers'])); ?></h3>
                                        <small class="text-muted">Total Members</small>
                                    </div>
                                    <div class="text-center flex-fill p-3 rounded-1 bg-info-transparent">
                                        <h3 class="mb-0 fw-bold"><?php echo e($crewStats['avgMembers']); ?></h3>
                                        <small class="text-muted">Avg / Crew</small>
                                    </div>
                                </div>
                                <h6 class="fw-semibold fs-13 mb-2">Largest Crews</h6>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $crewStats['largestCrews']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $crew): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="d-inline-block rounded-circle bg-primary-transparent p-2">
                                                <i class="fe fe-users text-primary fs-12"></i>
                                            </span>
                                            <span class="fs-13"><?php echo e($crew->name); ?></span>
                                        </div>
                                        <span class="badge bg-primary-transparent text-primary"><?php echo e($crew->members_count); ?>

                                            members</span>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <p class="text-muted text-center fs-13">No crews created</p>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>
                    </div>

                    
                    <div class="col-xl-3 col-lg-6 col-md-12">
                        <div class="card h-100 w-100">
                            <div class="card-header border-bottom">
                                <h4 class="card-title fw-semibold mb-0">
                                    <i class="fe fe-activity me-2 text-warning"></i>Recent Activity
                                </h4>
                            </div>
                            <div class="card-body p-0">
                                <div class="activity-timeline p-3">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $dailyActivities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $activity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                        <div class="d-flex align-items-start mb-3 activity-item">
                                            <div class="me-3 position-relative">
                                                <div class="activity-dot bg-<?php echo e($activity['color']); ?>"></div>
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$loop->last): ?>
                                                    <div class="activity-line"></div>
                                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between">
                                                    <h6 class="mb-0 fs-13 fw-semibold"><?php echo e($activity['title']); ?></h6>
                                                    <small class="text-muted"><?php echo e($activity['time']); ?></small>
                                                </div>
                                                <p class="mb-0 text-muted fs-12"><?php echo e($activity['description']); ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                        <div class="text-center text-muted py-4">
                                            <i class="fe fe-inbox fs-24 d-block mb-2"></i>
                                            No recent activity
                                        </div>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    
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
                                                <?php echo e(number_format($scheduleStats['total'])); ?></h3>
                                            <small class="text-muted">Total</small>
                                        </div>
                                    </div>
                                    <div class="col-4 text-center">
                                        <div class="p-3 rounded-1 bg-success-transparent">
                                            <h3 class="mb-0 fw-bold text-success">
                                                <?php echo e(number_format($scheduleStats['published'])); ?></h3>
                                            <small class="text-muted">Published</small>
                                        </div>
                                    </div>
                                    <div class="col-4 text-center">
                                        <div class="p-3 rounded-1 bg-warning-transparent">
                                            <h3 class="mb-0 fw-bold text-warning">
                                                <?php echo e(number_format($scheduleStats['draft'])); ?></h3>
                                            <small class="text-muted">Draft</small>
                                        </div>
                                    </div>
                                </div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($scheduleStats['avgGameDuration']): ?>
                                    <div class="mt-3 d-flex align-items-center gap-2">
                                        <i class="fe fe-clock text-muted"></i>
                                        <span class="text-muted fs-13">Average game duration:</span>
                                        <strong><?php echo e(round($scheduleStats['avgGameDuration'])); ?> minutes</strong>
                                    </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <div class="mt-3">
                                    <div class="progress" style="height:12px;">
                                        <?php $pubPct = $scheduleStats['total'] > 0 ? ($scheduleStats['published'] / $scheduleStats['total']) * 100 : 0; ?>
                                        <div class="progress-bar bg-success" role="progressbar"
                                            style="width:<?php echo e($pubPct); ?>%">
                                            <?php echo e($pubPct > 0 ? round($pubPct) . '%' : ''); ?></div>
                                    </div>
                                    <small class="text-muted"><?php echo e(round($pubPct)); ?>% of schedules published</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    
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
                                            <strong><?php echo e(number_format($sportsStats['total'])); ?></strong>
                                            <small class="text-success d-block">(<?php echo e($sportsStats['active']); ?>

                                                active)</small>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="p-2 summary-item">
                                            <small class="text-muted d-block">Total Courts</small>
                                            <strong><?php echo e(number_format($campStats['totalCourts'])); ?></strong>
                                            <small class="text-muted d-block">across all camps</small>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="p-2 summary-item">
                                            <small class="text-muted d-block">Avg Transaction</small>
                                            <strong>$<?php echo e(number_format($revenueStats['avgTransactionValue'], 2)); ?></strong>
                                            <small class="text-muted d-block">per payment</small>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="p-2 summary-item">
                                            <small class="text-muted d-block">Payment Attempts</small>
                                            <strong><?php echo e(number_format($paymentStats['totalAttempts'])); ?></strong>
                                            <small class="text-success d-block"><?php echo e($paymentSuccessRate['success']); ?>

                                                succeeded</small>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="p-2 summary-item">
                                            <small class="text-muted d-block">Referees Checked In</small>
                                            <strong><?php echo e(number_format($refereeStats['checkedIn'])); ?></strong>
                                            <small class="text-muted d-block">of
                                                <?php echo e(number_format($refereeStats['totalRegistrations'])); ?></small>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="p-2 summary-item">
                                            <small class="text-muted d-block">Deleted Users</small>
                                            <strong><?php echo e(number_format($userStats['deleted'])); ?></strong>
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
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
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
                    labels: <?php echo json_encode($monthlyRevenue['labels']); ?>,
                    datasets: [{
                            label: 'Revenue',
                            data: <?php echo json_encode($monthlyRevenue['revenue']); ?>,
                            backgroundColor: 'rgba(13, 110, 253, 0.7)',
                            borderColor: '#0d6efd',
                            borderWidth: 1,
                            borderRadius: 3,
                            order: 2,
                        },
                        {
                            label: 'Admin Fee',
                            data: <?php echo json_encode($monthlyRevenue['fees']); ?>,
                            backgroundColor: 'rgba(32, 201, 151, 0.6)',
                            borderColor: '#20c997',
                            borderWidth: 1,
                            borderRadius: 3,
                            order: 2,
                        },
                        {
                            label: 'Discounts',
                            data: <?php echo json_encode($monthlyRevenue['discounts']); ?>,
                            backgroundColor: 'rgba(220, 53, 69, 0.3)',
                            borderColor: '#dc3545',
                            borderWidth: 1,
                            borderRadius: 3,
                            order: 2,
                        },
                        {
                            label: 'Transactions',
                            data: <?php echo json_encode($monthlyRevenue['transactions']); ?>,
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
                            <?php echo e($campStats['upcoming']); ?>,
                            <?php echo e($campStats['ongoing']); ?>,
                            <?php echo e($campStats['completed']); ?>

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
                            <?php echo e($gameSlotStats['available']); ?>,
                            <?php echo e($gameSlotStats['assigned']); ?>,
                            <?php echo e($gameSlotStats['completed']); ?>,
                            <?php echo e($gameSlotStats['blocked']); ?>

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
                        data: <?php echo json_encode($monthlyCamps); ?>,
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
                    labels: <?php echo json_encode($userGrowth['labels']); ?>,
                    datasets: [{
                        label: 'New Users',
                        data: <?php echo json_encode($userGrowth['data']); ?>,
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
<?php $__env->stopPush(); ?>

<?php $__env->startPush('styles'); ?>
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
<?php $__env->stopPush(); ?>

<?php echo $__env->make('backend.app', ['title' => 'Dashboard'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\remote-work\whistle-works-backend\resources\views/backend/layouts/dashboard.blade.php ENDPATH**/ ?>