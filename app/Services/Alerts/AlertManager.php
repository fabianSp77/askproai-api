<?php

namespace App\Services\Alerts;

use App\Services\Monitoring\UnifiedAlertingService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Carbon\Carbon;

/**
 * Legacy AlertManager - Delegates to UnifiedAlertingService
 * @deprecated Use UnifiedAlertingService directly
 */
class AlertManager
{
    private UnifiedAlertingService $unifiedAlertingService;
    private array $alertChannels = [];
    private array $alertRules = [];
    
    public function __construct(UnifiedAlertingService $unifiedAlertingService = null)
    {
        $this->unifiedAlertingService = $unifiedAlertingService ?? app(UnifiedAlertingService::class);
        $this->loadConfiguration();
    }
    
    /**
     * Send an alert for a critical error
     * @deprecated Use UnifiedAlertingService::alert() instead
     */
    public function sendCriticalAlert(string $service, string $errorType, string $message, array $context = []): void
    {
        // Map to unified alerting service
        $rule = $this->mapErrorTypeToRule($errorType);
        $data = array_merge($context, [
            'service' => $service,
            'message' => $message,
        ]);
        
        $this->unifiedAlertingService->alert($rule, $data);
    }
    
    /**
     * Check system health and send alerts if needed
     * @deprecated Use UnifiedAlertingService::checkSystemHealth() instead
     */
    public function checkSystemHealth(): array
    {
        return $this->unifiedAlertingService->checkSystemHealth();
    }
    
    /**
     * Map legacy error types to unified alert rules
     */
    private function mapErrorTypeToRule(string $errorType): string
    {
        $mapping = [
            'api_degraded' => 'high_error_rate',
            'circuit_breaker_open' => 'portal_downtime',
            'api_down' => 'portal_downtime',
            'database_connection_failed' => 'database_connection_failure',
            'disk_space_critical' => 'high_error_rate',
            'disk_space_low' => 'high_error_rate',
            'slow_response' => 'high_error_rate',
            'high_error_rate' => 'high_error_rate',
            'database_size_large' => 'queue_backlog',
        ];
        
        return $mapping[$errorType] ?? 'high_error_rate';
    }
    
    /**
     * Check API health metrics
     */
    private function checkApiHealth(): array
    {
        $alerts = [];
        $threshold = $this->alertRules['api_success_rate_threshold'] ?? 90;
        $hourAgo = now()->subHour();
        
        $metrics = DB::table('circuit_breaker_metrics')
            ->select('service', DB::raw('COUNT(*) as total'), DB::raw('SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as success'))
            ->where('created_at', '>=', $hourAgo)
            ->groupBy('service')
            ->get();
        
        foreach ($metrics as $metric) {
            $successRate = $metric->total > 0 ? ($metric->success / $metric->total) * 100 : 100;
            
            if ($successRate < $threshold) {
                $alerts[] = [
                    'service' => $metric->service,
                    'type' => 'api_degraded',
                    'message' => "API success rate below threshold: {$successRate}%",
                    'context' => [
                        'success_rate' => $successRate,
                        'threshold' => $threshold,
                        'total_calls' => $metric->total,
                        'failed_calls' => $metric->total - $metric->success,
                    ],
                ];
            }
        }
        
        // Check circuit breaker states
        $circuitBreakerStatus = \App\Services\CircuitBreaker\CircuitBreaker::getStatus();
        foreach ($circuitBreakerStatus as $service => $status) {
            if ($status['state'] === 'open') {
                $alerts[] = [
                    'service' => $service,
                    'type' => 'circuit_breaker_open',
                    'message' => "Circuit breaker OPEN for {$service}",
                    'context' => [
                        'failures' => $status['failures'],
                        'last_failure' => $status['last_failure'],
                    ],
                ];
            }
        }
        
        return $alerts;
    }
    
    /**
     * Check error rates
     */
    private function checkErrorRates(): array
    {
        $alerts = [];
        $threshold = $this->alertRules['error_rate_threshold'] ?? 10;
        $timeWindow = now()->subMinutes(15);
        
        $errorCount = DB::table('critical_errors')
            ->where('created_at', '>=', $timeWindow)
            ->count();
        
        if ($errorCount > $threshold) {
            $topErrors = DB::table('critical_errors')
                ->select('service', 'error_type', DB::raw('COUNT(*) as count'))
                ->where('created_at', '>=', $timeWindow)
                ->groupBy('service', 'error_type')
                ->orderBy('count', 'desc')
                ->limit(5)
                ->get();
            
            $alerts[] = [
                'service' => 'system',
                'type' => 'high_error_rate',
                'message' => "High error rate detected: {$errorCount} errors in 15 minutes",
                'context' => [
                    'error_count' => $errorCount,
                    'threshold' => $threshold,
                    'top_errors' => $topErrors->toArray(),
                ],
            ];
        }
        
        return $alerts;
    }
    
    /**
     * Check performance metrics
     */
    private function checkPerformance(): array
    {
        $alerts = [];
        $threshold = $this->alertRules['response_time_threshold'] ?? 2000; // 2 seconds
        $hourAgo = now()->subHour();
        
        $slowApis = DB::table('circuit_breaker_metrics')
            ->select('service', DB::raw('AVG(duration_ms) as avg_duration'), DB::raw('MAX(duration_ms) as max_duration'))
            ->where('created_at', '>=', $hourAgo)
            ->where('status', 'success')
            ->groupBy('service')
            ->having('avg_duration', '>', $threshold)
            ->get();
        
        foreach ($slowApis as $api) {
            $alerts[] = [
                'service' => $api->service,
                'type' => 'slow_response',
                'message' => "API response time above threshold",
                'context' => [
                    'avg_duration' => round($api->avg_duration),
                    'max_duration' => round($api->max_duration),
                    'threshold' => $threshold,
                ],
            ];
        }
        
        return $alerts;
    }
    
    /**
     * Check system resources
     */
    private function checkSystemResources(): array
    {
        $alerts = [];
        
        // Check disk space
        $diskFreeSpace = disk_free_space('/');
        $diskTotalSpace = disk_total_space('/');
        $diskUsagePercent = (($diskTotalSpace - $diskFreeSpace) / $diskTotalSpace) * 100;
        
        if ($diskUsagePercent > 85) {
            $alerts[] = [
                'service' => 'system',
                'type' => 'disk_space_low',
                'message' => "Disk space usage critical: " . round($diskUsagePercent, 1) . "%",
                'context' => [
                    'disk_usage_percent' => $diskUsagePercent,
                    'free_space_gb' => round($diskFreeSpace / 1024 / 1024 / 1024, 2),
                ],
            ];
        }
        
        // Check database size
        $dbSize = DB::select("SELECT 
            SUM(data_length + index_length) / 1024 / 1024 AS size_mb 
            FROM information_schema.tables 
            WHERE table_schema = ?", [config('database.connections.mysql.database')])[0]->size_mb ?? 0;
        
        if ($dbSize > 5000) { // 5GB
            $alerts[] = [
                'service' => 'database',
                'type' => 'database_size_large',
                'message' => "Database size exceeds 5GB: " . round($dbSize / 1024, 2) . "GB",
                'context' => [
                    'size_mb' => $dbSize,
                    'size_gb' => round($dbSize / 1024, 2),
                ],
            ];
        }
        
        return $alerts;
    }
    
    /**
     * Store alert in database
     */
    private function storeAlert(string $service, string $errorType, string $message, array $context): string
    {
        $alertId = \Str::uuid()->toString();
        
        DB::table('system_alerts')->insert([
            'id' => $alertId,
            'service' => $service,
            'type' => $errorType,
            'message' => $message,
            'context' => json_encode($context),
            'severity' => $this->getSeverity($errorType),
            'created_at' => now(),
        ]);
        
        return $alertId;
    }
    
    /**
     * Send alert to specific channel
     */
    private function sendToChannel(string $channel, string $service, string $errorType, string $message, array $context, string $alertId): void
    {
        switch ($channel) {
            case 'email':
                $this->sendEmailAlert($service, $errorType, $message, $context, $alertId);
                break;
                
            case 'slack':
                $this->sendSlackAlert($service, $errorType, $message, $context, $alertId);
                break;
                
            case 'webhook':
                $this->sendWebhookAlert($service, $errorType, $message, $context, $alertId);
                break;
                
            case 'database':
                // Already stored in storeAlert()
                break;
                
            default:
                Log::warning("Unknown alert channel: {$channel}");
        }
    }
    
    /**
     * Send email alert
     */
    private function sendEmailAlert(string $service, string $errorType, string $message, array $context, string $alertId): void
    {
        $recipients = $this->alertChannels['email']['recipients'] ?? [];
        
        if (empty($recipients)) {
            // Get admin users - for now just use the first user
            $recipients = User::limit(1)
                ->pluck('email')
                ->toArray();
        }
        
        if (empty($recipients)) {
            Log::warning('No email recipients configured for alerts');
            return;
        }
        
        try {
            $severity = $this->getSeverity($errorType);
            
            Mail::send('emails.critical-alert', [
                'service' => $service,
                'errorType' => $errorType,
                'message' => $message,
                'context' => $context,
                'alertId' => $alertId,
                'severity' => $severity,
                'timestamp' => now(),
            ], function ($mail) use ($recipients, $service, $errorType, $severity) {
                $subject = "[" . strtoupper($severity) . "] AskProAI Alert: {$service} - {$errorType}";
                $mail->to($recipients)->subject($subject);
            });
        } catch (\Exception $e) {
            Log::error('Failed to send email alert', [
                'error' => $e->getMessage(),
                'alert_id' => $alertId,
            ]);
        }
    }
    
    /**
     * Send Slack alert (placeholder)
     */
    private function sendSlackAlert(string $service, string $errorType, string $message, array $context, string $alertId): void
    {
        // TODO: Implement Slack integration
        Log::info('Slack alert would be sent', [
            'service' => $service,
            'type' => $errorType,
            'message' => $message,
        ]);
    }
    
    /**
     * Send webhook alert
     */
    private function sendWebhookAlert(string $service, string $errorType, string $message, array $context, string $alertId): void
    {
        $webhookUrl = $this->alertChannels['webhook']['url'] ?? null;
        
        if (!$webhookUrl) {
            return;
        }
        
        try {
            \Http::timeout(5)->post($webhookUrl, [
                'alert_id' => $alertId,
                'service' => $service,
                'type' => $errorType,
                'message' => $message,
                'context' => $context,
                'severity' => $this->getSeverity($errorType),
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send webhook alert', [
                'error' => $e->getMessage(),
                'webhook_url' => $webhookUrl,
            ]);
        }
    }
    
    /**
     * Check if alert should be throttled
     */
    private function shouldThrottle(string $service, string $errorType): bool
    {
        $key = "alert_throttle:{$service}:{$errorType}";
        $throttleMinutes = $this->alertRules['throttle_minutes'] ?? 15;
        
        return Cache::has($key);
    }
    
    /**
     * Update throttle cache
     */
    private function updateThrottle(string $service, string $errorType): void
    {
        $key = "alert_throttle:{$service}:{$errorType}";
        $throttleMinutes = $this->alertRules['throttle_minutes'] ?? 15;
        
        Cache::put($key, true, now()->addMinutes($throttleMinutes));
    }
    
    /**
     * Get severity level for error type
     */
    private function getSeverity(string $errorType): string
    {
        $criticalTypes = [
            'circuit_breaker_open',
            'api_down',
            'database_connection_failed',
            'disk_space_critical',
        ];
        
        $highTypes = [
            'api_degraded',
            'high_error_rate',
            'slow_response',
            'disk_space_low',
        ];
        
        if (in_array($errorType, $criticalTypes)) {
            return 'critical';
        }
        
        if (in_array($errorType, $highTypes)) {
            return 'high';
        }
        
        return 'medium';
    }
    
    /**
     * Load configuration
     */
    private function loadConfiguration(): void
    {
        $this->alertChannels = config('alerts.channels', [
            'email' => ['enabled' => true],
            'database' => ['enabled' => true],
            'slack' => ['enabled' => false],
            'webhook' => ['enabled' => false],
        ]);
        
        $this->alertRules = config('alerts.rules', [
            'api_success_rate_threshold' => 90,
            'error_rate_threshold' => 10,
            'response_time_threshold' => 2000,
            'throttle_minutes' => 15,
        ]);
    }
}