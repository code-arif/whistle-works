<?php $__env->startSection('content'); ?>
    <!--app-content open-->
    <div class="app-content main-content mt-0">
        <div class="side-app">

            <!-- CONTAINER -->
            <div class="main-container container-fluid">

                
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Stripe Settings <i class="fa-solid fa-triangle-exclamation text-danger"
                                title="Warning"></i></h1>
                    </div>
                    <div class="ms-auto pageheader-btn">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Settings</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Stripe</li>
                        </ol>
                    </div>
                </div>
                

                <div class="row">
                    <div class="row col-md-12">
                        <div class="col-lg-12 col-xl-12 col-md-12 col-sm-12">
                            <h3>Stripe Boosting Credential</h3>
                            <div class="card box-shadow-0">
                                <div class="card-body">
                                    <form class="form form-horizontal" method="post"
                                        action="<?php echo e(route('admin.setting.stripe.update')); ?>" enctype="multipart/form-data">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('PATCH'); ?>
                                        <div class="row mb-4">
                                            <label for="stripe_key" class="col-md-3 form-label">Stripe Key</label>
                                            <div class="col-md-9">
                                                <input class="form-control <?php $__errorArgs = ['stripe_key'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                                    id="stripe_key" name="stripe_key" placeholder="Enter your stripe key"
                                                    type="text" value="<?php echo e(env('STRIPE_KEY') ?? old('stripe_key')); ?>">
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['stripe_key'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="row mb-4">
                                            <label for="stripe_secret" class="col-md-3 form-label">Stripe Secret</label>
                                            <div class="col-md-9">
                                                <input class="form-control <?php $__errorArgs = ['stripe_secret'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                                    id="stripe_secret" name="stripe_secret"
                                                    placeholder="Enter your stripe secret" type="text"
                                                    value="<?php echo e(env('STRIPE_SECRET') ?? old('stripe_secret')); ?>">
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['stripe_secret'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="row mb-4">
                                            <label for="stripe_webhook_secret" class="col-md-3 form-label">Stripe Boosting
                                                Webhook Secret</label>
                                            <div class="col-md-9">
                                                <input
                                                    class="form-control <?php $__errorArgs = ['stripe_webhook_secret'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                                    id="stripe_webhook_secret" name="stripe_webhook_secret"
                                                    placeholder="Enter your stripe webhook secret" type="text"
                                                    value="<?php echo e(env('STRIPE_WEBHOOK_SECRET') ?? old('stripe_webhook_secret')); ?>">
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['stripe_webhook_secret'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            </div>
                                        </div>


                                        <!-- checkout webhook -->
                                        <div class="row mb-4">
                                            <label for="stripe_webhook_secret" class="col-md-3 form-label">Stripe Checkout
                                                Webhook Secret</label>
                                            <div class="col-md-9">
                                                <input
                                                    class="form-control <?php $__errorArgs = ['stripe_checkout_webhook_secret'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                                    id="stripe_checkout_webhook_secret"
                                                    name="stripe_checkout_webhook_secret"
                                                    placeholder="Enter your stripe webhook secret" type="text"
                                                    value="<?php echo e(env('STRIPE_CHECKOUT_WEBHOOK_SECRET') ?? old('stripe_checkout_webhook_secret')); ?>">
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['stripe_checkout_webhook_secret'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            </div>
                                        </div>


                                        <!-- rental webhook -->
                                        <div class="row mb-4">
                                            <label for="stripe_webhook_secret" class="col-md-3 form-label">Stripe Rental
                                                Webhook Secret</label>
                                            <div class="col-md-9">
                                                <input
                                                    class="form-control <?php $__errorArgs = ['stripe_rented_webhook_secret'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                                    id="stripe_rental_checkout_webhook_secret"
                                                    name="stripe_rented_webhook_secret"
                                                    placeholder="Enter your stripe webhook secret" type="text"
                                                    value="<?php echo e(env('STRIPE_RENTED_WEBHOOK_SECRET') ?? old('stripe_rented_checkout_webhook_secret')); ?>">
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['stripe_rented_webhook_secret'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            </div>
                                        </div>


                                        <div class="row justify-content-end">
                                            <div class="col-sm-9">
                                                <div>
                                                    <button class="submit btn btn-primary" type="submit">Submit</button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- for onboarding -->
                    


                    
                    <div class="row col-md-6">
                        <div class="col-lg-12 col-xl-12 col-md-12 col-sm-12">
                            <h3>Software fee</h3>
                            <div class="card box-shadow-0">
                                <div class="card-body">
                                    <form class="form form-horizontal" method="post"
                                        action="<?php echo e(route('admin.setting.stripe.update-percentage')); ?>"
                                        enctype="multipart/form-data">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('PATCH'); ?>
                                        <div class="row mb-4">
                                            <label for="admin_percentage" class="col-md-3 form-label">Add software fee</label>
                                            <div class="col-md-9">
                                                <input class="form-control <?php $__errorArgs = ['CAMP_EXTRA_PRICE'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                                    id="admin_percentage" name="admin_percentage"
                                                    placeholder="Enter the software fee" type="text"
                                                    value="<?php echo e(env('CAMP_EXTRA_PRICE') ?? old('admin_percentage')); ?>">
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['admin_percentage'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            </div>
                                        </div>

                                        

                                        <div class="row justify-content-end">
                                            <div class="col-sm-9">
                                                <div>
                                                    <button class="submit btn btn-primary" type="submit">Submit</button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- CONTAINER CLOSED -->
<?php $__env->stopSection(); ?>



<?php $__env->startPush('scripts'); ?>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('backend.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/whistleworks-admin/htdocs/admin.whistleworks.org/resources/views/backend/layouts/settings/stripe_settings.blade.php ENDPATH**/ ?>