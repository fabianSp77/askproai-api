<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Auth\Notifications\VerifyEmail;

class CustomerVerifyEmailNotification extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $company = $notifiable->company;
        $verificationUrl = $this->verificationUrl($notifiable);
        
        return (new MailMessage)
            ->subject('E-Mail-Adresse bestätigen - ' . $company->name)
            ->greeting('Hallo ' . $notifiable->first_name . ',')
            ->line('Bitte klicken Sie auf den Button unten, um Ihre E-Mail-Adresse zu bestätigen.')
            ->action('E-Mail-Adresse bestätigen', $verificationUrl)
            ->line('Falls Sie kein Konto erstellt haben, können Sie diese E-Mail ignorieren.')
            ->salutation('Mit freundlichen Grüßen, ' . $company->name);
    }
    
    /**
     * Get the verification URL for the given notifiable.
     */
    protected function verificationUrl($notifiable): string
    {
        return \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'portal.verification.verify',
            \Carbon\Carbon::now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}