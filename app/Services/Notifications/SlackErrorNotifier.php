<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Slack Error Notifier
 *
 * Sends error notifications to Slack for:
 * - Production errors
 * - Webhook failures
 * - API errors
 * - Performance issues
 */
class SlackErrorNotifier
{
    private ?string $webhookUrl;
    private bool $enabled;
    private string $environment;

    public function __construct()
    {
        $this->webhookUrl = config('services.slack.error_webhook_url');
        $this->enabled = config('services.slack.error_notifications_enabled', false);
        $this->environment = config('app.env');
    }

    /**
     * Send error notification to Slack
     *
     * @param Exception $exception Exception that occurred
     * @param array $context Additional context
     * @return bool Success status
     */
    public function notifyException(Exception $exception, array $context = []): bool
    {
        if (!$this->shouldNotify()) {
            return false;
        }

        try {
            $payload = $this->buildExceptionPayload($exception, $context);
            return $this->sendToSlack($payload);

        } catch (Exception $e) {
            Log::error("Failed to send Slack notification", [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send error message to Slack
     *
     * @param string $title Error title
     * @param string $message Error message
     * @param array $context Additional context
     * @param string $severity Severity level (error, warning, critical)
     * @return bool Success status
     */
    public function notifyError(string $title, string $message, array $context = [], string $severity = 'error'): bool
    {
        if (!$this->shouldNotify()) {
            return false;
        }

        try {
            $payload = $this->buildErrorPayload($title, $message, $context, $severity);
            return $this->sendToSlack($payload);

        } catch (Exception $e) {
            Log::error("Failed to send Slack notification", [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send warning to Slack
     *
     * @param string $title Warning title
     * @param string $message Warning message
     * @param array $context Additional context
     * @return bool Success status
     */
    public function notifyWarning(string $title, string $message, array $context = []): bool
    {
        return $this->notifyError($title, $message, $context, 'warning');
    }

    /**
     * Check if notifications should be sent
     *
     * @return bool Should notify
     */
    private function shouldNotify(): bool
    {
        // Must be enabled
        if (!$this->enabled) {
            return false;
        }

        // Must have webhook URL
        if (empty($this->webhookUrl)) {
            return false;
        }

        // Only production + staging
        if (!in_array($this->environment, ['production', 'staging'])) {
            return false;
        }

        return true;
    }

    /**
     * Build payload for exception
     *
     * @param Exception $exception Exception
     * @param array $context Context
     * @return array Slack payload
     */
    private function buildExceptionPayload(Exception $exception, array $context): array
    {
        $color = $this->getSeverityColor('error');
        $correlationId = $context['correlation_id'] ?? 'N/A';
        $url = request() ? request()->fullUrl() : 'N/A';

        return [
            'text' => "🚨 *Production Error* in {$this->environment}",
            'attachments' => [
                [
                    'color' => $color,
                    'title' => get_class($exception),
                    'text' => $exception->getMessage(),
                    'fields' => [
                        [
                            'title' => 'Environment',
                            'value' => $this->environment,
                            'short' => true,
                        ],
                        [
                            'title' => 'Correlation ID',
                            'value' => $correlationId,
                            'short' => true,
                        ],
                        [
                            'title' => 'URL',
                            'value' => $url,
                            'short' => false,
                        ],
                        [
                            'title' => 'File',
                            'value' => $exception->getFile() . ':' . $exception->getLine(),
                            'short' => false,
                        ],
                    ],
                    'footer' => 'AskPro AI Gateway Error Monitor',
                    'footer_icon' => 'https://platform.slack-edge.com/img/default_application_icon.png',
                    'ts' => now()->timestamp,
                ],
            ],
        ];
    }

    /**
     * Build payload for generic error
     *
     * @param string $title Title
     * @param string $message Message
     * @param array $context Context
     * @param string $severity Severity
     * @return array Slack payload
     */
    private function buildErrorPayload(string $title, string $message, array $context, string $severity): array
    {
        $color = $this->getSeverityColor($severity);
        $icon = $this->getSeverityIcon($severity);
        $correlationId = $context['correlation_id'] ?? 'N/A';

        $fields = [
            [
                'title' => 'Environment',
                'value' => $this->environment,
                'short' => true,
            ],
            [
                'title' => 'Correlation ID',
                'value' => $correlationId,
                'short' => true,
            ],
        ];

        // Add context fields
        foreach ($context as $key => $value) {
            if ($key === 'correlation_id') {
                continue;
            }

            $fields[] = [
                'title' => ucwords(str_replace('_', ' ', $key)),
                'value' => is_array($value) ? json_encode($value) : (string)$value,
                'short' => strlen((string)$value) < 50,
            ];
        }

        return [
            'text' => "{$icon} *{$title}* in {$this->environment}",
            'attachments' => [
                [
                    'color' => $color,
                    'text' => $message,
                    'fields' => $fields,
                    'footer' => 'AskPro AI Gateway Monitor',
                    'footer_icon' => 'https://platform.slack-edge.com/img/default_application_icon.png',
                    'ts' => now()->timestamp,
                ],
            ],
        ];
    }

    /**
     * Send payload to Slack
     *
     * @param array $payload Slack payload
     * @return bool Success status
     */
    private function sendToSlack(array $payload): bool
    {
        try {
            $response = Http::timeout(5)->post($this->webhookUrl, $payload);

            if ($response->successful()) {
                Log::debug("Slack notification sent successfully");
                return true;
            } else {
                Log::warning("Slack notification failed", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

        } catch (Exception $e) {
            Log::error("Failed to send to Slack", [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get color for severity level
     *
     * @param string $severity Severity level
     * @return string Hex color
     */
    private function getSeverityColor(string $severity): string
    {
        return match ($severity) {
            'critical' => '#d32f2f', // Red
            'error' => '#f57c00',    // Orange
            'warning' => '#fbc02d',  // Yellow
            default => '#1976d2',    // Blue
        };
    }

    /**
     * Get icon for severity level
     *
     * @param string $severity Severity level
     * @return string Emoji icon
     */
    private function getSeverityIcon(string $severity): string
    {
        return match ($severity) {
            'critical' => '🔥',
            'error' => '🚨',
            'warning' => '⚠️',
            default => 'ℹ️',
        };
    }
}
