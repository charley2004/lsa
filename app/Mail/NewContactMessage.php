<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// Deliberately NOT queued: queuing would need a `queue:work` worker running
// continuously, which isn't part of this setup. Sent synchronously instead so
// mail just works the moment real SMTP credentials are in .env, with no extra
// moving part to forget to start. A contact-form volume this low (rate-limited
// to 5/min) doesn't need async delivery anyway.
class NewContactMessage extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ContactMessage $contactMessage)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New LSA contact message: ' . $this->contactMessage->subject,
            replyTo: [$this->contactMessage->email],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.contact-message',
        );
    }
}
