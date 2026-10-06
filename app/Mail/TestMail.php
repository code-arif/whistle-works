<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectText,
        public string $contentText
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectText,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px;">' .
                        '<h2 style="color: #0f172a; margin-top: 0; font-size: 20px;">' . htmlspecialchars($this->subjectText) . '</h2>' .
                        '<p style="color: #334155; font-size: 15px; line-height: 1.6;">' . nl2br(htmlspecialchars($this->contentText)) . '</p>' .
                        '<hr style="border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;" />' .
                        '<p style="font-size: 12px; color: #94a3b8; margin: 0;">Sent via Whistle-Works Executive Admin Panel &bull; ' . date('Y-m-d H:i:s') . '</p>' .
                        '</div>'
        );
    }
}
