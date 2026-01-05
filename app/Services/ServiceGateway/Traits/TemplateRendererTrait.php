<?php

declare(strict_types=1);

namespace App\Services\ServiceGateway\Traits;

use App\Models\ServiceCase;
use App\Services\RelativeTimeParser;

/**
 * TemplateRendererTrait
 *
 * Provides Mustache-style template rendering for email and webhook templates.
 * Supports simple placeholders {{variable}} and conditional blocks {{#key}}...{{/key}}
 *
 * @used-by \App\Mail\CustomTemplateEmail
 * @used-by \App\Services\ServiceGateway\OutputHandlers\WebhookOutputHandler
 */
trait TemplateRendererTrait
{
    /**
     * Render Mustache-style template with ServiceCase data.
     *
     * @param string $template Template with {{placeholders}}
     * @param ServiceCase $case Source data
     * @param array $extraData Additional data to merge
     * @return string Rendered template
     */
    protected function renderMustacheTemplate(string $template, ServiceCase $case, array $extraData = []): string
    {
        $data = $this->buildTemplateData($case, $extraData);

        // Process conditional blocks first {{#key}}...{{/key}}
        $template = $this->processConditionalBlocks($template, $data);

        // Replace simple placeholders {{variable}}
        return $this->replaceSimplePlaceholders($template, $data);
    }

    /**
     * Build template data map from ServiceCase.
     *
     * Supported placeholders:
     * - Ticket: ticket_id, subject, description, priority, priority_label, priority_color,
     *           status_label, case_type_label, category_name, category_prefix, created_at, ticket_url
     * - Customer: customer_name, customer_phone, customer_email, customer_location
     * - Additional: problem_since, others_affected, retell_call_id, has_additional_info
     *
     * @param ServiceCase $case
     * @param array $extraData
     * @return array<string, mixed>
     */
    protected function buildTemplateData(ServiceCase $case, array $extraData = []): array
    {
        $aiMetadata = $case->ai_metadata ?? [];

        // Customer data: prefer ai_metadata, fallback to customer relationship
        $customerName = $aiMetadata['customer_name'] ?? $case->customer?->name ?? '';
        $customerPhone = $aiMetadata['customer_phone'] ?? $case->customer?->phone ?? '';
        $customerEmail = $aiMetadata['customer_email'] ?? $case->customer?->email ?? '';
        $customerLocation = $aiMetadata['customer_location'] ?? '';

        // Additional info - enrich problem_since with absolute timestamp
        $problemSince = $this->enrichProblemSince(
            $aiMetadata['problem_since'] ?? '',
            $case->created_at
        );
        $othersAffected = $aiMetadata['others_affected'] ?? false;

        // Retell call ID from various sources
        $retellCallId = $case->call?->retell_call_id
            ?? $aiMetadata['retell_call_id']
            ?? $case->call_id
            ?? '';

        return array_merge([
            // Ticket identifiers
            'ticket_id' => $case->formatted_id ?? 'TKT-' . $case->id,
            'subject' => $case->subject ?? '',
            'description' => $case->description ?? '',

            // Priority
            'priority' => $case->priority ?? 'normal',
            'priority_label' => $this->getPriorityLabel($case->priority ?? 'normal'),
            'priority_color' => $this->getPriorityColor($case->priority ?? 'normal'),

            // Status & Type
            'status_label' => $this->getStatusLabel($case->status ?? 'new'),
            'case_type_label' => $this->getCaseTypeLabel($case->case_type ?? 'request'),

            // Category
            'category_name' => $case->category?->name ?? 'Allgemein',
            'category_prefix' => $this->getCategoryPrefix($case->category?->name),

            // Timestamps
            'created_at' => $case->created_at?->format('d.m.Y H:i') ?? now()->format('d.m.Y H:i'),

            // Customer data
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'customer_email' => $customerEmail,
            'customer_location' => $customerLocation,

            // Additional info
            'problem_since' => $problemSince,
            'others_affected' => $this->formatOthersAffected($othersAffected),
            'retell_call_id' => $retellCallId,

            // Boolean flags for conditionals
            'has_additional_info' => !empty($problemSince) || $this->isTruthy($othersAffected),

            // URLs
            'ticket_url' => config('app.url') . '/admin/service-cases/' . $case->id,
        ], $extraData);
    }

    /**
     * Process Mustache conditional blocks {{#key}}...{{/key}}
     *
     * - If value is truthy, keeps the content
     * - If value is falsy/empty, removes entire block
     */
    protected function processConditionalBlocks(string $template, array $data): string
    {
        // Match {{#key}}content{{/key}} including nested content
        return preg_replace_callback(
            '/\{\{#(\w+)\}\}(.*?)\{\{\/\1\}\}/s',
            function ($matches) use ($data) {
                $key = $matches[1];
                $content = $matches[2];

                $value = $data[$key] ?? null;

                // Check if condition is truthy
                if ($this->isTruthy($value)) {
                    // Process nested placeholders in the kept content
                    return $this->replaceSimplePlaceholders($content, $data);
                }

                return ''; // Remove entire block
            },
            $template
        ) ?? $template;
    }

    /**
     * Replace simple {{placeholder}} variables.
     */
    protected function replaceSimplePlaceholders(string $template, array $data): string
    {
        foreach ($data as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $template = str_replace('{{' . $key . '}}', (string) $value, $template);
            } elseif (is_bool($value)) {
                $template = str_replace('{{' . $key . '}}', $value ? 'Ja' : 'Nein', $template);
            }
        }

        return $template;
    }

    /**
     * Check if value is truthy for conditional blocks.
     */
    protected function isTruthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $lower = strtolower(trim($value));
            // German "ja" or "nein" handling
            if (in_array($lower, ['ja', 'yes', 'true', '1'])) {
                return true;
            }
            if (in_array($lower, ['nein', 'no', 'false', '0', ''])) {
                return false;
            }
            return !empty($value);
        }

        return !empty($value);
    }

    /**
     * Format others_affected for display.
     */
    protected function formatOthersAffected(mixed $value): string
    {
        if ($this->isTruthy($value)) {
            return 'Ja - Mehrere Mitarbeiter betroffen';
        }
        return '';
    }

    /**
     * Enrich problem_since with absolute timestamp if not already enriched.
     *
     * Converts relative times like "seit fünfzehn Minuten" to include
     * absolute timestamp: "seit fünfzehn Minuten (17:06 Uhr, Mo. 23. Dez. 2025)"
     *
     * @param string $value The problem_since value
     * @param \Carbon\Carbon|null $referenceTime Reference time (case creation time)
     * @return string Enriched value or original if empty/already enriched
     */
    protected function enrichProblemSince(string $value, $referenceTime = null): string
    {
        if (empty($value)) {
            return '';
        }

        // Check if already enriched (contains parentheses with time)
        if (preg_match('/\(\d{1,2}:\d{2}\s*Uhr/', $value)) {
            return $value;
        }

        // Parse and enrich using RelativeTimeParser
        $parser = new RelativeTimeParser();
        return $parser->format($value, $referenceTime ?? now());
    }

    /**
     * Get priority label in German.
     */
    protected function getPriorityLabel(string $priority): string
    {
        return match ($priority) {
            'critical' => 'KRITISCH',
            'high' => 'HOCH',
            'normal' => 'NORMAL',
            'low' => 'NIEDRIG',
            default => 'NORMAL',
        };
    }

    /**
     * Get priority color (hex).
     */
    protected function getPriorityColor(string $priority): string
    {
        return match ($priority) {
            'critical' => '#dc2626',  // red-600
            'high' => '#ea580c',      // orange-600
            'normal' => '#2563eb',    // blue-600
            'low' => '#16a34a',       // green-600
            default => '#2563eb',
        };
    }

    /**
     * Get status label in German.
     */
    protected function getStatusLabel(string $status): string
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
     * Get case type label in German.
     */
    protected function getCaseTypeLabel(string $type): string
    {
        return match ($type) {
            'incident' => 'Störung',
            'request' => 'Anfrage',
            'inquiry' => 'Rückfrage',
            default => 'Anfrage',
        };
    }

    /**
     * Get category prefix/emoji based on category name.
     */
    protected function getCategoryPrefix(?string $categoryName): string
    {
        if (!$categoryName) {
            return '[IT-Support]';
        }

        $lower = strtolower($categoryName);

        return match (true) {
            str_contains($lower, 'netzwerk') => '[Netzwerk]',
            str_contains($lower, 'm365') || str_contains($lower, 'microsoft') || str_contains($lower, 'cloud') => '[M365]',
            str_contains($lower, 'hardware') || str_contains($lower, 'arbeitsplatz') || str_contains($lower, 'endgerät') => '[Hardware]',
            str_contains($lower, 'drucker') || str_contains($lower, 'scanner') => '[Drucker]',
            str_contains($lower, 'sicherheit') || str_contains($lower, 'security') => '[SICHERHEIT]',
            str_contains($lower, 'server') => '[Server]',
            str_contains($lower, 'software') => '[Software]',
            str_contains($lower, 'telefon') || str_contains($lower, 'kommunikation') => '[Telefonie]',
            str_contains($lower, 'zugang') || str_contains($lower, 'benutzer') => '[Zugang]',
            default => '[IT-Support]',
        };
    }
}
