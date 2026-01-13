<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ServiceGatewayExchangeLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

/**
 * WebhookDeliveryFailedNotification
 *
 * Sends professional HTML email alerts when webhook deliveries fail.
 * Features:
 * - Color-coded error types (semantic/HTTP/exception)
 * - Dark mode support
 * - Mobile-responsive design
 * - Clear visual hierarchy for quick scanning
 *
 * Error Types:
 * - Semantic: HTTP 200 but error in response body
 * - HTTP: Server returned 4xx/5xx status codes
 * - Exception: Connection failures, timeouts, PHP exceptions
 *
 * @package App\Notifications
 */
class WebhookDeliveryFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param Collection<int, ServiceGatewayExchangeLog> $failedLogs
     * @param string $period Human-readable period description
     */
    public function __construct(
        public Collection $failedLogs,
        public string $period = 'letzte Stunde'
    ) {
        $this->queue = 'notifications';
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $count = $this->failedLogs->count();

        // Group errors by type for summary
        $semanticCount = $this->failedLogs->filter(fn ($log) => $log->hasSemanticError())->count();
        $httpErrorCount = $this->failedLogs->filter(fn ($log) =>
            $log->status_code && $log->status_code >= 400 && !$log->hasSemanticError()
        )->count();
        $exceptionCount = $this->failedLogs->filter(fn ($log) =>
            $log->error_class && !str_starts_with($log->error_class, 'SemanticError:')
        )->count();

        // Admin panel link with filter for failed status
        $adminUrl = url('/admin/service-gateway-exchange-logs?tableFilters[status][value]=0');

        // Use custom HTML template for professional appearance
        return (new MailMessage)
            ->subject("[WEBHOOK ALERT] {$count} fehlgeschlagene Zustellung" . ($count > 1 ? 'en' : '') . " ({$this->period})")
            ->view('emails.webhook-delivery-failed', [
                'count' => $count,
                'period' => $this->period,
                'semanticCount' => $semanticCount,
                'httpErrorCount' => $httpErrorCount,
                'exceptionCount' => $exceptionCount,
                'logs' => $this->failedLogs->take(5),
                'adminUrl' => $adminUrl,
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'failed_count' => $this->failedLogs->count(),
            'period' => $this->period,
            'log_ids' => $this->failedLogs->pluck('id')->toArray(),
        ];
    }

    /**
     * Get the type of error for a log entry.
     * Useful for categorization in the email template.
     */
    public static function getErrorType(ServiceGatewayExchangeLog $log): string
    {
        return match (true) {
            $log->hasSemanticError() => 'semantic',
            $log->status_code && $log->status_code >= 400 => 'http',
            default => 'exception',
        };
    }

    /**
     * Get a user-friendly label for the error type.
     */
    public static function getErrorTypeLabel(ServiceGatewayExchangeLog $log): string
    {
        return match (self::getErrorType($log)) {
            'semantic' => 'Semantischer Fehler',
            'http' => 'HTTP ' . ($log->status_code ?? 'Fehler'),
            'exception' => 'Exception',
        };
    }

    /**
     * Get the appropriate color class for the error type.
     * Returns Tailwind-style color names for use in templates.
     */
    public static function getErrorTypeColor(ServiceGatewayExchangeLog $log): string
    {
        return match (self::getErrorType($log)) {
            'semantic' => 'warning',  // amber/yellow
            'http' => 'danger',       // red
            'exception' => 'gray',    // neutral
        };
    }
}
