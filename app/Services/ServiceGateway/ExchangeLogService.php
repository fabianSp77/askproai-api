<?php

namespace App\Services\ServiceGateway;

use App\Models\ServiceGatewayExchangeLog;
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
        'secret',
        'password',
        'token',
        'access_token',
        'refresh_token',
        'bearer',
        'authorization',
        'auth',
        'credentials',

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
        'openai_key',
        'anthropic_key',

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
        'cookie',
        'set-cookie',
        'x-csrf-token',
        'x-xsrf-token',
        'x-askpro-signature', // Our own signature - don't leak
    ];

    /**
     * Log an outbound exchange (we send to external system).
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
        ?array $headers = null
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
            headers: $headers
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
        ?array $headers
    ): ServiceGatewayExchangeLog {
        try {
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
                'correlation_id' => $correlationId,
                'attempt_no' => $attemptNo,
                'max_attempts' => $maxAttempts,
                'error_class' => $errorClass,
                'error_message' => $this->sanitizeErrorMessage($errorMessage),
                'parent_event_id' => $parentEventId,
                'completed_at' => ($statusCode !== null || $errorClass !== null) ? now() : null,
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
        $log->update([
            'response_body_redacted' => $responseBody ? $this->redactPayload($responseBody) : $log->response_body_redacted,
            'status_code' => $statusCode ?? $log->status_code,
            'duration_ms' => $durationMs ?? $log->duration_ms,
            'error_class' => $errorClass ?? $log->error_class,
            'error_message' => $errorMessage ? $this->sanitizeErrorMessage($errorMessage) : $log->error_message,
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
