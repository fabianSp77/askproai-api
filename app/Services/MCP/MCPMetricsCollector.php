<?php

namespace App\Services\MCP;

use App\Models\MCPMetric;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MCPMetricsCollector
{
    protected MCPServiceRegistry $registry;
    protected array $alertRules;

    public function __construct(MCPServiceRegistry $registry)
    {
        $this->registry = $registry;
        $this->alertRules = config('mcp-monitoring.alerts', []);
    }

    /**
     * Record a metric for a service operation
     */
    public function recordMetric(
        string $service,
        string $operation,
        float $responseTime,
        string $status = 'success',
        ?string $errorMessage = null,
        array $metadata = []
    ): void {
        try {
            MCPMetric::create([
                'service' => $service,
                'operation' => $operation,
                'response_time' => $responseTime,
                'status' => $status,
                'error_message' => $errorMessage,
                'metadata' => $metadata,
                'created_at' => now(),
            ]);

            // Update cache metrics
            $this->updateCachedMetrics($service, $operation, $responseTime, $status);
            
            // Check alert rules
            $this->checkAlertRules($service, $operation, $responseTime, $status);
        } catch (\Exception $e) {
            Log::error('Failed to record MCP metric', [
                'service' => $service,
                'operation' => $operation,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get service health status
     */
    public function getServiceHealth(string $service): array
    {
        $cacheKey = "mcp_health:{$service}";
        
        return Cache::remember($cacheKey, 60, function () use ($service) {
            $recentMetrics = MCPMetric::where('service', $service)
                ->where('created_at', '>=', now()->subMinutes(5))
                ->get();

            if ($recentMetrics->isEmpty()) {
                return [
                    'status' => 'unknown',
                    'message' => 'No recent metrics available',
                ];
            }

            $totalRequests = $recentMetrics->count();
            $failedRequests = $recentMetrics->where('status', 'error')->count();
            $avgResponseTime = $recentMetrics->avg('response_time');
            $maxResponseTime = $recentMetrics->max('response_time');

            $errorRate = $totalRequests > 0 ? ($failedRequests / $totalRequests) * 100 : 0;
            $uptime = 100 - $errorRate;

            // Determine health status
            $status = 'healthy';
            if ($errorRate > 50) {
                $status = 'unhealthy';
            } elseif ($errorRate > 10 || $avgResponseTime > 1000) {
                $status = 'degraded';
            }

            // Check circuit breaker status
            $circuitBreaker = $this->getCircuitBreakerStatus($service);

            return [
                'status' => $status,
                'uptime' => round($uptime, 2),
                'error_rate' => round($errorRate, 2),
                'avg_response_time' => round($avgResponseTime, 2),
                'max_response_time' => round($maxResponseTime, 2),
                'total_requests' => $totalRequests,
                'failed_requests' => $failedRequests,
                'circuit_breaker' => $circuitBreaker,
                'last_check' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Get total requests in time range
     */
    public function getTotalRequests(string $timeRange = '1h'): int
    {
        $since = $this->getTimeRangeStart($timeRange);
        
        return MCPMetric::where('created_at', '>=', $since)->count();
    }

    /**
     * Get success rate in time range
     */
    public function getSuccessRate(string $timeRange = '1h'): float
    {
        $since = $this->getTimeRangeStart($timeRange);
        
        $total = MCPMetric::where('created_at', '>=', $since)->count();
        if ($total === 0) {
            return 100.0;
        }
        
        $successful = MCPMetric::where('created_at', '>=', $since)
            ->where('status', 'success')
            ->count();
            
        return round(($successful / $total) * 100, 2);
    }

    /**
     * Get average response time in time range
     */
    public function getAverageResponseTime(string $timeRange = '1h'): float
    {
        $since = $this->getTimeRangeStart($timeRange);
        
        $avg = MCPMetric::where('created_at', '>=', $since)
            ->avg('response_time');
            
        return round($avg ?? 0, 2);
    }

    /**
     * Get cache hit rate
     */
    public function getCacheHitRate(string $timeRange = '1h'): float
    {
        $cacheKey = "mcp_cache_stats:{$timeRange}";
        
        return Cache::remember($cacheKey, 300, function () use ($timeRange) {
            $since = $this->getTimeRangeStart($timeRange);
            
            $total = MCPMetric::where('created_at', '>=', $since)
                ->whereJsonContains('metadata->cache_checked', true)
                ->count();
                
            if ($total === 0) {
                return 0.0;
            }
            
            $hits = MCPMetric::where('created_at', '>=', $since)
                ->whereJsonContains('metadata->cache_hit', true)
                ->count();
                
            return round(($hits / $total) * 100, 2);
        });
    }

    /**
     * Get active circuit breakers
     */
    public function getActiveCircuitBreakers(): array
    {
        $activeBreakers = [];
        
        foreach ($this->registry->getAllServices() as $serviceName => $service) {
            $status = $this->getCircuitBreakerStatus($serviceName);
            if ($status !== 'closed') {
                $activeBreakers[] = [
                    'service' => $serviceName,
                    'status' => $status,
                    'since' => Cache::get("circuit_breaker:{$serviceName}:since"),
                ];
            }
        }
        
        return $activeBreakers;
    }

    /**
     * Get active alerts
     */
    public function getActiveAlerts(): array
    {
        $alerts = [];
        
        foreach ($this->alertRules as $rule) {
            // If service is null, evaluate for all services
            if (!isset($rule['service']) || $rule['service'] === null) {
                // Get all services from registry
                foreach ($this->registry->getAllServices() as $serviceName => $service) {
                    $ruleForService = $rule;
                    $ruleForService['service'] = $serviceName;
                    $alert = $this->evaluateAlertRule($ruleForService);
                    if ($alert !== null) {
                        $alerts[] = $alert;
                    }
                }
            } else {
                // Evaluate for specific service
                $alert = $this->evaluateAlertRule($rule);
                if ($alert !== null) {
                    $alerts[] = $alert;
                }
            }
        }
        
        return $alerts;
    }

    /**
     * Get Prometheus formatted metrics
     */
    public function getPrometheusMetrics(): string
    {
        $metrics = [];
        
        // System metrics
        $metrics[] = "# HELP mcp_requests_total Total number of MCP requests";
        $metrics[] = "# TYPE mcp_requests_total counter";
        
        foreach ($this->registry->getAllServices() as $serviceName => $service) {
            $total = MCPMetric::where('service', $serviceName)
                ->where('created_at', '>=', now()->subHour())
                ->count();
            $metrics[] = "mcp_requests_total{service=\"{$serviceName}\"} {$total}";
        }
        
        // Response time metrics
        $metrics[] = "";
        $metrics[] = "# HELP mcp_response_time_seconds Response time in seconds";
        $metrics[] = "# TYPE mcp_response_time_seconds histogram";
        
        foreach ($this->registry->getAllServices() as $serviceName => $service) {
            $responseTimes = MCPMetric::where('service', $serviceName)
                ->where('created_at', '>=', now()->subHour())
                ->pluck('response_time');
                
            if ($responseTimes->isNotEmpty()) {
                $avg = $responseTimes->avg() / 1000; // Convert to seconds
                $metrics[] = "mcp_response_time_seconds{service=\"{$serviceName}\",quantile=\"0.5\"} {$avg}";
            }
        }
        
        // Error rate metrics
        $metrics[] = "";
        $metrics[] = "# HELP mcp_errors_total Total number of errors";
        $metrics[] = "# TYPE mcp_errors_total counter";
        
        foreach ($this->registry->getAllServices() as $serviceName => $service) {
            $errors = MCPMetric::where('service', $serviceName)
                ->where('status', 'error')
                ->where('created_at', '>=', now()->subHour())
                ->count();
            $metrics[] = "mcp_errors_total{service=\"{$serviceName}\"} {$errors}";
        }
        
        return implode("\n", $metrics) . "\n";
    }

    /**
     * Get service metrics for a specific time range
     */
    public function getServiceMetrics(string $service, string $timeRange = '1h'): array
    {
        $since = $this->getTimeRangeStart($timeRange);
        
        $metrics = MCPMetric::where('service', $service)
            ->where('created_at', '>=', $since)
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m-%d %H:00:00") as hour'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as successful'),
                DB::raw('AVG(response_time) as avg_response_time'),
                DB::raw('MAX(response_time) as max_response_time'),
                DB::raw('MIN(response_time) as min_response_time')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        return [
            'service' => $service,
            'time_range' => $timeRange,
            'metrics' => $metrics->map(function ($metric) {
                return [
                    'timestamp' => $metric->hour,
                    'total_requests' => $metric->total,
                    'successful_requests' => $metric->successful,
                    'error_rate' => $metric->total > 0 
                        ? round((($metric->total - $metric->successful) / $metric->total) * 100, 2)
                        : 0,
                    'response_times' => [
                        'avg' => round($metric->avg_response_time, 2),
                        'min' => round($metric->min_response_time, 2),
                        'max' => round($metric->max_response_time, 2),
                    ],
                ];
            }),
            'summary' => [
                'total_requests' => $metrics->sum('total'),
                'successful_requests' => $metrics->sum('successful'),
                'overall_success_rate' => $metrics->sum('total') > 0
                    ? round(($metrics->sum('successful') / $metrics->sum('total')) * 100, 2)
                    : 100,
                'avg_response_time' => round($metrics->avg('avg_response_time'), 2),
            ],
        ];
    }

    protected function updateCachedMetrics(
        string $service,
        string $operation,
        float $responseTime,
        string $status
    ): void {
        $cacheKey = "mcp_metrics:{$service}";
        $metrics = Cache::get($cacheKey, []);
        
        if (!isset($metrics[$operation])) {
            $metrics[$operation] = [
                'total' => 0,
                'success' => 0,
                'error' => 0,
                'total_time' => 0,
            ];
        }
        
        $metrics[$operation]['total']++;
        $metrics[$operation][$status]++;
        $metrics[$operation]['total_time'] += $responseTime;
        
        Cache::put($cacheKey, $metrics, 3600);
    }

    protected function checkAlertRules(
        string $service,
        string $operation,
        float $responseTime,
        string $status
    ): void {
        foreach ($this->alertRules as $rule) {
            if ($this->shouldTriggerAlert($rule, $service, $operation, $responseTime, $status)) {
                $this->triggerAlert($rule, $service, $operation);
            }
        }
    }

    protected function shouldTriggerAlert(
        array $rule,
        string $service,
        string $operation,
        float $responseTime,
        string $status
    ): bool {
        // Check if rule applies to this service
        if (isset($rule['service']) && $rule['service'] !== $service) {
            return false;
        }
        
        // Check conditions
        if (isset($rule['condition'])) {
            switch ($rule['condition']['type']) {
                case 'error_rate':
                    $errorRate = $this->calculateRecentErrorRate($service, $rule['condition']['window'] ?? 300);
                    return $errorRate > ($rule['condition']['threshold'] ?? 10);
                    
                case 'response_time':
                    return $responseTime > ($rule['condition']['threshold'] ?? 1000);
                    
                case 'consecutive_errors':
                    return $status === 'error' && 
                           $this->getConsecutiveErrors($service) >= ($rule['condition']['count'] ?? 5);
            }
        }
        
        return false;
    }

    protected function triggerAlert(array $rule, string $service, string $operation): void
    {
        $alertKey = "alert:{$service}:{$rule['name']}";
        
        // Prevent alert spam
        if (Cache::has($alertKey)) {
            return;
        }
        
        Cache::put($alertKey, true, $rule['cooldown'] ?? 300);
        
        Log::warning('MCP Alert Triggered', [
            'rule' => $rule['name'],
            'service' => $service,
            'operation' => $operation,
            'severity' => $rule['severity'] ?? 'warning',
            'message' => $rule['message'] ?? 'Alert condition met',
        ]);
        
        // Dispatch alert notification if configured
        if (isset($rule['notify'])) {
            event(new \App\Events\MCPAlertTriggered($rule, $service, $operation));
        }
    }

    protected function calculateRecentErrorRate(string $service, int $seconds): float
    {
        $since = now()->subSeconds($seconds);
        
        $total = MCPMetric::where('service', $service)
            ->where('created_at', '>=', $since)
            ->count();
            
        if ($total === 0) {
            return 0;
        }
        
        $errors = MCPMetric::where('service', $service)
            ->where('created_at', '>=', $since)
            ->where('status', 'error')
            ->count();
            
        return ($errors / $total) * 100;
    }

    protected function getConsecutiveErrors(string $service): int
    {
        $recent = MCPMetric::where('service', $service)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->pluck('status');
            
        $consecutive = 0;
        foreach ($recent as $status) {
            if ($status === 'error') {
                $consecutive++;
            } else {
                break;
            }
        }
        
        return $consecutive;
    }

    protected function getCircuitBreakerStatus(string $service): string
    {
        $cacheKey = "circuit_breaker:{$service}:status";
        return Cache::get($cacheKey, 'closed');
    }

    protected function getTimeRangeStart(string $range): Carbon
    {
        return match ($range) {
            '5m' => now()->subMinutes(5),
            '15m' => now()->subMinutes(15),
            '30m' => now()->subMinutes(30),
            '1h' => now()->subHour(),
            '6h' => now()->subHours(6),
            '24h' => now()->subDay(),
            '7d' => now()->subWeek(),
            '30d' => now()->subMonth(),
            default => now()->subHour(),
        };
    }

    protected function evaluateAlertRule(array $rule): ?array
    {
        try {
            $service = $rule['service'] ?? null;
            $condition = $rule['condition'] ?? [];
            
            $triggered = false;
            $value = null;
            
            switch ($condition['type'] ?? null) {
                case 'error_rate':
                    if ($service === null) {
                        Log::warning('MCP: Cannot evaluate error_rate without service', [
                            'rule' => $rule['name'] ?? 'Unknown'
                        ]);
                        return null;
                    }
                    $value = $this->calculateRecentErrorRate(
                        $service,
                        $condition['window'] ?? 300
                    );
                    $triggered = $value > ($condition['threshold'] ?? 10);
                    break;
                    
                case 'response_time':
                    if ($service === null) {
                        Log::warning('MCP: Cannot evaluate response_time without service', [
                            'rule' => $rule['name'] ?? 'Unknown'
                        ]);
                        return null;
                    }
                    $value = MCPMetric::where('service', $service)
                        ->where('created_at', '>=', now()->subMinutes(5))
                        ->avg('response_time');
                    $triggered = $value > ($condition['threshold'] ?? 1000);
                    break;
                    
                case 'service_down':
                    if ($service === null) {
                        Log::warning('MCP: Cannot evaluate service_down without service', [
                            'rule' => $rule['name'] ?? 'Unknown'
                        ]);
                        return null;
                    }
                    $lastMetric = MCPMetric::where('service', $service)
                        ->latest()
                        ->first();
                    $triggered = !$lastMetric || 
                                $lastMetric->created_at < now()->subMinutes($condition['minutes'] ?? 5);
                    break;
                    
                case 'cache_miss_rate':
                    if ($service === null) {
                        // For cache miss rate, we can default to 'cache' service
                        $service = 'cache';
                    }
                    $value = 100 - $this->getCacheHitRate($condition['window'] ?? '10m');
                    $triggered = $value > ($condition['threshold'] ?? 50);
                    break;
                    
                case 'queue_size':
                    if ($service === null) {
                        // For queue size, we can default to 'queue' service
                        $service = 'queue';
                    }
                    // TODO: Implement queue size monitoring
                    $value = 0;
                    $triggered = false;
                    break;
                    
                default:
                    Log::warning('MCP: Unknown alert condition type', [
                        'type' => $condition['type'] ?? 'null',
                        'rule' => $rule['name'] ?? 'Unknown'
                    ]);
                    return null;
            }
            
            if ($triggered) {
                return [
                    'rule' => $rule['name'] ?? 'Unknown',
                    'service' => $service,
                    'severity' => $rule['severity'] ?? 'warning',
                    'message' => $rule['message'] ?? 'Alert condition met',
                    'value' => $value,
                    'threshold' => $condition['threshold'] ?? null,
                    'triggered_at' => now()->toIso8601String(),
                ];
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('Failed to evaluate alert rule', [
                'rule' => $rule,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}