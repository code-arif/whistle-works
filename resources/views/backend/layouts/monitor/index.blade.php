@extends('backend.app', ['title' => 'Payment Monitor'])

@section('content')
    <div class="app-content main-content mt-0">
        <div class="side-app">
            <div class="main-container container-fluid" style="margin-bottom:90px">

                {{-- PAGE HEADER --}}
                <div class="page-header d-flex flex-wrap align-items-center justify-content-between">
                    <div>
                        <h1 class="page-title">Payment Monitor</h1>
                        <p class="text-muted mb-0" style="font-size: 13px;">Real-time overview of transactions, registrations,
                            and coupon usage</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="refresh-btn" id="refreshDashboard" title="Refresh all data">
                            <i class="fa-solid fa-rotate" id="refreshIcon"></i>
                            <span>Refresh</span>
                        </button>
                        <ol class="breadcrumb mb-0 py-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.monitor.index') }}">Monitor</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Payments</li>
                        </ol>
                    </div>
                </div>

                {{-- ════════════════════════════════════════════════════════ --}}
                {{-- STATS CARDS ROW --}}
                {{-- ════════════════════════════════════════════════════════ --}}
                <div class="row g-3 mb-4">

                    {{-- Revenue --}}
                    <div class="col-xl-3 col-lg-6 col-md-6">
                        <div class="card stat-card" data-stat="revenue">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon" style="background: linear-gradient(118deg, #00AEEF, #0095CC);">
                                    <i class="fa-solid fa-dollar-sign"></i>
                                </div>
                                <div>
                                    <div class="stat-label">Total Revenue</div>
                                    <div class="stat-value">${{ number_format($stats['totalRevenue'], 2) }}</div>
                                    <div class="stat-sub">{{ $stats['transactionCount'] }} transactions</div>
                                </div>
                            </div>
                            <div class="stat-bg-icon"><i class="fa-solid fa-chart-line"></i></div>
                        </div>
                    </div>

                    {{-- Admin Fees --}}
                    <div class="col-xl-3 col-lg-6 col-md-6">
                        <div class="card stat-card" data-stat="admin-fees">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon" style="background: linear-gradient(118deg, #10B981, #059669);">
                                    <i class="fa-solid fa-building-columns"></i>
                                </div>
                                <div>
                                    <div class="stat-label">Admin Fees Collected</div>
                                    <div class="stat-value">${{ number_format($stats['totalAdminFees'], 2) }}</div>
                                    <div class="stat-sub">Director share:
                                        ${{ number_format($stats['totalDirectorAmount'], 2) }}</div>
                                </div>
                            </div>
                            <div class="stat-bg-icon"><i class="fa-solid fa-coins"></i></div>
                        </div>
                    </div>

                    {{-- Discounts / Coupons --}}
                    <div class="col-xl-3 col-lg-6 col-md-6">
                        <div class="card stat-card" data-stat="discounts">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon" style="background: linear-gradient(118deg, #F59E0B, #D97706);">
                                    <i class="fa-solid fa-tag"></i>
                                </div>
                                <div>
                                    <div class="stat-label">Discounts Given</div>
                                    <div class="stat-value">${{ number_format($stats['totalDiscountGiven'], 2) }}</div>
                                    <div class="stat-sub">{{ $stats['activeCoupons'] }} active coupons</div>
                                </div>
                            </div>
                            <div class="stat-bg-icon"><i class="fa-solid fa-percent"></i></div>
                        </div>
                    </div>

                    {{-- Registrations --}}
                    <div class="col-xl-3 col-lg-6 col-md-6">
                        <div class="card stat-card" data-stat="registrations">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon" style="background: linear-gradient(118deg, #8B5CF6, #7C3AED);">
                                    <i class="fa-solid fa-users"></i>
                                </div>
                                <div>
                                    <div class="stat-label">Registrations</div>
                                    <div class="stat-value">{{ $stats['totalRegistrations'] }}</div>
                                    <div class="stat-sub">{{ $stats['totalCheckedIn'] }} checked in ·
                                        {{ $stats['totalCamps'] }} camps</div>
                                </div>
                            </div>
                            <div class="stat-bg-icon"><i class="fa-solid fa-user-check"></i></div>
                        </div>
                    </div>

                </div>

                {{-- ════════════════════════════════════════════════════════ --}}
                {{-- CHART + PENDING SUMMARY ROW --}}
                {{-- ════════════════════════════════════════════════════════ --}}
                <div class="row g-3 mb-4">
                    <div class="col-xl-8 col-lg-7 d-flex">
                        <div class="card w-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between section-title"
                                    style="border-bottom: 2px solid #F3F4F6; padding-bottom: 12px; margin-bottom: 16px;">
                                    <div>
                                        <i class="fa-solid fa-chart-simple"></i>
                                        <span id="chartTitle">Revenue Trend (12 Months)</span>
                                    </div>
                                    <div class="btn-group btn-group-sm range-tabs" role="group" aria-label="Chart range">
                                        <button type="button" class="btn btn-outline-primary" data-months="6">6M</button>
                                        <button type="button" class="btn btn-outline-primary active"
                                            data-months="12">12M</button>
                                        <button type="button" class="btn btn-outline-primary" data-months="24">24M</button>
                                    </div>
                                </div>
                                <div class="chart-container">
                                    <canvas id="revenueChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4 col-lg-5 d-flex">
                        <div class="card w-100">
                            <div class="card-body">
                                <div class="section-title"><i class="fa-solid fa-circle-exclamation"></i> Quick Summary
                                </div>

                                {{-- Pending Attempts --}}
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <span class="text-muted">Pending Payments</span>
                                    <span class="badge bg-warning fs-6"><span
                                            data-key="pendingAttempts">{{ $stats['pendingAttempts'] }}</span></span>
                                </div>
                                {{-- Failed --}}
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <span class="text-muted">Failed Attempts</span>
                                    <span class="badge bg-danger fs-6"><span
                                            data-key="failedAttempts">{{ $stats['failedAttempts'] }}</span></span>
                                </div>
                                {{-- Successful --}}
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <span class="text-muted">Successful Payments</span>
                                    <span class="badge bg-success fs-6"><span
                                            data-key="transactionCount">{{ $stats['transactionCount'] }}</span></span>
                                </div>
                                {{-- Active Camps --}}
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <span class="text-muted">Active Camps</span>
                                    <span class="badge bg-info fs-6"><span
                                            data-key="activeCamps">{{ $stats['activeCamps'] }}</span></span>
                                </div>
                                {{-- Checked In --}}
                                <div class="d-flex justify-content-between align-items-center py-2">
                                    <span class="text-muted">Checked In</span>
                                    <span class="badge bg-primary fs-6"><span
                                            data-key="totalCheckedIn">{{ $stats['totalCheckedIn'] }}</span></span>
                                </div>

                                {{-- Top Coupons (mini list) --}}
                                <div id="topCouponsSection">
                                    @if ($topCoupons->count() > 0)
                                        <hr>
                                        <div class="section-title"
                                            style="font-size: 13px; border: none; padding-bottom: 6px; margin-bottom: 8px;">
                                            <i class="fa-solid fa-trophy"></i> Top Coupons
                                        </div>
                                        @foreach ($topCoupons as $c)
                                            <div class="d-flex justify-content-between align-items-center py-1"
                                                style="font-size: 13px;">
                                                <span><strong>{{ $c->code }}</strong></span>
                                                <span class="text-muted">{{ $c->used_count }} uses</span>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ════════════════════════════════════════════════════════ --}}
                {{-- DATA TABLES SECTION --}}
                {{-- ════════════════════════════════════════════════════════ --}}
                <div class="card">
                    <div class="card-header border-bottom d-flex align-items-center justify-content-between">
                        <h3 class="card-title mb-0" style="font-size: 16px;">
                            <i class="fa-solid fa-list me-1"></i> Detailed Logs
                        </h3>
                        <div class="monitor-tabs btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary active"
                                data-table="payments">Payments</button>
                            <button type="button" class="btn btn-outline-primary"
                                data-table="attempts">Attempts</button>
                            <button type="button" class="btn btn-outline-primary"
                                data-table="registrations">Registrations</button>
                            <button type="button" class="btn btn-outline-primary" data-table="coupons">Coupons</button>
                        </div>
                    </div>
                    <div class="card-body">
                        @include('backend.layouts.monitor.partials._table_payments')
                        @include('backend.layouts.monitor.partials._table_attempts')
                        @include('backend.layouts.monitor.partials._table_registrations')
                        @include('backend.layouts.monitor.partials._table_coupons')
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection



@push('styles')
    <link href="{{ asset('default/datatable.css') }}" rel="stylesheet" />
    <style>
        /* Stat Cards */
        .stat-card {
            border: none;
            border-radius: 12px;
            padding: 20px 24px;
            transition: all 0.25s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        }

        .stat-card .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #fff;
            flex-shrink: 0;
        }

        .stat-card .stat-label {
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #9CA3AF;
            margin-bottom: 2px;
        }

        .stat-card .stat-value {
            font-size: 26px;
            font-weight: 700;
            color: #1F2937;
            line-height: 1.2;
        }

        .stat-card .stat-sub {
            font-size: 12px;
            color: #6B7280;
            margin-top: 2px;
        }

        .stat-card .stat-bg-icon {
            position: absolute;
            right: -10px;
            bottom: -10px;
            font-size: 70px;
            opacity: 0.06;
            color: inherit;
        }

        /* Section Titles */
        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: #1F2937;
            padding-bottom: 12px;
            margin-bottom: 16px;
            border-bottom: 2px solid #F3F4F6;
        }

        .section-title i {
            color: #00AEEF;
            margin-right: 8px;
        }

        /* Chart Container */
        .chart-container {
            position: relative;
            height: 250px;
        }

        /* Tab Buttons */
        .monitor-tabs .btn {
            border-radius: 8px;
            padding: 6px 16px;
            font-size: 13px;
            font-weight: 500;
        }

        .monitor-tabs .btn.active {
            background: #00AEEF;
            color: #fff;
            border-color: #00AEEF;
        }

        /* Refresh button */
        .refresh-btn {
            border: 1px solid #E5E7EB;
            background: #fff;
            border-radius: 8px;
            padding: 6px 14px;
            font-size: 13px;
            font-weight: 500;
            color: #374151;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .refresh-btn:hover {
            background: #F9FAFB;
            border-color: #00AEEF;
            color: #00AEEF;
        }

        .refresh-btn:active {
            transform: scale(0.96);
        }

        .refresh-btn .spinning {
            animation: spin 0.8s linear infinite;
            transform-origin: center;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Table tweaks */
        .table-xs th,
        .table-xs td {
            padding: 6px 10px !important;
            font-size: 13px;
        }

        .table th {
            font-weight: 600;
            color: #374151;
            background: #F9FAFB;
        }

        /* Table Loading Overlay */
        .table-loader {
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(3px);
            -webkit-backdrop-filter: blur(3px);
            z-index: 1050;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 14px;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
            border-radius: 8px;
            pointer-events: none;
        }

        .table-loader.show {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }

        .table-loader-spinner {
            width: 42px;
            height: 42px;
            border: 3px solid #E5E7EB;
            border-top-color: #00AEEF;
            border-radius: 50%;
            animation: dt-loader-spin 0.7s linear infinite;
        }

        @keyframes dt-loader-spin {
            to {
                transform: rotate(360deg);
            }
        }

        .table-loader-text {
            font-size: 13px;
            font-weight: 500;
            color: #6B7280;
            letter-spacing: 0.3px;
        }

        /* Hide DataTables' built-in processing message since we use our own overlay */
        .dataTables_processing {
            display: none !important;
        }

        /* DataTables search & length controls */
        .dataTables_filter {
            text-align: right;
        }

        .dataTables_filter label {
            margin-bottom: 0;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 500;
            color: #374151;
            font-size: 13px;
        }

        .dataTables_filter input {
            border: 1px solid #D1D5DB;
            border-radius: 6px;
            padding: 5px 12px 5px 32px;
            font-size: 13px;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            width: 220px;
            background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512' fill='%239CA3AF'%3E%3Cpath d='M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z'/%3E%3C/svg%3E") 10px center / 16px no-repeat;
        }

        .dataTables_filter input:focus {
            border-color: #00AEEF;
            box-shadow: 0 0 0 3px rgba(0, 174, 239, 0.1);
        }

        .dataTables_filter input::placeholder {
            color: #9CA3AF;
        }

        .dataTables_length {
            text-align: left;
        }

        .dataTables_length label {
            margin-bottom: 0;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 500;
            color: #374151;
            font-size: 13px;
        }

        .dataTables_length select {
            border: 1px solid #D1D5DB;
            border-radius: 6px;
            padding: 5px 8px;
            font-size: 13px;
            outline: none;
            transition: border-color 0.2s ease;
            background: #fff;
            cursor: pointer;
        }

        .dataTables_length select:focus {
            border-color: #00AEEF;
        }

        .dataTables_info {
            font-size: 13px;
            color: #6B7280;
            padding-top: 8px;
        }

        .dataTables_paginate {
            padding-top: 8px;
        }

        /* .dataTables_paginate .paginate_button {
            border-radius: 6px;
            padding: 4px 12px;
            font-size: 13px;
            margin: 0 2px;
            border: 1px solid #E5E7EB;
            background: #fff;
            color: #374151 !important;
            transition: all 0.2s ease;
        } */
        .dataTables_paginate .paginate_button:hover {
            background: #F3F4F6;
            border-color: #D1D5DB;
            color: #00AEEF !important;
        }

        .dataTables_paginate .paginate_button.current {
            background: #00AEEF !important;
            border-color: #00AEEF !important;
            color: #fff !important;
        }

        .dataTables_paginate .paginate_button.disabled {
            opacity: 0.4;
            cursor: default;
        }

        /* Responsive: mobile-first breakpoints */
        @media (max-width: 575.98px) {
            .stat-card {
                padding: 14px 16px;
            }

            .stat-card .stat-icon {
                width: 36px;
                height: 36px;
                font-size: 14px;
            }

            .stat-card .stat-value {
                font-size: 18px;
            }

            .stat-card .stat-label {
                font-size: 10px;
            }

            .stat-card .stat-sub {
                font-size: 11px;
            }

            .stat-card .stat-bg-icon {
                font-size: 50px;
                right: -6px;
                bottom: -6px;
            }

            .chart-container {
                height: 190px;
            }

            .range-tabs {
                width: 100%;
            }

            .range-tabs .btn {
                flex: 1;
                font-size: 12px;
                padding: 5px 10px;
            }

            .badge.fs-6 {
                font-size: 0.8rem;
            }

            .page-header h1.page-title {
                font-size: 18px;
            }

            .monitor-tabs {
                width: 100%;
                flex-wrap: wrap;
            }

            .monitor-tabs .btn {
                flex: 1;
                min-width: 0;
                padding: 5px 8px;
                font-size: 11px;
            }
        }

        @media (min-width: 576px) and (max-width: 991.98px) {
            .stat-card {
                padding: 16px 20px;
            }

            .stat-card .stat-value {
                font-size: 22px;
            }

            .chart-container {
                height: 220px;
            }
        }

        @media (max-width: 767.98px) {
            .section-title.d-flex {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 8px;
            }

            .card-header.d-flex {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 10px;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 8px;
            }

            .page-header .ms-auto {
                margin-left: 0 !important;
            }
        }

        @media (max-width: 399.98px) {
            .chart-container {
                height: 160px;
            }

            .monitor-tabs .btn {
                font-size: 10px;
                padding: 4px 6px;
            }
        }
    </style>
@endpush

@push('scripts')
    @include('backend.partials._scripts-datatable')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            $.ajaxSetup({
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
                }
            });

            // ─── Revenue Chart ───
            const ctx = document.getElementById('revenueChart').getContext('2d');
            const chartData = @json($revenueChart);

            const labels = chartData.monthLabels.map(m => {
                const parts = m.split('-');
                const d = new Date(parts[0], parts[1] - 1);
                return d.toLocaleString('default', {
                    month: 'short',
                    year: '2-digit'
                });
            });

            // Modern gradient fills (keeps original colors)
            const chartHeight = document.getElementById('revenueChart').offsetHeight || 250;
            const revGrad = ctx.createLinearGradient(0, 0, 0, chartHeight);
            revGrad.addColorStop(0, 'rgba(0, 174, 239, 1)');
            revGrad.addColorStop(0.5, 'rgba(0, 174, 239, 0.8)');
            revGrad.addColorStop(1, 'rgba(0, 174, 239, 0.15)');

            const feesGrad = ctx.createLinearGradient(0, 0, 0, chartHeight);
            feesGrad.addColorStop(0, 'rgba(16, 185, 129, 1)');
            feesGrad.addColorStop(0.5, 'rgba(16, 185, 129, 0.8)');
            feesGrad.addColorStop(1, 'rgba(16, 185, 129, 0.15)');

            // Line chart area-fill gradient helper (lighter touch – fades to transparent)
            function makeAreaGradient(ctxColor, h) {
                const g = ctx.createLinearGradient(0, 0, 0, h);
                g.addColorStop(0, ctxColor.replace('0.15', '0.35'));
                g.addColorStop(1, ctxColor.replace('0.15', '0'));
                return g;
            }

            let revenueChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                            label: 'Revenue',
                            data: chartData.revenue,
                            borderColor: '#00AEEF',
                            backgroundColor: makeAreaGradient('rgba(0, 174, 239, 0.15)', chartHeight),
                            fill: true,
                            tension: 0.35,
                            pointRadius: 4,
                            pointHoverRadius: 7,
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: '#00AEEF',
                            pointBorderWidth: 3,
                            pointHoverBackgroundColor: '#00AEEF',
                            pointHoverBorderColor: '#ffffff',
                            pointHoverBorderWidth: 2,
                            borderWidth: 3,
                        },
                        {
                            label: 'Admin Fees',
                            data: chartData.fees,
                            borderColor: '#10B981',
                            backgroundColor: makeAreaGradient('rgba(16, 185, 129, 0.15)', chartHeight),
                            fill: true,
                            tension: 0.35,
                            pointRadius: 4,
                            pointHoverRadius: 7,
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: '#10B981',
                            pointBorderWidth: 3,
                            pointHoverBackgroundColor: '#10B981',
                            pointHoverBorderColor: '#ffffff',
                            pointHoverBorderWidth: 2,
                            borderWidth: 3,
                        },
                        {
                            label: 'Transactions',
                            data: chartData.transactions,
                            borderColor: '#8B5CF6',
                            fill: false,
                            tension: 0.35,
                            borderDash: [5, 4],
                            borderWidth: 2,
                            pointRadius: 3,
                            pointHoverRadius: 6,
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: '#8B5CF6',
                            pointBorderWidth: 2,
                            pointHoverBackgroundColor: '#8B5CF6',
                            pointHoverBorderColor: '#ffffff',
                            pointHoverBorderWidth: 2,
                            yAxisID: 'y1',
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 1000,
                        easing: 'easeOutQuart'
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                boxWidth: 10,
                                padding: 14,
                                font: {
                                    size: 12,
                                    weight: '500'
                                },
                                usePointStyle: true,
                                color: '#374151'
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(255,255,255,0.96)',
                            titleColor: '#1F2937',
                            titleFont: {
                                weight: '600',
                                size: 13
                            },
                            bodyColor: '#374151',
                            bodyFont: {
                                size: 12
                            },
                            borderColor: 'rgba(0,0,0,0.06)',
                            borderWidth: 1,
                            padding: 12,
                            cornerRadius: 8,
                            boxPadding: 6,
                            usePointStyle: true,
                            callbacks: {
                                label: function(ctx) {
                                    if (ctx.dataset.yAxisID === 'y1') {
                                        return ctx.dataset.label + ':  ' + Number(ctx.raw);
                                    }
                                    return ctx.dataset.label + ':  $' + Number(ctx.raw).toFixed(2);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Revenue ($)',
                                font: {
                                    size: 11
                                },
                                color: '#6B7280'
                            },
                            ticks: {
                                callback: v => '$' + (v >= 1000 ? (v / 1000).toFixed(1) + 'k' : v.toFixed(
                                    0)),
                                font: {
                                    size: 11
                                },
                                color: '#6B7280',
                                maxTicksLimit: 7
                            },
                            grid: {
                                color: 'rgba(0,0,0,0.04)',
                                drawBorder: false
                            }
                        },
                        y1: {
                            beginAtZero: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Transactions',
                                font: {
                                    size: 11
                                },
                                color: '#6B7280'
                            },
                            ticks: {
                                precision: 0,
                                font: {
                                    size: 11
                                },
                                color: '#8B5CF6',
                                maxTicksLimit: 6
                            },
                            grid: {
                                display: false
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
                                color: '#6B7280',
                                maxRotation: 45
                            }
                        }
                    }
                }
            });

            // ─── Chart Range Toggle ───
            $('.range-tabs .btn').on('click', function() {
                const btn = $(this);
                const months = btn.data('months');

                // Skip if already active
                if (btn.hasClass('active')) return;

                // Update button state
                $('.range-tabs .btn').removeClass('active');
                btn.addClass('active');

                // Update title with a subtle pulse
                const title = $('#chartTitle');
                title.text('Revenue Trend (' + months + ' Months)');

                // Fetch new data
                $.ajax({
                    url: '{{ route('admin.monitor.index') }}',
                    data: {
                        type: 'chart',
                        months: months
                    },
                    dataType: 'json',
                    success: function(data) {
                        const newLabels = data.monthLabels.map(function(m) {
                            const parts = m.split('-');
                            const d = new Date(parts[0], parts[1] - 1);
                            return d.toLocaleString('default', {
                                month: 'short',
                                year: '2-digit'
                            });
                        });

                        // Regenerate area-fill gradients for the line chart
                        const h = document.getElementById('revenueChart').offsetHeight || 250;
                        const canvasCtx = revenueChart.canvas.getContext('2d');
                        const rGrad = canvasCtx.createLinearGradient(0, 0, 0, h);
                        rGrad.addColorStop(0, 'rgba(0, 174, 239, 0.35)');
                        rGrad.addColorStop(1, 'rgba(0, 174, 239, 0)');

                        const fGrad = canvasCtx.createLinearGradient(0, 0, 0, h);
                        fGrad.addColorStop(0, 'rgba(16, 185, 129, 0.35)');
                        fGrad.addColorStop(1, 'rgba(16, 185, 129, 0)');

                        // Build fresh area-fill gradients for line chart
                        revenueChart.data.labels = newLabels;
                        revenueChart.data.datasets[0].data = data.revenue;
                        revenueChart.data.datasets[0].backgroundColor = rGrad;
                        revenueChart.data.datasets[1].data = data.fees;
                        revenueChart.data.datasets[1].backgroundColor = fGrad;
                        revenueChart.data.datasets[2].data = data.transactions;

                        // Animate the update with a fresh transition
                        revenueChart.update('default');
                    },
                    error: function() {
                        // Revert button state on failure
                        $('.range-tabs .btn').removeClass('active');
                        $('.range-tabs .btn[data-months="12"]').addClass('active');
                        title.text('Revenue Trend (12 Months)');
                    }
                });
            });

            // ─── Dashboard Refresh ───
            $('#refreshDashboard').on('click', function() {
                const btn = $(this);
                const icon = $('#refreshIcon');
                const activeRange = $('.range-tabs .btn.active').data('months') || 12;

                // Prevent double-click
                if (btn.prop('disabled')) return;
                btn.prop('disabled', true);
                icon.addClass('spinning');

                // Fetch chart + overview data in parallel
                const chartPromise = $.ajax({
                    url: '{{ route('admin.monitor.index') }}',
                    data: {
                        type: 'chart',
                        months: activeRange
                    },
                    dataType: 'json'
                });

                const overviewPromise = $.ajax({
                    url: '{{ route('admin.monitor.index') }}',
                    data: {
                        type: 'overview'
                    },
                    dataType: 'json'
                });

                $.when(chartPromise, overviewPromise).done(function(chartResp, overviewResp) {
                    // jQuery wraps each deferred args into an array: [data, status, jqXHR]
                    const data = chartResp[0];
                    const overview = overviewResp[0];
                    const s = overview.stats;

                    // 1. Update stat cards (using data-stat attributes for robustness)
                    const statMap = {
                        'revenue': {
                            val: '$' + nf(s.totalRevenue, 2),
                            sub: s.transactionCount + ' transactions'
                        },
                        'admin-fees': {
                            val: '$' + nf(s.totalAdminFees, 2),
                            sub: 'Director share: $' + nf(s.totalDirectorAmount, 2)
                        },
                        'discounts': {
                            val: '$' + nf(s.totalDiscountGiven, 2),
                            sub: s.activeCoupons + ' active coupons'
                        },
                        'registrations': {
                            val: s.totalRegistrations,
                            sub: s.totalCheckedIn + ' checked in \u00b7 ' + s.totalCamps +
                                ' camps'
                        },
                    };
                    $.each(statMap, function(key, vals) {
                        const card = $('.stat-card[data-stat="' + key + '"]');
                        card.find('.stat-value').text(vals.val);
                        card.find('.stat-sub').text(vals.sub);
                    });

                    // 2. Update Quick Summary badges via data-key attributes
                    $('.card:has(.section-title:contains("Quick Summary")) [data-key]').each(
                        function() {
                            const el = $(this);
                            const key = el.data('key');
                            if (s[key] !== undefined) el.text(s[key]);
                        });

                    // 3. Update Top Coupons (using dedicated container)
                    const $tc = $('#topCouponsSection');
                    if (overview.topCoupons && overview.topCoupons.length > 0) {
                        let html =
                            '<hr><div class="section-title" style="font-size:13px;border:none;padding-bottom:6px;margin-bottom:8px;">' +
                            '<i class="fa-solid fa-trophy"></i> Top Coupons</div>';
                        $.each(overview.topCoupons, function(i, c) {
                            html +=
                                '<div class="d-flex justify-content-between align-items-center py-1" style="font-size:13px;">' +
                                '<span><strong>' + $('<span>').text(c.code).html() +
                                '</strong></span>' +
                                '<span class="text-muted">' + c.used_count +
                                ' uses</span></div>';
                        });
                        $tc.html(html);
                    } else {
                        $tc.empty();
                    }

                    // 3. Update chart
                    const newLabels = data.monthLabels.map(function(m) {
                        const parts = m.split('-');
                        const d = new Date(parts[0], parts[1] - 1);
                        return d.toLocaleString('default', {
                            month: 'short',
                            year: '2-digit'
                        });
                    });
                    const h = document.getElementById('revenueChart').offsetHeight || 250;
                    const canvasCtx = revenueChart.canvas.getContext('2d');
                    const rGrad = canvasCtx.createLinearGradient(0, 0, 0, h);
                    rGrad.addColorStop(0, 'rgba(0, 174, 239, 0.35)');
                    rGrad.addColorStop(1, 'rgba(0, 174, 239, 0)');
                    const fGrad = canvasCtx.createLinearGradient(0, 0, 0, h);
                    fGrad.addColorStop(0, 'rgba(16, 185, 129, 0.35)');
                    fGrad.addColorStop(1, 'rgba(16, 185, 129, 0)');

                    revenueChart.data.labels = newLabels;
                    revenueChart.data.datasets[0].data = data.revenue;
                    revenueChart.data.datasets[0].backgroundColor = rGrad;
                    revenueChart.data.datasets[1].data = data.fees;
                    revenueChart.data.datasets[1].backgroundColor = fGrad;
                    revenueChart.data.datasets[2].data = data.transactions;
                    revenueChart.update('default');

                    // 4. Reload all DataTables
                    $.fn.DataTable.tables({
                        api: true
                    }).iterator('table', function() {
                        this.ajax.reload(null, false); // false = keep current page
                    });

                }).always(function() {
                    icon.removeClass('spinning');
                    btn.prop('disabled', false);
                });
            });

            // Helper: format numbers with commas (moved inside ready for clean scope)
            function nf(num, decimals) {
                if (num === undefined || num === null) return '0.00';
                num = Number(num);
                const parts = num.toFixed(decimals).split('.');
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                return parts.join('.');
            }

            // ─── Query String: read active tab from URL ───
            var urlParams = new URLSearchParams(window.location.search);
            var validTabs = ['payments', 'attempts', 'registrations', 'coupons'];
            var activeTab = urlParams.get('tab') || 'payments';
            if (validTabs.indexOf(activeTab) === -1) activeTab = 'payments';

            // ─── Lazy init tracker ───
            var tableInits = {
                payments: false,
                attempts: false,
                registrations: false,
                coupons: false
            };

            // ─── Helper: toggle loader overlay for a DataTable ───
            var ellipsisTimers = {};

            function toggleTableLoader(dtId, show) {
                var loaderId = '#loader-' + dtId.replace('dt', '').toLowerCase();
                var $loader = $(loaderId);
                var $text = $loader.find('.table-loader-text');
                if (show) {
                    $loader.addClass('show');
                    // Animate ellipsis via JS (CSS cannot animate content)
                    var baseText = $text.data('base') || $text.text().replace(/\.+$/, '');
                    $text.data('base', baseText);
                    var dots = 0;
                    if (ellipsisTimers[dtId]) clearInterval(ellipsisTimers[dtId]);
                    ellipsisTimers[dtId] = setInterval(function() {
                        dots = (dots + 1) % 4;
                        $text.text(baseText + '.'.repeat(dots));
                    }, 500);
                } else {
                    $loader.removeClass('show');
                    if (ellipsisTimers[dtId]) {
                        clearInterval(ellipsisTimers[dtId]);
                        delete ellipsisTimers[dtId];
                    }
                    // Reset text to base (3 dots for static state)
                    var base = $text.data('base');
                    if (base) $text.text(base + '...');
                }
            }

            // ─── DataTable init factories ───
            function initPaymentsTable() {
                if (tableInits.payments) return;
                var dt = $('#dtPayments').DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    order: [],
                    pageLength: 25,
                    dom: "<'row'<'col-sm-6'l><'col-sm-6'f>>tipr",
                    searchDelay: 500,
                    ajax: {
                        url: "{{ route('admin.monitor.index') }}",
                        data: {
                            type: 'payments'
                        }
                    },
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'camp',
                            name: 'camp'
                        },
                        {
                            data: 'referee',
                            name: 'referee'
                        },
                        {
                            data: 'amount',
                            name: 'amount'
                        },
                        {
                            data: 'admin_fee',
                            name: 'admin_fee'
                        },
                        {
                            data: 'director_amount',
                            name: 'director_amount'
                        },
                        {
                            data: 'discount',
                            name: 'discount'
                        },
                        {
                            data: 'coupon',
                            name: 'coupon'
                        },
                        {
                            data: 'paid_at',
                            name: 'paid_at'
                        },
                    ]
                });
                dt.on('processing.dt', function(e, settings, processing) {
                    toggleTableLoader('dtPayments', processing);
                });
                tableInits.payments = true;
            }

            function initAttemptsTable() {
                if (tableInits.attempts) return;
                var dt = $('#dtAttempts').DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    order: [],
                    pageLength: 25,
                    dom: "<'row'<'col-sm-6'l><'col-sm-6'f>>tipr",
                    searchDelay: 500,
                    ajax: {
                        url: "{{ route('admin.monitor.index') }}",
                        data: {
                            type: 'attempts'
                        }
                    },
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'camp',
                            name: 'camp'
                        },
                        {
                            data: 'referee',
                            name: 'referee'
                        },
                        {
                            data: 'amount',
                            name: 'amount'
                        },
                        {
                            data: 'status',
                            name: 'status'
                        },
                        {
                            data: 'coupon',
                            name: 'coupon'
                        },
                        {
                            data: 'created_at',
                            name: 'created_at'
                        },
                    ]
                });
                dt.on('processing.dt', function(e, settings, processing) {
                    toggleTableLoader('dtAttempts', processing);
                });
                tableInits.attempts = true;
            }

            function initRegistrationsTable() {
                if (tableInits.registrations) return;
                var dt = $('#dtRegistrations').DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    order: [],
                    pageLength: 25,
                    dom: "<'row'<'col-sm-6'l><'col-sm-6'f>>tipr",
                    searchDelay: 500,
                    ajax: {
                        url: "{{ route('admin.monitor.index') }}",
                        data: {
                            type: 'registrations'
                        }
                    },
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'camp',
                            name: 'camp'
                        },
                        {
                            data: 'referee',
                            name: 'referee'
                        },
                        {
                            data: 'status',
                            name: 'status'
                        },
                        {
                            data: 'registered_at',
                            name: 'registered_at'
                        },
                        {
                            data: 'checked_in_at',
                            name: 'checked_in_at'
                        },
                    ]
                });
                dt.on('processing.dt', function(e, settings, processing) {
                    toggleTableLoader('dtRegistrations', processing);
                });
                tableInits.registrations = true;
            }

            function initCouponsTable() {
                if (tableInits.coupons) return;
                var dt = $('#dtCoupons').DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    order: [],
                    pageLength: 25,
                    dom: "<'row'<'col-sm-6'l><'col-sm-6'f>>tipr",
                    searchDelay: 500,
                    ajax: {
                        url: "{{ route('admin.monitor.index') }}",
                        data: {
                            type: 'coupons'
                        }
                    },
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'code',
                            name: 'code'
                        },
                        {
                            data: 'type',
                            name: 'type'
                        },
                        {
                            data: 'usage',
                            name: 'usage'
                        },
                        {
                            data: 'revenue',
                            name: 'revenue'
                        },
                        {
                            data: 'status',
                            name: 'status'
                        },
                        {
                            data: 'expires',
                            name: 'expires'
                        },
                    ]
                });
                dt.on('processing.dt', function(e, settings, processing) {
                    toggleTableLoader('dtCoupons', processing);
                });
                tableInits.coupons = true;
            }

            // ─── Helpers: init map & DT resize ───
            var initMap = {
                payments: initPaymentsTable,
                attempts: initAttemptsTable,
                registrations: initRegistrationsTable,
                coupons: initCouponsTable
            };

            function getDtId(tabName) {
                var map = {
                    payments: 'dtPayments',
                    attempts: 'dtAttempts',
                    registrations: 'dtRegistrations',
                    coupons: 'dtCoupons'
                };
                return '#' + map[tabName];
            }

            function resizeDataTable(tabName) {
                var sel = getDtId(tabName);
                if ($.fn.DataTable.isDataTable(sel)) {
                    $(sel).DataTable().columns.adjust().responsive.recalc();
                }
            }

            // ─── Tab Switching with lazy init + query string ───
            function switchTab(tabName) {
                // Update tab buttons
                $('.monitor-tabs .btn').removeClass('active');
                $('.monitor-tabs .btn[data-table="' + tabName + '"]').addClass('active');

                // Show/hide tables
                $('[id^="table-"]').hide();
                $('#table-' + tabName).show();

                // Lazy-init the DataTable if not yet done
                if (!tableInits[tabName]) {
                    initMap[tabName]();
                }

                // Recalculate responsive columns after a tiny delay to let the browser layout settle
                setTimeout(function() {
                    resizeDataTable(tabName);
                }, 50);

                // Update browser URL query string without reloading (preserves other params)
                var url = new URL(window.location);
                url.searchParams.set('tab', tabName);
                history.replaceState(null, '', url);
            }

            // ─── Tab click handler ───
            $('.monitor-tabs .btn').on('click', function() {
                switchTab($(this).data('table'));
            });

            // ─── Activate initial tab from query string ───
            switchTab(activeTab);
        });
    </script>
@endpush
