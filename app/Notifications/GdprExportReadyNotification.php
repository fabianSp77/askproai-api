<?php

namespace App\Notifications;

use App\Models\GdprRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GdprExportReadyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected GdprRequest $gdprRequest;

    /**
     * Create a new notification instance.
     */
    public function __construct(GdprRequest $gdprRequest)
    {
        $this->gdprRequest = $gdprRequest;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
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
        $downloadUrl = route('portal.privacy.download-export', $this->gdprRequest);
        
        return (new MailMessage)
            ->subject(__('gdpr.emails.export_ready_subject'))
            ->greeting(__('Hallo :name,', ['name' => $notifiable->first_name]))
            ->line(__('gdpr.emails.export_ready_body'))
            ->action(__('Daten herunterladen'), $downloadUrl)
            ->line(__('Der Download-Link ist 7 Tage gültig.'))
            ->line(__('Aus Sicherheitsgründen müssen Sie sich anmelden, um Ihre Daten herunterzuladen.'))
            ->salutation(__('Mit freundlichen Grüßen'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'gdpr_request_id' => $this->gdprRequest->id,
            'type' => 'gdpr_export_ready',
        ];
    }
}