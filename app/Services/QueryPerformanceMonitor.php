<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QueryPerformanceMonitor
{
    protected array $queries = [];
    protected float $startTime;
    protected bool $enabled = false;

    public function __construct()
    {
        $this->enabled = config('app.debug', false) || config('monitoring.query_performance', false);
    }

    /**
     * Start monitoring
     */
    public function start(): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->startTime = microtime(true);
        $this->queries = [];

        DB::listen(function ($query) {
            $this->queries[] = [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time,
            ];
        });
    }

    /**
     * Stop monitoring and log results
     */
    public function stop(string $route = null): void
    {
        if (!$this->enabled || empty($this->queries)) {
            return;
        }

        $totalTime = (microtime(true) - $this->startTime) * 1000;
        $totalQueries = count($this->queries);
        $totalQueryTime = array_sum(array_column($this->queries, 'time'));

        // Only log slow requests
        if ($totalTime > 1000 || $totalQueries > 50) {
            Log::warning('Slow request detected', [
                'route' => $route,
                'total_time' => $totalTime,
                'total_queries' => $totalQueries,
                'total_query_time' => $totalQueryTime,
                'queries' => $this->queries,
            ]);
        }
    }

    /**
     * Get current queries
     */
    public function getQueries(): array
    {
        return $this->queries;
    }

    /**
     * Check if monitoring is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Get performance statistics
     */
    public function getStats(): array
    {
        if (!$this->enabled || empty($this->queries)) {
            return [
                'total_queries' => 0,
                'total_time' => 0,
                'avg_time' => 0,
                'slow_queries' => 0,
                'queries' => []
            ];
        }

        $totalQueries = count($this->queries);
        $totalTime = array_sum(array_column($this->queries, 'time'));
        $avgTime = $totalQueries > 0 ? $totalTime / $totalQueries : 0;
        $slowQueries = count(array_filter($this->queries, fn($q) => $q['time'] > 100));

        return [
            'total_queries' => $totalQueries,
            'total_time' => round($totalTime, 2),
            'avg_time' => round($avgTime, 2),
            'slow_queries' => $slowQueries,
            'queries' => $this->queries
        ];
    }
}