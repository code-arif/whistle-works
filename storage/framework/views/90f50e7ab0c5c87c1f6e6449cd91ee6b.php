<?php $__env->startSection('header'); ?>
    <tr>
        <td class="header">
            <a href="<?php echo e(config('app.url')); ?>" style="display: inline-block;">
                <img src="<?php echo e($message->embed(public_path('default/logo.png'))); ?>" alt="Whistle Works" class="logo">
            </a>
        </td>
    </tr>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <h1 style="color: #00aeef;">New Evaluator Registration</h1>

    <p>
        An evaluator has submitted a registration request for your camp.
    </p>

    <hr>

    <p><strong>Evaluator Name:</strong>
        <?php echo e($evaluator->first_name); ?> <?php echo e($evaluator->last_name); ?>

    </p>

    <p><strong>Email:</strong> <?php echo e($evaluator->email); ?></p>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(optional($evaluator->profile)->phone): ?>
        <p><strong>Phone:</strong> <?php echo e($evaluator->profile->phone); ?></p>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <hr>

    <p><strong>Camp Name:</strong> <?php echo e($camp->camp_name); ?></p>
    <p><strong>Camp Start Date:</strong>
        <?php echo e(optional($camp->start_date)->format('M d, Y')); ?>

    </p>

    <p><strong>Camp Location:</strong> <?php echo e($camp->location ?? 'N/A'); ?></p>

    <hr>

    <p>
        Please review the registration request from your director dashboard and approve or reject accordingly.
    </p>

    <p>
        Regards,<br>
        <strong>Whistle Works Team</strong>
    </p>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('emails.layout.master_layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/whistleworks-admin/htdocs/admin.whistleworks.org/resources/views/emails/director/evaluator-registration.blade.php ENDPATH**/ ?>