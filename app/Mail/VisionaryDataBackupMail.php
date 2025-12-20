<?php

namespace App\Mail;

use App\Models\ServiceCase;
use App\Models\RetellCallSession;
use App\Models\RetellTranscriptSegment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * VisionaryDataBackupMail
 *
 * Backup-E-Mail für Visionary Data Partner.
 * Diese E-Mail ist KEIN Endkunden-Notification, sondern ein
 * Backup-Kanal für Datenübertragung bei Ticket-Erstellung.
 *
 * Enthält:
 * - Vollständige Ticket-Daten (ServiceCase)
 * - Kundendaten (Customer + ai_metadata)
 * - Anruf-Details (Call)
 * - Komplettes Transkript (RetellTranscriptSegment)
 * - JSON-Block für maschinelle Verarbeitung
 *
 * NO-LEAK GUARANTEE:
 * - Keine Cost/Profit-Daten
 * - Keine internen IDs (agent_id, prompt, etc.)
 * - Keine API-Keys oder Credentials
 *
 * @package App\Mail
 */
class VisionaryDataBackupMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Maximum characters for inline transcript.
     * Longer transcripts will be truncated with a note.
     */
    public const MAX_TRANSCRIPT_CHARS = 20000;

    /**
     * The service case instance.
     */
    public ServiceCase $case;

    /**
     * Loaded transcript segments.
     */
    public Collection $transcriptSegments;

    /**
     * Prepared JSON data for machine parsing.
     */
    public array $jsonData;

    /**
     * Whether transcript was truncated due to length.
     */
    public bool $transcriptTruncated = false;

    /**
     * Original transcript character count (before truncation).
     */
    public int $originalTranscriptLength = 0;

    /**
     * Create a new message instance.
     */
    public function __construct(ServiceCase $case)
    {
        $this->case = $case;

        // Load all required relationships
        $this->loadRelationships();

        // Load transcript segments via RetellCallSession
        $this->transcriptSegments = $this->loadTranscript();

        // Calculate original transcript length and check for truncation
        $this->calculateTranscriptLength();

        // Prepare sanitized JSON data
        $this->jsonData = $this->prepareJsonData();
    }

    /**
     * Calculate transcript length and set truncation flag.
     */
    private function calculateTranscriptLength(): void
    {
        $totalLength = 0;
        foreach ($this->transcriptSegments as $segment) {
            $totalLength += strlen($segment->text ?? '');
        }

        $this->originalTranscriptLength = $totalLength;
        $this->transcriptTruncated = $totalLength > self::MAX_TRANSCRIPT_CHARS;
    }

    /**
     * Load all relationships needed for the email.
     */
    private function loadRelationships(): void
    {
        $this->case->load([
            'category',
            'company',
            'customer',
            'call' => function ($query) {
                $query->with(['phoneNumber']);
            },
        ]);
    }

    /**
     * Load transcript segments from RetellCallSession.
     *
     * Path: ServiceCase → Call → retell_call_id → RetellCallSession → transcriptSegments
     */
    private function loadTranscript(): Collection
    {
        if (!$this->case->call || !$this->case->call->retell_call_id) {
            return collect();
        }

        $retellCallId = $this->case->call->retell_call_id;

        // Find the RetellCallSession by call_id matching the Call's retell_call_id
        $session = RetellCallSession::where('call_id', $retellCallId)->first();

        if (!$session) {
            return collect();
        }

        return $session->transcriptSegments()
            ->orderBy('segment_sequence')
            ->get();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $emoji = $this->getCaseEmoji();
        $subject = "{$emoji} Backup: {$this->case->formatted_id} - {$this->case->subject}";

        return new Envelope(
            subject: $subject,
            from: config('mail.from.address'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.service-cases.visionary-backup-html',
            with: [
                'case' => $this->case,
                'transcriptSegments' => $this->transcriptSegments,
                'jsonData' => $this->jsonData,
                'jsonString' => json_encode($this->jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                'transcriptTruncated' => $this->transcriptTruncated,
                'originalTranscriptLength' => $this->originalTranscriptLength,
                'maxTranscriptChars' => self::MAX_TRANSCRIPT_CHARS,
            ]
        );
    }

    /**
     * Get emoji icon for case type.
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
     * Prepare sanitized JSON data for machine parsing.
     *
     * NO-LEAK GUARANTEE: Only includes safe, customer-facing data.
     */
    private function prepareJsonData(): array
    {
        $aiMetadata = $this->case->ai_metadata ?? [];

        return [
            'ticket' => [
                'id' => $this->case->formatted_id,
                'internal_id' => $this->case->id,
                'subject' => $this->case->subject,
                'description' => $this->case->description,
                'case_type' => $this->case->case_type,
                'priority' => $this->case->priority,
                'urgency' => $this->case->urgency,
                'impact' => $this->case->impact,
                'status' => $this->case->status,
                'created_at' => $this->case->created_at?->toIso8601String(),
                'sla_response_due_at' => $this->case->sla_response_due_at?->toIso8601String(),
                'sla_resolution_due_at' => $this->case->sla_resolution_due_at?->toIso8601String(),
            ],
            'customer' => [
                'name' => $aiMetadata['customer_name'] ?? $this->case->customer?->name ?? null,
                'phone' => $aiMetadata['customer_phone'] ?? $this->case->customer?->phone ?? null,
                'email' => $this->case->customer?->email ?? null,
                'location' => $aiMetadata['customer_location'] ?? null,
            ],
            'call' => $this->case->call ? [
                'id' => $this->case->call->retell_call_id,
                'from_number' => $this->case->call->from_number,
                'to_number' => $this->case->call->to_number,
                'duration_seconds' => $this->case->call->duration,
                'started_at' => $this->case->call->started_at?->toIso8601String(),
                'ended_at' => $this->case->call->ended_at?->toIso8601String(),
                'sentiment' => $this->case->call->sentiment,
            ] : null,
            'ai_analysis' => [
                'summary' => $aiMetadata['ai_summary'] ?? $this->case->call?->summary ?? null,
                'others_affected' => $aiMetadata['others_affected'] ?? null,
                'additional_notes' => $aiMetadata['additional_notes'] ?? null,
                'finalized_at' => $aiMetadata['finalized_at'] ?? null,
            ],
            'category' => $this->case->category ? [
                'id' => $this->case->category->id,
                'name' => $this->case->category->name,
            ] : null,
            'structured_data' => $this->case->structured_data,
            'transcript' => $this->prepareTranscriptData(),
            'meta' => [
                'company_id' => $this->case->company_id,
                'company_name' => $this->case->company?->name,
                'generated_at' => now()->timezone('Europe/Berlin')->toIso8601String(),
                'version' => '1.0',
                'transcript_truncated' => $this->transcriptTruncated,
                'transcript_total_chars' => $this->originalTranscriptLength,
            ],
        ];
    }

    /**
     * Prepare transcript data with truncation handling.
     */
    private function prepareTranscriptData(): array
    {
        $segments = [];
        $charCount = 0;
        $truncatedAt = null;

        foreach ($this->transcriptSegments as $segment) {
            $textLength = strlen($segment->text ?? '');

            // Check if adding this segment would exceed limit
            if ($charCount + $textLength > self::MAX_TRANSCRIPT_CHARS) {
                $truncatedAt = $segment->segment_sequence;
                break;
            }

            $segments[] = [
                'sequence' => $segment->segment_sequence,
                'role' => $segment->role,
                'text' => $segment->text,
                'offset_ms' => $segment->call_offset_ms,
                'sentiment' => $segment->sentiment,
            ];

            $charCount += $textLength;
        }

        // Add truncation notice if needed
        if ($truncatedAt !== null) {
            $segments[] = [
                'sequence' => $truncatedAt,
                'role' => 'system',
                'text' => sprintf(
                    '[TRANSKRIPT GEKÜRZT: %d von %d Zeichen angezeigt. Vollständiges Transkript über API abrufbar.]',
                    $charCount,
                    $this->originalTranscriptLength
                ),
                'offset_ms' => null,
                'sentiment' => null,
            ];
        }

        return $segments;
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
