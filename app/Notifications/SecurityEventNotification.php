<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SecurityEventNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $event,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $eventLabels = [
            'login' => 'New Login',
            'failed_login' => 'Failed Login Attempt',
            '2fa_enabled' => 'Two-Factor Authentication Enabled',
            '2fa_disabled' => 'Two-Factor Authentication Disabled',
            'password_changed' => 'Password Changed',
            'email_changed' => 'Email Address Changed',
            'device_added' => 'New Device Access',
        ];

        $eventMessage = $eventLabels[$this->event] ?? 'Security Event';

        $mail = (new MailMessage())
            ->subject("Security Alert: {$eventMessage}")
            ->greeting("Hello {$notifiable->name},")
            ->line("A security event occurred on your account.");

        if ($this->event === 'login') {
            $mail->line("**New login detected**")
                ->line("IP Address: {$this->ipAddress}")
                ->line("If this wasn't you, please change your password immediately.");
        } elseif ($this->event === 'failed_login') {
            $mail->line("**Failed login attempt detected**")
                ->line("IP Address: {$this->ipAddress}")
                ->line("If this wasn't you, please ensure your password is secure.");
        } elseif ($this->event === 'password_changed') {
            $mail->line("**Your password has been changed**")
                ->line("If you didn't make this change, please contact us immediately.");
        } elseif ($this->event === 'email_changed') {
            $mail->line("**Your email address has been changed**")
                ->line("If you didn't authorize this change, please contact us immediately.");
        } elseif ($this->event === '2fa_enabled') {
            $mail->line("**Two-Factor Authentication has been enabled**")
                ->line("Your account is now more secure.");
        } elseif ($this->event === '2fa_disabled') {
            $mail->line("**Two-Factor Authentication has been disabled**")
                ->line("Consider re-enabling it to keep your account secure.");
        }

        return $mail
            ->action('Review Account Security', url('/account/security'))
            ->line('Thank you for using Electroteca!');
    }
}
