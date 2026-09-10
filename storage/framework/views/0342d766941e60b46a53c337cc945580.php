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
    <h1 style="color: #00aeef;">Assigned as Assistant Director</h1>

    <p>
        Hello <strong><?php echo e($assistantDirector->first_name); ?> <?php echo e($assistantDirector->last_name); ?></strong>,
    </p>

    <p>
        You have been assigned as an <strong>Assistant Director</strong> for <strong><?php echo e($camp->camp_name); ?></strong> by <?php echo e($director->first_name); ?> <?php echo e($director->last_name); ?>.
    </p>

    <hr style="border: 0; border-top: 1px solid #e5e7eb; margin: 20px 0;">

    <h2 style="font-size: 18px; color: #111827; margin-bottom: 12px;">Camp Details</h2>
    <p><strong>Camp Name:</strong> <?php echo e($camp->camp_name); ?></p>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($camp->location)): ?>
        <p><strong>Location:</strong> <?php echo e($camp->location); ?></p>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($camp->start_date)): ?>
        <p><strong>Start Date:</strong> <?php echo e(\Carbon\Carbon::parse($camp->start_date)->format('M d, Y')); ?></p>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <p><strong>Assigned By:</strong> <?php echo e($director->first_name); ?> <?php echo e($director->last_name); ?> (<?php echo e($director->email); ?>)</p>

    <hr style="border: 0; border-top: 1px solid #e5e7eb; margin: 20px 0;">

    <p>
        You can now access and manage this camp directly from your Director Dashboard.
    </p>

    <p style="margin-top: 25px;">
        Regards,<br>
        <strong>Whistle Works Team</strong>
    </p>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('emails.layout.master_layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/whistleworks-admin/htdocs/admin.whistleworks.org/resources/views/emails/director/assistant-director-assigned.blade.php ENDPATH**/ ?>