<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackNotificationService
{
    private string $webhookUrl;

    private string $channel;

    private string $username;

    private string $iconEmoji;

    public function __construct()
    {
        $this->webhookUrl = config('monitoring.alerts.channels.slack.webhook_url', '');
        $this->channel = config('monitoring.alerts.channels.slack.channel', '#alerts');
        $this->username = 'AskProAI Bot';
        $this->iconEmoji = ':robot_face:';
    }

    /**
     * Send a simple message to Slack.
     */
    public function sendMessage(string $message, ?string $channel = null): bool
    {
        if (empty($this->webhookUrl)) {
            Log::warning('Slack webhook URL not configured');

            return false;
        }

        $payload = [
            'channel' => $channel ?? $this->channel,
            'username' => $this->username,
            'icon_emoji' => $this->iconEmoji,
            'text' => $message,
        ];

        return $this->send($payload);
    }

    /**
     * Send a rich formatted message with attachments.
     */
    public function sendRichMessage(
        string $title,
        string $message,
        string $severity = 'info',
        array $fields = [],
        array $actions = [],
        ?string $channel = null
    ): bool {
        if (empty($this->webhookUrl)) {
            Log::warning('Slack webhook URL not configured');

            return false;
        }

        $color = $this->getColorForSeverity($severity);
        $emoji = $this->getEmojiForSeverity($severity);

        $attachment = [
            'color' => $color,
            'title' => "{$emoji} {$title}",
            'text' => $message,
            'footer' => 'AskProAI System',
            'footer_icon' => 'https://api.askproai.de/images/logo-icon.png',
            'ts' => time(),
        ];

        if (! empty($fields)) {
            $attachment['fields'] = $this->formatFields($fields);
        }

        if (! empty($actions)) {
            $attachment['actions'] = $this->formatActions($actions);
        }

        $payload = [
            'channel' => $channel ?? $this->channel,
            'username' => $this->username,
            'icon_emoji' => $this->iconEmoji,
            'attachments' => [$attachment],
        ];

        return $this->send($payload);
    }

    /**
     * Send a system status update.
     */
    public function sendStatusUpdate(array $metrics, ?string $channel = null): bool
    {
        $fields = [];
        $overallStatus = 'good';

        foreach ($metrics as $metric => $data) {
            $status = $this->determineMetricStatus($metric, $data['value']);
            if ($status !== 'good') {
                $overallStatus = $status === 'danger' ? 'danger' : 'warning';
            }

            $fields[] = [
                'title' => $data['label'] ?? $metric,
                'value' => $this->formatMetricValue($data['value'], $data['unit'] ?? ''),
                'short' => true,
            ];
        }

        $title = 'System Status Update';
        $message = $overallStatus === 'good'
            ? 'All systems operating normally'
            : 'Some systems require attention';

        return $this->sendRichMessage(
            $title,
            $message,
            $overallStatus === 'danger' ? 'critical' : ($overallStatus === 'warning' ? 'high' : 'info'),
            $fields,
            [
                [
                    'text' => 'View Dashboard',
                    'url' => config('app.url') . '/admin/monitoring',
                ],
            ],
            $channel
        );
    }

    /**
     * Send deployment notification.
     */
    public function sendDeploymentNotification(
        string $version,
        string $environment,
        array $changes = [],
        ?string $channel = null
    ): bool {
        $fields = [
            ['title' => 'Version', 'value' => $version, 'short' => true],
            ['title' => 'Environment', 'value' => $environment, 'short' => true],
            ['title' => 'Deployed By', 'value' => exec('whoami'), 'short' => true],
            ['title' => 'Time', 'value' => now()->format('Y-m-d H:i:s'), 'short' => true],
        ];

        if (! empty($changes)) {
            $changesList = implode("\n", array_map(fn ($change) => "• {$change}", $changes));
            $fields[] = ['title' => 'Changes', 'value' => $changesList, 'short' => false];
        }

        return $this->sendRichMessage(
            'Deployment Notification',
            "New version deployed to {$environment}",
            'info',
            $fields,
            [],
            $channel ?? '#deployments'
        );
    }

    /**
     * Send error notification.
     */
    public function sendErrorNotification(
        string $service,
        string $error,
        array $context = [],
        ?string $channel = null
    ): bool {
        $fields = [
            ['title' => 'Service', 'value' => $service, 'short' => true],
            ['title' => 'Time', 'value' => now()->format('H:i:s'), 'short' => true],
        ];

        if (! empty($context)) {
            foreach ($context as $key => $value) {
                if (is_scalar($value)) {
                    $fields[] = [
                        'title' => ucfirst(str_replace('_', ' ', $key)),
                        'value' => (string) $value,
                        'short' => strlen((string) $value) < 30,
                    ];
                }
            }
        }

        return $this->sendRichMessage(
            'Error Alert',
            $error,
            'critical',
            $fields,
            [
                [
                    'text' => 'View Logs',
                    'url' => config('app.url') . '/admin/logs',
                ],
            ],
            $channel
        );
    }

    /**
     * Actually send the payload to Slack.
     */
    private function send(array $payload): bool
    {
        try {
            $response = Http::timeout(5)
                ->post($this->webhookUrl, $payload);

            if (! $response->successful()) {
                Log::error('Failed to send Slack notification', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Exception sending Slack notification', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get color for severity level.
     */
    private function getColorForSeverity(string $severity): string
    {
        return match ($severity) {
            'critical', 'danger' => '#FF0000',
            'high', 'warning' => '#FF8C00',
            'medium' => '#FFD700',
            'low', 'good' => '#36A64F',
            'info' => '#3AA3E3',
            default => '#808080',
        };
    }

    /**
     * Get emoji for severity level.
     */
    private function getEmojiForSeverity(string $severity): string
    {
        return match ($severity) {
            'critical', 'danger' => ':rotating_light:',
            'high', 'warning' => ':warning:',
            'medium' => ':information_source:',
            'low', 'good' => ':white_check_mark:',
            'info' => ':information_source:',
            default => ':grey_question:',
        };
    }

    /**
     * Format fields for Slack.
     */
    private function formatFields(array $fields): array
    {
        return array_map(function ($field) {
            if (is_array($field) && isset($field['title']) && isset($field['value'])) {
                return $field;
            }

            // Convert simple key-value pairs
            if (! is_array($field)) {
                return [
                    'title' => 'Value',
                    'value' => (string) $field,
                    'short' => true,
                ];
            }

            return $field;
        }, $fields);
    }

    /**
     * Format actions for Slack.
     */
    private function formatActions(array $actions): array
    {
        return array_map(function ($action) {
            $formatted = [
                'type' => 'button',
                'text' => $action['text'] ?? 'Action',
                'url' => $action['url'] ?? '#',
            ];

            if (isset($action['style'])) {
                $formatted['style'] = $action['style'];
            }

            return $formatted;
        }, $actions);
    }

    /**
     * Determine metric status.
     */
    private function determineMetricStatus(string $metric, $value): string
    {
        $thresholds = [
            'api_success_rate' => ['good' => 95, 'warning' => 90, 'danger' => 80],
            'response_time' => ['good' => 200, 'warning' => 500, 'danger' => 1000],
            'queue_size' => ['good' => 100, 'warning' => 500, 'danger' => 1000],
            'error_rate' => ['good' => 1, 'warning' => 5, 'danger' => 10],
            'cpu_usage' => ['good' => 50, 'warning' => 70, 'danger' => 90],
            'memory_usage' => ['good' => 60, 'warning' => 80, 'danger' => 90],
            'disk_usage' => ['good' => 70, 'warning' => 85, 'danger' => 95],
        ];

        if (! isset($thresholds[$metric])) {
            return 'good';
        }

        $threshold = $thresholds[$metric];
        $isReversed = in_array($metric, ['api_success_rate']); // Higher is better

        if ($isReversed) {
            if ($value >= $threshold['good']) {
                return 'good';
            }
            if ($value >= $threshold['warning']) {
                return 'warning';
            }

            return 'danger';
        } else {
            if ($value <= $threshold['good']) {
                return 'good';
            }
            if ($value <= $threshold['warning']) {
                return 'warning';
            }

            return 'danger';
        }
    }

    /**
     * Format metric value with unit.
     */
    private function formatMetricValue($value, string $unit): string
    {
        if (is_numeric($value)) {
            $value = round($value, 2);
        }

        return $unit ? "{$value}{$unit}" : (string) $value;
    }
}
