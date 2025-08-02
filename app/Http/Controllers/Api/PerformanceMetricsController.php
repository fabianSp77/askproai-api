<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PerformanceMonitoringService;
use App\Services\DatabaseOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class PerformanceMetricsController extends Controller
{
    protected $performanceMonitor;
    protected $databaseOptimizer;

    public function __construct(
        PerformanceMonitoringService $performanceMonitor,
        DatabaseOptimizationService $databaseOptimizer
    ) {
        $this->performanceMonitor = $performanceMonitor;
        $this->databaseOptimizer = $databaseOptimizer;
    }

    /**
     * Get current performance metrics.
     */
    public function index(Request $request)
    {
        $period = $request->get('period', 'last_5_minutes');
        
        // Get performance stats
        $stats = $this->performanceMonitor->getStats($period);
        
        // Get real-time metrics
        $realTimeMetrics = $this->performanceMonitor->getRealTimeMetrics();
        
        // Get system health
        $systemHealth = $this->getSystemHealth();
        
        // Get recommendations
        $analysis = $this->performanceMonitor->analyze();
        
        // Format metrics for dashboard
        $metrics = [
            'responseTime' => [
                'current' => $realTimeMetrics['request:api/dashboard']['last_value'] ?? 0,
                'avg' => $stats['requests']['avg_response_time'] ?? 0,
                'p95' => $stats['requests']['p95_response_time'] ?? 0,
                'p99' => $stats['requests']['p99_response_time'] ?? 0,
            ],
            'throughput' => [
                'current' => $stats['requests']['requests_per_second'] ?? 0,
                'total' => $stats['requests']['total_requests'] ?? 0,
                'trend' => $this->getThroughputTrend($period),
            ],
            'errorRate' => [
                'current' => $stats['errors']['error_rate'] ?? 0,
                'total' => $stats['errors']['total_errors'] ?? 0,
                'types' => $stats['errors']['error_types'] ?? [],
            ],
            'database' => [
                'queries' => $stats['database']['active_queries'] ?? 0,
                'slowQueries' => $stats['database']['slow_queries'] ?? 0,
                'connections' => $systemHealth['database']['connections'] ?? 0,
            ],
            'cache' => [
                'hitRate' => $stats['cache']['hit_rate'] ?? 0,
                'misses' => $stats['cache']['total_misses'] ?? 0,
                'memory' => $systemHealth['cache']['memory_usage'] ?? 0,
            ],
            'system' => [
                'cpu' => $systemHealth['cpu_usage'] ?? 0,
                'memory' => $systemHealth['memory_usage'] ?? 0,
                'disk' => $systemHealth['disk_usage'] ?? 0,
            ],
        ];
        
        // Get active alerts
        $alerts = $this->getActiveAlerts();
        
        // Format recommendations
        $recommendations = $this->formatRecommendations($analysis['recommendations']);
        
        return response()->json([
            'metrics' => $metrics,
            'recommendations' => $recommendations,
            'alerts' => $alerts,
            'score' => $analysis['score'],
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get performance trends for a specific metric.
     */
    public function trends(Request $request)
    {
        $metric = $request->get('metric', 'response_time');
        $period = $request->get('period', 'last_hour');
        $resolution = $request->get('resolution', 60);
        
        $trends = $this->performanceMonitor->getTrends($metric, $period, $resolution);
        
        return response()->json([
            'metric' => $metric,
            'period' => $period,
            'data' => $trends,
        ]);
    }

    /**
     * Get database optimization recommendations.
     */
    public function databaseAnalysis()
    {
        // Start monitoring
        $this->databaseOptimizer->startMonitoring();
        
        // Run some sample queries to analyze
        $this->runSampleQueries();
        
        // Stop monitoring and get analysis
        $analysis = $this->databaseOptimizer->stopMonitoring();
        
        // Get database health
        $health = $this->databaseOptimizer->analyzeDatabaseHealth();
        
        return response()->json([
            'analysis' => $analysis,
            'health' => $health,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Trigger performance optimization.
     */
    public function optimize(Request $request)
    {
        $type = $request->get('type', 'all');
        $results = [];
        
        switch ($type) {
            case 'cache':
                $results['cache'] = $this->optimizeCache();
                break;
                
            case 'database':
                $results['database'] = $this->optimizeDatabase();
                break;
                
            case 'queries':
                $results['queries'] = $this->optimizeQueries();
                break;
                
            case 'all':
            default:
                $results['cache'] = $this->optimizeCache();
                $results['database'] = $this->optimizeDatabase();
                $results['queries'] = $this->optimizeQueries();
                break;
        }
        
        return response()->json([
            'type' => $type,
            'results' => $results,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Clear performance data and reset metrics.
     */
    public function reset()
    {
        // Clear Redis metrics
        if (Redis::connection()) {
            $keys = Redis::keys('metrics:*');
            foreach ($keys as $key) {
                Redis::del($key);
            }
            
            $keys = Redis::keys('realtime:*');
            foreach ($keys as $key) {
                Redis::del($key);
            }
        }
        
        // Clear cache
        Cache::tags(['performance'])->flush();
        
        return response()->json([
            'message' => 'Performance metrics reset successfully',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get system health metrics.
     */
    protected function getSystemHealth(): array
    {
        $health = [
            'cpu_usage' => 0,
            'memory_usage' => 0,
            'disk_usage' => 0,
            'database' => [
                'connections' => 0,
                'status' => 'healthy',
            ],
            'cache' => [
                'memory_usage' => 0,
                'status' => 'healthy',
            ],
        ];
        
        // Get CPU usage
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            $cpuCount = $this->getCpuCount();
            $health['cpu_usage'] = round(($load[0] / $cpuCount) * 100, 2);
        }
        
        // Get memory usage
        $memInfo = $this->getMemoryInfo();
        if ($memInfo['total'] > 0) {
            $health['memory_usage'] = round((($memInfo['total'] - $memInfo['available']) / $memInfo['total']) * 100, 2);
        }
        
        // Get disk usage
        $diskTotal = disk_total_space('/');
        $diskFree = disk_free_space('/');
        if ($diskTotal > 0) {
            $health['disk_usage'] = round((($diskTotal - $diskFree) / $diskTotal) * 100, 2);
        }
        
        // Get database connections
        try {
            $connections = DB::select('SHOW PROCESSLIST');
            $maxConnections = DB::select('SHOW VARIABLES LIKE "max_connections"')[0]->Value ?? 100;
            $health['database']['connections'] = round((count($connections) / $maxConnections) * 100, 2);
        } catch (\Exception $e) {
            $health['database']['status'] = 'error';
        }
        
        // Get Redis memory usage
        if (Redis::connection()) {
            try {
                $info = Redis::info('memory');
                $usedMemory = $info['used_memory'] ?? 0;
                $maxMemory = config('database.redis.options.maxmemory', 512 * 1024 * 1024); // Default 512MB
                $health['cache']['memory_usage'] = round(($usedMemory / $maxMemory) * 100, 2);
            } catch (\Exception $e) {
                $health['cache']['status'] = 'error';
            }
        }
        
        return $health;
    }

    /**
     * Get throughput trend data.
     */
    protected function getThroughputTrend(string $period): array
    {
        $trend = [];
        $now = now();
        $minutes = $this->getPeriodMinutes($period);
        
        for ($i = $minutes; $i >= 0; $i -= 5) {
            $time = $now->copy()->subMinutes($i);
            $key = 'metrics:http.request.count:' . $time->format('YmdH');
            
            $count = 0;
            if (Redis::connection()) {
                $count = Redis::zcount($key, $time->timestamp - 300, $time->timestamp);
            }
            
            $trend[] = [
                'time' => $time->format('H:i'),
                'value' => round($count / 300, 2), // Requests per second
            ];
        }
        
        return $trend;
    }

    /**
     * Get active alerts.
     */
    protected function getActiveAlerts(): array
    {
        $alerts = [];
        
        // Check response time
        $avgResponseTime = Cache::get('metrics:avg_response_time', 0);
        if ($avgResponseTime > 1000) {
            $alerts[] = [
                'type' => 'warning',
                'message' => 'High Response Time',
                'description' => "Average response time is {$avgResponseTime}ms (threshold: 1000ms)",
                'action' => 'Optimize',
                'timestamp' => now()->toIso8601String(),
            ];
        }
        
        // Check error rate
        $errorRate = Cache::get('metrics:error_rate', 0);
        if ($errorRate > 0.05) {
            $alerts[] = [
                'type' => 'error',
                'message' => 'High Error Rate',
                'description' => "Error rate is " . round($errorRate * 100, 2) . "% (threshold: 5%)",
                'action' => 'Investigate',
                'timestamp' => now()->toIso8601String(),
            ];
        }
        
        // Check memory usage
        $memoryUsage = $this->getMemoryUsagePercentage();
        if ($memoryUsage > 80) {
            $alerts[] = [
                'type' => 'warning',
                'message' => 'High Memory Usage',
                'description' => "Memory usage is {$memoryUsage}% (threshold: 80%)",
                'action' => 'Optimize',
                'timestamp' => now()->toIso8601String(),
            ];
        }
        
        return $alerts;
    }

    /**
     * Format recommendations for display.
     */
    protected function formatRecommendations(array $recommendations): array
    {
        $formatted = [];
        
        foreach ($recommendations as $recommendation) {
            if (is_string($recommendation)) {
                $formatted[] = [
                    'type' => 'general',
                    'issue' => 'Performance',
                    'recommendation' => $recommendation,
                    'impact' => 50,
                ];
            } else {
                $formatted[] = [
                    'type' => $recommendation['priority'] ?? 'medium',
                    'issue' => $recommendation['issue'] ?? 'Unknown',
                    'recommendation' => $recommendation['suggestion'] ?? $recommendation['sql'] ?? 'N/A',
                    'impact' => $this->calculateImpact($recommendation),
                ];
            }
        }
        
        // Sort by impact
        usort($formatted, function ($a, $b) {
            return $b['impact'] - $a['impact'];
        });
        
        return array_slice($formatted, 0, 10); // Top 10 recommendations
    }

    /**
     * Calculate recommendation impact.
     */
    protected function calculateImpact(array $recommendation): int
    {
        $impact = 50; // Default
        
        if (isset($recommendation['priority'])) {
            switch ($recommendation['priority']) {
                case 'critical':
                case 'high':
                    $impact = 90;
                    break;
                case 'medium':
                    $impact = 60;
                    break;
                case 'low':
                    $impact = 30;
                    break;
            }
        }
        
        if (isset($recommendation['type'])) {
            if ($recommendation['type'] === 'index') {
                $impact = max($impact, 80);
            } elseif ($recommendation['type'] === 'n_plus_one') {
                $impact = max($impact, 85);
            }
        }
        
        return $impact;
    }

    /**
     * Run sample queries for analysis.
     */
    protected function runSampleQueries(): void
    {
        // Run some typical queries to analyze
        try {
            DB::table('appointments')
                ->where('company_id', 1)
                ->where('starts_at', '>=', now())
                ->limit(50)
                ->get();
            
            DB::table('customers')
                ->where('company_id', 1)
                ->where('first_name', 'like', '%john%')
                ->limit(20)
                ->get();
            
            DB::table('calls')
                ->join('customers', 'calls.customer_id', '=', 'customers.id')
                ->where('calls.company_id', 1)
                ->orderBy('calls.created_at', 'desc')
                ->limit(100)
                ->get();
        } catch (\Exception $e) {
            // Ignore errors during analysis
        }
    }

    /**
     * Optimize cache performance.
     */
    protected function optimizeCache(): array
    {
        $results = [
            'cleared' => 0,
            'optimized' => 0,
            'saved_memory' => 0,
        ];
        
        // Clear expired cache entries
        $results['cleared'] = Cache::store('redis')->flush() ? 1 : 0;
        
        // Warm up critical caches
        $this->warmUpCache();
        $results['optimized']++;
        
        // Get memory saved
        if (Redis::connection()) {
            $before = Redis::info('memory')['used_memory'] ?? 0;
            Redis::bgrewriteaof();
            sleep(1);
            $after = Redis::info('memory')['used_memory'] ?? 0;
            $results['saved_memory'] = max(0, $before - $after);
        }
        
        return $results;
    }

    /**
     * Optimize database performance.
     */
    protected function optimizeDatabase(): array
    {
        $results = [
            'tables_optimized' => 0,
            'indexes_created' => 0,
            'queries_analyzed' => 0,
        ];
        
        // Analyze tables
        $tables = DB::select('SHOW TABLES');
        $dbName = DB::getDatabaseName();
        
        foreach ($tables as $table) {
            $tableName = $table->{"Tables_in_{$dbName}"};
            
            // Optimize table
            try {
                DB::statement("OPTIMIZE TABLE {$tableName}");
                $results['tables_optimized']++;
            } catch (\Exception $e) {
                // Continue with next table
            }
        }
        
        return $results;
    }

    /**
     * Optimize slow queries.
     */
    protected function optimizeQueries(): array
    {
        $results = [
            'queries_analyzed' => 0,
            'indexes_suggested' => 0,
            'queries_rewritten' => 0,
        ];
        
        // Get slow queries from log
        $slowQueries = DB::select('SELECT * FROM mysql.slow_log ORDER BY query_time DESC LIMIT 10');
        
        foreach ($slowQueries as $query) {
            $analysis = $this->databaseOptimizer->optimizeQuery($query->sql_text);
            
            $results['queries_analyzed']++;
            
            if (!empty($analysis['suggestions'])) {
                foreach ($analysis['suggestions'] as $suggestion) {
                    if ($suggestion['type'] === 'add_indexes') {
                        $results['indexes_suggested'] += count($suggestion['indexes']);
                    } elseif ($suggestion['type'] === 'rewrite') {
                        $results['queries_rewritten']++;
                    }
                }
            }
        }
        
        return $results;
    }

    /**
     * Warm up critical caches.
     */
    protected function warmUpCache(): void
    {
        // Cache company settings
        $companies = DB::table('companies')->where('is_active', true)->get();
        foreach ($companies as $company) {
            Cache::remember("company:{$company->id}:settings", 7200, function () use ($company) {
                return $company;
            });
        }
        
        // Cache branch data
        $branches = DB::table('branches')->where('is_active', true)->get();
        foreach ($branches as $branch) {
            Cache::remember("branch:{$branch->id}:data", 3600, function () use ($branch) {
                return $branch;
            });
        }
    }

    /**
     * Helper methods
     */
    protected function getCpuCount(): int
    {
        if (is_file('/proc/cpuinfo')) {
            $cpuinfo = file_get_contents('/proc/cpuinfo');
            preg_match_all('/^processor/m', $cpuinfo, $matches);
            return count($matches[0]);
        }
        
        return 1; // Default
    }

    protected function getMemoryInfo(): array
    {
        $info = [
            'total' => 0,
            'available' => 0,
        ];
        
        if (is_file('/proc/meminfo')) {
            $meminfo = file_get_contents('/proc/meminfo');
            
            if (preg_match('/MemTotal:\s+(\d+)/', $meminfo, $matches)) {
                $info['total'] = $matches[1] * 1024; // Convert to bytes
            }
            
            if (preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $matches)) {
                $info['available'] = $matches[1] * 1024;
            } elseif (preg_match('/MemFree:\s+(\d+)/', $meminfo, $matches)) {
                $info['available'] = $matches[1] * 1024;
            }
        }
        
        return $info;
    }

    protected function getMemoryUsagePercentage(): float
    {
        $memInfo = $this->getMemoryInfo();
        if ($memInfo['total'] > 0) {
            return round((($memInfo['total'] - $memInfo['available']) / $memInfo['total']) * 100, 2);
        }
        return 0;
    }

    protected function getPeriodMinutes(string $period): int
    {
        switch ($period) {
            case 'last_5_minutes': return 5;
            case 'last_15_minutes': return 15;
            case 'last_hour': return 60;
            case 'last_6_hours': return 360;
            case 'last_day': return 1440;
            default: return 60;
        }
    }
}