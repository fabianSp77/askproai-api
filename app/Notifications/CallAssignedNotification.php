<?php

namespace App\Notifications;

use App\Models\Call;
use App\Models\PortalUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CallAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $call;
    protected $assignedBy;

    /**
     * Create a new notification instance.
     */
    public function __construct(Call $call, PortalUser $assignedBy)
    {
        $this->call = $call;
        $this->assignedBy = $assignedBy;
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
            ->subject('Neuer Anruf zugewiesen')
            ->greeting('Hallo ' . $notifiable->name . ',')
            ->line('Ihnen wurde ein neuer Anruf zugewiesen von ' . $this->assignedBy->name . '.')
            ->line('**Anrufdetails:**')
            ->line('- Telefonnummer: ' . $this->call->phone_number)
            ->line('- Datum/Zeit: ' . $this->call->created_at->format('d.m.Y H:i'))
            ->line('- Dauer: ' . gmdate('i:s', $this->call->duration_sec ?? 0))
            ->action('Anruf anzeigen', $url)
            ->line('Bitte bearbeiten Sie diesen Anruf zeitnah.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'call_assigned',
            'call_id' => $this->call->id,
            'assigned_by' => [
                'id' => $this->assignedBy->id,
                'name' => $this->assignedBy->name
            ],
            'call' => [
                'phone_number' => $this->call->phone_number,
                'created_at' => $this->call->created_at->toIso8601String(),
                'duration_sec' => $this->call->duration_sec
            ]
        ];
    }
}