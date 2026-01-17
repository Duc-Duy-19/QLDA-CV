<?php

namespace App\Mail;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
//
class ApplicationSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Xác nhận đã nhận hồ sơ ứng tuyển',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.application-submitted',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
