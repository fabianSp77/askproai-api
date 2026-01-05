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
 * ServiceCaseNotificationV2
 *
 * Enhanced Mailable for IT support ticket notifications.
 * Supports both internal staff and external customer notifications
 * with HTML and plain text versions.
 *
 * Features:
 * - Priority-based subject line formatting
 * - SLA information and warnings
 * - AI metadata display (customer location, problem duration, etc.)
 * - Mobile-responsive HTML design
 * - Plain text fallback for accessibility
 *
 * Queue: Implements ShouldQueue for async delivery
 *
 * @package App\Mail
 */
class ServiceCaseNotificationV2 extends Mailable implements ShouldQueue
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
     * Priority labels in German.
     *
     * @var array<string, string>
     */
    protected array $priorityLabels = [
        'critical' => 'KRITISCH',
        'high' => 'HOCH',
        'normal' => 'NORMAL',
        'low' => 'NIEDRIG',
    ];

    /**
     * Type labels in German.
     *
     * @var array<string, string>
     */
    protected array $typeLabels = [
        'incident' => 'Stoerung',
        'request' => 'Anfrage',
        'inquiry' => 'Rueckfrage',
    ];

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

        // Eager load required relationships
        $this->loadRelationships($case);
    }

    /**
     * Load all required relationships for the case.
     *
     * @param \App\Models\ServiceCase $case
     * @return void
     */
    protected function loadRelationships(ServiceCase $case): void
    {
        $relationships = ['category', 'customer', 'company', 'call', 'assignedTo'];

        foreach ($relationships as $relation) {
            if (!$case->relationLoaded($relation)) {
                $case->load($relation);
            }
        }
    }

    /**
     * Get the message envelope.
     *
     * Subject line format:
     * - Internal: [PRIORITY] Type [DRINGEND]: Subject | Ticket-ID
     * - Customer: Ihre Anfrage: Subject (Ticket-ID)
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->generateSubjectLine(),
            from: config('mail.from.address'),
            replyTo: $this->getReplyToAddress(),
        );
    }

    /**
     * Get the message content definition.
     *
     * Uses HTML view with plain text alternative.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.service-cases.notification-html-v2',
            text: 'emails.service-cases.notification-text',
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
     * Format for internal:
     *   [KRITISCH] Stoerung [DRINGEND]: Server down | TKT-2025-00042
     *
     * Format for customer:
     *   Ihre Anfrage: Server down (TKT-2025-00042)
     *
     * @return string Email subject
     */
    protected function generateSubjectLine(): string
    {
        if ($this->recipientType === 'customer') {
            return $this->generateCustomerSubject();
        }

        return $this->generateInternalSubject();
    }

    /**
     * Generate subject line for internal staff.
     *
     * @return string
     */
    protected function generateInternalSubject(): string
    {
        $priority = $this->priorityLabels[$this->case->priority] ?? 'NORMAL';
        $type = $this->typeLabels[$this->case->case_type] ?? 'Anfrage';

        // Build urgency tag
        $urgencyTag = '';
        if ($this->case->priority === 'critical' || ($this->case->urgency ?? null) === 'critical') {
            $urgencyTag = ' [DRINGEND]';
        }

        // Check for SLA breach
        $slaTag = '';
        if ($this->isSlaBreached()) {
            $slaTag = ' [SLA!]';
        }

        // Truncate subject if too long (email clients have limits)
        $subject = $this->truncateSubject($this->case->subject, 60);

        return sprintf(
            '[%s] %s%s%s: %s | %s',
            $priority,
            $type,
            $urgencyTag,
            $slaTag,
            $subject,
            $this->case->formatted_id
        );
    }

    /**
     * Generate subject line for customer.
     *
     * @return string
     */
    protected function generateCustomerSubject(): string
    {
        $subject = $this->truncateSubject($this->case->subject, 80);

        return sprintf(
            'Ihre Anfrage: %s (%s)',
            $subject,
            $this->case->formatted_id
        );
    }

    /**
     * Check if SLA is breached.
     *
     * @return bool
     */
    protected function isSlaBreached(): bool
    {
        if ($this->case->sla_response_due_at && now()->isAfter($this->case->sla_response_due_at)) {
            return true;
        }

        if ($this->case->sla_resolution_due_at && now()->isAfter($this->case->sla_resolution_due_at)) {
            return true;
        }

        // Fallback: Check based on priority if no explicit SLA
        if (!$this->case->sla_response_due_at) {
            $minutesSinceCreation = $this->case->created_at->diffInMinutes(now());

            return match ($this->case->priority) {
                'critical' => $minutesSinceCreation > 60,
                'high' => $minutesSinceCreation > 240,
                default => false,
            };
        }

        return false;
    }

    /**
     * Truncate subject line to maximum length.
     *
     * @param string $subject
     * @param int $maxLength
     * @return string
     */
    protected function truncateSubject(string $subject, int $maxLength): string
    {
        if (mb_strlen($subject) <= $maxLength) {
            return $subject;
        }

        return mb_substr($subject, 0, $maxLength - 3) . '...';
    }

    /**
     * Get reply-to address based on context.
     *
     * @return string|null
     */
    protected function getReplyToAddress(): ?string
    {
        if ($this->recipientType === 'customer') {
            // Customer notification - use company email if available
            if ($this->case->company?->email) {
                return $this->case->company->email;
            }
        }

        return config('mail.from.address');
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
