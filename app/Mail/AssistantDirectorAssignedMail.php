<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AssistantDirectorAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $assistantDirector;
    public $director;
    public $camp;

    public function __construct($assistantDirector, $director, $camp)
    {
        $this->assistantDirector = $assistantDirector;
        $this->director          = $director;
        $this->camp              = $camp;
    }

    public function build()
    {
        $campName = $this->camp->camp_name ?? 'Camp';

        return $this->subject("Assigned as Assistant Director for {$campName}")
                    ->view('emails.director.assistant-director-assigned')
                    ->with([
                        'assistantDirector' => $this->assistantDirector,
                        'director'          => $this->director,
                        'camp'              => $this->camp,
                    ]);
    }
}
