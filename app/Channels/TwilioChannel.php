<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;
use Throwable;

/**
 * Custom Laravel notification channel for Twilio SMS.
 *
 * Usage in any Notification class:
 *
 *   public function via($notifiable): array
 *   {
 *       return ['database', TwilioChannel::class];
 *   }
 *
 *   public function toTwilio($notifiable): TwilioMessage
 *   {
 *       return (new TwilioMessage)->content('Your SMS text here.');
 *   }
 *
 * The notifiable model (User) must expose a routeNotificationForTwilio() method
 * that returns the E.164 phone number, e.g. "+8801712345678".
 * If the method returns null / empty the SMS is silently skipped.
 */
class TwilioChannel
{
    private ?Client $client = null;
    private ?string $from = null;

    public function __construct()
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $this->from = config('services.twilio.from');

        if ($sid && $token) {
            $this->client = new Client($sid, $token);
        }
    }

    /**
     * Send the given notification.
     */
    public function send(mixed $notifiable, Notification $notification): void
    {
        // 1. Skip test users (IDs 3 through 90)
        if (isset($notifiable->id) && $notifiable->id >= 3 && $notifiable->id <= 90) {
            return;
        }

        // 2. Check if user has opted out of SMS notifications
        if (isset($notifiable->receive_sms_notifications) && !$notifiable->receive_sms_notifications) {
            return;
        }

        // 3. Resolve the recipient phone number from the notifiable model
        $to = $notifiable->routeNotificationFor('twilio', $notification);

        if (empty($to)) {
            return;
        }

        // 2. Build the TwilioMessage DTO
        if (!method_exists($notification, 'toTwilio')) {
            Log::warning('TwilioChannel: notification has no toTwilio() method', [
                'class' => get_class($notification),
            ]);
            return;
        }

        /** @var TwilioMessage $message */
        $message = $notification->toTwilio($notifiable);

        if (empty(trim($message->content))) {
            return;
        }

        // 3. Dispatch via Twilio REST API
        if (!$this->client || !$this->from) {
            Log::warning('TwilioChannel: skipped — missing Twilio configuration (SID, Token, or From)', [
                'has_client' => !!$this->client,
                'has_from'   => !!$this->from,
            ]);
            return;
        }

        try {
            $this->client->messages->create($to, [
                'from' => $this->from,
                'body' => $message->content,
            ]);

        } catch (Throwable $e) {
            // Log and swallow — never let an SMS failure break the main transaction
            Log::error('TwilioChannel: failed to send SMS', [
                'to'    => $to,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
