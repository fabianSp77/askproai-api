<?php

namespace App\Services\HealthChecks;

use App\Contracts\IntegrationHealthCheck;
use App\Contracts\HealthCheckResult;
use App\Models\Company;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class RedisHealthCheck implements IntegrationHealthCheck
{
    /**
     * Get the name of this health check
     */
    public function getName(): string
    {
        return 'Redis Cache & Queue';
    }
    
    /**
     * Get the priority of this check (higher = more important)
     */
    public function getPriority(): int
    {
        return 90; // High priority - cache and queue are important
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
            $pong = Redis::ping();
            // Redis::ping() returns true on success
            if ($pong !== true && $pong !== 'PONG' && $pong !== '+PONG' && $pong !== 1) {
                throw new \Exception('Redis ping failed: ' . var_export($pong, true));
            }
            
            // Test write/read operations
            $testKey = "health_check_test_{$company->id}";
            $testValue = 'test_' . time();
            
            Redis::set($testKey, $testValue, 'EX', 10);
            $readValue = Redis::get($testKey);
            
            if ($readValue !== $testValue) {
                throw new \Exception('Redis read/write test failed');
            }
            
            // Get Redis info
            $info = Redis::info();
            $memory = $info['used_memory_human'] ?? 'unknown';
            $connectedClients = $info['connected_clients'] ?? 0;
            $totalCommands = $info['total_commands_processed'] ?? 0;
            $evictedKeys = $info['evicted_keys'] ?? 0;
            $hitRate = 0;
            
            if (isset($info['keyspace_hits']) && isset($info['keyspace_misses'])) {
                $hits = $info['keyspace_hits'];
                $misses = $info['keyspace_misses'];
                if (($hits + $misses) > 0) {
                    $hitRate = ($hits / ($hits + $misses)) * 100;
                }
            }
            
            // Check queue sizes (if using Redis for queues)
            $defaultQueueSize = Redis::llen('queues:default');
            $webhookQueueSize = Redis::llen('queues:webhooks');
            $highQueueSize = Redis::llen('queues:high');
            
            $responseTime = (microtime(true) - $startTime) * 1000;
            
            $metrics = [
                'response_time_ms' => round($responseTime, 2),
                'memory_usage' => $memory,
                'connected_clients' => $connectedClients,
                'hit_rate_percent' => round($hitRate, 2),
                'evicted_keys' => $evictedKeys,
                'queue_sizes' => [
                    'default' => $defaultQueueSize,
                    'webhooks' => $webhookQueueSize,
                    'high' => $highQueueSize,
                ],
                'total_commands' => $totalCommands,
            ];
            
            // Determine health status
            $issues = [];
            $suggestions = [];
            
            if ($evictedKeys > 1000) {
                $issues[] = "High number of evicted keys: {$evictedKeys}";
                $suggestions[] = 'Consider increasing Redis memory limit';
            }
            
            if ($hitRate < 80 && ($info['keyspace_hits'] ?? 0) > 1000) {
                $issues[] = "Low cache hit rate: {$hitRate}%";
                $suggestions[] = 'Review cache TTL settings and cache key strategy';
            }
            
            if ($defaultQueueSize > 1000 || $webhookQueueSize > 500) {
                $issues[] = 'High queue backlog detected';
                $suggestions[] = 'Scale up queue workers or optimize job processing';
            }
            
            if (!empty($issues)) {
                return HealthCheckResult::degraded(
                    'Redis is operational but has performance concerns',
                    $issues,
                    $suggestions,
                    $metrics
                );
            }
            
            return HealthCheckResult::healthy(
                'Redis is working properly',
                [
                    'memory' => $memory,
                    'hit_rate' => "{$hitRate}%",
                    'queue_status' => 'normal'
                ],
                $metrics
            );
            
        } catch (\Exception $e) {
            Log::error('Redis health check failed', [
                'error' => $e->getMessage(),
                'company_id' => $company->id
            ]);
            
            return HealthCheckResult::unhealthy(
                'Redis connection failed',
                ['error' => $e->getMessage()],
                [
                    'Check Redis server is running',
                    'Verify Redis credentials',
                    'Check network connectivity to Redis'
                ]
            );
        }
    }
    
    /**
     * Attempt to automatically fix issues
     */
    public function attemptAutoFix(Company $company, array $issues): bool
    {
        try {
            // Clear expired keys
            Redis::flushExpired();
            
            // If memory is an issue, try to reclaim memory
            foreach ($issues as $issue) {
                if (str_contains($issue, 'evicted keys')) {
                    // Run memory doctor
                    Redis::command('MEMORY', ['DOCTOR']);
                    return true;
                }
            }
            
            return false;
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
            if (str_contains($issue, 'evicted keys')) {
                $fixes[] = 'Increase maxmemory setting in Redis configuration';
                $fixes[] = 'Review and optimize cache TTL values';
                $fixes[] = 'Consider using Redis cluster for horizontal scaling';
            }
            
            if (str_contains($issue, 'hit rate')) {
                $fixes[] = 'Analyze cache miss patterns';
                $fixes[] = 'Implement cache warming strategies';
                $fixes[] = 'Review cache key generation logic';
            }
            
            if (str_contains($issue, 'queue backlog')) {
                $fixes[] = 'Increase number of queue workers';
                $fixes[] = 'Optimize job processing time';
                $fixes[] = 'Consider job batching for bulk operations';
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
            // Get Redis info
            $info = Redis::info();
            
            // Get memory stats
            $memory = Redis::info('memory');
            
            // Get client list
            $clients = Redis::client('list');
            $clientCount = count(explode("\n", trim($clients)));
            
            // Get queue sizes
            $queues = ['default', 'notifications', 'webhooks', 'emails'];
            $queueSizes = [];
            foreach ($queues as $queue) {
                try {
                    $size = Redis::llen("queues:{$queue}");
                    $queueSizes[$queue] = $size;
                } catch (\Exception $e) {
                    $queueSizes[$queue] = 'unknown';
                }
            }
            
            // Calculate cache hit rate
            $keyspaceHits = $info['keyspace_hits'] ?? 0;
            $keyspaceMisses = $info['keyspace_misses'] ?? 0;
            $totalOps = $keyspaceHits + $keyspaceMisses;
            $hitRate = $totalOps > 0 ? round(($keyspaceHits / $totalOps) * 100, 2) : 0;
            
            return [
                'redis_version' => $info['redis_version'] ?? 'Unknown',
                'uptime_seconds' => $info['uptime_in_seconds'] ?? 0,
                'connected_clients' => $clientCount,
                'memory' => [
                    'used_memory_human' => $memory['used_memory_human'] ?? 'Unknown',
                    'used_memory_peak_human' => $memory['used_memory_peak_human'] ?? 'Unknown',
                    'memory_fragmentation_ratio' => $memory['mem_fragmentation_ratio'] ?? 'Unknown',
                    'maxmemory_human' => $memory['maxmemory_human'] ?? 'Unknown',
                    'evicted_keys' => $info['evicted_keys'] ?? 0,
                ],
                'performance' => [
                    'instantaneous_ops_per_sec' => $info['instantaneous_ops_per_sec'] ?? 0,
                    'total_commands_processed' => $info['total_commands_processed'] ?? 0,
                    'keyspace_hits' => $keyspaceHits,
                    'keyspace_misses' => $keyspaceMisses,
                    'cache_hit_rate' => $hitRate . '%',
                ],
                'queue_sizes' => $queueSizes,
                'persistence' => [
                    'aof_enabled' => $info['aof_enabled'] ?? 0,
                    'rdb_last_save_time' => isset($info['rdb_last_save_time']) ? date('Y-m-d H:i:s', $info['rdb_last_save_time']) : 'Unknown',
                    'rdb_changes_since_last_save' => $info['rdb_changes_since_last_save'] ?? 'Unknown',
                ],
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'Failed to collect diagnostics',
                'message' => $e->getMessage(),
            ];
        }
    }
}