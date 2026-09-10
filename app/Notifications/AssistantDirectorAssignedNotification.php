<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class AssistantDirectorAssignedNotification extends Notification
{
    use Queueable;

    protected $camp;
    protected $director;
    protected $assistantDirector;

    public function __construct($camp, $director, $assistantDirector = null)
    {
        $this->camp              = $camp;
        $this->director          = $director;
        $this->assistantDirector = $assistantDirector;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification for database storage.
     */
    public function toArray($notifiable): array
    {
        $campName = $this->camp->camp_name ?? 'a camp';
        $assistantName = $this->assistantDirector ? ($this->assistantDirector->first_name . ' ' . $this->assistantDirector->last_name) : 'Assistant Director';

        return [
            'type'                  => 'assistant_director_assigned',
            'title'                 => 'Assistant Director Assignment',
            'message'               => "You have been added as an assistant director to {$campName} added by {$assistantName}.",
            'camp_id'               => $this->camp ? $this->camp->id : null,
            'director_id'           => $this->director ? $this->director->id : null,
            'assistant_director_id' => $this->assistantDirector ? $this->assistantDirector->id : null,
        ];
    }
}
