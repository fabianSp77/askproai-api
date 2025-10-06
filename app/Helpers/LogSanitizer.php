<?php

namespace App\Helpers;

use Illuminate\Support\Facades\App;

/**
 * Log Sanitizer - GDPR-compliant logging helper
 *
 * Redacts PII (emails, phones, names) and secrets (tokens, API keys) from logs
 * to prevent GDPR violations and security token leakage.
 *
 * Usage:
 *   Log::info('Request received', LogSanitizer::sanitize($request->all()));
 *   Log::info('Headers', LogSanitizer::sanitizeHeaders($request->headers->all()));
 */
class LogSanitizer
{
    /**
     * Sensitive keys that should always be redacted
     */
    private const SENSITIVE_KEYS = [
        'password',
        'token',
        'api_key',
        'api-key',
        'apikey',
        'secret',
        'authorization',
        'auth',
        'bearer',
        'x-api-key',
        'access_token',
        'refresh_token',
        'private_key',
        'credit_card',
        'card_number',
        'cvv',
        'ssn',
    ];

    /**
     * PII keys that should be redacted in production
     */
    private const PII_KEYS = [
        'email',
        'phone',
        'phone_number',
        'mobile',
        'name',
        'customer_name',
        'first_name',
        'last_name',
        'address',
        'street',
        'city',
        'postal_code',
        'zip',
        'date_of_birth',
        'dob',
        'customer_email',
    ];

    /**
     * Sanitize data array for logging
     *
     * @param mixed $data Data to sanitize
     * @param bool $redactPII Whether to redact PII (default: production only)
     * @return mixed Sanitized data
     */
    public static function sanitize($data, bool $redactPII = null): mixed
    {
        $redactPII = $redactPII ?? App::environment('production');

        if (is_array($data)) {
            return self::sanitizeArray($data, $redactPII);
        }

        if (is_object($data)) {
            return self::sanitizeArray((array) $data, $redactPII);
        }

        if (is_string($data)) {
            return self::sanitizeString($data, $redactPII);
        }

        return $data;
    }

    /**
     * Sanitize HTTP headers for logging
     *
     * @param array $headers Headers array
     * @return array Sanitized headers
     */
    public static function sanitizeHeaders(array $headers): array
    {
        $sanitized = [];

        foreach ($headers as $key => $value) {
            $lowerKey = strtolower($key);

            // Always redact authorization headers
            if (str_contains($lowerKey, 'authorization') ||
                str_contains($lowerKey, 'token') ||
                str_contains($lowerKey, 'api-key') ||
                str_contains($lowerKey, 'bearer')) {
                $sanitized[$key] = '[REDACTED]';
                continue;
            }

            // Sanitize header values that might contain sensitive data
            if (is_array($value)) {
                $sanitized[$key] = array_map(fn($v) => self::sanitizeString($v, true), $value);
            } else {
                $sanitized[$key] = self::sanitizeString($value, true);
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize request body for logging
     *
     * @param array $body Request body
     * @param bool $redactPII Whether to redact PII
     * @return array Sanitized body
     */
    public static function sanitizeRequestBody(array $body, bool $redactPII = null): array
    {
        $redactPII = $redactPII ?? App::environment('production');
        return self::sanitizeArray($body, $redactPII);
    }

    /**
     * Sanitize array recursively
     *
     * @param array $array Array to sanitize
     * @param bool $redactPII Whether to redact PII
     * @return array Sanitized array
     */
    private static function sanitizeArray(array $array, bool $redactPII): array
    {
        $sanitized = [];

        foreach ($array as $key => $value) {
            $lowerKey = strtolower((string) $key);

            // Always redact sensitive keys (secrets, tokens)
            if (self::isSensitiveKey($lowerKey)) {
                $sanitized[$key] = '[REDACTED]';
                continue;
            }

            // Redact PII keys if enabled
            if ($redactPII && self::isPIIKey($lowerKey)) {
                $sanitized[$key] = '[PII_REDACTED]';
                continue;
            }

            // Recursively sanitize nested arrays/objects
            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeArray($value, $redactPII);
            } elseif (is_object($value)) {
                $sanitized[$key] = self::sanitizeArray((array) $value, $redactPII);
            } elseif (is_string($value)) {
                $sanitized[$key] = self::sanitizeString($value, $redactPII);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize string content
     *
     * @param string $string String to sanitize
     * @param bool $redactPII Whether to redact PII patterns
     * @return string Sanitized string
     */
    private static function sanitizeString(string $string, bool $redactPII): string
    {
        // Always redact bearer tokens
        $string = preg_replace('/Bearer\s+[A-Za-z0-9\-._~+\/]+=*/i', 'Bearer [REDACTED]', $string);

        // Always redact API keys (32+ hex/alphanumeric strings)
        $string = preg_replace('/\b[a-f0-9]{32,}\b/i', '[API_KEY_REDACTED]', $string);

        if ($redactPII) {
            // Redact email addresses
            $string = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', '[EMAIL_REDACTED]', $string);

            // Redact phone numbers (various formats)
            $string = preg_replace('/(\+?\d{1,3}[\s-]?)?\(?\d{2,4}\)?[\s-]?\d{3,4}[\s-]?\d{3,4}/', '[PHONE_REDACTED]', $string);
        }

        return $string;
    }

    /**
     * Check if key is sensitive (always redact)
     *
     * @param string $key Lowercase key name
     * @return bool
     */
    private static function isSensitiveKey(string $key): bool
    {
        foreach (self::SENSITIVE_KEYS as $sensitive) {
            if (str_contains($key, $sensitive)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if key contains PII
     *
     * @param string $key Lowercase key name
     * @return bool
     */
    private static function isPIIKey(string $key): bool
    {
        foreach (self::PII_KEYS as $pii) {
            if (str_contains($key, $pii)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create safe log context for production
     *
     * @param array $context Log context array
     * @return array Sanitized context
     */
    public static function safeContext(array $context): array
    {
        return self::sanitize($context, App::environment('production'));
    }
}
