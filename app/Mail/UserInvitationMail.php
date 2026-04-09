<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;

    public function __construct(User $user)
    {
        $this->user = $user->loadMissing(['company', 'role']);
    }

    public function build()
    {
        return $this
            ->subject('You have been invited to VIGIA')
            ->view('emails.users.invitation');
    }
}