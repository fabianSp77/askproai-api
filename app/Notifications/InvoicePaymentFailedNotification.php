<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\AggregateInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Invoice Payment Failed Notification
 *
 * Sent to administrators when a Stripe invoice payment fails.
 * This is a critical alert for billing issues requiring attention.
 */
class InvoicePaymentFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public AggregateInvoice $invoice,
        public string $failureMessage,
        public ?string $stripeEventId = null
    ) {
        $this->queue = 'notifications';
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
        $partnerName = $this->invoice->partnerCompany?->name ?? 'Unknown Partner';
        $invoiceNumber = $this->invoice->invoice_number;
        $total = $this->invoice->formatted_total;
        $period = $this->invoice->billing_period_display;

        return (new MailMessage)
            ->error()
            ->subject("[BILLING ALERT] Payment Failed: {$invoiceNumber}")
            ->greeting("Invoice Payment Failed")
            ->line("A partner invoice payment has failed in Stripe.")
            ->line("**Invoice Details:**")
            ->line("- **Invoice:** {$invoiceNumber}")
            ->line("- **Partner:** {$partnerName}")
            ->line("- **Amount:** {$total}")
            ->line("- **Period:** {$period}")
            ->line("- **Stripe Invoice:** {$this->invoice->stripe_invoice_id}")
            ->line("**Failure Reason:**")
            ->line("```")
            ->line($this->failureMessage)
            ->line("```")
            ->action('View Invoice in Admin', $this->getInvoiceUrl())
            ->action('View in Stripe Dashboard', $this->getStripeUrl())
            ->line("Please follow up with the partner regarding payment.");
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'partner_id' => $this->invoice->partner_company_id,
            'partner_name' => $this->invoice->partnerCompany?->name,
            'total_cents' => $this->invoice->total_cents,
            'failure_message' => $this->failureMessage,
            'stripe_invoice_id' => $this->invoice->stripe_invoice_id,
            'stripe_event_id' => $this->stripeEventId,
        ];
    }

    /**
     * Get the URL to view the invoice in Filament admin.
     */
    private function getInvoiceUrl(): string
    {
        return url("/admin/aggregate-invoices/{$this->invoice->id}");
    }

    /**
     * Get the URL to view the invoice in Stripe dashboard.
     */
    private function getStripeUrl(): string
    {
        $stripeId = $this->invoice->stripe_invoice_id;
        // Stripe test mode uses different dashboard URL
        $prefix = str_starts_with($stripeId, 'in_') ? '' : '';
        return "https://dashboard.stripe.com/invoices/{$stripeId}";
    }
}
