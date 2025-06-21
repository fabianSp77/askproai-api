<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class WizardPerformanceTracker
{
    private array $timers = [];
    private array $metrics = [];

    /**
     * Start a performance timer
     */
    public function startTimer(string $operation): void
    {
        $this->timers[$operation] = microtime(true);
    }

    /**
     * End a timer and record the metric
     */
    public function endTimer(string $operation, array $context = []): float
    {
        if (!isset($this->timers[$operation])) {
            return 0;
        }

        $duration = microtime(true) - $this->timers[$operation];
        
        $this->metrics[$operation] = [
            'duration_ms' => round($duration * 1000, 2),
            'context' => $context,
            'timestamp' => now()->toISOString()
        ];

        // Log slow operations
        if ($duration > 1.0) {
            Log::warning("Slow wizard operation: {$operation}", [
                'duration_seconds' => round($duration, 2),
                'context' => $context
            ]);
        }

        // Track in cache for dashboard
        $this->updatePerformanceStats($operation, $duration);

        unset($this->timers[$operation]);
        return $duration;
    }

    /**
     * Record a counter metric
     */
    public function increment(string $metric, int $value = 1): void
    {
        $key = "wizard.metrics.{$metric}";
        Cache::increment($key, $value);
    }

    /**
     * Get all recorded metrics
     */
    public function getMetrics(): array
    {
        return $this->metrics;
    }

    /**
     * Get performance summary
     */
    public function getSummary(): array
    {
        $totalDuration = array_sum(array_column($this->metrics, 'duration_ms'));
        
        return [
            'total_duration_ms' => $totalDuration,
            'operations' => count($this->metrics),
            'slowest_operation' => $this->getSlowestOperation(),
            'metrics' => $this->metrics
        ];
    }

    private function getSlowestOperation(): ?array
    {
        if (empty($this->metrics)) {
            return null;
        }

        $slowest = null;
        $maxDuration = 0;

        foreach ($this->metrics as $operation => $data) {
            if ($data['duration_ms'] > $maxDuration) {
                $maxDuration = $data['duration_ms'];
                $slowest = [
                    'operation' => $operation,
                    'duration_ms' => $data['duration_ms']
                ];
            }
        }

        return $slowest;
    }

    private function updatePerformanceStats(string $operation, float $duration): void
    {
        $key = "wizard.performance.{$operation}";
        $stats = Cache::get($key, [
            'count' => 0,
            'total_duration' => 0,
            'min_duration' => PHP_FLOAT_MAX,
            'max_duration' => 0
        ]);

        $stats['count']++;
        $stats['total_duration'] += $duration;
        $stats['min_duration'] = min($stats['min_duration'], $duration);
        $stats['max_duration'] = max($stats['max_duration'], $duration);
        $stats['avg_duration'] = $stats['total_duration'] / $stats['count'];

        Cache::put($key, $stats, now()->addHours(24));
    }
}