<!doctype html>
<html lang="en" dir="ltr">
<head>
    <!-- META DATA -->
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, user-scalable=0'>
    <meta http-equiv="content-type" content="text/html;charset=UTF-8" />
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="<?php echo strip_tags(settings()->description ?? ''); ?>">
    <meta name="author" content="<?php echo e(settings()->author ?? ''); ?>">
    <meta name="keywords" content="<?php echo strip_tags(settings()->keywords ?? ''); ?>">

    <!-- FAVICON -->
    <link rel="shortcut icon" type="image/png" href="<?php echo e(asset(settings()->favicon ?? 'default/favicon.png')); ?>" />

    <!-- TITLE -->
    <title><?php echo e(config('app.name')); ?> - <?php echo e($title ?? settings()->title ?? ''); ?></title>
    <!-- Scripts -->

    <script>

    window.authUserId = <?php echo e(auth()->id() ?? 'null'); ?>;
</script>

    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"></noscript>

    <?php echo app('Illuminate\Foundation\Vite')(['resources/js/app.js']); ?>

    <?php echo $__env->make('backend.partials._styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(); ?>



</head>

<body class="ltr app sidebar-mini">
    <?php echo $__env->make('backend.partials._loader', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <!-- PAGE -->
    <div class="page">
        <div class="page-main">
            <?php echo $__env->make('backend.partials._header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo $__env->make('backend.partials._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

            <?php echo $__env->yieldContent('content'); ?>
        </div>

        <?php echo $__env->make('backend.partials._footer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
    <!-- page -->
    <?php echo $__env->make('backend.partials._scripts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scripts(); ?>

</body>

</html>
<?php /**PATH /home/whistleworks-admin/htdocs/admin.whistleworks.org/resources/views/backend/app.blade.php ENDPATH**/ ?>