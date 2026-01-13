<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ServiceGatewayExchangeLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * WebhookDeliveryFailedNotification
 *
 * Sends email alerts when webhook deliveries fail.
 * Includes HTTP errors, semantic errors (HTTP 200 but error in body),
 * and PHP exceptions.
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

        $mail = (new MailMessage)
            ->subject("[WEBHOOK ALERT] {$count} fehlgeschlagene Zustellungen ({$this->period})")
            ->greeting('Webhook-Fehler erkannt')
            ->line("In der {$this->period} sind **{$count} Webhook-Zustellungen** fehlgeschlagen:");

        // Summary by type
        $mail->line('');
        $mail->line('**Zusammenfassung nach Fehlertyp:**');
        if ($semanticCount > 0) {
            $mail->line("- {$semanticCount} Semantische Fehler (HTTP 200 aber Fehler im Body)");
        }
        if ($httpErrorCount > 0) {
            $mail->line("- {$httpErrorCount} HTTP-Fehler (4xx/5xx)");
        }
        if ($exceptionCount > 0) {
            $mail->line("- {$exceptionCount} Exceptions (Verbindungsfehler, Timeouts)");
        }

        $mail->line('');
        $mail->line('**Details der letzten 5 Fehler:**');

        // Show top 5 errors with details
        foreach ($this->failedLogs->take(5) as $log) {
            $statusType = match (true) {
                $log->hasSemanticError() => '[Semantisch]',
                $log->status_code >= 400 => '[HTTP]',
                default => '[Exception]',
            };

            $mail->line("---");
            $mail->line("**{$statusType} {$log->endpoint}**");
            $mail->line("Status: " . ($log->status_code ?? 'N/A') . " | Typ: {$log->error_class}");
            if ($log->error_message) {
                $mail->line("Fehler: " . substr($log->error_message, 0, 100) . (strlen($log->error_message) > 100 ? '...' : ''));
            }
            $mail->line("Zeit: {$log->created_at->format('d.m.Y H:i:s')}");
        }

        if ($count > 5) {
            $mail->line('');
            $mail->line("... und " . ($count - 5) . " weitere Fehler.");
        }

        // Admin panel link with filter
        $adminUrl = url('/admin/service-gateway-exchange-logs?tableFilters[status_type][value]=semantic_error');

        $mail->action('Alle Fehler im Admin-Panel anzeigen', $adminUrl)
            ->line('')
            ->line('Bitte pruefen Sie die fehlgeschlagenen Webhooks und beheben Sie eventuelle Konfigurationsprobleme.')
            ->salutation('AskProAI Service Gateway');

        return $mail;
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
}
