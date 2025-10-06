<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Throwable;

class ErrorMonitoringService
{
    private const CACHE_PREFIX = 'error_monitor:';
    private const ERROR_THRESHOLD = 10;
    private const TIME_WINDOW = 300; // 5 minutes

    /**
     * Track and analyze errors with intelligent categorization
     */
    public function trackError(Throwable $exception, array $context = []): void
    {
        $errorKey = $this->generateErrorKey($exception);
        $errorData = [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'code' => $exception->getCode(),
            'trace' => array_slice($exception->getTrace(), 0, 5),
            'context' => $context,
            'timestamp' => now()->toIso8601String(),
            'url' => request()->fullUrl() ?? 'CLI',
            'method' => request()->method() ?? 'CLI',
            'ip' => request()->ip() ?? 'CLI',
            'user_agent' => request()->userAgent() ?? 'CLI',
        ];

        // Store in cache for quick access
        $this->storeErrorInCache($errorKey, $errorData);

        // Check for error patterns
        $this->detectErrorPatterns($errorKey, $errorData);

        // Log with appropriate severity
        $this->logWithSeverity($exception, $errorData);
    }

    /**
     * Generate unique key for error grouping
     */
    private function generateErrorKey(Throwable $exception): string
    {
        return md5(
            get_class($exception) .
            $exception->getFile() .
            $exception->getLine()
        );
    }

    /**
     * Store error in cache for pattern detection
     */
    private function storeErrorInCache(string $key, array $data): void
    {
        $cacheKey = self::CACHE_PREFIX . $key;
        $errors = Cache::get($cacheKey, []);
        $errors[] = $data;

        // Keep only recent errors
        $errors = array_filter($errors, function($error) {
            return now()->diffInSeconds($error['timestamp']) < self::TIME_WINDOW;
        });

        Cache::put($cacheKey, $errors, now()->addMinutes(10));
    }

    /**
     * Detect error patterns and trigger alerts
     */
    private function detectErrorPatterns(string $key, array $errorData): void
    {
        $cacheKey = self::CACHE_PREFIX . $key;
        $errors = Cache::get($cacheKey, []);
        $errorCount = count($errors);

        // Check if error threshold exceeded
        if ($errorCount >= self::ERROR_THRESHOLD) {
            $this->triggerAlert('high_frequency', [
                'error_key' => $key,
                'count' => $errorCount,
                'time_window' => self::TIME_WINDOW,
                'latest_error' => $errorData
            ]);
        }

        // Check for cascading failures
        $this->checkCascadingFailures($errorData);

        // Check for security-related errors
        $this->checkSecurityErrors($errorData);
    }

    /**
     * Check for cascading failures
     */
    private function checkCascadingFailures(array $errorData): void
    {
        $recentErrors = Cache::get(self::CACHE_PREFIX . 'recent', []);
        $recentErrors[] = $errorData['message'];
        $recentErrors = array_slice($recentErrors, -20);

        Cache::put(self::CACHE_PREFIX . 'recent', $recentErrors, now()->addMinutes(5));

        // Detect database connection issues
        if ($this->containsPattern($recentErrors, ['connection', 'database', 'SQLSTATE'])) {
            $this->triggerAlert('database_issue', ['errors' => $recentErrors]);
        }

        // Detect memory issues
        if ($this->containsPattern($recentErrors, ['memory', 'exhausted', 'allocated'])) {
            $this->triggerAlert('memory_issue', ['errors' => $recentErrors]);
        }
    }

    /**
     * Check for security-related errors
     */
    private function checkSecurityErrors(array $errorData): void
    {
        $securityPatterns = [
            'authentication', 'unauthorized', 'forbidden',
            'csrf', 'token', 'permission', 'denied'
        ];

        $message = strtolower($errorData['message']);
        foreach ($securityPatterns as $pattern) {
            if (str_contains($message, $pattern)) {
                $this->triggerAlert('security_concern', $errorData);
                break;
            }
        }
    }

    /**
     * Check if array contains patterns
     */
    private function containsPattern(array $haystack, array $patterns): bool
    {
        $text = implode(' ', array_map('strtolower', $haystack));
        foreach ($patterns as $pattern) {
            if (str_contains($text, strtolower($pattern))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Trigger alert for critical issues
     */
    private function triggerAlert(string $type, array $data): void
    {
        Log::critical("Error Alert: {$type}", $data);

        // Store alert in database for dashboard
        Cache::put(
            self::CACHE_PREFIX . 'alert:' . $type,
            array_merge($data, ['triggered_at' => now()]),
            now()->addHours(1)
        );
    }

    /**
     * Log with appropriate severity based on error type
     */
    private function logWithSeverity(Throwable $exception, array $data): void
    {
        $severity = $this->determineSeverity($exception);

        switch ($severity) {
            case 'emergency':
                Log::emergency($exception->getMessage(), $data);
                break;
            case 'critical':
                Log::critical($exception->getMessage(), $data);
                break;
            case 'error':
                Log::error($exception->getMessage(), $data);
                break;
            case 'warning':
                Log::warning($exception->getMessage(), $data);
                break;
            default:
                Log::info($exception->getMessage(), $data);
        }
    }

    /**
     * Determine error severity
     */
    private function determineSeverity(Throwable $exception): string
    {
        $className = get_class($exception);

        // Critical errors
        if (str_contains($className, 'Database') ||
            str_contains($className, 'Redis') ||
            str_contains($className, 'Fatal')) {
            return 'critical';
        }

        // Security errors
        if (str_contains($className, 'Auth') ||
            str_contains($className, 'Token') ||
            str_contains($className, 'Permission')) {
            return 'error';
        }

        // Validation errors
        if (str_contains($className, 'Validation') ||
            str_contains($className, 'NotFound')) {
            return 'warning';
        }

        return 'error';
    }

    /**
     * Get error statistics
     */
    public function getStatistics(): array
    {
        $stats = [];
        $keys = Cache::get(self::CACHE_PREFIX . 'keys', []);

        foreach ($keys as $key) {
            $errors = Cache::get(self::CACHE_PREFIX . $key, []);
            if (!empty($errors)) {
                $latest = end($errors);
                $stats[] = [
                    'key' => $key,
                    'count' => count($errors),
                    'latest' => $latest['timestamp'],
                    'message' => $latest['message'],
                    'file' => basename($latest['file']) . ':' . $latest['line']
                ];
            }
        }

        return [
            'errors' => $stats,
            'alerts' => $this->getActiveAlerts(),
            'summary' => [
                'total_errors' => array_sum(array_column($stats, 'count')),
                'unique_errors' => count($stats),
                'time_range' => self::TIME_WINDOW . ' seconds'
            ]
        ];
    }

    /**
     * Get active alerts
     */
    private function getActiveAlerts(): array
    {
        $alertTypes = ['high_frequency', 'database_issue', 'memory_issue', 'security_concern'];
        $alerts = [];

        foreach ($alertTypes as $type) {
            $alert = Cache::get(self::CACHE_PREFIX . 'alert:' . $type);
            if ($alert) {
                $alerts[] = array_merge(['type' => $type], $alert);
            }
        }

        return $alerts;
    }
}