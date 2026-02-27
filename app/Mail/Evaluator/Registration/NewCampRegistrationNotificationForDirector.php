<?php

namespace App\Mail\Evaluator\Registration;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Director\Models\Camp;

class NewCampRegistrationNotificationForDirector extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $evaluator;
    public $camp;

    public function __construct(User $evaluator, Camp $camp)
    {
        $this->evaluator = $evaluator;
        $this->camp = $camp;
    }

    public function build()
    {
        return $this->subject('New Evaluator Registered for Your Camp')
            ->view('emails.director.evaluator-registration')
            ->with([
                'evaluator' => $this->evaluator,
                'camp' => $this->camp,
            ]);
    }
}
