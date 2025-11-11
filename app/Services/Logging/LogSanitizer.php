<?php

namespace App\Services\Logging;

/**
 * 🔒 GDPR Log Sanitization Service
 *
 * Removes or redacts Personally Identifiable Information (PII) from log messages
 * to comply with GDPR Article 32 (Security of Processing).
 *
 * USAGE:
 * use App\Services\Logging\LogSanitizer;
 *
 * // Sanitize array data before logging
 * Log::info('User data', LogSanitizer::sanitize($userData));
 *
 * // Sanitize string message
 * Log::info(LogSanitizer::sanitizeString("Email: user@example.com"));
 */
class LogSanitizer
{
    /**
     * PII field keys that should be redacted
     *
     * @var array<string>
     */
    protected static array $piiFields = [
        // Contact Information
        'email',
        'phone',
        'phone_number',
        'mobile',
        'telephone',
        'customer_phone',
        'customer_email',

        // Personal Details
        'name',
        'first_name',
        'last_name',
        'full_name',
        'customer_name',

        // Address Information
        'address',
        'street',
        'city',
        'zip',
        'postal_code',
        'postcode',

        // Identification
        'ip',
        'ip_address',
        'user_agent',
        'device_id',
        'session_id',

        // Sensitive Data
        'password',
        'token',
        'api_key',
        'secret',
        'credit_card',
        'card_number',
        'ssn',
        'tax_id',
    ];

    /**
     * Regex patterns for detecting PII in strings
     *
     * @var array<string, string>
     */
    protected static array $piiPatterns = [
        // Email addresses
        'email' => '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/',

        // Phone numbers (various formats)
        'phone' => '/\b(?:\+?\d{1,3}[-.\s]?)?\(?\d{2,4}\)?[-.\s]?\d{2,4}[-.\s]?\d{2,4}\b/',

        // IP addresses (IPv4)
        'ip' => '/\b(?:\d{1,3}\.){3}\d{1,3}\b/',

        // Credit card numbers (basic pattern)
        'credit_card' => '/\b\d{4}[-\s]?\d{4}[-\s]?\d{4}[-\s]?\d{4}\b/',
    ];

    /**
     * Sanitize an array or object by redacting PII fields
     *
     * @param mixed $data Data to sanitize (array, object, or scalar)
     * @param bool $deep Whether to recursively sanitize nested structures
     * @return mixed Sanitized data
     */
    public static function sanitize(mixed $data, bool $deep = true): mixed
    {
        // Handle null
        if ($data === null) {
            return null;
        }

        // Handle arrays
        if (is_array($data)) {
            return static::sanitizeArray($data, $deep);
        }

        // Handle objects
        if (is_object($data)) {
            return static::sanitizeObject($data, $deep);
        }

        // Handle strings
        if (is_string($data)) {
            return static::sanitizeString($data);
        }

        // Pass through scalars (int, float, bool)
        return $data;
    }

    /**
     * Sanitize an array by redacting PII fields
     *
     * @param array $data
     * @param bool $deep
     * @return array
     */
    protected static function sanitizeArray(array $data, bool $deep): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            // Check if this key is a PII field
            if (static::isPiiField($key)) {
                $sanitized[$key] = static::redact($value);
            } elseif ($deep && (is_array($value) || is_object($value))) {
                $sanitized[$key] = static::sanitize($value, true);
            } elseif (is_string($value)) {
                $sanitized[$key] = static::sanitizeString($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize an object by redacting PII properties
     *
     * @param object $data
     * @param bool $deep
     * @return object
     */
    protected static function sanitizeObject(object $data, bool $deep): object
    {
        // Convert to array, sanitize, convert back
        $array = json_decode(json_encode($data), true);
        $sanitized = static::sanitizeArray($array, $deep);
        return (object) $sanitized;
    }

    /**
     * Sanitize a string by removing PII patterns
     *
     * @param string $text
     * @return string
     */
    public static function sanitizeString(string $text): string
    {
        foreach (static::$piiPatterns as $type => $pattern) {
            $text = preg_replace($pattern, static::getRedactionMask($type), $text);
        }

        return $text;
    }

    /**
     * Check if a field key is a PII field
     *
     * @param string $key
     * @return bool
     */
    protected static function isPiiField(string $key): bool
    {
        // Normalize key (lowercase, remove underscores/dashes)
        $normalizedKey = strtolower(str_replace(['_', '-'], '', $key));

        foreach (static::$piiFields as $piiField) {
            $normalizedPii = str_replace(['_', '-'], '', $piiField);
            if (str_contains($normalizedKey, $normalizedPii)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Redact a value based on its type
     *
     * @param mixed $value
     * @return string
     */
    protected static function redact(mixed $value): string
    {
        if (is_string($value)) {
            // Keep first 2 characters for debugging context
            $length = strlen($value);
            if ($length <= 2) {
                return '***';
            } elseif ($length <= 5) {
                return substr($value, 0, 1) . '***';
            } else {
                return substr($value, 0, 2) . '***' . substr($value, -1);
            }
        }

        return '[REDACTED]';
    }

    /**
     * Get redaction mask for a specific PII type
     *
     * @param string $type
     * @return string
     */
    protected static function getRedactionMask(string $type): string
    {
        return match ($type) {
            'email' => '[EMAIL_REDACTED]',
            'phone' => '[PHONE_REDACTED]',
            'ip' => '[IP_REDACTED]',
            'credit_card' => '[CARD_REDACTED]',
            default => '[PII_REDACTED]',
        };
    }

    /**
     * Add custom PII field to redaction list
     *
     * @param string $fieldName
     * @return void
     */
    public static function addPiiField(string $fieldName): void
    {
        if (!in_array($fieldName, static::$piiFields)) {
            static::$piiFields[] = $fieldName;
        }
    }

    /**
     * Add custom PII pattern to redaction list
     *
     * @param string $name Pattern name
     * @param string $pattern Regex pattern
     * @return void
     */
    public static function addPiiPattern(string $name, string $pattern): void
    {
        static::$piiPatterns[$name] = $pattern;
    }

    /**
     * Check if production environment (where strict GDPR compliance required)
     *
     * @return bool
     */
    public static function shouldSanitize(): bool
    {
        return config('app.env') === 'production' || config('app.gdpr_log_sanitization', true);
    }

    /**
     * Conditionally sanitize based on environment
     *
     * @param mixed $data
     * @return mixed
     */
    public static function conditionalSanitize(mixed $data): mixed
    {
        return static::shouldSanitize() ? static::sanitize($data) : $data;
    }
}
