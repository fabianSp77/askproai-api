<?php

namespace App\Services\Resilience;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Failure Detector Service
 *
 * Monitors and tracks failures from external services (Cal.com API)
 * Provides proactive failure detection before circuit breaker opens
 * Useful for metrics, alerting, and logging
 */
class FailureDetector
{
    private const CACHE_KEY_PREFIX = 'failure_detector:';
    private const FAILURE_WINDOW = 300; // 5 minutes

    /**
     * Track a failure and determine severity
     *
     * @param string $service Service name (e.g., 'calcom', 'retell')
     * @param string $reason Reason for failure
     * @param int $severity 1=low, 2=medium, 3=critical
     * @param array $context Additional context
     */
    public function recordFailure(
        string $service,
        string $reason,
        int $severity = 2,
        array $context = []
    ): void {
        $key = $this->getCacheKey($service, 'failures');
        $failures = Cache::get($key, []);

        // Record failure with timestamp
        $failures[] = [
            'timestamp' => now()->timestamp,
            'reason' => $reason,
            'severity' => $severity,
            'context' => $context,
        ];

        // Keep only last 100 failures
        $failures = array_slice($failures, -100);

        Cache::put($key, $failures, self::FAILURE_WINDOW);

        // Update failure rate
        $this->updateFailureRate($service);

        Log::warning("Service failure recorded", [
            'service' => $service,
            'reason' => $reason,
            'severity' => $severity,
            'failure_count' => count($failures),
        ]);
    }

    /**
     * Get failure statistics for a service
     *
     * @return array With keys: total, recent, rate, severity_distribution, last_failure_at
     */
    public function getFailureStats(string $service): array
    {
        $key = $this->getCacheKey($service, 'failures');
        $failures = Cache::get($key, []);

        $recentFailures = array_filter($failures, function ($f) {
            return ($f['timestamp'] ?? 0) > (now()->timestamp - 60); // Last minute
        });

        $severityDistribution = [
            'low' => 0,
            'medium' => 0,
            'critical' => 0,
        ];

        foreach ($failures as $failure) {
            $severity = $failure['severity'] ?? 2;
            if ($severity === 1) {
                $severityDistribution['low']++;
            } elseif ($severity === 3) {
                $severityDistribution['critical']++;
            } else {
                $severityDistribution['medium']++;
            }
        }

        $lastFailureAt = null;
        if (!empty($failures)) {
            $lastFailureAt = $failures[array_key_last($failures)]['timestamp'] ?? null;
        }

        return [
            'total' => count($failures),
            'recent' => count($recentFailures),
            'rate' => $this->getFailureRate($service),
            'severity_distribution' => $severityDistribution,
            'last_failure_at' => $lastFailureAt,
        ];
    }

    /**
     * Check if service is experiencing degradation
     *
     * @return bool True if failure rate exceeds threshold
     */
    public function isServiceDegraded(string $service, float $threshold = 0.25): bool
    {
        $rate = $this->getFailureRate($service);
        return $rate >= $threshold;
    }

    /**
     * Check if service is in critical state
     *
     * @return bool True if too many critical failures
     */
    public function isServiceCritical(string $service): bool
    {
        $stats = $this->getFailureStats($service);
        return $stats['recent'] >= 3 || $stats['severity_distribution']['critical'] >= 5;
    }

    /**
     * Clear failures for a service (usually after recovery)
     */
    public function reset(string $service): void
    {
        Cache::forget($this->getCacheKey($service, 'failures'));
        Cache::forget($this->getCacheKey($service, 'failure_rate'));

        Log::info("Failure detector reset for service", [
            'service' => $service,
        ]);
    }

    /**
     * Get cache key for service metrics
     */
    private function getCacheKey(string $service, string $metric): string
    {
        return self::CACHE_KEY_PREFIX . "{$service}:{$metric}";
    }

    /**
     * Update failure rate (percentage of recent operations that failed)
     */
    private function updateFailureRate(string $service): void
    {
        // This would be updated by tracking both successes and failures
        // For now, simple implementation based on failure count
        // In production, would track success/total ratio
        $rateKey = $this->getCacheKey($service, 'failure_rate');
        $stats = $this->getFailureStats($service);

        // Simple heuristic: if >5 failures in 5min window, mark as degraded
        $rate = min(1.0, $stats['recent'] / 10);
        Cache::put($rateKey, $rate, self::FAILURE_WINDOW);
    }

    /**
     * Get current failure rate (0.0 to 1.0)
     */
    private function getFailureRate(string $service): float
    {
        $rateKey = $this->getCacheKey($service, 'failure_rate');
        return Cache::get($rateKey, 0.0);
    }

    /**
     * Get health status for monitoring dashboard
     */
    public function getHealthStatus(string $service): array
    {
        $stats = $this->getFailureStats($service);
        $isDegraded = $this->isServiceDegraded($service);
        $isCritical = $this->isServiceCritical($service);

        return [
            'service' => $service,
            'status' => $isCritical ? 'critical' : ($isDegraded ? 'degraded' : 'healthy'),
            'statistics' => $stats,
            'degradation_threshold' => 0.25,
            'critical_threshold' => 3,
        ];
    }
}
