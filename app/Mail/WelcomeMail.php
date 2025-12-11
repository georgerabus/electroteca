<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Support\Facades\URL;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        // Create a temporary signed verification URL valid for 48 hours
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addHours(48),
            ['id' => $this->user->id, 'hash' => sha1($this->user->email)]
        );

        return $this->subject('Verify your email for ' . config('app.name'))
            ->view('emails.welcome')
            ->with([
                'user' => $this->user,
                'verificationUrl' => $verificationUrl,
            ]);
    }
}
