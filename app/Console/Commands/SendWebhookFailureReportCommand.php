<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ServiceGatewayExchangeLog;
use App\Notifications\WebhookDeliveryFailedNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * SendWebhookFailureReportCommand
 *
 * Sends email reports for failed webhook deliveries.
 * Designed to run hourly via scheduler.
 *
 * Features:
 * - Detects HTTP errors, semantic errors, and exceptions
 * - Prevents duplicate notifications via notification_sent_at
 * - Configurable threshold and lookback period
 *
 * @package App\Console\Commands
 */
class SendWebhookFailureReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'webhook:send-failure-report
                            {--hours=1 : Hours to look back for failures}
                            {--threshold=1 : Minimum failures to trigger notification}
                            {--dry-run : Show what would be sent without actually sending}';

    /**
     * The console command description.
     */
    protected $description = 'Send email report for failed webhook deliveries (including semantic errors)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $threshold = (int) $this->option('threshold');
        $dryRun = (bool) $this->option('dry-run');

        $this->info("Checking for webhook failures in the last {$hours} hour(s)...");

        // Find failed webhooks (including semantic errors)
        $failedLogs = ServiceGatewayExchangeLog::query()
            ->where('direction', 'outbound')
            ->where('created_at', '>=', now()->subHours($hours))
            ->where(function ($query) {
                // Any error_class set (includes SemanticError:* and exceptions)
                $query->whereNotNull('error_class')
                    // OR HTTP errors without error_class
                    ->orWhere('status_code', '>=', 400);
            })
            // Not yet notified
            ->whereNull('notification_sent_at')
            // Exclude test webhooks from alerts
            ->where('is_test', false)
            ->orderBy('created_at', 'desc')
            ->get();

        $this->info("Found {$failedLogs->count()} failed webhook(s)");

        if ($failedLogs->count() < $threshold) {
            $this->info("Below threshold ({$threshold}). No notification sent.");
            return self::SUCCESS;
        }

        // Group by error type for logging
        $semanticCount = $failedLogs->filter(fn ($log) => $log->hasSemanticError())->count();
        $httpErrorCount = $failedLogs->filter(fn ($log) =>
            $log->status_code && $log->status_code >= 400 && !$log->hasSemanticError()
        )->count();
        $exceptionCount = $failedLogs->filter(fn ($log) =>
            $log->error_class && !str_starts_with($log->error_class, 'SemanticError:')
        )->count();

        $this->table(
            ['Typ', 'Anzahl'],
            [
                ['Semantische Fehler', $semanticCount],
                ['HTTP Fehler (4xx/5xx)', $httpErrorCount],
                ['Exceptions', $exceptionCount],
                ['Gesamt', $failedLogs->count()],
            ]
        );

        // Get admin email from config
        $adminEmail = config('mail.webhook_alerts.admin_email', 'fabian@askproai.de');

        if ($dryRun) {
            $this->warn("DRY RUN: Would send notification to {$adminEmail}");
            $this->info("Log IDs: " . $failedLogs->pluck('id')->join(', '));
            return self::SUCCESS;
        }

        // Send notification
        try {
            Notification::route('mail', $adminEmail)
                ->notify(new WebhookDeliveryFailedNotification(
                    failedLogs: $failedLogs,
                    period: $hours === 1 ? 'letzte Stunde' : "letzten {$hours} Stunden"
                ));

            $this->info("Notification sent to {$adminEmail}");

            // Mark logs as notified
            ServiceGatewayExchangeLog::whereIn('id', $failedLogs->pluck('id'))
                ->update(['notification_sent_at' => now()]);

            $this->info("Marked {$failedLogs->count()} log(s) as notified");

            Log::info('[SendWebhookFailureReport] Notification sent', [
                'admin_email' => $adminEmail,
                'failed_count' => $failedLogs->count(),
                'semantic_count' => $semanticCount,
                'http_error_count' => $httpErrorCount,
                'exception_count' => $exceptionCount,
                'log_ids' => $failedLogs->pluck('id')->toArray(),
            ]);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Failed to send notification: {$e->getMessage()}");

            Log::error('[SendWebhookFailureReport] Failed to send notification', [
                'error' => $e->getMessage(),
                'admin_email' => $adminEmail,
                'failed_count' => $failedLogs->count(),
            ]);

            return self::FAILURE;
        }
    }
}
