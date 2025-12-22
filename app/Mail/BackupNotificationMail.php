<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\RetellCallSession;
use App\Models\ServiceCase;
use App\Models\ServiceOutputConfiguration;
use App\Services\Audio\AudioStorageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * BackupNotificationMail
 *
 * Unified backup email with configurable features for different use cases.
 *
 * Modes:
 * - MODE_TECHNICAL: Full technical details with transcript (for partners like Visionary Data)
 * - MODE_ADMINISTRATIVE: Clean, sanitized format with JSON attachment (for IT-Systemhaus)
 *
 * Features (configurable per ServiceOutputConfiguration):
 * - Transcript: Chat format in HTML, plain text in JSON
 * - Summary: AI-generated call summary
 * - Audio: none | link (signed URL) | attachment (if <10MB)
 * - JSON attachment: Full ticket data as file
 *
 * Security:
 * - NO external provider URLs (Retell recording_url) in output
 * - Provider names sanitized (retell_call_id → anruf_referenz)
 * - Audio via signed internal URLs only
 *
 * @package App\Mail
 */
class BackupNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    // Mode constants
    public const MODE_TECHNICAL = 'technical';
    public const MODE_ADMINISTRATIVE = 'admin';

    // Limits
    public const MAX_TRANSCRIPT_CHARS = 20000;
    public const MAX_ATTACHMENT_SIZE_MB = 10;

    /**
     * The service case instance.
     */
    public ServiceCase $case;

    /**
     * The output configuration (optional).
     */
    public ?ServiceOutputConfiguration $config;

    /**
     * Current mode (technical or admin).
     */
    private string $mode = self::MODE_ADMINISTRATIVE;

    /**
     * Whether to include transcript in email.
     */
    private bool $includeTranscript = true;

    /**
     * Whether to include AI summary.
     */
    private bool $includeSummary = true;

    /**
     * Whether to sanitize provider references.
     */
    private bool $sanitizeProviderRefs = true;

    /**
     * Whether to attach JSON file.
     */
    private bool $attachJson = true;

    /**
     * Audio option: none, link, attachment.
     */
    private string $audioOption = 'none';

    /**
     * Loaded transcript segments.
     */
    private Collection $transcriptSegments;

    /**
     * Chat-formatted transcript for HTML.
     */
    private array $chatTranscript = [];

    /**
     * Plain transcript text for JSON.
     */
    private string $plainTranscript = '';

    /**
     * Sanitized JSON data for display and attachment.
     */
    private array $sanitizedData = [];

    /**
     * Whether transcript was truncated.
     */
    private bool $transcriptTruncated = false;

    /**
     * Original transcript character count.
     */
    private int $originalTranscriptLength = 0;

    /**
     * Secure audio URL (generated at runtime).
     */
    private ?string $audioUrl = null;

    /**
     * Whether audio attachment size was exceeded.
     */
    private bool $audioSizeExceeded = false;

    /**
     * Create a new message instance.
     */
    public function __construct(ServiceCase $case, ?ServiceOutputConfiguration $config = null)
    {
        $this->case = $case;
        $this->config = $config;

        // Load relationships
        $this->loadRelationships();

        // Configure mode and features
        $this->configureFromSettings($config);

        // Load transcript (if enabled)
        if ($this->includeTranscript) {
            $this->loadTranscriptData();
        }

        // Build sanitized data
        $this->sanitizedData = $this->buildSanitizedData();

        // Generate audio URL if needed
        if ($this->audioOption !== 'none' && $this->case->audio_object_key) {
            $this->prepareAudioUrl();
        }
    }

    /**
     * Configure features based on config and mode detection.
     */
    private function configureFromSettings(?ServiceOutputConfiguration $config): void
    {
        // Auto-detect mode from config name
        if ($this->isVisionaryConfig($config)) {
            $this->mode = self::MODE_TECHNICAL;
            $this->includeTranscript = true;
            $this->sanitizeProviderRefs = false;
            $this->attachJson = false; // Visionary gets inline JSON only
        }

        // Override from config settings if available
        if ($config) {
            $this->includeTranscript = $config->include_transcript ?? $this->includeTranscript;
            $this->includeSummary = $config->include_summary ?? $this->includeSummary;
            $this->audioOption = $config->email_audio_option ?? 'none';
        }

        Log::debug('[BackupNotificationMail] Configured', [
            'case_id' => $this->case->id,
            'mode' => $this->mode,
            'include_transcript' => $this->includeTranscript,
            'include_summary' => $this->includeSummary,
            'audio_option' => $this->audioOption,
            'sanitize_providers' => $this->sanitizeProviderRefs,
        ]);
    }

    /**
     * Check if config is for Visionary Data (technical mode).
     */
    private function isVisionaryConfig(?ServiceOutputConfiguration $config): bool
    {
        if (!$config) {
            return false;
        }

        $name = strtolower($config->name ?? '');
        return str_contains($name, 'visionary');
    }

    /**
     * Load all required relationships.
     */
    private function loadRelationships(): void
    {
        $relations = ['category', 'customer', 'company', 'call'];

        foreach ($relations as $relation) {
            if (!$this->case->relationLoaded($relation)) {
                $this->case->load($relation);
            }
        }
    }

    /**
     * Load transcript from RetellTranscriptSegment.
     */
    private function loadTranscriptData(): void
    {
        $this->transcriptSegments = $this->loadTranscriptSegments();

        if ($this->transcriptSegments->isEmpty()) {
            return;
        }

        // Calculate length and truncation
        $this->calculateTranscriptLength();

        // Build chat format for HTML
        $this->chatTranscript = $this->formatTranscriptAsChat();

        // Build plain text for JSON
        $this->plainTranscript = $this->formatTranscriptAsPlainText();
    }

    /**
     * Load transcript segments from RetellCallSession.
     */
    private function loadTranscriptSegments(): Collection
    {
        if (!$this->case->call || !$this->case->call->retell_call_id) {
            return collect();
        }

        $retellCallId = $this->case->call->retell_call_id;
        $session = RetellCallSession::where('call_id', $retellCallId)->first();

        if (!$session) {
            return collect();
        }

        return $session->transcriptSegments()
            ->orderBy('segment_sequence')
            ->get();
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
     * Format transcript as chat array for HTML display.
     */
    private function formatTranscriptAsChat(): array
    {
        $chat = [];
        $charCount = 0;

        foreach ($this->transcriptSegments as $segment) {
            $textLength = strlen($segment->text ?? '');

            if ($charCount + $textLength > self::MAX_TRANSCRIPT_CHARS) {
                $chat[] = [
                    'role' => 'system',
                    'text' => sprintf(
                        '[Transkript gekürzt: %d von %d Zeichen]',
                        $charCount,
                        $this->originalTranscriptLength
                    ),
                    'time' => null,
                ];
                break;
            }

            $chat[] = [
                'role' => $segment->role,
                'text' => $segment->text,
                'time' => $this->formatOffsetAsTime($segment->call_offset_ms),
                'sentiment' => $segment->sentiment,
            ];

            $charCount += $textLength;
        }

        return $chat;
    }

    /**
     * Format transcript as plain text for JSON.
     */
    private function formatTranscriptAsPlainText(): string
    {
        $lines = [];
        foreach ($this->transcriptSegments as $segment) {
            $role = $segment->role === 'agent' ? 'Support' : 'Kunde';
            $lines[] = "[{$role}]: {$segment->text}";
        }
        return implode("\n", $lines);
    }

    /**
     * Format milliseconds offset as MM:SS.
     */
    private function formatOffsetAsTime(?int $offsetMs): ?string
    {
        if ($offsetMs === null) {
            return null;
        }

        $totalSeconds = (int) floor($offsetMs / 1000);
        $minutes = (int) floor($totalSeconds / 60);
        $seconds = $totalSeconds % 60;

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    /**
     * Prepare secure audio URL.
     */
    private function prepareAudioUrl(): void
    {
        if (!$this->case->audio_object_key) {
            return;
        }

        // Check size for attachment option
        if ($this->audioOption === 'attachment') {
            $audioService = app(AudioStorageService::class);
            $sizeMb = $audioService->getSizeMb($this->case->audio_object_key);

            if ($sizeMb > self::MAX_ATTACHMENT_SIZE_MB) {
                Log::info('[BackupNotificationMail] Audio too large for attachment, using link', [
                    'case_id' => $this->case->id,
                    'size_mb' => $sizeMb,
                    'max_mb' => self::MAX_ATTACHMENT_SIZE_MB,
                ]);
                $this->audioOption = 'link';
                $this->audioSizeExceeded = true;
            }
        }

        // Generate signed URL for link option
        // Note: Route uses 'api.audio.stream' (not 'download') to avoid server-side blocking
        // Note: Parameter name must be 'serviceCase' to match Laravel implicit model binding
        if ($this->audioOption === 'link') {
            $this->audioUrl = URL::signedRoute(
                'api.audio.stream',
                ['serviceCase' => $this->case->id],
                now()->addHours(24) // 24h validity for email links
            );
        }
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $prefix = $this->getPriorityPrefix();
        $category = $this->case->category?->name ?? 'IT-Support';

        return new Envelope(
            subject: "{$prefix}[{$category}] {$this->case->formatted_id}: {$this->case->subject}",
            from: config('mail.from.address'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.service-cases.backup-notification',
            with: [
                'case' => $this->case,
                'jsonData' => $this->sanitizedData,
                'mode' => $this->mode,
                'includeTranscript' => $this->includeTranscript,
                'includeSummary' => $this->includeSummary,
                'chatTranscript' => $this->chatTranscript,
                'transcriptTruncated' => $this->transcriptTruncated,
                'originalTranscriptLength' => $this->originalTranscriptLength,
                'audioOption' => $this->audioOption,
                'audioUrl' => $this->audioUrl,
                'audioSizeExceeded' => $this->audioSizeExceeded,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        // JSON attachment
        if ($this->attachJson) {
            $filename = sprintf(
                'ticket_%s_%s.json',
                $this->case->formatted_id,
                $this->case->created_at->format('Y-m-d_His')
            );

            $jsonContent = json_encode(
                $this->sanitizedData,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );

            $attachments[] = Attachment::fromData(fn () => $jsonContent, $filename)
                ->withMime('application/json');
        }

        // Audio attachment (only if configured and within size limit)
        if ($this->audioOption === 'attachment' && $this->case->audio_object_key) {
            try {
                $audioService = app(AudioStorageService::class);

                if ($audioService->exists($this->case->audio_object_key)) {
                    $audioFilename = sprintf(
                        'anruf_%s.mp3',
                        $this->case->formatted_id
                    );

                    $attachments[] = Attachment::fromStorageDisk(
                        $audioService->getDiskName(),
                        $this->case->audio_object_key
                    )->as($audioFilename)->withMime('audio/mpeg');
                }
            } catch (\Exception $e) {
                Log::warning('[BackupNotificationMail] Failed to attach audio', [
                    'case_id' => $this->case->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $attachments;
    }

    /**
     * Build sanitized data array.
     */
    private function buildSanitizedData(): array
    {
        $meta = $this->case->ai_metadata ?? [];

        // Build sanitized metadata
        $sanitizedMeta = $this->sanitizeProviderRefs
            ? $this->sanitizeMetadata($meta)
            : $meta;

        $data = [
            // Ticket identification
            'ticket' => [
                'id' => $this->case->formatted_id,
                'interne_id' => $this->case->id,
                'erstellt_am' => $this->case->created_at?->timezone('Europe/Berlin')->format('d.m.Y H:i:s'),
                'aktualisiert_am' => $this->case->updated_at?->timezone('Europe/Berlin')->format('d.m.Y H:i:s'),
            ],

            // Classification
            'klassifizierung' => [
                'kategorie' => $this->case->category?->name ?? 'Allgemein',
                'kategorie_id' => $this->case->category_id,
                'typ' => $this->getTypeLabel($this->case->case_type),
                'prioritaet' => $this->getPriorityLabel($this->case->priority),
                'status' => $this->getStatusLabel($this->case->status),
            ],

            // Issue content
            'anfrage' => [
                'betreff' => $this->case->subject,
                'beschreibung' => $this->case->description,
            ],

            // Contact information
            'kontakt' => [
                'name' => $sanitizedMeta['kunde_name'] ?? $meta['customer_name'] ?? null,
                'telefon' => $sanitizedMeta['kunde_telefon'] ?? $meta['customer_phone'] ?? null,
                'email' => $sanitizedMeta['kunde_email'] ?? $meta['customer_email'] ?? null,
                'standort' => $sanitizedMeta['kunde_standort'] ?? $meta['customer_location'] ?? null,
            ],

            // Additional context
            'kontext' => [
                'problem_seit' => $sanitizedMeta['problem_seit'] ?? $meta['problem_since'] ?? null,
                'weitere_betroffen' => $this->formatOthersAffected($meta['others_affected'] ?? null),
                'anruf_referenz' => $this->sanitizeProviderRefs
                    ? ($sanitizedMeta['anruf_referenz'] ?? null)
                    : ($meta['retell_call_id'] ?? $this->case->call?->retell_call_id ?? null),
            ],

            // Company info
            'unternehmen' => [
                'name' => $this->case->company?->name ?? config('app.name'),
                'id' => $this->case->company_id,
            ],

            // Metadata
            'meta' => [
                'quelle' => 'voice_intake',
                'version' => '2.0',
                'generiert_am' => now()->timezone('Europe/Berlin')->format('d.m.Y H:i:s'),
            ],
        ];

        // Add summary if enabled
        if ($this->includeSummary) {
            $data['zusammenfassung'] = $meta['summary'] ?? $this->case->call?->summary ?? null;
        }

        // Add transcript if enabled
        if ($this->includeTranscript && !empty($this->plainTranscript)) {
            $data['transkript'] = [
                'text' => $this->plainTranscript,
                'gekuerzt' => $this->transcriptTruncated,
                'gesamtzeichen' => $this->originalTranscriptLength,
            ];
        }

        return $data;
    }

    /**
     * Sanitize metadata to remove external provider references.
     */
    private function sanitizeMetadata(array $meta): array
    {
        $sanitized = [];

        // Map fields with German names
        $fieldMapping = [
            'customer_name' => 'kunde_name',
            'customer_phone' => 'kunde_telefon',
            'customer_email' => 'kunde_email',
            'customer_location' => 'kunde_standort',
            'problem_since' => 'problem_seit',
            'others_affected' => 'weitere_betroffen',
            'retell_call_id' => 'anruf_referenz',
        ];

        foreach ($fieldMapping as $original => $sanitizedKey) {
            if (isset($meta[$original]) && !empty($meta[$original])) {
                $sanitized[$sanitizedKey] = $meta[$original];
            }
        }

        return $sanitized;
    }

    /**
     * Format others_affected value.
     */
    private function formatOthersAffected(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'Ja' : 'Nein';
        }

        if (is_string($value)) {
            $lower = strtolower(trim($value));
            if (in_array($lower, ['ja', 'yes', 'true', '1'])) {
                return 'Ja';
            }
            if (in_array($lower, ['nein', 'no', 'false', '0'])) {
                return 'Nein';
            }
            return $value;
        }

        return (string) $value;
    }

    /**
     * Get priority prefix for subject line.
     */
    private function getPriorityPrefix(): string
    {
        return match ($this->case->priority) {
            'critical' => '[KRITISCH] ',
            'high' => '[DRINGEND] ',
            default => '',
        };
    }

    /**
     * Get German priority label.
     */
    private function getPriorityLabel(string $priority): string
    {
        return match ($priority) {
            'critical' => 'Kritisch',
            'high' => 'Hoch',
            'normal' => 'Normal',
            'low' => 'Niedrig',
            default => 'Normal',
        };
    }

    /**
     * Get German status label.
     */
    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            'new' => 'Neu',
            'open' => 'Offen',
            'pending' => 'Wartend',
            'in_progress' => 'In Bearbeitung',
            'resolved' => 'Gelöst',
            'closed' => 'Geschlossen',
            default => ucfirst($status),
        };
    }

    /**
     * Get German type label.
     */
    private function getTypeLabel(string $type): string
    {
        return match ($type) {
            'incident' => 'Störung',
            'request' => 'Anfrage',
            'inquiry' => 'Rückfrage',
            default => 'Anfrage',
        };
    }

    /**
     * Get the current mode.
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * Check if transcript is included.
     */
    public function hasTranscript(): bool
    {
        return $this->includeTranscript && !empty($this->chatTranscript);
    }

    /**
     * Check if audio is available.
     */
    public function hasAudio(): bool
    {
        return $this->audioOption !== 'none' && $this->case->audio_object_key !== null;
    }
}
