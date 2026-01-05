<?php

namespace App\Mail;

use App\Models\ServiceCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * ServiceCaseNotification
 *
 * Mailable for service case notifications sent to internal staff
 * or external customers. Supports different content rendering
 * based on recipient type.
 *
 * Queue: Implements ShouldQueue for async delivery
 * Template: Uses markdown view with case data
 *
 * @package App\Mail
 */
class ServiceCaseNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The service case instance.
     *
     * @var \App\Models\ServiceCase
     */
    public ServiceCase $case;

    /**
     * Recipient type (internal or customer).
     *
     * @var string
     */
    public string $recipientType;

    /**
     * Create a new message instance.
     *
     * @param \App\Models\ServiceCase $case Service case to notify about
     * @param string $recipientType Recipient type: 'internal' or 'customer'
     */
    public function __construct(ServiceCase $case, string $recipientType = 'internal')
    {
        $this->case = $case;
        $this->recipientType = $recipientType;

        // Load relationships if not already loaded
        if (!$case->relationLoaded('category')) {
            $case->load('category');
        }
        if (!$case->relationLoaded('customer')) {
            $case->load('customer');
        }
        if (!$case->relationLoaded('company')) {
            $case->load('company');
        }
        if (!$case->relationLoaded('call')) {
            $case->load('call');
        }
    }

    /**
     * Get the message envelope.
     *
     * Subject line adapts based on case type and urgency.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope(): Envelope
    {
        $subject = $this->generateSubjectLine();

        return new Envelope(
            subject: $subject,
            from: config('mail.from.address'),
            replyTo: $this->getReplyToAddress(),
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.service-cases.notification-html',
            with: [
                'case' => $this->case,
                'recipientType' => $this->recipientType,
                'isInternal' => $this->recipientType === 'internal',
                'isCustomer' => $this->recipientType === 'customer',
            ]
        );
    }

    /**
     * Build the subject line based on case properties.
     *
     * @return string Email subject
     */
    private function generateSubjectLine(): string
    {
        $emoji = $this->getCaseEmoji();
        $typeLabel = $this->getCaseTypeLabel();

        if ($this->recipientType === 'customer') {
            return "{$emoji} Ihre Anfrage: {$this->case->subject}";
        }

        // Internal notification
        $urgencyLabel = $this->case->priority === 'critical' || $this->case->urgency === 'critical'
            ? ' [DRINGEND]'
            : '';

        return "{$emoji} {$typeLabel}{$urgencyLabel}: {$this->case->subject}";
    }

    /**
     * Get emoji icon for case type.
     *
     * @return string Emoji character
     */
    private function getCaseEmoji(): string
    {
        return match($this->case->case_type) {
            'incident' => '🚨',
            'request' => '📋',
            'inquiry' => '💬',
            default => '📞',
        };
    }

    /**
     * Get human-readable case type label.
     *
     * @return string Localized case type
     */
    private function getCaseTypeLabel(): string
    {
        return match($this->case->case_type) {
            'incident' => 'Neue Störungsmeldung',
            'request' => 'Neue Anfrage',
            'inquiry' => 'Neue Anfrage',
            default => 'Neues Anliegen',
        };
    }

    /**
     * Get reply-to address based on context.
     *
     * For internal notifications, use company email if available.
     * For customer notifications, use default reply-to.
     *
     * @return string|null Reply-to email address
     */
    private function getReplyToAddress(): ?string
    {
        if ($this->recipientType === 'internal') {
            return config('mail.from.address');
        }

        // Customer notification - use company email if available
        if ($this->case->company && $this->case->company->email) {
            return $this->case->company->email;
        }

        return config('mail.from.address');
    }

    /**
     * Get the attachments for the message.
     *
     * Override this method to add attachments if needed.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
