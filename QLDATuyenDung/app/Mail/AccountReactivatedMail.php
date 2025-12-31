<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
//
class AccountReactivatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function build()
    {
        return $this->subject('Thông báo: Tài khoản của bạn đã được kích hoạt lại')
            ->view('emails.account-reactivated')
            ->with([
                'user' => $this->user,
            ]);
    }
}
