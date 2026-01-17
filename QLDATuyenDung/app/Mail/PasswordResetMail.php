<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
//
class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $url;

    public function __construct($user, $token)
    {
        $this->user = $user; // object user
        $this->url = url("/reset-password?token=$token&email=" . $user->email);
    }

    public function build()
    {
        return $this->subject('Reset your password')
            ->view('emails.password-reset')
            ->with([
                'user' => $this->user,
                'url' => $this->url,
            ]);
    }
}
