<?php

namespace App\Notifications;

use App\Models\UserInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * User Invitation Email Notification
 *
 * Sends invitation email to customer with:
 * - Role-specific permissions
 * - Short, readable invitation link
 * - Personal message (if provided)
 * - Expiry information
 */
class UserInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public UserInvitation $invitation
    ) {
        // Queue on 'notifications' queue with 1 minute delay
        // to allow transaction to commit
        $this->queue = 'notifications';
        $this->delay = now()->addSeconds(5);
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
        return (new MailMessage)
            ->subject('Einladung zum Kundenportal - ' . $this->invitation->company->name)
            ->markdown('emails.customer-portal.invitation', [
                'invitation' => $this->invitation,
            ]);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invitation_id' => $this->invitation->id,
            'email' => $this->invitation->email,
            'token' => $this->invitation->token,
            'expires_at' => $this->invitation->expires_at,
        ];
    }

    /**
     * Determine which queues should be used for each notification channel.
     */
    public function viaQueues(): array
    {
        return [
            'mail' => 'notifications',
        ];
    }
}
