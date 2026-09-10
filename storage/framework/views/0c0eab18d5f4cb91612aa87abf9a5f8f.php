<?php $__env->startPush('styles'); ?>
    <link href="<?php echo e(asset('default/datatable.css')); ?>" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/dropify@0.2.2/dist/css/dropify.min.css" rel="stylesheet" />
    <style>
        .modal-content {
            border-radius: 8px;
            border: none;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.12);
        }

        .modal-header {
            border-bottom: 1px solid #f1f5f9;
            padding: 16px 20px;
        }

        .modal-header .modal-title {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
        }

        .modal-body {
            padding: 20px;
        }

        .modal-footer {
            border-top: 1px solid #f1f5f9;
            padding: 12px 20px;
        }

        .dropify-wrapper {
            height: 120px;
        }

        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 6px;
        }
    </style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <div class="app-content main-content mt-0">
        <div class="side-app">
            <div class="main-container container-fluid">

                <!-- PAGE-HEADER -->
                <div class="page-header d-flex flex-wrap align-items-center justify-content-between">
                    <div>
                        <h1 class="page-title">Sports Types</h1>
                        <p class="text-muted mb-0" style="font-size: 13px;">Manage sports types, fees, and icons</p>
                    </div>
                    <ol class="breadcrumb mb-0 py-0">
                        <li class="breadcrumb-item"><a href="<?php echo e(route('admin.sports-type.index')); ?>">Sports Types</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Index</li>
                    </ol>
                </div>

                <!-- ROW -->
                <div class="row">
                    <div class="col-12 col-sm-12">
                        <div class="card product-sales-main">
                            <div class="card-header border-bottom">
                                <h3 class="card-title mb-0">List</h3>
                                <div class="card-options ms-auto">
                                    <button type="button"
                                        class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1"
                                        onclick="openCreateModal()">
                                        <i class="fe fe-plus"></i>
                                        <span>Add</span>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered text-nowrap border-bottom" id="datatable">
                                    <thead>
                                        <tr>
                                            <th class="bg-transparent border-bottom-0 wp-15">ID</th>
                                            <th class="bg-transparent border-bottom-0">Sports Type Name</th>
                                            <th class="bg-transparent border-bottom-0">Camps</th>
                                            <th class="bg-transparent border-bottom-0">Fee</th>
                                            <th class="bg-transparent border-bottom-0">Icon</th>
                                            <th class="bg-transparent border-bottom-0">Status</th>
                                            <th class="bg-transparent border-bottom-0">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    
    <div class="modal fade" id="sportsTypeModal" tabindex="-1" aria-labelledby="sportsTypeModalLabel" aria-hidden="true"
        data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" style="max-width:520px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sportsTypeModalLabel">Create Sports Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <form id="sportsTypeForm" method="POST" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" id="sportsTypeId" name="sportsTypeId" value="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="sports_name" class="form-label">Sports Type Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="sports_name" id="sports_name"
                                placeholder="Enter sports type name" required>
                            <div class="invalid-feedback" id="sports_name_error"></div>
                        </div>
                        <div class="mb-3">
                            <label for="sports_fee" class="form-label">Sports Fee ($)</label>
                            <input type="number" class="form-control" name="sports_fee" id="sports_fee" placeholder="0.00"
                                step="0.01" min="0">
                            <div class="invalid-feedback" id="sports_fee_error"></div>
                        </div>
                        <div class="mb-0">
                            <label for="icon" class="form-label">Icon</label>
                            <input type="file" class="dropify form-control" name="icon" id="icon"
                                data-default-file="<?php echo e(asset('default/logo.png')); ?>"
                                data-allowed-file-extensions="jpeg png jpg gif svg" data-max-file-size="5M">
                            <small class="text-muted">Image size less than 5MB. Allowed: jpeg, jpg, png, gif, svg.</small>
                            <div class="invalid-feedback" id="icon_error"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="sportsTypeSubmitBtn">
                            <i class="fe fe-save me-1"></i> Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    <?php echo $__env->make('backend.partials._scripts-datatable', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <script src="https://cdn.jsdelivr.net/npm/dropify@0.2.2/dist/js/dropify.min.js"></script>
    <script>
        // ── Dropify: init once, then just update preview ──
        function resetDropifyPreview(defaultFile) {
            var el = $('#icon');
            var drp = el.data('dropify');
            if (!drp) {
                // First time: initialize
                el.dropify({
                    defaultFile: defaultFile || '<?php echo e(asset('default/logo.png')); ?>',
                    messages: {
                        'default': 'Drag & drop or click to upload',
                        'replace': 'Drag & drop or click to replace',
                        'remove': 'Remove',
                        'error': 'An error occurred'
                    }
                });
            } else {
                // Already initialized — update default file FIRST, then reset preview
                if (defaultFile) {
                    drp.settings.defaultFile = defaultFile;
                }
                drp.resetPreview();
                drp.clearElement();
            }
        }

        $(document).ready(function() {
            $.ajaxSetup({
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                }
            });

            // Initialize DataTable
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
                        <img src="<?php echo e(asset('default/loader.gif')); ?>" alt="Loader" style="width: 50px;">
                        </div>`
                    },
                    scroller: {
                        loadingIndicator: false
                    },
                    pagingType: "full_numbers",
                    dom: "<'row justify-content-between table-topbar'<'col-md-4 col-sm-3'l><'col-md-5 col-sm-5 px-0'f>>tipr",
                    ajax: {
                        url: "<?php echo e(route('admin.sports-type.index')); ?>",
                        type: "GET",
                    },
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'sports_name',
                            name: 'sports_name',
                            orderable: true,
                            searchable: true
                        },                        {
                            data: 'camps_count',
                            name: 'camps_count',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'sports_fee',
                            name: 'sports_fee',
                            orderable: true,
                            searchable: true,
                            render: function(data) {
                                if (data === null || data === undefined || data === '')
                                return '$0.00';
                                return '$' + parseFloat(data).toFixed(2);
                            }
                        },
                        {
                            data: 'icon',
                            name: 'icon',
                            orderable: false,
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

            // Init dropify on first modal show
            $('#sportsTypeModal').on('shown.bs.modal', function() {
                var el = $('#icon');
                if (!el.data('dropify')) {
                    resetDropifyPreview('<?php echo e(asset('default/logo.png')); ?>');
                }
            });

            // Reset form on modal close
            $('#sportsTypeModal').on('hidden.bs.modal', function() {
                $('#sportsTypeForm')[0].reset();
                $('#sportsTypeId').val('');
                $('#sportsTypeModalLabel').text('Create Sports Type');
                resetDropifyPreview('<?php echo e(asset('default/logo.png')); ?>');
                $('.invalid-feedback').text('').hide();
                $('.is-invalid').removeClass('is-invalid');
            });

            // Form submission via AJAX
            $('#sportsTypeForm').on('submit', function(e) {
                e.preventDefault();
                var formData = new FormData(this);
                var id = $('#sportsTypeId').val();
                var isEdit = id !== '';
                var url = isEdit ?
                    "<?php echo e(route('admin.sports-type.update', ':id')); ?>".replace(':id', id) :
                    "<?php echo e(route('admin.sports-type.store')); ?>";

                // Clear previous errors
                $('.invalid-feedback').text('').hide();
                $('.is-invalid').removeClass('is-invalid');

                $('#sportsTypeSubmitBtn').prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Saving...'
                    );

                $.ajax({
                    type: "POST",
                    url: url,
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(resp) {
                        $('#sportsTypeModal').modal('hide');
                        toastr.success(resp.message);
                        $('#datatable').DataTable().ajax.reload();
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON;
                            if (errors.errors) {
                                $.each(errors.errors, function(field, messages) {
                                    var input = $('[name="' + field + '"]');
                                    input.addClass('is-invalid');
                                    $('#' + field + '_error').text(messages[0]).show();
                                });
                            } else if (errors.message) {
                                toastr.error(errors.message);
                            }
                        } else {
                            toastr.error(xhr.responseJSON?.message || 'Something went wrong!');
                        }
                    },
                    complete: function() {
                        $('#sportsTypeSubmitBtn').prop('disabled', false).html(
                            '<i class="fe fe-save me-1"></i> Save');
                    }
                });
            });
        });

        // ── Open Create Modal ──
        function openCreateModal() {
            $('#sportsTypeId').val('');
            $('#sportsTypeModalLabel').text('Create Sports Type');
            $('#sportsTypeForm')[0].reset();
            resetDropifyPreview('<?php echo e(asset('default/logo.png')); ?>');
            $('.invalid-feedback').text('').hide();
            $('.is-invalid').removeClass('is-invalid');
            $('#sportsTypeModal').modal('show');
        }

        // ── Open Edit Modal ──
        function openEditModal(id) {
            NProgress.start();
            var url = "<?php echo e(route('admin.sports-type.get', ':id')); ?>".replace(':id', id);

            $.ajax({
                type: "GET",
                url: url,
                success: function(resp) {
                    NProgress.done();
                    var item = resp.data;
                    $('#sportsTypeId').val(item.id);
                    $('#sports_name').val(item.sports_name);
                    $('#sports_fee').val(item.sports_fee || '');
                    $('#sportsTypeModalLabel').text('Edit Sports Type');

                    var defaultIcon = item.icon ?
                        '<?php echo e(url('')); ?>/' + item.icon :
                        '<?php echo e(asset('default/logo.png')); ?>';
                    resetDropifyPreview(defaultIcon);

                    $('.invalid-feedback').text('').hide();
                    $('.is-invalid').removeClass('is-invalid');

                    $('#sportsTypeModal').modal('show');
                },
                error: function(error) {
                    NProgress.done();
                    toastr.error(error.responseJSON?.message || 'Failed to load sports type!');
                }
            });
        }

        // ── Status Change ──
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
            let url = "<?php echo e(route('admin.sports-type.status', ':id')); ?>";
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
                    toastr.error(error.responseJSON?.message || 'Something went wrong!');
                }
            });
        }

        // ── Delete ──
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
            let url = "<?php echo e(route('admin.sports-type.destroy', ':id')); ?>";
            let csrfToken = '<?php echo e(csrf_token()); ?>';
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
                    toastr.error(error.responseJSON?.message || 'Something went wrong!');
                }
            });
        }
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('backend.app', ['title' => 'Sports Types'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/whistleworks-admin/htdocs/admin.whistleworks.org/resources/views/backend/layouts/sportsType/index.blade.php ENDPATH**/ ?>