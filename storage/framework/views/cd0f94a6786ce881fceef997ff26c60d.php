<?php $__env->startSection('content'); ?>
<!-- CONTAINER OPEN -->
<div class="col col-login mx-auto text-center">
    <a href="index.html" class="text-center">
        <img src="<?php echo e(asset($settings->logo ?? 'default/logo.svg')); ?>" class="header-brand-img" alt="">
    </a>
</div>
<div class="container-login100">
    <div class="wrap-login100 p-0">
        <div class="card-body">
            <form class="card shadow-none" method="POST" action="<?php echo e(route('password.store')); ?>">
                <?php echo csrf_field(); ?>

                <div class="card-body">
                    <div class="text-center">
                        <span class="login100-form-title">
                            Forgot Password
                        </span>
                        <p class="text-muted">Enter your email and instructions will be sent to you!</p>
                    </div>
                    <div class="pt-3" id="forgot">

                        <!-- Password Reset Token -->
                        <input type="hidden" name="token" value="<?php echo e($request->route('token')); ?>">

                        <div class="form-group">
                            <label class="form-label" for="email">E-Mail:</label>
                            <input class="form-control" name="email" id="email" placeholder="Enter Your Email" type="email" value="<?php echo e(old('email')); ?>">
                        </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="alert alert-danger"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        <div class="form-group">
                            <label class="form-label" for="password">Password:</label>
                            <input class="form-control" name="password" id="password" placeholder="Enter Your Password" type="password" value="<?php echo e(old('password')); ?>">
                        </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="alert alert-danger"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        <div class="form-group">
                            <label class="form-label" for="password_confirmation">Confirm Password:</label>
                            <input class="form-control" name="password_confirmation" id="password_confirmation" placeholder="Enter Your Confirm Password" type="password" value="<?php echo e(old('password_confirmation')); ?>">
                        </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['password_confirmation'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="alert alert-danger"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        <div class="container-login100-form-btn">
                            <button type="submit" class="login100-form-btn btn-primary">
                                Reset Password
                            </button>
                        </div>

                    </div>

                </div>
            </form>
        </div>
    </div>
</div>
<!-- CONTAINER CLOSED -->
<?php $__env->stopSection(); ?>
<?php echo $__env->make('auth.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/whistleworks-admin/htdocs/admin.whistleworks.org/resources/views/auth/reset-password.blade.php ENDPATH**/ ?>