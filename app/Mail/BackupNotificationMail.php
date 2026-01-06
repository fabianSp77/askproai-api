<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\RetellCallSession;
use App\Models\ServiceCase;
use App\Models\ServiceOutputConfiguration;
use App\Services\Audio\AudioStorageService;
use App\Services\RelativeTimeParser;
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
 * Features (configurable per ServiceOutputConfiguration):
 * - Transcript: Chat format in HTML
 * - Summary: AI-generated call summary
 * - Audio: none | link (signed URL) | attachment (if <10MB)
 * - JSON attachment: Single case JSON (identical to webhook payload)
 *
 * Security:
 * - NO internal IDs exposed (case_id, company_id, customer_id, etc.)
 * - NO external provider references (Retell URLs/IDs)
 * - Uses opaque ticket reference (TKT-YYYY-XXXXX) instead
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
     * Whether to attach case JSON file (webhook-compatible format).
     */
    private bool $attachCaseJson = true;

    /**
     * Audio option: none, link, attachment.
     */
    private string $audioOption = 'none';

    /**
     * Whether to show admin link in email.
     */
    private bool $showAdminLink = false;

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
     * Case JSON payload (webhook-compatible format for attachment).
     */
    private array $casePayload = [];

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

        // Build case payload (webhook-compatible format for attachment)
        if ($this->attachCaseJson) {
            $this->casePayload = $this->buildWebhookPayload();
        }

        // Generate audio URL if needed
        if ($this->audioOption !== 'none' && $this->case->audio_object_key) {
            $this->prepareAudioUrl();
        }
    }

    /**
     * Configure features based on config settings.
     */
    private function configureFromSettings(?ServiceOutputConfiguration $config): void
    {
        if (!$config) {
            return;
        }

        // Set mode from explicit email_template_type
        $templateType = $config->email_template_type ?? 'admin';
        $this->mode = match ($templateType) {
            'technical' => self::MODE_TECHNICAL,
            'admin' => self::MODE_ADMINISTRATIVE,
            default => self::MODE_ADMINISTRATIVE,
        };

        // Enable transcript for technical mode by default
        if ($this->mode === self::MODE_TECHNICAL) {
            $this->includeTranscript = true;
        }

        // Override from config settings
        $this->includeTranscript = $config->include_transcript ?? $this->includeTranscript;
        $this->includeSummary = $config->include_summary ?? $this->includeSummary;
        $this->audioOption = $config->email_audio_option ?? 'none';
        $this->showAdminLink = $config->email_show_admin_link ?? true;

        Log::debug('[BackupNotificationMail] Configured from settings', [
            'case_id' => $this->case->id,
            'email_template_type' => $templateType,
            'mode' => $this->mode,
            'include_transcript' => $this->includeTranscript,
            'include_summary' => $this->includeSummary,
            'audio_option' => $this->audioOption,
            'show_admin_link' => $this->showAdminLink,
        ]);
    }

    
    /**
     * Load all required relationships.
     * Includes nested phoneNumber for Servicenummer and Zugehöriges Unternehmen display.
     */
    private function loadRelationships(): void
    {
        $relations = [
            'category',
            'customer',
            'company',
            'call.phoneNumber.company',
            'call.phoneNumber.branch',
        ];

        foreach ($relations as $relation) {
            $topLevel = \Illuminate\Support\Str::before($relation, '.');
            if (!$this->case->relationLoaded($topLevel)) {
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
     * Load transcript segments from RetellCallSession or fallback to Call.transcript.
     */
    private function loadTranscriptSegments(): Collection
    {
        if (!$this->case->call) {
            return collect();
        }

        // Try RetellTranscriptSegment first (detailed segments with timing)
        if ($this->case->call->retell_call_id) {
            $retellCallId = $this->case->call->retell_call_id;
            $session = RetellCallSession::where('call_id', $retellCallId)->first();

            if ($session && $session->transcriptSegments()->exists()) {
                return $session->transcriptSegments()
                    ->orderBy('segment_sequence')
                    ->get();
            }
        }

        // Fallback: Parse Call.transcript field
        return $this->parseCallTranscript();
    }

    /**
     * Parse Call.transcript plain text into segment-like objects.
     *
     * Format expected: "Agent: Text here...\nUser: Text here...\n"
     *
     * @return Collection
     */
    private function parseCallTranscript(): Collection
    {
        $transcript = $this->case->call?->transcript;

        if (empty($transcript)) {
            Log::debug('[BackupNotificationMail] No transcript in Call model', [
                'case_id' => $this->case->id,
                'call_id' => $this->case->call?->id,
            ]);
            return collect();
        }

        Log::info('[BackupNotificationMail] Using Call.transcript fallback', [
            'case_id' => $this->case->id,
            'transcript_length' => strlen($transcript),
        ]);

        $segments = [];
        $sequence = 0;

        // Split by newlines and parse "Role: Text" format
        $lines = preg_split('/\r?\n/', $transcript);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Match "Agent: " or "User: " prefix
            if (preg_match('/^(Agent|User):\s*(.+)$/i', $line, $matches)) {
                $role = strtolower($matches[1]) === 'agent' ? 'agent' : 'user';
                $text = trim($matches[2]);

                if (!empty($text)) {
                    $segments[] = (object) [
                        'role' => $role,
                        'text' => $text,
                        'call_offset_ms' => null,
                        'sentiment' => null,
                        'segment_sequence' => $sequence++,
                    ];
                }
            }
        }

        return collect($segments);
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
        // Extract metadata for view (avoiding internal IDs)
        $meta = $this->case->ai_metadata ?? [];

        return new Content(
            view: 'emails.service-cases.backup-notification',
            with: [
                'case' => $this->case,
                'meta' => $meta,
                'mode' => $this->mode,
                'includeTranscript' => $this->includeTranscript,
                'includeSummary' => $this->includeSummary,
                'chatTranscript' => $this->chatTranscript,
                'transcriptTruncated' => $this->transcriptTruncated,
                'originalTranscriptLength' => $this->originalTranscriptLength,
                'audioOption' => $this->audioOption,
                'audioUrl' => $this->audioUrl,
                'audioSizeExceeded' => $this->audioSizeExceeded,
                'showAdminLink' => $this->showAdminLink,
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

        // Case JSON attachment (webhook-compatible format)
        // Single attachment identical to what's sent via webhook
        if ($this->attachCaseJson && !empty($this->casePayload)) {
            $filename = sprintf(
                'case_%s_%s.json',
                $this->case->formatted_id,
                $this->case->created_at->format('Y-m-d_His')
            );

            $jsonContent = json_encode(
                $this->casePayload,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );

            $attachments[] = Attachment::fromData(fn () => $jsonContent, $filename)
                ->withMime('application/json');

            Log::debug('[BackupNotificationMail] Case JSON attached', [
                'case_id' => $this->case->id,
                'filename' => $filename,
                'size_bytes' => strlen($jsonContent),
            ]);
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
     * Enrich problem_since with absolute timestamp if not already enriched.
     *
     * Backwards compatible: enriches old cases that only have relative time.
     * New cases already have enriched format from ServiceDeskHandler.
     *
     * @param string|null $value The problem_since value
     * @return string|null Enriched value with absolute timestamp
     */
    private function enrichProblemSince(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        // Check if already enriched - detect various enrichment patterns:
        // Pattern 1: "(17:31 Uhr, Di. 23. Dez.)" - parentheses format
        // Pattern 2: "Mi. 24. Dez. 09:00 –" - new format with weekday, date, time
        // Pattern 3: Contains "–" (em-dash) typically added during enrichment
        $alreadyEnrichedPatterns = [
            '/\(\d{1,2}:\d{2}\s*Uhr/',                           // (17:31 Uhr...
            '/[A-Z][a-z]\.\s+\d{1,2}\.\s+[A-Z][a-z]{2}\.\s+\d{1,2}:\d{2}/', // Mi. 24. Dez. 09:00
            '/\d{1,2}:\d{2}\s+Uhr,\s+[A-Z][a-z]\./',            // 09:00 Uhr, Mi.
        ];

        foreach ($alreadyEnrichedPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return $value;
            }
        }

        // Parse and enrich using RelativeTimeParser
        $parser = new RelativeTimeParser();
        $referenceTime = $this->case->created_at ?? now();

        return $parser->format($value, $referenceTime);
    }

    /**
     * Build webhook-style payload for technical integration.
     *
     * This mirrors WebhookOutputHandler::buildPayload() format to provide
     * the same data structure that external systems receive via webhook.
     *
     * @return array Webhook-compatible payload
     */
    private function buildWebhookPayload(): array
    {
        $aiMeta = $this->case->ai_metadata ?? [];

        // Generate audio URL if feature enabled and audio available
        $audioUrl = null;
        $audioTtl = $this->config?->audio_url_ttl_minutes ?? config('gateway.delivery.audio_url_ttl_minutes', 60);

        if (config('gateway.features.audio_in_webhook', false) && $this->case->audio_object_key) {
            try {
                $audioService = app(AudioStorageService::class);
                $audioUrl = $audioService->getPresignedUrl($this->case->audio_object_key, $audioTtl);
            } catch (\Exception $e) {
                Log::debug('[BackupNotificationMail] Could not generate audio URL', [
                    'case_id' => $this->case->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Clean payload - no internal IDs, no architecture hints
        // Matches WebhookOutputHandler format
        $ticketReference = sprintf('TKT-%s-%05d', $this->case->created_at->format('Y'), $this->case->id);

        $payload = [
            'ticket' => [
                'reference' => $ticketReference,
                'summary' => $this->case->subject,
                'description' => $this->case->description,
                'type' => $this->case->case_type,
                'priority' => $this->case->priority,
                'category' => $this->case->category?->name ?? 'Allgemein',
                'created_at' => $this->case->created_at?->toIso8601String(),
            ],
            'context' => [
                'urgency' => $this->case->urgency ?? 'normal',
                'impact' => $this->case->impact ?? 'normal',
                'problem_since' => $this->enrichProblemSince($aiMeta['problem_since'] ?? null),
                'others_affected' => $aiMeta['others_affected'] ?? null,
            ],
            'customer' => [
                'name' => $aiMeta['customer_name'] ?? $this->case->customer?->name ?? null,
                'phone' => $aiMeta['customer_phone'] ?? $this->case->customer?->phone ?? null,
                'email' => $aiMeta['customer_email'] ?? $this->case->customer?->email ?? null,
                'location' => $aiMeta['customer_location'] ?? null,
            ],
        ];

        // Add audio URL only if available (no redundant boolean flags)
        if ($audioUrl) {
            $payload['audio'] = [
                'url' => $audioUrl,
                'expires_at' => now()->addMinutes($audioTtl)->toIso8601String(),
            ];
        }

        // Add transcript if available
        if (!empty($this->plainTranscript)) {
            $payload['transcript'] = $this->buildTranscriptPayload();
        }

        return $payload;
    }

    /**
     * Build transcript payload for webhook format.
     */
    private function buildTranscriptPayload(): array
    {
        if ($this->transcriptSegments->isEmpty()) {
            return [
                'format' => 'text',
                'content' => $this->plainTranscript,
                'segment_count' => 1,
            ];
        }

        $segments = [];
        foreach ($this->transcriptSegments as $segment) {
            $segments[] = [
                'role' => $segment->role ?? 'unknown',
                'content' => $segment->text ?? '',
                'timestamp' => $this->formatOffsetAsTime($segment->call_offset_ms),
            ];
        }

        return [
            'format' => 'segments',
            'segments' => $segments,
            'segment_count' => count($segments),
            'total_chars' => $this->originalTranscriptLength,
        ];
    }

    /**
     * Map case type to Jira-compatible type.
     */
    private function mapCaseType(string $type): string
    {
        return match($type) {
            'incident' => 'Bug',
            'request' => 'Task',
            'inquiry' => 'Story',
            default => 'Task',
        };
    }

    /**
     * Map priority to Jira-compatible priority.
     */
    private function mapPriority(string $priority): string
    {
        return match($priority) {
            'critical' => 'Highest',
            'high' => 'High',
            'normal' => 'Medium',
            'low' => 'Low',
            default => 'Medium',
        };
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
