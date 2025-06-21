<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Auth\Notifications\ResetPassword;

class CustomerResetPasswordNotification extends ResetPassword implements ShouldQueue
{
    use Queueable;

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $company = $notifiable->company;
        $url = $this->resetUrl($notifiable);
        
        return (new MailMessage)
            ->subject('Passwort zurücksetzen - ' . $company->name)
            ->greeting('Hallo ' . $notifiable->first_name . ',')
            ->line('Sie erhalten diese E-Mail, weil wir eine Anfrage zum Zurücksetzen des Passworts für Ihr Konto erhalten haben.')
            ->action('Passwort zurücksetzen', $url)
            ->line('Dieser Link läuft in ' . config('auth.passwords.customers.expire') . ' Minuten ab.')
            ->line('Falls Sie kein neues Passwort angefordert haben, können Sie diese E-Mail ignorieren.')
            ->salutation('Mit freundlichen Grüßen, ' . $company->name);
    }
    
    /**
     * Get the reset URL for the given notifiable.
     */
    protected function resetUrl($notifiable): string
    {
        return url(route('portal.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));
    }
}