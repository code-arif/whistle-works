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
    <h1 style="color: #00aeef;">Camp Registration Submitted</h1>

    <p>
        Dear <?php echo e($evaluator->first_name); ?>,
    </p>

    <p>
        Your registration request for the following camp has been successfully submitted and is currently pending director
        approval.
    </p>

    <hr>

    <p><strong>Camp Name:</strong> <?php echo e($camp->camp_name); ?></p>

    <p><strong>Start Date:</strong>
        <?php echo e(optional($camp->start_date)->format('M d, Y')); ?>

    </p>

    <p><strong>Location:</strong> <?php echo e($camp->location ?? 'N/A'); ?></p>

    <hr>

    <p>
        You will receive another email once your registration has been reviewed.
    </p>

    <p>
        Thank you for being part of Whistle Works.
    </p>

    <p>
        Regards,<br>
        <strong>Whistle Works Team</strong>
    </p>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('emails.layout.master_layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/whistleworks-admin/htdocs/admin.whistleworks.org/resources/views/emails/evaluator/registration-confirmation.blade.php ENDPATH**/ ?>