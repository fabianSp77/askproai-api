<?php

namespace App\Services\MCP;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Connection;

class MCPConnectionPoolManager
{
    /**
     * Connection pool settings
     */
    protected array $poolConfig = [
        'min_connections' => 5,
        'max_connections' => 100,
        'idle_timeout' => 300, // 5 minutes
        'health_check_interval' => 60, // 1 minute
        'connection_timeout' => 5,
        'retry_attempts' => 3,
        'retry_delay' => 100 // milliseconds
    ];
    
    /**
     * Current pool statistics
     */
    protected array $stats = [
        'active_connections' => 0,
        'idle_connections' => 0,
        'total_connections' => 0,
        'failed_connections' => 0,
        'health_checks' => 0,
        'last_health_check' => null
    ];
    
    /**
     * Optimize connection pool based on current load
     */
    public function optimizePool(): array
    {
        Log::info('[MCPConnectionPoolManager] Starting connection pool optimization');
        
        $recommendations = [];
        
        // Get current database status
        $status = $this->getDatabaseStatus();
        
        // Analyze connection usage
        $usage = $this->analyzeConnectionUsage($status);
        
        // Generate recommendations
        if ($usage['connection_usage_percent'] > 80) {
            $recommendations[] = [
                'action' => 'increase_max_connections',
                'current' => $status['max_connections'],
                'recommended' => (int)($status['max_connections'] * 1.5),
                'reason' => 'High connection usage detected'
            ];
        }
        
        if ($usage['idle_connections'] > 50) {
            $recommendations[] = [
                'action' => 'reduce_idle_timeout',
                'current' => $this->poolConfig['idle_timeout'],
                'recommended' => 180,
                'reason' => 'Too many idle connections'
            ];
        }
        
        if ($usage['aborted_connections'] > 10) {
            $recommendations[] = [
                'action' => 'increase_connection_timeout',
                'current' => $this->poolConfig['connection_timeout'],
                'recommended' => 10,
                'reason' => 'High number of aborted connections'
            ];
        }
        
        // Apply optimizations
        $applied = $this->applyOptimizations($recommendations);
        
        // Update statistics
        $this->updateStats($status);
        
        return [
            'status' => $status,
            'usage' => $usage,
            'recommendations' => $recommendations,
            'applied' => $applied,
            'stats' => $this->stats
        ];
    }
    
    /**
     * Get database connection status
     */
    protected function getDatabaseStatus(): array
    {
        try {
            $variables = [];
            
            // Get key variables
            $vars = DB::select("SHOW VARIABLES WHERE Variable_name IN ('max_connections', 'wait_timeout', 'interactive_timeout', 'thread_cache_size')");
            foreach ($vars as $var) {
                $variables[$var->Variable_name] = $var->Value;
            }
            
            // Get current status
            $status = DB::select("SHOW STATUS WHERE Variable_name IN ('Threads_connected', 'Threads_running', 'Connections', 'Aborted_connects', 'Aborted_clients')");
            foreach ($status as $stat) {
                $variables[$stat->Variable_name] = $stat->Value;
            }
            
            return $variables;
        } catch (\Exception $e) {
            Log::error('[MCPConnectionPoolManager] Failed to get database status', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Analyze connection usage
     */
    protected function analyzeConnectionUsage(array $status): array
    {
        $maxConnections = (int)($status['max_connections'] ?? 100);
        $currentConnections = (int)($status['Threads_connected'] ?? 0);
        $runningThreads = (int)($status['Threads_running'] ?? 0);
        $totalConnections = (int)($status['Connections'] ?? 0);
        $abortedConnections = (int)($status['Aborted_connects'] ?? 0);
        
        return [
            'max_connections' => $maxConnections,
            'current_connections' => $currentConnections,
            'running_threads' => $runningThreads,
            'idle_connections' => $currentConnections - $runningThreads,
            'connection_usage_percent' => round(($currentConnections / $maxConnections) * 100, 2),
            'total_connections' => $totalConnections,
            'aborted_connections' => $abortedConnections,
            'aborted_rate' => $totalConnections > 0 ? round(($abortedConnections / $totalConnections) * 100, 2) : 0
        ];
    }
    
    /**
     * Apply optimizations
     */
    protected function applyOptimizations(array $recommendations): array
    {
        $applied = [];
        
        foreach ($recommendations as $recommendation) {
            try {
                switch ($recommendation['action']) {
                    case 'increase_max_connections':
                        // This requires MySQL privileges
                        Log::warning('[MCPConnectionPoolManager] Cannot automatically increase max_connections. Manual intervention required.', $recommendation);
                        $applied[] = [
                            'action' => $recommendation['action'],
                            'status' => 'manual_required',
                            'command' => "SET GLOBAL max_connections = {$recommendation['recommended']};"
                        ];
                        break;
                        
                    case 'reduce_idle_timeout':
                        DB::statement("SET GLOBAL wait_timeout = {$recommendation['recommended']}");
                        $this->poolConfig['idle_timeout'] = $recommendation['recommended'];
                        $applied[] = [
                            'action' => $recommendation['action'],
                            'status' => 'applied'
                        ];
                        break;
                        
                    case 'increase_connection_timeout':
                        // Update Laravel config
                        config(['database.connections.mysql.options.PDO::ATTR_TIMEOUT' => $recommendation['recommended']]);
                        $this->poolConfig['connection_timeout'] = $recommendation['recommended'];
                        $applied[] = [
                            'action' => $recommendation['action'],
                            'status' => 'applied'
                        ];
                        break;
                }
            } catch (\Exception $e) {
                Log::error('[MCPConnectionPoolManager] Failed to apply optimization', [
                    'action' => $recommendation['action'],
                    'error' => $e->getMessage()
                ]);
                $applied[] = [
                    'action' => $recommendation['action'],
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $applied;
    }
    
    /**
     * Update pool statistics
     */
    protected function updateStats(array $status): void
    {
        $this->stats['active_connections'] = (int)($status['Threads_running'] ?? 0);
        $this->stats['idle_connections'] = (int)($status['Threads_connected'] ?? 0) - $this->stats['active_connections'];
        $this->stats['total_connections'] = (int)($status['Threads_connected'] ?? 0);
        $this->stats['failed_connections'] = (int)($status['Aborted_connects'] ?? 0);
        $this->stats['last_health_check'] = now();
        $this->stats['health_checks']++;
        
        // Cache stats for monitoring
        Cache::put('mcp:connection_pool:stats', $this->stats, 300);
    }
    
    /**
     * Perform health check on connections
     */
    public function healthCheck(): array
    {
        $results = [
            'healthy' => true,
            'checks' => []
        ];
        
        // Check primary connection
        try {
            DB::connection()->getPdo();
            DB::select('SELECT 1');
            $results['checks']['primary'] = 'ok';
        } catch (\Exception $e) {
            $results['healthy'] = false;
            $results['checks']['primary'] = 'failed: ' . $e->getMessage();
        }
        
        // Check read replica if configured
        if (config('database.connections.mysql.read')) {
            try {
                DB::connection('mysql::read')->getPdo();
                DB::connection('mysql::read')->select('SELECT 1');
                $results['checks']['read_replica'] = 'ok';
            } catch (\Exception $e) {
                $results['healthy'] = false;
                $results['checks']['read_replica'] = 'failed: ' . $e->getMessage();
            }
        }
        
        // Check connection pool status
        $status = $this->getDatabaseStatus();
        $usage = $this->analyzeConnectionUsage($status);
        
        if ($usage['connection_usage_percent'] > 90) {
            $results['healthy'] = false;
            $results['checks']['connection_pool'] = 'critical: ' . $usage['connection_usage_percent'] . '% usage';
        } else {
            $results['checks']['connection_pool'] = 'ok: ' . $usage['connection_usage_percent'] . '% usage';
        }
        
        // Log health check
        if (!$results['healthy']) {
            Log::error('[MCPConnectionPoolManager] Health check failed', $results);
        }
        
        return $results;
    }
    
    /**
     * Get connection pool metrics for monitoring
     */
    public function getMetrics(): array
    {
        $status = $this->getDatabaseStatus();
        $usage = $this->analyzeConnectionUsage($status);
        
        return [
            'connections' => [
                'active' => $usage['running_threads'],
                'idle' => $usage['idle_connections'],
                'total' => $usage['current_connections'],
                'max' => $usage['max_connections'],
                'usage_percent' => $usage['connection_usage_percent']
            ],
            'performance' => [
                'queries_per_second' => $this->getQueriesPerSecond(),
                'slow_queries' => $this->getSlowQueryCount(),
                'aborted_connections' => $usage['aborted_connections'],
                'aborted_rate' => $usage['aborted_rate']
            ],
            'config' => $this->poolConfig,
            'last_check' => $this->stats['last_health_check']
        ];
    }
    
    /**
     * Get queries per second
     */
    protected function getQueriesPerSecond(): float
    {
        try {
            $result = DB::select("SHOW STATUS LIKE 'Questions'");
            $questions = (int)($result[0]->Value ?? 0);
            
            $result = DB::select("SHOW STATUS LIKE 'Uptime'");
            $uptime = (int)($result[0]->Value ?? 1);
            
            return round($questions / $uptime, 2);
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get slow query count
     */
    protected function getSlowQueryCount(): int
    {
        try {
            $result = DB::select("SHOW STATUS LIKE 'Slow_queries'");
            return (int)($result[0]->Value ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * Configure connection pool for optimal performance
     */
    public function configure(array $config = []): void
    {
        $this->poolConfig = array_merge($this->poolConfig, $config);
        
        // Apply Laravel database configuration
        config([
            'database.connections.mysql.options' => array_merge(
                config('database.connections.mysql.options', []),
                [
                    \PDO::ATTR_PERSISTENT => true,
                    \PDO::ATTR_TIMEOUT => $this->poolConfig['connection_timeout'],
                    \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci', SESSION sql_mode='TRADITIONAL'"
                ]
            )
        ]);
        
        Log::info('[MCPConnectionPoolManager] Connection pool configured', $this->poolConfig);
    }
}