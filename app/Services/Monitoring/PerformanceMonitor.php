<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PerformanceMonitor
{
    private array $config;
    private float $startTime;
    private array $checkpoints = [];
    private array $queries = [];
    private bool $enabled;

    public function __construct()
    {
        $this->config = config('monitoring.apm');
        $this->enabled = $this->config['enabled'] ?? true;
        $this->startTime = microtime(true);
    }

    /**
     * Start monitoring a transaction
     */
    public function startTransaction(string $name, string $type = 'request'): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->checkpoints = [];
        $this->queries = [];
        $this->startTime = microtime(true);

        // Enable query logging if configured
        if ($this->config['database']['log_queries'] ?? false) {
            DB::enableQueryLog();
        }
    }

    /**
     * Add a checkpoint
     */
    public function checkpoint(string $name): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->checkpoints[$name] = microtime(true) - $this->startTime;
    }

    /**
     * End transaction and record metrics
     */
    public function endTransaction(string $name, array $metadata = []): void
    {
        if (!$this->enabled) {
            return;
        }

        $duration = (microtime(true) - $this->startTime) * 1000; // Convert to ms

        // Get query log if enabled
        if ($this->config['database']['log_queries'] ?? false) {
            $this->queries = DB::getQueryLog();
            DB::disableQueryLog();
        }

        // Check if this transaction type has specific monitoring config
        $transactionConfig = $this->config['transactions'][$name] ?? null;
        
        if ($transactionConfig) {
            // Check if duration exceeds threshold
            if ($duration > ($transactionConfig['threshold'] ?? 1000)) {
                $this->recordSlowTransaction($name, $duration, $metadata);
            }

            // Sample based on configured rate
            $sampleRate = $transactionConfig['sample_rate'] ?? 0.1;
            if (mt_rand() / mt_getrandmax() > $sampleRate) {
                return;
            }
        }

        // Record metrics
        $this->recordMetrics($name, $duration, $metadata);
    }

    /**
     * Monitor external API call
     */
    public function monitorApiCall(string $service, callable $callback)
    {
        if (!$this->enabled) {
            return $callback();
        }

        $startTime = microtime(true);
        $success = true;
        $error = null;

        try {
            $result = $callback();
            return $result;
        } catch (\Exception $e) {
            $success = false;
            $error = $e->getMessage();
            throw $e;
        } finally {
            $duration = (microtime(true) - $startTime) * 1000;
            
            $this->recordApiCall($service, $duration, $success, $error);
        }
    }

    /**
     * Record slow transaction
     */
    private function recordSlowTransaction(string $name, float $duration, array $metadata): void
    {
        Log::channel('performance')->warning('Slow transaction detected', [
            'transaction' => $name,
            'duration_ms' => $duration,
            'checkpoints' => $this->checkpoints,
            'metadata' => $metadata,
            'queries' => $this->analyzeQueries(),
        ]);

        // Send to Sentry as performance issue
        if (function_exists('Sentry\\captureMessage')) {
            \Sentry\captureMessage("Slow transaction: $name", \Sentry\Severity::warning(), [
                'extra' => [
                    'duration_ms' => $duration,
                    'checkpoints' => $this->checkpoints,
                    'slow_queries' => $this->getSlowQueries(),
                ],
            ]);
        }
    }

    /**
     * Analyze queries for performance issues
     */
    private function analyzeQueries(): array
    {
        if (empty($this->queries)) {
            return [];
        }

        $analysis = [
            'total_queries' => count($this->queries),
            'total_time' => 0,
            'slow_queries' => [],
            'duplicate_queries' => [],
        ];

        $queryHashes = [];
        $slowThreshold = $this->config['database']['slow_query_threshold'] ?? 100;

        foreach ($this->queries as $query) {
            $time = $query['time'] ?? 0;
            $analysis['total_time'] += $time;

            // Check for slow queries
            if ($time > $slowThreshold) {
                $analysis['slow_queries'][] = [
                    'query' => $query['query'],
                    'time' => $time,
                    'bindings' => $query['bindings'] ?? [],
                ];
            }

            // Check for duplicate queries
            $hash = md5($query['query']);
            if (isset($queryHashes[$hash])) {
                $queryHashes[$hash]['count']++;
            } else {
                $queryHashes[$hash] = [
                    'query' => $query['query'],
                    'count' => 1,
                ];
            }
        }

        // Find duplicates
        foreach ($queryHashes as $hash => $info) {
            if ($info['count'] > 1) {
                $analysis['duplicate_queries'][] = [
                    'query' => $info['query'],
                    'count' => $info['count'],
                ];
            }
        }

        return $analysis;
    }

    /**
     * Get slow queries
     */
    private function getSlowQueries(): array
    {
        if (empty($this->queries)) {
            return [];
        }

        $slowThreshold = $this->config['database']['slow_query_threshold'] ?? 100;
        $slowQueries = [];

        foreach ($this->queries as $query) {
            if (($query['time'] ?? 0) > $slowThreshold) {
                $slowQueries[] = [
                    'query' => $query['query'],
                    'time' => $query['time'],
                ];
            }
        }

        return $slowQueries;
    }

    /**
     * Record metrics
     */
    private function recordMetrics(string $name, float $duration, array $metadata): void
    {
        // Store in cache for aggregation
        $metricsKey = "metrics:$name:" . date('YmdH');
        $metrics = Cache::get($metricsKey, [
            'count' => 0,
            'total_duration' => 0,
            'min_duration' => PHP_FLOAT_MAX,
            'max_duration' => 0,
            'errors' => 0,
        ]);

        $metrics['count']++;
        $metrics['total_duration'] += $duration;
        $metrics['min_duration'] = min($metrics['min_duration'], $duration);
        $metrics['max_duration'] = max($metrics['max_duration'], $duration);

        if (isset($metadata['error']) && $metadata['error']) {
            $metrics['errors']++;
        }

        Cache::put($metricsKey, $metrics, now()->addHours(25));

        // Log if in debug mode
        if (config('monitoring.logging.levels.api') === 'debug') {
            Log::channel('monitoring')->debug('Transaction completed', [
                'name' => $name,
                'duration_ms' => $duration,
                'metadata' => $metadata,
            ]);
        }
    }

    /**
     * Record API call metrics
     */
    private function recordApiCall(string $service, float $duration, bool $success, ?string $error): void
    {
        $serviceConfig = $this->config['external_services'][$service] ?? null;
        
        // Check for timeout
        if ($serviceConfig && $duration > ($serviceConfig['timeout_threshold'] ?? 5000)) {
            Log::channel('monitoring')->warning('API call timeout', [
                'service' => $service,
                'duration_ms' => $duration,
            ]);
        }

        // Store metrics
        $metricsKey = "api_metrics:$service:" . date('YmdH');
        $metrics = Cache::get($metricsKey, [
            'total_calls' => 0,
            'successful_calls' => 0,
            'failed_calls' => 0,
            'total_duration' => 0,
            'errors' => [],
        ]);

        $metrics['total_calls']++;
        $metrics['total_duration'] += $duration;
        
        if ($success) {
            $metrics['successful_calls']++;
        } else {
            $metrics['failed_calls']++;
            $metrics['errors'][] = [
                'error' => $error,
                'timestamp' => now()->toIso8601String(),
            ];
        }

        Cache::put($metricsKey, $metrics, now()->addHours(25));

        // Check error rate threshold
        if ($serviceConfig) {
            $errorRate = $metrics['failed_calls'] / $metrics['total_calls'];
            if ($errorRate > ($serviceConfig['error_rate_threshold'] ?? 0.05)) {
                app(AlertingService::class)->alert('high_api_error_rate', [
                    'service' => $service,
                    'error_rate' => $errorRate,
                    'total_calls' => $metrics['total_calls'],
                ]);
            }
        }
    }

    /**
     * Get current metrics
     */
    public function getMetrics(string $name, ?string $period = null): array
    {
        $period = $period ?: date('YmdH');
        $metricsKey = "metrics:$name:$period";
        
        return Cache::get($metricsKey, [
            'count' => 0,
            'total_duration' => 0,
            'min_duration' => 0,
            'max_duration' => 0,
            'errors' => 0,
            'avg_duration' => 0,
        ]);
    }

    /**
     * Get API metrics
     */
    public function getApiMetrics(string $service, ?string $period = null): array
    {
        $period = $period ?: date('YmdH');
        $metricsKey = "api_metrics:$service:$period";
        
        $metrics = Cache::get($metricsKey, [
            'total_calls' => 0,
            'successful_calls' => 0,
            'failed_calls' => 0,
            'total_duration' => 0,
            'errors' => [],
        ]);

        if ($metrics['total_calls'] > 0) {
            $metrics['success_rate'] = $metrics['successful_calls'] / $metrics['total_calls'];
            $metrics['avg_duration'] = $metrics['total_duration'] / $metrics['total_calls'];
        }

        return $metrics;
    }
}