<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\VonageMessage;
use Illuminate\Notifications\Notification;
use App\Channels\PushNotificationChannel;

class LowBalanceWarning extends Notification implements ShouldQueue
{
    use Queueable;

    protected int $balanceCents;
    protected int $thresholdCents;
    protected bool $criticalLevel;

    public function __construct(int $balanceCents, int $thresholdCents = 500)
    {
        $this->balanceCents = $balanceCents;
        $this->thresholdCents = $thresholdCents;
        $this->criticalLevel = $balanceCents < 200; // Under 2€ is critical
    }

    /**
     * Determine notification urgency and channels
     */
    public function via($notifiable): array
    {
        $channels = ['database'];
        
        // Critical level: use all available channels
        if ($this->criticalLevel) {
            $channels[] = 'mail';
            
            if ($notifiable->phone_verified) {
                $channels[] = 'vonage';
            }
            
            if ($notifiable->notification_preferences['push_enabled'] ?? false) {
                $channels[] = PushNotificationChannel::class;
            }
        } else {
            // Normal warning: respect preferences
            $preferences = $notifiable->notification_preferences ?? [];
            
            if ($preferences['email_warnings'] ?? true) {
                $channels[] = 'mail';
            }
            
            if ($preferences['push_enabled'] ?? false) {
                $channels[] = PushNotificationChannel::class;
            }
        }
        
        return $channels;
    }

    /**
     * Get the mail representation
     */
    public function toMail($notifiable): MailMessage
    {
        $balance = number_format($this->balanceCents / 100, 2, ',', '.');
        $threshold = number_format($this->thresholdCents / 100, 2, ',', '.');
        
        $mail = (new MailMessage);
        
        if ($this->criticalLevel) {
            $mail->subject('⚠️ Kritisch niedriges Guthaben')
                 ->greeting('Wichtige Warnung!')
                 ->error()
                 ->line('Ihr Guthaben ist kritisch niedrig und Ihre Dienste könnten bald unterbrochen werden.');
        } else {
            $mail->subject('Niedriges Guthaben')
                 ->greeting('Hallo ' . $notifiable->name . ',')
                 ->line('Ihr Guthaben ist unter den Schwellenwert gefallen.');
        }
        
        return $mail->line('**Aktuelles Guthaben:** ' . $balance . ' €')
            ->line('**Warnschwelle:** ' . $threshold . ' €')
            ->line('Bitte laden Sie Ihr Guthaben auf, um eine unterbrechungsfreie Nutzung zu gewährleisten.')
            ->action('Jetzt aufladen', route('customer.billing.topup'))
            ->when(
                $notifiable->tenant->settings['auto_topup_enabled'] ?? false,
                function ($mail) {
                    return $mail->line('Hinweis: Ihre automatische Aufladung ist aktiviert und wird bei Erreichen des Mindestguthabens ausgeführt.');
                }
            );
    }

    /**
     * Get the SMS representation
     */
    public function toVonage($notifiable): VonageMessage
    {
        $balance = number_format($this->balanceCents / 100, 2, ',', '.');
        
        $message = $this->criticalLevel
            ? "WARNUNG: Kritisch niedriges Guthaben ({$balance}€). Dienste könnten unterbrochen werden. Jetzt aufladen: " . route('customer.billing.topup')
            : "AskPro: Niedriges Guthaben ({$balance}€). Bitte aufladen: " . route('customer.billing.topup');
        
        return (new VonageMessage)->content($message);
    }

    /**
     * Get the array representation
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'low_balance_warning',
            'balance' => $this->balanceCents,
            'threshold' => $this->thresholdCents,
            'critical' => $this->criticalLevel,
            'title' => $this->criticalLevel ? 'Kritisch niedriges Guthaben' : 'Niedriges Guthaben',
            'message' => sprintf(
                'Ihr Guthaben beträgt nur noch %s€',
                number_format($this->balanceCents / 100, 2, ',', '.')
            ),
            'timestamp' => now()->toIso8601String()
        ];
    }

    /**
     * Get the push notification representation
     */
    public function toPush($notifiable): array
    {
        $balance = number_format($this->balanceCents / 100, 2, ',', '.');
        
        return [
            'title' => $this->criticalLevel ? '⚠️ Kritisch niedriges Guthaben' : 'Niedriges Guthaben',
            'body' => "Ihr Guthaben beträgt nur noch {$balance}€",
            'icon' => $this->criticalLevel ? 'error' : 'warning',
            'url' => route('customer.billing.topup'),
            'actions' => [
                [
                    'action' => 'topup',
                    'title' => 'Aufladen'
                ]
            ],
            'requireInteraction' => $this->criticalLevel
        ];
    }
}