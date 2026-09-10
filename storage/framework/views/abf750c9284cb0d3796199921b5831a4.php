<!-- BOOTSTRAP CSS -->
<link id="style" href="<?php echo e(asset('backend/plugins/bootstrap/css/bootstrap.min.css')); ?>" rel="stylesheet" />

<!-- STYLE CSS -->
<link href="<?php echo e(asset('backend/css/style.css')); ?>" rel="stylesheet" />
<link href="<?php echo e(asset('backend/css/skin-modes.css')); ?>" rel="stylesheet" />


<!--- FONT-ICONS CSS -->
<link href="<?php echo e(asset('backend/plugins/icons/icons.css')); ?>" rel="stylesheet" />

<!-- INTERNAL Switcher css -->
<link href="<?php echo e(asset('backend/switcher/css/switcher.css')); ?>" rel="stylesheet">
<link href="<?php echo e(asset('backend/switcher/demo.css')); ?>" rel="stylesheet">


<link href="<?php echo e(asset('backend/css/toastr.css')); ?>" rel="stylesheet" />

<!-- Bootstrap icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" integrity="sha512-dPXYcDub/aeb08c63jRq/k6GaKccl256JQy/AnOq7CAnEZ9FzSL9wSbcZkMp4R26vBsMLFYH4kQ67/bbV8XaCQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />

<!-- font awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css" integrity="sha512-5Hs3dF2AEPkpNAR7UiOHba+lRSJNeM2ECkwxUIxC1Q/FLycGTbNapWXB4tP889k5T5Ju8fs4b1P5z/iB4nMfSQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />


<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Dropify/0.2.2/css/dropify.css" integrity="sha512-In/+MILhf6UMDJU4ZhDL0R0fEpsp4D3Le23m6+ujDWXwl3whwpucJG1PEmI3B07nyJx+875ccs+yX2CqQJUxUw==" crossorigin="anonymous" referrerpolicy="no-referrer" />



<!-- loader -->
<link rel='stylesheet' href="<?php echo e(asset('default')); ?>/nprogress/nprogress.css" />



<style>
    .header-brand-img {
        height: 4rem;
    }

    .side-header {
        height: 75px;
        padding: 4px 17px !important;
    }

    .note-editor.note-airframe .note-editing-area .note-editable,
    .note-editor.note-frame .note-editing-area .note-editable {
        word-wrap: break-word;
        min-height: 100px;
        overflow: auto;
        padding: 10px;
    }
</style>

<?php echo $__env->yieldPushContent('styles'); ?>


<style>
    .fl-wrapper {
        z-index: 1000 !important;
    }
</style>

<?php echo $__env->make('backend.partials._custom-style', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/whistleworks-admin/htdocs/admin.whistleworks.org/resources/views/backend/partials/_styles.blade.php ENDPATH**/ ?>