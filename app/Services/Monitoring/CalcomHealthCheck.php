<?php

namespace App\Services\Monitoring;

use App\Services\CalcomV2Client;
use App\Models\CalcomEventMap;
use App\Models\Appointment;
use App\Models\WebhookEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Cal.com Integration Health Check Service
 * Monitors API connectivity, performance, and integration health
 */
class CalcomHealthCheck
{
    private CalcomV2Client $client;
    private array $metrics = [];
    private array $issues = [];

    public function __construct()
    {
        $this->client = new CalcomV2Client();
    }

    /**
     * Run comprehensive health check
     */
    public function check(): array
    {
        $startTime = microtime(true);

        // Initialize result structure
        $result = [
            'status' => 'healthy',
            'timestamp' => Carbon::now()->toIso8601String(),
            'checks' => [],
            'metrics' => [],
            'issues' => []
        ];

        // Run individual checks
        $checks = [
            'api_connectivity' => $this->checkApiConnectivity(),
            'authentication' => $this->checkAuthentication(),
            'event_types' => $this->checkEventTypes(),
            'recent_bookings' => $this->checkRecentBookings(),
            'webhook_processing' => $this->checkWebhookProcessing(),
            'error_rate' => $this->checkErrorRate(),
            'response_time' => $this->checkResponseTime(),
            'database_sync' => $this->checkDatabaseSync()
        ];

        // Aggregate results
        foreach ($checks as $name => $check) {
            $result['checks'][$name] = $check;

            if ($check['status'] === 'critical') {
                $result['status'] = 'critical';
            } elseif ($check['status'] === 'warning' && $result['status'] !== 'critical') {
                $result['status'] = 'warning';
            }
        }

        // Add performance metrics
        $result['metrics'] = $this->getMetrics();
        $result['issues'] = $this->issues;
        $result['execution_time_ms'] = round((microtime(true) - $startTime) * 1000, 2);

        // Cache result for dashboard
        Cache::put('calcom_health_check', $result, 60);

        return $result;
    }

    /**
     * Check API connectivity
     */
    private function checkApiConnectivity(): array
    {
        try {
            $start = microtime(true);
            $response = $this->client->getEventTypes();
            $duration = round((microtime(true) - $start) * 1000, 2);

            if ($response->successful()) {
                return [
                    'status' => 'healthy',
                    'message' => 'API is reachable',
                    'response_time_ms' => $duration
                ];
            }

            $this->issues[] = "API returned status {$response->status()}";
            return [
                'status' => 'critical',
                'message' => 'API request failed',
                'http_status' => $response->status(),
                'response_time_ms' => $duration
            ];

        } catch (\Exception $e) {
            $this->issues[] = "API connectivity error: " . $e->getMessage();
            return [
                'status' => 'critical',
                'message' => 'Cannot reach Cal.com API',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check authentication
     */
    private function checkAuthentication(): array
    {
        try {
            $response = $this->client->getEventTypes();

            if ($response->status() === 401) {
                $this->issues[] = "Authentication failed - check API key";
                return [
                    'status' => 'critical',
                    'message' => 'Authentication failed'
                ];
            }

            return [
                'status' => 'healthy',
                'message' => 'Authentication successful'
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'warning',
                'message' => 'Could not verify authentication',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check event types synchronization
     */
    private function checkEventTypes(): array
    {
        try {
            // Check local event mappings
            $localMappings = CalcomEventMap::count();
            $syncedMappings = CalcomEventMap::where('sync_status', 'synced')->count();
            $errorMappings = CalcomEventMap::where('sync_status', 'error')->count();

            // Check last sync time
            $lastSync = CalcomEventMap::max('updated_at');
            $hoursSinceSync = $lastSync ? Carbon::parse($lastSync)->diffInHours() : null;

            $status = 'healthy';
            $messages = [];

            if ($errorMappings > 0) {
                $status = 'warning';
                $messages[] = "$errorMappings event types have sync errors";
                $this->issues[] = "$errorMappings event types with sync errors";
            }

            if ($hoursSinceSync && $hoursSinceSync > 24) {
                $status = 'warning';
                $messages[] = "Event types not synced for {$hoursSinceSync} hours";
            }

            if ($localMappings === 0) {
                $status = 'critical';
                $messages[] = "No event type mappings found";
                $this->issues[] = "No Cal.com event type mappings configured";
            }

            return [
                'status' => $status,
                'message' => empty($messages) ? 'Event types properly synced' : implode(', ', $messages),
                'total_mappings' => $localMappings,
                'synced_mappings' => $syncedMappings,
                'error_mappings' => $errorMappings,
                'last_sync' => $lastSync ? Carbon::parse($lastSync)->toIso8601String() : null
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'warning',
                'message' => 'Could not check event types',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check recent booking activity
     */
    private function checkRecentBookings(): array
    {
        try {
            $last24Hours = Appointment::where('created_at', '>=', Carbon::now()->subDay())
                ->count();

            $last7Days = Appointment::where('created_at', '>=', Carbon::now()->subWeek())
                ->count();

            $failedLast24Hours = Appointment::where('created_at', '>=', Carbon::now()->subDay())
                ->where('status', 'failed')
                ->count();

            $status = 'healthy';
            $messages = [];

            if ($failedLast24Hours > 5) {
                $status = 'warning';
                $messages[] = "$failedLast24Hours failed bookings in last 24 hours";
                $this->issues[] = "High number of failed bookings: $failedLast24Hours";
            }

            if ($last7Days === 0) {
                $status = 'warning';
                $messages[] = "No bookings in the last 7 days";
            }

            $this->metrics['bookings_24h'] = $last24Hours;
            $this->metrics['bookings_7d'] = $last7Days;
            $this->metrics['failed_24h'] = $failedLast24Hours;

            return [
                'status' => $status,
                'message' => empty($messages) ? 'Normal booking activity' : implode(', ', $messages),
                'last_24_hours' => $last24Hours,
                'last_7_days' => $last7Days,
                'failed_last_24_hours' => $failedLast24Hours
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'warning',
                'message' => 'Could not check booking activity',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check webhook processing
     */
    private function checkWebhookProcessing(): array
    {
        try {
            $recentWebhooks = WebhookEvent::where('source', 'calcom')
                ->where('created_at', '>=', Carbon::now()->subDay())
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $processed = $recentWebhooks['processed'] ?? 0;
            $failed = $recentWebhooks['failed'] ?? 0;
            $pending = $recentWebhooks['pending'] ?? 0;

            $total = $processed + $failed + $pending;
            $failureRate = $total > 0 ? round(($failed / $total) * 100, 2) : 0;

            $status = 'healthy';
            $messages = [];

            if ($failureRate > 10) {
                $status = 'warning';
                $messages[] = "High webhook failure rate: {$failureRate}%";
                $this->issues[] = "Webhook failure rate: {$failureRate}%";
            }

            if ($pending > 100) {
                $status = 'warning';
                $messages[] = "$pending webhooks pending processing";
                $this->issues[] = "$pending unprocessed webhooks";
            }

            $this->metrics['webhooks_processed'] = $processed;
            $this->metrics['webhooks_failed'] = $failed;
            $this->metrics['webhooks_pending'] = $pending;

            return [
                'status' => $status,
                'message' => empty($messages) ? 'Webhooks processing normally' : implode(', ', $messages),
                'processed' => $processed,
                'failed' => $failed,
                'pending' => $pending,
                'failure_rate' => $failureRate . '%'
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'warning',
                'message' => 'Could not check webhook processing',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check API error rate
     */
    private function checkErrorRate(): array
    {
        try {
            // Get error rate from logs or cache
            $errorCount = Cache::get('calcom_api_errors_1h', 0);
            $totalCalls = Cache::get('calcom_api_calls_1h', 1);
            $errorRate = round(($errorCount / $totalCalls) * 100, 2);

            $status = 'healthy';
            $message = "Error rate: {$errorRate}%";

            if ($errorRate > 5) {
                $status = 'warning';
                $this->issues[] = "API error rate above threshold: {$errorRate}%";
            }

            if ($errorRate > 15) {
                $status = 'critical';
            }

            $this->metrics['api_error_rate'] = $errorRate;
            $this->metrics['api_errors_1h'] = $errorCount;
            $this->metrics['api_calls_1h'] = $totalCalls;

            return [
                'status' => $status,
                'message' => $message,
                'error_count' => $errorCount,
                'total_calls' => $totalCalls,
                'error_rate' => $errorRate . '%'
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'warning',
                'message' => 'Could not check error rate',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check API response times
     */
    private function checkResponseTime(): array
    {
        try {
            // Measure current response time
            $times = [];
            for ($i = 0; $i < 3; $i++) {
                $start = microtime(true);
                $this->client->getEventTypes();
                $times[] = (microtime(true) - $start) * 1000;
                usleep(100000); // 100ms delay between requests
            }

            $avgTime = round(array_sum($times) / count($times), 2);
            $maxTime = round(max($times), 2);

            $status = 'healthy';
            $message = "Avg response time: {$avgTime}ms";

            if ($avgTime > 1000) {
                $status = 'warning';
                $this->issues[] = "Slow API response time: {$avgTime}ms";
            }

            if ($avgTime > 3000) {
                $status = 'critical';
            }

            $this->metrics['avg_response_time_ms'] = $avgTime;
            $this->metrics['max_response_time_ms'] = $maxTime;

            return [
                'status' => $status,
                'message' => $message,
                'average_ms' => $avgTime,
                'max_ms' => $maxTime,
                'samples' => count($times)
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'warning',
                'message' => 'Could not measure response time',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check database synchronization
     */
    private function checkDatabaseSync(): array
    {
        try {
            // Check for orphaned appointments
            $orphanedAppointments = Appointment::whereNotNull('calcom_v2_booking_id')
                ->where('status', 'booked')
                ->where('updated_at', '<', Carbon::now()->subHours(24))
                ->whereNull('last_sync_at')
                ->count();

            // Check for composite booking integrity
            $brokenComposites = Appointment::where('is_composite', true)
                ->whereJsonLength('segments', '<', 2)
                ->count();

            $status = 'healthy';
            $messages = [];

            if ($orphanedAppointments > 0) {
                $status = 'warning';
                $messages[] = "$orphanedAppointments appointments not synced";
                $this->issues[] = "$orphanedAppointments orphaned appointments";
            }

            if ($brokenComposites > 0) {
                $status = 'warning';
                $messages[] = "$brokenComposites broken composite bookings";
                $this->issues[] = "$brokenComposites composite bookings with missing segments";
            }

            return [
                'status' => $status,
                'message' => empty($messages) ? 'Database properly synced' : implode(', ', $messages),
                'orphaned_appointments' => $orphanedAppointments,
                'broken_composites' => $brokenComposites
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'warning',
                'message' => 'Could not check database sync',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get aggregated metrics
     */
    private function getMetrics(): array
    {
        return array_merge($this->metrics, [
            'timestamp' => Carbon::now()->toIso8601String(),
            'cache_hit_rate' => Cache::get('calcom_cache_hit_rate', 0),
            'active_bookings_today' => Appointment::whereDate('starts_at', Carbon::today())
                ->where('status', 'booked')
                ->count()
        ]);
    }

    /**
     * Get quick health status (cached)
     */
    public static function quickCheck(): array
    {
        return Cache::remember('calcom_quick_health', 30, function() {
            $instance = new self();

            try {
                // Just check API connectivity
                $response = $instance->client->getEventTypes();

                return [
                    'status' => $response->successful() ? 'healthy' : 'unhealthy',
                    'api_reachable' => $response->successful(),
                    'timestamp' => Carbon::now()->toIso8601String()
                ];
            } catch (\Exception $e) {
                return [
                    'status' => 'unhealthy',
                    'api_reachable' => false,
                    'error' => $e->getMessage(),
                    'timestamp' => Carbon::now()->toIso8601String()
                ];
            }
        });
    }

    /**
     * Send alert if critical issues found
     */
    public function alertIfCritical(): void
    {
        $result = $this->check();

        if ($result['status'] === 'critical') {
            Log::critical('Cal.com integration critical issues detected', [
                'issues' => $result['issues'],
                'failed_checks' => array_filter($result['checks'], fn($c) => $c['status'] === 'critical')
            ]);

            // Here you would send alerts via email, Slack, etc.
            // Example: Notification::route('slack', config('monitoring.slack_webhook'))
            //     ->notify(new CalcomCriticalAlert($result));
        }
    }
}