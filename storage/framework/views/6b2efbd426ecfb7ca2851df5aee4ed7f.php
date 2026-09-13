<?php $__env->startSection('content'); ?>
<!-- PAGE -->
<div class="page">
    <!-- PAGE-CONTENT OPEN -->
     <div class="page-content error-page error2">
         <div class="container text-center">
             <div class="error-template">
                 <h2 class="text-white mb-2">404<span class="fs-20">error</span></h2>
                 <h5 class="error-details text-white">
                     Oops! Some error has occured, Requested page not found!
                 </h5>
                 <div class="text-center">
                     <a class="btn btn-primary mt-5 mb-5" href="<?php echo e(url('/')); ?>"> <i class="fa fa-long-arrow-left"></i> Back to Home </a>
                 </div>
             </div>
         </div>
     </div>
     <!-- PAGE-CONTENT OPEN CLOSED -->
 </div>
 <!-- End PAGE -->
 <?php $__env->stopSection(); ?>
<?php echo $__env->make('auth.app', ['title' => '404'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\remote-work\whistle-works-backend\resources\views/errors/404.blade.php ENDPATH**/ ?>