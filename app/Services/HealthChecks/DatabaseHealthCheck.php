<?php

namespace App\Services\HealthChecks;

use App\Contracts\IntegrationHealthCheck;
use App\Contracts\HealthCheckResult;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DatabaseHealthCheck implements IntegrationHealthCheck
{
    /**
     * Get the name of this health check
     */
    public function getName(): string
    {
        return 'Database Connection';
    }
    
    /**
     * Get the priority of this check (higher = more important)
     */
    public function getPriority(): int
    {
        return 100; // Highest priority - database is critical
    }
    
    /**
     * Is this check critical for system operation?
     */
    public function isCritical(): bool
    {
        return true;
    }
    
    /**
     * Perform the health check
     */
    public function check(Company $company): HealthCheckResult
    {
        $startTime = microtime(true);
        
        try {
            // Test basic connectivity
            DB::select('SELECT 1');
            
            // Test table access
            $companyCount = Company::where('id', $company->id)->count();
            if ($companyCount !== 1) {
                throw new \Exception('Cannot access company data');
            }
            
            // Check connection pool status
            $connectionInfo = DB::select("SHOW STATUS LIKE 'Threads_connected'")[0] ?? null;
            $maxConnections = DB::select("SHOW VARIABLES LIKE 'max_connections'")[0] ?? null;
            
            $threadsConnected = $connectionInfo->Value ?? 0;
            $maxAllowed = $maxConnections->value ?? 100;
            $connectionUsage = ($threadsConnected / $maxAllowed) * 100;
            
            // Check slow queries
            $slowQueries = DB::select("SHOW STATUS LIKE 'Slow_queries'")[0] ?? null;
            $slowQueryCount = $slowQueries->Value ?? 0;
            
            $responseTime = (microtime(true) - $startTime) * 1000;
            
            $metrics = [
                'response_time_ms' => round($responseTime, 2),
                'connections_used' => $threadsConnected,
                'connections_max' => $maxAllowed,
                'connection_usage_percent' => round($connectionUsage, 2),
                'slow_queries' => $slowQueryCount,
            ];
            
            // Determine health status
            if ($connectionUsage > 80) {
                return HealthCheckResult::degraded(
                    'Database connection pool usage is high',
                    ['connection_usage' => "{$connectionUsage}%"],
                    ['Consider increasing max_connections or optimizing queries'],
                    $metrics
                );
            }
            
            if ($slowQueryCount > 100) {
                return HealthCheckResult::degraded(
                    'High number of slow queries detected',
                    ['slow_queries' => $slowQueryCount],
                    ['Review and optimize slow queries'],
                    $metrics
                );
            }
            
            return HealthCheckResult::healthy(
                'Database connection is working properly',
                [
                    'connection_pool' => "{$threadsConnected}/{$maxAllowed} connections",
                    'slow_queries' => $slowQueryCount
                ],
                $metrics
            );
            
        } catch (\Exception $e) {
            Log::error('Database health check failed', [
                'error' => $e->getMessage(),
                'company_id' => $company->id
            ]);
            
            return HealthCheckResult::unhealthy(
                'Database connection failed',
                ['error' => $e->getMessage()],
                [
                    'Check database credentials',
                    'Verify database server is running',
                    'Check network connectivity'
                ]
            );
        }
    }
    
    /**
     * Attempt to automatically fix issues
     */
    public function attemptAutoFix(Company $company, array $issues): bool
    {
        // Try to clear query cache
        try {
            DB::statement('RESET QUERY CACHE');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Get suggested fixes for common issues
     */
    public function getSuggestedFixes(array $issues): array
    {
        $fixes = [];
        
        foreach ($issues as $issue) {
            if (str_contains($issue, 'connection pool')) {
                $fixes[] = 'Increase max_connections in MySQL configuration';
                $fixes[] = 'Review and close idle connections';
                $fixes[] = 'Implement connection pooling';
            }
            
            if (str_contains($issue, 'slow queries')) {
                $fixes[] = 'Enable slow query log and analyze queries';
                $fixes[] = 'Add indexes to frequently queried columns';
                $fixes[] = 'Optimize complex queries';
            }
        }
        
        return $fixes;
    }
    
    /**
     * Get detailed diagnostics information
     */
    public function getDiagnostics(): array
    {
        try {
            // Database version
            $version = DB::select('SELECT VERSION() as version')[0]->version ?? 'Unknown';
            
            // Key performance metrics
            $metrics = [];
            $statusVars = DB::select("SHOW STATUS WHERE Variable_name IN ('Threads_connected', 'Slow_queries', 'Uptime', 'Questions', 'Com_select', 'Com_insert', 'Com_update', 'Com_delete')");
            foreach ($statusVars as $var) {
                $metrics[$var->Variable_name] = $var->Value;
            }
            
            // Key settings
            $settings = [];
            $configVars = DB::select("SHOW VARIABLES WHERE Variable_name IN ('max_connections', 'thread_cache_size', 'query_cache_size', 'innodb_buffer_pool_size', 'slow_query_log', 'long_query_time')");
            foreach ($configVars as $var) {
                $settings[$var->Variable_name] = $var->Value;
            }
            
            // Calculate QPS (Queries Per Second)
            $uptime = $metrics['Uptime'] ?? 1;
            $questions = $metrics['Questions'] ?? 0;
            $qps = $uptime > 0 ? round($questions / $uptime, 2) : 0;
            
            // Table sizes
            $tableSizes = DB::select("
                SELECT 
                    table_name,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
                    table_rows
                FROM information_schema.tables
                WHERE table_schema = DATABASE()
                ORDER BY (data_length + index_length) DESC
                LIMIT 10
            ");
            
            return [
                'database_version' => $version,
                'uptime_seconds' => $metrics['Uptime'] ?? 0,
                'performance_metrics' => [
                    'queries_per_second' => $qps,
                    'total_queries' => $metrics['Questions'] ?? 0,
                    'select_queries' => $metrics['Com_select'] ?? 0,
                    'insert_queries' => $metrics['Com_insert'] ?? 0,
                    'update_queries' => $metrics['Com_update'] ?? 0,
                    'delete_queries' => $metrics['Com_delete'] ?? 0,
                    'slow_queries' => $metrics['Slow_queries'] ?? 0,
                    'threads_connected' => $metrics['Threads_connected'] ?? 0,
                ],
                'configuration' => [
                    'max_connections' => $settings['max_connections'] ?? 'Unknown',
                    'thread_cache_size' => $settings['thread_cache_size'] ?? 'Unknown',
                    'query_cache_size' => $settings['query_cache_size'] ?? 'Unknown',
                    'innodb_buffer_pool_size' => $settings['innodb_buffer_pool_size'] ?? 'Unknown',
                    'slow_query_log' => $settings['slow_query_log'] ?? 'OFF',
                    'long_query_time' => $settings['long_query_time'] ?? 'Unknown',
                ],
                'largest_tables' => array_map(function ($table) {
                    return [
                        'name' => $table->table_name,
                        'size_mb' => $table->size_mb,
                        'rows' => $table->table_rows,
                    ];
                }, $tableSizes),
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'Failed to collect diagnostics',
                'message' => $e->getMessage(),
            ];
        }
    }
}