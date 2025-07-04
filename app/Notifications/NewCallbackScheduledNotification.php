<?php

namespace App\Notifications;

use App\Models\Call;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewCallbackScheduledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $call;
    protected $callbackDateTime;

    /**
     * Create a new notification instance.
     */
    public function __construct(Call $call, Carbon $callbackDateTime)
    {
        $this->call = $call;
        $this->callbackDateTime = $callbackDateTime;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        $channels = [];
        
        if ($notifiable->notification_preferences['email'] ?? true) {
            $channels[] = 'mail';
        }
        
        if ($notifiable->notification_preferences['database'] ?? true) {
            $channels[] = 'database';
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $url = config('app.url') . '/business/calls/' . $this->call->id;
        
        return (new MailMessage)
            ->subject('Rückruf geplant für ' . $this->callbackDateTime->format('d.m.Y H:i'))
            ->greeting('Hallo ' . $notifiable->name . ',')
            ->line('Ein Rückruf wurde geplant.')
            ->line('**Rückrufdetails:**')
            ->line('- Datum/Zeit: ' . $this->callbackDateTime->format('d.m.Y H:i'))
            ->line('- Telefonnummer: ' . $this->call->phone_number)
            ->when($this->call->customer, function ($message) {
                return $message->line('- Kunde: ' . $this->call->customer->name);
            })
            ->when($this->call->callPortalData->callback_notes, function ($message) {
                return $message->line('- Notizen: ' . $this->call->callPortalData->callback_notes);
            })
            ->action('Anruf anzeigen', $url)
            ->line('Bitte führen Sie den Rückruf zum geplanten Zeitpunkt durch.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'callback_scheduled',
            'call_id' => $this->call->id,
            'callback_datetime' => $this->callbackDateTime->toIso8601String(),
            'call' => [
                'phone_number' => $this->call->phone_number,
                'customer_name' => $this->call->customer->name ?? null,
                'callback_notes' => $this->call->callPortalData->callback_notes ?? null
            ]
        ];
    }
}