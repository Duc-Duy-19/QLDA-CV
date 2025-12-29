<?php

namespace App\Mail;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
//
class ApplicationApprovedMail extends Mailable
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
            subject: 'Chúc mừng! Hồ sơ của bạn đã được chấp nhận',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.application-approved',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
