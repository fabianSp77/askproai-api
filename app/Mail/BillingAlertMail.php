<?php

namespace App\Mail;

use App\Models\BillingAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BillingAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public BillingAlert $alert;

    /**
     * Create a new message instance.
     */
    public function __construct(BillingAlert $alert)
    {
        $this->alert = $alert;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = match ($this->alert->alert_type) {
            'usage_limit' => 'Usage Limit Alert: ' . $this->alert->title,
            'payment_reminder' => 'Payment Reminder: ' . $this->alert->title,
            'subscription_renewal' => 'Subscription Renewal Notice',
            'overage_warning' => 'Overage Warning: Additional charges may apply',
            'payment_failed' => 'Payment Failed - Action Required',
            'budget_exceeded' => 'Budget Alert: ' . $this->alert->title,
            'low_balance' => 'Low Balance Alert',
            'invoice_generated' => 'New Invoice Available',
            default => 'Billing Alert: ' . $this->alert->title,
        };

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.billing.alert',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}