<?php

namespace App\Services\Logging;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ProductionLogger
{
    private static ?string $traceId = null;
    
    /**
     * Get or generate trace ID for request
     */
    public static function getTraceId(): string
    {
        if (!self::$traceId) {
            self::$traceId = request()->header('X-Trace-ID', Str::uuid()->toString());
        }
        return self::$traceId;
    }
    
    /**
     * Log error with full context
     */
    public function logError(\Throwable $e, array $context = []): void
    {
        $defaultContext = $this->getDefaultContext();
        $errorContext = $this->getErrorContext($e);
        
        $fullContext = array_merge($defaultContext, $errorContext, $context);
        
        Log::error($e->getMessage(), $fullContext);
        
        // Also log to separate error tracking if critical
        if ($this->isCriticalError($e)) {
            $this->logCriticalError($e, $fullContext);
        }
    }
    
    /**
     * Log API call with context
     */
    public function logApiCall(string $service, string $method, array $params, $response, float $duration): void
    {
        $context = array_merge($this->getDefaultContext(), [
            'api_call' => [
                'service' => $service,
                'method' => $method,
                'params' => $this->sanitizeParams($params),
                'duration_ms' => round($duration * 1000, 2),
                'success' => $response !== null,
                'response_size' => is_string($response) ? strlen($response) : strlen(json_encode($response)),
            ]
        ]);
        
        Log::info("API call to {$service}::{$method}", $context);
    }
    
    /**
     * Log webhook received
     */
    public function logWebhook(string $source, string $event, array $payload): void
    {
        $context = array_merge($this->getDefaultContext(), [
            'webhook' => [
                'source' => $source,
                'event' => $event,
                'payload_size' => strlen(json_encode($payload)),
                'headers' => request()->headers->all(),
            ]
        ]);
        
        Log::info("Webhook received from {$source}", $context);
    }
    
    /**
     * Log performance metrics
     */
    public function logPerformance(string $operation, float $duration, array $metrics = []): void
    {
        $context = array_merge($this->getDefaultContext(), [
            'performance' => array_merge([
                'operation' => $operation,
                'duration_ms' => round($duration * 1000, 2),
                'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            ], $metrics)
        ]);
        
        Log::info("Performance metric: {$operation}", $context);
    }
    
    /**
     * Log security event
     */
    public function logSecurity(string $event, array $details = []): void
    {
        $context = array_merge($this->getDefaultContext(), [
            'security' => array_merge([
                'event' => $event,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'referer' => request()->header('referer'),
            ], $details)
        ]);
        
        Log::warning("Security event: {$event}", $context);
    }
    
    /**
     * Get default context for all logs
     */
    private function getDefaultContext(): array
    {
        return [
            'trace_id' => self::getTraceId(),
            'request' => [
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ],
            'user' => [
                'id' => Auth::id(),
                'company_id' => $this->getCurrentCompanyId(),
                'branch_id' => session('branch_id'),
            ],
            'server' => [
                'hostname' => gethostname(),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
            ],
            'timestamp' => now()->toIso8601String(),
        ];
    }
    
    /**
     * Get error-specific context
     */
    private function getErrorContext(\Throwable $e): array
    {
        return [
            'exception' => [
                'class' => get_class($e),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $this->sanitizeStackTrace($e->getTrace()),
                'previous' => $e->getPrevious() ? get_class($e->getPrevious()) : null,
            ]
        ];
    }
    
    /**
     * Get current company ID from various sources
     */
    private function getCurrentCompanyId(): ?int
    {
        // Try auth user
        if (Auth::check() && Auth::user()->company_id) {
            return Auth::user()->company_id;
        }
        
        // Try session
        if (session()->has('company_id')) {
            return session('company_id');
        }
        
        // Try request context
        if (request()->route('company')) {
            return request()->route('company');
        }
        
        return null;
    }
    
    /**
     * Sanitize sensitive data from parameters
     */
    private function sanitizeParams(array $params): array
    {
        $sensitiveKeys = ['password', 'api_key', 'token', 'secret', 'credit_card'];
        
        array_walk_recursive($params, function (&$value, $key) use ($sensitiveKeys) {
            foreach ($sensitiveKeys as $sensitive) {
                if (stripos($key, $sensitive) !== false) {
                    $value = '***REDACTED***';
                }
            }
        });
        
        return $params;
    }
    
    /**
     * Sanitize stack trace to remove sensitive data
     */
    private function sanitizeStackTrace(array $trace): array
    {
        return array_map(function ($frame) {
            // Remove args to prevent sensitive data exposure
            unset($frame['args']);
            return $frame;
        }, array_slice($trace, 0, 10)); // Limit to first 10 frames
    }
    
    /**
     * Check if error is critical
     */
    private function isCriticalError(\Throwable $e): bool
    {
        // Define critical error types
        $criticalTypes = [
            \PDOException::class,
            \Illuminate\Database\QueryException::class,
            \Symfony\Component\HttpKernel\Exception\HttpException::class,
        ];
        
        foreach ($criticalTypes as $type) {
            if ($e instanceof $type) {
                return true;
            }
        }
        
        // Check error codes
        $criticalCodes = [500, 503, 401, 403];
        return in_array($e->getCode(), $criticalCodes);
    }
    
    /**
     * Log critical error to separate channel
     */
    private function logCriticalError(\Throwable $e, array $context): void
    {
        // Log to critical channel
        Log::channel('critical')->error("CRITICAL: " . $e->getMessage(), $context);
        
        // Store in database for monitoring
        \DB::table('critical_errors')->insert([
            'trace_id' => self::getTraceId(),
            'error_class' => get_class($e),
            'error_message' => $e->getMessage(),
            'error_code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'context' => json_encode($context),
            'created_at' => now(),
        ]);
        
        // TODO: Send alert to monitoring service
    }
}