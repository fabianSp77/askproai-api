<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Events\RateLimitExceeded;
use App\Models\MetricLog;
use Carbon\Carbon;

class RateLimitMonitor
{
    /**
     * Track rate limit violation
     */
    public function trackViolation(string $key, string $endpoint, int $attempts): void
    {
        if (!config('rate-limiting.monitoring.log_violations', true)) {
            return;
        }

        // Log the violation
        Log::warning('Rate limit exceeded', [
            'key' => $key,
            'endpoint' => $endpoint,
            'attempts' => $attempts,
            'timestamp' => now()->toIso8601String(),
        ]);

        // Track violations per hour
        $hourKey = 'rate_limit_violations:' . now()->format('Y-m-d-H');
        Cache::increment($hourKey, 1);
        Cache::expire($hourKey, 3600); // Keep for 1 hour

        // Check if we should send an alert
        $violations = Cache::get($hourKey, 0);
        if ($violations >= config('rate-limiting.monitoring.alert_threshold', 100)) {
            $this->sendAlert($violations);
        }

        // Store metrics if enabled
        if (config('rate-limiting.monitoring.metrics_enabled', true)) {
            $this->storeMetrics($key, $endpoint, $attempts);
        }

        // Dispatch event for additional handling
        event(new RateLimitExceeded($key, $endpoint, $attempts));
    }

    /**
     * Get current violation count
     */
    public function getViolationCount(): int
    {
        $hourKey = 'rate_limit_violations:' . now()->format('Y-m-d-H');
        return Cache::get($hourKey, 0);
    }

    /**
     * Get rate limit statistics
     */
    public function getStatistics(): array
    {
        $stats = [];
        
        // Get hourly violations for last 24 hours
        for ($i = 0; $i < 24; $i++) {
            $hour = now()->subHours($i);
            $key = 'rate_limit_violations:' . $hour->format('Y-m-d-H');
            $stats['hourly_violations'][$hour->format('H:00')] = Cache::get($key, 0);
        }

        // Get top violated endpoints
        $stats['top_endpoints'] = $this->getTopViolatedEndpoints();
        
        // Get current status
        $stats['current_hour_violations'] = $this->getViolationCount();
        $stats['alert_threshold'] = config('rate-limiting.monitoring.alert_threshold', 100);
        
        return $stats;
    }

    /**
     * Store metrics for analysis
     */
    private function storeMetrics(string $key, string $endpoint, int $attempts): void
    {
        try {
            MetricLog::create([
                'type' => 'rate_limit_violation',
                'data' => [
                    'key' => $key,
                    'endpoint' => $endpoint,
                    'attempts' => $attempts,
                ],
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to store rate limit metrics', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send alert for high violation count
     */
    private function sendAlert(int $violations): void
    {
        // Check if we've already sent an alert this hour
        $alertKey = 'rate_limit_alert_sent:' . now()->format('Y-m-d-H');
        if (Cache::has($alertKey)) {
            return;
        }

        Log::critical('High rate limit violations detected', [
            'violations' => $violations,
            'threshold' => config('rate-limiting.monitoring.alert_threshold', 100),
            'hour' => now()->format('Y-m-d H:00'),
        ]);

        // Mark alert as sent
        Cache::put($alertKey, true, 3600);

        // TODO: Send email/Slack notification to admin
    }

    /**
     * Get top violated endpoints
     */
    private function getTopViolatedEndpoints(): array
    {
        // This would need a more sophisticated tracking mechanism
        // For now, return empty array
        return [];
    }
}