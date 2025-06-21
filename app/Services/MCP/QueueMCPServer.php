<?php

namespace App\Services\MCP;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;

class QueueMCPServer
{
    protected array $config;
    protected JobRepository $jobs;
    protected MetricsRepository $metrics;
    protected SupervisorRepository $supervisors;
    
    public function __construct(
        JobRepository $jobs,
        MetricsRepository $metrics,
        SupervisorRepository $supervisors
    ) {
        $this->jobs = $jobs;
        $this->metrics = $metrics;
        $this->supervisors = $supervisors;
        
        $this->config = [
            'cache' => [
                'ttl' => 60, // 1 minute for queue data
                'prefix' => 'mcp:queue'
            ]
        ];
    }
    
    /**
     * Get queue overview
     */
    public function getOverview(): array
    {
        $cacheKey = $this->getCacheKey('overview');
        
        return Cache::remember($cacheKey, $this->config['cache']['ttl'], function () {
            $stats = [
                'horizon_status' => $this->getHorizonStatus(),
                'queues' => $this->getQueueStats(),
                'failed_jobs' => $this->getFailedJobsCount(),
                'workers' => $this->getWorkerStats(),
                'throughput' => $this->getThroughput(),
                'monitored_at' => now()->toIso8601String()
            ];
            
            return $stats;
        });
    }
    
    /**
     * Get failed jobs
     */
    public function getFailedJobs(array $params = []): array
    {
        $limit = min($params['limit'] ?? 50, 100);
        $tag = $params['tag'] ?? null;
        
        $failedJobs = $this->jobs->getFailed($tag, 0, $limit);
        
        // Convert collection to array if needed
        if ($failedJobs instanceof \Illuminate\Support\Collection) {
            $failedJobs = $failedJobs->toArray();
        }
        
        return [
            'jobs' => array_map(function ($job) {
                return [
                    'id' => $job->id,
                    'name' => $job->name ?? 'Unknown',
                    'queue' => $job->queue,
                    'failed_at' => $job->failed_at,
                    'exception' => $this->truncateException($job->exception),
                    'tags' => $job->tags ?? [],
                    'can_retry' => true
                ];
            }, $failedJobs),
            'count' => count($failedJobs),
            'total_failed' => $this->getFailedJobsCount()
        ];
    }
    
    /**
     * Get recent jobs
     */
    public function getRecentJobs(array $params = []): array
    {
        $limit = min($params['limit'] ?? 50, 100);
        $queue = $params['queue'] ?? null;
        
        $recentJobs = $this->jobs->getRecent(0, $limit);
        
        // Convert collection to array if needed
        if ($recentJobs instanceof \Illuminate\Support\Collection) {
            $recentJobs = $recentJobs->toArray();
        }
        
        if ($queue) {
            $recentJobs = array_filter($recentJobs, function ($job) use ($queue) {
                return $job->queue === $queue;
            });
        }
        
        return [
            'jobs' => array_map(function ($job) {
                return [
                    'id' => $job->id,
                    'name' => $job->name ?? 'Unknown',
                    'queue' => $job->queue,
                    'status' => $job->status,
                    'created_at' => $job->created_at ?? null,
                    'completed_at' => $job->completed_at ?? null,
                    'runtime' => $job->runtime ?? null,
                    'tags' => $job->tags ?? []
                ];
            }, array_values($recentJobs)),
            'count' => count($recentJobs)
        ];
    }
    
    /**
     * Get job details
     */
    public function getJobDetails(string $jobId): array
    {
        $job = $this->jobs->getJobs([$jobId])[0] ?? null;
        
        if (!$job) {
            return ['error' => 'Job not found'];
        }
        
        return [
            'job' => [
                'id' => $job->id,
                'name' => $job->name ?? 'Unknown',
                'queue' => $job->queue,
                'status' => $job->status,
                'payload' => $job->payload ?? [],
                'exception' => $job->exception ?? null,
                'failed_at' => $job->failed_at ?? null,
                'completed_at' => $job->completed_at ?? null,
                'created_at' => $job->created_at ?? null,
                'runtime' => $job->runtime ?? null,
                'memory' => $job->memory ?? null,
                'tags' => $job->tags ?? [],
                'retried_by' => $job->retried_by ?? null
            ]
        ];
    }
    
    /**
     * Retry failed job
     */
    public function retryJob(string $jobId): array
    {
        try {
            $this->jobs->retry($jobId);
            
            // Clear cache
            $this->clearCache();
            
            return [
                'success' => true,
                'message' => 'Job queued for retry',
                'job_id' => $jobId
            ];
        } catch (\Exception $e) {
            Log::error('Failed to retry job', [
                'job_id' => $jobId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Failed to retry job',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get queue metrics
     */
    public function getMetrics(array $params = []): array
    {
        $period = $params['period'] ?? 'hour'; // hour, day, week
        
        $cacheKey = $this->getCacheKey('metrics', ['period' => $period]);
        
        return Cache::remember($cacheKey, $this->config['cache']['ttl'], function () use ($period) {
            $snapshot = $this->metrics->snapshot();
            
            return [
                'throughput' => [
                    'jobs_per_minute' => $snapshot->throughput ?? 0,
                    'runtime_average' => $snapshot->runtimeAverage ?? 0
                ],
                'queues' => $this->getQueueMetrics(),
                'wait_time' => [
                    'average' => $this->getAverageWaitTime(),
                    'max' => $this->getMaxWaitTime()
                ],
                'period' => $period,
                'measured_at' => now()->toIso8601String()
            ];
        });
    }
    
    /**
     * Get worker/supervisor status
     */
    public function getWorkers(): array
    {
        $supervisors = $this->supervisors->all();
        
        return [
            'supervisors' => array_map(function ($supervisor) {
                return [
                    'name' => $supervisor->name,
                    'pid' => $supervisor->pid,
                    'status' => $supervisor->status,
                    'processes' => $supervisor->processes,
                    'options' => $supervisor->options,
                    'working_memory' => $supervisor->workingMemory ?? 0
                ];
            }, $supervisors),
            'total_processes' => $this->calculateTotalProcesses($supervisors),
            'active_supervisors' => count(array_filter($supervisors, function ($s) {
                return $s->status === 'running';
            }))
        ];
    }
    
    /**
     * Search jobs
     */
    public function searchJobs(array $params): array
    {
        $query = $params['query'] ?? '';
        $type = $params['type'] ?? 'all'; // all, failed, completed
        $limit = min($params['limit'] ?? 50, 100);
        
        $jobs = [];
        
        if ($type === 'all' || $type === 'failed') {
            $failedJobs = $this->jobs->getFailed(null, 0, $limit);
            if ($failedJobs instanceof \Illuminate\Support\Collection) {
                $failedJobs = $failedJobs->toArray();
            }
            foreach ($failedJobs as $job) {
                if ($this->jobMatchesQuery($job, $query)) {
                    $jobs[] = $job;
                }
            }
        }
        
        if ($type === 'all' || $type === 'completed') {
            $recentJobs = $this->jobs->getRecent(0, $limit);
            if ($recentJobs instanceof \Illuminate\Support\Collection) {
                $recentJobs = $recentJobs->toArray();
            }
            foreach ($recentJobs as $job) {
                if ($job->status === 'completed' && $this->jobMatchesQuery($job, $query)) {
                    $jobs[] = $job;
                }
            }
        }
        
        return [
            'jobs' => array_slice($jobs, 0, $limit),
            'count' => count($jobs),
            'query' => $query,
            'type' => $type
        ];
    }
    
    /**
     * Get Horizon status
     */
    protected function getHorizonStatus(): string
    {
        try {
            $masters = app(MasterSupervisorRepository::class)->all();
            return !empty($masters) && $masters[0]->status === 'running' ? 'running' : 'stopped';
        } catch (\Exception $e) {
            return 'unknown';
        }
    }
    
    /**
     * Get queue statistics
     */
    protected function getQueueStats(): array
    {
        $queues = [];
        
        // Get queue sizes from Redis
        $queueNames = ['default', 'notifications', 'webhooks', 'emails'];
        
        foreach ($queueNames as $queue) {
            try {
                $redis = app('redis')->connection();
                $size = $redis->llen("queues:{$queue}");
            } catch (\Exception $e) {
                $size = 0;
            }
            
            $queues[$queue] = [
                'size' => $size,
                'status' => $size > 100 ? 'high' : ($size > 50 ? 'medium' : 'normal')
            ];
        }
        
        return $queues;
    }
    
    /**
     * Get failed jobs count
     */
    protected function getFailedJobsCount(): int
    {
        return DB::table('failed_jobs')->count();
    }
    
    /**
     * Get worker statistics
     */
    protected function getWorkerStats(): array
    {
        $supervisors = $this->supervisors->all();
        
        $total = 0;
        foreach ($supervisors as $supervisor) {
            $total += (int) ($supervisor->processes ?? 0);
        }
        
        return [
            'total' => $total,
            'active' => count(array_filter($supervisors, fn($s) => $s->status === 'running')),
            'paused' => count(array_filter($supervisors, fn($s) => $s->status === 'paused'))
        ];
    }
    
    /**
     * Get throughput
     */
    protected function getThroughput(): array
    {
        $snapshot = $this->metrics->snapshot();
        
        return [
            'jobs_per_minute' => $snapshot->throughput ?? 0,
            'runtime_average' => $snapshot->runtimeAverage ?? 0
        ];
    }
    
    /**
     * Get queue metrics
     */
    protected function getQueueMetrics(): array
    {
        $metrics = [];
        $queues = ['default', 'notifications', 'webhooks', 'emails'];
        
        foreach ($queues as $queue) {
            $metrics[$queue] = [
                'throughput' => $this->metrics->throughputByQueue($queue),
                'runtime' => $this->metrics->runtimeByQueue($queue)
            ];
        }
        
        return $metrics;
    }
    
    /**
     * Get average wait time
     */
    protected function getAverageWaitTime(): float
    {
        // This would need custom implementation based on your needs
        return 0.0;
    }
    
    /**
     * Get max wait time
     */
    protected function getMaxWaitTime(): float
    {
        // This would need custom implementation based on your needs
        return 0.0;
    }
    
    /**
     * Check if job matches search query
     */
    protected function jobMatchesQuery($job, string $query): bool
    {
        if (empty($query)) {
            return true;
        }
        
        $searchFields = [
            $job->id ?? '',
            $job->name ?? '',
            $job->queue ?? '',
            implode(' ', $job->tags ?? [])
        ];
        
        $searchString = strtolower(implode(' ', $searchFields));
        return strpos($searchString, strtolower($query)) !== false;
    }
    
    /**
     * Truncate exception for display
     */
    protected function truncateException(?string $exception): ?string
    {
        if (!$exception) {
            return null;
        }
        
        // Get first 500 characters
        return strlen($exception) > 500 
            ? substr($exception, 0, 500) . '...' 
            : $exception;
    }
    
    /**
     * Generate cache key
     */
    protected function getCacheKey(string $type, array $params = []): string
    {
        $prefix = $this->config['cache']['prefix'];
        $key = "{$prefix}:{$type}";
        
        if (!empty($params)) {
            $key .= ':' . md5(json_encode($params));
        }
        
        return $key;
    }
    
    /**
     * Clear cache
     */
    public function clearCache(): void
    {
        Cache::forget($this->getCacheKey('overview'));
        Cache::forget($this->getCacheKey('metrics'));
    }
    
    /**
     * Calculate total processes from supervisors
     */
    protected function calculateTotalProcesses($supervisors): int
    {
        $total = 0;
        foreach ($supervisors as $supervisor) {
            $total += (int) ($supervisor->processes ?? 0);
        }
        return $total;
    }
}