<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerMagicLinkNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $token;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $url = route('portal.magic-link.verify', ['token' => $this->token]);
        $company = $notifiable->company;
        
        return (new MailMessage)
            ->subject('Ihr Login-Link für ' . $company->name)
            ->greeting('Hallo ' . $notifiable->first_name . ',')
            ->line('Sie haben einen Login-Link für das Kundenportal angefordert.')
            ->action('Jetzt einloggen', $url)
            ->line('Dieser Link ist 24 Stunden gültig.')
            ->line('Falls Sie diesen Link nicht angefordert haben, können Sie diese E-Mail ignorieren.')
            ->salutation('Mit freundlichen Grüßen, ' . $company->name);
    }
}