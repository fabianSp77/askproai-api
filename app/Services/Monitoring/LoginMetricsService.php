<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LoginMetricsService
{
    /**
     * Record a login attempt.
     */
    public function recordLoginAttempt(array $data): void
    {
        $data = array_merge([
            'email' => null,
            'user_id' => null,
            'portal' => 'business',
            'success' => false,
            'failure_reason' => null,
            'ip_address' => null,
            'user_agent' => null,
            'duration_ms' => null,
            'company_id' => null,
            'timestamp' => now(),
        ], $data);

        try {
            // Store in database
            DB::table('login_metrics')->insert([
                'email' => $data['email'],
                'user_id' => $data['user_id'],
                'portal' => $data['portal'],
                'success' => $data['success'],
                'failure_reason' => $data['failure_reason'],
                'ip_address' => $data['ip_address'],
                'user_agent' => substr($data['user_agent'] ?? '', 0, 255),
                'duration_ms' => $data['duration_ms'],
                'company_id' => $data['company_id'],
                'created_at' => $data['timestamp'],
            ]);

            // Update real-time metrics cache
            $this->updateRealtimeMetrics($data);
            
            // Check if we need to trigger alerts
            $this->checkAlertThresholds();
            
        } catch (\Exception $e) {
            Log::error('Failed to record login metrics', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
        }
    }

    /**
     * Get login success rate for a time period.
     */
    public function getSuccessRate(string $period = 'hour', ?string $portal = null): float
    {
        $since = $this->getTimeSince($period);
        
        $query = DB::table('login_metrics')
            ->where('created_at', '>=', $since);
            
        if ($portal) {
            $query->where('portal', $portal);
        }
        
        $total = $query->count();
        
        if ($total === 0) {
            return 100.0; // No attempts = 100% success
        }
        
        $successful = (clone $query)->where('success', true)->count();
        
        return round(($successful / $total) * 100, 2);
    }

    /**
     * Get detailed metrics for dashboard.
     */
    public function getDetailedMetrics(string $period = 'day'): array
    {
        $since = $this->getTimeSince($period);
        
        // Overall metrics
        $totalAttempts = DB::table('login_metrics')
            ->where('created_at', '>=', $since)
            ->count();
            
        $successfulAttempts = DB::table('login_metrics')
            ->where('created_at', '>=', $since)
            ->where('success', true)
            ->count();
            
        $failedAttempts = $totalAttempts - $successfulAttempts;
        
        // Failure reasons breakdown
        $failureReasons = DB::table('login_metrics')
            ->where('created_at', '>=', $since)
            ->where('success', false)
            ->whereNotNull('failure_reason')
            ->groupBy('failure_reason')
            ->select('failure_reason', DB::raw('COUNT(*) as count'))
            ->orderBy('count', 'desc')
            ->get();
            
        // Hourly breakdown for chart
        $hourlyData = $this->getHourlyBreakdown($since);
        
        // Average response time
        $avgResponseTime = DB::table('login_metrics')
            ->where('created_at', '>=', $since)
            ->whereNotNull('duration_ms')
            ->avg('duration_ms');
            
        // Most failed users
        $topFailedUsers = DB::table('login_metrics')
            ->where('created_at', '>=', $since)
            ->where('success', false)
            ->whereNotNull('email')
            ->groupBy('email')
            ->select('email', DB::raw('COUNT(*) as failure_count'))
            ->orderBy('failure_count', 'desc')
            ->limit(10)
            ->get();
            
        // Geographic distribution (by IP)
        $geoDistribution = $this->getGeographicDistribution($since);
        
        return [
            'period' => $period,
            'since' => $since->toIso8601String(),
            'total_attempts' => $totalAttempts,
            'successful_attempts' => $successfulAttempts,
            'failed_attempts' => $failedAttempts,
            'success_rate' => $totalAttempts > 0 ? round(($successfulAttempts / $totalAttempts) * 100, 2) : 100,
            'failure_reasons' => $failureReasons,
            'hourly_data' => $hourlyData,
            'avg_response_time_ms' => round($avgResponseTime ?? 0, 2),
            'top_failed_users' => $topFailedUsers,
            'geo_distribution' => $geoDistribution,
        ];
    }

    /**
     * Get real-time metrics from cache.
     */
    public function getRealtimeMetrics(): array
    {
        return Cache::get('login_metrics:realtime', [
            'current_success_rate' => 100,
            'attempts_last_5min' => 0,
            'failures_last_5min' => 0,
            'active_alerts' => [],
            'last_updated' => now()->toIso8601String(),
        ]);
    }

    /**
     * Update real-time metrics in cache.
     */
    private function updateRealtimeMetrics(array $data): void
    {
        $metrics = $this->getRealtimeMetrics();
        
        // Get last 5 minutes data
        $last5Min = now()->subMinutes(5);
        $recentAttempts = DB::table('login_metrics')
            ->where('created_at', '>=', $last5Min)
            ->get();
            
        $total = $recentAttempts->count();
        $successful = $recentAttempts->where('success', true)->count();
        
        $metrics['attempts_last_5min'] = $total;
        $metrics['failures_last_5min'] = $total - $successful;
        $metrics['current_success_rate'] = $total > 0 ? round(($successful / $total) * 100, 2) : 100;
        $metrics['last_updated'] = now()->toIso8601String();
        
        Cache::put('login_metrics:realtime', $metrics, 300); // 5 minutes
    }

    /**
     * Check if we need to trigger alerts.
     */
    private function checkAlertThresholds(): void
    {
        $metrics = $this->getRealtimeMetrics();
        $alerts = [];
        
        // Check success rate threshold
        $threshold = config('monitoring.login.alert_threshold', 85);
        if ($metrics['current_success_rate'] < $threshold && $metrics['attempts_last_5min'] >= 5) {
            $alerts[] = [
                'type' => 'low_success_rate',
                'severity' => 'warning',
                'message' => "Login success rate dropped to {$metrics['current_success_rate']}% (threshold: {$threshold}%)",
                'triggered_at' => now()->toIso8601String(),
            ];
        }
        
        // Check for sudden spike in failures
        if ($metrics['failures_last_5min'] >= 10) {
            $alerts[] = [
                'type' => 'high_failure_rate',
                'severity' => 'critical',
                'message' => "{$metrics['failures_last_5min']} login failures in the last 5 minutes",
                'triggered_at' => now()->toIso8601String(),
            ];
        }
        
        // Update alerts in cache
        if (!empty($alerts)) {
            $existingAlerts = $metrics['active_alerts'] ?? [];
            $metrics['active_alerts'] = array_merge($existingAlerts, $alerts);
            Cache::put('login_metrics:realtime', $metrics, 300);
            
            // Log alerts
            foreach ($alerts as $alert) {
                Log::channel('auth')->warning('Login metric alert triggered', $alert);
            }
            
            // TODO: Send notifications (email, Slack, etc.)
        }
    }

    /**
     * Get hourly breakdown for charts.
     */
    private function getHourlyBreakdown(Carbon $since): array
    {
        $hours = [];
        $current = now()->startOfHour();
        
        while ($current->gte($since)) {
            $nextHour = $current->copy()->addHour();
            
            $attempts = DB::table('login_metrics')
                ->whereBetween('created_at', [$current, $nextHour])
                ->count();
                
            $successful = DB::table('login_metrics')
                ->whereBetween('created_at', [$current, $nextHour])
                ->where('success', true)
                ->count();
                
            $hours[] = [
                'hour' => $current->format('H:00'),
                'date' => $current->format('Y-m-d'),
                'attempts' => $attempts,
                'successful' => $successful,
                'failed' => $attempts - $successful,
                'success_rate' => $attempts > 0 ? round(($successful / $attempts) * 100, 2) : 100,
            ];
            
            $current->subHour();
        }
        
        return array_reverse($hours);
    }

    /**
     * Get geographic distribution by IP.
     */
    private function getGeographicDistribution(Carbon $since): array
    {
        // Group by IP prefix (first 3 octets for privacy)
        $ips = DB::table('login_metrics')
            ->where('created_at', '>=', $since)
            ->whereNotNull('ip_address')
            ->select(DB::raw("
                SUBSTRING_INDEX(ip_address, '.', 3) as ip_prefix,
                COUNT(*) as total_attempts,
                SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as successful_attempts,
                COUNT(DISTINCT email) as unique_users
            "))
            ->groupBy('ip_prefix')
            ->orderBy('total_attempts', 'desc')
            ->limit(20)
            ->get();
            
        return $ips->map(function ($ip) {
            $ip->success_rate = $ip->total_attempts > 0 
                ? round(($ip->successful_attempts / $ip->total_attempts) * 100, 2)
                : 100;
            return $ip;
        })->toArray();
    }

    /**
     * Get time since based on period.
     */
    private function getTimeSince(string $period): Carbon
    {
        return match ($period) {
            '5min' => now()->subMinutes(5),
            '15min' => now()->subMinutes(15),
            'hour' => now()->subHour(),
            'day' => now()->subDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            default => now()->subHour(),
        };
    }

    /**
     * Clear old metrics data.
     */
    public function cleanupOldMetrics(int $daysToKeep = 30): int
    {
        $cutoffDate = now()->subDays($daysToKeep);
        
        return DB::table('login_metrics')
            ->where('created_at', '<', $cutoffDate)
            ->delete();
    }
}