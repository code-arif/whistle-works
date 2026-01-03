<?php

namespace App\Mail;

use Modules\Director\Models\Camp;
use App\Models\CampPayment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentSuccessfulMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $camp;
    public $payment;
    public $subject;

    public function __construct(User $user, Camp $camp, CampPayment $payment, $subject = null)
    {
        $this->user = $user;
        $this->camp = $camp;
        $this->payment = $payment;
        $this->subject = $subject ?? 'Payment Successful - Whistle Works';
    }

    public function build()
    {
        return $this->subject($this->subject)
                    ->view('emails.payment.successful')
                    ->with([
                        'user' => $this->user,
                        'camp' => $this->camp,
                        'payment' => $this->payment,
                    ]);
    }
}
