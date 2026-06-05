<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A throwaway message the Email settings tab sends so an admin can confirm the
 * configured SMTP transport actually delivers.
 */
class TestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function envelope(): Envelope
    {
        return new Envelope(subject: config('app.name').' — test email');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.test');
    }
}
