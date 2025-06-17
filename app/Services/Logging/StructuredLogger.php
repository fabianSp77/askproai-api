<?php

namespace App\Services\Logging;

use App\Models\ApiCallLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StructuredLogger
{
    /**
     * The correlation ID for the current request
     */
    private ?string $correlationId = null;

    /**
     * Additional context that will be added to all logs
     */
    private array $globalContext = [];

    /**
     * Sensitive keys that should be masked
     */
    private array $sensitiveKeys = [
        'password',
        'api_key',
        'token',
        'secret',
        'credit_card',
        'card_number',
        'cvv',
        'authorization',
        'bearer',
        'key',
        'private_key',
        'refresh_token',
        'access_token'
    ];

    /**
     * Get or generate correlation ID for the current request
     */
    public function getCorrelationId(): string
    {
        if (!$this->correlationId) {
            // Try to get from request header first
            $this->correlationId = request()->header('X-Correlation-ID') 
                ?? request()->header('X-Request-ID')
                ?? Str::uuid()->toString();
        }
        
        return $this->correlationId;
    }

    /**
     * Set correlation ID explicitly
     */
    public function setCorrelationId(string $correlationId): self
    {
        $this->correlationId = $correlationId;
        return $this;
    }

    /**
     * Add global context that will be included in all logs
     */
    public function withContext(array $context): self
    {
        $this->globalContext = array_merge($this->globalContext, $context);
        return $this;
    }

    /**
     * Log booking flow step with detailed context
     */
    public function logBookingFlow(string $step, array $context = []): void
    {
        $channel = 'booking_flow';
        
        $logData = $this->buildLogData([
            'flow' => 'booking',
            'step' => $step,
            'booking_context' => array_merge([
                'customer_id' => $context['customer_id'] ?? null,
                'appointment_id' => $context['appointment_id'] ?? null,
                'service_id' => $context['service_id'] ?? null,
                'staff_id' => $context['staff_id'] ?? null,
                'branch_id' => $context['branch_id'] ?? null,
                'requested_time' => $context['requested_time'] ?? null,
                'duration_minutes' => $context['duration_minutes'] ?? null,
            ], $context)
        ]);

        // Log to file
        $this->log($channel, 'info', "Booking flow: {$step}", $logData);

        // Store important steps in database for analytics
        $this->storeBookingFlowStep($step, $logData);
    }

    /**
     * Log API call with automatic storage to ApiCallLog model
     */
    public function logApiCall(
        string $service,
        string $endpoint,
        string $method = 'POST',
        array $requestData = [],
        $response = null,
        ?float $duration = null,
        ?string $error = null
    ): void {
        $startTime = $requestData['start_time'] ?? Carbon::now();
        $endTime = Carbon::now();
        
        if ($duration === null && $startTime instanceof Carbon) {
            $duration = $startTime->diffInMilliseconds($endTime);
        } else {
            $duration = ($duration ?? 0) * 1000; // Convert to milliseconds
        }

        // Extract response data
        $responseStatus = null;
        $responseBody = null;
        $responseHeaders = null;

        if ($response) {
            if (is_object($response) && method_exists($response, 'status')) {
                $responseStatus = $response->status();
                $responseBody = method_exists($response, 'json') ? $response->json() : $response->body();
                $responseHeaders = method_exists($response, 'headers') ? $response->headers() : [];
            } elseif (is_array($response)) {
                $responseStatus = $response['status'] ?? 200;
                $responseBody = $response['body'] ?? $response;
                $responseHeaders = $response['headers'] ?? [];
            }
        }

        // Prepare data for ApiCallLog
        $apiCallData = [
            'correlation_id' => $this->getCorrelationId(),
            'service' => $service,
            'endpoint' => $endpoint,
            'method' => strtoupper($method),
            'request_headers' => $this->maskSensitiveData($requestData['headers'] ?? []),
            'request_body' => $this->maskSensitiveData($requestData['body'] ?? $requestData),
            'response_status' => $responseStatus,
            'response_headers' => $this->maskSensitiveData($responseHeaders),
            'response_body' => $this->maskSensitiveData($responseBody),
            'duration_ms' => $duration,
            'company_id' => $this->getCurrentCompanyId(),
            'user_id' => Auth::id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'error_message' => $error,
            'requested_at' => $startTime,
            'responded_at' => $endTime,
        ];

        // Store in database
        try {
            ApiCallLog::create($apiCallData);
        } catch (\Exception $e) {
            // Log the error but don't fail the request
            Log::error('Failed to store API call log', [
                'error' => $e->getMessage(),
                'api_call_data' => $apiCallData
            ]);
        }

        // Also log to file for immediate visibility
        $channel = strtolower($service);
        if (!in_array($channel, ['calcom', 'retell', 'webhooks'])) {
            $channel = 'api';
        }

        $level = $error ? 'error' : 'info';
        $message = $error 
            ? "API call failed to {$service}::{$endpoint}: {$error}"
            : "API call to {$service}::{$endpoint}";

        $this->log($channel, $level, $message, [
            'api_call' => $apiCallData
        ]);
    }

    /**
     * Log webhook processing
     */
    public function logWebhook(string $source, string $event, array $payload, array $context = []): void
    {
        $logData = $this->buildLogData([
            'webhook' => [
                'source' => $source,
                'event' => $event,
                'event_id' => $payload['id'] ?? $payload['event_id'] ?? null,
                'payload' => $this->maskSensitiveData($payload),
                'headers' => $this->maskSensitiveData(request()->headers->all()),
                'signature_valid' => $context['signature_valid'] ?? true,
            ]
        ]);

        $this->log('webhooks', 'info', "Webhook received: {$source}::{$event}", $logData);
    }

    /**
     * Log error with full context
     */
    public function logError(\Throwable $exception, array $context = []): void
    {
        $logData = $this->buildLogData([
            'error' => [
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $this->sanitizeStackTrace($exception->getTrace()),
            ],
            'context' => $context
        ]);

        Log::error($exception->getMessage(), $logData);

        // Log to critical channel if it's a critical error
        if ($this->isCriticalError($exception)) {
            Log::channel('critical')->error("CRITICAL: " . $exception->getMessage(), $logData);
        }
    }

    /**
     * Log performance metrics
     */
    public function logPerformance(string $operation, float $duration, array $metrics = []): void
    {
        $logData = $this->buildLogData([
            'performance' => array_merge([
                'operation' => $operation,
                'duration_ms' => round($duration * 1000, 2),
                'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            ], $metrics)
        ]);

        // Log to slow queries channel if it's a database operation
        if (str_contains(strtolower($operation), 'query') || str_contains(strtolower($operation), 'database')) {
            Log::channel('slow_queries')->warning("Slow operation: {$operation}", $logData);
        } else {
            Log::info("Performance metric: {$operation}", $logData);
        }
    }

    /**
     * Log security event
     */
    public function logSecurity(string $event, string $severity = 'warning', array $details = []): void
    {
        $logData = $this->buildLogData([
            'security' => array_merge([
                'event' => $event,
                'severity' => $severity,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'referer' => request()->header('referer'),
                'origin' => request()->header('origin'),
            ], $details)
        ]);

        Log::channel('critical')->$severity("Security event: {$event}", $logData);
    }

    /**
     * Log to a specific channel with a specific level
     */
    public function log(string $channel, string $level, string $message, array $context = []): void
    {
        $logData = $this->buildLogData($context);
        
        // Use the channel if it exists, otherwise use default
        if (config("logging.channels.{$channel}")) {
            Log::channel($channel)->$level($message, $logData);
        } else {
            Log::$level($message, $logData);
        }
    }

    /**
     * Build complete log data with all context
     */
    private function buildLogData(array $context = []): array
    {
        return array_merge(
            $this->getDefaultContext(),
            $this->globalContext,
            $context
        );
    }

    /**
     * Get default context for all logs
     */
    private function getDefaultContext(): array
    {
        return [
            'correlation_id' => $this->getCorrelationId(),
            'timestamp' => now()->toIso8601String(),
            'request' => [
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'request_id' => request()->header('X-Request-ID'),
            ],
            'user' => [
                'id' => Auth::id(),
                'company_id' => $this->getCurrentCompanyId(),
                'branch_id' => session('branch_id'),
                'type' => Auth::check() ? get_class(Auth::user()) : null,
            ],
            'server' => [
                'hostname' => gethostname(),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'environment' => app()->environment(),
            ],
        ];
    }

    /**
     * Get current company ID from various sources
     */
    private function getCurrentCompanyId(): ?string
    {
        // Try auth user
        if (Auth::check() && Auth::user()->company_id) {
            return Auth::user()->company_id;
        }
        
        // Try session
        if (session()->has('company_id')) {
            return session('company_id');
        }
        
        // Try request header
        if (request()->hasHeader('X-Company-ID')) {
            return request()->header('X-Company-ID');
        }
        
        // Try route parameter
        if (request()->route('company')) {
            return request()->route('company');
        }
        
        return null;
    }

    /**
     * Mask sensitive data recursively
     */
    private function maskSensitiveData($data): mixed
    {
        if (!$data) {
            return $data;
        }

        if (is_string($data)) {
            return $data;
        }

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if ($this->isSensitiveKey($key)) {
                    $data[$key] = '***MASKED***';
                } elseif (is_array($value) || is_object($value)) {
                    $data[$key] = $this->maskSensitiveData($value);
                }
            }
        } elseif (is_object($data)) {
            $data = (array) $data;
            return (object) $this->maskSensitiveData($data);
        }

        return $data;
    }

    /**
     * Check if a key contains sensitive information
     */
    private function isSensitiveKey(string $key): bool
    {
        $lowerKey = strtolower($key);
        
        foreach ($this->sensitiveKeys as $sensitiveKey) {
            if (str_contains($lowerKey, $sensitiveKey)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Sanitize stack trace to remove sensitive data
     */
    private function sanitizeStackTrace(array $trace): array
    {
        return array_map(function ($frame) {
            // Remove args to prevent sensitive data exposure
            unset($frame['args']);
            
            // Only keep essential information
            return [
                'file' => $frame['file'] ?? 'unknown',
                'line' => $frame['line'] ?? 0,
                'function' => $frame['function'] ?? 'unknown',
                'class' => $frame['class'] ?? null,
                'type' => $frame['type'] ?? null,
            ];
        }, array_slice($trace, 0, 10)); // Limit to first 10 frames
    }

    /**
     * Check if error is critical
     */
    private function isCriticalError(\Throwable $e): bool
    {
        // Critical exception types
        $criticalTypes = [
            \PDOException::class,
            \Illuminate\Database\QueryException::class,
            \Symfony\Component\HttpKernel\Exception\HttpException::class,
            \Illuminate\Auth\AuthenticationException::class,
            \Illuminate\Auth\Access\AuthorizationException::class,
            \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
        ];
        
        foreach ($criticalTypes as $type) {
            if ($e instanceof $type) {
                return true;
            }
        }
        
        // Check HTTP status codes
        if (method_exists($e, 'getStatusCode')) {
            $statusCode = $e->getStatusCode();
            return in_array($statusCode, [500, 503, 401, 403, 404]);
        }
        
        // Check error codes
        return in_array($e->getCode(), [500, 503, 401, 403]);
    }

    /**
     * Store booking flow step in database for analytics
     */
    private function storeBookingFlowStep(string $step, array $logData): void
    {
        try {
            DB::table('booking_flow_logs')->insert([
                'correlation_id' => $this->getCorrelationId(),
                'step' => $step,
                'company_id' => $this->getCurrentCompanyId(),
                'branch_id' => $logData['booking_context']['branch_id'] ?? null,
                'customer_id' => $logData['booking_context']['customer_id'] ?? null,
                'appointment_id' => $logData['booking_context']['appointment_id'] ?? null,
                'context' => json_encode($logData),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Silently fail - we don't want logging to break the application
            Log::error('Failed to store booking flow step', [
                'error' => $e->getMessage(),
                'step' => $step
            ]);
        }
    }

    /**
     * Create a child logger with additional context
     */
    public function withAdditionalContext(array $context): self
    {
        $logger = clone $this;
        $logger->globalContext = array_merge($this->globalContext, $context);
        return $logger;
    }

    /**
     * Log a successful operation
     */
    public function success(string $message, array $context = []): void
    {
        $this->log('default', 'info', "✓ {$message}", array_merge(['status' => 'success'], $context));
    }

    /**
     * Log a failed operation
     */
    public function failure(string $message, array $context = []): void
    {
        $this->log('default', 'error', "✗ {$message}", array_merge(['status' => 'failure'], $context));
    }

    /**
     * Log a warning
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log('default', 'warning', "⚠ {$message}", array_merge(['status' => 'warning'], $context));
    }

    /**
     * Log informational message
     */
    public function info(string $message, array $context = []): void
    {
        $this->log('default', 'info', $message, $context);
    }

    /**
     * Log debug message
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log('default', 'debug', $message, $context);
    }
}