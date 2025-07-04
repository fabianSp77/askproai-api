<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Customer;
use App\Models\Company;

class GDPRDataDeletionMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Customer $customer,
        public Company $company,
        public string $confirmationLink,
        public string $expiresAt,
        public string $language = 'de'
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->language === 'de' 
            ? 'Bestätigung Ihrer Löschanfrage - ' . $this->company->name
            : 'Confirmation of Your Deletion Request - ' . $this->company->name;
            
        return new Envelope(
            subject: $subject,
            from: $this->company->email ?? config('mail.from.address'),
            replyTo: [$this->company->email ?? config('mail.from.address')],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.gdpr.data-deletion',
            with: [
                'customerName' => $this->customer->name,
                'companyName' => $this->company->name,
                'confirmationLink' => $this->confirmationLink,
                'expiresAt' => $this->expiresAt,
                'locale' => $this->language,
            ]
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