<?php

namespace App\Services\Monitoring;

use App\Models\{Appointment, CalcomEventMap, WebhookEvent};
use App\Services\CalcomV2Client;
use Illuminate\Support\Facades\{DB, Cache, Log};
use Illuminate\Support\Collection;
use Carbon\Carbon;

class CalcomMetricsCollector
{
    private CalcomV2Client $client;
    private array $metrics = [];

    public function __construct(?CalcomV2Client $client = null)
    {
        $this->client = $client ?? new CalcomV2Client();
    }

    /**
     * Collect all metrics for Cal.com integration
     */
    public function collectAllMetrics(): array
    {
        $this->metrics = [
            'timestamp' => now()->toIso8601String(),
            'api' => $this->collectApiMetrics(),
            'bookings' => $this->collectBookingMetrics(),
            'webhooks' => $this->collectWebhookMetrics(),
            'performance' => $this->collectPerformanceMetrics(),
            'synchronization' => $this->collectSyncMetrics(),
            'errors' => $this->collectErrorMetrics(),
            'composite' => $this->collectCompositeMetrics(),
            'availability' => $this->collectAvailabilityMetrics(),
            'capacity' => $this->collectCapacityMetrics(),
            'trends' => $this->collectTrendMetrics()
        ];

        // Cache metrics for dashboard
        Cache::put('calcom:metrics:latest', $this->metrics, 300);

        return $this->metrics;
    }

    /**
     * API Health Metrics
     */
    private function collectApiMetrics(): array
    {
        $metrics = Cache::get('calcom:api:metrics', [
            'total_requests' => 0,
            'successful_requests' => 0,
            'failed_requests' => 0,
            'response_times' => []
        ]);

        $avgResponseTime = count($metrics['response_times']) > 0
            ? array_sum($metrics['response_times']) / count($metrics['response_times'])
            : 0;

        $successRate = $metrics['total_requests'] > 0
            ? ($metrics['successful_requests'] / $metrics['total_requests']) * 100
            : 100;

        // Test current API connectivity
        $apiStatus = 'unknown';
        $latency = 0;

        try {
            $start = microtime(true);
            $response = $this->client->getEventTypes();
            $latency = (microtime(true) - $start) * 1000;

            $apiStatus = $response->successful() ? 'healthy' : 'degraded';
        } catch (\Exception $e) {
            $apiStatus = 'down';
            Log::error('Cal.com API health check failed', ['error' => $e->getMessage()]);
        }

        return [
            'status' => $apiStatus,
            'latency_ms' => round($latency, 2),
            'success_rate' => round($successRate, 2),
            'avg_response_time_ms' => round($avgResponseTime, 2),
            'total_requests_24h' => $metrics['total_requests'],
            'failed_requests_24h' => $metrics['failed_requests'],
            'last_check' => now()->toIso8601String()
        ];
    }

    /**
     * Booking Metrics
     */
    private function collectBookingMetrics(): array
    {
        $now = Carbon::now();
        $startOfDay = $now->copy()->startOfDay();
        $startOfWeek = $now->copy()->startOfWeek();
        $startOfMonth = $now->copy()->startOfMonth();

        return [
            'today' => [
                'total' => Appointment::whereDate('created_at', $now->toDateString())->count(),
                'booked' => Appointment::whereDate('created_at', $now->toDateString())
                    ->where('status', 'booked')->count(),
                'cancelled' => Appointment::whereDate('created_at', $now->toDateString())
                    ->where('status', 'cancelled')->count(),
                'completed' => Appointment::whereDate('created_at', $now->toDateString())
                    ->where('status', 'completed')->count()
            ],
            'this_week' => [
                'total' => Appointment::whereBetween('created_at', [$startOfWeek, $now])->count(),
                'conversion_rate' => $this->calculateConversionRate($startOfWeek, $now)
            ],
            'this_month' => [
                'total' => Appointment::whereBetween('created_at', [$startOfMonth, $now])->count(),
                'avg_per_day' => Appointment::whereBetween('created_at', [$startOfMonth, $now])
                    ->count() / $now->diffInDays($startOfMonth, true)
            ],
            'upcoming_24h' => Appointment::where('status', 'booked')
                ->whereBetween('starts_at', [$now, $now->copy()->addDay()])
                ->count(),
            'cancellation_rate_7d' => $this->calculateCancellationRate(7),
            'no_show_rate_30d' => $this->calculateNoShowRate(30)
        ];
    }

    /**
     * Webhook Metrics
     */
    private function collectWebhookMetrics(): array
    {
        $recentWebhooks = WebhookEvent::where('created_at', '>=', Carbon::now()->subDay())
            ->get();

        $processingTimes = $recentWebhooks
            ->filter(fn($w) => $w->processed_at)
            ->map(fn($w) => $w->processed_at->diffInMilliseconds($w->created_at))
            ->toArray();

        $avgProcessingTime = count($processingTimes) > 0
            ? array_sum($processingTimes) / count($processingTimes)
            : 0;

        return [
            'total_24h' => $recentWebhooks->count(),
            'processed' => $recentWebhooks->where('status', 'processed')->count(),
            'failed' => $recentWebhooks->where('status', 'failed')->count(),
            'pending' => $recentWebhooks->where('status', 'pending')->count(),
            'avg_processing_time_ms' => round($avgProcessingTime, 2),
            'unique_events' => $recentWebhooks->pluck('event_type')->unique()->count(),
            'duplicate_rate' => $this->calculateDuplicateRate($recentWebhooks),
            'last_received' => WebhookEvent::latest()->first()?->created_at?->toIso8601String()
        ];
    }

    /**
     * Performance Metrics
     */
    private function collectPerformanceMetrics(): array
    {
        $performanceData = Cache::get('calcom:performance:metrics', []);

        return [
            'api_response_p50' => $performanceData['p50'] ?? 0,
            'api_response_p95' => $performanceData['p95'] ?? 0,
            'api_response_p99' => $performanceData['p99'] ?? 0,
            'database_query_avg_ms' => $this->getAverageQueryTime(),
            'cache_hit_rate' => $this->getCacheHitRate(),
            'queue_depth' => DB::table('jobs')->count(),
            'failed_jobs_24h' => DB::table('failed_jobs')
                ->where('failed_at', '>=', Carbon::now()->subDay())
                ->count(),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2)
        ];
    }

    /**
     * Synchronization Metrics
     */
    private function collectSyncMetrics(): array
    {
        $mappings = CalcomEventMap::all();

        return [
            'total_mappings' => $mappings->count(),
            'synced' => $mappings->where('sync_status', 'synced')->count(),
            'pending' => $mappings->where('sync_status', 'pending')->count(),
            'failed' => $mappings->where('sync_status', 'failed')->count(),
            'out_of_sync' => $this->detectOutOfSyncMappings(),
            'orphaned_appointments' => $this->detectOrphanedAppointments(),
            'last_sync_check' => Cache::get('calcom:last_sync_check'),
            'sync_lag_minutes' => $this->calculateSyncLag()
        ];
    }

    /**
     * Error Metrics
     */
    private function collectErrorMetrics(): array
    {
        $errors = Cache::get('calcom:errors:recent', []);
        $errorCounts = array_count_values(array_column($errors, 'type'));

        return [
            'total_24h' => count($errors),
            'by_type' => $errorCounts,
            'rate_limiting_hits' => Cache::get('calcom:rate_limit:hits', 0),
            'timeout_errors' => Cache::get('calcom:timeout:count', 0),
            'validation_errors' => Cache::get('calcom:validation:errors', 0),
            'compensation_saga_triggers' => Cache::get('calcom:saga:compensations', 0),
            'circuit_breaker_trips' => Cache::get('calcom:circuit:trips', 0),
            'most_common_error' => $this->getMostCommonError($errorCounts)
        ];
    }

    /**
     * Composite Booking Metrics
     */
    private function collectCompositeMetrics(): array
    {
        $compositeBookings = Appointment::where('is_composite', true)
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->get();

        $segmentCounts = $compositeBookings
            ->map(fn($a) => count($a->segments ?? []))
            ->filter()
            ->toArray();

        $avgSegments = count($segmentCounts) > 0
            ? array_sum($segmentCounts) / count($segmentCounts)
            : 0;

        return [
            'total_7d' => $compositeBookings->count(),
            'avg_segments' => round($avgSegments, 1),
            'success_rate' => $this->calculateCompositeSuccessRate($compositeBookings),
            'avg_pause_duration_min' => $this->calculateAvgPauseDuration($compositeBookings),
            'most_common_pattern' => $this->getMostCommonCompositePattern($compositeBookings),
            'compensation_rate' => $this->calculateCompensationRate($compositeBookings)
        ];
    }

    /**
     * Availability Metrics
     */
    private function collectAvailabilityMetrics(): array
    {
        $cacheKey = 'calcom:availability:metrics';
        $availabilityData = Cache::get($cacheKey, []);

        return [
            'slots_queried_24h' => $availabilityData['queries'] ?? 0,
            'avg_slots_per_query' => $availabilityData['avg_slots'] ?? 0,
            'cache_usage' => [
                'hits' => $availabilityData['cache_hits'] ?? 0,
                'misses' => $availabilityData['cache_misses'] ?? 0,
                'hit_rate' => $this->calculateCacheHitRate(
                    $availabilityData['cache_hits'] ?? 0,
                    $availabilityData['cache_misses'] ?? 0
                )
            ],
            'peak_query_hour' => $availabilityData['peak_hour'] ?? null,
            'avg_query_time_ms' => $availabilityData['avg_query_time'] ?? 0
        ];
    }

    /**
     * Capacity Metrics
     */
    private function collectCapacityMetrics(): array
    {
        $now = Carbon::now();
        $nextWeek = $now->copy()->addWeek();

        $upcomingAppointments = Appointment::where('status', 'booked')
            ->whereBetween('starts_at', [$now, $nextWeek])
            ->get();

        $staffUtilization = $upcomingAppointments
            ->groupBy('staff_id')
            ->map(fn($apps) => [
                'appointments' => $apps->count(),
                'hours' => $apps->sum(fn($a) =>
                    Carbon::parse($a->ends_at)->diffInMinutes(Carbon::parse($a->starts_at)) / 60
                )
            ]);

        return [
            'upcoming_appointments_7d' => $upcomingAppointments->count(),
            'staff_utilization' => [
                'avg_appointments' => $staffUtilization->avg('appointments'),
                'avg_hours' => round($staffUtilization->avg('hours'), 1),
                'max_appointments' => $staffUtilization->max('appointments'),
                'staff_count' => $staffUtilization->count()
            ],
            'busiest_day' => $this->findBusiestDay($upcomingAppointments),
            'peak_hours' => $this->findPeakHours($upcomingAppointments),
            'booking_density' => $this->calculateBookingDensity($upcomingAppointments)
        ];
    }

    /**
     * Trend Metrics
     */
    private function collectTrendMetrics(): array
    {
        $dailyData = $this->getDailyMetrics(30);

        return [
            'booking_trend_30d' => $this->calculateTrend($dailyData->pluck('bookings')),
            'cancellation_trend_30d' => $this->calculateTrend($dailyData->pluck('cancellations')),
            'revenue_trend_30d' => $this->calculateTrend($dailyData->pluck('revenue')),
            'growth_rate_mom' => $this->calculateMonthOverMonthGrowth(),
            'seasonal_pattern' => $this->detectSeasonalPattern($dailyData),
            'forecast_next_7d' => $this->forecastNextWeek($dailyData)
        ];
    }

    // Helper Methods

    private function calculateConversionRate(Carbon $start, Carbon $end): float
    {
        $total = Appointment::whereBetween('created_at', [$start, $end])->count();
        $booked = Appointment::whereBetween('created_at', [$start, $end])
            ->where('status', 'booked')->count();

        return $total > 0 ? round(($booked / $total) * 100, 2) : 0;
    }

    private function calculateCancellationRate(int $days): float
    {
        $start = Carbon::now()->subDays($days);
        $total = Appointment::where('created_at', '>=', $start)->count();
        $cancelled = Appointment::where('created_at', '>=', $start)
            ->where('status', 'cancelled')->count();

        return $total > 0 ? round(($cancelled / $total) * 100, 2) : 0;
    }

    private function calculateNoShowRate(int $days): float
    {
        $start = Carbon::now()->subDays($days);
        $pastAppointments = Appointment::where('starts_at', '<', Carbon::now())
            ->where('starts_at', '>=', $start)
            ->where('status', 'booked')
            ->count();

        $noShows = Appointment::where('starts_at', '<', Carbon::now())
            ->where('starts_at', '>=', $start)
            ->where('status', 'no_show')
            ->count();

        $total = $pastAppointments + $noShows;
        return $total > 0 ? round(($noShows / $total) * 100, 2) : 0;
    }

    private function calculateDuplicateRate(Collection $webhooks): float
    {
        if ($webhooks->isEmpty()) return 0;

        $duplicates = $webhooks->groupBy('cal_booking_id')
            ->filter(fn($group) => $group->count() > 1)
            ->count();

        return round(($duplicates / $webhooks->count()) * 100, 2);
    }

    private function getAverageQueryTime(): float
    {
        $queryTimes = Cache::get('db:query:times', []);
        return count($queryTimes) > 0
            ? round(array_sum($queryTimes) / count($queryTimes), 2)
            : 0;
    }

    private function getCacheHitRate(): float
    {
        $hits = Cache::get('cache:hits', 0);
        $misses = Cache::get('cache:misses', 0);

        return $this->calculateCacheHitRate($hits, $misses);
    }

    private function calculateCacheHitRate(int $hits, int $misses): float
    {
        $total = $hits + $misses;
        return $total > 0 ? round(($hits / $total) * 100, 2) : 0;
    }

    private function detectOutOfSyncMappings(): int
    {
        return CalcomEventMap::where('sync_status', 'synced')
            ->where('last_synced_at', '<', Carbon::now()->subHours(24))
            ->count();
    }

    private function detectOrphanedAppointments(): int
    {
        return Appointment::whereNull('cal_booking_id')
            ->where('status', 'booked')
            ->where('created_at', '<', Carbon::now()->subHour())
            ->count();
    }

    private function calculateSyncLag(): float
    {
        $lastSync = CalcomEventMap::where('sync_status', 'synced')
            ->max('last_synced_at');

        if (!$lastSync) return 0;

        return Carbon::parse($lastSync)->diffInMinutes(Carbon::now());
    }

    private function getMostCommonError(array $errorCounts): ?string
    {
        if (empty($errorCounts)) return null;

        arsort($errorCounts);
        return key($errorCounts);
    }

    private function calculateCompositeSuccessRate(Collection $bookings): float
    {
        if ($bookings->isEmpty()) return 100;

        $successful = $bookings->filter(fn($b) => $b->status === 'booked')->count();
        return round(($successful / $bookings->count()) * 100, 2);
    }

    private function calculateAvgPauseDuration(Collection $bookings): float
    {
        $pauseDurations = $bookings
            ->map(fn($b) => $b->metadata['pause_duration'] ?? null)
            ->filter()
            ->toArray();

        return count($pauseDurations) > 0
            ? round(array_sum($pauseDurations) / count($pauseDurations), 1)
            : 0;
    }

    private function getMostCommonCompositePattern(Collection $bookings): ?string
    {
        $patterns = $bookings
            ->map(fn($b) => implode('->', array_column($b->segments ?? [], 'key')))
            ->countBy()
            ->sortDesc()
            ->first();

        return $patterns ? key($patterns) : null;
    }

    private function calculateCompensationRate(Collection $bookings): float
    {
        if ($bookings->isEmpty()) return 0;

        $compensated = $bookings
            ->filter(fn($b) => ($b->metadata['compensated'] ?? false) === true)
            ->count();

        return round(($compensated / $bookings->count()) * 100, 2);
    }

    private function findBusiestDay(Collection $appointments): ?string
    {
        return $appointments
            ->groupBy(fn($a) => Carbon::parse($a->starts_at)->format('l'))
            ->map->count()
            ->sortDesc()
            ->keys()
            ->first();
    }

    private function findPeakHours(Collection $appointments): array
    {
        return $appointments
            ->groupBy(fn($a) => Carbon::parse($a->starts_at)->format('H'))
            ->map->count()
            ->sortDesc()
            ->take(3)
            ->keys()
            ->map(fn($h) => "{$h}:00")
            ->toArray();
    }

    private function calculateBookingDensity(Collection $appointments): float
    {
        if ($appointments->isEmpty()) return 0;

        $totalSlots = 7 * 8 * 60; // 7 days * 8 hours * 60 minutes
        $bookedMinutes = $appointments->sum(fn($a) =>
            Carbon::parse($a->ends_at)->diffInMinutes(Carbon::parse($a->starts_at))
        );

        return round(($bookedMinutes / $totalSlots) * 100, 2);
    }

    private function getDailyMetrics(int $days): Collection
    {
        return collect(range(0, $days - 1))->map(function($daysAgo) {
            $date = Carbon::now()->subDays($daysAgo)->toDateString();

            return [
                'date' => $date,
                'bookings' => Appointment::whereDate('created_at', $date)
                    ->where('status', 'booked')->count(),
                'cancellations' => Appointment::whereDate('created_at', $date)
                    ->where('status', 'cancelled')->count(),
                'revenue' => 0 // Placeholder - implement if revenue tracking exists
            ];
        });
    }

    private function calculateTrend(Collection $data): string
    {
        if ($data->count() < 2) return 'stable';

        $firstHalf = $data->take($data->count() / 2)->avg();
        $secondHalf = $data->skip($data->count() / 2)->avg();

        if ($secondHalf > $firstHalf * 1.1) return 'increasing';
        if ($secondHalf < $firstHalf * 0.9) return 'decreasing';
        return 'stable';
    }

    private function calculateMonthOverMonthGrowth(): float
    {
        $thisMonth = Appointment::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

        $lastMonth = Appointment::whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->whereYear('created_at', Carbon::now()->subMonth()->year)
            ->count();

        if ($lastMonth === 0) return 0;

        return round((($thisMonth - $lastMonth) / $lastMonth) * 100, 2);
    }

    private function detectSeasonalPattern(Collection $dailyData): string
    {
        $weekdayAvg = $dailyData->filter(fn($d) => !Carbon::parse($d['date'])->isWeekend())
            ->avg('bookings');

        $weekendAvg = $dailyData->filter(fn($d) => Carbon::parse($d['date'])->isWeekend())
            ->avg('bookings');

        if ($weekdayAvg > $weekendAvg * 1.5) return 'weekday_heavy';
        if ($weekendAvg > $weekdayAvg * 1.5) return 'weekend_heavy';
        return 'balanced';
    }

    private function forecastNextWeek(Collection $dailyData): array
    {
        // Simple moving average forecast
        $recentAvg = $dailyData->take(7)->avg('bookings');
        $trend = $this->calculateTrend($dailyData->pluck('bookings'));

        $multiplier = match($trend) {
            'increasing' => 1.1,
            'decreasing' => 0.9,
            default => 1.0
        };

        return [
            'expected_bookings' => round($recentAvg * 7 * $multiplier),
            'confidence' => 'medium',
            'based_on' => '30-day moving average with trend adjustment'
        ];
    }
}