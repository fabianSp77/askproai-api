<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AutoTopupProcessed extends Notification implements ShouldQueue
{
    use Queueable;

    protected int $amountCents;
    protected int $newBalanceCents;
    protected string $paymentMethod;
    protected array $metadata;

    public function __construct(int $amountCents, array $metadata = [])
    {
        $this->amountCents = $amountCents;
        $this->metadata = $metadata;
        $this->newBalanceCents = $metadata['new_balance'] ?? 0;
        $this->paymentMethod = $metadata['payment_method'] ?? 'Gespeicherte Zahlungsmethode';
    }

    public function via($notifiable): array
    {
        $channels = ['database'];
        
        // Auto-topup notifications should always be sent via email
        $channels[] = 'mail';
        
        // Also send push if enabled
        if ($notifiable->notification_preferences['push_enabled'] ?? false) {
            $channels[] = \App\Channels\PushNotificationChannel::class;
        }
        
        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        $amount = number_format($this->amountCents / 100, 2, ',', '.');
        $balance = number_format($this->newBalanceCents / 100, 2, ',', '.');
        
        return (new MailMessage)
            ->subject('Automatische Aufladung durchgeführt')
            ->greeting('Hallo ' . $notifiable->name . ',')
            ->line('Ihr Guthaben wurde automatisch aufgeladen, da es unter den festgelegten Schwellenwert gefallen ist.')
            ->line('**Aufgeladener Betrag:** ' . $amount . ' €')
            ->line('**Zahlungsmethode:** ' . $this->paymentMethod)
            ->line('**Neues Guthaben:** ' . $balance . ' €')
            ->action('Transaktionsdetails anzeigen', route('customer.transactions'))
            ->line('Sie können die automatische Aufladung jederzeit in Ihren Einstellungen anpassen oder deaktivieren.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'auto_topup_processed',
            'amount' => $this->amountCents,
            'new_balance' => $this->newBalanceCents,
            'payment_method' => $this->paymentMethod,
            'title' => 'Automatische Aufladung',
            'message' => sprintf(
                '%s€ wurden automatisch aufgeladen',
                number_format($this->amountCents / 100, 2, ',', '.')
            ),
            'timestamp' => now()->toIso8601String(),
            'metadata' => $this->metadata
        ];
    }

    public function toPush($notifiable): array
    {
        $amount = number_format($this->amountCents / 100, 2, ',', '.');
        
        return [
            'title' => 'Automatische Aufladung',
            'body' => "{$amount}€ wurden automatisch aufgeladen",
            'icon' => 'success',
            'url' => route('customer.transactions')
        ];
    }
}