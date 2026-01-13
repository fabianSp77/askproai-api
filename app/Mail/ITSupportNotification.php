<?php

namespace App\Mail;

use App\Models\ServiceCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * ITSupportNotification
 *
 * Professional IT support ticket notification email with JSON attachment.
 * Designed for internal IT staff notifications with full technical details.
 *
 * Features:
 * - Clean, professional email layout (German language)
 * - Priority-based color coding
 * - Clickable contact information
 * - Full JSON data attachment for ticket details
 * - No external provider references (sanitized output)
 *
 * @package App\Mail
 */
class ITSupportNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The service case instance.
     */
    public ServiceCase $case;

    /**
     * Priority labels in German.
     */
    public array $priorityLabels = [
        'critical' => 'Kritisch',
        'high' => 'Hoch',
        'normal' => 'Normal',
        'low' => 'Niedrig',
    ];

    /**
     * Status labels in German.
     */
    public array $statusLabels = [
        'new' => 'Neu',
        'open' => 'Offen',
        'pending' => 'Wartend',
        'resolved' => 'Geloest',
        'closed' => 'Geschlossen',
    ];

    /**
     * Create a new message instance.
     *
     * @param ServiceCase $case Service case to notify about
     */
    public function __construct(ServiceCase $case)
    {
        $this->case = $case;

        // Ensure all required relationships are loaded
        $relationships = ['category', 'customer', 'company', 'call'];
        foreach ($relationships as $relation) {
            if (!$case->relationLoaded($relation)) {
                $case->load($relation);
            }
        }
    }

    /**
     * Get the message envelope.
     *
     * Subject format: [PRIORITY] Ticket-ID: Subject
     */
    public function envelope(): Envelope
    {
        $priorityPrefix = $this->getPriorityPrefix();
        $subject = "{$priorityPrefix}[{$this->case->formatted_id}] {$this->case->subject}";

        return new Envelope(
            subject: $subject,
            from: config('mail.from.address'),
            replyTo: config('mail.from.address'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.service-cases.it-support-notification',
            with: [
                'case' => $this->case,
                'priorityLabels' => $this->priorityLabels,
                'statusLabels' => $this->statusLabels,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * Includes a JSON file with complete, sanitized ticket data.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $jsonData = $this->buildSanitizedTicketData();
        $jsonString = json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $filename = sprintf(
            'ticket_%s_%s.json',
            $this->case->formatted_id,
            $this->case->created_at->format('Y-m-d_His')
        );

        return [
            Attachment::fromData(fn () => $jsonString, $filename)
                ->withMime('application/json'),
        ];
    }

    /**
     * Build sanitized ticket data for JSON attachment.
     *
     * Removes all external provider references (e.g., retell_call_id)
     * and uses internal naming conventions.
     *
     * @return array<string, mixed>
     */
    private function buildSanitizedTicketData(): array
    {
        $aiMeta = $this->case->ai_metadata ?? [];

        // Extract contact info with fallbacks
        $customerName = $aiMeta['customer_name'] ?? $this->case->customer?->name ?? 'Nicht angegeben';
        $customerPhone = $aiMeta['customer_phone'] ?? $this->case->customer?->phone ?? null;
        $customerEmail = $aiMeta['customer_email'] ?? $this->case->customer?->email ?? null;
        $customerLocation = $aiMeta['customer_location'] ?? $aiMeta['location'] ?? null;

        // Sanitize metadata - remove all provider-specific and internal fields
        $sanitizedMeta = collect($aiMeta)
            ->except([
                'retell_call_id',
                'retell_agent_id',
                'retell_call_status',
                'calcom_booking_id',
                'call_id',
            ])
            ->toArray();

        // Security: Only export opaque references and business data, no internal IDs
        return [
            'ticket' => [
                'referenz' => $this->case->formatted_id,
                'erstellt_am' => $this->case->created_at->timezone('Europe/Berlin')->format('Y-m-d H:i:s'),
                'aktualisiert_am' => $this->case->updated_at->timezone('Europe/Berlin')->format('Y-m-d H:i:s'),
            ],
            'klassifizierung' => [
                'prioritaet' => $this->case->priority,
                'prioritaet_label' => $this->priorityLabels[$this->case->priority] ?? $this->case->priority,
                'status' => $this->case->status,
                'status_label' => $this->statusLabels[$this->case->status] ?? $this->case->status,
                'kategorie' => $this->case->category?->name ?? null,
            ],
            'inhalt' => [
                'betreff' => $this->case->subject,
                'beschreibung' => $this->case->description,
            ],
            'kontakt' => [
                'name' => $customerName,
                'telefon' => $customerPhone,
                'email' => $customerEmail,
                'standort' => $customerLocation,
            ],
            'problem_details' => [
                'problem_seit' => $aiMeta['problem_since'] ?? null,
                'andere_betroffen' => $aiMeta['others_affected'] ?? null,
            ],
            'metadaten' => $sanitizedMeta,
            'export' => [
                'generiert_am' => now()->timezone('Europe/Berlin')->format('Y-m-d H:i:s'),
                'version' => '1.1',
            ],
        ];
    }

    /**
     * Get subject line priority prefix based on case priority.
     *
     * @return string Priority indicator for subject line
     */
    private function getPriorityPrefix(): string
    {
        return match ($this->case->priority) {
            'critical' => '[KRITISCH] ',
            'high' => '[DRINGEND] ',
            default => '',
        };
    }
}
