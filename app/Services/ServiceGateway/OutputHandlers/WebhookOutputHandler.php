<?php

namespace App\Services\ServiceGateway\OutputHandlers;

use App\Models\ServiceCase;
use App\Models\ServiceOutputConfiguration;
use App\Services\Audio\AudioStorageService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WebhookOutputHandler
 *
 * Handler for delivering service case notifications via webhook to external systems.
 * Supports HMAC signature authentication, custom headers, and payload templates.
 * Implements OutputHandlerInterface for service gateway integration.
 *
 * Features:
 * - HMAC-SHA256 signature authentication
 * - Jira/ServiceNow compatible default payload
 * - Custom payload templates with variable substitution
 * - Custom header support
 * - External reference tracking
 * - Comprehensive error logging
 *
 * Flow:
 * 1. Validate webhook configuration exists
 * 2. Build payload (template or default)
 * 3. Generate HMAC signature if secret configured
 * 4. Send HTTP POST request
 * 5. Track external reference if returned
 * 6. Log success/failure
 *
 * @package App\Services\ServiceGateway\OutputHandlers
 */
class WebhookOutputHandler implements OutputHandlerInterface
{
    /**
     * HTTP request timeout in seconds
     *
     * @var int
     */
    private int $timeout = 30;

    /**
     * Deliver service case notification via webhook.
     *
     * Sends HTTP POST request to configured webhook URL with case data.
     * Generates HMAC signature for authentication if secret is configured.
     * Tracks external reference (ticket ID) if returned in response.
     *
     * @param \App\Models\ServiceCase $case Service case to notify about
     * @return bool True if webhook was delivered successfully (2xx status)
     */
    public function deliver(ServiceCase $case): bool
    {
        Log::info('[WebhookOutputHandler] Starting webhook delivery', [
            'case_id' => $case->id,
            'case_type' => $case->case_type,
            'priority' => $case->priority,
        ]);

        // Load configuration relationship
        if (!$case->relationLoaded('category')) {
            $case->load('category.outputConfiguration');
        } else if ($case->category && !$case->category->relationLoaded('outputConfiguration')) {
            $case->category->load('outputConfiguration');
        }

        $config = $case->category?->outputConfiguration;

        // Validate webhook configuration
        if (!$config || !$this->supportsWebhook($config)) {
            Log::warning('[WebhookOutputHandler] No webhook config for case', [
                'case_id' => $case->id,
                'category_id' => $case->category_id,
                'has_config' => !is_null($config),
            ]);
            return false;
        }

        // Build payload and headers
        $payload = $this->buildPayload($case, $config);
        $headers = $this->buildHeaders($case, $config, $payload);

        // Send webhook request
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($headers)
                ->post($config->webhook_url, $payload);

            if ($response->successful()) {
                Log::info('[WebhookOutputHandler] Delivery successful', [
                    'case_id' => $case->id,
                    'status' => $response->status(),
                    'response_size' => strlen($response->body()),
                ]);

                // Store external reference if returned
                // Support common formats: {id: "123"}, {key: "TICKET-123"}, {ticket_id: "123"}
                $externalId = $this->extractExternalId($response->json());

                if ($externalId) {
                    $case->update(['external_reference' => $externalId]);

                    Log::info('[WebhookOutputHandler] External reference stored', [
                        'case_id' => $case->id,
                        'external_id' => $externalId,
                    ]);
                }

                return true;
            }

            // HTTP error (4xx, 5xx)
            Log::error('[WebhookOutputHandler] HTTP error', [
                'case_id' => $case->id,
                'status' => $response->status(),
                'body' => $response->body(),
                'url' => $config->webhook_url,
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error('[WebhookOutputHandler] Exception during delivery', [
                'case_id' => $case->id,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'url' => $config->webhook_url,
            ]);
            return false;
        }
    }

    /**
     * Test webhook delivery configuration without sending.
     *
     * Validates configuration and returns diagnostic information
     * about webhook setup and readiness.
     *
     * @param \App\Models\ServiceCase $case Service case to test
     * @return array Test results with configuration status
     */
    public function test(ServiceCase $case): array
    {
        Log::info('[WebhookOutputHandler] Testing webhook configuration', [
            'case_id' => $case->id,
        ]);

        $results = [
            'handler' => 'webhook',
            'case_id' => $case->id,
            'status' => 'failed',
            'can_deliver' => false,
            'issues' => [],
            'config' => [],
        ];

        // Load configuration
        if (!$case->relationLoaded('category')) {
            $case->load('category.outputConfiguration');
        }

        $config = $case->category?->outputConfiguration;

        if (!$config) {
            $results['issues'][] = 'No output configuration found';
            return $results;
        }

        if (!$this->supportsWebhook($config)) {
            $results['issues'][] = 'Configuration does not support webhook output';
            return $results;
        }

        // Check webhook URL
        if (empty($config->webhook_url)) {
            $results['issues'][] = 'Webhook URL not configured';
            return $results;
        }

        if (!filter_var($config->webhook_url, FILTER_VALIDATE_URL)) {
            $results['issues'][] = 'Webhook URL is not a valid URL';
            return $results;
        }

        // Build test config info
        $results['config'] = [
            'url' => $config->webhook_url,
            'has_secret' => !empty($config->webhook_secret),
            'has_template' => !empty($config->webhook_template),
            'has_custom_headers' => !empty($config->webhook_headers),
            'timeout' => $this->timeout,
        ];

        // Configuration is valid
        $results['status'] = 'ready';
        $results['can_deliver'] = true;

        Log::info('[WebhookOutputHandler] Configuration test passed', [
            'case_id' => $case->id,
            'url' => $config->webhook_url,
        ]);

        return $results;
    }

    /**
     * Get the output handler type identifier.
     *
     * @return string Handler type
     */
    public function getType(): string
    {
        return 'webhook';
    }

    /**
     * Check if configuration supports webhook output.
     *
     * @param mixed $config Output configuration model
     * @return bool True if webhook is supported
     */
    private function supportsWebhook($config): bool
    {
        if (!$config) {
            return false;
        }

        return method_exists($config, 'sendsWebhook')
            && $config->sendsWebhook()
            && !empty($config->webhook_url);
    }

    /**
     * Build webhook payload from case data.
     *
     * Uses custom template if configured, otherwise generates
     * Jira/ServiceNow compatible default payload.
     *
     * @param \App\Models\ServiceCase $case Service case
     * @param \App\Models\ServiceOutputConfiguration $config Output configuration
     * @return array Webhook payload
     */
    private function buildPayload(ServiceCase $case, ServiceOutputConfiguration $config): array
    {
        // Use template if provided
        if (!empty($config->webhook_template)) {
            return $this->renderTemplate($case, $config->webhook_template);
        }

        // Generate audio URL if feature enabled and audio available
        $audioUrl = $this->generateAudioUrl($case, $config);
        $audioTtl = $config->audio_url_ttl_minutes ?? config('gateway.delivery.audio_url_ttl_minutes', 60);

        // Generate default Jira-compatible payload
        return [
            'fields' => [
                'summary' => $case->subject,
                'description' => $case->description,
                'issuetype' => ['name' => $this->mapCaseType($case->case_type)],
                'priority' => ['name' => $this->mapPriority($case->priority)],
                'labels' => ['askpro', 'voice-intake', 'service-gateway'],
                'customfield_10000' => $case->id, // AskPro Case ID
            ],
            'meta' => [
                'source' => 'askpro_service_gateway',
                'version' => '1.1',
                'case_id' => $case->id,
                'company_id' => $case->company_id,
                'call_id' => $case->call_id,
                'customer_id' => $case->customer_id,
                'created_at' => $case->created_at->toIso8601String(),
                'urgency' => $case->urgency,
                'impact' => $case->impact,
            ],
            'enrichment' => [
                'status' => $case->enrichment_status ?? 'pending',
                'enriched_at' => $case->enriched_at?->toIso8601String(),
                'transcript_available' => ($case->transcript_segment_count ?? 0) > 0,
                'transcript_segment_count' => $case->transcript_segment_count,
                'transcript_char_count' => $case->transcript_char_count,
                'audio_available' => !empty($case->audio_object_key),
                'audio_url' => $audioUrl,
                'audio_url_expires_at' => $audioUrl ? now()->addMinutes($audioTtl)->toIso8601String() : null,
            ],
        ];
    }

    /**
     * Build HTTP headers for webhook request.
     *
     * Includes standard headers plus HMAC signature if secret configured.
     *
     * @param \App\Models\ServiceCase $case Service case
     * @param \App\Models\ServiceOutputConfiguration $config Output configuration
     * @param array $payload Webhook payload
     * @return array HTTP headers
     */
    private function buildHeaders(ServiceCase $case, ServiceOutputConfiguration $config, array $payload): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'AskProServiceGateway/1.0',
            'X-AskPro-Case-Id' => (string) $case->id,
            'X-AskPro-Company-Id' => (string) $case->company_id,
            'X-AskPro-Event' => 'service_case.created',
            'X-AskPro-Timestamp' => now()->toIso8601String(),
        ];

        // Add HMAC signature if secret configured
        if (!empty($config->webhook_secret)) {
            $jsonPayload = json_encode($payload);
            $signature = hash_hmac('sha256', $jsonPayload, $config->webhook_secret);
            $headers['X-AskPro-Signature'] = $signature;

            Log::debug('[WebhookOutputHandler] HMAC signature generated', [
                'case_id' => $case->id,
                'payload_size' => strlen($jsonPayload),
            ]);
        }

        // Merge custom headers (custom headers can override defaults)
        if (!empty($config->webhook_headers) && is_array($config->webhook_headers)) {
            $headers = array_merge($headers, $config->webhook_headers);

            Log::debug('[WebhookOutputHandler] Custom headers merged', [
                'case_id' => $case->id,
                'custom_header_count' => count($config->webhook_headers),
            ]);
        }

        return $headers;
    }

    /**
     * Render custom template with case variable substitution.
     *
     * Replaces {{variable}} placeholders with case data.
     *
     * @param \App\Models\ServiceCase $case Service case
     * @param array|string $template Template structure
     * @return array Rendered payload
     */
    private function renderTemplate(ServiceCase $case, $template): array
    {
        // Convert template to JSON string for replacement
        $json = is_array($template) ? json_encode($template) : $template;

        // Get audio URL for template (if enabled)
        $config = $case->category?->outputConfiguration;
        $audioUrl = $config ? $this->generateAudioUrl($case, $config) : null;
        $audioTtl = $config?->audio_url_ttl_minutes ?? config('gateway.delivery.audio_url_ttl_minutes', 60);

        // Build replacement map
        $replacements = [
            // Case fields
            '{{case.id}}' => (string) $case->id,
            '{{case.subject}}' => $case->subject,
            '{{case.description}}' => $case->description,
            '{{case.case_type}}' => $case->case_type,
            '{{case.priority}}' => $case->priority,
            '{{case.urgency}}' => $case->urgency ?? 'normal',
            '{{case.impact}}' => $case->impact ?? 'normal',
            '{{case.status}}' => $case->status,
            '{{case.company_id}}' => (string) $case->company_id,
            '{{case.customer_id}}' => (string) ($case->customer_id ?? ''),
            '{{case.call_id}}' => (string) ($case->call_id ?? ''),
            '{{case.category_id}}' => (string) $case->category_id,
            '{{case.external_reference}}' => $case->external_reference ?? '',
            '{{case.created_at}}' => $case->created_at->toIso8601String(),
            '{{case.updated_at}}' => $case->updated_at->toIso8601String(),

            // Enrichment fields
            '{{enrichment.status}}' => $case->enrichment_status ?? 'pending',
            '{{enrichment.enriched_at}}' => $case->enriched_at?->toIso8601String() ?? '',
            '{{enrichment.transcript_available}}' => ($case->transcript_segment_count ?? 0) > 0 ? 'true' : 'false',
            '{{enrichment.transcript_segment_count}}' => (string) ($case->transcript_segment_count ?? 0),
            '{{enrichment.transcript_char_count}}' => (string) ($case->transcript_char_count ?? 0),
            '{{enrichment.audio_available}}' => !empty($case->audio_object_key) ? 'true' : 'false',
            '{{enrichment.audio_url}}' => $audioUrl ?? '',
            '{{enrichment.audio_url_expires_at}}' => $audioUrl ? now()->addMinutes($audioTtl)->toIso8601String() : '',

            // Meta fields
            '{{timestamp}}' => now()->toIso8601String(),
            '{{source}}' => 'askpro_service_gateway',
        ];

        // Perform replacements
        $json = str_replace(array_keys($replacements), array_values($replacements), $json);

        // Decode back to array
        $rendered = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('[WebhookOutputHandler] Template rendering failed', [
                'case_id' => $case->id,
                'json_error' => json_last_error_msg(),
            ]);

            // Fallback to default payload
            return $this->buildPayload($case, $case->category->outputConfiguration);
        }

        return $rendered;
    }

    /**
     * Map internal case type to external system type.
     *
     * Provides Jira-compatible type mapping.
     *
     * @param string $type Case type
     * @return string External type name
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
     * Map internal priority to external system priority.
     *
     * Provides Jira-compatible priority mapping.
     *
     * @param string $priority Case priority
     * @return string External priority name
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
     * Extract external ticket ID from webhook response.
     *
     * Supports common response formats from Jira, ServiceNow, Zendesk, etc.
     *
     * @param array|null $response Response JSON data
     * @return string|null External ticket ID
     */
    private function extractExternalId(?array $response): ?string
    {
        if (!$response) {
            return null;
        }

        // Try common field names used by various ticket systems
        $fields = [
            'id',           // Generic
            'key',          // Jira
            'ticket_id',    // ServiceNow, Zendesk
            'number',       // ServiceNow
            'issue_id',     // GitHub Issues
            'case_id',      // Salesforce
            'sys_id',       // ServiceNow internal ID
        ];

        foreach ($fields as $field) {
            if (isset($response[$field]) && !empty($response[$field])) {
                return (string) $response[$field];
            }
        }

        return null;
    }

    /**
     * Generate presigned audio URL for webhook payload.
     *
     * Respects the GATEWAY_AUDIO_IN_WEBHOOK feature flag and generates
     * a time-limited presigned URL for secure audio access.
     *
     * @param ServiceCase $case Service case with audio_object_key
     * @param ServiceOutputConfiguration $config Output configuration with TTL settings
     * @return string|null Presigned URL or null if not available/disabled
     */
    private function generateAudioUrl(ServiceCase $case, ServiceOutputConfiguration $config): ?string
    {
        // Check if audio in webhook is enabled (feature flag)
        if (!config('gateway.features.audio_in_webhook', false)) {
            Log::debug('[WebhookOutputHandler] Audio in webhook disabled by feature flag', [
                'case_id' => $case->id,
            ]);
            return null;
        }

        // Check if audio exists
        if (empty($case->audio_object_key)) {
            Log::debug('[WebhookOutputHandler] No audio available for case', [
                'case_id' => $case->id,
            ]);
            return null;
        }

        // Check if audio is expired
        if ($case->audio_expires_at && $case->audio_expires_at->isPast()) {
            Log::debug('[WebhookOutputHandler] Audio expired for case', [
                'case_id' => $case->id,
                'expired_at' => $case->audio_expires_at->toIso8601String(),
            ]);
            return null;
        }

        try {
            // Get TTL from config (default 60 minutes)
            $ttlMinutes = $config->audio_url_ttl_minutes ?? config('gateway.delivery.audio_url_ttl_minutes', 60);

            // Generate presigned URL via AudioStorageService
            $audioService = app(AudioStorageService::class);
            $url = $audioService->getPresignedUrl($case->audio_object_key, $ttlMinutes);

            if ($url) {
                Log::debug('[WebhookOutputHandler] Audio URL generated', [
                    'case_id' => $case->id,
                    'ttl_minutes' => $ttlMinutes,
                ]);
            }

            return $url;

        } catch (\Exception $e) {
            Log::warning('[WebhookOutputHandler] Failed to generate audio URL', [
                'case_id' => $case->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
