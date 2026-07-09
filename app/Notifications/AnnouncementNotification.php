<?php

namespace App\Notifications;

use App\Channels\TwilioChannel;
use App\Channels\TwilioMessage;
use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AnnouncementNotification extends Notification
{
    use Queueable;

    protected $announcement;

    public function __construct(Announcement $announcement)
    {
        $this->announcement = $announcement;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        return ['database', TwilioChannel::class];
    }

    /**
     * Get the array representation of the notification (for database).
     */
    public function toArray($notifiable)
    {
        return [
            'announcement_id' => $this->announcement->id,
            'subject' => $this->announcement->subject,
            'message' => $this->announcement->message,
            'created_by' => $this->announcement->created_by,
            'creator_name' => $this->announcement->creator->first_name ?? 'Director',
            'sent_at' => $this->announcement->sent_at,
            'type' => 'announcement', // Distinguish from other notification types
        ];
    }

    public function toTwilio($notifiable): TwilioMessage
    {
        $subject = $this->announcement->subject;
        $message = html_entity_decode(strip_tags($this->announcement->message), ENT_QUOTES, 'UTF-8');
        $creator = $this->announcement->creator->first_name . ' ' . $this->announcement->creator->last_name ?? 'Director';

        $body = "New Announcement from {$creator}: {$subject}\n\n{$message}";

        if (mb_strlen($body) > 400) {
            $body = "A new announcement has been sent by \"{$creator}\". Please log in to Whistle Works for more details.";
        }

        return (new TwilioMessage)->content($body);
    }
}
