<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Mail\MonitoringAlert;

class AlertingService
{
    private array $config;
    private array $activeAlerts = [];

    public function __construct()
    {
        $this->config = config('monitoring.alerts');
    }

    /**
     * Send an alert for a critical event
     */
    public function alert(string $rule, array $data = []): void
    {
        if (!$this->shouldAlert($rule, $data)) {
            return;
        }

        $ruleConfig = $this->config['rules'][$rule] ?? null;
        if (!$ruleConfig || !($ruleConfig['enabled'] ?? true)) {
            return;
        }

        $alert = [
            'rule' => $rule,
            'severity' => $ruleConfig['severity'] ?? 'medium',
            'message' => $this->buildMessage($rule, $data),
            'data' => $data,
            'timestamp' => now(),
        ];

        // Send to configured channels
        foreach ($ruleConfig['channels'] ?? [] as $channel) {
            $this->sendToChannel($channel, $alert);
        }

        // Record the alert
        $this->recordAlert($rule, $alert);
    }

    /**
     * Check if we should send an alert based on thresholds and windows
     */
    private function shouldAlert(string $rule, array $data): bool
    {
        $ruleConfig = $this->config['rules'][$rule] ?? null;
        if (!$ruleConfig) {
            return false;
        }

        // Check if we're within the throttle window
        $cacheKey = "alert_throttle:$rule";
        if (Cache::has($cacheKey)) {
            return false;
        }

        // Check threshold if applicable
        if (isset($ruleConfig['threshold']) && isset($ruleConfig['window'])) {
            $count = $this->getEventCount($rule, $ruleConfig['window']);
            if ($count < $ruleConfig['threshold']) {
                return false;
            }
        }

        // Set throttle to prevent alert spam
        Cache::put($cacheKey, true, now()->addMinutes(15));

        return true;
    }

    /**
     * Send alert to a specific channel
     */
    private function sendToChannel(string $channel, array $alert): void
    {
        $channelConfig = $this->config['channels'][$channel] ?? null;
        if (!$channelConfig || !($channelConfig['enabled'] ?? false)) {
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
            }
        } catch (\Exception $e) {
            Log::error("Failed to send alert to $channel", [
                'error' => $e->getMessage(),
                'alert' => $alert,
            ]);
        }
    }

    /**
     * Send email alert
     */
    private function sendEmailAlert(array $config, array $alert): void
    {
        $recipients = $config['recipients'] ?? [];
        if (empty($recipients)) {
            return;
        }

        foreach ($recipients as $recipient) {
            Mail::to($recipient)->send(new MonitoringAlert($alert));
        }
    }

    /**
     * Send Slack alert
     */
    private function sendSlackAlert(array $config, array $alert): void
    {
        $webhookUrl = $config['webhook_url'] ?? null;
        if (!$webhookUrl) {
            return;
        }

        $color = match ($alert['severity']) {
            'critical' => '#FF0000',
            'high' => '#FF8C00',
            'medium' => '#FFD700',
            'low' => '#90EE90',
            default => '#808080',
        };

        $payload = [
            'channel' => $config['channel'] ?? '#alerts',
            'username' => 'AskProAI Monitoring',
            'icon_emoji' => ':warning:',
            'attachments' => [
                [
                    'color' => $color,
                    'title' => "Alert: {$alert['rule']}",
                    'text' => $alert['message'],
                    'fields' => [
                        [
                            'title' => 'Severity',
                            'value' => ucfirst($alert['severity']),
                            'short' => true,
                        ],
                        [
                            'title' => 'Time',
                            'value' => $alert['timestamp']->format('Y-m-d H:i:s'),
                            'short' => true,
                        ],
                    ],
                    'footer' => 'AskProAI Monitoring',
                    'ts' => $alert['timestamp']->timestamp,
                ],
            ],
        ];

        Http::post($webhookUrl, $payload);
    }

    /**
     * Send SMS alert (placeholder - implement with your SMS provider)
     */
    private function sendSmsAlert(array $config, array $alert): void
    {
        $recipients = $config['recipients'] ?? [];
        if (empty($recipients)) {
            return;
        }

        // TODO: Implement SMS sending with your provider
        // Example: Twilio, SNS, etc.
        
        Log::info('SMS alert would be sent', [
            'recipients' => $recipients,
            'alert' => $alert,
        ]);
    }

    /**
     * Build alert message based on rule and data
     */
    private function buildMessage(string $rule, array $data): string
    {
        $messages = [
            'payment_failure' => 'Multiple payment failures detected: %d failures in the last %d minutes',
            'security_breach_attempt' => 'Security breach attempt detected: %d attempts from IP %s',
            'stripe_webhook_failure' => 'Stripe webhook processing failures: %d failures in %d minutes',
            'high_error_rate' => 'High error rate detected: %s%% errors in the last %d minutes',
            'database_connection_failure' => 'Database connection failures: %d failures in %d seconds',
            'queue_backlog' => 'Queue backlog detected: %d jobs pending',
            'portal_downtime' => 'Customer portal is down! Immediate action required',
        ];

        $template = $messages[$rule] ?? 'Alert triggered for rule: ' . $rule;

        return sprintf($template, ...array_values($data));
    }

    /**
     * Get event count within a time window
     */
    private function getEventCount(string $rule, int $window): int
    {
        $cacheKey = "alert_events:$rule";
        $events = Cache::get($cacheKey, []);
        
        // Filter events within the window
        $cutoff = now()->subSeconds($window);
        $events = array_filter($events, function ($timestamp) use ($cutoff) {
            return $timestamp > $cutoff;
        });

        return count($events);
    }

    /**
     * Record an alert event
     */
    public function recordEvent(string $rule): void
    {
        $cacheKey = "alert_events:$rule";
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
     * Record alert in database/log
     */
    private function recordAlert(string $rule, array $alert): void
    {
        // Log the alert
        Log::channel('monitoring')->warning('Alert triggered', [
            'rule' => $rule,
            'alert' => $alert,
        ]);

        // Store in cache for dashboard
        $this->activeAlerts[] = $alert;
        Cache::put('active_alerts', $this->activeAlerts, now()->addHours(24));
    }

    /**
     * Get active alerts
     */
    public function getActiveAlerts(): array
    {
        return Cache::get('active_alerts', []);
    }

    /**
     * Clear an alert
     */
    public function clearAlert(string $rule): void
    {
        $this->activeAlerts = array_filter($this->activeAlerts, function ($alert) use ($rule) {
            return $alert['rule'] !== $rule;
        });
        
        Cache::put('active_alerts', $this->activeAlerts, now()->addHours(24));
    }
}