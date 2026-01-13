<?php

namespace App\Services\ServiceGateway;

use App\Models\ServiceGatewayExchangeLog;
use App\Services\ServiceGateway\ResponseBodyAnalyzer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * ExchangeLogService
 *
 * Audit logging service for Service Gateway external communications.
 * Implements No-Leak Guarantee through comprehensive data redaction.
 *
 * NO-LEAK GUARANTEE:
 * This service ensures that NO sensitive information is ever stored:
 * - API keys, secrets, tokens
 * - Internal IDs (Retell agent_id, Twilio SID, etc.)
 * - Cost/margin/profit data
 * - Prompts and system prompts
 * - Internal URLs
 *
 * ALLOWED DATA:
 * - Ticket IDs (formatted: TKT-2025-XXXXX)
 * - Subject, description (customer-facing)
 * - Priority, urgency, impact
 * - Customer name/phone (with consent)
 * - Correlation IDs, timestamps
 *
 * @package App\Services\ServiceGateway
 */
class ExchangeLogService
{
    /**
     * Fields that MUST be redacted from payloads.
     * These are sensitive and must NEVER be logged.
     *
     * @var array<string>
     */
    private const REDACTED_FIELDS = [
        // Authentication & Secrets
        'api_key',
        'apikey',
        'api-key',
        'api_secret',
        'secret',
        'password',
        'token',
        'access_token',
        'refresh_token',
        'bearer',
        'authorization',
        'authorization_bearer',
        'auth',
        'credentials',
        'private_key',
        'client_secret',
        'webhook_secret',
        'signing_secret',
        'jwt_secret',
        'hmac_secret',

        // Retell-specific
        'retell_api_key',
        'agent_id',
        'llm_id',
        'prompt',
        'system_prompt',
        'begin_message',
        'general_prompt',
        'voice_id',

        // Cost & Financial
        'cost',
        'price',
        'margin',
        'profit',
        'revenue',
        'amount',
        'total_cost',
        'call_cost',
        'llm_cost',
        'tts_cost',
        'stt_cost',

        // Third-party credentials
        'twilio_sid',
        'twilio_auth_token',
        'twilio_account_sid',
        'calcom_api_key',
        'stripe_secret',
        'stripe_key',
        'stripe_webhook_secret',
        'openai_key',
        'openai_api_key',
        'anthropic_key',
        'anthropic_api_key',
        'azure_key',
        'aws_secret_key',
        'aws_access_key',

        // Internal identifiers (should not leak)
        'internal_id',
        'db_id',
        'user_id', // Internal user IDs
        'admin_id',
    ];

    /**
     * URL patterns that indicate internal URLs (must be redacted).
     *
     * @var array<string>
     */
    private const INTERNAL_URL_PATTERNS = [
        'api-gateway',
        'localhost',
        '127.0.0.1',
        '/admin/',
        '/internal/',
        '/debug/',
        '/private/',
        '.local',
        ':8000',
        ':3000',
    ];

    /**
     * Headers that must be redacted.
     *
     * @var array<string>
     */
    private const REDACTED_HEADERS = [
        'authorization',
        'x-api-key',
        'x-auth-token',
        'x-signature',
        'x-webhook-secret',
        'x-hub-signature',
        'x-hub-signature-256',
        'cookie',
        'set-cookie',
        'x-csrf-token',
        'x-xsrf-token',
        'x-askpro-signature', // Our own signature - don't leak
        'x-retell-signature',
        'x-stripe-signature',
    ];

    /**
     * Log an outbound exchange (we send to external system).
     *
     * @param bool $isTest Whether this is a test webhook (not a real delivery)
     * @param int|null $outputConfigurationId The ServiceOutputConfiguration ID for filtering
     */
    public function logOutbound(
        string $endpoint,
        string $method,
        array $requestBody,
        ?array $responseBody = null,
        ?int $statusCode = null,
        ?int $durationMs = null,
        ?int $callId = null,
        ?int $serviceCaseId = null,
        ?int $companyId = null,
        ?string $correlationId = null,
        int $attemptNo = 1,
        int $maxAttempts = 3,
        ?string $errorClass = null,
        ?string $errorMessage = null,
        ?string $parentEventId = null,
        ?array $headers = null,
        bool $isTest = false,
        ?int $outputConfigurationId = null
    ): ServiceGatewayExchangeLog {
        return $this->log(
            direction: 'outbound',
            endpoint: $this->sanitizeEndpoint($endpoint),
            method: $method,
            requestBody: $requestBody,
            responseBody: $responseBody,
            statusCode: $statusCode,
            durationMs: $durationMs,
            callId: $callId,
            serviceCaseId: $serviceCaseId,
            companyId: $companyId,
            correlationId: $correlationId,
            attemptNo: $attemptNo,
            maxAttempts: $maxAttempts,
            errorClass: $errorClass,
            errorMessage: $errorMessage,
            parentEventId: $parentEventId,
            headers: $headers,
            isTest: $isTest,
            outputConfigurationId: $outputConfigurationId
        );
    }

    /**
     * Log an inbound exchange (external system sends to us).
     */
    public function logInbound(
        string $endpoint,
        string $method,
        array $requestBody,
        ?array $responseBody = null,
        ?int $statusCode = null,
        ?int $durationMs = null,
        ?int $callId = null,
        ?int $serviceCaseId = null,
        ?int $companyId = null,
        ?string $correlationId = null,
        ?array $headers = null
    ): ServiceGatewayExchangeLog {
        return $this->log(
            direction: 'inbound',
            endpoint: $endpoint,
            method: $method,
            requestBody: $requestBody,
            responseBody: $responseBody,
            statusCode: $statusCode,
            durationMs: $durationMs,
            callId: $callId,
            serviceCaseId: $serviceCaseId,
            companyId: $companyId,
            correlationId: $correlationId,
            attemptNo: 1,
            maxAttempts: 1,
            errorClass: null,
            errorMessage: null,
            parentEventId: null,
            headers: $headers
        );
    }

    /**
     * Log an internal exchange (job processing, async operations).
     *
     * DRY-001: Centralized method for job exchange logging.
     * Use this instead of direct ServiceGatewayExchangeLog::create() in jobs.
     *
     * @param string $operation Operation name (e.g., 'audio-storage', 'enrichment', 'delivery')
     * @param string $status Status code: 'success', 'failed', 'skipped', 'timeout'
     * @param ServiceCase|null $case The service case (if available)
     * @param array $context Additional context data to log
     * @param string|null $error Error message if failed
     * @param int $attemptNo Current attempt number
     * @param int $maxAttempts Maximum attempts
     */
    public function logInternal(
        string $operation,
        string $status,
        ?\App\Models\ServiceCase $case = null,
        array $context = [],
        ?string $error = null,
        int $attemptNo = 1,
        int $maxAttempts = 1
    ): ?ServiceGatewayExchangeLog {
        // Map status to HTTP status code for consistency
        $statusCode = match ($status) {
            'success' => 200,
            'skipped' => 204,
            'timeout' => 408,
            'pending' => 202,
            default => 500,
        };

        return $this->log(
            direction: 'internal',
            endpoint: $operation,
            method: 'PROCESS',
            requestBody: array_merge(
                $case ? ['case_id' => $case->id, 'call_id' => $case->call_id] : [],
                $context
            ),
            responseBody: array_merge(
                ['status' => $status],
                $error ? ['error' => $error] : []
            ),
            statusCode: $statusCode,
            durationMs: 0,
            callId: $case?->call_id,
            serviceCaseId: $case?->id,
            companyId: $case?->company_id,
            correlationId: $case ? $case->id . '-' . $operation . '-' . now()->format('His') : null,
            attemptNo: $attemptNo,
            maxAttempts: $maxAttempts,
            errorClass: $error ? 'JobError' : null,
            errorMessage: $error,
            parentEventId: null,
            headers: null
        );
    }

    /**
     * Create exchange log with full redaction.
     */
    private function log(
        string $direction,
        string $endpoint,
        string $method,
        array $requestBody,
        ?array $responseBody,
        ?int $statusCode,
        ?int $durationMs,
        ?int $callId,
        ?int $serviceCaseId,
        ?int $companyId,
        ?string $correlationId,
        int $attemptNo,
        int $maxAttempts,
        ?string $errorClass,
        ?string $errorMessage,
        ?string $parentEventId,
        ?array $headers,
        bool $isTest = false,
        ?int $outputConfigurationId = null
    ): ServiceGatewayExchangeLog {
        try {
            // Detect semantic errors in response body
            // This catches cases where HTTP 200 is returned but body contains error information
            $finalErrorClass = $errorClass;
            $finalErrorMessage = $errorMessage;

            if (is_null($finalErrorClass) && $responseBody && ($statusCode === null || $statusCode < 400)) {
                $analyzer = app(ResponseBodyAnalyzer::class);
                [$bodyErrorClass, $bodyErrorMessage] = $analyzer->analyze($responseBody, $statusCode ?? 200);

                if ($bodyErrorClass !== null) {
                    $finalErrorClass = $bodyErrorClass;
                    $finalErrorMessage = $bodyErrorMessage;

                    Log::warning('[ExchangeLogService] Semantic error detected in response body', [
                        'error_class' => $bodyErrorClass,
                        'error_message' => $bodyErrorMessage,
                        'http_status' => $statusCode,
                        'endpoint' => $endpoint,
                    ]);
                }
            }

            $log = ServiceGatewayExchangeLog::create([
                'event_id' => (string) Str::uuid(),
                'direction' => $direction,
                'endpoint' => $endpoint,
                'http_method' => strtoupper($method),
                'request_body_redacted' => $this->redactPayload($requestBody),
                'response_body_redacted' => $responseBody ? $this->redactPayload($responseBody) : null,
                'headers_redacted' => $headers ? $this->redactHeaders($headers) : null,
                'status_code' => $statusCode,
                'duration_ms' => $durationMs,
                'call_id' => $callId,
                'service_case_id' => $serviceCaseId,
                'company_id' => $companyId,
                'output_configuration_id' => $outputConfigurationId,
                'correlation_id' => $correlationId,
                'attempt_no' => $attemptNo,
                'max_attempts' => $maxAttempts,
                'error_class' => $finalErrorClass,
                'error_message' => $this->sanitizeErrorMessage($finalErrorMessage),
                'parent_event_id' => $parentEventId,
                'is_test' => $isTest,
                'completed_at' => ($statusCode !== null || $finalErrorClass !== null) ? now() : null,
            ]);

            Log::debug('[ExchangeLogService] Exchange logged', [
                'event_id' => $log->event_id,
                'direction' => $direction,
                'endpoint' => $endpoint,
                'status' => $statusCode,
            ]);

            return $log;

        } catch (\Exception $e) {
            // Logging should never break the main flow
            Log::error('[ExchangeLogService] Failed to log exchange', [
                'error' => $e->getMessage(),
                'direction' => $direction,
                'endpoint' => $endpoint,
            ]);

            // Return a non-persisted log object so callers can continue
            return new ServiceGatewayExchangeLog([
                'event_id' => (string) Str::uuid(),
                'direction' => $direction,
                'endpoint' => $endpoint,
            ]);
        }
    }

    /**
     * Update log with completion data (for async operations).
     */
    public function complete(
        ServiceGatewayExchangeLog $log,
        ?array $responseBody = null,
        ?int $statusCode = null,
        ?int $durationMs = null,
        ?string $errorClass = null,
        ?string $errorMessage = null
    ): ServiceGatewayExchangeLog {
        // Detect semantic errors in response body (if no error already set)
        $finalErrorClass = $errorClass ?? $log->error_class;
        $finalErrorMessage = $errorMessage ?? $log->error_message;
        $effectiveStatus = $statusCode ?? $log->status_code;

        if (is_null($finalErrorClass) && $responseBody && ($effectiveStatus === null || $effectiveStatus < 400)) {
            $analyzer = app(ResponseBodyAnalyzer::class);
            [$bodyErrorClass, $bodyErrorMessage] = $analyzer->analyze($responseBody, $effectiveStatus ?? 200);

            if ($bodyErrorClass !== null) {
                $finalErrorClass = $bodyErrorClass;
                $finalErrorMessage = $bodyErrorMessage;

                Log::warning('[ExchangeLogService] Semantic error detected on completion', [
                    'event_id' => $log->event_id,
                    'error_class' => $bodyErrorClass,
                    'error_message' => $bodyErrorMessage,
                    'http_status' => $effectiveStatus,
                ]);
            }
        }

        $log->update([
            'response_body_redacted' => $responseBody ? $this->redactPayload($responseBody) : $log->response_body_redacted,
            'status_code' => $effectiveStatus,
            'duration_ms' => $durationMs ?? $log->duration_ms,
            'error_class' => $finalErrorClass,
            'error_message' => $finalErrorMessage ? $this->sanitizeErrorMessage($finalErrorMessage) : $log->error_message,
            'completed_at' => now(),
        ]);

        return $log;
    }

    /**
     * Redact sensitive fields from payload.
     * Implements No-Leak Guarantee.
     */
    public function redactPayload(array $payload): array
    {
        return $this->recursiveRedact($payload);
    }

    /**
     * Recursively redact sensitive data from nested arrays.
     */
    private function recursiveRedact(array $data): array
    {
        $redacted = [];

        foreach ($data as $key => $value) {
            $lowerKey = strtolower((string) $key);

            // Check if this key should be redacted
            if ($this->shouldRedactKey($lowerKey)) {
                $redacted[$key] = '[REDACTED]';
                continue;
            }

            // Recursively process nested arrays
            if (is_array($value)) {
                $redacted[$key] = $this->recursiveRedact($value);
                continue;
            }

            // Check if value looks like a secret (even if key is innocent)
            if (is_string($value) && $this->looksLikeSecret($value)) {
                $redacted[$key] = '[REDACTED:secret_pattern]';
                continue;
            }

            // Check for internal URLs
            if (is_string($value) && $this->isInternalUrl($value)) {
                $redacted[$key] = '[REDACTED:internal_url]';
                continue;
            }

            $redacted[$key] = $value;
        }

        return $redacted;
    }

    /**
     * Check if a key should be redacted.
     */
    private function shouldRedactKey(string $key): bool
    {
        foreach (self::REDACTED_FIELDS as $redactedField) {
            // Exact match
            if ($key === $redactedField) {
                return true;
            }

            // Contains match (e.g., "api_key_id" should match "api_key")
            if (str_contains($key, $redactedField)) {
                return true;
            }

            // Variations with underscores/dashes
            $variations = [
                str_replace('_', '-', $redactedField),
                str_replace('-', '_', $redactedField),
                str_replace('_', '', $redactedField),
            ];

            foreach ($variations as $variation) {
                if (str_contains($key, $variation)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if a value looks like a secret (token, API key, etc.).
     */
    private function looksLikeSecret(string $value): bool
    {
        // Too short to be a secret
        if (strlen($value) < 16) {
            return false;
        }

        // Bearer tokens
        if (str_starts_with($value, 'Bearer ')) {
            return true;
        }

        // Base64-encoded tokens (typically 40+ chars)
        if (strlen($value) > 40 && preg_match('/^[A-Za-z0-9+\/=]+$/', $value)) {
            return true;
        }

        // API key patterns (sk-, pk-, rk-, etc.)
        if (preg_match('/^(sk|pk|rk|ak|key)[-_][A-Za-z0-9]{16,}$/', $value)) {
            return true;
        }

        // UUIDs in certain positions might be secrets
        // But we allow formatted ticket IDs like TKT-2025-XXXXX
        if (preg_match('/^[a-f0-9]{32,}$/i', $value)) {
            return true;
        }

        return false;
    }

    /**
     * Check if URL is internal (should not be logged).
     */
    private function isInternalUrl(string $value): bool
    {
        // Quick check if it looks like a URL
        if (!str_contains($value, '://') && !str_starts_with($value, '/')) {
            return false;
        }

        foreach (self::INTERNAL_URL_PATTERNS as $pattern) {
            if (str_contains(strtolower($value), $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Redact HTTP headers.
     */
    public function redactHeaders(array $headers): array
    {
        $redacted = [];

        foreach ($headers as $key => $value) {
            $lowerKey = strtolower($key);

            if (in_array($lowerKey, self::REDACTED_HEADERS)) {
                $redacted[$key] = '[REDACTED]';
                continue;
            }

            // Check for auth-like headers
            if (str_contains($lowerKey, 'auth') ||
                str_contains($lowerKey, 'token') ||
                str_contains($lowerKey, 'key') ||
                str_contains($lowerKey, 'secret')) {
                $redacted[$key] = '[REDACTED]';
                continue;
            }

            $redacted[$key] = $value;
        }

        return $redacted;
    }

    /**
     * Sanitize endpoint URL for storage.
     * Removes query parameters that might contain sensitive data.
     */
    private function sanitizeEndpoint(string $endpoint): string
    {
        $parsed = parse_url($endpoint);

        if (!$parsed) {
            return '[INVALID_URL]';
        }

        // Rebuild URL without query string
        $sanitized = '';

        if (isset($parsed['scheme'])) {
            $sanitized .= $parsed['scheme'] . '://';
        }

        if (isset($parsed['host'])) {
            $sanitized .= $parsed['host'];
        }

        if (isset($parsed['port'])) {
            $sanitized .= ':' . $parsed['port'];
        }

        if (isset($parsed['path'])) {
            $sanitized .= $parsed['path'];
        }

        // Query parameters are stripped for security
        // We note if they existed
        if (isset($parsed['query'])) {
            $sanitized .= '?[query_redacted]';
        }

        return $sanitized;
    }

    /**
     * Sanitize error messages to remove potential sensitive data.
     */
    private function sanitizeErrorMessage(?string $message): ?string
    {
        if ($message === null) {
            return null;
        }

        // Remove potential file paths
        $message = preg_replace('/\/[^\s]+\.php/', '[file]', $message);

        // Remove potential IP addresses
        $message = preg_replace('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', '[ip]', $message);

        // Remove potential credentials in URLs
        $message = preg_replace('/\/\/[^:]+:[^@]+@/', '//[credentials]@', $message);

        // Limit length
        if (strlen($message) > 1000) {
            $message = substr($message, 0, 1000) . '... [truncated]';
        }

        return $message;
    }

    /**
     * Get statistics for a company's exchanges.
     */
    public function getStats(int $companyId, ?int $days = 7): array
    {
        $since = now()->subDays($days);

        $query = ServiceGatewayExchangeLog::forCompany($companyId)
            ->where('created_at', '>=', $since);

        $total = (clone $query)->count();
        $successful = (clone $query)->successful()->count();
        $failed = (clone $query)->failed()->count();

        $avgDuration = (clone $query)
            ->whereNotNull('duration_ms')
            ->avg('duration_ms');

        return [
            'period_days' => $days,
            'total' => $total,
            'successful' => $successful,
            'failed' => $failed,
            'success_rate' => $total > 0 ? round(($successful / $total) * 100, 1) : 0,
            'avg_duration_ms' => $avgDuration ? round($avgDuration) : null,
        ];
    }
}
