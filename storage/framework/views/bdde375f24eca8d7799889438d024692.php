<script>
    <?php if(session()->has('t-success')): ?>
        toastr.success("<?php echo e(session('t-success')); ?>");
        <?php echo e(session()->forget('t-success')); ?>

    <?php endif; ?>
    <?php if(session()->has('t-error')): ?>
        toastr.error("<?php echo e(session('t-error')); ?>");
        <?php echo e(session()->forget('t-error')); ?>

    <?php endif; ?>
</script>
<?php /**PATH /home/whistleworks-admin/htdocs/admin.whistleworks.org/resources/views/backend/partials/_toster.blade.php ENDPATH**/ ?>