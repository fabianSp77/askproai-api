<?php

declare(strict_types=1);

namespace App\Services\ServiceGateway;

use App\Models\ServiceCase;

/**
 * EmailTemplateDataProvider
 *
 * Provides organized template variables for email rendering.
 * Centralizes all 39 template variables with proper grouping and null-safety.
 *
 * Usage:
 *   $provider = new EmailTemplateDataProvider($serviceCase);
 *   $variables = $provider->getVariables();
 */
class EmailTemplateDataProvider
{
    /**
     * All available template variables grouped by category.
     * Serves as source of truth for documentation and UI helpers.
     */
    public const AVAILABLE_VARIABLES = [
        'customer' => [
            'customer_name' => 'Name des Kunden',
            'customer_email' => 'E-Mail des Kunden',
            'customer_phone' => 'Telefonnummer des Kunden',
        ],
        'case' => [
            'case_number' => 'Fallnummer',
            'case_subject' => 'Betreff des Falls',
            'case_description' => 'Beschreibung des Falls',
            'case_status' => 'Status des Falls',
            'case_priority' => 'Priorität des Falls',
            'created_at' => 'Erstellungsdatum (formatiert)',
        ],
        'source' => [
            'source' => 'Quelle (übersetzt, z.B. "Telefonanruf")',
            'case_type' => 'Falltyp (übersetzt, z.B. "Störung")',
            'category' => 'Kategorie-Name',
        ],
        'audio' => [
            'audio_url' => 'Signierter Link zur Audioaufnahme (24h gültig)',
            'has_audio' => 'Audio vorhanden (Ja/Nein)',
            'audio_duration' => 'Audio-Länge (M:SS Format)',
        ],
        'sla' => [
            'sla_response_due' => 'Response-SLA Fälligkeitsdatum',
            'sla_resolution_due' => 'Resolution-SLA Fälligkeitsdatum',
            'is_overdue' => 'SLA überfällig (Ja/Nein)',
            'is_at_risk' => 'SLA gefährdet (<30min, Ja/Nein)',
            'is_response_overdue' => 'Response-SLA überfällig (Ja/Nein)',
            'is_resolution_overdue' => 'Resolution-SLA überfällig (Ja/Nein)',
        ],
        'call' => [
            'call_duration' => 'Anrufdauer (M:SS Format)',
            'caller_number' => 'Anrufer-Nummer (formatiert)',
            'service_number' => 'Angerufene Servicenummer (formatiert)',
            'called_company_name' => 'Name der angerufenen Company',
            'called_branch_name' => 'Name der angerufenen Filiale',
            'service_number_formatted' => 'Formatierte Servicenummer',
            'receiver_display' => 'Empfänger (Filiale + Nummer)',
        ],
        'ai' => [
            'ai_summary' => 'KI-generierte Zusammenfassung',
            'ai_confidence' => 'KI-Konfidenz (Prozent)',
            'customer_location' => 'Kundenstandort (AI-Metadaten)',
            'problem_since' => 'Problem besteht seit (AI-Metadaten)',
        ],
        'admin' => [
            'admin_url' => 'Signierter Admin-Link (72h gültig)',
        ],
        'transcript' => [
            'transcript' => 'Vollständiges Transkript (max 10.000 Zeichen)',
            'transcript_truncated' => 'Transkript gekürzt (Ja/Nein)',
            'transcript_length' => 'Original-Länge des Transkripts',
        ],
    ];

    /**
     * The service case to extract variables from.
     */
    private ServiceCase $case;

    /**
     * Create a new data provider instance.
     *
     * @param  ServiceCase  $case  Service case model with relationships loaded
     */
    public function __construct(ServiceCase $case)
    {
        $this->case = $case;
    }

    /**
     * Get all template variables for email rendering.
     *
     * Returns a flat array of all variables merged from all groups.
     * Variables are null-safe and return empty strings for missing data.
     *
     * @return array<string, string> All template variables
     */
    public function getVariables(): array
    {
        return array_merge(
            $this->getCustomerVariables(),
            $this->getCaseVariables(),
            $this->getAudioVariables(),
            $this->getSlaVariables(),
            $this->getCallVariables(),
            $this->getAiVariables(),
            $this->getAdminVariables(),
            $this->getTranscriptVariables(),
            $this->getSourceVariables(),
            $this->getColorVariables()
        );
    }

    /**
     * Get customer-related variables.
     *
     * @return array<string, string>
     */
    private function getCustomerVariables(): array
    {
        return [
            'customer_name' => $this->case->customer?->name ?? '',
            'customer_email' => $this->case->customer?->email ?? '',
            'customer_phone' => $this->case->customer?->phone ?? '',
            'company_name' => $this->case->company?->name ?? '',
        ];
    }

    /**
     * Get case-related variables.
     *
     * @return array<string, string>
     */
    private function getCaseVariables(): array
    {
        return [
            'case_number' => $this->case->case_number ?? '',
            'case_subject' => $this->case->subject ?? '',
            'case_description' => $this->case->description ?? '',
            'case_status' => $this->case->status ?? '',
            'case_priority' => $this->case->priority ?? '',
            'created_at' => $this->case->created_at?->format('d.m.Y H:i') ?? '',
        ];
    }

    /**
     * Get audio-related variables.
     *
     * @return array<string, string>
     */
    private function getAudioVariables(): array
    {
        // Generate signed URL for audio download (24h validity)
        $audioUrl = '';
        if ($this->case->audio_object_key) {
            $audioUrl = \Illuminate\Support\Facades\URL::signedRoute(
                'api.audio.stream',
                ['serviceCase' => $this->case->id],
                now()->addHours(24)
            );
        }

        // Has audio check
        $hasAudio = $this->case->audio_object_key ? 'Ja' : 'Nein';

        // Format audio duration as M:SS
        $audioDuration = '';
        if ($this->case->call?->duration_sec) {
            $minutes = floor($this->case->call->duration_sec / 60);
            $seconds = $this->case->call->duration_sec % 60;
            $audioDuration = sprintf('%d:%02d', $minutes, $seconds);
        }

        return [
            'audio_url' => $audioUrl,
            'has_audio' => $hasAudio,
            'audio_duration' => $audioDuration,
        ];
    }

    /**
     * Get SLA-related variables.
     *
     * @return array<string, string>
     */
    private function getSlaVariables(): array
    {
        // Format SLA due dates with Europe/Berlin timezone
        $slaResponseDue = '';
        if ($this->case->sla_response_due_at) {
            $slaResponseDue = $this->case->sla_response_due_at
                ->setTimezone('Europe/Berlin')
                ->format('d.m.Y H:i');
        }

        $slaResolutionDue = '';
        if ($this->case->sla_resolution_due_at) {
            $slaResolutionDue = $this->case->sla_resolution_due_at
                ->setTimezone('Europe/Berlin')
                ->format('d.m.Y H:i');
        }

        // Check if response-SLA is at risk (< 30 minutes until due)
        $isAtRisk = 'Nein';
        if ($this->case->sla_response_due_at && ! $this->case->isResponseOverdue()) {
            $minutesUntilDue = now()->diffInMinutes($this->case->sla_response_due_at, false);
            if ($minutesUntilDue >= 0 && $minutesUntilDue < 30) {
                $isAtRisk = 'Ja';
            }
        }

        // Check if either response or resolution is overdue
        $isOverdue = 'Nein';
        if ($this->case->isResponseOverdue() || $this->case->isResolutionOverdue()) {
            $isOverdue = 'Ja';
        }

        // Individual overdue checks
        $isResponseOverdue = $this->case->isResponseOverdue() ? 'Ja' : 'Nein';
        $isResolutionOverdue = $this->case->isResolutionOverdue() ? 'Ja' : 'Nein';

        return [
            'sla_response_due' => $slaResponseDue,
            'sla_resolution_due' => $slaResolutionDue,
            'is_overdue' => $isOverdue,
            'is_at_risk' => $isAtRisk,
            'is_response_overdue' => $isResponseOverdue,
            'is_resolution_overdue' => $isResolutionOverdue,
        ];
    }

    /**
     * Get call-related variables.
     *
     * @return array<string, string>
     */
    private function getCallVariables(): array
    {
        $call = $this->case->call;

        // Extract company name (nullable-safe)
        $calledCompanyName = $call?->phoneNumber?->company?->name ?? '';

        // Extract branch name (nullable-safe)
        $calledBranchName = $call?->phoneNumber?->branch?->name ?? '';

        // Extract formatted service number (nullable-safe)
        $serviceNumberFormatted = $call?->phoneNumber?->formatted_number ?? '';

        // Build receiver display (Branch + Phone)
        $receiverDisplay = '';
        if ($calledBranchName && $serviceNumberFormatted) {
            $receiverDisplay = $calledBranchName.' - '.$serviceNumberFormatted;
        } elseif ($calledBranchName) {
            $receiverDisplay = $calledBranchName;
        } elseif ($serviceNumberFormatted) {
            $receiverDisplay = $serviceNumberFormatted;
        }

        // Format call duration as M:SS
        $callDuration = '';
        if ($call?->duration_sec) {
            $minutes = floor($call->duration_sec / 60);
            $seconds = $call->duration_sec % 60;
            $callDuration = sprintf('%d:%02d', $minutes, $seconds);
        }

        // Format caller number with spaces
        $callerNumber = '';
        if ($call?->from_number) {
            $callerNumber = $this->formatPhoneNumber($call->from_number);
        }

        // Format service number with spaces
        $serviceNumber = '';
        if ($call?->to_number) {
            $serviceNumber = $this->formatPhoneNumber($call->to_number);
        }

        return [
            'call_duration' => $callDuration,
            'caller_number' => $callerNumber,
            'service_number' => $serviceNumber,
            'called_company_name' => $calledCompanyName,
            'called_branch_name' => $calledBranchName,
            'service_number_formatted' => $serviceNumberFormatted,
            'receiver_display' => $receiverDisplay,
        ];
    }

    /**
     * Get AI-related variables.
     *
     * @return array<string, string>
     */
    private function getAiVariables(): array
    {
        $aiMetadata = $this->case->ai_metadata ?? [];

        // Extract AI summary with fallback between 'summary' and 'ai_summary'
        $aiSummary = $aiMetadata['ai_summary'] ?? $aiMetadata['summary'] ?? '';

        // Format AI confidence as percentage (0.95 -> "95%")
        $aiConfidence = '';
        if (isset($aiMetadata['confidence'])) {
            $aiConfidence = number_format($aiMetadata['confidence'] * 100, 0).'%';
        }

        // Extract customer location from AI metadata
        $customerLocation = $aiMetadata['customer_location'] ?? '';

        // Extract problem duration from AI metadata
        $problemSince = $aiMetadata['problem_since'] ?? '';

        return [
            'ai_summary' => $aiSummary,
            'ai_confidence' => $aiConfidence,
            'customer_location' => $customerLocation,
            'problem_since' => $problemSince,
        ];
    }

    /**
     * Get admin-related variables.
     *
     * @return array<string, string>
     */
    private function getAdminVariables(): array
    {
        // Generate signed URL for admin panel access (72h validity)
        $adminUrl = \Illuminate\Support\Facades\URL::signedRoute(
            'filament.admin.resources.service-cases.view',
            ['record' => $this->case->id],
            now()->addHours(72)
        );

        return [
            'admin_url' => $adminUrl,
        ];
    }

    /**
     * Get transcript-related variables.
     *
     * Builds a formatted transcript from RetellTranscriptSegments.
     * Truncates to 10,000 characters with '...' if needed.
     *
     * @return array<string, string>
     */
    private function getTranscriptVariables(): array
    {
        $callSession = $this->case->callSession;

        // Return empty values if no call session or segments
        if (! $callSession || ! $callSession->transcriptSegments) {
            return [
                'transcript' => '',
                'transcript_truncated' => 'Nein',
                'transcript_length' => '',
            ];
        }

        // Build full transcript from segments sorted by sequence
        // Use collection sortBy instead of query to leverage eager-loaded relationship
        $segments = $callSession->transcriptSegments->sortBy('segment_sequence');

        if ($segments->isEmpty()) {
            return [
                'transcript' => '',
                'transcript_truncated' => 'Nein',
                'transcript_length' => '',
            ];
        }

        // Format transcript with role labels
        $fullTranscript = '';
        foreach ($segments as $segment) {
            $roleLabel = $segment->role === 'agent' ? 'Agent' : 'Kunde';
            $fullTranscript .= $roleLabel.': '.$segment->text."\n";
        }

        // Calculate original length
        $originalLength = strlen($fullTranscript);
        $formattedLength = number_format($originalLength, 0, ',', '.').' Zeichen';

        // Truncate if necessary (max 10,000 characters)
        $maxLength = 10000;
        $isTruncated = $originalLength > $maxLength;
        $transcript = $isTruncated
            ? substr($fullTranscript, 0, $maxLength).'...'
            : $fullTranscript;

        return [
            'transcript' => $transcript,
            'transcript_truncated' => $isTruncated ? 'Ja' : 'Nein',
            'transcript_length' => $formattedLength,
        ];
    }

    /**
     * Get source-related variables.
     *
     * @return array<string, string>
     */
    private function getSourceVariables(): array
    {
        return [
            'source' => $this->case->source_label ?? '',
            'case_type' => $this->case->case_type_label ?? '',
            'category' => $this->case->category?->name ?? '',
        ];
    }

    /**
     * Get color-related variables for email styling.
     *
     * @return array<string, string>
     */
    private function getColorVariables(): array
    {
        return [
            'priority_color' => '',
            'status_color' => '',
        ];
    }

    /**
     * Format a phone number with spaces for better readability.
     *
     * Formats German phone numbers (+49) with spaces between segments.
     * Example: +4930123456 becomes +49 30 123456
     *
     * @param  string  $number  Raw phone number
     * @return string Formatted phone number with spaces
     */
    private function formatPhoneNumber(string $number): string
    {
        // Remove all non-numeric characters except +
        $cleaned = preg_replace('/[^0-9+]/', '', $number);

        // Format German numbers (+49)
        if (strpos($cleaned, '+49') === 0) {
            // Format: +49 XXX XXXXXXX
            return preg_replace('/(\+49)(\d{3})(\d+)/', '$1 $2 $3', $cleaned);
        }

        // Return original number if not a recognized format
        return $number;
    }
}
