<?php $__env->startSection('header'); ?>
    <tr>
        <td class="header">
            <!-- Logo -->
            <a href="<?php echo e(config('app.url')); ?>" style="display: inline-block;">
                <img src="<?php echo e($message->embed(public_path('images/logo.png'))); ?>" alt="Whistle Works" class="logo"
                    style="max-width: 200px; height: auto;">
            </a>
        </td>
    </tr>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <!-- Check-in Notification Header -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 25px;">
        <tr>
            <td align="center">
                <div
                    style="
                background-color: #4CAF50;
                width: 70px;
                height: 70px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 15px;
            ">
                    <span style="color: #ffffff; font-size: 32px; font-weight: bold;">✓</span>
                </div>
                <h1 style="color: #333333; font-size: 24px; font-weight: bold; margin: 0;">
                    New Camp Check-in
                </h1>
            </td>
        </tr>
    </table>

    <!-- Greeting -->
    <p style="margin-bottom: 20px; font-size: 16px; color: #333333;">
        Hello <strong><?php echo e($director->first_name); ?></strong>,
    </p>

    <!-- Main Message -->
    <p style="margin-bottom: 25px; font-size: 15px; color: #555555; line-height: 1.6;">
        A referee has successfully checked into your camp.
    </p>

    <!-- Referee Info Card -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
        style="margin: 30px 0; border: 1px solid #00aeef; border-radius: 8px; overflow: hidden;">
        <tr>
            <td style="padding: 25px; background-color: #f8fdff;">
                <h3
                    style="color: #00aeef; font-size: 18px; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #00aeef;">
                    Referee Information
                </h3>

                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td style="padding: 10px 0;">
                            <span
                                style="color: #666666; font-size: 14px; min-width: 120px; display: inline-block;">Name:</span>
                            <strong style="color: #333333; font-size: 14px;">
                                <?php echo e($referee->first_name); ?> <?php echo e($referee->last_name); ?>

                            </strong>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 0;">
                            <span
                                style="color: #666666; font-size: 14px; min-width: 120px; display: inline-block;">Username:</span>
                            <strong style="color: #333333; font-size: 14px;">
                                <?php echo e($referee->username); ?>

                            </strong>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 0;">
                            <span
                                style="color: #666666; font-size: 14px; min-width: 120px; display: inline-block;">Email:</span>
                            <strong style="color: #333333; font-size: 14px;">
                                <?php echo e($referee->email); ?>

                            </strong>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 0;">
                            <span style="color: #666666; font-size: 14px; min-width: 120px; display: inline-block;">Check-in
                                Time:</span>
                            <strong style="color: #333333; font-size: 14px;">
                                <?php echo e($registration->checked_in_at->format('F j, Y \a\t h:i A')); ?>

                            </strong>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Camp Info Card -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
        style="margin: 20px 0; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden;">
        <tr>
            <td style="padding: 25px;">
                <h3
                    style="color: #333333; font-size: 18px; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #00aeef;">
                    Camp Details
                </h3>

                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td style="padding: 10px 0;">
                            <span style="color: #666666; font-size: 14px; min-width: 120px; display: inline-block;">Camp
                                Name:</span>
                            <strong style="color: #333333; font-size: 14px;"><?php echo e($camp->camp_name); ?></strong>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 0;">
                            <span
                                style="color: #666666; font-size: 14px; min-width: 120px; display: inline-block;">Location:</span>
                            <strong style="color: #333333; font-size: 14px;"><?php echo e($camp->location); ?></strong>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 0;">
                            <span
                                style="color: #666666; font-size: 14px; min-width: 120px; display: inline-block;">Dates:</span>
                            <strong style="color: #333333; font-size: 14px;">
                                <?php echo e(\Carbon\Carbon::parse($camp->start_date)->format('M j')); ?> -
                                <?php echo e(\Carbon\Carbon::parse($camp->end_date)->format('M j, Y')); ?>

                            </strong>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 0;">
                            <span style="color: #666666; font-size: 14px; min-width: 120px; display: inline-block;">Check-in
                                Status:</span>
                            <strong style="color: #4CAF50; font-size: 14px;">✓ Completed</strong>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Quick Stats -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 25px 0;">
        <tr>
            <td>
                <h3 style="color: #333333; font-size: 16px; margin-bottom: 15px;">
                    📊 Quick Stats
                </h3>
                <div style="display: flex; flex-wrap: wrap; gap: 15px;">
                    <div
                        style="flex: 1; min-width: 150px; background-color: #f8f9fa; padding: 15px; border-radius: 6px; text-align: center;">
                        <div style="font-size: 24px; font-weight: bold; color: #00aeef;">
                            #<?php echo e($registration->id); ?>

                        </div>
                        <div style="font-size: 12px; color: #666666;">
                            Check-in ID
                        </div>
                    </div>
                    <div
                        style="flex: 1; min-width: 150px; background-color: #f8f9fa; padding: 15px; border-radius: 6px; text-align: center;">
                        <div style="font-size: 24px; font-weight: bold; color: #4CAF50;">
                            <?php echo e($registration->checked_in_at->format('h:i A')); ?>

                        </div>
                        <div style="font-size: 12px; color: #666666;">
                            Check-in Time
                        </div>
                    </div>
                    <div
                        style="flex: 1; min-width: 150px; background-color: #f8f9fa; padding: 15px; border-radius: 6px; text-align: center;">
                        <div style="font-size: 24px; font-weight: bold; color: #ff9800;">
                            Day 1
                        </div>
                        <div style="font-size: 12px; color: #666666;">
                            Camp Day
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Action Buttons -->
    

    <!-- Additional Notes -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
        style="margin: 20px 0; background-color: #f8f9fa; border-radius: 8px; padding: 20px;">
        <tr>
            <td>
                <h3 style="color: #333333; font-size: 14px; margin-bottom: 10px;">
                    ℹ️ Note:
                </h3>
                <p style="margin: 0; font-size: 13px; color: #666666; line-height: 1.5;">
                    This is an automated notification. The referee has been marked as checked-in in the system.
                    You can view detailed attendance reports from your camp dashboard.
                </p>
            </td>
        </tr>
    </table>

    <!-- Closing -->
    <p style="margin-top: 30px; font-size: 15px; color: #333333;">
        Best regards,<br>
        <strong>The Whistle Works Team</strong>
    </p>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('footer'); ?>
    <tr>
        <td class="footer">
            <!-- Quick Links -->
            <div class="footer-links">
                <a href="<?php echo e(config('app.frontend_url')); ?>/about-us"
                    style="color: #00aeef; text-decoration: none; margin: 0 10px;">About Us</a>
                <a href="<?php echo e(config('app.frontend_url')); ?>"
                    style="color: #00aeef; text-decoration: none; margin: 0 10px;">Home</a>
                <a href="<?php echo e(config('app.frontend_url')); ?>/all-camps"
                    style="color: #00aeef; text-decoration: none; margin: 0 10px;">All Camps</a>
            </div>

            <!-- Notification Preferences -->
            <div style="margin: 15px 0; font-size: 12px; color: #666666; text-align: center;">
                You're receiving this email because you're the director of "<?php echo e($camp->camp_name); ?>".<br>
                <a href="<?php echo e(config('app.url')); ?>/director/settings/notifications" style="color: #999999;">Manage
                    notification preferences</a>
            </div>

            <!-- Copyright -->
            <div class="copyright">
                &copy; <?php echo e(date('Y')); ?> Whistle Works Inc. All rights reserved.<br>
                <small>Camp check-in notification for <?php echo e($director->email); ?></small>
            </div>
        </td>
    </tr>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('emails.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/whistleworks-admin/htdocs/admin.whistleworks.org/resources/views/emails/camp/checkin-notification.blade.php ENDPATH**/ ?>