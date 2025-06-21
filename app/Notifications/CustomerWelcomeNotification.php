<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerWelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $password;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $password)
    {
        $this->password = $password;
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
        $company = $notifiable->company;
        $portalUrl = route('portal.login');
        
        return (new MailMessage)
            ->subject('Willkommen im Kundenportal von ' . $company->name)
            ->greeting('Hallo ' . $notifiable->first_name . ',')
            ->line('Ihr Zugang zum Kundenportal wurde erfolgreich eingerichtet!')
            ->line('Sie können sich nun mit folgenden Zugangsdaten anmelden:')
            ->line('**E-Mail:** ' . $notifiable->email)
            ->line('**Passwort:** ' . $this->password)
            ->action('Zum Kundenportal', $portalUrl)
            ->line('Im Kundenportal können Sie:')
            ->line('• Ihre Termine einsehen und verwalten')
            ->line('• Rechnungen herunterladen')
            ->line('• Ihr Profil aktualisieren')
            ->line('Bitte ändern Sie Ihr Passwort nach der ersten Anmeldung.')
            ->salutation('Mit freundlichen Grüßen, ' . $company->name);
    }
}