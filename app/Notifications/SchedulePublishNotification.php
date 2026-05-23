<?php

namespace App\Notifications;

use App\Channels\TwilioChannel;
use App\Channels\TwilioMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SchedulePublishNotification extends Notification
{
    use Queueable;

    protected $camp;
    protected $director;
    protected $schedule;

    public function __construct($camp, $director, $schedule)
    {
        $this->camp = $camp;
        $this->director = $director;
        $this->schedule = $schedule;
    }

    public function via($notifiable)
    {
        return ['database', TwilioChannel::class];
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'schedule_published',

            'title' => 'Camp Schedule Published',

            'message' => "The schedule for {$this->camp->camp_name} has been published. You can now view your assigned game slots.",

            'camp' => [
                'id' => $this->camp->id,
                'name' => $this->camp->camp_name,
                'logo' => $this->camp->camp_logo ? asset($this->camp->camp_logo) : asset('default/no_image.webp'),
                'location' => $this->camp->location,
                'start_date' => $this->camp->start_date,
                'end_date' => $this->camp->end_date,
                'address' => $this->camp->address ?? null,
            ],

            'schedule' => [
                'id' => $this->schedule->id,
                'game_duration' => $this->schedule->game_duration,
                'total_slots' => $this->schedule->gameSlots()->count(),
            ],

            'published_by' => [
                'id' => $this->director->id,
                'name' => $this->director->first_name . ' ' . $this->director->last_name,
                'role' => 'Director',
            ],

            'action_url' => "/referee/camp/{$this->camp->id}/assigned-slots", // Frontend URL
        ];
    }

    public function toTwilio($notifiable): TwilioMessage
    {
        $body = "Hi {$notifiable->first_name}, the schedule for {$this->camp->camp_name} has been published. You can now view your assigned game slots.";

        return (new TwilioMessage)->content($body);
    }
}
