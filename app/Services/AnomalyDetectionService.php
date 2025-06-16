<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\Company;

class AnomalyDetectionService
{
    protected array $thresholds = [
        'error_rate' => 0.05,
        'response_time' => 500,
        'queue_size' => 1000,
        'calls_per_minute' => 100,
        'failed_jobs_per_hour' => 10,
        'appointment_no_show_rate' => 0.2,
        'company_inactive_days' => 7,
    ];

    protected array $patterns = [];

    public function __construct()
    {
        $this->loadHistoricalPatterns();
    }

    /**
     * Detect anomalies across the entire system
     */
    public function detectSystemAnomalies(): array
    {
        $anomalies = [];

        // Performance anomalies
        $anomalies = array_merge($anomalies, $this->detectPerformanceAnomalies());

        // Traffic anomalies
        $anomalies = array_merge($anomalies, $this->detectTrafficAnomalies());

        // Business anomalies
        $anomalies = array_merge($anomalies, $this->detectBusinessAnomalies());

        // Company-specific anomalies
        $anomalies = array_merge($anomalies, $this->detectCompanyAnomalies());

        // Pattern-based anomalies
        $anomalies = array_merge($anomalies, $this->detectPatternAnomalies());

        return $anomalies;
    }

    /**
     * Detect performance-related anomalies
     */
    protected function detectPerformanceAnomalies(): array
    {
        $anomalies = [];

        // Database performance
        $dbResponseTime = $this->measureDatabaseResponseTime();
        if ($dbResponseTime > $this->thresholds['response_time']) {
            $anomalies[] = [
                'type' => 'database_performance',
                'severity' => $dbResponseTime > 1000 ? 'critical' : 'high',
                'message' => 'Database response time is degraded',
                'value' => $dbResponseTime,
                'threshold' => $this->thresholds['response_time'],
                'recommendation' => 'Check database connections and query performance',
                'timestamp' => now()->toIso8601String(),
            ];
        }

        // Queue performance
        $queueSize = DB::table('jobs')->count();
        $oldestJob = DB::table('jobs')->orderBy('created_at')->first();
        if ($queueSize > $this->thresholds['queue_size']) {
            $anomalies[] = [
                'type' => 'queue_backlog',
                'severity' => $queueSize > 5000 ? 'critical' : 'medium',
                'message' => 'Queue processing is backing up',
                'value' => $queueSize,
                'threshold' => $this->thresholds['queue_size'],
                'oldest_job_age' => $oldestJob ? now()->diffInMinutes($oldestJob->created_at) : 0,
                'recommendation' => 'Scale queue workers or investigate job failures',
                'timestamp' => now()->toIso8601String(),
            ];
        }

        // Failed jobs
        $failedJobs = DB::table('failed_jobs')
            ->where('failed_at', '>=', now()->subHour())
            ->count();
        if ($failedJobs > $this->thresholds['failed_jobs_per_hour']) {
            $anomalies[] = [
                'type' => 'job_failures',
                'severity' => 'high',
                'message' => 'High rate of job failures detected',
                'value' => $failedJobs,
                'threshold' => $this->thresholds['failed_jobs_per_hour'],
                'recommendation' => 'Check failed jobs table and application logs',
                'timestamp' => now()->toIso8601String(),
            ];
        }

        return $anomalies;
    }

    /**
     * Detect traffic-related anomalies
     */
    protected function detectTrafficAnomalies(): array
    {
        $anomalies = [];

        // Current traffic vs historical average
        $currentCallRate = Call::where('created_at', '>=', now()->subMinute())->count();
        $historicalAvg = $this->getHistoricalCallRate();
        
        if ($currentCallRate > $historicalAvg * 3) {
            $anomalies[] = [
                'type' => 'traffic_spike',
                'severity' => $currentCallRate > $historicalAvg * 5 ? 'high' : 'medium',
                'message' => 'Unusual spike in call volume detected',
                'value' => $currentCallRate,
                'historical_average' => $historicalAvg,
                'spike_factor' => round($currentCallRate / max($historicalAvg, 1), 1),
                'recommendation' => 'Monitor system resources and scale if needed',
                'timestamp' => now()->toIso8601String(),
            ];
        }

        // Sudden drop in traffic
        if ($historicalAvg > 10 && $currentCallRate < $historicalAvg * 0.2) {
            $anomalies[] = [
                'type' => 'traffic_drop',
                'severity' => 'high',
                'message' => 'Significant drop in call volume detected',
                'value' => $currentCallRate,
                'historical_average' => $historicalAvg,
                'drop_percentage' => round((1 - ($currentCallRate / max($historicalAvg, 1))) * 100),
                'recommendation' => 'Check external integrations and system availability',
                'timestamp' => now()->toIso8601String(),
            ];
        }

        // Geographic anomalies (if location data available)
        $unusualLocations = $this->detectUnusualGeographicPatterns();
        if (!empty($unusualLocations)) {
            $anomalies[] = [
                'type' => 'geographic_anomaly',
                'severity' => 'low',
                'message' => 'Calls from unusual geographic locations',
                'locations' => $unusualLocations,
                'recommendation' => 'Review for potential security concerns',
                'timestamp' => now()->toIso8601String(),
            ];
        }

        return $anomalies;
    }

    /**
     * Detect business-related anomalies
     */
    protected function detectBusinessAnomalies(): array
    {
        $anomalies = [];

        // No-show rate
        $recentAppointments = Appointment::where('starts_at', '>=', now()->subDay())
            ->where('starts_at', '<', now()->subHour())
            ->count();
        $noShows = Appointment::where('starts_at', '>=', now()->subDay())
            ->where('starts_at', '<', now()->subHour())
            ->where('status', 'no_show')
            ->count();
        
        if ($recentAppointments > 10) {
            $noShowRate = $noShows / $recentAppointments;
            if ($noShowRate > $this->thresholds['appointment_no_show_rate']) {
                $anomalies[] = [
                    'type' => 'high_no_show_rate',
                    'severity' => 'medium',
                    'message' => 'Unusually high appointment no-show rate',
                    'value' => round($noShowRate * 100, 1),
                    'threshold' => $this->thresholds['appointment_no_show_rate'] * 100,
                    'total_appointments' => $recentAppointments,
                    'no_shows' => $noShows,
                    'recommendation' => 'Review reminder system and customer communication',
                    'timestamp' => now()->toIso8601String(),
                ];
            }
        }

        // Booking patterns
        $bookingAnomalies = $this->detectBookingPatternAnomalies();
        $anomalies = array_merge($anomalies, $bookingAnomalies);

        return $anomalies;
    }

    /**
     * Detect company-specific anomalies
     */
    protected function detectCompanyAnomalies(): array
    {
        $anomalies = [];

        // Inactive companies
        $inactiveCompanies = Company::where('is_active', true)
            ->whereDoesntHave('calls', function ($query) {
                $query->where('created_at', '>=', now()->subDays($this->thresholds['company_inactive_days']));
            })
            ->whereDoesntHave('appointments', function ($query) {
                $query->where('created_at', '>=', now()->subDays($this->thresholds['company_inactive_days']));
            })
            ->count();

        if ($inactiveCompanies > 0) {
            $anomalies[] = [
                'type' => 'inactive_companies',
                'severity' => 'low',
                'message' => 'Companies with no recent activity detected',
                'value' => $inactiveCompanies,
                'inactive_days' => $this->thresholds['company_inactive_days'],
                'recommendation' => 'Reach out to inactive companies for engagement',
                'timestamp' => now()->toIso8601String(),
            ];
        }

        // Company-specific performance issues
        $performanceIssues = $this->detectCompanyPerformanceIssues();
        $anomalies = array_merge($anomalies, $performanceIssues);

        return $anomalies;
    }

    /**
     * Detect pattern-based anomalies using machine learning concepts
     */
    protected function detectPatternAnomalies(): array
    {
        $anomalies = [];

        // Time-based patterns
        $currentHour = now()->hour;
        $dayOfWeek = now()->dayOfWeek;
        $expectedCallRate = $this->patterns['hourly_patterns'][$dayOfWeek][$currentHour] ?? 0;
        $actualCallRate = Call::where('created_at', '>=', now()->startOfHour())
            ->where('created_at', '<', now()->endOfHour())
            ->count();

        if ($expectedCallRate > 0) {
            $deviation = abs($actualCallRate - $expectedCallRate) / $expectedCallRate;
            if ($deviation > 0.5) {
                $anomalies[] = [
                    'type' => 'pattern_deviation',
                    'severity' => $deviation > 0.8 ? 'medium' : 'low',
                    'message' => 'Call volume deviates from historical pattern',
                    'expected' => $expectedCallRate,
                    'actual' => $actualCallRate,
                    'deviation_percentage' => round($deviation * 100),
                    'time_context' => [
                        'hour' => $currentHour,
                        'day_of_week' => now()->format('l'),
                    ],
                    'recommendation' => 'Monitor for sustained pattern changes',
                    'timestamp' => now()->toIso8601String(),
                ];
            }
        }

        // Seasonal patterns
        $seasonalAnomalies = $this->detectSeasonalAnomalies();
        $anomalies = array_merge($anomalies, $seasonalAnomalies);

        return $anomalies;
    }

    /**
     * Helper methods
     */
    protected function measureDatabaseResponseTime(): float
    {
        $start = microtime(true);
        try {
            DB::select('SELECT 1');
            return (microtime(true) - $start) * 1000;
        } catch (\Exception $e) {
            return 9999;
        }
    }

    protected function getHistoricalCallRate(): float
    {
        return Cache::remember('historical_call_rate', 300, function () {
            $rates = [];
            for ($i = 1; $i <= 7; $i++) {
                $date = now()->subDays($i);
                $count = Call::whereDate('created_at', $date)
                    ->whereBetween(DB::raw('HOUR(created_at)'), [now()->hour - 1, now()->hour + 1])
                    ->count();
                $rates[] = $count / 180; // Average per minute over 3 hours
            }
            return array_sum($rates) / count($rates);
        });
    }

    protected function detectUnusualGeographicPatterns(): array
    {
        // Placeholder - would analyze IP addresses or location data
        return [];
    }

    protected function detectBookingPatternAnomalies(): array
    {
        $anomalies = [];

        // Check for bulk bookings
        $recentBulkBookings = Appointment::select('customer_id', DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', now()->subHour())
            ->groupBy('customer_id')
            ->having('count', '>', 5)
            ->get();

        if ($recentBulkBookings->count() > 0) {
            $anomalies[] = [
                'type' => 'bulk_bookings',
                'severity' => 'low',
                'message' => 'Unusual bulk booking activity detected',
                'customers' => $recentBulkBookings->pluck('customer_id')->toArray(),
                'recommendation' => 'Review for potential abuse or legitimate bulk needs',
                'timestamp' => now()->toIso8601String(),
            ];
        }

        return $anomalies;
    }

    protected function detectCompanyPerformanceIssues(): array
    {
        $anomalies = [];

        // Companies with high failure rates
        $companies = Company::withCount([
            'calls as total_calls' => function ($query) {
                $query->where('created_at', '>=', now()->subDay());
            },
            'calls as failed_calls' => function ($query) {
                $query->where('created_at', '>=', now()->subDay())
                    ->where('call_successful', false); // Use the correct column
            }
        ])->having('total_calls', '>', 10)
          ->get();

        foreach ($companies as $company) {
            if ($company->total_calls > 0) {
                $failureRate = $company->failed_calls / $company->total_calls;
                if ($failureRate > 0.2) {
                    $anomalies[] = [
                        'type' => 'company_high_failure_rate',
                        'severity' => 'medium',
                        'message' => "High call failure rate for {$company->name}",
                        'company_id' => $company->id,
                        'company_name' => $company->name,
                        'failure_rate' => round($failureRate * 100, 1),
                        'total_calls' => $company->total_calls,
                        'failed_calls' => $company->failed_calls,
                        'recommendation' => 'Check company configuration and integrations',
                        'timestamp' => now()->toIso8601String(),
                    ];
                }
            }
        }

        return $anomalies;
    }

    protected function detectSeasonalAnomalies(): array
    {
        // Placeholder for seasonal pattern detection
        return [];
    }

    protected function loadHistoricalPatterns(): void
    {
        $this->patterns = Cache::remember('anomaly_patterns', 3600, function () {
            $patterns = ['hourly_patterns' => []];
            
            // Build hourly patterns for each day of week
            for ($dow = 0; $dow < 7; $dow++) {
                $patterns['hourly_patterns'][$dow] = [];
                for ($hour = 0; $hour < 24; $hour++) {
                    // Calculate average calls for this hour on this day of week
                    $avg = Call::whereRaw('DAYOFWEEK(created_at) = ?', [$dow + 1])
                        ->whereRaw('HOUR(created_at) = ?', [$hour])
                        ->where('created_at', '>=', now()->subWeeks(4))
                        ->count() / 4; // Average over 4 weeks
                    
                    $patterns['hourly_patterns'][$dow][$hour] = $avg;
                }
            }
            
            return $patterns;
        });
    }

    /**
     * Get severity score for prioritization
     */
    public function getSeverityScore(string $severity): int
    {
        return match ($severity) {
            'critical' => 4,
            'high' => 3,
            'medium' => 2,
            'low' => 1,
            default => 0,
        };
    }

    /**
     * Get recommendations based on current anomalies
     */
    public function getSystemRecommendations(array $anomalies): array
    {
        $recommendations = [];

        // Group anomalies by type
        $groupedAnomalies = collect($anomalies)->groupBy('type');

        // Generate recommendations based on patterns
        if ($groupedAnomalies->has('database_performance') && $groupedAnomalies->has('queue_backlog')) {
            $recommendations[] = [
                'priority' => 'high',
                'action' => 'Scale infrastructure',
                'reason' => 'Multiple performance indicators suggest system is under heavy load',
                'steps' => [
                    'Increase database connection pool size',
                    'Add more queue workers',
                    'Consider horizontal scaling',
                ],
            ];
        }

        if ($groupedAnomalies->has('traffic_spike')) {
            $recommendations[] = [
                'priority' => 'medium',
                'action' => 'Enable auto-scaling',
                'reason' => 'Traffic patterns show unexpected spikes',
                'steps' => [
                    'Configure auto-scaling policies',
                    'Set up traffic monitoring alerts',
                    'Review rate limiting settings',
                ],
            ];
        }

        if ($groupedAnomalies->has('high_no_show_rate')) {
            $recommendations[] = [
                'priority' => 'medium',
                'action' => 'Improve appointment reminders',
                'reason' => 'High no-show rate impacts business efficiency',
                'steps' => [
                    'Send additional reminder 2 hours before appointment',
                    'Implement SMS reminders',
                    'Add cancellation link in reminders',
                ],
            ];
        }

        return $recommendations;
    }
}