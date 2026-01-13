<?php

namespace App\Mail;

use App\Models\AggregateInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Partner invoice notification email.
 *
 * Sends invoice notification with Stripe payment link to partner billing contacts.
 * Supports primary recipient and CC recipients from partner company settings.
 */
class PartnerInvoiceMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public AggregateInvoice $invoice
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $partner = $this->invoice->partnerCompany;
        $invoiceNumber = $this->invoice->invoice_number;

        // Build CC recipients from partner settings
        $ccRecipients = collect($partner->getPartnerBillingCcEmails())
            ->filter()
            ->map(fn ($email) => new Address($email))
            ->toArray();

        return new Envelope(
            subject: "Rechnung {$invoiceNumber} - {$this->invoice->billing_period_display}",
            cc: $ccRecipients,
            replyTo: [
                new Address(
                    config('mail.from.address', 'billing@askpro.ai'),
                    config('mail.from.name', 'AskPro AI Billing')
                ),
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $partner = $this->invoice->partnerCompany;

        return new Content(
            view: 'emails.partner-invoice-v2',
            with: [
                'invoice' => $this->invoice,
                'partner' => $partner,
                'paymentUrl' => $this->invoice->stripe_hosted_invoice_url,
                'billingPeriod' => $this->invoice->billing_period_display,
                'formattedTotal' => $this->invoice->formatted_total,
                'dueDate' => $this->invoice->due_at?->format('d.m.Y'),
                'itemCount' => $this->invoice->items()->count(),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * Note: Per user decision, we only send the Stripe link, no PDF attachment.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
