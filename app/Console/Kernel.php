<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\SendStripeMeterEvent::class,
        \App\Console\Commands\SendStripeUsage::class, // ← Legacy-Command kann bleiben
        \App\Console\Commands\SyncRetellCalls::class,
        \App\Console\Commands\DetectCallConversions::class,
        \App\Console\Commands\ConfigureRetellWebhook::class,
        \App\Console\Commands\MonitorProcessingTimeHealth::class,
        \App\Console\Commands\AlertAppointmentSyncFailures::class, // 🆕 PHASE 3 (2025-11-24)
        \App\Console\Commands\ReconcileCallSuccess::class, // 🆕 2025-11-27: Fix false negatives
        \App\Console\Commands\QueueHealthCheckCommand::class, // 🆕 2025-12-31: Stale worker detection
        \App\Console\Commands\GenerateMonthlyInvoicesCommand::class, // 🆕 2026-01-09: Partner monthly billing
        \App\Console\Commands\SendWebhookFailureReportCommand::class, // 🆕 2026-01-13: Webhook failure alerts
    ];

    public function schedule(Schedule $schedule): void
    {
        // Exchange rates update - runs daily at 2am to get fresh USD/EUR/GBP rates from ECB
        $schedule->command('exchange-rates:update')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/exchange-rates.log'))
            ->emailOutputOnFailure(config('mail.admin_email', 'admin@askpro.ai'));

        // Cal.com Event Type sync - runs every 30 minutes as backup for webhooks
        $schedule->command('calcom:sync-services')
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/calcom-sync.log'));

        // Retell calls sync - runs every 15 minutes to get new calls
        $schedule->command('retell:sync-calls --limit=100 --days=1')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/retell-sync.log'));

        // Full Retell sync - runs once daily at night to ensure nothing is missed
        $schedule->command('retell:sync-calls --limit=1000 --days=7')
            ->dailyAt('03:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/retell-sync-full.log'));

        // Detect call conversions - runs every hour
        $schedule->command('calls:detect-conversions --hours=2 --auto-link')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/conversion-detection.log'));

        // Clean up stuck calls - runs every 10 minutes
        // Uses 2 hour threshold (test calls use 'test' status, so legitimate calls won't be cleaned)
        $schedule->command('calls:cleanup-stuck --hours=2')
            ->everyTenMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/calls-cleanup.log'));

        // Daily validation: Check for NULL company_id in customers (data integrity)
        $schedule->command('customer:validate-company-id --fail-on-issues')
            ->dailyAt('04:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/customer-validation.log'))
            ->emailOutputOnFailure(config('mail.admin_email', 'admin@askpro.ai'));

        // Materialized Stats: Refresh customer modification stats hourly (for O(1) policy quota checks)
        $schedule->call(function () {
            $service = app(\App\Services\Policies\MaterializedStatService::class);
            $service->refreshAllStats();
        })
            ->name('materialized-stats-refresh')
            ->hourly()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/materialized-stats.log'))
            ->onOneServer();

        // Materialized Stats: Clean up old stats daily at 3am (stats older than 90 days)
        $schedule->call(function () {
            $service = app(\App\Services\Policies\MaterializedStatService::class);
            $service->cleanupOldStats();
        })
            ->name('materialized-stats-cleanup')
            ->dailyAt('03:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/materialized-stats.log'))
            ->onOneServer();

        // 🛡️ DATA CONSISTENCY MONITORING (2025-10-20)

        // Real-time inconsistency detection - runs every 5 minutes
        $schedule->call(function () {
            $monitor = app(\App\Services\Monitoring\DataConsistencyMonitor::class);
            $inconsistencies = $monitor->detectInconsistencies();

            if (!empty($inconsistencies)) {
                Log::warning('⚠️ Data inconsistencies detected', [
                    'count' => count($inconsistencies),
                    'issues' => $inconsistencies
                ]);
            }
        })
            ->name('data-consistency-check')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/data-consistency.log'))
            ->onOneServer();

        // Daily validation report - comprehensive data quality report
        $schedule->call(function () {
            $monitor = app(\App\Services\Monitoring\DataConsistencyMonitor::class);
            $report = $monitor->generateDailyReport();

            Log::info('📊 Daily data consistency report generated', $report);
        })
            ->name('data-consistency-daily-report')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/data-consistency.log'))
            ->emailOutputOnFailure(config('mail.admin_email', 'admin@askpro.ai'))
            ->onOneServer();

        // Manual review queue processing - runs every hour
        $schedule->call(function () {
            $monitor = app(\App\Services\Monitoring\DataConsistencyMonitor::class);
            $processed = $monitor->processManualReviewQueue();

            if ($processed > 0) {
                Log::info("✅ Processed {$processed} items from manual review queue");
            }
        })
            ->name('manual-review-queue-processing')
            ->hourly()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/data-consistency.log'))
            ->onOneServer();

        // 📊 PROCESSING TIME / SPLIT APPOINTMENTS MONITORING (2025-10-28)

        // Processing Time health check - runs hourly during business hours
        // Monitors phase creation success rate and detects orphaned appointments
        $schedule->command('monitor:processing-time-health')
            ->hourly()
            ->between('8:00', '20:00')
            ->timezone('Europe/Berlin')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/processing-time-health.log'));

        // 📞 CALLBACK SLA MONITORING (2025-11-13)

        // Callback SLA check - runs every 5 minutes during business hours
        // Monitors callback response times and triggers alerts/escalations
        // Thresholds: 60min (warning), 90min (critical), 120min (escalation)
        $schedule->job(new \App\Jobs\CheckCallbackSlaJob())
            ->everyFiveMinutes()
            ->between('8:00', '20:00')
            ->timezone('Europe/Berlin')
            ->withoutOverlapping();

        // 📧 CUSTOMER PORTAL: EMAIL QUEUE PROCESSING (2025-11-24)

        // Process invitation email queue - runs every 5 minutes
        // Sends pending invitation emails with retry mechanism
        // Exponential backoff: 5min → 30min → 2hr (max 3 attempts)
        $schedule->job(new \App\Jobs\ProcessInvitationEmailsJob())
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/invitation-emails.log'));

        // Clean up expired invitations - runs daily at 3am
        // Soft-deletes expired invitations after 30 days
        // Hard-deletes soft-deleted invitations after 60 days
        // Cleans up old failed email queue items
        $schedule->job(new \App\Jobs\CleanupExpiredInvitationsJob())
            ->dailyAt('03:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/invitation-cleanup.log'));

        // Clean up expired appointment reservations - runs every 10 minutes
        // Removes Redis-based slot locks that have expired
        $schedule->job(new \App\Jobs\CleanupExpiredReservationsJob())
            ->everyTenMinutes()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/reservation-cleanup.log'));

        // 🔊 AUDIO STORAGE CLEANUP (2025-12-22) - SERVICE GATEWAY
        //
        // Delete expired audio recordings from S3/MinIO.
        // Retention: 60 days from upload
        // Fallback: S3 lifecycle rules (if configured)
        $schedule->command('audio:cleanup')
            ->dailyAt('04:30')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/audio-cleanup.log'));

        // ⚡ ESCALATION RULES PROCESSING (2025-12-28) - SERVICE GATEWAY
        //
        // Process automated escalation rules for ServiceNow-style SLA monitoring.
        // Opt-in per company (escalation_rules_enabled = true).
        // Actions: Email notification, Group reassignment, Priority escalation, Webhook
        $schedule->job(new \App\Jobs\ServiceGateway\ProcessEscalationRulesJob())
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/escalation-rules.log'))
            ->onOneServer();

        // 🔄 QUEUE HEALTH MONITORING (2025-12-31)
        //
        // Detects "stale workers" - processes that are running but not processing jobs.
        // Common causes: memory leaks, deadlocks, PHP process hangs.
        // Triggers graceful queue:restart if >10 jobs are stale (>5 min old).
        // This prevents data loss (webhooks not sent, emails not delivered).
        $schedule->command('queue:health-check')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/queue-health.log'))
            ->onOneServer();

        // 📧 WEBHOOK FAILURE ALERTS (2026-01-13) - SERVICE GATEWAY
        //
        // Sends hourly email alerts when webhook deliveries fail.
        // Detects three types of failures:
        // - HTTP errors (4xx/5xx status codes)
        // - Semantic errors (HTTP 200 but error in response body)
        // - Exceptions (connection failures, timeouts)
        // Recipients: fabian@askproai.de (configurable in config/mail.php)
        $schedule->command('webhook:send-failure-report --hours=1 --threshold=1')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/webhook-failure-alerts.log'))
            ->onOneServer();

        // 🔄 APPOINTMENT SYNC MONITORING (2025-11-24) - PHASE 3

        // Alert on appointment sync failures - runs every 15 minutes
        // Detects critical issues: stale pending (>1h), ancient failures (>24h), manual review required
        // Logs to storage/logs/laravel.log (channel: calcom)
        // Exit code FAILURE (1) triggers alert if critical issues detected
        $schedule->command('appointments:alert-sync-failures')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/appointment-sync-alerts.log'));

        // 🛡️ CALL SUCCESS RECONCILIATION (2025-11-27) - FALSE NEGATIVE FIX
        //
        // Safety net for race condition between booking and Retell sync:
        // 1. Booking creates appointment with call_successful = true
        // 2. Retell sync job runs (every 15 min) and overwrites with call_successful = false
        //    (because transcript says "error" even though booking succeeded)
        // 3. This reconciliation job runs AFTER sync and corrects false negatives
        //
        // Runs every 10 minutes (after the 15-minute sync window)
        $schedule->command('calls:reconcile-success --days=2')
            ->everyTenMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/call-reconciliation.log'));

        // 💰 PARTNER MONTHLY BILLING (2026-01-09)
        //
        // Generate and send monthly invoices to partners on the 1st of each month.
        // Each partner receives ONE aggregated invoice covering all their managed companies.
        // Charges include: Call minutes, monthly service fees, service changes, setup fees.
        // --send flag automatically finalizes and emails invoices via Stripe.
        $schedule->command('billing:generate-monthly-invoices --send')
            ->monthlyOn(1, '09:00')
            ->timezone('Europe/Berlin')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/partner-billing.log'))
            ->emailOutputOnFailure(config('mail.admin_email', 'admin@askpro.ai'))
            ->onOneServer()
            ->environments(['production']);

        // Collect appointment sync metrics - runs every 5 minutes
        // Caches metrics for dashboard widget (5-minute TTL)
        // Updates sync rate history for trending charts
        $schedule->call(function () {
            $collector = app(\App\Services\Monitoring\CalcomMetricsCollector::class);
            $metrics = $collector->collectAllMetrics();

            // Cache for dashboard widget
            \Illuminate\Support\Facades\Cache::put('calcom:metrics:latest', $metrics, 300);

            // Update sync rate history (for chart)
            $syncRate = $metrics['synchronization']['appointments']['success_rate_24h'] ?? 100;
            $history = \Illuminate\Support\Facades\Cache::get('calcom:appointment_sync:history', []);
            $history[] = $syncRate;
            $history = array_slice($history, -7); // Keep last 7 data points
            \Illuminate\Support\Facades\Cache::put('calcom:appointment_sync:history', $history, 3600);

            \Illuminate\Support\Facades\Log::channel('calcom')->debug('📊 Appointment sync metrics collected', [
                'success_rate' => $syncRate,
                'health_status' => $metrics['synchronization']['appointments']['health_status'] ?? 'unknown',
            ]);
        })
            ->name('appointment-sync-metrics-collection')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->onOneServer();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
