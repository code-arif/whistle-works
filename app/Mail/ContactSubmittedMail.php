<?php

namespace App\Mail;

use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ContactSubmittedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $contact;

    public function __construct(Contact $contact)
    {
        $this->contact = $contact;
    }

    public function build()
    {
        $subject = 'New Inquiry: ' . ($this->contact->subject ?: 'General Contact Submission');

        $mail = $this->subject($subject)
                     ->view('emails.contact_submitted')
                     ->with([
                         'contact' => $this->contact,
                     ]);

        if (!empty($this->contact->email)) {
            $mail->replyTo($this->contact->email, $this->contact->name ?: null);
        }

        return $mail;
    }
}
