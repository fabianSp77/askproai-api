<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PerformanceMonitoring
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        // Track request
        $this->trackRequest();

        $response = $next($request);

        // Calculate metrics
        $duration = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
        $memoryUsed = memory_get_usage() - $startMemory;

        // Log slow requests
        if ($duration > 1000) { // Requests taking more than 1 second
            Log::warning('Slow request detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'duration_ms' => $duration,
                'memory_bytes' => $memoryUsed,
                'ip' => $request->ip(),
            ]);

            $this->incrementSlowRequests();
        }

        // Update performance metrics
        $this->updateMetrics($duration);

        // Add performance headers (useful for debugging)
        if (config('app.debug')) {
            $response->headers->set('X-Response-Time', round($duration, 2) . 'ms');
            $response->headers->set('X-Memory-Usage', $this->formatBytes($memoryUsed));
        }

        return $response;
    }

    /**
     * Track incoming request
     */
    private function trackRequest(): void
    {
        // Increment total requests counter
        Cache::increment('total_requests');

        // Track requests per minute
        $key = 'requests_minute_' . date('YmdHi');
        Cache::increment($key);
        Cache::put('requests_per_minute', Cache::get($key, 0), 70);
    }

    /**
     * Increment slow requests counter
     */
    private function incrementSlowRequests(): void
    {
        $key = 'slow_requests_' . date('YmdH');
        Cache::increment($key);
        Cache::put('slow_requests', Cache::get($key, 0), 3700);
    }

    /**
     * Update performance metrics
     */
    private function updateMetrics(float $duration): void
    {
        $metrics = Cache::get('performance_metrics', [
            'avg_response_time' => 0,
            'total_time' => 0,
            'request_count' => 0,
        ]);

        $metrics['total_time'] += $duration;
        $metrics['request_count']++;
        $metrics['avg_response_time'] = $metrics['total_time'] / $metrics['request_count'];

        Cache::put('performance_metrics', $metrics, 3600);

        // Update average response time
        Cache::put('average_response_time', round($metrics['avg_response_time'], 2), 3600);
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        return round($bytes / (1 << (10 * $pow)), 2) . ' ' . $units[$pow];
    }
}