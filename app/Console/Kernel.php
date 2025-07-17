<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Schedule appointment reminders every 5 minutes
        $schedule->command('appointments:schedule-reminders')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/appointment-reminders.log'));

        // Clean up old notifications daily
        $schedule->command('notifications:cleanup')
            ->daily()
            ->at('02:00');

        // Generate availability reports weekly
        $schedule->command('availability:report')
            ->weekly()
            ->sundays()
            ->at('08:00');

        // Sync cal.com data every hour
        $schedule->command('calcom:sync')
            ->hourly()
            ->withoutOverlapping();

        // Auto-sync Event Types every hour
        $schedule->command('calcom:auto-sync --all')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground();

        // Warm caches every 30 minutes for all active companies
        $schedule->command('cache:warm --async')
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // Warm event types cache more frequently (every 15 minutes)
        $schedule->command('cache:warm --type=event_types --async')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // Clean up sessions every 30 minutes
        $schedule->command('sessions:cleanup')
            ->everyThirtyMinutes()
            ->withoutOverlapping();

        // Broadcast system metrics every 10 seconds for real-time updates
        $schedule->command('system:broadcast-metrics')
            ->everyTenSeconds()
            ->withoutOverlapping()
            ->runInBackground();

        // Run health checks every 5 minutes
        $schedule->command('health:check')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // Cleanup old alerts daily
        $schedule->command('alerts:cleanup')
            ->daily()
            ->at('03:00');

        // Clean up expired appointment locks every 5 minutes
        $schedule->command('locks:cleanup')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // MCP Discovery & Evolution System schedules

        // Discover new MCPs daily
        $schedule->command('mcp:discover')
            ->daily()
            ->at('09:00')
            ->withoutOverlapping();

        // Analyze UI/UX weekly
        $schedule->command('uiux:analyze --suggest')
            ->weekly()
            ->mondays()
            ->at('10:00');

        // Run continuous improvement analysis hourly
        $schedule->command('improvement:analyze')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground();

        // Generate improvement reports weekly
        $schedule->command('improvement:analyze --report')
            ->weekly()
            ->sundays()
            ->at('23:00');

        // Performance optimization tasks
        $schedule->command('performance:optimize --cache')
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('performance:optimize --pool')
            ->hourly()
            ->withoutOverlapping();

        // Analyze slow queries daily during low traffic
        $schedule->command('performance:optimize --analyze')
            ->daily()
            ->at('04:00');

        // Sync Retell agent configurations every hour
        $schedule->command('retell:sync-configurations --all')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/retell-sync.log'));

        // Force sync all Retell agent configurations once a day at 2 AM
        $schedule->command('retell:sync-configurations --all --force')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/retell-sync.log'));

        // Sync Cal.com event type users daily at 3:30 AM
        $schedule->command('calcom:sync-users --all --async')
            ->dailyAt('03:30')
            ->withoutOverlapping()
            ->onOneServer()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/calcom-user-sync.log'));

        // Import Retell calls every 15 minutes
        $schedule->command('retell:fetch-calls --limit=50')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/retell-call-import.log'));

        // Collect metrics every minute for Prometheus monitoring
        $schedule->command('metrics:collect')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground()
            ->onOneServer();

        // Run system health checks and trigger alerts every 5 minutes
        $schedule->command('monitoring:health-check')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/health-checks.log'));

        // Knowledge base file watcher (moved from KnowledgeServiceProvider)
        if (config('knowledge.auto_index.enabled', false)) {
            $schedule->call(function () {
                $watcher = app(\App\Services\FileWatcherService::class);
                $watcher->checkForChanges();
                $watcher->setLastCheck();
            })->everyMinute()
              ->name('knowledge:watch')
              ->withoutOverlapping();
        }

        // Billing Automation Jobs
        
        // Create billing periods at the start of each month
        $schedule->command('billing:create-periods')
            ->monthlyOn(1, '00:01')
            ->withoutOverlapping()
            ->runInBackground()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/billing-periods.log'));
        
        // Also check daily for any missed billing periods
        $schedule->command('billing:create-periods')
            ->dailyAt('01:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/billing-periods.log'));
        
        // Report usage to Stripe every hour
        $schedule->command('billing:report-usage')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/billing-usage.log'));
        
        // Process billing periods and create invoices daily
        $schedule->command('billing:process-periods')
            ->dailyAt('02:30')
            ->withoutOverlapping()
            ->runInBackground()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/billing-process.log'));
        
        // Also run billing processing on the 1st of each month after period creation
        $schedule->command('billing:process-periods')
            ->monthlyOn(1, '03:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/billing-process.log'));
        
        // Dunning Management Jobs
        
        // Process dunning retries every 4 hours
        $schedule->command('dunning:process-retries')
            ->everyFourHours()
            ->withoutOverlapping()
            ->runInBackground()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/dunning-retries.log'));
        
        // Also run dunning retries daily at 10 AM for better success rates
        $schedule->command('dunning:process-retries')
            ->dailyAt('10:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/dunning-retries.log'));
        
        // Check prepaid balances for low balance warnings
        $schedule->command('billing:check-low-balances')
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/prepaid-balance-monitoring.log'));
        
        // Billing Alerts Jobs
        
        // Check billing alerts every hour
        $schedule->command('billing:check-alerts')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/billing-alerts.log'));
        
        // Also check alerts during business hours more frequently
        $schedule->command('billing:check-alerts')
            ->everyThirtyMinutes()
            ->between('08:00', '18:00')
            ->weekdays()
            ->withoutOverlapping()
            ->runInBackground()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/billing-alerts.log'));
        
        // Prepaid Balance Monitoring
        
        // Check low balances every hour
        $schedule->command('balances:check-low')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/balance-monitoring.log'));
        
        // Also check during business hours more frequently
        $schedule->command('balances:check-low')
            ->everyThirtyMinutes()
            ->between('08:00', '20:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/balance-monitoring.log'));
            
        // Call Summary Batch Emails
        
        // Send hourly call summaries
        $schedule->command('calls:send-batch-summaries --frequency=hourly')
            ->hourly()
            ->at('05')
            ->withoutOverlapping()
            ->runInBackground()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/call-summaries.log'));
            
        // Send daily call summaries at 8 AM
        $schedule->command('calls:send-batch-summaries --frequency=daily')
            ->dailyAt('08:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/call-summaries.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
