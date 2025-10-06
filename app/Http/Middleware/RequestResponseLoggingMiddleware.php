<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequestResponseLoggingMiddleware
{
    /**
     * Paths to exclude from logging
     */
    private array $excludedPaths = [
        'api/health',
        'api/monitoring',
        'telescope',
        'horizon',
        '_debugbar',
    ];

    /**
     * Sensitive fields to redact
     */
    private array $sensitiveFields = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'api_key',
        'api_secret',
        'token',
        'access_token',
        'refresh_token',
        'credit_card',
        'card_number',
        'cvv',
        'ssn',
        'bank_account',
        'routing_number',
    ];

    /**
     * Handle an incoming request
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip logging for excluded paths
        if ($this->shouldSkipLogging($request)) {
            return $next($request);
        }

        // Generate unique request ID
        $requestId = $this->generateRequestId();
        $request->headers->set('X-Request-ID', $requestId);

        // Log incoming request
        $this->logRequest($request, $requestId);

        // Process request
        $response = $next($request);

        // Log outgoing response
        $this->logResponse($request, $response, $requestId);

        // Add request ID to response headers
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }

    /**
     * Check if logging should be skipped
     */
    private function shouldSkipLogging(Request $request): bool
    {
        // Skip if logging is disabled
        if (!config('logging.api_requests', true)) {
            return true;
        }

        // Check excluded paths
        foreach ($this->excludedPaths as $path) {
            if ($request->is($path) || $request->is($path . '/*')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate unique request ID
     */
    private function generateRequestId(): string
    {
        return Str::uuid()->toString();
    }

    /**
     * Log incoming request
     */
    private function logRequest(Request $request, string $requestId): void
    {
        $logData = [
            'request_id' => $requestId,
            'type' => 'request',
            'timestamp' => now()->toIso8601String(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => $request->user()?->id,
        ];

        // Add headers (excluding sensitive ones)
        $logData['headers'] = $this->sanitizeHeaders($request->headers->all());

        // Add query parameters
        if ($request->query()) {
            $logData['query'] = $this->sanitizeData($request->query());
        }

        // Add request body
        if ($request->isJson()) {
            $logData['body'] = $this->sanitizeData($request->json()->all());
        } elseif ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('PATCH')) {
            $logData['body'] = $this->sanitizeData($request->all());
        }

        // Add route information
        if ($route = $request->route()) {
            $logData['route'] = [
                'name' => $route->getName(),
                'controller' => $route->getActionName(),
                'middleware' => $route->middleware(),
                'parameters' => $this->sanitizeData($route->parameters()),
            ];
        }

        // Log at appropriate level
        $this->logAtLevel('info', 'API Request', $logData);
    }

    /**
     * Log outgoing response
     */
    private function logResponse(Request $request, Response $response, string $requestId): void
    {
        $logData = [
            'request_id' => $requestId,
            'type' => 'response',
            'timestamp' => now()->toIso8601String(),
            'status_code' => $response->getStatusCode(),
            'status_text' => Response::$statusTexts[$response->getStatusCode()] ?? 'Unknown',
        ];

        // Add response headers
        $logData['headers'] = $this->sanitizeHeaders($response->headers->all());

        // Add response body (limited for large responses)
        $content = $response->getContent();
        $contentLength = strlen($content);
        $logData['content_length'] = $contentLength;

        if ($contentLength > 0) {
            // Try to decode JSON response
            $decodedContent = json_decode($content, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                // It's JSON, sanitize and log
                $logData['body'] = $this->sanitizeData($decodedContent);
            } elseif ($contentLength < 1000) {
                // Small non-JSON response, log as is
                $logData['body'] = substr($content, 0, 1000);
            } else {
                // Large non-JSON response, log truncated
                $logData['body'] = '[Response too large - ' . $contentLength . ' bytes]';
            }
        }

        // Determine log level based on status code
        $logLevel = $this->determineLogLevel($response->getStatusCode());

        // Log response
        $this->logAtLevel($logLevel, 'API Response', $logData);
    }

    /**
     * Sanitize headers to remove sensitive information
     */
    private function sanitizeHeaders(array $headers): array
    {
        $sanitized = [];
        $sensitiveHeaders = ['authorization', 'cookie', 'x-api-key', 'x-api-secret'];

        foreach ($headers as $name => $values) {
            $lowerName = strtolower($name);

            if (in_array($lowerName, $sensitiveHeaders)) {
                $sanitized[$name] = ['***REDACTED***'];
            } else {
                $sanitized[$name] = $values;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize data to remove sensitive information
     */
    private function sanitizeData($data): mixed
    {
        if (!is_array($data)) {
            return $data;
        }

        $sanitized = [];

        foreach ($data as $key => $value) {
            $lowerKey = strtolower($key);

            // Check if key contains sensitive field name
            $isSensitive = false;
            foreach ($this->sensitiveFields as $sensitiveField) {
                if (str_contains($lowerKey, $sensitiveField)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $sanitized[$key] = '***REDACTED***';
            } elseif (is_array($value)) {
                // Recursively sanitize nested arrays
                $sanitized[$key] = $this->sanitizeData($value);
            } else {
                // Truncate very long values
                if (is_string($value) && strlen($value) > 1000) {
                    $sanitized[$key] = substr($value, 0, 1000) . '... [truncated]';
                } else {
                    $sanitized[$key] = $value;
                }
            }
        }

        return $sanitized;
    }

    /**
     * Determine log level based on status code
     */
    private function determineLogLevel(int $statusCode): string
    {
        if ($statusCode >= 500) {
            return 'error';
        }

        if ($statusCode >= 400) {
            return 'warning';
        }

        if ($statusCode >= 300) {
            return 'info';
        }

        return 'info';
    }

    /**
     * Log at specified level
     */
    private function logAtLevel(string $level, string $message, array $context): void
    {
        // Use separate channel for API logs
        $channel = config('logging.api_channel', 'api');

        try {
            Log::channel($channel)->{$level}($message, $context);
        } catch (\Exception $e) {
            // Fall back to default channel if custom channel doesn't exist
            Log::{$level}($message, $context);
        }
    }
}