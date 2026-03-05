<?php

namespace App\Mail;


use App\Models\CampPayment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Director\Models\Camp;
use Modules\Director\Models\CampRefereeCheckin;

class RegistrationConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $camp;
    public $registration;
    public $subject;

    public $payment;

    public function __construct(User $user, Camp $camp, CampRefereeCheckin $registration, CampPayment $payment)
    {
        $this->user = $user;
        $this->camp = $camp;
        $this->registration = $registration;
        $this->payment = $payment;
    }

    public function build()
    {
        return $this->subject($this->subject)
            ->view('emails.registration.confirmation')
            ->with([
                'user' => $this->user,
                'camp' => $this->camp,
                'registration' => $this->registration,
            ]);
    }
}
