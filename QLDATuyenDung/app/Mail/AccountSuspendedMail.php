<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
//
class AccountSuspendedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $reason;
    public $suspendedAt;
    public $suspendedBy;

    public function __construct($user, $reason = null, $suspendedBy = null)
    {
        $this->user = $user;
        $this->reason = $reason;
        $this->suspendedAt = $user->suspended_at ?? now();
        $this->suspendedBy = $suspendedBy;
    }

    public function build()
    {
        return $this->subject('Thông báo: Tài khoản của bạn đã bị khóa')
            ->view('emails.account-suspended')
            ->with([
                'user' => $this->user,
                'reason' => $this->reason,
                'suspendedAt' => $this->suspendedAt,
            ]);
    }
}
