<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SystemAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected array $alerts;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $alerts)
    {
        $this->alerts = $alerts;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('🚨 AskProAI System Alert - ' . count($this->alerts) . ' kritische Probleme')
            ->greeting('Hallo ' . $notifiable->name . ',')
            ->line('Es wurden kritische Systemprobleme erkannt, die Ihre sofortige Aufmerksamkeit erfordern:');

        foreach ($this->alerts as $alert) {
            $message->line('');
            $message->line('**' . $alert['message'] . '**');
            
            if (!empty($alert['context'])) {
                foreach ($alert['context'] as $key => $value) {
                    $message->line('- ' . ucfirst(str_replace('_', ' ', $key)) . ': ' . (is_array($value) ? json_encode($value) : $value));
                }
            }
        }

        $message->line('');
        $message->line('**Empfohlene Aktionen:**');
        $message->line('1. Öffnen Sie das System Monitoring Dashboard');
        $message->line('2. Führen Sie einen vollständigen Preflight Check durch');
        $message->line('3. Überprüfen Sie die Logs für weitere Details');
        
        $message->action('System Monitoring öffnen', url('/admin/system-monitoring-dashboard'));
        
        $message->line('Zeit: ' . now()->format('d.m.Y H:i:s'));

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'system_alert',
            'severity' => 'critical',
            'alert_count' => count($this->alerts),
            'alerts' => $this->alerts,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}