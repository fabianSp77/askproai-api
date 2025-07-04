<?php

namespace App\Notifications;

use App\Models\PrepaidBalance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowBalanceWarningNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected PrepaidBalance $balance;

    /**
     * Create a new notification instance.
     */
    public function __construct(PrepaidBalance $balance)
    {
        $this->balance = $balance;
    }

    /**
     * Get the notification's delivery channels.
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
        $company = $this->balance->company;
        $percentage = round($this->balance->getBalancePercentage(), 0);
        $effectiveBalance = number_format($this->balance->getEffectiveBalance(), 2, ',', '.');
        
        // Generate one-click topup URL
        $topupUrl = route('business.billing.topup', [
            'suggested' => 100, // Suggest 100€ topup
        ]);

        return (new MailMessage)
            ->subject('⚠️ Niedriger Guthabenstand - Nur noch ' . $percentage . '% verfügbar')
            ->greeting('Hallo ' . $notifiable->name . ',')
            ->line('Ihr Guthaben bei ' . $company->name . ' ist fast aufgebraucht.')
            ->line('**Aktueller Stand:**')
            ->line('• Verfügbares Guthaben: **' . $effectiveBalance . ' €**')
            ->line('• Verbleibend: **' . $percentage . '%**')
            ->line('Um eine unterbrechungsfreie Nutzung sicherzustellen, laden Sie bitte Ihr Guthaben auf.')
            ->action('Jetzt Guthaben aufladen', $topupUrl)
            ->line('Bei Fragen stehen wir Ihnen gerne zur Verfügung.')
            ->salutation('Mit freundlichen Grüßen,')
            ->salutation('Ihr AskProAI Team');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $company = $this->balance->company;
        
        return [
            'type' => 'low_balance_warning',
            'company_id' => $company->id,
            'company_name' => $company->name,
            'balance' => $this->balance->balance,
            'effective_balance' => $this->balance->getEffectiveBalance(),
            'percentage' => $this->balance->getBalancePercentage(),
            'threshold' => $this->balance->low_balance_threshold,
        ];
    }
}