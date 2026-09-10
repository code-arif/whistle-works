@extends('backend.app', ['title' => 'Coupons'])

@section('content')
    <!--app-content open-->
    <div class="app-content main-content mt-0">
        <div class="side-app">

            <!-- CONTAINER -->
            <div class="main-container container-fluid">

                <!-- PAGE-HEADER -->
                <div class="page-header d-flex flex-wrap align-items-center justify-content-between">
                    <div>
                        <h1 class="page-title">Coupons</h1>
                        <p class="text-muted mb-0" style="font-size: 13px;">Manage discount coupons and promotional codes</p>
                    </div>
                    <ol class="breadcrumb mb-0 py-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.coupon.index') }}">Coupons</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Index</li>
                    </ol>
                </div>
                <!-- PAGE-HEADER END -->

                <!-- ROW -->
                <div class="row">
                    <div class="col-12 col-sm-12">
                        <div class="card product-sales-main">
                            <div class="card-header border-bottom">
                                <h3 class="card-title mb-0">Coupon List</h3>
                                <div class="card-options ms-auto">
                                    <button class="btn btn-primary btn-sm d-inline-flex align-items-center"
                                        onclick="openCreateModal()">
                                        <i class="fe fe-plus me-1"></i>
                                        <span>Add Coupon</span>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="">
                                    <table class="table table-bordered text-nowrap border-bottom" id="datatable">
                                        <thead>
                                            <tr>
                                                <th class="bg-transparent border-bottom-0 wp-15">ID</th>
                                                <th class="bg-transparent border-bottom-0">Code</th>
                                                <th class="bg-transparent border-bottom-0">Discount</th>
                                                <th class="bg-transparent border-bottom-0">Usage</th>
                                                <th class="bg-transparent border-bottom-0">Scope</th>
                                                <th class="bg-transparent border-bottom-0">Expires</th>
                                                <th class="bg-transparent border-bottom-0">Status</th>
                                                <th class="bg-transparent border-bottom-0">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div><!-- COL END -->
                </div>
                <!-- ROW END -->

            </div>
        </div>
    </div>
    <!-- CONTAINER CLOSED -->

    {{-- =================================================================== --}}
    {{-- COUPON MODAL (Create / Edit) — Upgraded Pro UI                     --}}
    {{-- =================================================================== --}}
    <div class="modal fade" id="couponModal" tabindex="-1" aria-labelledby="couponModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                {{-- Header --}}
                <div class="modal-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="modal-icon">
                            <i class="fe fe-tag"></i>
                        </div>
                        <div>
                            <h5 class="modal-title" id="couponModalLabel">Create Coupon</h5>
                            <p class="modal-subtitle mb-0" id="couponModalSubtitle">Fill in the details below</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">&times;</button>
                </div>

                <form id="couponForm">
                    @csrf
                    <input type="hidden" name="id" id="couponId" value="">

                    <div class="modal-body p-0">
                        {{-- Tabs --}}
                        <ul class="modal-tabs" id="couponTabs" role="tablist">
                            <li class="modal-tab active" data-tab="basic">
                                <i class="fe fe-info"></i>
                                <span>Basic Info</span>
                            </li>
                            <li class="modal-tab" data-tab="scope">
                                <i class="fe fe-shield"></i>
                                <span>Scope &amp; Limits</span>
                            </li>
                        </ul>

                        {{-- ───── TAB 1: BASIC INFO ───── --}}
                        <div class="tab-content" id="tab-basic">
                            <div class="p-4">
                                {{-- Coupon Code Row --}}
                                <div class="row g-3 mb-3">
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label for="code" class="form-label">
                                                Coupon Code <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fe fe-hash"></i></span>
                                                <input type="text" class="form-control" name="code" id="code"
                                                    placeholder="e.g. SUMMER2024" maxlength="50" required
                                                    oninput="updateCodePreview()">
                                                <button type="button" class="btn btn-outline-secondary" onclick="generateCode()"
                                                    title="Generate random code">
                                                    <i class="fe fe-refresh-cw"></i>
                                                </button>
                                            </div>
                                            <div class="invalid-feedback code-feedback"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end">
                                        <div class="code-preview" id="codePreview">
                                            <span class="code-badge">NEW</span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Discount Row --}}
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="type" class="form-label">
                                                Discount Type <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select" name="type" id="type" required
                                                onchange="updateDiscountPreview()">
                                                <option value="percentage">Percentage (%)</option>
                                                <option value="fixed">Fixed Amount ($)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="discount_value" class="form-label">
                                                Discount Value <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text" id="discount-symbol">%</span>
                                                <input type="number" step="0.01" min="0" class="form-control"
                                                    name="discount_value" id="discount_value" value="0" required
                                                    oninput="updateDiscountPreview()">
                                            </div>
                                            <div class="invalid-feedback value-feedback"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="discount-preview-box" id="discountPreview">
                                            <div class="preview-label">Discount Preview</div>
                                            <div class="preview-value" id="discountPreviewValue">$0.00 off</div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Status Toggle --}}
                                <div class="row g-3 mt-3">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                            <select class="form-select" name="status" id="status" required>
                                                <option value="active">Active</option>
                                                <option value="inactive">Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="max_uses" class="form-label">Max Uses</label>
                                            <input type="number" min="1" class="form-control" name="max_uses"
                                                id="max_uses" placeholder="Unlimited">
                                            <small class="text-muted">Leave empty for unlimited</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="expires_at" class="form-label">Expiry Date</label>
                                            <input type="date" class="form-control" name="expires_at" id="expires_at">
                                            <small class="text-muted">Leave empty if never expires</small>
                                        </div>
                                    </div>
                                </div>

                                {{-- Used Count (edit mode) --}}
                                <div class="row g-3 mt-3" id="usedCountRow" style="display: none;">
                                    <div class="col-12">
                                        <div class="used-count-card">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="used-count-label">Usage Progress</span>
                                                <span class="used-count-text" id="usedCountDisplay">0 / ∞</span>
                                            </div>
                                            <div class="used-count-bar">
                                                <div class="used-count-fill" id="usedCountFill" style="width: 0%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ───── TAB 2: SCOPE & LIMITS ───── --}}
                        <div class="tab-content" id="tab-scope" style="display: none;">
                            <div class="p-4">
                                <div class="scope-card">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="scope-title-icon">
                                            <i class="fe fe-target"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0" style="font-size: 14px; font-weight: 600;">Coupon Scope</h6>
                                            <small class="text-muted">Leave all empty for a global coupon</small>
                                        </div>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="camp_id" class="form-label">
                                                    <i class="fe fe-map-pin me-1"></i> Specific Camp
                                                </label>
                                                <select class="form-select" name="camp_id" id="camp_id">
                                                    <option value="">-- All Camps --</option>
                                                    @foreach ($camps as $camp)
                                                        <option value="{{ $camp->id }}">{{ $camp->camp_name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="referee_ids" class="form-label">
                                                    <i class="fe fe-users me-1"></i> Specific Referees
                                                </label>
                                                <select class="form-select" name="referee_ids[]" id="referee_ids"
                                                    multiple="multiple" style="width: 100%;">
                                                    @foreach ($referees as $referee)
                                                        <option value="{{ $referee->id }}" data-avatar="{{ $referee->avatar ? asset($referee->avatar) : asset('default/profile.jpg') }}">
                                                            {{ $referee->first_name }} {{ $referee->last_name }}
                                                            ({{ $referee->email }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <small class="text-muted">Search and select referees. Leave empty for all referees.</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Quick Summary --}}
                                <div class="summary-card mt-3" id="scopeSummary">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fe fe-info text-primary"></i>
                                        <span id="scopeSummaryText">This coupon will be available to all referees for all camps.</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="modal-footer">
                        <div class="d-flex align-items-center gap-2 me-auto">
                            <span class="text-muted" style="font-size: 12px;">
                                <i class="fe fe-corner-down-left"></i> Ctrl+Enter to submit
                            </span>
                        </div>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="modalSubmitBtn">
                            <i class="fe fe-save me-1"></i> <span id="modalSubmitText">Create Coupon</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @include('backend.partials._scripts-datatable')
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            $.ajaxSetup({
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                }
            });

            // ─── Init DataTable ───
            if (!$.fn.DataTable.isDataTable('#datatable')) {
                let dTable = $('#datatable').DataTable({
                    order: [],
                    lengthMenu: [
                        [10, 25, 50, 100, -1],
                        [10, 25, 50, 100, "All"]
                    ],
                    processing: true,
                    responsive: true,
                    serverSide: true,

                    language: {
                        processing: `<div class="text-center">
                        <img src="{{ asset('default/loader.gif') }}" alt="Loader" style="width: 50px;">
                        </div>`
                    },

                    scroller: {
                        loadingIndicator: false
                    },
                    pagingType: "full_numbers",
                    dom: "<'row justify-content-between table-topbar'<'col-md-6 col-sm-6'l><'col-md-6 col-sm-6'f>>tipr",
                    ajax: {
                        url: "{{ route('admin.coupon.index') }}",
                        type: "GET",
                    },

                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'code',
                            name: 'code',
                            orderable: true,
                            searchable: true
                        },
                        {
                            data: 'type',
                            name: 'type',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'usage',
                            name: 'usage',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'scope',
                            name: 'scope',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'expires_at',
                            name: 'expires_at',
                            orderable: true,
                            searchable: false
                        },
                        {
                            data: 'status',
                            name: 'status',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'action',
                            name: 'action',
                            orderable: false,
                            searchable: false,
                            className: 'dt-center text-center'
                        },
                    ],
                });
            }

            // ─── Init Select2 for referee multi-select (with search & custom template) ───
            $('#referee_ids').select2({
                placeholder: 'Search referees...',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#couponModal'),
                closeOnSelect: false,
                templateResult: formatRefereeOption,
                templateSelection: formatRefereeSelection,
                escapeMarkup: function(m) { return m; },
                language: {
                    noResults: function() { return 'No referees found'; },
                    searching: function() { return 'Searching...'; },
                },
            });

            function formatRefereeOption(option) {
                if (!option.id) return option.text;
                const avatar = $(option.element).data('avatar') || '{{ asset("default/profile.jpg") }}';
                const name = option.text.split(' (')[0];
                const email = option.text.split(' (')[1]?.replace(')', '') || '';
                return $(
                    `<div class="d-flex align-items-center gap-2 py-1">
                        <img src="${avatar}" alt="" width="28" height="28" class="rounded-circle" style="object-fit: cover;">
                        <div>
                            <div class="fw-medium" style="font-size: 13px;">${name}</div>
                            <small class="text-muted">${email}</small>
                        </div>
                    </div>`
                );
            }

            function formatRefereeSelection(option) {
                if (!option.id) return option.text;
                return option.text.split(' (')[0];
            }

            // ─── Tab Switching ───
            $('.modal-tab').on('click', function() {
                const tab = $(this).data('tab');
                $('.modal-tab').removeClass('active');
                $(this).addClass('active');
                $('.tab-content').hide();
                $('#tab-' + tab).fadeIn(200);
            });

            // ─── Scope Summary Updater ───
            $('#camp_id, #referee_ids').on('change', updateScopeSummary);

            // ─── Discount type toggle ───
            $('#type').on('change', function() {
                const symbol = $('#discount-symbol');
                symbol.text(this.value === 'percentage' ? '%' : '$');
                updateDiscountPreview();
            });

            // ─── Modal hidden reset ───
            $('#couponModal').on('hidden.bs.modal', function() {
                $('#couponForm')[0].reset();
                $('#couponForm').removeClass('was-validated');
                $('.invalid-feedback').hide();
                $('.is-invalid').removeClass('is-invalid');
                $('#couponId').val('');
                $('#usedCountRow').hide();
                $('#discount_value').val(0);
                $('#type').val('percentage').trigger('change');
                $('#status').val('active');
                $('#referee_ids').val(null).trigger('change');
                $('#codePreview').html('<span class="code-badge badge-new">NEW</span>');
                updateDiscountPreview();
                updateScopeSummary();
                // Reset to tab 1
                $('.modal-tab').removeClass('active');
                $('.modal-tab[data-tab="basic"]').addClass('active');
                $('.tab-content').hide();
                $('#tab-basic').show();
            });

            // ─── Keyboard shortcut: Ctrl+Enter to submit ───
            $(document).on('keydown', '#couponForm input, #couponForm select, #couponForm textarea', function(e) {
                if (e.ctrlKey && e.key === 'Enter') {
                    e.preventDefault();
                    $('#couponForm').submit();
                }
            });

            // ─── Autogrow text (not needed for inputs, but good for UX) ───
            updateCodePreview();
            updateDiscountPreview();
            updateScopeSummary();
        });

        // ════════════════════════════════════════════════════════════════
        // SCOPE SUMMARY (global — called from openCreateModal/openEditModal)
        // ════════════════════════════════════════════════════════════════
        function updateScopeSummary() {
            const campId = $('#camp_id').val();
            const refereeIds = $('#referee_ids').val();
            const $summary = $('#scopeSummaryText');

            const parts = [];
            if (campId) {
                const campName = $('#camp_id option:selected').text();
                parts.push(`camp <strong>${campName}</strong>`);
            }
            if (refereeIds && refereeIds.length > 0) {
                parts.push(`<strong>${refereeIds.length}</strong> specific referee(s)`);
            }

            if (parts.length === 0) {
                $summary.html('This coupon will be available to <strong>all referees</strong> for <strong>all camps</strong>.');
            } else {
                $summary.html('This coupon is scoped to: ' + parts.join(' and ') + '.');
            }
        }

        // ════════════════════════════════════════════════════════════════
        // AUTO-GENERATE COUPON CODE
        // ════════════════════════════════════════════════════════════════
        function generateCode() {
            const prefix = 'CPN';
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let code = prefix;
            for (let i = 0; i < 7; i++) {
                code += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            $('#code').val(code);
            updateCodePreview();
        }

        // ════════════════════════════════════════════════════════════════
        // LIVE CODE PREVIEW
        // ════════════════════════════════════════════════════════════════
        function updateCodePreview() {
            const code = $('#code').val().trim();
            const $preview = $('#codePreview');
            if (code) {
                // Determine badge color
                const isEdit = !!$('#couponId').val();
                const badgeText = isEdit ? 'EDIT' : 'NEW';
                $preview.html(`
                    <div class="code-preview-badge">
                        <span class="code-badge ${isEdit ? 'badge-edit' : 'badge-new'}">${badgeText}</span>
                        <span class="code-text">${code.toUpperCase()}</span>
                    </div>
                `);
            } else {
                $preview.html('<span class="code-badge badge-new">NEW</span>');
            }
        }

        // ════════════════════════════════════════════════════════════════
        // LIVE DISCOUNT PREVIEW
        // ════════════════════════════════════════════════════════════════
        function updateDiscountPreview() {
            const type = $('#type').val();
            const value = parseFloat($('#discount_value').val()) || 0;
            const $preview = $('#discountPreviewValue');

            if (type === 'percentage') {
                $preview.text(`${value}% off`);
            } else {
                $preview.text(`$${value.toFixed(2)} off`);
            }
        }

        // ════════════════════════════════════════════════════════════════
        // OPEN MODAL: CREATE
        // ════════════════════════════════════════════════════════════════
        function openCreateModal() {
            $('#couponForm')[0].reset();
            $('#couponForm').removeClass('was-validated');
            $('.invalid-feedback').hide();
            $('.is-invalid').removeClass('is-invalid');
            $('#couponId').val('');
            $('#usedCountRow').hide();
            $('#type').val('percentage').trigger('change');
            $('#status').val('active');
            $('#discount_value').val(0);
            $('#modalSubmitText').text('Create Coupon');
            $('#couponModalLabel').text('Create Coupon');
            $('#couponModalSubtitle').text('Fill in the details below');
            $('#referee_ids').val(null).trigger('change');
            updateCodePreview();
            updateDiscountPreview();
            updateScopeSummary();
            // Reset tab
            $('.modal-tab').removeClass('active');
            $('.modal-tab[data-tab="basic"]').addClass('active');
            $('.tab-content').hide();
            $('#tab-basic').show();
            $('#couponModal').modal('show');
        }

        // ════════════════════════════════════════════════════════════════
        // OPEN MODAL: EDIT
        // ════════════════════════════════════════════════════════════════
        function openEditModal(id) {
            NProgress.start();

            $.ajax({
                url: "{{ route('admin.coupon.getCoupon', ':id') }}".replace(':id', id),
                type: "GET",
                success: function(response) {
                    NProgress.done();
                    if (response.success) {
                        const d = response.data;

                        $('#couponId').val(d.id);
                        $('#code').val(d.code);
                        $('#type').val(d.type).trigger('change');
                        $('#discount_value').val(d.discount_value);
                        $('#max_uses').val(d.max_uses);
                        $('#status').val(d.status);
                        $('#expires_at').val(d.expires_at);
                        $('#camp_id').val(d.camp_id);
                        $('#referee_ids').val(d.referee_ids).trigger('change');

                        // Show used count
                        const hasMax = d.max_uses !== null && d.max_uses > 0;
                        const maxDisplay = hasMax ? d.max_uses : '∞';
                        const pct = hasMax ? Math.min(100, (d.used_count / d.max_uses) * 100) : 0;
                        $('#usedCountDisplay').text(d.used_count + ' / ' + maxDisplay);
                        $('#usedCountFill').css('width', pct + '%');
                        if (pct >= 90) {
                            $('#usedCountFill').css('background', '#EF4444');
                        } else if (pct >= 70) {
                            $('#usedCountFill').css('background', '#F59E0B');
                        } else {
                            $('#usedCountFill').css('background', '#10B981');
                        }
                        $('#usedCountRow').show();

                        updateCodePreview();
                        updateDiscountPreview();
                        updateScopeSummary();

                        $('#modalSubmitText').text('Update Coupon');
                        $('#couponModalLabel').text('Edit Coupon');
                        $('#couponModalSubtitle').text('Editing: ' + d.code);
                        $('#couponModal').modal('show');
                    } else {
                        toastr.error(response.message || 'Failed to load coupon data.');
                    }
                },
                error: function() {
                    NProgress.done();
                    toastr.error('Failed to load coupon data.');
                }
            });
        }

        // ════════════════════════════════════════════════════════════════
        // FORM SUBMIT (Create / Update)
        // ════════════════════════════════════════════════════════════════
        $('#couponForm').on('submit', function(e) {
            e.preventDefault();

            const id = $('#couponId').val();
            const isEdit = !!id;
            const url = isEdit ?
                "{{ route('admin.coupon.update', ':id') }}".replace(':id', id) :
                "{{ route('admin.coupon.store') }}";

            const formData = $(this).serialize();

            // Disable and show loading state
            NProgress.start();
            $('#modalSubmitBtn').prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Saving...'
            );

            $.ajax({
                type: "POST",
                url: url,
                data: formData,
                success: function(response) {
                    NProgress.done();
                    $('#modalSubmitBtn').prop('disabled', false).html(
                        '<i class="fe fe-save me-1"></i> <span id="modalSubmitText">' +
                        (isEdit ? 'Update Coupon' : 'Create Coupon') + '</span>'
                    );
                    toastr.success(response.message);
                    $('#couponModal').modal('hide');
                    $('#datatable').DataTable().ajax.reload();
                },
                error: function(xhr) {
                    NProgress.done();
                    $('#modalSubmitBtn').prop('disabled', false).html(
                        '<i class="fe fe-save me-1"></i> <span id="modalSubmitText">' +
                        (isEdit ? 'Update Coupon' : 'Create Coupon') + '</span>'
                    );

                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        const errors = xhr.responseJSON.errors;
                        $('.is-invalid').removeClass('is-invalid');
                        $('.invalid-feedback').hide().text('');

                        $.each(errors, function(field, messages) {
                            const input = $('[name="' + field + '"], [name="' + field + '[]"]');
                            if (input.length) {
                                input.addClass('is-invalid');
                                const feedback = input.siblings('.invalid-feedback');
                                if (feedback.length) {
                                    feedback.text(messages[0]).show();
                                } else {
                                    toastr.error(field + ': ' + messages[0]);
                                }
                            } else {
                                toastr.error(messages[0]);
                            }
                        });
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        toastr.error(xhr.responseJSON.message);
                    } else {
                        toastr.error('Something went wrong. Please try again.');
                    }
                }
            });
        });

        // ════════════════════════════════════════════════════════════════
        // STATUS TOGGLE
        // ════════════════════════════════════════════════════════════════
        function showStatusChangeAlert(id) {
            event.preventDefault();

            Swal.fire({
                title: 'Are you sure?',
                text: 'You want to update the status?',
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'Yes',
                cancelButtonText: 'No',
            }).then((result) => {
                if (result.isConfirmed) {
                    statusChange(id);
                }
            });
        }

        function statusChange(id) {
            NProgress.start();
            let url = "{{ route('admin.coupon.status', ':id') }}";
            $.ajax({
                type: "GET",
                url: url.replace(':id', id),
                success: function(resp) {
                    NProgress.done();
                    toastr.success(resp.message);
                    $('#datatable').DataTable().ajax.reload();
                },
                error: function(error) {
                    NProgress.done();
                    toastr.error(error.message);
                }
            });
        }

        // ════════════════════════════════════════════════════════════════
        // DELETE
        // ════════════════════════════════════════════════════════════════
        function showDeleteConfirm(id) {
            event.preventDefault();
            Swal.fire({
                title: 'Are you sure you want to delete this record?',
                text: 'If you delete this, it will be gone forever.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!',
            }).then((result) => {
                if (result.isConfirmed) {
                    deleteItem(id);
                }
            });
        }

        function deleteItem(id) {
            NProgress.start();
            let url = "{{ route('admin.coupon.destroy', ':id') }}";
            let csrfToken = '{{ csrf_token() }}';
            $.ajax({
                type: "DELETE",
                url: url.replace(':id', id),
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                success: function(resp) {
                    NProgress.done();
                    toastr.success(resp.message);
                    $('#datatable').DataTable().ajax.reload();
                },
                error: function(error) {
                    NProgress.done();
                    toastr.error(error.message);
                }
            });
        }
    </script>
@endpush

@push('styles')
    <link href="{{ asset('default/datatable.css') }}" rel="stylesheet" />
    <style>
        /* ═══════════════════════════════════════════════════════════════
           PRO COUPON MODAL — Complete UI Upgrade
           ═══════════════════════════════════════════════════════════════ */

        /* ─── Modal Container ─── */
        .modal-content {
            border: none;
            border-radius: 10px;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        /* ─── Header ─── */
        .modal-header {
            background: linear-gradient(118deg, #00AEEF, #0095CC);
            color: #fff;
            border-radius: 10px 10px 0 0;
            padding: 18px 24px;
            border-bottom: none;
        }

        .modal-header .modal-icon {
            width: 38px;
            height: 38px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #fff;
        }

        .modal-header .modal-title {
            font-weight: 600;
            font-size: 16px;
        }

        .modal-header .modal-subtitle {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.75);
            margin-top: 1px;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.7;
        }

        .modal-header .btn-close:hover {
            opacity: 1;
        }

        /* ─── Modal Tabs ─── */
        .modal-tabs {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            background: #F8FAFC;
            border-bottom: 1px solid #E2E8F0;
            gap: 0;
        }

        .modal-tab {
            flex: 1;
            text-align: center;
            padding: 12px 16px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            color: #6c757d;
            border-bottom: 2px solid transparent;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .modal-tab i {
            font-size: 15px;
        }

        .modal-tab:hover {
            color: #495057;
            background: #f8f9fa;
        }

        .modal-tab.active {
            color: #00AEEF;
            border-bottom-color: #00AEEF;
            background: #fff;
        }

        /* ─── Body ─── */
        .modal-body {
            padding: 0;
            background: #fff;
        }

        /* ─── Form Elements ─── */
        .form-label {
            font-weight: 600;
            font-size: 13px;
            color: #1E293B;
            margin-bottom: 6px;
            letter-spacing: -0.01em;
        }

        .form-control,
        .form-select {
            /* border-radius: 10px; */
            border: 1.5px solid #E2E8F0;
            font-size: 12px;
            /* padding: 10px 14px; */
            transition: all 0.2s ease;
            /* background: #fff; */
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #00AEEF;
            box-shadow: 0 0 0 4px rgba(0, 174, 239, 0.12);
            outline: none;
        }

        .form-control:hover,
        .form-select:hover {
            border-color: #94A3B8;
        }

        .input-group-text {
            border-radius: 10px 0 0 10px;
            background: #F1F5F9;
            border: 1.5px solid #E2E8F0;
            border-right: none;
            font-weight: 600;
            color: #64748B;
            font-size: 13px;
        }

        .input-group .form-control {
            border-radius: 0 10px 10px 0;
        }

        .input-group .btn-outline-secondary {
            border-radius: 0 10px 10px 0;
            border: 1.5px solid #E2E8F0;
            border-left: none;
            padding: 10px 14px;
            color: #64748B;
            transition: all 0.2s;
        }

        .input-group .btn-outline-secondary:hover {
            background: #F1F5F9;
            color: #1E293B;
        }

        /* ─── Code Preview ─── */
        .code-preview {
            padding: 4px 0;
        }

        .code-preview-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            background: #F8FAFC;
            border: 1.5px dashed #E2E8F0;
            border-radius: 10px;
            margin-bottom: 13px;
        }

        .code-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .badge-new {
            background: #D1FAE5;
            color: #065F46;
        }

        .badge-edit {
            background: #DBEAFE;
            color: #1E40AF;
        }

        .code-text {
            font-family: 'SF Mono', 'Fira Code', 'Courier New', monospace;
            font-size: 15px;
            font-weight: 700;
            color: #1E293B;
            letter-spacing: 0.08em;
        }

        /* ─── Discount Preview Box ─── */
        .discount-preview-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 10px 16px;
            min-height: 42px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .discount-preview-box .preview-label {
            /* font-size: 11px; */
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #6c757d;
            margin-bottom: 2px;
        }

        .discount-preview-box .preview-value {
            font-size: 18px;
            font-weight: 700;
            color: #00AEEF;
        }

        /* ─── Used Count Progress ─── */

        /* ─── Used Count Progress ─── */
        .used-count-card {
            background: #F8FAFC;
            border: 1.5px solid #E2E8F0;
            border-radius: 12px;
            padding: 14px 18px;
        }

        .used-count-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748B;
        }

        .used-count-text {
            font-size: 14px;
            font-weight: 700;
            color: #1E293B;
        }

        .used-count-bar {
            width: 100%;
            height: 8px;
            background: #E2E8F0;
            border-radius: 99px;
            overflow: hidden;
        }

        .used-count-fill {
            height: 100%;
            background: #10B981;
            border-radius: 99px;
            transition: width 0.5s ease, background 0.3s ease;
        }

        /* ─── Scope Card ─── */
        .scope-card {
            background: #FAFBFC;
            border: 1.5px solid #E2E8F0;
            border-radius: 14px;
            padding: 20px;
            transition: border-color 0.2s;
        }

        .scope-card:focus-within {
            border-color: #00AEEF;
        }

        .scope-title-icon {
            width: 36px;
            height: 36px;
            background: #e9ecef;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #00AEEF;
            margin-right: 12px;
        }

        .scope-card .form-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748B;
        }

        /* ─── Summary Card ─── */
        .summary-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 13px;
            color: #6c757d;
        }

        /* ─── Select2 Overrides ─── */
        .select2-container--default .select2-selection--multiple {
            border: 1.5px solid #E2E8F0 !important;
            /* border-radius: 10px !important;
            min-height: 44px;
            padding: 4px 4px; */
        }

        .select2-container--default.select2-container--focus .select2-selection--multiple {
            border-color: #00AEEF !important;
            box-shadow: 0 0 0 4px rgba(0, 174, 239, 0.12) !important;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            /* background: #e9ecef !important; */
            /* border: 1px solid #ced4da !important; */
            /* border-radius: 6px !important; */
            padding: 2px 8px 2px 4px !important;
            font-size: 12px;
            color: #495057;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: #64748B !important;
            margin-right: 4px;
        }

        .select2-container--default .select2-search--inline .select2-search__field {
            font-size: 13px !important;
            padding: 4px 8px !important;
        }

        .select2-dropdown {
            border: 1px solid #E2E8F0 !important;
            border-radius: 10px !important;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08) !important;
            margin-top: 4px;
        }

        .select2-results__option {
            padding: 8px 12px !important;
        }

        .select2-results__option--highlighted {
            background: #F1F5F9 !important;
            color: #1E293B !important;
        }

        /* ─── Footer ─── */
        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid #E2E8F0;
            border-radius: 0;
            background: #F8FAFC;
        }

        .modal-footer .btn {
            padding: 10px 22px;
            font-weight: 600;
            border-radius: 10px;
            font-size: 13px;
        }

        .modal-footer .btn-primary {
            background: linear-gradient(135deg, #00AEEF, #0095CC);
            border: none;
            transition: all 0.2s ease;
        }

        .modal-footer .btn-primary:hover {
            background: linear-gradient(135deg, #0095CC, #007EA8);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(0, 174, 239, 0.3);
        }

        .modal-footer .btn-primary:disabled {
            opacity: 0.7;
            transform: none;
        }

        .modal-footer .btn-light {
            background: #F1F5F9;
            border: 1px solid #E2E8F0;
            color: #475569;
        }

        .modal-footer .btn-light:hover {
            background: #E2E8F0;
        }

        /* ─── Responsive ─── */
        @media (max-width: 768px) {
            .modal-dialog {
                margin: 0;
            }
            .modal-tab span {
                display: none;
            }
            .modal-tab i {
                font-size: 18px;
            }
            .modal-tabs {
                gap: 0;
            }
        }

        /* ─── Animations ─── */
        @keyframes fadeSlideUp {
            from {
                opacity: 0;
                transform: translateY(8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .tab-content {
            animation: fadeSlideUp 0.25s ease-out;
        }

        .modal.fade .modal-dialog {
            transition: transform 0.3s ease-out, opacity 0.2s ease-out;
        }

        .modal.show .modal-dialog {
            transform: none;
        }

        /* ─── Select2 Search Placeholder ─── */
        .select2-container--default .select2-selection--multiple .select2-selection__placeholder {
            color: #94A3B8 !important;
            font-size: 13px;
        }

        /* ─── Small text ─── */
        .text-muted small,
        small.text-muted {
            font-size: 11px;
            color: #94A3B8;
            margin-top: 4px;
            display: inline-block;
        }
    </style>
@endpush
