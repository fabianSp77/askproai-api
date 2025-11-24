<?php

namespace App\Services\Metrics;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Reservation Metrics Collector
 *
 * Purpose: Track optimistic reservation system performance metrics
 * Storage: Redis cache (real-time) + structured logs (historical)
 * Migration Path: Exportable to Prometheus via /metrics endpoint
 *
 * Metrics Tracked:
 * - reservation_created_total (counter)
 * - reservation_expired_total (counter)
 * - reservation_converted_total (counter)
 * - reservation_cancelled_total (counter)
 * - reservation_conversion_rate (gauge)
 * - reservation_time_to_conversion (histogram)
 * - reservation_lifetime (histogram)
 * - active_reservations (gauge)
 * - reservation_errors_total (counter)
 */
class ReservationMetricsCollector
{
    private const CACHE_PREFIX = 'metrics:reservations:';
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Track reservation creation
     */
    public function trackCreated(int $companyId, bool $isCompound = false): void
    {
        $this->incrementCounter('created', $companyId);

        if ($isCompound) {
            $this->incrementCounter('created_compound', $companyId);
        }

        $this->log('reservation_created', [
            'company_id' => $companyId,
            'is_compound' => $isCompound,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Track reservation expiration
     */
    public function trackExpired(int $companyId, int $lifetimeSeconds): void
    {
        $this->incrementCounter('expired', $companyId);
        $this->recordHistogram('lifetime', $companyId, $lifetimeSeconds);

        $this->log('reservation_expired', [
            'company_id' => $companyId,
            'lifetime_seconds' => $lifetimeSeconds,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Track successful conversion to appointment
     */
    public function trackConverted(int $companyId, int $timeToConversionSeconds, bool $isCompound = false): void
    {
        $this->incrementCounter('converted', $companyId);
        $this->recordHistogram('time_to_conversion', $companyId, $timeToConversionSeconds);

        if ($isCompound) {
            $this->incrementCounter('converted_compound', $companyId);
        }

        // Update conversion rate
        $this->updateConversionRate($companyId);

        $this->log('reservation_converted', [
            'company_id' => $companyId,
            'time_to_conversion_seconds' => $timeToConversionSeconds,
            'is_compound' => $isCompound,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Track reservation cancellation
     */
    public function trackCancelled(int $companyId, string $reason = 'unknown'): void
    {
        $this->incrementCounter('cancelled', $companyId);
        $this->incrementCounter('cancelled_reason_' . $reason, $companyId);

        $this->log('reservation_cancelled', [
            'company_id' => $companyId,
            'reason' => $reason,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Track reservation error
     */
    public function trackError(int $companyId, string $errorType, string $errorMessage): void
    {
        $this->incrementCounter('errors', $companyId);
        $this->incrementCounter('errors_' . $errorType, $companyId);

        $this->log('reservation_error', [
            'company_id' => $companyId,
            'error_type' => $errorType,
            'error_message' => $errorMessage,
            'timestamp' => now()->toIso8601String(),
        ], 'error');
    }

    /**
     * Update active reservations count
     */
    public function updateActiveCount(int $companyId, int $count): void
    {
        $key = self::CACHE_PREFIX . "active:{$companyId}";
        Cache::put($key, $count, self::CACHE_TTL);
    }

    /**
     * Get metrics for a company
     */
    public function getMetrics(int $companyId): array
    {
        $metrics = [
            'created' => $this->getCounter('created', $companyId),
            'created_compound' => $this->getCounter('created_compound', $companyId),
            'expired' => $this->getCounter('expired', $companyId),
            'converted' => $this->getCounter('converted', $companyId),
            'converted_compound' => $this->getCounter('converted_compound', $companyId),
            'cancelled' => $this->getCounter('cancelled', $companyId),
            'errors' => $this->getCounter('errors', $companyId),
            'active' => $this->getGauge('active', $companyId),
            'conversion_rate' => $this->getGauge('conversion_rate', $companyId),
        ];

        // Calculate derived metrics
        $total = $metrics['converted'] + $metrics['expired'] + $metrics['cancelled'];
        $metrics['completion_rate'] = $total > 0
            ? round(($metrics['converted'] / $total) * 100, 2)
            : 0;

        $metrics['active_rate'] = $metrics['created'] > 0
            ? round(($metrics['active'] / $metrics['created']) * 100, 2)
            : 0;

        return $metrics;
    }

    /**
     * Reset metrics for a company (use for testing only)
     */
    public function reset(int $companyId): void
    {
        $keys = [
            'created', 'created_compound', 'expired', 'converted', 'converted_compound',
            'cancelled', 'errors', 'active', 'conversion_rate'
        ];

        foreach ($keys as $key) {
            Cache::forget(self::CACHE_PREFIX . "{$key}:{$companyId}");
        }
    }

    /**
     * Export metrics in Prometheus format (for future integration)
     */
    public function exportPrometheus(): string
    {
        // This will be implemented when Prometheus is set up
        // For now, return empty string
        return '';
    }

    // ========================================================================
    // PRIVATE HELPER METHODS
    // ========================================================================

    /**
     * Increment a counter metric
     */
    private function incrementCounter(string $metric, int $companyId, int $amount = 1): void
    {
        $key = self::CACHE_PREFIX . "{$metric}:{$companyId}";
        $current = Cache::get($key, 0);
        Cache::put($key, $current + $amount, self::CACHE_TTL);
    }

    /**
     * Get counter value
     */
    private function getCounter(string $metric, int $companyId): int
    {
        $key = self::CACHE_PREFIX . "{$metric}:{$companyId}";
        return Cache::get($key, 0);
    }

    /**
     * Get gauge value
     */
    private function getGauge(string $metric, int $companyId): float
    {
        $key = self::CACHE_PREFIX . "{$metric}:{$companyId}";
        return Cache::get($key, 0.0);
    }

    /**
     * Record histogram value (simplified - stores average for now)
     */
    private function recordHistogram(string $metric, int $companyId, int $value): void
    {
        $key = self::CACHE_PREFIX . "histogram:{$metric}:{$companyId}";
        $data = Cache::get($key, ['count' => 0, 'sum' => 0]);

        $data['count']++;
        $data['sum'] += $value;
        $data['avg'] = $data['sum'] / $data['count'];

        Cache::put($key, $data, self::CACHE_TTL);
    }

    /**
     * Update conversion rate
     */
    private function updateConversionRate(int $companyId): void
    {
        $created = $this->getCounter('created', $companyId);
        $converted = $this->getCounter('converted', $companyId);

        if ($created > 0) {
            $rate = ($converted / $created) * 100;
            $key = self::CACHE_PREFIX . "conversion_rate:{$companyId}";
            Cache::put($key, round($rate, 2), self::CACHE_TTL);
        }
    }

    /**
     * Log structured metric
     */
    private function log(string $event, array $data, string $level = 'info'): void
    {
        $logData = array_merge([
            'event' => $event,
            'metric_type' => 'reservation',
        ], $data);

        Log::channel('daily')->$level('[METRIC] ' . $event, $logData);
    }
}
