<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\VonageMessage;
use Illuminate\Notifications\Notification;
use App\Models\BalanceTopup;
use App\Channels\PushNotificationChannel;
use App\Channels\WebhookChannel;

class BalanceTopupSuccessful extends Notification implements ShouldQueue
{
    use Queueable;

    protected BalanceTopup $topup;
    protected array $metadata;

    public function __construct(BalanceTopup $topup)
    {
        $this->topup = $topup;
        $this->metadata = [
            'type' => 'balance_topup_successful',
            'amount' => $topup->amount,
            'bonus' => $topup->bonus_amount,
            'new_balance' => $topup->tenant->balance_cents / 100,
            'timestamp' => now()->toIso8601String()
        ];
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        $channels = ['database']; // Always store in database
        
        // Check user notification preferences
        $preferences = $notifiable->notification_preferences ?? [];
        
        if ($preferences['email_billing'] ?? true) {
            $channels[] = 'mail';
        }
        
        if ($preferences['sms_billing'] ?? false && $notifiable->phone_verified) {
            $channels[] = 'vonage';
        }
        
        if ($preferences['push_enabled'] ?? false) {
            $channels[] = PushNotificationChannel::class;
        }
        
        // Send to webhook if configured
        if ($notifiable->tenant->webhook_url) {
            $channels[] = WebhookChannel::class;
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $amount = number_format($this->topup->amount, 2, ',', '.');
        $totalCredit = number_format($this->topup->amount + $this->topup->bonus_amount, 2, ',', '.');
        
        $mail = (new MailMessage)
            ->subject('Guthaben erfolgreich aufgeladen')
            ->greeting('Hallo ' . $notifiable->name . ',')
            ->line('Ihre Guthaben-Aufladung wurde erfolgreich verarbeitet.')
            ->line('**Aufladungsbetrag:** ' . $amount . ' €');
        
        if ($this->topup->bonus_amount > 0) {
            $bonus = number_format($this->topup->bonus_amount, 2, ',', '.');
            $mail->line('**Bonus:** ' . $bonus . ' €')
                 ->line('**Gesamt gutgeschrieben:** ' . $totalCredit . ' €');
        }
        
        $newBalance = number_format($this->topup->tenant->balance_cents / 100, 2, ',', '.');
        
        return $mail->line('**Neues Guthaben:** ' . $newBalance . ' €')
            ->action('Zum Dashboard', route('customer.dashboard'))
            ->line('Die Rechnung wird Ihnen in Kürze per E-Mail zugestellt.')
            ->line('Bei Fragen stehen wir Ihnen gerne zur Verfügung.');
    }

    /**
     * Get the SMS representation.
     */
    public function toVonage($notifiable): VonageMessage
    {
        $amount = number_format($this->topup->amount + $this->topup->bonus_amount, 2, ',', '.');
        $balance = number_format($this->topup->tenant->balance_cents / 100, 2, ',', '.');
        
        return (new VonageMessage)
            ->content("AskPro: Aufladung erfolgreich! {$amount}€ gutgeschrieben. Neues Guthaben: {$balance}€");
    }

    /**
     * Get the array representation for database.
     */
    public function toArray($notifiable): array
    {
        return array_merge($this->metadata, [
            'topup_id' => $this->topup->id,
            'title' => 'Guthaben aufgeladen',
            'message' => sprintf(
                'Aufladung von %s€ erfolgreich verarbeitet',
                number_format($this->topup->amount, 2, ',', '.')
            )
        ]);
    }

    /**
     * Get the push notification representation.
     */
    public function toPush($notifiable): array
    {
        return [
            'title' => 'Guthaben aufgeladen',
            'body' => sprintf(
                '%s€ wurden Ihrem Konto gutgeschrieben',
                number_format($this->topup->amount + $this->topup->bonus_amount, 2, ',', '.')
            ),
            'icon' => 'success',
            'url' => route('customer.transactions'),
            'data' => $this->metadata
        ];
    }

    /**
     * Get the webhook payload.
     */
    public function toWebhook($notifiable): array
    {
        return [
            'event' => 'balance.topup.successful',
            'tenant_id' => $this->topup->tenant_id,
            'data' => array_merge($this->metadata, [
                'topup_id' => $this->topup->id,
                'payment_method' => $this->topup->payment_method,
                'stripe_reference' => $this->topup->stripe_payment_intent_id
            ])
        ];
    }
}