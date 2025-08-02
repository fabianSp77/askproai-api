<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Real-time Performance Monitoring Middleware
 * Tracks response times, query counts, and memory usage
 */
class PerformanceMonitor
{
    /**
     * Performance thresholds for alerts
     */
    protected const THRESHOLDS = [
        'response_time_ms' => 500,      // Alert if response > 500ms
        'query_count' => 50,             // Alert if queries > 50
        'memory_usage_mb' => 100,        // Alert if memory > 100MB
        'query_time_ms' => 100,          // Alert if single query > 100ms
    ];
    
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip monitoring for static assets
        if ($this->shouldSkip($request)) {
            return $next($request);
        }
        
        // Start performance tracking
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        $startQueries = count(DB::getQueryLog());
        
        // Enable query logging
        DB::enableQueryLog();
        
        // Track query execution times
        DB::listen(function ($query) {
            if ($query->time > self::THRESHOLDS['query_time_ms']) {
                Log::channel('performance')->warning('Slow query detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time_ms' => $query->time,
                    'connection' => $query->connectionName,
                ]);
            }
        });
        
        // Process request
        $response = $next($request);
        
        // Calculate metrics
        $duration = (microtime(true) - $startTime) * 1000; // Convert to ms
        $memoryUsed = (memory_get_usage(true) - $startMemory) / 1024 / 1024; // Convert to MB
        $queryCount = count(DB::getQueryLog()) - $startQueries;
        $queries = DB::getQueryLog();
        
        // Analyze performance
        $metrics = [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'response_time_ms' => round($duration, 2),
            'memory_used_mb' => round($memoryUsed, 2),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'query_count' => $queryCount,
            'total_query_time_ms' => round(collect($queries)->sum('time'), 2),
            'status_code' => $response->getStatusCode(),
            'user_id' => auth()->id(),
            'company_id' => auth()->user()->company_id ?? null,
            'timestamp' => now()->toIso8601String(),
        ];
        
        // Check thresholds and log warnings
        $this->checkThresholds($metrics);
        
        // Update real-time metrics
        $this->updateRealTimeMetrics($metrics);
        
        // Add performance headers for debugging
        if (config('app.debug') || $request->hasHeader('X-Debug-Performance')) {
            $response->headers->set('X-Response-Time', $duration . 'ms');
            $response->headers->set('X-Query-Count', $queryCount);
            $response->headers->set('X-Memory-Usage', $memoryUsed . 'MB');
            $response->headers->set('X-Memory-Peak', $metrics['memory_peak_mb'] . 'MB');
        }
        
        // Log detailed metrics for slow requests
        if ($duration > self::THRESHOLDS['response_time_ms']) {
            $this->logSlowRequest($metrics, $queries);
        }
        
        // Disable query logging to prevent memory leaks
        DB::disableQueryLog();
        
        return $response;
    }
    
    /**
     * Check if request should skip monitoring
     */
    protected function shouldSkip(Request $request): bool
    {
        $skipPaths = [
            'css/*',
            'js/*',
            'images/*',
            'fonts/*',
            'favicon.ico',
            'robots.txt',
            '_debugbar/*',
            'livewire/*',
            'health-check',
        ];
        
        foreach ($skipPaths as $path) {
            if ($request->is($path)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check performance thresholds and log warnings
     */
    protected function checkThresholds(array $metrics): void
    {
        $warnings = [];
        
        if ($metrics['response_time_ms'] > self::THRESHOLDS['response_time_ms']) {
            $warnings[] = "Response time {$metrics['response_time_ms']}ms exceeds threshold";
        }
        
        if ($metrics['query_count'] > self::THRESHOLDS['query_count']) {
            $warnings[] = "Query count {$metrics['query_count']} exceeds threshold";
        }
        
        if ($metrics['memory_used_mb'] > self::THRESHOLDS['memory_usage_mb']) {
            $warnings[] = "Memory usage {$metrics['memory_used_mb']}MB exceeds threshold";
        }
        
        if (!empty($warnings)) {
            Log::channel('performance')->warning('Performance threshold exceeded', [
                'warnings' => $warnings,
                'metrics' => $metrics,
            ]);
        }
    }
    
    /**
     * Update real-time metrics in cache
     */
    protected function updateRealTimeMetrics(array $metrics): void
    {
        $key = 'performance_metrics_' . now()->format('Y-m-d-H');
        
        Cache::remember($key, 3600, function () {
            return [
                'requests' => 0,
                'total_response_time' => 0,
                'total_queries' => 0,
                'total_memory' => 0,
                'slow_requests' => 0,
                'errors' => 0,
            ];
        });
        
        // Atomic increment operations
        Cache::increment($key . ':requests');
        Cache::increment($key . ':total_response_time', $metrics['response_time_ms']);
        Cache::increment($key . ':total_queries', $metrics['query_count']);
        Cache::increment($key . ':total_memory', $metrics['memory_used_mb']);
        
        if ($metrics['response_time_ms'] > self::THRESHOLDS['response_time_ms']) {
            Cache::increment($key . ':slow_requests');
        }
        
        if ($metrics['status_code'] >= 500) {
            Cache::increment($key . ':errors');
        }
        
        // Update endpoint-specific metrics
        $endpointKey = 'endpoint_metrics:' . md5($metrics['method'] . ':' . $metrics['url']);
        $endpointMetrics = Cache::get($endpointKey, [
            'count' => 0,
            'total_time' => 0,
            'max_time' => 0,
            'min_time' => PHP_INT_MAX,
        ]);
        
        $endpointMetrics['count']++;
        $endpointMetrics['total_time'] += $metrics['response_time_ms'];
        $endpointMetrics['max_time'] = max($endpointMetrics['max_time'], $metrics['response_time_ms']);
        $endpointMetrics['min_time'] = min($endpointMetrics['min_time'], $metrics['response_time_ms']);
        
        Cache::put($endpointKey, $endpointMetrics, 3600);
    }
    
    /**
     * Log detailed information for slow requests
     */
    protected function logSlowRequest(array $metrics, array $queries): void
    {
        // Sort queries by execution time
        $slowQueries = collect($queries)
            ->sortByDesc('time')
            ->take(10)
            ->map(function ($query) {
                return [
                    'sql' => $query['query'],
                    'bindings' => $query['bindings'],
                    'time_ms' => $query['time'],
                ];
            })
            ->toArray();
        
        Log::channel('performance')->warning('Slow request detected', [
            'metrics' => $metrics,
            'slowest_queries' => $slowQueries,
            'backtrace' => $this->getSimplifiedBacktrace(),
        ]);
    }
    
    /**
     * Get simplified backtrace for debugging
     */
    protected function getSimplifiedBacktrace(): array
    {
        return collect(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20))
            ->filter(function ($frame) {
                return isset($frame['file']) && 
                       !str_contains($frame['file'], 'vendor/') &&
                       !str_contains($frame['file'], 'bootstrap/');
            })
            ->map(function ($frame) {
                return [
                    'file' => str_replace(base_path() . '/', '', $frame['file']),
                    'line' => $frame['line'],
                    'function' => $frame['function'] ?? 'unknown',
                ];
            })
            ->take(10)
            ->values()
            ->toArray();
    }
    
    /**
     * Get current performance metrics
     */
    public static function getMetrics(): array
    {
        $key = 'performance_metrics_' . now()->format('Y-m-d-H');
        
        $requests = Cache::get($key . ':requests', 0);
        $totalTime = Cache::get($key . ':total_response_time', 0);
        $totalQueries = Cache::get($key . ':total_queries', 0);
        $totalMemory = Cache::get($key . ':total_memory', 0);
        $slowRequests = Cache::get($key . ':slow_requests', 0);
        $errors = Cache::get($key . ':errors', 0);
        
        return [
            'requests' => $requests,
            'avg_response_time_ms' => $requests > 0 ? round($totalTime / $requests, 2) : 0,
            'avg_queries_per_request' => $requests > 0 ? round($totalQueries / $requests, 2) : 0,
            'avg_memory_mb' => $requests > 0 ? round($totalMemory / $requests, 2) : 0,
            'slow_request_percentage' => $requests > 0 ? round(($slowRequests / $requests) * 100, 2) : 0,
            'error_rate' => $requests > 0 ? round(($errors / $requests) * 100, 2) : 0,
            'period' => now()->format('Y-m-d H:00'),
        ];
    }
}