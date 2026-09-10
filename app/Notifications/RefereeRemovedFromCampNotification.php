<?php

namespace App\Notifications;

use App\Channels\TwilioChannel;
use App\Channels\TwilioMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RefereeRemovedFromCampNotification extends Notification
{
    use Queueable;

    protected $camp;
    protected $director;

    public function __construct($camp, $director)
    {
        $this->camp = $camp;
        $this->director = $director;
    }

    public function via($notifiable)
    {
        return ['database', TwilioChannel::class];
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'referee_removed_from_camp',

            'title' => 'Removed from Camp',

            'message' => 'You have been removed from a camp by the camp director.',

            'camp' => [
                'id' => $this->camp->id,
                'name' => $this->camp->camp_name,
            ],

            'removed_by' => [
                'id' => $this->director->id,
                'name' => $this->director->first_name . ' ' . $this->director->last_name,
                'role' => 'Director',
            ],
        ];
    }

    public function toTwilio($notifiable): TwilioMessage
    {
        $body = "Hi {$notifiable->first_name}, you have been removed from the camp: {$this->camp->camp_name}.";

        return (new TwilioMessage)->content($body);
    }
}
