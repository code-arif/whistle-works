<?php $__env->startSection('title', 'Testimonial section'); ?>

<?php $__env->startSection('content'); ?>
    <!--app-content open-->
    <div class="app-content main-content mt-0">
        <div class="side-app">
            <!-- CONTAINER -->
            <div class="main-container container-fluid">
                
                <div class="page-header d-flex flex-wrap align-items-center justify-content-between">
                    <div>
                        <h1 class="page-title">Home Page — Testimonial Section</h1>
                        <p class="text-muted mb-0" style="font-size: 13px;">Manage testimonial reviews and feedback</p>
                    </div>
                    <ol class="breadcrumb mb-0 py-0">
                        <li class="breadcrumb-item"><a href="<?php echo e(route('admin.cms.home.testimonial.index')); ?>">Home page</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Testimonial section</li>
                    </ol>
                </div>


                <!-- Review Counter -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fe fe-star text-warning" style="font-size: 40px;"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?php echo e($count); ?></h3>
                                        <p class="text-muted mb-0">Total Reviews</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                
                <div class="row">
                    <div class="col-4">
                        <div class="card box-shadow-0">
                            <div class="card-header bg-light">
                                <h4 class="card-title">Header</h4>
                            </div>
                            <div class="card-body">
                                <form class="form-horizontal" method="post"
                                    action="<?php echo e(route('admin.cms.home.testimonial.update')); ?>" enctype="multipart/form-data">
                                    <?php echo csrf_field(); ?>
                                    <div class="row mb-4">

                                        
                                        <div class="form-group mb-3">
                                            <label for="title" class="form-label">Testimonial</label>
                                            <input type="text" class="form-control <?php $__errorArgs = ['title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                                name="title" placeholder="Enter title" id="title"
                                                value="<?php echo e($data->title ?? old('title')); ?>">
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                <span class="text-danger"><?php echo e($message); ?></span>
                                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </div>

                                        
                                        <div class="form-group mb-3">
                                            <label for="description" class="form-label">Description</label>
                                            <textarea name="description" class="form-control <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" rows="4"
                                                placeholder="Enter description"><?php echo e($data->description ?? old('description')); ?></textarea>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                <span class="text-danger"><?php echo e($message); ?></span>
                                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </div>

                                        
                                        <div class="form-group">
                                            <button class="btn btn-primary" type="submit">Save changes</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>


                    
                    <div class="col-8">
                        <div class="card box-shadow-0">

                            <div class="card-header bg-light d-flex justify-content-between">
                                <h4 class="card-title">Manage Reviews</h4>
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#reviewModal"
                                    id="addReviewBtn">
                                    <span>Add Review</span>
                                </button>
                            </div>

                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table text-nowrap mb-0 table-bordered" id="datatable">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Author</th>
                                                <th>Designation</th>
                                                <th>Avatar</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            
                                        </tbody>
                                    </table>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Review Modal (Add/Edit) -->
    <div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="reviewForm" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" id="reviewID">

                    <div class="modal-header">
                        <h5 class="modal-title" id="reviewModalLabel">Add Review</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row">

                            <!-- Author Name -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Author Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="author_name" id="author_name"
                                    placeholder="Enter author name">
                                <span class="text-danger error-text author_name_error"></span>
                            </div>

                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Designation <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="designation" id="designation"
                                    placeholder="Enter designation">
                                <span class="text-danger error-text designation_error"></span>
                            </div>

                            <!-- Review Text -->
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Review Text</label>
                                <textarea class="form-control" name="review_text" id="review_text" rows="4" placeholder="Enter review text"></textarea>
                                <span class="text-danger error-text review_text_error"></span>
                            </div>

                            <!-- Avatar -->
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Author Avatar</label>
                                <input type="file" name="author_avatar" id="author_avatar"
                                    class="form-control dropify" accept="image">
                                <span class="text-danger error-text author_avatar_error"></span>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="reviewSubmitBtn">Save Review</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Review Modal -->
    <div class="modal fade" id="viewReviewModal" tabindex="-1" aria-labelledby="viewReviewModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewReviewModalLabel">Review Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 text-center mb-4">
                            <img id="view_author_avatar" src="" alt="Author Avatar" class="rounded-circle"
                                style="width: 120px; height: 120px; object-fit: cover; display: none;">
                            <div id="no_avatar" class="text-muted" style="display: none;">
                                <i class="fe fe-user" style="font-size: 80px;"></i>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Author Name:</label>
                            <p id="view_author_name" class="text-muted"></p>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Designation:</label>
                            <p id="view_designation" class="text-muted"></p>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Review Text:</label>
                            <p id="view_review_text" class="text-muted"></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

<?php $__env->stopSection(); ?>


<?php $__env->startPush('scripts'); ?>
    <?php echo $__env->make('backend.partials._scripts-datatable', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
    <script>
        $(document).ready(function() {

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // Initialize DataTable
            let dTable = $('#datatable').DataTable({
                order: [],
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "All"]
                ],
                processing: true,
                responsive: false,
                serverSide: true,
                language: {
                    processing: `<div class="text-center"><img src="<?php echo e(asset('default/loader.gif')); ?>" style="width:50px;"></div>`
                },
                ajax: {
                    url: "<?php echo e(route('admin.cms.home.testimonial.index')); ?>",
                    type: "GET"
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'author_name'
                    },
                    {
                        data: 'designation'
                    },
                    {
                        data: 'author_avatar',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    }
                ]
            });

            // Open Modal for Add
            $('#addReviewBtn').click(function() {
                $('#reviewModalLabel').text('Add Review');
                $('#reviewForm')[0].reset();
                $('#reviewID').val('');
                $('.error-text').text('');
                $('.dropify').dropify('destroy').dropify();
                $('#reviewSubmitBtn').prop('disabled', false).text('Save Review');
                $('#reviewModal').modal('show');
            });

            // Form Submit (Create & Update)
            $('#reviewForm').on('submit', function(e) {
                e.preventDefault();
                let formData = new FormData(this);
                let id = $('#reviewID').val();

                let url = id ?
                    "<?php echo e(route('admin.cms.home.testimonial.item.update', ':id')); ?>".replace(':id', id) :
                    "<?php echo e(route('admin.cms.home.testimonial.item.store')); ?>";

                if (id) formData.append('_method', 'POST');

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    beforeSend: function() {
                        $('.error-text').text('');
                        $('#reviewSubmitBtn').prop('disabled', true).text('Saving...');
                    },
                    success: function(res) {
                        if (res.success) {
                            $('#reviewModal').modal('hide');
                            toastr.success(res.message);

                            // Reload DataTable
                            dTable.ajax.reload(null, false);

                            $('#reviewForm')[0].reset();
                            $('.dropify').dropify('destroy').dropify();
                        } else {
                            if (res.errors) {
                                $.each(res.errors, function(field, messages) {
                                    $('.' + field + '_error').text(messages[0]);
                                });
                            }
                        }
                    },
                    error: function(xhr) {
                        $('#reviewSubmitBtn').prop('disabled', false).text('Save Review');
                        if (xhr.status === 422) {
                            $.each(xhr.responseJSON.errors, function(field, messages) {
                                $('.' + field + '_error').text(messages[0]);
                            });
                        } else {
                            toastr.error(xhr.responseJSON.message || 'Something went wrong!');
                        }
                    },
                    complete: function() {
                        $('#reviewSubmitBtn').prop('disabled', false).text('Save Review');
                    }
                });
            });

            // Edit Review
            $(document).on('click', '.editReview', function() {
                let id = $(this).data('id');
                let url = "<?php echo e(route('admin.cms.home.testimonial.item.edit', ':id')); ?>".replace(':id', id);

                $.get(url, function(res) {
                    if (res.success) {
                        $('#reviewModalLabel').text('Edit Review');
                        $('#reviewID').val(res.data.id);
                        $('#author_name').val(res.data.author_name);
                        $('#designation').val(res.data.designation);
                        $('#review_text').val(res.data.review_text || '');

                        // Handle Dropify image
                        let imageInput = $('#author_avatar').dropify();
                        imageInput = imageInput.data('dropify');
                        imageInput.resetPreview();
                        imageInput.clearElement();

                        if (res.data.author_avatar) {
                            let baseUrl = "<?php echo e(asset('')); ?>";
                            imageInput.settings.defaultFile = baseUrl + res.data.author_avatar;
                            imageInput.destroy();
                            imageInput.init();
                        }

                        $('#reviewModal').modal('show');
                    } else {
                        toastr.error(res.message || 'Failed to load review.');
                    }
                }).fail(function(xhr) {
                    console.error(xhr);
                    toastr.error('Server error. Please try again.');
                });
            });

            // View Review
            $(document).on('click', '.viewReview', function() {
                let id = $(this).data('id');
                let url = "<?php echo e(route('admin.cms.home.testimonial.item.show', ':id')); ?>".replace(':id', id);

                $.get(url, function(res) {
                    if (res.success) {
                        $('#view_author_name').text(res.data.author_name || 'N/A');
                        $('#view_designation').text(res.data.designation || 'N/A');
                        $('#view_review_text').text(res.data.review_text ||
                            'No review text provided');

                        // Display Avatar or Placeholder
                        if (res.data.author_avatar) {
                            $('#view_author_avatar').attr('src', "<?php echo e(asset('/')); ?>" + res.data
                                .author_avatar).show();
                            $('#no_avatar').hide();
                        } else {
                            $('#view_author_avatar').hide();
                            $('#no_avatar').show();
                        }

                        // Display Rating Stars
                        let rating = res.data.rating || 0;
                        let starsHtml = '';
                        for (let i = 1; i <= 5; i++) {
                            if (i <= rating) {
                                starsHtml += '<i class="fa fa-star"></i> ';
                            } else {
                                starsHtml += '<i class="fa fa-star" style="color: #ddd;"></i> ';
                            }
                        }
                        $('#view_rating_stars').html(starsHtml);

                        $('#viewReviewModal').modal('show');
                    } else {
                        toastr.error(res.message || 'Failed to load review.');
                    }
                }).fail(function() {
                    toastr.error('Server error. Please try again.');
                });
            });

            // Delete Confirm - Make dTable global accessible
            window.dTable = dTable;
        });

        // Delete Confirm
        window.showDeleteConfirm = function(id) {
            Swal.fire({
                title: 'Delete Review?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete!',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33'
            }).then(result => {
                if (result.isConfirmed) deleteReview(id);
            });
        };

        function deleteReview(id) {
            $.ajax({
                url: "<?php echo e(route('admin.cms.home.testimonial.item.delete', '')); ?>/" + id,
                type: 'DELETE',
                success: function(res) {
                    if (res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: res.message,
                            timer: 2000,
                            showConfirmButton: false
                        });

                        // Reload DataTable without page reset
                        window.dTable.ajax.reload(null, false);
                    } else {
                        toastr.error(res.message);
                    }
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Failed to delete.');
                }
            });
        }
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('backend.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/whistleworks-admin/htdocs/admin.whistleworks.org/resources/views/backend/layouts/cms/home/testimonial.blade.php ENDPATH**/ ?>