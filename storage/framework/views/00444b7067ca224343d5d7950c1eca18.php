<?php
$settings = \Illuminate\Support\Facades\Cache::remember('auth_settings', 600, function () {
    return \App\Models\Setting::first();
});
?>
<!doctype html>
<html lang="en" dir="ltr">

<meta http-equiv="content-type" content="text/html;charset=UTF-8" />

<head>
    <?php echo htmlScriptTagJsApi(); ?>

    <!-- META DATA -->
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, user-scalable=0'>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="<?php echo strip_tags($settings->description ?? ''); ?>">
    <meta name="author" content="<?php echo e($settings->author ?? ''); ?>">
    <meta name="keywords" content="<?php echo strip_tags($settings->keywords ?? ''); ?>">

    <!-- TITLE -->
    <title><?php echo e(config('app.name')); ?> - <?php echo e($title ?? $settings->title ?? ''); ?></title>

    <!-- FAVICON -->
    <link rel="shortcut icon" type="image/x-icon" href="<?php echo e(asset($settings->favicon ?? 'default/logo.svg')); ?>" />

    <!-- BOOTSTRAP CSS -->
    <link id="style" href="<?php echo e(asset('backend')); ?>/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet" />

    <!-- STYLE CSS -->
    <link href="<?php echo e(asset('backend')); ?>/css/style.css" rel="stylesheet" />
    <link href="<?php echo e(asset('backend')); ?>/css/skin-modes.css" rel="stylesheet" />

    <!--- FONT-ICONS CSS -->
    <link href="<?php echo e(asset('backend')); ?>/plugins/icons/icons.css" rel="stylesheet" />

    <!-- INTERNAL Switcher css -->
    <link href="<?php echo e(asset('backend')); ?>/switcher/css/switcher.css" rel="stylesheet">
    <link href="<?php echo e(asset('backend')); ?>/switcher/demo.css" rel="stylesheet">

    <style>
        .header-brand-img {
            height: 4rem;
        }

        .fl-wrapper {
            z-index: 1000 !important;
        }
    </style>

</head>

<body class="ltr login-img" style="background-image: url('default/bg.jpg')">

    

    <?php echo $__env->make('backend.partials._loader', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <!-- Switcher Icon-->
    

    <!-- PAGE -->
    <div class="page">
        <div class="">
            <?php echo $__env->yieldContent('content'); ?>
        </div>
    </div>
    <!-- page -->


    <!-- JQUERY JS -->
    <script src="<?php echo e(asset('backend')); ?>/plugins/jquery/jquery.min.js"></script>

    <!-- BOOTSTRAP JS -->
    <script src="<?php echo e(asset('backend')); ?>/plugins/bootstrap/js/popper.min.js"></script>
    <script src="<?php echo e(asset('backend')); ?>/plugins/bootstrap/js/bootstrap.min.js"></script>

    <!-- Perfect SCROLLBAR JS-->
    <script src="<?php echo e(asset('backend')); ?>/plugins/p-scroll/perfect-scrollbar.js"></script>

    <!-- STICKY JS -->
    <script src="<?php echo e(asset('backend')); ?>/js/sticky.js"></script>



    <!-- COLOR THEME JS -->
    <script src="<?php echo e(asset('backend')); ?>/js/themeColors.js"></script>

    <!-- CUSTOM JS -->
    <script src="<?php echo e(asset('backend')); ?>/js/custom.js"></script>

    <!-- SWITCHER JS -->
    <script src="<?php echo e(asset('backend')); ?>/switcher/js/switcher.js"></script>

</body>

</html>
<?php /**PATH E:\remote-work\whistle-works-backend\resources\views/auth/app.blade.php ENDPATH**/ ?>