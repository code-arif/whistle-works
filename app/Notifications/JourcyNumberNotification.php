<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class JourcyNumberNotification extends Notification
{
    use Queueable;

    protected $referee;
    protected $camp;
    protected $director;
    protected $jourcyNumber;

    public function __construct($referee, $camp, $director, $jourcyNumber)
    {
        $this->referee = $referee;
        $this->camp = $camp;
        $this->director = $director;
        $this->jourcyNumber = $jourcyNumber;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'jourcy_number_assigned',

            'title' => 'New Jourcy Number Assigned',

            'message' => "You have been assigned a new Jourcy Number for the camp.",

            'jourcy_number' => $this->jourcyNumber,

            'camp' => [
                'id' => $this->camp->id,
                'name' => $this->camp->camp_name,
            ],

            'assigned_by' => [
                'id' => $this->director->id,
                'name' => $this->director->first_name . ' ' . $this->director->last_name,
                'role' => 'Director',
            ],
        ];
    }
}
