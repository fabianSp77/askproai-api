<?php

namespace App\Services\MCP;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Http;
use App\Models\SystemMetric;
use App\Models\ErrorLog;
use Carbon\Carbon;

class MonitoringMCPServer extends BaseMCPServer
{
    protected string $name = 'monitoring';
    protected string $version = '1.0.0';
    protected string $description = 'Comprehensive system monitoring and health checks';
    
    public function getTools(): array
    {
        return [
            [
                'name' => 'getSystemHealth',
                'description' => 'Get overall system health status',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'include_details' => ['type' => 'boolean', 'description' => 'Include detailed component status'],
                        'check_external' => ['type' => 'boolean', 'description' => 'Check external service health']
                    ]
                ]
            ],
            [
                'name' => 'getPerformanceMetrics',
                'description' => 'Get system performance metrics',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'metric_types' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'cpu, memory, disk, network, database, cache, queue'
                        ],
                        'time_range' => ['type' => 'string', 'description' => '1h, 6h, 24h, 7d, 30d'],
                        'aggregation' => ['type' => 'string', 'enum' => ['min', 'avg', 'max']]
                    ]
                ]
            ],
            [
                'name' => 'monitorApiEndpoints',
                'description' => 'Monitor API endpoint performance and availability',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'endpoints' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'include_response_times' => ['type' => 'boolean'],
                        'include_error_rates' => ['type' => 'boolean'],
                        'time_range' => ['type' => 'string']
                    ]
                ]
            ],
            [
                'name' => 'getErrorLogs',
                'description' => 'Retrieve and analyze error logs',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'severity' => ['type' => 'string', 'enum' => ['debug', 'info', 'warning', 'error', 'critical']],
                        'time_range' => ['type' => 'string'],
                        'pattern' => ['type' => 'string', 'description' => 'Search pattern'],
                        'group_by' => ['type' => 'string', 'enum' => ['type', 'endpoint', 'user', 'time']],
                        'limit' => ['type' => 'integer']
                    ]
                ]
            ],
            [
                'name' => 'monitorDatabasePerformance',
                'description' => 'Monitor database performance and query statistics',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'include_slow_queries' => ['type' => 'boolean'],
                        'include_table_stats' => ['type' => 'boolean'],
                        'include_connection_stats' => ['type' => 'boolean'],
                        'slow_query_threshold' => ['type' => 'number', 'description' => 'Threshold in seconds']
                    ]
                ]
            ],
            [
                'name' => 'monitorQueueHealth',
                'description' => 'Monitor queue health and job processing',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'queues' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'include_failed_jobs' => ['type' => 'boolean'],
                        'include_processing_times' => ['type' => 'boolean']
                    ]
                ]
            ],
            [
                'name' => 'setAlert',
                'description' => 'Configure monitoring alerts',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string'],
                        'metric' => ['type' => 'string'],
                        'condition' => ['type' => 'string', 'enum' => ['gt', 'lt', 'eq', 'gte', 'lte']],
                        'threshold' => ['type' => 'number'],
                        'duration' => ['type' => 'integer', 'description' => 'Duration in minutes'],
                        'actions' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'enabled' => ['type' => 'boolean']
                    ],
                    'required' => ['name', 'metric', 'condition', 'threshold']
                ]
            ],
            [
                'name' => 'generateHealthReport',
                'description' => 'Generate comprehensive health report',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'report_type' => ['type' => 'string', 'enum' => ['summary', 'detailed', 'executive']],
                        'include_recommendations' => ['type' => 'boolean'],
                        'format' => ['type' => 'string', 'enum' => ['json', 'html', 'pdf']]
                    ]
                ]
            ]
        ];
    }
    
    public function executeTool(string $name, array $arguments): array
    {
        return match ($name) {
            'getSystemHealth' => $this->getSystemHealth($arguments),
            'getPerformanceMetrics' => $this->getPerformanceMetrics($arguments),
            'monitorApiEndpoints' => $this->monitorApiEndpoints($arguments),
            'getErrorLogs' => $this->getErrorLogs($arguments),
            'monitorDatabasePerformance' => $this->monitorDatabasePerformance($arguments),
            'monitorQueueHealth' => $this->monitorQueueHealth($arguments),
            'setAlert' => $this->setAlert($arguments),
            'generateHealthReport' => $this->generateHealthReport($arguments),
            default => ['error' => "Unknown tool: {$name}"]
        };
    }
    
    protected function getSystemHealth(array $args): array
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'components' => []
        ];
        
        // Database health
        $dbHealth = $this->checkDatabaseHealth();
        $health['components']['database'] = $dbHealth;
        if ($dbHealth['status'] !== 'healthy') {
            $health['status'] = 'degraded';
        }
        
        // Cache health
        $cacheHealth = $this->checkCacheHealth();
        $health['components']['cache'] = $cacheHealth;
        if ($cacheHealth['status'] !== 'healthy') {
            $health['status'] = 'degraded';
        }
        
        // Queue health
        $queueHealth = $this->checkQueueHealth();
        $health['components']['queue'] = $queueHealth;
        if ($queueHealth['status'] !== 'healthy') {
            $health['status'] = 'degraded';
        }
        
        // Filesystem health
        $filesystemHealth = $this->checkFilesystemHealth();
        $health['components']['filesystem'] = $filesystemHealth;
        if ($filesystemHealth['status'] === 'critical') {
            $health['status'] = 'unhealthy';
        }
        
        // External services (if requested)
        if ($args['check_external'] ?? false) {
            $externalHealth = $this->checkExternalServices();
            $health['components']['external_services'] = $externalHealth;
            
            foreach ($externalHealth as $service) {
                if ($service['status'] === 'critical') {
                    $health['status'] = 'degraded';
                }
            }
        }
        
        // Memory and CPU
        $resourceHealth = $this->checkResourceHealth();
        $health['components']['resources'] = $resourceHealth;
        if ($resourceHealth['status'] === 'critical') {
            $health['status'] = 'unhealthy';
        }
        
        // Calculate overall health score
        $healthScore = $this->calculateHealthScore($health['components']);
        $health['health_score'] = $healthScore;
        
        // Add details if requested
        if ($args['include_details'] ?? false) {
            $health['details'] = $this->getHealthDetails();
        }
        
        // Store health check result
        $this->storeHealthCheck($health);
        
        return $health;
    }
    
    protected function getPerformanceMetrics(array $args): array
    {
        $metricTypes = $args['metric_types'] ?? ['cpu', 'memory', 'database', 'cache'];
        $timeRange = $args['time_range'] ?? '1h';
        $aggregation = $args['aggregation'] ?? 'avg';
        
        $metrics = [];
        
        foreach ($metricTypes as $type) {
            switch ($type) {
                case 'cpu':
                    $metrics['cpu'] = $this->getCpuMetrics($timeRange, $aggregation);
                    break;
                    
                case 'memory':
                    $metrics['memory'] = $this->getMemoryMetrics($timeRange, $aggregation);
                    break;
                    
                case 'disk':
                    $metrics['disk'] = $this->getDiskMetrics($timeRange, $aggregation);
                    break;
                    
                case 'network':
                    $metrics['network'] = $this->getNetworkMetrics($timeRange, $aggregation);
                    break;
                    
                case 'database':
                    $metrics['database'] = $this->getDatabaseMetrics($timeRange, $aggregation);
                    break;
                    
                case 'cache':
                    $metrics['cache'] = $this->getCacheMetrics($timeRange, $aggregation);
                    break;
                    
                case 'queue':
                    $metrics['queue'] = $this->getQueueMetrics($timeRange, $aggregation);
                    break;
            }
        }
        
        // Add trends
        $metrics['trends'] = $this->calculateMetricTrends($metrics, $timeRange);
        
        // Add alerts if any metrics exceed thresholds
        $metrics['alerts'] = $this->checkMetricAlerts($metrics);
        
        return $metrics;
    }
    
    protected function monitorApiEndpoints(array $args): array
    {
        $endpoints = $args['endpoints'] ?? $this->getMonitoredEndpoints();
        $timeRange = $args['time_range'] ?? '1h';
        
        $monitoring = [
            'timestamp' => now()->toIso8601String(),
            'endpoints' => []
        ];
        
        foreach ($endpoints as $endpoint) {
            $endpointStats = $this->getEndpointStats($endpoint, $timeRange);
            
            $monitoring['endpoints'][$endpoint] = [
                'status' => $endpointStats['availability'] > 99 ? 'healthy' : 'degraded',
                'availability' => $endpointStats['availability'],
                'total_requests' => $endpointStats['total_requests'],
                'success_rate' => $endpointStats['success_rate']
            ];
            
            if ($args['include_response_times'] ?? true) {
                $monitoring['endpoints'][$endpoint]['response_times'] = [
                    'p50' => $endpointStats['p50_response_time'],
                    'p95' => $endpointStats['p95_response_time'],
                    'p99' => $endpointStats['p99_response_time'],
                    'avg' => $endpointStats['avg_response_time']
                ];
            }
            
            if ($args['include_error_rates'] ?? true) {
                $monitoring['endpoints'][$endpoint]['errors'] = [
                    'rate' => $endpointStats['error_rate'],
                    'types' => $endpointStats['error_types'],
                    'recent' => $endpointStats['recent_errors']
                ];
            }
        }
        
        // Overall API health
        $monitoring['overall'] = $this->calculateOverallApiHealth($monitoring['endpoints']);
        
        return $monitoring;
    }
    
    protected function getErrorLogs(array $args): array
    {
        $severity = $args['severity'] ?? null;
        $timeRange = $args['time_range'] ?? '24h';
        $pattern = $args['pattern'] ?? null;
        $groupBy = $args['group_by'] ?? 'type';
        $limit = $args['limit'] ?? 100;
        
        $query = ErrorLog::query();
        
        // Apply filters
        if ($severity) {
            $query->where('severity', $severity);
        }
        
        $startTime = $this->parseTimeRange($timeRange);
        $query->where('created_at', '>=', $startTime);
        
        if ($pattern) {
            $query->where(function ($q) use ($pattern) {
                $q->where('message', 'LIKE', "%{$pattern}%")
                  ->orWhere('context', 'LIKE', "%{$pattern}%");
            });
        }
        
        // Get logs
        $logs = $query->orderBy('created_at', 'desc')->limit($limit)->get();
        
        // Group results
        $grouped = $this->groupErrorLogs($logs, $groupBy);
        
        // Analyze patterns
        $analysis = $this->analyzeErrorPatterns($logs);
        
        return [
            'total_errors' => $logs->count(),
            'time_range' => [
                'from' => $startTime->toIso8601String(),
                'to' => now()->toIso8601String()
            ],
            'grouped' => $grouped,
            'analysis' => $analysis,
            'recent_errors' => $logs->take(10)->map(function ($log) {
                return [
                    'id' => $log->id,
                    'severity' => $log->severity,
                    'message' => $log->message,
                    'type' => $log->type,
                    'file' => $log->file,
                    'line' => $log->line,
                    'created_at' => $log->created_at->toIso8601String(),
                    'user_id' => $log->user_id,
                    'request_id' => $log->request_id
                ];
            })
        ];
    }
    
    protected function monitorDatabasePerformance(array $args): array
    {
        $performance = [
            'timestamp' => now()->toIso8601String(),
            'status' => 'healthy',
            'metrics' => []
        ];
        
        // Connection statistics
        if ($args['include_connection_stats'] ?? true) {
            $connections = DB::select('SHOW STATUS LIKE "Threads_connected"')[0];
            $maxConnections = DB::select('SHOW VARIABLES LIKE "max_connections"')[0];
            
            $performance['connections'] = [
                'current' => (int) $connections->Value,
                'max' => (int) $maxConnections->Value,
                'usage_percent' => round(((int) $connections->Value / (int) $maxConnections->Value) * 100, 2)
            ];
            
            if ($performance['connections']['usage_percent'] > 80) {
                $performance['status'] = 'warning';
            }
        }
        
        // Slow queries
        if ($args['include_slow_queries'] ?? true) {
            $threshold = $args['slow_query_threshold'] ?? 1.0;
            $slowQueries = $this->getSlowQueries($threshold);
            
            $performance['slow_queries'] = [
                'count' => count($slowQueries),
                'threshold' => $threshold,
                'queries' => array_slice($slowQueries, 0, 10) // Top 10
            ];
            
            if (count($slowQueries) > 10) {
                $performance['status'] = 'degraded';
            }
        }
        
        // Table statistics
        if ($args['include_table_stats'] ?? true) {
            $tableStats = $this->getTableStatistics();
            $performance['tables'] = $tableStats;
        }
        
        // Query cache statistics
        $cacheStats = $this->getQueryCacheStats();
        $performance['query_cache'] = $cacheStats;
        
        // InnoDB statistics
        $innodbStats = $this->getInnoDBStats();
        $performance['innodb'] = $innodbStats;
        
        return $performance;
    }
    
    protected function monitorQueueHealth(array $args): array
    {
        $queues = $args['queues'] ?? ['default', 'emails', 'webhooks'];
        $health = [
            'timestamp' => now()->toIso8601String(),
            'status' => 'healthy',
            'queues' => []
        ];
        
        foreach ($queues as $queue) {
            $queueStats = [
                'name' => $queue,
                'size' => Redis::llen("queues:{$queue}"),
                'processing' => Redis::llen("queues:{$queue}:processing"),
                'delayed' => Redis::zcard("queues:{$queue}:delayed"),
                'reserved' => Redis::zcard("queues:{$queue}:reserved")
            ];
            
            // Get processing times if requested
            if ($args['include_processing_times'] ?? true) {
                $queueStats['processing_times'] = $this->getQueueProcessingTimes($queue);
            }
            
            // Get failed jobs if requested
            if ($args['include_failed_jobs'] ?? true) {
                $failedJobs = DB::table('failed_jobs')
                    ->where('queue', $queue)
                    ->where('failed_at', '>=', now()->subHours(24))
                    ->get();
                    
                $queueStats['failed_jobs'] = [
                    'count' => $failedJobs->count(),
                    'recent' => $failedJobs->take(5)->map(function ($job) {
                        return [
                            'id' => $job->id,
                            'exception' => substr($job->exception, 0, 200),
                            'failed_at' => $job->failed_at
                        ];
                    })
                ];
            }
            
            // Determine queue health
            if ($queueStats['size'] > 1000 || $queueStats['failed_jobs']['count'] > 10) {
                $health['status'] = 'degraded';
                $queueStats['status'] = 'warning';
            } else {
                $queueStats['status'] = 'healthy';
            }
            
            $health['queues'][] = $queueStats;
        }
        
        // Horizon status if available
        if (class_exists(\Laravel\Horizon\Horizon::class)) {
            $health['horizon'] = $this->getHorizonStatus();
        }
        
        return $health;
    }
    
    protected function setAlert(array $args): array
    {
        $alert = [
            'id' => uniqid('alert_'),
            'name' => $args['name'],
            'metric' => $args['metric'],
            'condition' => $args['condition'],
            'threshold' => $args['threshold'],
            'duration' => $args['duration'] ?? 5,
            'actions' => $args['actions'] ?? ['log', 'email'],
            'enabled' => $args['enabled'] ?? true,
            'created_at' => now()->toIso8601String()
        ];
        
        // Store alert configuration
        Cache::put("monitoring:alert:{$alert['id']}", $alert, now()->addYear());
        
        // Add to active alerts list
        $activeAlerts = Cache::get('monitoring:alerts:active', []);
        $activeAlerts[] = $alert['id'];
        Cache::put('monitoring:alerts:active', array_unique($activeAlerts), now()->addYear());
        
        return [
            'success' => true,
            'alert' => $alert,
            'message' => "Alert '{$alert['name']}' has been configured successfully"
        ];
    }
    
    protected function generateHealthReport(array $args): array
    {
        $reportType = $args['report_type'] ?? 'summary';
        $includeRecommendations = $args['include_recommendations'] ?? true;
        $format = $args['format'] ?? 'json';
        
        // Collect all health data
        $healthData = [
            'generated_at' => now()->toIso8601String(),
            'report_type' => $reportType,
            'system_health' => $this->getSystemHealth(['include_details' => true]),
            'performance_metrics' => $this->getPerformanceMetrics([
                'metric_types' => ['cpu', 'memory', 'database', 'cache', 'queue'],
                'time_range' => '24h'
            ]),
            'api_monitoring' => $this->monitorApiEndpoints(['time_range' => '24h']),
            'error_summary' => $this->getErrorLogs(['time_range' => '24h', 'group_by' => 'severity']),
            'database_performance' => $this->monitorDatabasePerformance([]),
            'queue_health' => $this->monitorQueueHealth([])
        ];
        
        // Generate summary
        $summary = $this->generateHealthSummary($healthData);
        
        // Generate recommendations if requested
        $recommendations = [];
        if ($includeRecommendations) {
            $recommendations = $this->generateHealthRecommendations($healthData);
        }
        
        $report = [
            'summary' => $summary,
            'data' => $healthData,
            'recommendations' => $recommendations,
            'trends' => $this->calculateHealthTrends()
        ];
        
        // Format output
        if ($format === 'html') {
            return [
                'html' => $this->generateHtmlReport($report),
                'url' => $this->storeReport($report, 'html')
            ];
        } elseif ($format === 'pdf') {
            return [
                'url' => $this->generatePdfReport($report)
            ];
        }
        
        return $report;
    }
    
    // Helper methods
    
    protected function checkDatabaseHealth(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $responseTime = (microtime(true) - $start) * 1000;
            
            return [
                'status' => $responseTime < 100 ? 'healthy' : 'degraded',
                'response_time' => round($responseTime, 2),
                'message' => 'Database connection successful'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
        }
    }
    
    protected function checkCacheHealth(): array
    {
        try {
            $key = 'health_check_' . uniqid();
            Cache::put($key, true, 60);
            $value = Cache::get($key);
            Cache::forget($key);
            
            if ($value === true) {
                return [
                    'status' => 'healthy',
                    'message' => 'Cache is working properly'
                ];
            }
            
            return [
                'status' => 'degraded',
                'message' => 'Cache read/write issue'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'message' => 'Cache connection failed: ' . $e->getMessage()
            ];
        }
    }
    
    protected function checkQueueHealth(): array
    {
        try {
            $size = Redis::llen('queues:default');
            $processing = Redis::llen('queues:default:processing');
            
            $status = 'healthy';
            if ($size > 1000) {
                $status = 'degraded';
            } elseif ($size > 5000) {
                $status = 'critical';
            }
            
            return [
                'status' => $status,
                'queue_size' => $size,
                'processing' => $processing,
                'message' => "Queue size: {$size}, Processing: {$processing}"
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'message' => 'Queue connection failed: ' . $e->getMessage()
            ];
        }
    }
    
    protected function checkFilesystemHealth(): array
    {
        $paths = [
            'storage' => storage_path(),
            'logs' => storage_path('logs'),
            'cache' => storage_path('framework/cache'),
            'sessions' => storage_path('framework/sessions')
        ];
        
        $health = ['status' => 'healthy', 'paths' => []];
        
        foreach ($paths as $name => $path) {
            if (!is_writable($path)) {
                $health['status'] = 'critical';
                $health['paths'][$name] = 'not writable';
            } else {
                $health['paths'][$name] = 'ok';
            }
        }
        
        // Check disk space
        $freeSpace = disk_free_space('/');
        $totalSpace = disk_total_space('/');
        $usedPercent = round((($totalSpace - $freeSpace) / $totalSpace) * 100, 2);
        
        $health['disk_usage'] = [
            'used_percent' => $usedPercent,
            'free_gb' => round($freeSpace / 1024 / 1024 / 1024, 2),
            'total_gb' => round($totalSpace / 1024 / 1024 / 1024, 2)
        ];
        
        if ($usedPercent > 90) {
            $health['status'] = 'critical';
        } elseif ($usedPercent > 80) {
            $health['status'] = 'degraded';
        }
        
        return $health;
    }
    
    protected function checkExternalServices(): array
    {
        $services = [
            'retell' => config('services.retell.base_url') . '/health',
            'calcom' => config('services.calcom.base_url') . '/health',
            'stripe' => 'https://api.stripe.com/v1/charges?limit=1',
            'pusher' => 'https://api.pusher.com/apps/' . config('broadcasting.connections.pusher.app_id') . '/channels'
        ];
        
        $results = [];
        
        foreach ($services as $name => $url) {
            try {
                $start = microtime(true);
                $response = Http::timeout(5)->get($url);
                $responseTime = (microtime(true) - $start) * 1000;
                
                $results[$name] = [
                    'status' => $response->successful() ? 'healthy' : 'degraded',
                    'response_time' => round($responseTime, 2),
                    'status_code' => $response->status()
                ];
            } catch (\Exception $e) {
                $results[$name] = [
                    'status' => 'critical',
                    'message' => 'Service unreachable: ' . $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
    
    protected function checkResourceHealth(): array
    {
        // CPU usage
        $cpuUsage = sys_getloadavg()[0]; // 1-minute load average
        $cpuCores = 4; // Should be detected dynamically
        $cpuPercent = ($cpuUsage / $cpuCores) * 100;
        
        // Memory usage
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        $memoryLimitBytes = $this->convertToBytes($memoryLimit);
        $memoryPercent = ($memoryUsage / $memoryLimitBytes) * 100;
        
        $status = 'healthy';
        if ($cpuPercent > 80 || $memoryPercent > 80) {
            $status = 'degraded';
        } elseif ($cpuPercent > 95 || $memoryPercent > 95) {
            $status = 'critical';
        }
        
        return [
            'status' => $status,
            'cpu' => [
                'usage_percent' => round($cpuPercent, 2),
                'load_average' => $cpuUsage,
                'cores' => $cpuCores
            ],
            'memory' => [
                'usage_percent' => round($memoryPercent, 2),
                'used_mb' => round($memoryUsage / 1024 / 1024, 2),
                'limit' => $memoryLimit
            ]
        ];
    }
    
    protected function convertToBytes(string $value): int
    {
        $unit = strtolower(substr($value, -1));
        $value = (int) $value;
        
        switch ($unit) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }
    
    protected function calculateHealthScore(array $components): int
    {
        $weights = [
            'database' => 0.3,
            'cache' => 0.2,
            'queue' => 0.2,
            'filesystem' => 0.15,
            'resources' => 0.15
        ];
        
        $score = 0;
        
        foreach ($components as $name => $component) {
            $weight = $weights[$name] ?? 0.1;
            
            $componentScore = match($component['status'] ?? 'unknown') {
                'healthy' => 100,
                'degraded' => 60,
                'critical' => 20,
                default => 0
            };
            
            $score += $componentScore * $weight;
        }
        
        return (int) round($score);
    }
    
    protected function storeHealthCheck(array $health): void
    {
        SystemMetric::create([
            'type' => 'health_check',
            'value' => $health['health_score'],
            'metadata' => $health,
            'created_at' => now()
        ]);
        
        // Keep only last 7 days of health checks
        SystemMetric::where('type', 'health_check')
            ->where('created_at', '<', now()->subDays(7))
            ->delete();
    }
}