<?php

namespace App\Services\Monitoring;

use App\Mail\MonitoringAlert;
use App\Models\User;
use App\Services\CircuitBreaker\CircuitBreakerManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class UnifiedAlertingService
{
    private array $config;

    private MetricsCollector $metricsCollector;

    private CircuitBreakerManager $circuitBreakerManager;

    public function __construct(
        MetricsCollector $metricsCollector,
        CircuitBreakerManager $circuitBreakerManager
    ) {
        $this->config = config('monitoring.alerts');
        $this->metricsCollector = $metricsCollector;
        $this->circuitBreakerManager = $circuitBreakerManager;
    }

    /**
     * Process an alert based on rule configuration.
     */
    public function alert(string $rule, array $data = []): void
    {
        $ruleConfig = $this->config['rules'][$rule] ?? null;

        if (! $ruleConfig || ! ($ruleConfig['enabled'] ?? true)) {
            return;
        }

        // Check thresholds and windows
        if (! $this->shouldAlert($rule, $data, $ruleConfig)) {
            return;
        }

        $alert = [
            'id' => \Str::uuid()->toString(),
            'rule' => $rule,
            'severity' => $ruleConfig['severity'] ?? 'medium',
            'message' => $this->buildMessage($rule, $data),
            'data' => $data,
            'timestamp' => now(),
        ];

        // Record the alert
        $this->recordAlert($alert);

        // Send to configured channels
        foreach ($ruleConfig['channels'] ?? [] as $channel) {
            $this->sendToChannel($channel, $alert);
        }

        // Update metrics
        $this->metricsCollector->incrementCounter('alerts_triggered', [
            'rule' => $rule,
            'severity' => $alert['severity'],
        ]);
    }

    /**
     * Check system health and trigger alerts.
     */
    public function checkSystemHealth(): array
    {
        $alerts = [];

        // Payment failures
        if ($failures = $this->checkPaymentFailures()) {
            $alerts[] = ['rule' => 'payment_failure', 'data' => $failures];
        }

        // Security breach attempts
        if ($breaches = $this->checkSecurityBreaches()) {
            $alerts[] = ['rule' => 'security_breach_attempt', 'data' => $breaches];
        }

        // Stripe webhook failures
        if ($webhookFailures = $this->checkStripeWebhookFailures()) {
            $alerts[] = ['rule' => 'stripe_webhook_failure', 'data' => $webhookFailures];
        }

        // High error rate
        if ($errorRate = $this->checkErrorRate()) {
            $alerts[] = ['rule' => 'high_error_rate', 'data' => $errorRate];
        }

        // Database connection failures
        if ($dbFailures = $this->checkDatabaseConnections()) {
            $alerts[] = ['rule' => 'database_connection_failure', 'data' => $dbFailures];
        }

        // Queue backlog
        if ($queueBacklog = $this->checkQueueBacklog()) {
            $alerts[] = ['rule' => 'queue_backlog', 'data' => $queueBacklog];
        }

        // Portal downtime (check circuit breakers)
        if ($this->checkPortalDowntime()) {
            $alerts[] = ['rule' => 'portal_downtime', 'data' => []];
        }

        // Process all alerts
        foreach ($alerts as $alert) {
            $this->alert($alert['rule'], $alert['data']);
        }

        return $alerts;
    }

    /**
     * Check if alert should be sent based on rules.
     */
    private function shouldAlert(string $rule, array $data, array $ruleConfig): bool
    {
        // Check throttling
        $throttleKey = "alert_throttle:$rule";
        if (Cache::has($throttleKey)) {
            return false;
        }

        // Check threshold and window
        if (isset($ruleConfig['threshold']) && isset($ruleConfig['window'])) {
            $events = $this->getRecentEvents($rule, $ruleConfig['window']);

            if (count($events) < $ruleConfig['threshold']) {
                return false;
            }
        }

        // Set throttle
        $throttleMinutes = $this->config['rules']['throttle_minutes'] ?? 15;
        Cache::put($throttleKey, true, now()->addMinutes($throttleMinutes));

        return true;
    }

    /**
     * Send alert to specific channel.
     */
    private function sendToChannel(string $channel, array $alert): void
    {
        $channelConfig = $this->config['channels'][$channel] ?? null;

        if (! $channelConfig || ! ($channelConfig['enabled'] ?? false)) {
            return;
        }

        try {
            switch ($channel) {
                case 'email':
                    $this->sendEmailAlert($channelConfig, $alert);

                    break;
                case 'slack':
                    $this->sendSlackAlert($channelConfig, $alert);

                    break;
                case 'sms':
                    $this->sendSmsAlert($channelConfig, $alert);

                    break;
                default:
                    Log::warning("Unknown alert channel: {$channel}");
            }
        } catch (\Exception $e) {
            Log::error("Failed to send alert to {$channel}", [
                'error' => $e->getMessage(),
                'alert' => $alert,
            ]);
        }
    }

    /**
     * Send email alert.
     */
    private function sendEmailAlert(array $config, array $alert): void
    {
        $recipients = $config['recipients'] ?? [];

        // Fall back to admin users if no recipients configured
        if (empty($recipients)) {
            $recipients = User::whereHas('roles', function ($query) {
                $query->where('name', 'admin');
            })->pluck('email')->toArray();
        }

        if (empty($recipients)) {
            Log::warning('No email recipients configured for alerts');

            return;
        }

        foreach ($recipients as $recipient) {
            Mail::to($recipient)->queue(new MonitoringAlert($alert));
        }
    }

    /**
     * Send Slack alert with rich formatting.
     */
    private function sendSlackAlert(array $config, array $alert): void
    {
        $webhookUrl = $config['webhook_url'] ?? null;
        if (! $webhookUrl) {
            Log::warning('No Slack webhook URL configured');

            return;
        }

        $color = match ($alert['severity']) {
            'critical' => '#FF0000',
            'high' => '#FF8C00',
            'medium' => '#FFD700',
            'low' => '#90EE90',
            default => '#808080',
        };

        $emoji = match ($alert['severity']) {
            'critical' => ':rotating_light:',
            'high' => ':warning:',
            'medium' => ':information_source:',
            'low' => ':white_check_mark:',
            default => ':grey_question:',
        };

        $fields = [];
        foreach ($alert['data'] as $key => $value) {
            if (! is_array($value)) {
                $fields[] = [
                    'title' => ucfirst(str_replace('_', ' ', $key)),
                    'value' => (string) $value,
                    'short' => strlen((string) $value) < 30,
                ];
            }
        }

        $payload = [
            'channel' => $config['channel'] ?? '#alerts',
            'username' => 'AskProAI Monitoring',
            'icon_emoji' => $emoji,
            'attachments' => [
                [
                    'color' => $color,
                    'title' => "{$emoji} Alert: {$alert['rule']}",
                    'text' => $alert['message'],
                    'fields' => $fields,
                    'footer' => 'AskProAI Monitoring System',
                    'footer_icon' => 'https://api.askproai.de/images/logo-icon.png',
                    'ts' => $alert['timestamp']->timestamp,
                    'actions' => [
                        [
                            'type' => 'button',
                            'text' => 'View Dashboard',
                            'url' => config('app.url') . '/admin/monitoring',
                            'style' => $alert['severity'] === 'critical' ? 'danger' : 'primary',
                        ],
                    ],
                ],
            ],
        ];

        Http::timeout(5)->post($webhookUrl, $payload);
    }

    /**
     * Send SMS alert (placeholder for implementation).
     */
    private function sendSmsAlert(array $config, array $alert): void
    {
        $recipients = $config['recipients'] ?? [];

        if (empty($recipients)) {
            return;
        }

        // Only send SMS for critical alerts by default
        if ($alert['severity'] !== 'critical' && ! ($config['all_severities'] ?? false)) {
            return;
        }

        $message = sprintf(
            '[%s] AskProAI Alert: %s - %s',
            strtoupper($alert['severity']),
            $alert['rule'],
            $alert['message']
        );

        // TODO: Implement with your SMS provider (Twilio, AWS SNS, etc.)
        Log::info('SMS alert would be sent', [
            'recipients' => $recipients,
            'message' => $message,
        ]);
    }

    /**
     * Build alert message.
     */
    private function buildMessage(string $rule, array $data): string
    {
        $templates = [
            'payment_failure' => 'Payment failures detected: %d failures in the last %d minutes',
            'security_breach_attempt' => 'Security breach attempts: %d attempts from %s in %d seconds',
            'stripe_webhook_failure' => 'Stripe webhook failures: %d failures in %d minutes',
            'high_error_rate' => 'High error rate: %.2f%% errors in the last %d minutes',
            'database_connection_failure' => 'Database connection failures: %d failures in %d seconds',
            'queue_backlog' => 'Queue backlog alert: %d jobs pending (threshold: %d)',
            'portal_downtime' => 'Customer portal is DOWN! Immediate action required.',
        ];

        $template = $templates[$rule] ?? "Alert triggered: {$rule}";

        if (str_contains($template, '%')) {
            return sprintf($template, ...array_values($data));
        }

        return $template;
    }

    /**
     * Check payment failures.
     */
    private function checkPaymentFailures(): ?array
    {
        $window = $this->config['rules']['payment_failure']['window'] ?? 300;
        $threshold = $this->config['rules']['payment_failure']['threshold'] ?? 3;

        $failures = DB::table('payment_failures')
            ->where('created_at', '>=', now()->subSeconds($window))
            ->count();

        if ($failures >= $threshold) {
            return [
                'count' => $failures,
                'window' => $window / 60,
            ];
        }

        return null;
    }

    /**
     * Check security breach attempts.
     */
    private function checkSecurityBreaches(): ?array
    {
        $window = $this->config['rules']['security_breach_attempt']['window'] ?? 60;
        $threshold = $this->config['rules']['security_breach_attempt']['threshold'] ?? 5;

        $breaches = DB::table('security_logs')
            ->where('created_at', '>=', now()->subSeconds($window))
            ->where('type', 'breach_attempt')
            ->groupBy('ip_address')
            ->havingRaw('COUNT(*) >= ?', [$threshold])
            ->first();

        if ($breaches) {
            return [
                'count' => $breaches->total ?? $threshold,
                'ip' => $breaches->ip_address ?? 'multiple',
                'window' => $window,
            ];
        }

        return null;
    }

    /**
     * Check Stripe webhook failures.
     */
    private function checkStripeWebhookFailures(): ?array
    {
        $window = $this->config['rules']['stripe_webhook_failure']['window'] ?? 300;
        $threshold = $this->config['rules']['stripe_webhook_failure']['threshold'] ?? 5;

        $failures = DB::table('webhook_events')
            ->where('created_at', '>=', now()->subSeconds($window))
            ->where('provider', 'stripe')
            ->where('status', 'failed')
            ->count();

        if ($failures >= $threshold) {
            return [
                'count' => $failures,
                'window' => $window / 60,
            ];
        }

        return null;
    }

    /**
     * Check error rate.
     */
    private function checkErrorRate(): ?array
    {
        $window = $this->config['rules']['high_error_rate']['window'] ?? 300;
        $threshold = $this->config['rules']['high_error_rate']['threshold'] ?? 0.05;

        $stats = DB::table('api_call_logs')
            ->where('created_at', '>=', now()->subSeconds($window))
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN status_code >= 500 THEN 1 ELSE 0 END) as errors')
            ->first();

        if ($stats && $stats->total > 0) {
            $errorRate = $stats->errors / $stats->total;

            if ($errorRate >= $threshold) {
                return [
                    'rate' => $errorRate * 100,
                    'window' => $window / 60,
                ];
            }
        }

        return null;
    }

    /**
     * Check database connections.
     */
    private function checkDatabaseConnections(): ?array
    {
        $window = $this->config['rules']['database_connection_failure']['window'] ?? 60;
        $threshold = $this->config['rules']['database_connection_failure']['threshold'] ?? 3;

        try {
            // Check if we can connect to database
            DB::connection()->getPdo();

            // Check recent connection failures in logs
            $failures = Cache::get('db_connection_failures', 0);

            if ($failures >= $threshold) {
                return [
                    'count' => $failures,
                    'window' => $window,
                ];
            }
        } catch (\Exception $e) {
            // Increment failure counter
            Cache::increment('db_connection_failures');
            Cache::put('db_connection_failures_expire', true, $window);

            return [
                'count' => $threshold,
                'window' => $window,
            ];
        }

        return null;
    }

    /**
     * Check queue backlog.
     */
    private function checkQueueBacklog(): ?array
    {
        $threshold = $this->config['rules']['queue_backlog']['threshold'] ?? 1000;

        $backlog = DB::table('jobs')->count();

        if ($backlog >= $threshold) {
            return [
                'count' => $backlog,
                'threshold' => $threshold,
            ];
        }

        return null;
    }

    /**
     * Check portal downtime.
     */
    private function checkPortalDowntime(): bool
    {
        // Check if any critical service circuit breakers are open
        $criticalServices = ['stripe', 'calcom', 'database'];

        foreach ($criticalServices as $service) {
            $status = $this->circuitBreakerManager->getStatus($service);
            if ($status['state'] === 'open') {
                return true;
            }
        }

        // Check if portal endpoint is responding
        try {
            $response = Http::timeout(5)->get(config('app.url') . '/health');

            return ! $response->successful();
        } catch (\Exception $e) {
            return true;
        }
    }

    /**
     * Get recent events for a rule.
     */
    private function getRecentEvents(string $rule, int $window): array
    {
        $cacheKey = "alert_events:{$rule}";
        $events = Cache::get($cacheKey, []);

        // Filter events within window
        $cutoff = now()->subSeconds($window);

        return array_filter($events, function ($timestamp) use ($cutoff) {
            return $timestamp > $cutoff;
        });
    }

    /**
     * Record an event for threshold checking.
     */
    public function recordEvent(string $rule): void
    {
        $cacheKey = "alert_events:{$rule}";
        $events = Cache::get($cacheKey, []);

        // Add new event
        $events[] = now();

        // Keep only recent events (last hour)
        $cutoff = now()->subHour();
        $events = array_filter($events, function ($timestamp) use ($cutoff) {
            return $timestamp > $cutoff;
        });

        Cache::put($cacheKey, array_values($events), now()->addHours(2));
    }

    /**
     * Record alert in database.
     */
    private function recordAlert(array $alert): void
    {
        DB::table('system_alerts')->insert([
            'id' => $alert['id'],
            'rule' => $alert['rule'],
            'severity' => $alert['severity'],
            'message' => $alert['message'],
            'data' => json_encode($alert['data']),
            'created_at' => $alert['timestamp'],
        ]);

        // Log the alert
        Log::channel('monitoring')->warning('Alert triggered', $alert);
    }

    /**
     * Get active alerts.
     */
    public function getActiveAlerts(int $hours = 24): array
    {
        return DB::table('system_alerts')
            ->where('created_at', '>=', now()->subHours($hours))
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($alert) {
                $alert->data = json_decode($alert->data, true);

                return $alert;
            })
            ->toArray();
    }

    /**
     * Test alert system.
     */
    public function testAlert(?string $channel = null): void
    {
        $testAlert = [
            'id' => \Str::uuid()->toString(),
            'rule' => 'test_alert',
            'severity' => 'medium',
            'message' => 'This is a test alert from AskProAI monitoring system',
            'data' => [
                'test' => true,
                'timestamp' => now()->toDateTimeString(),
                'environment' => config('app.env'),
            ],
            'timestamp' => now(),
        ];

        if ($channel) {
            $this->sendToChannel($channel, $testAlert);
        } else {
            // Send to all enabled channels
            foreach ($this->config['channels'] as $channelName => $channelConfig) {
                if ($channelConfig['enabled'] ?? false) {
                    $this->sendToChannel($channelName, $testAlert);
                }
            }
        }
    }
}
