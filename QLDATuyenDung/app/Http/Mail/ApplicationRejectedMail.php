<?php

namespace App\Mail;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
//
class ApplicationRejectedMail extends Mailable
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
            subject: 'Thông báo về hồ sơ ứng tuyển của bạn',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.application-rejected',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
