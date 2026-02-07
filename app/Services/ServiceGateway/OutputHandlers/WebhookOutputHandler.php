<?php

namespace App\Services\ServiceGateway\OutputHandlers;

use App\Constants\ServiceGatewayConstants;
use App\Models\ServiceCase;
use App\Models\ServiceOutputConfiguration;
use App\Services\Audio\AudioStorageService;
use App\Services\RelativeTimeParser;
use App\Services\ServiceGateway\ExchangeLogService;
use App\Services\ServiceGateway\Traits\UsesWebhookPresets;
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
    use UsesWebhookPresets;

    /**
     * HTTP request timeout in seconds
     *
     * @var int
     */
    private int $timeout = ServiceGatewayConstants::WEBHOOK_TIMEOUT_SECONDS;

    /**
     * Exchange log service for audit logging
     */
    private ExchangeLogService $exchangeLogService;

    public function __construct(?ExchangeLogService $exchangeLogService = null)
    {
        $this->exchangeLogService = $exchangeLogService ?? app(ExchangeLogService::class);
    }

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

        // SSRF-001: Runtime check - block internal/private URLs
        if (!$this->isExternalUrl($config->webhook_url)) {
            Log::critical('[WebhookOutputHandler] SSRF-001: Blocked internal URL at runtime', [
                'case_id' => $case->id,
                'config_id' => $config->id,
                'url' => $this->maskUrl($config->webhook_url),
            ]);
            return false;
        }

        // Build payload and headers
        $payload = $this->buildPayload($case, $config);
        $headers = $this->buildHeaders($case, $config, $payload);

        // Log detailed payload information for debugging
        Log::info('[WebhookOutputHandler] 📤 Webhook Payload Details', [
            'ticket_reference' => $payload['ticket']['reference'] ?? null,
            'webhook_url' => $this->maskUrl($config->webhook_url),
            'payload_summary' => [
                'summary' => $payload['ticket']['summary'] ?? null,
                'description_length' => strlen($payload['ticket']['description'] ?? ''),
                'priority' => $payload['ticket']['priority'] ?? null,
                'type' => $payload['ticket']['type'] ?? null,
                'category' => $payload['ticket']['category'] ?? null,
            ],
            'customer_data' => $payload['customer'] ?? [],
            'context_data' => $payload['context'] ?? [],
            'transcript_included' => isset($payload['transcript']),
            'transcript_segments' => $payload['transcript']['segment_count'] ?? 0,
            'audio_url_included' => isset($payload['audio']['url']),
            'total_payload_size' => strlen(json_encode($payload)),
        ]);

        // Send webhook request
        // SSRF-001: withoutRedirecting() prevents redirect-based SSRF attacks
        $startTime = microtime(true);

        try {
            $response = Http::timeout($this->timeout)
                ->withoutRedirecting()
                ->withHeaders($headers)
                ->post($config->webhook_url, $payload);

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);
            $responseBody = $response->json() ?? [];

            if ($response->successful()) {
                Log::info('[WebhookOutputHandler] ✅ Webhook Delivery Successful', [
                    'case_id' => $case->id,
                    'http_status' => $response->status(),
                    'response_size' => strlen($response->body()),
                    'response_body' => $this->truncateForLog($response->body(), 500),
                    'delivery_time_ms' => $durationMs,
                ]);

                // Log to Exchange Logs for Filament visibility
                $this->exchangeLogService->logOutbound(
                    endpoint: $config->webhook_url,
                    method: 'POST',
                    requestBody: $payload,
                    responseBody: $responseBody,
                    statusCode: $response->status(),
                    durationMs: $durationMs,
                    callId: $case->call_id,
                    serviceCaseId: $case->id,
                    companyId: $case->company_id,
                    correlationId: $case->id . '-webhook-' . now()->format('His'),
                    outputConfigurationId: $config->id,
                );

                // Store external reference if returned
                // Support common formats: {id: "123"}, {key: "TICKET-123"}, {ticket_id: "123"}
                $externalId = $this->extractExternalId($responseBody);

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

            // Log failed delivery to Exchange Logs
            $this->exchangeLogService->logOutbound(
                endpoint: $config->webhook_url,
                method: 'POST',
                requestBody: $payload,
                responseBody: $responseBody,
                statusCode: $response->status(),
                durationMs: $durationMs,
                callId: $case->call_id,
                serviceCaseId: $case->id,
                companyId: $case->company_id,
                correlationId: $case->id . '-webhook-' . now()->format('His'),
                errorClass: 'HttpError',
                errorMessage: 'HTTP ' . $response->status() . ': ' . $this->truncateForLog($response->body(), 200),
                outputConfigurationId: $config->id,
            );

            return false;

        } catch (\Exception $e) {
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            Log::error('[WebhookOutputHandler] Exception during delivery', [
                'case_id' => $case->id,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'url' => $config->webhook_url,
            ]);

            // Log exception to Exchange Logs
            $this->exchangeLogService->logOutbound(
                endpoint: $config->webhook_url,
                method: 'POST',
                requestBody: $payload,
                responseBody: null,
                statusCode: null,
                durationMs: $durationMs,
                callId: $case->call_id,
                serviceCaseId: $case->id,
                companyId: $case->company_id,
                correlationId: $case->id . '-webhook-' . now()->format('His'),
                errorClass: get_class($e),
                errorMessage: $e->getMessage(),
                outputConfigurationId: $config->id,
            );

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
            'has_template' => !empty($config->webhook_payload_template),
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
     * Uses webhookIsActive() which checks both output_type and webhook_enabled toggle.
     *
     * @param mixed $config Output configuration model
     * @return bool True if webhook is supported and enabled
     */
    private function supportsWebhook($config): bool
    {
        if (!$config) {
            return false;
        }

        // Use webhookIsActive() which respects both output_type and webhook_enabled toggle
        return method_exists($config, 'webhookIsActive')
            && $config->webhookIsActive()
            && !empty($config->webhook_url);
    }

    /**
     * Validate webhook configuration.
     *
     * Checks if the configuration has required fields and valid URL format.
     * Used for pre-flight validation in admin UI.
     *
     * @param ServiceOutputConfiguration $config Output configuration to validate
     * @return bool True if configuration is valid
     */
    public function validate(ServiceOutputConfiguration $config): bool
    {
        // Check webhook URL is present
        if (empty($config->webhook_url)) {
            Log::debug('[WebhookOutputHandler] Validation failed: empty webhook_url', [
                'config_id' => $config->id,
            ]);
            return false;
        }

        // Validate URL format (must be valid URL)
        if (!filter_var($config->webhook_url, FILTER_VALIDATE_URL)) {
            Log::debug('[WebhookOutputHandler] Validation failed: invalid URL format', [
                'config_id' => $config->id,
                'url' => $this->maskUrl($config->webhook_url),
            ]);
            return false;
        }

        // SSRF-001: Block internal/private network access
        if (!$this->isExternalUrl($config->webhook_url)) {
            Log::warning('[WebhookOutputHandler] SSRF-001: Blocked internal URL', [
                'config_id' => $config->id,
                'url' => $this->maskUrl($config->webhook_url),
            ]);
            return false;
        }

        // Warn but don't fail for non-HTTPS (might be internal)
        if (!str_starts_with($config->webhook_url, 'https://')) {
            Log::warning('[WebhookOutputHandler] Non-HTTPS webhook URL', [
                'config_id' => $config->id,
            ]);
        }

        return true;
    }

    /**
     * SSRF-001: Hardened external URL validation.
     *
     * Defense-in-depth approach addressing:
     * - Protocol whitelist (https only in production)
     * - Port whitelist (80, 443)
     * - Hostname blocklist with canonicalization
     * - IPv6 awareness (including IPv4-mapped addresses)
     * - Alternative IP notation (decimal, octal, hex)
     * - DNS resolution with ALL record validation
     *
     * @param string $url URL to validate
     * @return bool True if URL is safe (external), false if internal/private
     */
    private function isExternalUrl(string $url): bool
    {
        // 1. Parse URL and validate structure
        $parsed = parse_url($url);
        if (!$parsed || empty($parsed['host'])) {
            Log::debug('[SSRF] Invalid URL structure', ['url' => $this->maskUrl($url)]);
            return false;
        }

        $scheme = strtolower($parsed['scheme'] ?? '');
        $host = strtolower($parsed['host']);
        $port = $parsed['port'] ?? null;

        // 2. Protocol whitelist - HTTPS only in production
        $allowedSchemes = app()->isProduction() ? ['https'] : ['http', 'https'];
        if (!in_array($scheme, $allowedSchemes, true)) {
            Log::debug('[SSRF] Blocked scheme', ['scheme' => $scheme, 'url' => $this->maskUrl($url)]);
            return false;
        }

        // 3. Port whitelist (null = default port)
        $allowedPorts = [null, 80, 443];
        if (!in_array($port, $allowedPorts, true)) {
            Log::debug('[SSRF] Blocked port', ['port' => $port, 'url' => $this->maskUrl($url)]);
            return false;
        }

        // 4. Detect URL encoding tricks and suspicious patterns
        if (preg_match('/%[0-9a-f]{2}|\\\\|@.*@|\x00/i', $url)) {
            Log::debug('[SSRF] Blocked suspicious URL encoding', ['url' => $this->maskUrl($url)]);
            return false;
        }

        // 5. Strip IPv6 brackets and interface identifier for validation
        $hostToCheck = $host;
        if (preg_match('/^\[(.+)\]$/', $host, $matches)) {
            $hostToCheck = $matches[1];
            $hostToCheck = preg_replace('/%.*$/', '', $hostToCheck); // Strip %eth0 etc.
        }

        // 6. Block known dangerous hostnames
        $blockedHostnames = [
            'localhost', 'localhost.localdomain', 'ip6-localhost', 'ip6-loopback',
            'kubernetes.default', 'kubernetes.default.svc',
            'metadata.google.internal', 'instance-data', // Cloud metadata
        ];
        if (in_array($hostToCheck, $blockedHostnames, true)) {
            Log::debug('[SSRF] Blocked hostname', ['host' => $host]);
            return false;
        }

        // 7. Check if host looks like an IP address (starts with digit, 0x, or is IPv6)
        //    Only call isBlockedIp() for IP-like hosts, NOT for hostnames
        $looksLikeIp = preg_match('/^[\d\[]|^0x/i', $hostToCheck);
        if ($looksLikeIp) {
            $normalizedIp = $this->normalizeIpAddress($hostToCheck);
            if ($normalizedIp !== false && $this->isBlockedIp($normalizedIp)) {
                Log::debug('[SSRF] Blocked IP address', ['ip' => $hostToCheck, 'normalized' => $normalizedIp]);
                return false;
            }
            // If normalizedIp is false but it looks like an IP, it's likely an obfuscation attempt
            if ($normalizedIp === false) {
                Log::debug('[SSRF] Invalid IP notation blocked', ['ip' => $hostToCheck]);
                return false;
            }
            // If it's a valid public IP, allow it (no need for DNS resolution)
            return true;
        }

        // 8. Resolve ALL DNS records (A and AAAA) for hostnames
        $resolvedIps = $this->resolveAllIps($hostToCheck);
        if (empty($resolvedIps)) {
            Log::debug('[SSRF] DNS resolution failed', ['host' => $host]);
            return false;
        }

        // 9. Validate ALL resolved IPs are external
        foreach ($resolvedIps as $ip) {
            if ($this->isBlockedIp($ip)) {
                Log::debug('[SSRF] Resolved to blocked IP', [
                    'host' => $host,
                    'resolved_ip' => $ip,
                ]);
                return false;
            }
        }

        return true;
    }

    /**
     * Check if IP address is in a blocked range.
     *
     * Handles IPv4, IPv6, IPv4-mapped IPv6, and alternative notations.
     */
    private function isBlockedIp(string $ip): bool
    {
        // Normalize IP to detect obfuscation (decimal, octal, hex)
        $normalizedIp = $this->normalizeIpAddress($ip);
        if ($normalizedIp === false) {
            return true; // Invalid = blocked
        }

        // Check IPv4
        if (filter_var($normalizedIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return filter_var(
                $normalizedIp,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            ) === false;
        }

        // Check IPv6
        if (filter_var($normalizedIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // IPv4-mapped IPv6 (::ffff:x.x.x.x)
            if (preg_match('/^::ffff:(\d+\.\d+\.\d+\.\d+)$/i', $normalizedIp, $matches)) {
                return $this->isBlockedIp($matches[1]);
            }
            // Loopback (::1)
            if (in_array($normalizedIp, ['::1', '0:0:0:0:0:0:0:1'], true)) {
                return true;
            }
            // Link-local (fe80::/10)
            if (preg_match('/^fe[89ab][0-9a-f]:/i', $normalizedIp)) {
                return true;
            }
            // Unique local (fc00::/7)
            if (preg_match('/^f[cd][0-9a-f]{2}:/i', $normalizedIp)) {
                return true;
            }
            return false;
        }

        return true; // Unknown format = blocked
    }

    /**
     * Normalize IP address to standard format.
     *
     * Converts decimal (2130706433), octal (0177.0.0.1), hex (0x7f.0.0.1) to dotted decimal.
     */
    private function normalizeIpAddress(string $ip): string|false
    {
        // Standard validation first
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }

        // Decimal notation (e.g., 2130706433 = 127.0.0.1)
        if (preg_match('/^\d{8,10}$/', $ip)) {
            $long = (int) $ip;
            if ($long >= 0 && $long <= 4294967295) {
                return long2ip($long);
            }
        }

        // Octal/hex notation (e.g., 0177.0.0.1, 0x7f.0.0.1)
        if (preg_match('/^([0-9a-fx]+)\.([0-9a-fx]+)\.([0-9a-fx]+)\.([0-9a-fx]+)$/i', $ip, $parts)) {
            $octets = [];
            for ($i = 1; $i <= 4; $i++) {
                $octet = $this->parseOctet($parts[$i]);
                if ($octet === false || $octet < 0 || $octet > 255) {
                    return false;
                }
                $octets[] = $octet;
            }
            return implode('.', $octets);
        }

        // Shortened notation (e.g., 127.1 = 127.0.0.1)
        if (preg_match('/^(\d+)\.(\d+)$/', $ip, $parts)) {
            $first = (int) $parts[1];
            $last = (int) $parts[2];
            if ($first <= 255 && $last <= 16777215) {
                return long2ip(($first << 24) | $last);
            }
        }

        return false;
    }

    /**
     * Parse an octet in decimal, octal, or hex format.
     */
    private function parseOctet(string $value): int|false
    {
        $value = strtolower($value);
        if (str_starts_with($value, '0x')) {
            return hexdec($value);
        }
        if (str_starts_with($value, '0') && strlen($value) > 1 && ctype_digit($value)) {
            return octdec($value);
        }
        if (ctype_digit($value)) {
            return (int) $value;
        }
        return false;
    }

    /**
     * Resolve all IP addresses for a hostname (A and AAAA records).
     */
    private function resolveAllIps(string $host): array
    {
        $ips = [];

        // Get IPv4 addresses
        $dns4 = @dns_get_record($host, DNS_A);
        if ($dns4) {
            foreach ($dns4 as $record) {
                if (isset($record['ip'])) {
                    $ips[] = $record['ip'];
                }
            }
        }

        // Get IPv6 addresses
        $dns6 = @dns_get_record($host, DNS_AAAA);
        if ($dns6) {
            foreach ($dns6 as $record) {
                if (isset($record['ipv6'])) {
                    $ips[] = $record['ipv6'];
                }
            }
        }

        // Fallback to gethostbyname if dns_get_record fails
        if (empty($ips)) {
            $ip = gethostbyname($host);
            if ($ip !== $host) {
                $ips[] = $ip;
            }
        }

        return array_unique($ips);
    }

    /**
     * Build webhook payload from case data.
     *
     * Priority order:
     * 1. Linked webhook preset template (if configured)
     * 2. Custom template in configuration
     * 3. Default Jira/ServiceNow compatible payload
     *
     * @param \App\Models\ServiceCase $case Service case
     * @param \App\Models\ServiceOutputConfiguration $config Output configuration
     * @return array Webhook payload
     */
    private function buildPayload(ServiceCase $case, ServiceOutputConfiguration $config): array
    {
        // Priority 1: Try preset template first (new feature)
        $presetPayload = $this->buildPayloadFromPreset($case, $config);
        if ($presetPayload !== null) {
            Log::info('[WebhookOutputHandler] Using preset template', [
                'case_id' => $case->id,
                'preset_id' => $config->webhook_preset_id,
                'preset_name' => $config->webhookPreset?->name,
            ]);
            return $presetPayload;
        }

        // Priority 2: Use custom template if provided
        if (!empty($config->webhook_payload_template)) {
            Log::info('[WebhookOutputHandler] Using custom payload template', [
                'case_id' => $case->id,
                'config_id' => $config->id,
            ]);
            return $this->renderTemplate($case, $config->webhook_payload_template);
        }

        // Generate audio URL if feature enabled and audio available
        $audioUrl = $this->generateAudioUrl($case, $config);
        $audioTtl = $config->audio_url_ttl_minutes ?? config('gateway.delivery.audio_url_ttl_minutes', 60);

        // Build clean payload - no internal IDs, no architecture hints
        // Only data the recipient actually needs
        $ticketReference = sprintf('TKT-%s-%05d', $case->created_at->format('Y'), $case->id);

        $payload = [
            'ticket' => [
                'reference' => $ticketReference,
                'summary' => $case->subject,
                'description' => $case->description,
                'type' => $case->case_type,
                'priority' => $case->priority,
                'category' => $case->category?->name ?? 'Allgemein',
                'created_at' => $case->created_at->toIso8601String(),
            ],
            'context' => [
                'urgency' => $case->urgency ?? 'normal',
                'impact' => $case->impact ?? 'normal',
            ],
        ];

        // Add audio URL only if available (no redundant boolean flags)
        if ($audioUrl) {
            $payload['audio'] = [
                'url' => $audioUrl,
                'expires_at' => now()->addMinutes($audioTtl)->toIso8601String(),
            ];
        }

        // Include customer data from ai_metadata
        $aiMeta = $case->ai_metadata ?? [];
        $payload['customer'] = [
            'name' => $aiMeta['customer_name'] ?? $case->customer?->name ?? null,
            'company' => $aiMeta['customer_company'] ?? null,
            'phone' => $aiMeta['customer_phone'] ?? $case->customer?->phone ?? null,
            'email' => $aiMeta['customer_email'] ?? $case->customer?->email ?? null,
            'location' => $aiMeta['customer_location'] ?? null,
        ];

        // Add problem context (enriched timestamp, no redundant original)
        $problemSince = $aiMeta['problem_since'] ?? null;
        $payload['context']['problem_since'] = $this->enrichProblemSince($problemSince, $case->created_at);
        $payload['context']['others_affected'] = $aiMeta['others_affected'] ?? false;

        // Include triage details from AI classification (K3: use_case_detail)
        if (!empty($aiMeta['use_case_detail'])) {
            $payload['context']['use_case_detail'] = $aiMeta['use_case_detail'];
        }
        if (!empty($aiMeta['use_case_category'])) {
            $payload['context']['use_case_category'] = $aiMeta['use_case_category'];
        }

        // Include transcript if configured (clean structure)
        if ($config->webhook_include_transcript ?? false) {
            $transcript = $this->buildTranscriptPayload($case);
            if ($transcript) {
                $payload['transcript'] = $transcript;
            }
        }

        return $payload;
    }

    /**
     * Build HTTP headers for webhook request.
     *
     * Priority order for headers:
     * 1. Standard headers (always included)
     * 2. Preset headers template (if using preset)
     * 3. Custom headers from configuration
     * 4. HMAC signature (if secret configured)
     *
     * @param \App\Models\ServiceCase $case Service case
     * @param \App\Models\ServiceOutputConfiguration $config Output configuration
     * @param array $payload Webhook payload
     * @return array HTTP headers
     */
    private function buildHeaders(ServiceCase $case, ServiceOutputConfiguration $config, array $payload): array
    {
        // Clean headers - no internal IDs exposed
        // Use opaque ticket reference instead
        $ticketReference = $payload['ticket']['reference'] ?? sprintf('TKT-%s-%05d', $case->created_at->format('Y'), $case->id);

        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'ServiceGateway/1.0',
            'X-Ticket-Reference' => $ticketReference,
            'X-Event' => 'service_case.created',
            'X-Timestamp' => now()->toIso8601String(),
        ];

        // Merge preset headers if using a preset
        $presetHeaders = $this->buildHeadersFromPreset($config);
        if ($presetHeaders && is_array($presetHeaders)) {
            $headers = array_merge($headers, $presetHeaders);

            Log::debug('[WebhookOutputHandler] Preset headers merged', [
                'case_id' => $case->id,
                'preset_header_count' => count($presetHeaders),
            ]);
        }

        // Add HMAC signature if secret configured
        if (!empty($config->webhook_secret)) {
            $jsonPayload = json_encode($payload);
            $signature = hash_hmac('sha256', $jsonPayload, $config->webhook_secret);
            $headers['X-Signature'] = $signature;

            Log::debug('[WebhookOutputHandler] HMAC signature generated', [
                'case_id' => $case->id,
                'payload_size' => strlen($jsonPayload),
            ]);
        }

        // Merge custom headers (custom headers can override preset and defaults)
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
        // SECURITY: Internal IDs (case.id, company_id, customer_id, call_id, category_id)
        // are intentionally NOT exposed. Use {{ticket.reference}} for opaque identification.
        $ticketReference = sprintf('TKT-%s-%05d', $case->created_at->format('Y'), $case->id);

        $replacements = [
            // Ticket reference (opaque, no internal ID exposure)
            '{{ticket.reference}}' => $ticketReference,

            // Case business fields (safe to expose)
            '{{case.subject}}' => $case->subject,
            '{{case.description}}' => $case->description,
            '{{case.case_type}}' => $case->case_type,
            '{{case.priority}}' => $case->priority,
            '{{case.urgency}}' => $case->urgency ?? 'normal',
            '{{case.impact}}' => $case->impact ?? 'normal',
            '{{case.status}}' => $case->status,
            '{{case.category}}' => $case->category?->name ?? 'Allgemein',
            '{{case.external_reference}}' => $case->external_reference ?? '',
            '{{case.created_at}}' => $case->created_at->toIso8601String(),
            '{{case.updated_at}}' => $case->updated_at->toIso8601String(),

            // Enrichment fields (safe, no IDs)
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
     * Supports common response formats from Jira, ServiceNow, Zendesk, VisionaryData, etc.
     * Includes type validation, length checking, and sanitization for robustness.
     *
     * @param array|null $response Response JSON data
     * @return string|null External ticket ID (max 100 chars, sanitized)
     */
    private function extractExternalId(?array $response): ?string
    {
        if (!$response) {
            return null;
        }

        // Common field names used by various ticket systems
        // Priority order: first match wins
        $fields = [
            'id',           // Generic
            'key',          // Jira
            'ticket_id',    // ServiceNow, Zendesk, VisionaryData
            'number',       // ServiceNow
            'issue_id',     // GitHub Issues
            'case_id',      // Salesforce
            'sys_id',       // ServiceNow internal ID
        ];

        foreach ($fields as $field) {
            if (!isset($response[$field])) {
                continue;
            }

            $value = $response[$field];

            // Skip null/empty but NOT "0" (which is a valid ID)
            if ($value === null || $value === '') {
                continue;
            }

            // Type validation: Skip arrays/objects (prevents "(string) array" = "Array" bug)
            if (is_array($value) || is_object($value)) {
                Log::warning('[WebhookOutputHandler] Non-scalar ticket ID skipped', [
                    'field' => $field,
                    'type' => gettype($value),
                ]);
                continue;
            }

            $stringValue = (string) $value;

            // Length check (VARCHAR(100) DB limit)
            if (strlen($stringValue) > 100) {
                Log::warning('[WebhookOutputHandler] Ticket ID truncated', [
                    'field' => $field,
                    'original_length' => strlen($stringValue),
                ]);
                $stringValue = substr($stringValue, 0, 100);
            }

            // Sanitize control characters (prevent display issues)
            $stringValue = preg_replace('/[\x00-\x1F\x7F]/u', '', $stringValue);
            $stringValue = trim($stringValue);

            if ($stringValue !== '') {
                return $stringValue;
            }
        }

        return null;
    }

    /**
     * Build transcript payload from call data.
     *
     * Extracts transcript from the associated call and formats it
     * for the webhook payload as an array of speaker turns.
     *
     * @param ServiceCase $case Service case with call relationship
     * @return array|null Transcript data or null if not available
     */
    private function buildTranscriptPayload(ServiceCase $case): ?array
    {
        // Load call relationship if needed
        if (!$case->relationLoaded('call')) {
            $case->load('call');
        }

        $call = $case->call;

        if (!$call || empty($call->transcript)) {
            Log::debug('[WebhookOutputHandler] No transcript available', [
                'case_id' => $case->id,
                'call_id' => $case->call_id,
            ]);
            return null;
        }

        // Parse transcript - can be JSON array or raw text
        $transcriptData = $call->transcript;

        // If it's a JSON string, decode it
        if (is_string($transcriptData)) {
            $decoded = json_decode($transcriptData, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $transcriptData = $decoded;
            } else {
                // Raw text transcript - wrap in simple structure
                return [
                    'format' => 'text',
                    'content' => $transcriptData,
                    'segment_count' => 1,
                ];
            }
        }

        // Structure for JSON array transcripts
        if (is_array($transcriptData)) {
            $segments = [];
            foreach ($transcriptData as $segment) {
                $segments[] = [
                    'role' => $segment['role'] ?? 'unknown',
                    'content' => $segment['content'] ?? $segment['words'] ?? '',
                    'timestamp' => $segment['timestamp'] ?? null,
                ];
            }

            Log::debug('[WebhookOutputHandler] Transcript included in payload', [
                'case_id' => $case->id,
                'segment_count' => count($segments),
            ]);

            return [
                'format' => 'segments',
                'segments' => $segments,
                'segment_count' => count($segments),
                'total_chars' => strlen(json_encode($segments)),
            ];
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

    /**
     * Enrich problem_since with absolute timestamp.
     *
     * Converts relative time expressions like "seit fünfzehn Minuten"
     * to include absolute timestamps: "seit fünfzehn Minuten (17:31 Uhr, Di. 23. Dez. 2025)"
     *
     * @param string|null $value The problem_since value
     * @param \Carbon\Carbon|null $referenceTime Reference time (case creation time)
     * @return string|null Enriched value or original if empty/already enriched
     */
    private function enrichProblemSince(?string $value, $referenceTime = null): ?string
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
        return $parser->format($value, $referenceTime ?? now());
    }

    /**
     * Mask sensitive parts of URL for logging.
     *
     * @param string $url Full URL
     * @return string Masked URL
     */
    private function maskUrl(string $url): string
    {
        $parsed = parse_url($url);
        $host = $parsed['host'] ?? 'unknown';
        $path = $parsed['path'] ?? '/';

        // Show host and truncated path
        return $host . (strlen($path) > 30 ? substr($path, 0, 30) . '...' : $path);
    }

    /**
     * Truncate string for logging purposes.
     *
     * @param string $content Content to truncate
     * @param int $maxLength Maximum length
     * @return string Truncated content
     */
    private function truncateForLog(string $content, int $maxLength = 500): string
    {
        if (strlen($content) <= $maxLength) {
            return $content;
        }

        return substr($content, 0, $maxLength) . '... [truncated ' . (strlen($content) - $maxLength) . ' chars]';
    }
}
