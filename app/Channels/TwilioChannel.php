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
    private Client $client;
    private string $from;

    public function __construct()
    {
        $this->client = new Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );

        $this->from = config('services.twilio.from');
    }

    /**
     * Send the given notification.
     */
    public function send(mixed $notifiable, Notification $notification): void
    {
        // 1. Resolve the recipient phone number from the notifiable model
        $to = $notifiable->routeNotificationFor('twilio', $notification);

        if (empty($to)) {
            Log::debug('TwilioChannel: skipped — no phone number', [
                'notifiable_id'   => $notifiable->id ?? null,
                'notifiable_type' => get_class($notifiable),
            ]);
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
            Log::debug('TwilioChannel: skipped — empty message body');
            return;
        }

        // 3. Dispatch via Twilio REST API
        try {
            $this->client->messages->create($to, [
                'from' => $this->from,
                'body' => $message->content,
            ]);

            Log::info('TwilioChannel: SMS sent', [
                'to'             => $to,
                'notifiable_id'  => $notifiable->id ?? null,
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
