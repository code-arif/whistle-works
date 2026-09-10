@extends('backend.app', ['title' => 'User Management'])

@section('content')
    <div class="app-content main-content mt-0">
        <div class="side-app" style="margin-bottom: 50px">
            <div class="main-container container-fluid">

                <!-- PAGE-HEADER -->
                <div class="page-header d-flex flex-wrap align-items-center justify-content-between">
                    <div>
                        <h1 class="page-title mb-1">User Management</h1>
                        <p class="text-muted fs-13 mb-0">Manage referees, directors, and evaluators</p>
                    </div>
                    <ol class="breadcrumb mb-0 py-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.users.manage.index') }}">Users</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Index</li>
                    </ol>
                </div>
                <!-- PAGE-HEADER END -->

                {{-- STATISTICS ROW --}}
                <div class="row mb-4 g-3">
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                        <div class="card stats-card" style="border-left-color: #dc3545; cursor: pointer;"
                            onclick="switchTab('all')">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Total Users</h6>
                                        <h3 class="mb-0">{{ array_sum($roleCounts->toArray()) }}</h3>
                                    </div>
                                    <div class="icon-service bg-danger-transparent text-danger p-3 rounded-3">
                                        <i class="fe fe-users fs-20"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                        <div class="card stats-card" style="border-left-color: #007bff; cursor: pointer;"
                            onclick="switchTab('director')">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Directors</h6>
                                        <h3 class="mb-0">{{ $roleCounts['director'] ?? 0 }}</h3>
                                    </div>
                                    <div class="icon-service bg-primary-transparent text-primary p-3 rounded-3">
                                        <i class="fe fe-briefcase fs-20"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                        <div class="card stats-card" style="border-left-color: #28a745; cursor: pointer;"
                            onclick="switchTab('referee')">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Referees</h6>
                                        <h3 class="mb-0">{{ $roleCounts['referee'] ?? 0 }}</h3>
                                    </div>
                                    <div class="icon-service bg-success-transparent text-success p-3 rounded-3">
                                        <i class="fe fe-flag fs-20"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                        <div class="card stats-card" style="border-left-color: #17a2b8; cursor: pointer;"
                            onclick="switchTab('evaluator')">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Evaluators</h6>
                                        <h3 class="mb-0">{{ $roleCounts['evaluator'] ?? 0 }}</h3>
                                    </div>
                                    <div class="icon-service bg-info-transparent text-info p-3 rounded-3">
                                        <i class="fe fe-check-circle fs-20"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- TABS --}}
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="monitor-tabs btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary active" data-role="all">All Users</button>
                            <button type="button" class="btn btn-outline-primary" data-role="director">Directors <span
                                    class="badge p-2 bg-primary ms-1">{{ $roleCounts['director'] ?? 0 }}</span></button>
                            <button type="button" class="btn btn-outline-primary" data-role="referee">Referees <span
                                    class="badge p-2 bg-success ms-1">{{ $roleCounts['referee'] ?? 0 }}</span></button>
                            <button type="button" class="btn btn-outline-primary" data-role="evaluator">Evaluators <span
                                    class="badge p-2 bg-info ms-1">{{ $roleCounts['evaluator'] ?? 0 }}</span></button>
                        </div>
                    </div>
                </div>

                {{-- FILTERS --}}
                <div class="row">
                    <div class="col-12">
                        <div class="filter-card">
                            <div class="row align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" id="statusFilter">
                                        <option value="">All Status</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Date From</label>
                                    <input type="date" class="form-control" id="dateFrom">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Date To</label>
                                    <input type="date" class="form-control" id="dateTo">
                                </div>
                                <div class="col-md-5 d-flex gap-2">
                                    <button type="button" class="btn btn-primary" onclick="applyFilters()">
                                        <i class="fe fe-filter me-1"></i> Apply
                                    </button>
                                    <button type="button" class="btn btn-secondary" onclick="resetFilters()">
                                        <i class="fe fe-refresh-cw me-1"></i> Reset
                                    </button>
                                    <button class="btn btn-outline-primary ms-auto" onclick="exportUsers()">
                                        <i class="fe fe-download me-1"></i> Export
                                    </button>
                                    <a href="{{ route('admin.users.manage.trash') }}"
                                        class="btn btn-outline-danger btn-sm ms-2 d-inline-flex align-items-center gap-1">
                                        <i class="fe fe-trash-2" style="font-size: 12px; line-height: 1;"></i>
                                        <span>Trash</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- USER LIST TABLES (separate per role) --}}
                <div class="row">
                    <div class="col-12 col-sm-12">
                        <div class="card product-sales-main">
                            <div class="card-header border-bottom">
                                <h3 class="card-title mb-0" id="tableTitle">All Users</h3>
                            </div>
                            <div class="card-body">
                                @include('backend.layouts.users.partials._table_all')
                                @include('backend.layouts.users.partials._table_directors')
                                @include('backend.layouts.users.partials._table_referees')
                                @include('backend.layouts.users.partials._table_evaluators')
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link href="{{ asset('default/datatable.css') }}" rel="stylesheet" />
    <style>
        /* Stat Cards (reduced shadow) */
        .stats-card {
            border: none;
            border-radius: 10px;
            border-left: 4px solid;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.06);
        }

        /* Filter Card */
        .filter-card {
            background: #F9FAFB;
            border: 1px solid #E5E7EB;
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 20px;
        }

        .filter-card .form-label {
            font-weight: 600;
            font-size: 13px;
            color: #374151;
            margin-bottom: 5px;
        }

        .filter-card .form-select,
        .filter-card .form-control {
            border: 1px solid #D1D5DB;
            border-radius: 6px;
            font-size: 13px;
            transition: border-color 0.2s ease;
        }

        .filter-card .form-select:focus,
        .filter-card .form-control:focus {
            border-color: #00AEEF;
            box-shadow: 0 0 0 3px rgba(0, 174, 239, 0.08);
        }

        /* Tab Buttons (monitor-tabs style) */
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
            box-shadow: none;
        }

        .monitor-tabs .btn .badge {
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 8px;
        }

        /* Loading Overlay */
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
            animation: users-loader-spin 0.7s linear infinite;
        }

        @keyframes users-loader-spin {
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

        /* DataTables Controls */
        .dataTables_processing {
            display: none !important;
        }

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

        /* Clickable user name */
        .user-name-link {
            transition: color 0.2s ease, background-color 0.2s ease;
        }

        a:hover .user-name-link {
            color: #00AEEF !important;
        }

        a:hover small.text-muted {
            color: #00AEEF !important;
        }

        /* ─── Subtle CSS Tooltip ─── */
        .user-profile-link {
            position: relative;
        }

        .user-profile-link::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: -6px;
            left: 50%;
            transform: translateX(-50%) translateY(100%) scale(0.9);
            background: rgba(15, 23, 42, 0.9);
            color: #fff;
            font-size: 11px;
            font-weight: 500;
            padding: 4px 10px;
            border-radius: 6px;
            white-space: nowrap;
            letter-spacing: 0.3px;
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            pointer-events: none;
            z-index: 1050;
        }

        .user-profile-link::before {
            content: '';
            position: absolute;
            bottom: -6px;
            left: 50%;
            transform: translateX(-50%) translateY(100%) rotate(45deg);
            width: 6px;
            height: 6px;
            background: rgba(15, 23, 42, 0.9);
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            pointer-events: none;
            z-index: 10;
        }

        .user-profile-link:hover::after {
            opacity: 1;
            visibility: visible;
            transform: translateX(-50%) translateY(100%) scale(1);
        }

        .user-profile-link:hover::before {
            opacity: 1;
            visibility: visible;
            transform: translateX(-50%) translateY(calc(100% - 3px)) rotate(45deg);
        }

        /* Utility */
        .badge-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }

        .text-break {
            word-break: break-word;
        }

        /* Responsive */
        @media (max-width: 767.98px) {
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

            .filter-card .row>div {
                margin-bottom: 8px;
            }
        }
    </style>
@endpush

@push('scripts')
    @include('backend.partials._scripts-datatable')
@endpush

@push('scripts')
    <script>
        // Lazy init tracker
        var tableInits = {
            all: false,
            directors: false,
            referees: false,
            evaluators: false
        };

        // Ellipsis timers
        var ellipsisTimers = {};

        function toggleTableLoader(tableId, show) {
            var $loader = $('#loader-' + tableId);
            if (!$loader.length) return;
            var $text = $loader.find('.table-loader-text');
            if (show) {
                $loader.addClass('show');
                var baseText = $text.data('base') || $text.text().replace(/\.+$/, '');
                $text.data('base', baseText);
                var dots = 0;
                if (ellipsisTimers[tableId]) clearInterval(ellipsisTimers[tableId]);
                ellipsisTimers[tableId] = setInterval(function() {
                    dots = (dots + 1) % 4;
                    $text.text(baseText + '.'.repeat(dots));
                }, 500);
            } else {
                $loader.removeClass('show');
                if (ellipsisTimers[tableId]) {
                    clearInterval(ellipsisTimers[tableId]);
                    delete ellipsisTimers[tableId];
                }
                var base = $text.data('base');
                if (base) $text.text(base + '...');
            }
        }

        // Shared columns config (user, contact, last_active, status, action)
        var sharedColumns = [{
                data: 'DT_RowIndex',
                name: 'DT_RowIndex',
                orderable: false,
                searchable: false
            },
            {
                data: 'full_name',
                name: 'first_name',
                orderable: true,
                searchable: true
            },
            {
                data: 'contact',
                name: 'email',
                orderable: true,
                searchable: true
            },
            {
                data: 'last_active',
                name: 'last_activity_at',
                orderable: true,
                searchable: false
            },
            {
                data: 'status',
                name: 'status',
                orderable: false,
                searchable: false,
                className: 'text-center'
            },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false,
                className: 'dt-center text-center'
            }
        ];

        var tableConfigs = {
            all: {
                dtId: '#dtAll',
                type: 'all',
                columns: [].concat(sharedColumns.slice(0, 3), [{
                    data: 'roles',
                    name: 'roles',
                    orderable: false,
                    searchable: false
                }], sharedColumns.slice(3))
            },
            directors: {
                dtId: '#dtDirectors',
                type: 'directors',
                columns: [].concat(sharedColumns.slice(0, 3), [{
                        data: 'stripe_status',
                        name: 'stripe_status',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'total_revenue',
                        name: 'total_revenue',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'camp_count',
                        name: 'camp_count',
                        orderable: false,
                        searchable: false
                    }
                ], sharedColumns.slice(3))
            },
            referees: {
                dtId: '#dtReferees',
                type: 'referees',
                columns: [].concat(sharedColumns.slice(0, 3), [{
                        data: 'camps',
                        name: 'camps',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'checkins',
                        name: 'checkins',
                        orderable: false,
                        searchable: false
                    }
                ], sharedColumns.slice(3))
            },
            evaluators: {
                dtId: '#dtEvaluators',
                type: 'evaluators',
                columns: [].concat(sharedColumns.slice(0, 3), [{
                    data: 'evaluations_count',
                    name: 'evaluations_count',
                    orderable: false,
                    searchable: false
                }], sharedColumns.slice(3))
            }
        };

        // Lazy init factories
        var initMap = {
            all: initAllTable,
            directors: initDirectorsTable,
            referees: initRefereesTable,
            evaluators: initEvaluatorsTable
        };

        var tabToKey = {
            all: 'all',
            director: 'directors',
            referee: 'referees',
            evaluator: 'evaluators'
        };

        function initAllTable() {
            if (tableInits.all) return;
            var cfg = tableConfigs.all;
            var dt = $(cfg.dtId).DataTable({
                order: [
                    [0, 'desc']
                ],
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "All"]
                ],
                processing: true,
                responsive: true,
                serverSide: true,
                searchDelay: 500,
                pagingType: "full_numbers",
                dom: "<'row'<'col-sm-6'l><'col-sm-6'f>>tipr",
                ajax: {
                    url: "{{ route('admin.users.manage.index') }}",
                    data: {
                        type: 'all',
                        status: function() {
                            return $('#statusFilter').val();
                        },
                        date_from: function() {
                            return $('#dateFrom').val();
                        },
                        date_to: function() {
                            return $('#dateTo').val();
                        }
                    }
                },
                columns: cfg.columns
            });
            dt.on('processing.dt', function(e, s, p) {
                toggleTableLoader('all', p);
            });
            tableInits.all = true;
        }

        function initDirectorsTable() {
            if (tableInits.directors) return;
            var cfg = tableConfigs.directors;
            var dt = $(cfg.dtId).DataTable({
                order: [
                    [0, 'desc']
                ],
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "All"]
                ],
                processing: true,
                responsive: true,
                serverSide: true,
                searchDelay: 500,
                pagingType: "full_numbers",
                dom: "<'row'<'col-sm-6'l><'col-sm-6'f>>tipr",
                ajax: {
                    url: "{{ route('admin.users.manage.index') }}",
                    data: {
                        type: 'directors',
                        status: function() {
                            return $('#statusFilter').val();
                        },
                        date_from: function() {
                            return $('#dateFrom').val();
                        },
                        date_to: function() {
                            return $('#dateTo').val();
                        }
                    }
                },
                columns: cfg.columns
            });
            dt.on('processing.dt', function(e, s, p) {
                toggleTableLoader('directors', p);
            });
            tableInits.directors = true;
        }

        function initRefereesTable() {
            if (tableInits.referees) return;
            var cfg = tableConfigs.referees;
            var dt = $(cfg.dtId).DataTable({
                order: [
                    [0, 'desc']
                ],
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "All"]
                ],
                processing: true,
                responsive: true,
                serverSide: true,
                searchDelay: 500,
                pagingType: "full_numbers",
                dom: "<'row'<'col-sm-6'l><'col-sm-6'f>>tipr",
                ajax: {
                    url: "{{ route('admin.users.manage.index') }}",
                    data: {
                        type: 'referees',
                        status: function() {
                            return $('#statusFilter').val();
                        },
                        date_from: function() {
                            return $('#dateFrom').val();
                        },
                        date_to: function() {
                            return $('#dateTo').val();
                        }
                    }
                },
                columns: cfg.columns
            });
            dt.on('processing.dt', function(e, s, p) {
                toggleTableLoader('referees', p);
            });
            tableInits.referees = true;
        }

        function initEvaluatorsTable() {
            if (tableInits.evaluators) return;
            var cfg = tableConfigs.evaluators;
            var dt = $(cfg.dtId).DataTable({
                order: [
                    [0, 'desc']
                ],
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "All"]
                ],
                processing: true,
                responsive: true,
                serverSide: true,
                searchDelay: 500,
                pagingType: "full_numbers",
                dom: "<'row'<'col-sm-6'l><'col-sm-6'f>>tipr",
                ajax: {
                    url: "{{ route('admin.users.manage.index') }}",
                    data: {
                        type: 'evaluators',
                        status: function() {
                            return $('#statusFilter').val();
                        },
                        date_from: function() {
                            return $('#dateFrom').val();
                        },
                        date_to: function() {
                            return $('#dateTo').val();
                        }
                    }
                },
                columns: cfg.columns
            });
            dt.on('processing.dt', function(e, s, p) {
                toggleTableLoader('evaluators', p);
            });
            tableInits.evaluators = true;
        }

        // Reload all initialized DataTables
        function reloadAllTables() {
            $.fn.DataTable.tables({
                api: true
            }).iterator('table', function() {
                this.ajax.reload(null, false);
            });
        }

        // Switch Tab (show/hide + lazy init + query string)
        function switchTab(role) {
            var key = tabToKey[role] || 'all';

            // Update tab buttons
            $('.monitor-tabs .btn').removeClass('active');
            $('.monitor-tabs .btn[data-role="' + role + '"]').addClass('active');

            // Show/hide tables
            $('[id^="table-"]').hide();
            $('#table-' + key).show();

            // Update title
            var titles = {
                all: 'All Users',
                directors: 'Directors',
                referees: 'Referees',
                evaluators: 'Evaluators'
            };
            document.getElementById('tableTitle').textContent = titles[key] || 'Users';

            // Lazy init
            if (!tableInits[key]) {
                initMap[key]();
            }

            // Responsive fix
            var cfg = tableConfigs[key];
            if ($.fn.DataTable.isDataTable(cfg.dtId)) {
                setTimeout(function() {
                    $(cfg.dtId).DataTable().columns.adjust().responsive.recalc();
                }, 50);
            }

            // URL query string
            var url = new URL(window.location);
            if (role === 'all') {
                url.searchParams.delete('role');
            } else {
                url.searchParams.set('role', role);
            }
            history.replaceState(null, '', url);
        }

        // Filter controls
        function applyFilters() {
            reloadAllTables();
        }

        function resetFilters() {
            $('#statusFilter').val('');
            $('#dateFrom').val('');
            $('#dateTo').val('');
            reloadAllTables();
        }

        // Status Toggle
        function showStatusChangeAlert(id) {
            event.preventDefault();
            Swal.fire({
                title: 'Are you sure?',
                text: 'You want to update the user status?',
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'Yes, update it!',
                cancelButtonText: 'Cancel',
            }).then((result) => {
                if (result.isConfirmed) statusChange(id);
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
                        reloadAllTables();
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

        // Delete
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
                if (result.isConfirmed) deleteUser(id);
            });
        }

        function deleteUser(id) {
            NProgress.start();
            let url = "{{ route('admin.users.manage.destroy', ':id') }}";
            $.ajax({
                type: "DELETE",
                url: url.replace(':id', id),
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(resp) {
                    NProgress.done();
                    if (resp.success) {
                        toastr.success(resp.message);
                        reloadAllTables();
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

        // ── View ──
        function goToView(id) {
            let url = "{{ route('admin.users.manage.show', ':id') }}";
            window.location.href = url.replace(':id', id);
        }

        // ── Export ──
        function exportUsers() {
            Swal.fire({
                title: 'Export Users',
                html: `
            <div class="text-start">
                <div class="mb-3">
                    <label class="form-label">Export Format</label>
                    <select class="form-select" id="exportFormat">
                        <option value="csv">CSV</option>
                        <option value="excel">Excel (XLSX)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Select Fields to Export</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="exportAll" checked>
                        <label class="form-check-label" for="exportAll"><strong>Select All</strong></label>
                    </div>
                    <hr>
                    <div class="form-check">
                        <input class="form-check-input export-field" type="checkbox" value="name" checked>
                        <label class="form-check-label">Name</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input export-field" type="checkbox" value="username" checked>
                        <label class="form-check-label">Username</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input export-field" type="checkbox" value="email" checked>
                        <label class="form-check-label">Email</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input export-field" type="checkbox" value="phone" checked>
                        <label class="form-check-label">Phone</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input export-field" type="checkbox" value="roles" checked>
                        <label class="form-check-label">Roles</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input export-field" type="checkbox" value="status" checked>
                        <label class="form-check-label">Status</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input export-field" type="checkbox" value="created_at" checked>
                        <label class="form-check-label">Created Date</label>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="applyCurrentFilters">
                        <label class="form-check-label" for="applyCurrentFilters">Apply current filters</label>
                    </div>
                </div>
            </div>`,
                showCancelButton: true,
                confirmButtonText: '<i class="fe fe-download me-1"></i> Export',
                cancelButtonText: 'Cancel',
                width: '500px',
                didOpen: () => {
                    document.getElementById('exportAll').addEventListener('change', function() {
                        document.querySelectorAll('.export-field').forEach(cb => cb.checked = this
                            .checked);
                    });
                    document.querySelectorAll('.export-field').forEach(cb => {
                        cb.addEventListener('change', function() {
                            document.getElementById('exportAll').checked =
                                Array.from(document.querySelectorAll('.export-field')).every(
                                    c => c.checked);
                        });
                    });
                },
                preConfirm: () => {
                    const format = document.getElementById('exportFormat').value;
                    const fields = Array.from(document.querySelectorAll('.export-field:checked')).map(cb => cb
                        .value);
                    const applyFilters = document.getElementById('applyCurrentFilters').checked;
                    if (fields.length === 0) {
                        Swal.showValidationMessage('Please select at least one field');
                        return false;
                    }
                    return {
                        format,
                        fields,
                        applyFilters
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) processExport(result.value);
            });
        }

        function processExport(options) {
            Swal.fire({
                title: 'Exporting...',
                html: `<div class="text-center">
                <img src="{{ asset('default/loader.gif') }}" alt="Loading" style="width: 80px;">
                <p class="mt-3">Please wait...</p>
            </div>`,
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => NProgress.start()
            });

            $.ajax({
                url: "{{ route('admin.users.manage.export') }}",
                type: "POST",
                data: {
                    format: options.format,
                    fields: options.fields,
                    role: options.applyFilters ? currentRole === 'all' ? '' : currentRole : '',
                    status: options.applyFilters ? $('#statusFilter').val() : '',
                    date_from: options.applyFilters ? $('#dateFrom').val() : '',
                    date_to: options.applyFilters ? $('#dateTo').val() : '',
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                xhrFields: {
                    responseType: 'blob'
                },
                success: function(blob, status, xhr) {
                    NProgress.done();
                    Swal.close();
                    const disposition = xhr.getResponseHeader('Content-Disposition');
                    let filename = 'users_export.' + options.format;
                    if (disposition && disposition.indexOf('filename=') !== -1) {
                        const m = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(disposition);
                        if (m) filename = m[1].replace(/['"]/g, '');
                    }
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    toastr.success('File downloaded successfully!');
                },
                error: function(xhr) {
                    NProgress.done();
                    Swal.close();
                    if (xhr.status === 404) toastr.error('No users found to export!');
                    else if (xhr.status === 422) toastr.error('Invalid export parameters!');
                    else toastr.error('Failed to export users.');
                }
            });
        }

        // ─── Document Ready ───
        $(document).ready(function() {
            $.ajaxSetup({
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                }
            });

            // Read role from query string
            var urlParams = new URLSearchParams(window.location.search);
            var validRoles = ['all', 'director', 'referee', 'evaluator'];
            var urlRole = urlParams.get('role') || 'all';
            if (validRoles.indexOf(urlRole) === -1) urlRole = 'all';
            currentRole = urlRole;

            // Set active tab button
            $('.monitor-tabs .btn[data-role="' + currentRole + '"]').addClass('active');

            // Activate initial tab (lazy inits the table)
            switchTab(currentRole);

            // Tab click handler
            $('.monitor-tabs .btn').on('click', function() {
                switchTab($(this).data('role'));
            });
        });
    </script>
@endpush
