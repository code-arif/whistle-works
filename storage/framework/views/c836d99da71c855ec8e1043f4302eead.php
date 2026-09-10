<?php $__env->startSection('header'); ?>
    <tr>
        <td class="header">
            <!-- Logo -->
            <a href="<?php echo e(config('app.url')); ?>" style="display: inline-block;">
                <img src="<?php echo e($message->embed(public_path('default/logo.png'))); ?>" alt="Whistle Works" class="logo"
                    style="max-width: 200px; height: auto;">
                <!-- Fallback text if image doesn't load -->
                <div style="color: #ffffff; font-size: 24px; font-weight: bold; margin-top: 10px; display: none;">
                    WHISTLE WORKS
                </div>
            </a>
        </td>
    </tr>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <!-- Greeting -->
    <p style="margin-bottom: 20px; font-size: 18px; font-weight: 600;">
        Hello Admin,
    </p>

    <!-- Message -->
    <p style="margin-bottom: 25px; font-size: 15px; line-height: 1.6;">
        <span style="color: #00aeef; font-weight: 600;">Registration Alert:</span> A new user has just registered on the platform. Please review the details below.
    </p>

    <!-- Details Box -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 30px 0; border-top: 3px solid #00aeef; border-bottom: 1px solid rgba(128, 128, 128, 0.2);">
        <tr>
            <td align="left" style="padding: 20px 10px;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td width="35%" style="padding: 10px 0; font-size: 14px; font-weight: 600; color: #00aeef; border-bottom: 1px solid rgba(128, 128, 128, 0.1);">User ID</td>
                        <td width="65%" style="padding: 10px 0; font-size: 14px; font-weight: 500; border-bottom: 1px solid rgba(128, 128, 128, 0.1);">#<?php echo e($notiData['user_id']); ?></td>
                    </tr>
                    <tr>
                        <td width="35%" style="padding: 10px 0; font-size: 14px; font-weight: 600; color: #00aeef; border-bottom: 1px solid rgba(128, 128, 128, 0.1);">Name</td>
                        <td width="65%" style="padding: 10px 0; font-size: 14px; font-weight: 500; border-bottom: 1px solid rgba(128, 128, 128, 0.1);"><?php echo e($notiData['name'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <td width="35%" style="padding: 10px 0; font-size: 14px; font-weight: 600; color: #00aeef; border-bottom: 1px solid rgba(128, 128, 128, 0.1);">Username</td>
                        <td width="65%" style="padding: 10px 0; font-size: 14px; font-weight: 500; border-bottom: 1px solid rgba(128, 128, 128, 0.1);"><?php echo e($notiData['username']); ?></td>
                    </tr>
                    <tr>
                        <td width="35%" style="padding: 10px 0; font-size: 14px; font-weight: 600; color: #00aeef; border-bottom: 1px solid rgba(128, 128, 128, 0.1);">Email</td>
                        <td width="65%" style="padding: 10px 0; font-size: 14px; font-weight: 500; border-bottom: 1px solid rgba(128, 128, 128, 0.1);">
                            <a href="mailto:<?php echo e($notiData['email']); ?>" style="color: inherit; text-decoration: none;"><?php echo e($notiData['email']); ?></a>
                        </td>
                    </tr>
                    <tr>
                        <td width="35%" style="padding: 10px 0; font-size: 14px; font-weight: 600; color: #00aeef; border-bottom: 1px solid rgba(128, 128, 128, 0.1);">Phone</td>
                        <td width="65%" style="padding: 10px 0; font-size: 14px; font-weight: 500; border-bottom: 1px solid rgba(128, 128, 128, 0.1);"><?php echo e($notiData['phone']); ?></td>
                    </tr>
                    <tr>
                        <td width="35%" style="padding: 10px 0; font-size: 14px; font-weight: 600; color: #00aeef; border-bottom: 1px solid rgba(128, 128, 128, 0.1);">Role</td>
                        <td width="65%" style="padding: 10px 0; font-size: 14px; font-weight: 500; text-transform: capitalize; border-bottom: 1px solid rgba(128, 128, 128, 0.1);"><?php echo e($notiData['role']); ?></td>
                    </tr>
                    <tr>
                        <td width="35%" style="padding: 10px 0; font-size: 14px; font-weight: 600; color: #00aeef;">Status</td>
                        <td width="65%" style="padding: 10px 0; font-size: 14px; font-weight: 500;">
                            <span style="display: inline-block; padding: 4px 12px; background-color: #00aeef; color: #ffffff; border-radius: 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">
                                <?php echo e(ucfirst($notiData['status'])); ?>

                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-top: 20px;">
        <tr>
            <td align="center">
                <a href="<?php echo e(config('app.url')); ?>" style="display: inline-block; padding: 14px 28px; background-color: #00aeef; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px; transition: background-color 0.3s ease;">
                    View Dashboard
                </a>
            </td>
        </tr>
    </table>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('footer'); ?>
    <tr>
        <td class="footer">
            <!-- Copyright -->
            <div class="copyright">
                &copy; <?php echo e(date('Y')); ?> Whistle Works Inc. All rights reserved.<br>
                <small>This is an automated administrative notification. Please do not reply.</small>
            </div>
        </td>
    </tr>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('emails.layout.master_layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/whistleworks-admin/htdocs/admin.whistleworks.org/resources/views/emails/user-register/admin-registration.blade.php ENDPATH**/ ?>