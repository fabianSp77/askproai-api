<?php

declare(strict_types=1);

namespace App\Services\ServiceGateway;

use Illuminate\Support\Facades\Log;

/**
 * ResponseBodyAnalyzer
 *
 * Analyzes HTTP response bodies for semantic errors that indicate failure
 * even when HTTP status code is 200 OK.
 *
 * Common patterns detected:
 * - {"error": "..."} - Explicit error field
 * - {"errors": [...]} - Error array
 * - {"success": false} - Success flag
 * - {"status": 401} - Status code in body (VisionaryData pattern)
 * - {"status": "failed"} - Status string
 *
 * @package App\Services\ServiceGateway
 */
class ResponseBodyAnalyzer
{
    /**
     * Known error field patterns in various API responses.
     */
    private const ERROR_FIELDS = [
        'error',           // {"error": "..."}
        'errors',          // {"errors": [...]}
        'error_message',   // {"error_message": "..."}
        'error_code',      // {"error_code": 401}
        'exception',       // {"exception": "..."}
        'fault',           // SOAP: {"fault": {...}}
    ];

    /**
     * Status strings that indicate failure.
     */
    private const FAILURE_STATUSES = [
        'failed',
        'failure',
        'error',
        'rejected',
        'denied',
        'invalid',
    ];

    /**
     * Analyzes response body for semantic errors.
     *
     * Returns an array with [errorClass, errorMessage] where both are null
     * if no semantic error was detected.
     *
     * @param array|null $responseBody The parsed JSON response body
     * @param int $httpStatus The HTTP status code (for context)
     * @return array{0: string|null, 1: string|null} [errorClass, errorMessage]
     */
    public function analyze(?array $responseBody, int $httpStatus = 200): array
    {
        if (!$responseBody || !is_array($responseBody)) {
            return [null, null];
        }

        // Pattern 1: Explicit "error" field (VisionaryData, generic APIs)
        if (isset($responseBody['error']) && $this->isNonEmptyValue($responseBody['error'])) {
            $message = $this->extractMessage($responseBody['error']);
            return ['SemanticError:ErrorField', $message];
        }

        // Pattern 2: "errors" array (REST APIs, GraphQL)
        if (isset($responseBody['errors']) && is_array($responseBody['errors']) && count($responseBody['errors']) > 0) {
            $firstError = $responseBody['errors'][0] ?? null;
            $message = $this->extractMessage($firstError) ?? 'Unknown error in errors array';
            return ['SemanticError:ErrorsArray', $message];
        }

        // Pattern 3: "error_message" field (some legacy APIs)
        if (isset($responseBody['error_message']) && $this->isNonEmptyValue($responseBody['error_message'])) {
            $message = $this->extractMessage($responseBody['error_message']);
            return ['SemanticError:ErrorMessage', $message];
        }

        // Pattern 4: success=false (common pattern)
        if (array_key_exists('success', $responseBody) && $responseBody['success'] === false) {
            $message = $responseBody['message']
                ?? $responseBody['reason']
                ?? $responseBody['error']
                ?? 'Success flag is false';
            return ['SemanticError:SuccessFalse', $this->extractMessage($message)];
        }

        // Pattern 5: Numeric status >= 400 in body (VisionaryData pattern)
        if (isset($responseBody['status']) && is_numeric($responseBody['status'])) {
            $bodyStatus = (int) $responseBody['status'];
            if ($bodyStatus >= 400) {
                $message = $responseBody['error']
                    ?? $responseBody['message']
                    ?? $responseBody['error_message']
                    ?? "Status {$bodyStatus} in response body";
                return ['SemanticError:StatusInBody', $this->extractMessage($message)];
            }
        }

        // Pattern 6: Status string indicates failure
        if (isset($responseBody['status']) && is_string($responseBody['status'])) {
            $statusLower = strtolower(trim($responseBody['status']));
            if (in_array($statusLower, self::FAILURE_STATUSES, true)) {
                $message = $responseBody['message']
                    ?? $responseBody['reason']
                    ?? $responseBody['error']
                    ?? "Status: {$responseBody['status']}";
                return ['SemanticError:StatusString', $this->extractMessage($message)];
            }
        }

        // Pattern 7: "exception" field (SOAP/Java APIs)
        if (isset($responseBody['exception']) && $this->isNonEmptyValue($responseBody['exception'])) {
            $message = $this->extractMessage($responseBody['exception']);
            return ['SemanticError:Exception', $message];
        }

        // Pattern 8: "fault" field (SOAP)
        if (isset($responseBody['fault']) && $this->isNonEmptyValue($responseBody['fault'])) {
            $message = $this->extractMessage($responseBody['fault']);
            return ['SemanticError:Fault', $message];
        }

        // No semantic error detected
        return [null, null];
    }

    /**
     * Checks if a response body contains a semantic error.
     *
     * @param array|null $responseBody The parsed JSON response body
     * @return bool True if semantic error detected
     */
    public function hasError(?array $responseBody): bool
    {
        [$errorClass, ] = $this->analyze($responseBody);
        return $errorClass !== null;
    }

    /**
     * Extracts a human-readable message from various value types.
     *
     * @param mixed $value The value to extract message from
     * @return string The extracted message (truncated to 500 chars)
     */
    private function extractMessage(mixed $value): string
    {
        if (is_string($value)) {
            return $this->sanitizeMessage($value);
        }

        if (is_array($value)) {
            // Try common message fields
            if (isset($value['message'])) {
                return $this->sanitizeMessage((string) $value['message']);
            }
            if (isset($value['msg'])) {
                return $this->sanitizeMessage((string) $value['msg']);
            }
            if (isset($value['description'])) {
                return $this->sanitizeMessage((string) $value['description']);
            }
            if (isset($value['text'])) {
                return $this->sanitizeMessage((string) $value['text']);
            }
            // Fallback to JSON encoding
            return $this->sanitizeMessage(json_encode($value, JSON_UNESCAPED_UNICODE));
        }

        if (is_object($value)) {
            return $this->sanitizeMessage(json_encode($value, JSON_UNESCAPED_UNICODE));
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        return 'Unknown error';
    }

    /**
     * Sanitizes and truncates a message string.
     *
     * @param string $message The message to sanitize
     * @return string Sanitized message (max 500 chars)
     */
    private function sanitizeMessage(string $message): string
    {
        // Remove control characters
        $message = preg_replace('/[\x00-\x1F\x7F]/u', '', $message);

        // Trim whitespace
        $message = trim($message);

        // Truncate to 500 characters
        if (strlen($message) > 500) {
            $message = substr($message, 0, 497) . '...';
        }

        return $message ?: 'Unknown error';
    }

    /**
     * Checks if a value is non-empty (handles edge cases like 0).
     *
     * @param mixed $value The value to check
     * @return bool True if value is non-empty
     */
    private function isNonEmptyValue(mixed $value): bool
    {
        if ($value === null || $value === '' || $value === []) {
            return false;
        }

        // "0" and 0 are valid error codes
        return true;
    }
}
