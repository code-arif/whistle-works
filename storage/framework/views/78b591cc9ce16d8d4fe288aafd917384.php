<!-- BACK-TO-TOP -->
<a href="#top" id="back-to-top"><i class="fa fa-long-arrow-up"></i></a>


<!-- JQUERY JS -->
<script src="<?php echo e(asset('backend/plugins/jquery/jquery.min.js')); ?>"></script>

<!-- BOOTSTRAP JS -->
<script src="<?php echo e(asset('backend/plugins/bootstrap/js/popper.min.js')); ?>"></script>
<script src="<?php echo e(asset('backend/plugins/bootstrap/js/bootstrap.min.js')); ?>"></script>

<!-- SIDE-MENU JS -->
<script src="<?php echo e(asset('backend/plugins/sidemenu/sidemenu.js')); ?>"></script>

<!-- Perfect SCROLLBAR JS-->
<script src="<?php echo e(asset('backend/plugins/p-scroll/perfect-scrollbar.js')); ?>"></script>
<!-- <script src="<?php echo e(asset('backend/plugins/p-scroll/pscroll.js')); ?>"></script> -->

<!-- STICKY JS -->
<script src="<?php echo e(asset('backend/js/sticky.js')); ?>"></script>


<!-- INTERNAL SELECT2 JS -->
<script src="<?php echo e(asset('backend/plugins/select2/select2.full.min.js')); ?>"></script>

<!-- INDEX JS -->
<script src="<?php echo e(asset('backend/js/index1.js')); ?>"></script>
<script src="<?php echo e(asset('backend/js/index.js')); ?>"></script>

<!-- Reply JS-->
<script src="<?php echo e(asset('backend/js/reply.js')); ?>"></script>


<!-- COLOR THEME JS -->
<script src="<?php echo e(asset('backend/js/themeColors.js')); ?>"></script>

<!-- CUSTOM JS -->
<script src="<?php echo e(asset('backend/js/custom.js')); ?>"></script>

<!-- SWITCHER JS -->
<script src="<?php echo e(asset('backend/switcher/js/switcher.js')); ?>"></script>


<script src="<?php echo e(asset('backend/js/toastr.min.js')); ?>"></script>



<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<script src="https://cdnjs.cloudflare.com/ajax/libs/Dropify/0.2.2/js/dropify.min.js" integrity="sha512-8QFTrG0oeOiyWo/VM9Y8kgxdlCryqhIxVeRpWSezdRRAvarxVtwLnGroJgnVW9/XBRduxO/z1GblzPrMQoeuew==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
    $('.dropify').dropify();
</script>



<!-- loader -->
<script src="<?php echo e(asset('default')); ?>/nprogress/nprogress.js"></script>


<!-- Toster -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>


<script>
    toastr.options = {
        "closeButton": true,
        "debug": false,
        "newestOnTop": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "preventDuplicates": true,
        "onclick": null,
        "showDuration": 300,
        "hideDuration": 300,
        "timeOut": 5000,
        "extendedTimeOut": 1000,
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    };
</script>

<?php echo $__env->make('backend.partials._toster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<?php echo $__env->make('backend.partials._ajax', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<?php echo $__env->make('backend.partials._notification', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<?php echo $__env->make('backend.partials._custom-script', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<?php echo $__env->yieldPushContent('scripts'); ?>
<?php echo $__env->yieldPushContent('page-scripts'); ?>
<?php /**PATH E:\remote-work\whistle-works-backend\resources\views/backend/partials/_scripts.blade.php ENDPATH**/ ?>