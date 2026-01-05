<?php

namespace App\Services\ServiceGateway;

use App\Models\ServiceCase;
use App\Models\WebhookPreset;
use App\Services\Audio\AudioStorageService;
use App\Services\RelativeTimeParser;
use Illuminate\Support\Facades\Log;

/**
 * WebhookTemplateEngine
 *
 * Enhanced template rendering engine for webhook payloads.
 *
 * Supports:
 * - Simple variable substitution: {{variable}}
 * - Nested paths: {{case.subject}}, {{customer.name}}
 * - Default values: {{variable|default:fallback}}
 * - Conditional blocks: {{#if variable}}content{{/if}}
 * - Negative conditionals: {{#unless variable}}content{{/unless}}
 * - Required field validation
 *
 * Thread-safe and stateless design for production use.
 */
class WebhookTemplateEngine
{
    /**
     * Audio storage service for URL generation
     */
    private ?AudioStorageService $audioService = null;

    /**
     * Cached variable context for current render operation
     */
    private array $context = [];

    /**
     * Validation errors from last render
     */
    private array $errors = [];

    /**
     * Render a preset template with case data.
     *
     * @param WebhookPreset $preset The webhook preset template
     * @param ServiceCase $case The service case to render
     * @param array $overrides Additional/override values
     * @return array Rendered payload
     * @throws TemplateRenderException On critical render failure
     */
    public function render(WebhookPreset $preset, ServiceCase $case, array $overrides = []): array
    {
        $this->errors = [];

        // Build context from case data
        $this->context = $this->buildContext($case, $preset, $overrides);

        // Validate required variables
        $missing = $preset->validateRequiredVariables($this->context);
        if (!empty($missing)) {
            $this->errors = array_map(fn($v) => "Missing required variable: {$v}", $missing);
            Log::warning('[WebhookTemplateEngine] Missing required variables', [
                'case_id' => $case->id,
                'preset_id' => $preset->id,
                'missing' => $missing,
            ]);
        }

        // Get template and render
        $template = $preset->payload_template;

        try {
            $rendered = $this->renderTemplate($template);

            Log::debug('[WebhookTemplateEngine] Template rendered successfully', [
                'case_id' => $case->id,
                'preset' => $preset->slug,
                'payload_size' => strlen(json_encode($rendered)),
            ]);

            return $rendered;

        } catch (\Exception $e) {
            Log::error('[WebhookTemplateEngine] Render failed', [
                'case_id' => $case->id,
                'preset_id' => $preset->id,
                'error' => $e->getMessage(),
            ]);

            throw new TemplateRenderException(
                "Failed to render template: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    /**
     * Render a raw template array with provided context.
     *
     * @param array $template Template structure
     * @param array $context Variable context
     * @return array Rendered payload
     */
    public function renderRaw(array $template, array $context): array
    {
        $this->context = $context;
        $this->errors = [];

        return $this->renderTemplate($template);
    }

    /**
     * Get validation errors from last render.
     *
     * @return array List of error messages
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Check if last render had errors.
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Validate a template without rendering.
     *
     * Checks for:
     * - Valid JSON structure
     * - Balanced conditional blocks
     * - Valid variable syntax
     *
     * @param array|string $template Template to validate
     * @return array Validation result ['valid' => bool, 'errors' => array]
     */
    public function validate($template): array
    {
        $errors = [];

        // Convert to JSON string for analysis
        $json = is_array($template) ? json_encode($template) : $template;

        // Check JSON validity
        if (is_string($template)) {
            json_decode($template);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors[] = 'Invalid JSON: ' . json_last_error_msg();
                return ['valid' => false, 'errors' => $errors];
            }
        }

        // Check for balanced conditionals
        $ifCount = preg_match_all('/\{\{#if\s+/', $json);
        $endIfCount = preg_match_all('/\{\{\/if\}\}/', $json);
        if ($ifCount !== $endIfCount) {
            $errors[] = "Unbalanced {{#if}}/{{/if}} blocks: {$ifCount} opens, {$endIfCount} closes";
        }

        $unlessCount = preg_match_all('/\{\{#unless\s+/', $json);
        $endUnlessCount = preg_match_all('/\{\{\/unless\}\}/', $json);
        if ($unlessCount !== $endUnlessCount) {
            $errors[] = "Unbalanced {{#unless}}/{{/unless}} blocks: {$unlessCount} opens, {$endUnlessCount} closes";
        }

        // Check for invalid variable syntax
        if (preg_match_all('/\{\{([^}]*[^a-zA-Z0-9_.|:#\/\s][^}]*)\}\}/', $json, $matches)) {
            foreach ($matches[1] as $invalid) {
                $errors[] = "Invalid variable syntax: {{{$invalid}}}";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Extract all variables from a template.
     *
     * @param array|string $template Template to analyze
     * @return array List of variable names
     */
    public function extractVariables($template): array
    {
        $json = is_array($template) ? json_encode($template) : $template;
        $variables = [];

        // Simple variables: {{variable}} or {{path.to.variable}}
        preg_match_all('/\{\{([a-zA-Z0-9_.]+)(?:\|[^}]+)?\}\}/', $json, $simpleMatches);
        if (!empty($simpleMatches[1])) {
            $variables = array_merge($variables, $simpleMatches[1]);
        }

        // Conditional variables: {{#if variable}} or {{#unless variable}}
        preg_match_all('/\{\{#(?:if|unless)\s+([a-zA-Z0-9_.]+)\}\}/', $json, $condMatches);
        if (!empty($condMatches[1])) {
            $variables = array_merge($variables, $condMatches[1]);
        }

        return array_values(array_unique($variables));
    }

    // =========================================================================
    // Private Implementation
    // =========================================================================

    /**
     * Build the variable context from case data.
     */
    private function buildContext(ServiceCase $case, WebhookPreset $preset, array $overrides): array
    {
        // Load relationships if needed
        if (!$case->relationLoaded('category')) {
            $case->load('category.outputConfiguration');
        }
        if (!$case->relationLoaded('call')) {
            $case->load('call');
        }
        if (!$case->relationLoaded('customer')) {
            $case->load('customer');
        }

        $config = $case->category?->outputConfiguration;
        $aiMeta = $case->ai_metadata ?? [];

        // Generate ticket reference
        $ticketReference = sprintf('TKT-%s-%05d', $case->created_at->format('Y'), $case->id);

        // Generate audio URL if available
        $audioUrl = $this->generateAudioUrl($case, $config);
        $audioTtl = $config?->audio_url_ttl_minutes ?? 60;

        // Build comprehensive context
        $context = [
            // Ticket reference (safe, no internal IDs)
            'ticket' => [
                'reference' => $ticketReference,
            ],

            // Case fields
            'case' => [
                'subject' => $case->subject ?? '',
                'description' => $case->description ?? '',
                'case_type' => $case->case_type ?? 'incident',
                'priority' => $case->priority ?? 'normal',
                'urgency' => $case->urgency ?? 'normal',
                'impact' => $case->impact ?? 'normal',
                'status' => $case->status ?? 'new',
                'category' => $case->category?->name ?? 'General',
                'external_reference' => $case->external_reference ?? '',
                'created_at' => $case->created_at?->toIso8601String() ?? '',
                'updated_at' => $case->updated_at?->toIso8601String() ?? '',
            ],

            // Customer data
            'customer' => [
                'name' => $aiMeta['customer_name'] ?? $case->customer?->name ?? '',
                'phone' => $aiMeta['customer_phone'] ?? $case->customer?->phone ?? '',
                'email' => $aiMeta['customer_email'] ?? $case->customer?->email ?? '',
                'location' => $aiMeta['customer_location'] ?? '',
            ],

            // Context/problem details
            'context' => [
                'problem_since' => $this->enrichProblemSince(
                    $aiMeta['problem_since'] ?? null,
                    $case->created_at
                ),
                'others_affected' => $aiMeta['others_affected'] ?? false,
            ],

            // Enrichment data
            'enrichment' => [
                'status' => $case->enrichment_status ?? 'pending',
                'enriched_at' => $case->enriched_at?->toIso8601String() ?? '',
                'transcript_available' => ($case->transcript_segment_count ?? 0) > 0,
                'transcript_segment_count' => $case->transcript_segment_count ?? 0,
                'audio_available' => !empty($case->audio_object_key),
                'audio_url' => $audioUrl ?? '',
                'audio_url_expires_at' => $audioUrl
                    ? now()->addMinutes($audioTtl)->toIso8601String()
                    : '',
            ],

            // Metadata
            'timestamp' => now()->toIso8601String(),
            'source' => 'service_gateway',
        ];

        // Add transcript if available
        $context['transcript'] = $this->buildTranscriptContext($case);

        // Merge preset defaults
        $context = array_replace_recursive(
            $preset->getMergedDefaults(),
            $context
        );

        // Apply overrides last
        $context = array_replace_recursive($context, $overrides);

        return $context;
    }

    /**
     * Recursively render a template structure.
     */
    private function renderTemplate($template): array
    {
        // Convert to JSON, process, convert back
        $json = json_encode($template);

        // Process conditionals first (they may contain variables)
        $json = $this->processConditionals($json);

        // Process simple variables
        $json = $this->processVariables($json);

        // Decode result
        $result = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('JSON decode failed after rendering: ' . json_last_error_msg());
        }

        return $result;
    }

    /**
     * Process conditional blocks: {{#if var}}...{{/if}} and {{#unless var}}...{{/unless}}
     */
    private function processConditionals(string $json): string
    {
        // Process {{#if variable}}content{{/if}}
        // Use non-greedy matching and handle nested content carefully
        $json = preg_replace_callback(
            '/\{\{#if\s+([a-zA-Z0-9_.]+)\}\}(.*?)\{\{\/if\}\}/s',
            function ($matches) {
                $variable = $matches[1];
                $content = $matches[2];
                $value = $this->getValue($variable);

                // Truthy check: not null, not empty string, not false, not 0
                if ($value !== null && $value !== '' && $value !== false && $value !== 0) {
                    return $content;
                }
                return '';
            },
            $json
        );

        // Process {{#unless variable}}content{{/unless}}
        $json = preg_replace_callback(
            '/\{\{#unless\s+([a-zA-Z0-9_.]+)\}\}(.*?)\{\{\/unless\}\}/s',
            function ($matches) {
                $variable = $matches[1];
                $content = $matches[2];
                $value = $this->getValue($variable);

                // Show content only if value is falsy
                if ($value === null || $value === '' || $value === false || $value === 0) {
                    return $content;
                }
                return '';
            },
            $json
        );

        // Clean up any empty JSON elements that might result from conditionals
        // Remove trailing commas before closing brackets
        $json = preg_replace('/,\s*([\]}])/', '$1', $json);
        // Remove empty strings in arrays
        $json = preg_replace('/,\s*""/', '', $json);
        $json = preg_replace('/""\s*,/', '', $json);

        return $json;
    }

    /**
     * Process simple variables: {{variable}} and {{variable|default:value}}
     */
    private function processVariables(string $json): string
    {
        // Process {{variable|default:value}} first
        $json = preg_replace_callback(
            '/\{\{([a-zA-Z0-9_.]+)\|default:([^}]+)\}\}/',
            function ($matches) {
                $variable = $matches[1];
                $default = $matches[2];
                $value = $this->getValue($variable);

                if ($value === null || $value === '') {
                    return $this->escapeForJson($default);
                }
                return $this->escapeForJson($value);
            },
            $json
        );

        // Process simple {{variable}}
        $json = preg_replace_callback(
            '/\{\{([a-zA-Z0-9_.]+)\}\}/',
            function ($matches) {
                $variable = $matches[1];
                $value = $this->getValue($variable);

                return $this->escapeForJson($value ?? '');
            },
            $json
        );

        return $json;
    }

    /**
     * Get a value from context using dot notation.
     */
    private function getValue(string $path): mixed
    {
        return data_get($this->context, $path);
    }

    /**
     * Escape a value for JSON string context.
     */
    private function escapeForJson(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        // String value - escape for JSON
        $escaped = json_encode((string) $value);
        // Remove surrounding quotes since we're inserting into existing JSON string
        return substr($escaped, 1, -1);
    }

    /**
     * Build transcript context from case data.
     */
    private function buildTranscriptContext(ServiceCase $case): ?array
    {
        $call = $case->call;
        if (!$call || empty($call->transcript)) {
            return null;
        }

        $transcriptData = $call->transcript;

        // Handle JSON string
        if (is_string($transcriptData)) {
            $decoded = json_decode($transcriptData, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $transcriptData = $decoded;
            } else {
                return [
                    'format' => 'text',
                    'content' => $transcriptData,
                    'segment_count' => 1,
                ];
            }
        }

        if (is_array($transcriptData)) {
            $segments = [];
            foreach ($transcriptData as $segment) {
                $segments[] = [
                    'role' => $segment['role'] ?? 'unknown',
                    'content' => $segment['content'] ?? $segment['words'] ?? '',
                    'timestamp' => $segment['timestamp'] ?? null,
                ];
            }

            return [
                'format' => 'segments',
                'segments' => $segments,
                'segment_count' => count($segments),
            ];
        }

        return null;
    }

    /**
     * Enrich problem_since with absolute timestamp.
     */
    private function enrichProblemSince(?string $value, $referenceTime = null): ?string
    {
        if (empty($value)) {
            return null;
        }

        // Check if already enriched
        $alreadyEnrichedPatterns = [
            '/\(\d{1,2}:\d{2}\s*Uhr/',
            '/[A-Z][a-z]\.\s+\d{1,2}\.\s+[A-Z][a-z]{2}\.\s+\d{1,2}:\d{2}/',
            '/\d{1,2}:\d{2}\s+Uhr,\s+[A-Z][a-z]\./',
        ];

        foreach ($alreadyEnrichedPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return $value;
            }
        }

        $parser = new RelativeTimeParser();
        return $parser->format($value, $referenceTime ?? now());
    }

    /**
     * Generate presigned audio URL.
     */
    private function generateAudioUrl(ServiceCase $case, $config): ?string
    {
        if (!config('gateway.features.audio_in_webhook', false)) {
            return null;
        }

        if (empty($case->audio_object_key)) {
            return null;
        }

        if ($case->audio_expires_at && $case->audio_expires_at->isPast()) {
            return null;
        }

        try {
            $this->audioService ??= app(AudioStorageService::class);
            $ttlMinutes = $config?->audio_url_ttl_minutes ?? 60;

            return $this->audioService->getPresignedUrl($case->audio_object_key, $ttlMinutes);
        } catch (\Exception $e) {
            Log::warning('[WebhookTemplateEngine] Failed to generate audio URL', [
                'case_id' => $case->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}

/**
 * Custom exception for template render failures.
 */
class TemplateRenderException extends \RuntimeException
{
}
