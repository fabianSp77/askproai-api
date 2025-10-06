<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PerformanceMonitoringMiddleware
{
    /**
     * Performance thresholds for alerting
     */
    private array $thresholds = [
        'response_time_warning' => 1000, // 1 second in ms
        'response_time_critical' => 3000, // 3 seconds in ms
        'memory_warning' => 50, // 50 MB
        'memory_critical' => 100, // 100 MB
        'query_count_warning' => 20,
        'query_count_critical' => 50,
        'query_time_warning' => 500, // 500ms total query time
        'query_time_critical' => 1000, // 1 second total query time
    ];

    /**
     * Handle an incoming request
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip monitoring for health check endpoints to avoid recursion
        if ($request->is('api/health*') || $request->is('api/monitoring*')) {
            return $next($request);
        }

        // Start performance monitoring
        $monitor = $this->startMonitoring($request);

        // Enable query logging
        if (config('app.debug') || config('monitoring.log_queries', false)) {
            DB::enableQueryLog();
        }

        // Process request
        $response = $next($request);

        // Complete monitoring
        $metrics = $this->completeMonitoring($monitor, $request, $response);

        // Store metrics
        $this->storeMetrics($metrics);

        // Check for performance issues
        $this->checkPerformanceIssues($metrics);

        // Add performance headers (optional, useful for debugging)
        if (config('monitoring.add_headers', false)) {
            $this->addPerformanceHeaders($response, $metrics);
        }

        return $response;
    }

    /**
     * Start monitoring
     */
    private function startMonitoring(Request $request): array
    {
        return [
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true),
            'request_id' => $this->generateRequestId(),
            'uri' => $request->getRequestUri(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_id' => $request->user()?->id,
        ];
    }

    /**
     * Complete monitoring and calculate metrics
     */
    private function completeMonitoring(array $monitor, Request $request, Response $response): array
    {
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        $metrics = [
            'request_id' => $monitor['request_id'],
            'timestamp' => now()->toIso8601String(),
            'request' => [
                'method' => $monitor['method'],
                'uri' => $monitor['uri'],
                'route' => $request->route()?->getName() ?? 'unknown',
                'controller' => $request->route()?->getActionName() ?? 'unknown',
                'ip' => $monitor['ip'],
                'user_id' => $monitor['user_id'],
                'user_agent' => $request->userAgent(),
            ],
            'response' => [
                'status_code' => $response->getStatusCode(),
                'size' => strlen($response->getContent()),
            ],
            'performance' => [
                'duration_ms' => round(($endTime - $monitor['start_time']) * 1000, 2),
                'memory_usage_mb' => round(($endMemory - $monitor['start_memory']) / 1048576, 2),
                'memory_peak_mb' => round(memory_get_peak_usage(true) / 1048576, 2),
                'cpu_usage' => sys_getloadavg()[0] ?? null,
            ],
        ];

        // Add database metrics if query logging is enabled
        if (DB::logging()) {
            $queries = DB::getQueryLog();
            $metrics['database'] = [
                'query_count' => count($queries),
                'total_time_ms' => round(array_sum(array_column($queries, 'time')), 2),
                'queries' => $this->formatQueries($queries),
            ];
        }

        return $metrics;
    }

    /**
     * Generate unique request ID
     */
    private function generateRequestId(): string
    {
        return sprintf(
            '%s-%s',
            now()->format('Ymd-His'),
            substr(md5(uniqid()), 0, 8)
        );
    }

    /**
     * Format queries for storage
     */
    private function formatQueries(array $queries): array
    {
        // Only keep slowest queries for analysis
        $queries = collect($queries)
            ->sortByDesc('time')
            ->take(10)
            ->map(function ($query) {
                return [
                    'sql' => $this->sanitizeSql($query['query']),
                    'time_ms' => $query['time'],
                    'slow' => $query['time'] > 100, // Mark queries over 100ms as slow
                ];
            })
            ->values()
            ->toArray();

        return $queries;
    }

    /**
     * Sanitize SQL query for storage
     */
    private function sanitizeSql(string $sql): string
    {
        // Remove sensitive data patterns
        $sql = preg_replace('/\b\d{4,}\b/', 'XXX', $sql); // Hide long numbers
        $sql = preg_replace('/\'[^\']*\'/', "'XXX'", $sql); // Hide string values

        return substr($sql, 0, 500); // Limit length
    }

    /**
     * Store metrics for analysis
     */
    private function storeMetrics(array $metrics): void
    {
        // Store in cache for real-time monitoring
        $cacheKey = 'performance:' . $metrics['request_id'];
        Cache::put($cacheKey, $metrics, now()->addMinutes(10));

        // Update rolling statistics
        $this->updateRollingStats($metrics);

        // Store in database for long-term analysis (optional)
        if (config('monitoring.store_in_database', false)) {
            $this->storeInDatabase($metrics);
        }

        // Log if response time is concerning
        if ($metrics['performance']['duration_ms'] > $this->thresholds['response_time_warning']) {
            Log::warning('Slow API response detected', [
                'request_id' => $metrics['request_id'],
                'uri' => $metrics['request']['uri'],
                'duration_ms' => $metrics['performance']['duration_ms'],
                'user_id' => $metrics['request']['user_id'],
            ]);
        }
    }

    /**
     * Update rolling statistics
     */
    private function updateRollingStats(array $metrics): void
    {
        $statsKey = 'performance:stats:' . now()->format('Y-m-d-H');
        $stats = Cache::get($statsKey, $this->getEmptyStats());

        // Update counts
        $stats['request_count']++;
        $stats['total_duration'] += $metrics['performance']['duration_ms'];
        $stats['total_memory'] += $metrics['performance']['memory_usage_mb'];

        // Update response time distribution
        $duration = $metrics['performance']['duration_ms'];
        if ($duration < 100) {
            $stats['response_time_distribution']['0-100ms']++;
        } elseif ($duration < 500) {
            $stats['response_time_distribution']['100-500ms']++;
        } elseif ($duration < 1000) {
            $stats['response_time_distribution']['500-1000ms']++;
        } elseif ($duration < 3000) {
            $stats['response_time_distribution']['1-3s']++;
        } else {
            $stats['response_time_distribution']['3s+']++;
        }

        // Update status code distribution
        $statusCode = $metrics['response']['status_code'];
        $statusGroup = substr($statusCode, 0, 1) . 'xx';
        $stats['status_codes'][$statusGroup] = ($stats['status_codes'][$statusGroup] ?? 0) + 1;

        // Track slowest endpoints
        $endpoint = $metrics['request']['route'];
        if (!isset($stats['slowest_endpoints'][$endpoint])) {
            $stats['slowest_endpoints'][$endpoint] = [
                'count' => 0,
                'total_time' => 0,
                'max_time' => 0,
            ];
        }
        $stats['slowest_endpoints'][$endpoint]['count']++;
        $stats['slowest_endpoints'][$endpoint]['total_time'] += $duration;
        $stats['slowest_endpoints'][$endpoint]['max_time'] = max(
            $stats['slowest_endpoints'][$endpoint]['max_time'],
            $duration
        );

        Cache::put($statsKey, $stats, now()->addHours(2));
    }

    /**
     * Get empty stats structure
     */
    private function getEmptyStats(): array
    {
        return [
            'request_count' => 0,
            'total_duration' => 0,
            'total_memory' => 0,
            'response_time_distribution' => [
                '0-100ms' => 0,
                '100-500ms' => 0,
                '500-1000ms' => 0,
                '1-3s' => 0,
                '3s+' => 0,
            ],
            'status_codes' => [
                '2xx' => 0,
                '3xx' => 0,
                '4xx' => 0,
                '5xx' => 0,
            ],
            'slowest_endpoints' => [],
        ];
    }

    /**
     * Check for performance issues
     */
    private function checkPerformanceIssues(array $metrics): void
    {
        $issues = [];

        // Check response time
        if ($metrics['performance']['duration_ms'] > $this->thresholds['response_time_critical']) {
            $issues[] = [
                'type' => 'critical',
                'metric' => 'response_time',
                'value' => $metrics['performance']['duration_ms'],
                'threshold' => $this->thresholds['response_time_critical'],
            ];
        } elseif ($metrics['performance']['duration_ms'] > $this->thresholds['response_time_warning']) {
            $issues[] = [
                'type' => 'warning',
                'metric' => 'response_time',
                'value' => $metrics['performance']['duration_ms'],
                'threshold' => $this->thresholds['response_time_warning'],
            ];
        }

        // Check memory usage
        if ($metrics['performance']['memory_usage_mb'] > $this->thresholds['memory_critical']) {
            $issues[] = [
                'type' => 'critical',
                'metric' => 'memory_usage',
                'value' => $metrics['performance']['memory_usage_mb'],
                'threshold' => $this->thresholds['memory_critical'],
            ];
        } elseif ($metrics['performance']['memory_usage_mb'] > $this->thresholds['memory_warning']) {
            $issues[] = [
                'type' => 'warning',
                'metric' => 'memory_usage',
                'value' => $metrics['performance']['memory_usage_mb'],
                'threshold' => $this->thresholds['memory_warning'],
            ];
        }

        // Check database queries
        if (isset($metrics['database'])) {
            if ($metrics['database']['query_count'] > $this->thresholds['query_count_critical']) {
                $issues[] = [
                    'type' => 'critical',
                    'metric' => 'query_count',
                    'value' => $metrics['database']['query_count'],
                    'threshold' => $this->thresholds['query_count_critical'],
                ];
            }

            if ($metrics['database']['total_time_ms'] > $this->thresholds['query_time_critical']) {
                $issues[] = [
                    'type' => 'critical',
                    'metric' => 'query_time',
                    'value' => $metrics['database']['total_time_ms'],
                    'threshold' => $this->thresholds['query_time_critical'],
                ];
            }
        }

        // Alert if critical issues found
        if (!empty($issues)) {
            $this->alertPerformanceIssues($metrics, $issues);
        }
    }

    /**
     * Alert about performance issues
     */
    private function alertPerformanceIssues(array $metrics, array $issues): void
    {
        $criticalIssues = array_filter($issues, fn($issue) => $issue['type'] === 'critical');

        if (!empty($criticalIssues)) {
            Log::critical('Critical performance issues detected', [
                'request_id' => $metrics['request_id'],
                'uri' => $metrics['request']['uri'],
                'issues' => $criticalIssues,
                'metrics' => $metrics,
            ]);
        } else {
            Log::warning('Performance issues detected', [
                'request_id' => $metrics['request_id'],
                'uri' => $metrics['request']['uri'],
                'issues' => $issues,
            ]);
        }
    }

    /**
     * Add performance headers to response
     */
    private function addPerformanceHeaders(Response $response, array $metrics): void
    {
        $response->headers->set('X-Request-ID', $metrics['request_id']);
        $response->headers->set('X-Response-Time', $metrics['performance']['duration_ms'] . 'ms');
        $response->headers->set('X-Memory-Usage', $metrics['performance']['memory_usage_mb'] . 'MB');

        if (isset($metrics['database'])) {
            $response->headers->set('X-DB-Queries', $metrics['database']['query_count']);
            $response->headers->set('X-DB-Time', $metrics['database']['total_time_ms'] . 'ms');
        }
    }

    /**
     * Store metrics in database (optional)
     */
    private function storeInDatabase(array $metrics): void
    {
        try {
            DB::table('api_performance_metrics')->insert([
                'request_id' => $metrics['request_id'],
                'method' => $metrics['request']['method'],
                'uri' => $metrics['request']['uri'],
                'route' => $metrics['request']['route'],
                'status_code' => $metrics['response']['status_code'],
                'response_size' => $metrics['response']['size'],
                'duration_ms' => $metrics['performance']['duration_ms'],
                'memory_mb' => $metrics['performance']['memory_usage_mb'],
                'query_count' => $metrics['database']['query_count'] ?? 0,
                'query_time_ms' => $metrics['database']['total_time_ms'] ?? 0,
                'user_id' => $metrics['request']['user_id'],
                'ip_address' => $metrics['request']['ip'],
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Don't fail request if metrics storage fails
            Log::error('Failed to store performance metrics', [
                'error' => $e->getMessage(),
                'request_id' => $metrics['request_id'],
            ]);
        }
    }

    /**
     * Get performance statistics
     */
    public static function getStatistics(int $hours = 1): array
    {
        $stats = [];
        $now = now();

        for ($i = 0; $i < $hours; $i++) {
            $hour = $now->copy()->subHours($i);
            $statsKey = 'performance:stats:' . $hour->format('Y-m-d-H');
            $hourStats = Cache::get($statsKey);

            if ($hourStats) {
                $stats[$hour->format('Y-m-d H:00')] = $hourStats;
            }
        }

        return $stats;
    }
}