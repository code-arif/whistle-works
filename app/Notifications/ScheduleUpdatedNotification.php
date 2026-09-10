<?php

namespace App\Notifications;

use App\Channels\TwilioChannel;
use App\Channels\TwilioMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ScheduleUpdatedNotification extends Notification
{
    use Queueable;

    protected $camp;
    protected $director;
    protected $schedule;
    protected $changeType; // 'slots_added', 'slots_removed', 'assignments_changed'

    public function __construct($camp, $director, $schedule, $changeType = 'assignments_changed')
    {
        $this->camp = $camp;
        $this->director = $director;
        $this->schedule = $schedule;
        $this->changeType = $changeType;
    }

    public function via($notifiable)
    {
        return ['database', TwilioChannel::class];
    }

    public function toArray($notifiable)
    {
        $messages = [
            'slots_added' => 'New game slots have been added to the schedule.',
            'slots_removed' => 'Some game slots have been removed from the schedule.',
            'assignments_changed' => 'The schedule has been updated. Please check your assignments.',
        ];

        return [
            'type' => 'schedule_updated',

            'title' => 'Schedule Updated',

            'message' => "The schedule for {$this->camp->camp_name} has been updated. " . ($messages[$this->changeType] ?? $messages['assignments_changed']),

            'change_type' => $this->changeType,

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
                'status' => $this->schedule->status,
                'total_slots' => $this->schedule->gameSlots()->count(),
            ],

            'updated_by' => [
                'id' => $this->director->id,
                'name' => $this->director->first_name . ' ' . $this->director->last_name,
                'role' => 'Director',
            ],

            'action_url' => "/referee/camp/{$this->camp->id}/assigned-slots",
        ];
    }

    public function toTwilio($notifiable): TwilioMessage
    {
        $messages = [
            'slots_added' => 'New game added.',
            'slots_removed' => 'Some game removed.',
            'assignments_changed' => 'Schedule updated. Please check assignments.',
        ];

        $msg = $messages[$this->changeType] ?? $messages['assignments_changed'];
        $body = "Hi {$notifiable->first_name}, the schedule for {$this->camp->camp_name} has been updated. {$msg}";

        return (new TwilioMessage)->content($body);
    }
}
