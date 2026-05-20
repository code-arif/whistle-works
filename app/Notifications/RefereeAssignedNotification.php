<?php

namespace App\Notifications;

use App\Channels\TwilioChannel;
use App\Channels\TwilioMessage;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Notification when referee is assigned to a game slot.
 *
 * Channels:
 *  - database  (unchanged — existing behaviour preserved)
 *  - TwilioChannel  (NEW — sends an SMS to the referee's phone number)
 */
class RefereeAssignedNotification extends Notification
{
    use Queueable;

    protected $gameSlot;
    protected $camp;
    protected $director;
    protected $assignmentType; // 'individual' or 'crew'
    protected $crewName;

    public function __construct($gameSlot, $camp, $director, $assignmentType = 'individual', $crewName = null)
    {
        $this->gameSlot       = $gameSlot;
        $this->camp           = $camp;
        $this->director       = $director;
        $this->assignmentType = $assignmentType;
        $this->crewName       = $crewName;
    }

    // -------------------------------------------------------------------------
    // Channels
    // -------------------------------------------------------------------------

    public function via($notifiable): array
    {
        // Keep existing database channel; add Twilio SMS channel
        return ['database', TwilioChannel::class];
    }

    // -------------------------------------------------------------------------
    // Database payload (UNCHANGED — identical to original)
    // -------------------------------------------------------------------------

    public function toArray($notifiable): array
    {
        $message = $this->assignmentType === 'crew'
            ? "You have been assigned to a game as part of {$this->crewName} crew."
            : "You have been assigned to a new game slot.";

        return [
            'type'            => 'referee_assigned',
            'title'           => 'New Game Assignment',
            'message'         => $message,
            'assignment_type' => $this->assignmentType,
            'crew_name'       => $this->crewName,

            'game_slot' => [
                'id'           => $this->gameSlot->id,
                'date'         => $this->gameSlot->game_date,
                'start_time'   => $this->gameSlot->start_time,
                'end_time'     => $this->gameSlot->end_time,
                'court_name'   => $this->gameSlot->court_name,
                'court_number' => $this->gameSlot->court_number,
            ],

            'location' => [
                'name'      => $this->gameSlot->location->location_name ?? 'N/A',
                'latitude'  => $this->gameSlot->location->latitude     ?? null,
                'longitude' => $this->gameSlot->location->longitude    ?? null,
                'address'   => $this->gameSlot->location->address      ?? null,
            ],

            'camp' => [
                'id'   => $this->camp->id,
                'name' => $this->camp->camp_name,
                'logo' => $this->camp->camp_logo
                    ? asset($this->camp->camp_logo)
                    : asset('default/no_image.webp'),
            ],

            'assigned_by' => [
                'id'   => $this->director->id,
                'name' => $this->director->first_name . ' ' . $this->director->last_name,
                'role' => 'Director',
            ],

            'action_url' => "/referee/camp/{$this->camp->id}/assigned-slots",
        ];
    }

    // -------------------------------------------------------------------------
    // Twilio SMS payload (NEW)
    // -------------------------------------------------------------------------

    /**
     * Build the SMS body sent to the referee.
     *
     * Kept intentionally short — SMS has a 160-char soft limit per segment.
     */
    public function toTwilio($notifiable): TwilioMessage
    {
        $date      = Carbon::parse($this->gameSlot->game_date)->format('M d, Y');
        $startTime = Carbon::parse($this->gameSlot->start_time)->format('h:i A');
        $endTime   = Carbon::parse($this->gameSlot->end_time)->format('h:i A');
        $court     = $this->gameSlot->court_name;
        $campName  = $this->camp->camp_name;

        Log::info($this->assignmentType);

        if ($this->assignmentType === 'crew') {
            $body = "Hi {$notifiable->first_name}, you've been assigned to {$campName} as part of the {$this->crewName} crew.\n"
                . "Court: {$court} | {$date} | {$startTime} - {$endTime}";
        } else {
            $body = "Hi {$notifiable->first_name}, you've been assigned to a game at {$campName}.\n"
                . "Court: {$court} | {$date} | {$startTime} - {$endTime}";
        }

        return (new TwilioMessage)->content($body);
    }
}
