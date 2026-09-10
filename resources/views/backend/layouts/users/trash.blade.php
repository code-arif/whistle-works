@extends('backend.app', ['title' => 'Deleted Users'])

@section('content')
    <div class="app-content main-content mt-0">
        <div class="side-app" style="margin-bottom: 50px">
            <div class="main-container container-fluid">

                <!-- PAGE-HEADER -->
                <div class="page-header d-flex flex-wrap align-items-center justify-content-between">
                    <div>
                        <h1 class="page-title">Deleted Users</h1>
                        <p class="text-muted mb-0" style="font-size: 13px;">Manage deleted user accounts — restore or permanently remove them</p>
                    </div>
                    <ol class="breadcrumb mb-0 py-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.users.manage.index') }}">Users</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Trash</li>
                    </ol>
                </div>
                <!-- PAGE-HEADER END -->

                {{-- STATISTICS ROW --}}
                <div class="row mb-4 g-3">
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                        <div class="card stats-card" style="border-left-color: #dc3545;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Deleted Users</h6>
                                        <h3 class="mb-0">{{ $trashCount }}</h3>
                                    </div>
                                    <div class="icon-service bg-danger-transparent text-danger p-3 rounded-3">
                                        <i class="fe fe-trash-2 fs-20"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-9 col-lg-6 col-md-6 col-sm-12 d-flex align-items-end justify-content-end">
                        <a href="{{ route('admin.users.manage.index') }}" class="btn btn-outline-primary d-inline-flex align-items-center gap-1">
                            <i class="fe fe-arrow-left"></i>
                            <span>Back to Users</span>
                        </a>
                    </div>
                </div>

                {{-- TRASH TABLE --}}
                <div class="row">
                    <div class="col-12 col-sm-12">
                        <div class="card product-sales-main">
                            <div class="card-header border-bottom">
                                <h3 class="card-title mb-0">Deleted User Accounts</h3>
                            </div>
                            <div class="card-body">
                                @include('backend.layouts.users.partials._table_trash')
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
        .stats-card {
            border: none;
            border-radius: 10px;
            border-left: 4px solid;
            transition: all 0.2s ease;
        }
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.06);
        }
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
            to { transform: rotate(360deg); }
        }
        .table-loader-text {
            font-size: 13px;
            font-weight: 500;
            color: #6B7280;
            letter-spacing: 0.3px;
        }
        .dataTables_processing { display: none !important; }
        .dataTables_filter { text-align: right; }
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
        .dataTables_length select:focus { border-color: #00AEEF; }
        .dataTables_info { font-size: 13px; color: #6B7280; padding-top: 8px; }
        .dataTables_paginate { padding-top: 8px; }
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
    </style>
@endpush

@push('scripts')
    @include('backend.partials._scripts-datatable')
@endpush

@push('scripts')
    <script>
        var ellipsisTimer = null;

        function toggleTrashLoader(show) {
            var $loader = $('#loader-trash');
            if (!$loader.length) return;
            var $text = $loader.find('.table-loader-text');
            if (show) {
                $loader.addClass('show');
                var baseText = $text.data('base') || $text.text().replace(/\.+$/, '');
                $text.data('base', baseText);
                var dots = 0;
                if (ellipsisTimer) clearInterval(ellipsisTimer);
                ellipsisTimer = setInterval(function() {
                    dots = (dots + 1) % 4;
                    $text.text(baseText + '.'.repeat(dots));
                }, 500);
            } else {
                $loader.removeClass('show');
                if (ellipsisTimer) {
                    clearInterval(ellipsisTimer);
                    ellipsisTimer = null;
                }
                var base = $text.data('base');
                if (base) $text.text(base + '...');
            }
        }

        // Restore confirmation
        function showRestoreAlert(id) {
            event.preventDefault();
            Swal.fire({
                title: 'Restore User?',
                text: 'This user will be restored with all their data.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fe fe-refresh-cw me-1"></i> Yes, restore!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) restoreUser(id);
            });
        }

        function restoreUser(id) {
            NProgress.start();
            let url = "{{ route('admin.users.manage.restore', ':id') }}";
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
                        $('#dtTrash').DataTable().ajax.reload(null, false);
                    } else {
                        toastr.error(resp.message);
                    }
                },
                error: function(error) {
                    NProgress.done();
                    toastr.error(error.responseJSON?.message || 'Failed to restore user!');
                }
            });
        }

        // Force delete confirmation
        function showForceDeleteAlert(id) {
            event.preventDefault();
            Swal.fire({
                title: 'Permanently Delete?',
                html: 'This action <strong>cannot be undone</strong>! All user data will be permanently removed.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fe fe-trash-2 me-1"></i> Yes, delete permanently!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) forceDeleteUser(id);
            });
        }

        function forceDeleteUser(id) {
            NProgress.start();
            let url = "{{ route('admin.users.manage.forceDelete', ':id') }}";
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
                        $('#dtTrash').DataTable().ajax.reload(null, false);
                    } else {
                        toastr.error(resp.message);
                    }
                },
                error: function(error) {
                    NProgress.done();
                    toastr.error(error.responseJSON?.message || 'Failed to permanently delete user!');
                }
            });
        }

        $(document).ready(function() {
            $.ajaxSetup({
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                }
            });

            $('#dtTrash').DataTable({
                order: [[4, 'desc']],
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                processing: true,
                responsive: true,
                serverSide: true,
                searchDelay: 500,
                pagingType: "full_numbers",
                dom: "<'row'<'col-sm-6'l><'col-sm-6'f>>tipr",
                ajax: {
                    url: "{{ route('admin.users.manage.trash') }}"
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'full_name', name: 'first_name', orderable: true, searchable: true },
                    { data: 'contact', name: 'email', orderable: true, searchable: true },
                    { data: 'roles', name: 'roles', orderable: false, searchable: false },
                    { data: 'deleted_at', name: 'deleted_at', orderable: true, searchable: false },
                    { data: 'action', name: 'action', orderable: false, searchable: false, className: 'dt-center text-center' }
                ]
            });
        });
    </script>
@endpush
