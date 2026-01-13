<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminPasswordResetNotification extends Notification
{
    /**
     * The password reset token.
     */
    public string $token;

    /**
     * The password reset URL.
     */
    public string $url;

    /**
     * Create a new notification instance.
     * Note: This notification is sent synchronously for immediate delivery.
     */
    public function __construct(string $token, string $url)
    {
        $this->token = $token;
        $this->url = $url;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $expireMinutes = config('auth.passwords.users.expire', 60);

        return (new MailMessage)
            ->subject('Passwort zurücksetzen | Password Reset - AskProAI')
            ->view('emails.auth.password-reset', [
                'url' => $this->url,
                'user' => $notifiable,
                'expireMinutes' => $expireMinutes,
            ]);
    }
}
