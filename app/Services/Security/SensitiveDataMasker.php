<?php

namespace App\Services\Security;

class SensitiveDataMasker
{
    /**
     * List of sensitive field names to mask
     */
    private const SENSITIVE_FIELDS = [
        'api_key',
        'apiKey',
        'api_token',
        'apiToken',
        'token',
        'secret',
        'password',
        'pass',
        'pwd',
        'credential',
        'credentials',
        'access_token',
        'refresh_token',
        'bearer_token',
        'auth_token',
        'private_key',
        'webhook_secret',
        'signing_secret',
        'client_secret',
        'app_secret',
        'calcom_api_key',
        'retell_api_key',
        'retell_token',
        'stripe_secret',
        'stripe_webhook_secret',
        'database_password',
        'mail_password',
        'smtp_password',
        'RETELL_TOKEN',
        'DEFAULT_CALCOM_API_KEY',
        'DEFAULT_RETELL_API_KEY',
        'CALCOM_WEBHOOK_SECRET',
        'RETELL_WEBHOOK_SECRET'
    ];

    /**
     * List of headers to mask
     */
    private const SENSITIVE_HEADERS = [
        'Authorization',
        'X-API-Key',
        'X-Auth-Token',
        'X-Access-Token',
        'api-key',
        'Cal-Api-Key'
    ];

    /**
     * Mask sensitive data in arrays recursively
     */
    public function mask($data): mixed
    {
        if (is_array($data)) {
            return $this->maskArray($data);
        }
        
        if (is_object($data)) {
            return $this->maskObject($data);
        }
        
        if (is_string($data)) {
            return $this->maskString($data);
        }
        
        return $data;
    }

    /**
     * Mask sensitive data in arrays
     */
    private function maskArray(array $data): array
    {
        $masked = [];
        
        foreach ($data as $key => $value) {
            if ($this->isSensitiveField($key)) {
                $masked[$key] = $this->maskValue($value);
            } elseif (is_array($value)) {
                $masked[$key] = $this->maskArray($value);
            } elseif (is_object($value)) {
                $masked[$key] = $this->maskObject($value);
            } else {
                $masked[$key] = $value;
            }
        }
        
        return $masked;
    }

    /**
     * Mask sensitive data in objects
     */
    private function maskObject($object): mixed
    {
        // If it's a simple object, convert to array and mask
        if (is_object($object) && !($object instanceof \stdClass)) {
            return get_class($object) . ' (masked)';
        }
        
        $array = (array) $object;
        $masked = $this->maskArray($array);
        
        return (object) $masked;
    }

    /**
     * Mask sensitive strings (e.g., in URLs or connection strings)
     */
    private function maskString(string $string): string
    {
        // Mask API keys in URLs
        $string = preg_replace(
            '/(\?|&)(api_?key|token|secret)=([^&\s]+)/i',
            '$1$2=***MASKED***',
            $string
        );
        
        // Mask bearer tokens
        $string = preg_replace(
            '/Bearer\s+[\w\-\.]+/i',
            'Bearer ***MASKED***',
            $string
        );
        
        // Mask key patterns (including sk_test_ patterns)
        $string = preg_replace(
            '/\b(key_|sk_test_|sk_live_|sk_|pk_|tok_|secret_)[\w\-]+\b/i',
            '$1***MASKED***',
            $string
        );
        
        return $string;
    }

    /**
     * Check if a field name is sensitive
     */
    private function isSensitiveField(string $fieldName): bool
    {
        $normalized = strtolower($fieldName);
        
        foreach (self::SENSITIVE_FIELDS as $sensitive) {
            if ($normalized === strtolower($sensitive)) {
                return true;
            }
            
            // Also check for partial matches (e.g., 'stripe_api_key')
            if (str_contains($normalized, strtolower($sensitive))) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Mask a sensitive value
     */
    private function maskValue($value): string
    {
        if (is_null($value) || $value === '') {
            return '(empty)';
        }
        
        if (!is_string($value)) {
            return '***MASKED***';
        }
        
        $length = strlen($value);
        
        if ($length <= 4) {
            return '***MASKED***';
        }
        
        if ($length <= 8) {
            return substr($value, 0, 2) . str_repeat('*', $length - 2);
        }
        
        // For longer values, show first 3 and last 3 characters
        $starCount = max($length - 6, 10); // At least 10 stars
        return substr($value, 0, 3) . str_repeat('*', $starCount) . substr($value, -3);
    }

    /**
     * Mask HTTP headers
     */
    public function maskHeaders(array $headers): array
    {
        $masked = [];
        
        foreach ($headers as $key => $values) {
            if ($this->isSensitiveHeader($key)) {
                $masked[$key] = ['***MASKED***'];
            } else {
                $masked[$key] = $values;
            }
        }
        
        return $masked;
    }

    /**
     * Check if a header is sensitive
     */
    private function isSensitiveHeader(string $header): bool
    {
        $normalized = strtolower($header);
        
        foreach (self::SENSITIVE_HEADERS as $sensitive) {
            if ($normalized === strtolower($sensitive)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Create safe log context by masking sensitive data
     */
    public function createSafeContext(array $context): array
    {
        return $this->maskArray($context);
    }

    /**
     * Mask exception for logging
     */
    public function maskException(\Throwable $exception): array
    {
        return [
            'message' => $this->maskString($exception->getMessage()),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $this->maskStackTrace($exception->getTrace())
        ];
    }

    /**
     * Mask stack trace
     */
    public function maskStackTrace(array $trace): array
    {
        $masked = [];
        
        foreach ($trace as $frame) {
            $maskedFrame = $frame;
            
            if (isset($maskedFrame['args'])) {
                $maskedFrame['args'] = $this->maskArray($maskedFrame['args']);
            }
            
            $masked[] = $maskedFrame;
        }
        
        return $masked;
    }

    /**
     * Quick static method for masking data
     */
    public static function quickMask($data): mixed
    {
        return (new self())->mask($data);
    }
}