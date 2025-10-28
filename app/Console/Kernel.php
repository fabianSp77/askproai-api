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
        // Uses 4 hour threshold to avoid cleaning up very long legitimate calls
        $schedule->command('calls:cleanup-stuck --hours=4')
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
            ->appendOutputTo(storage_path('logs/materialized-stats.log'));

        // Materialized Stats: Clean up old stats daily at 3am (stats older than 90 days)
        $schedule->call(function () {
            $service = app(\App\Services\Policies\MaterializedStatService::class);
            $service->cleanupOldStats();
        })
            ->name('materialized-stats-cleanup')
            ->dailyAt('03:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/materialized-stats.log'));

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
            ->appendOutputTo(storage_path('logs/data-consistency.log'));

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
            ->emailOutputOnFailure(config('mail.admin_email', 'admin@askpro.ai'));

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
            ->appendOutputTo(storage_path('logs/data-consistency.log'));

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
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
