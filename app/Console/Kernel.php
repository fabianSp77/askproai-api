<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\SendStripeMeterEvent::class,
        \App\Console\Commands\SendStripeUsage::class, // ← Legacy-Command kann bleiben
    ];

    protected function schedule(Schedule $schedule): void
    {
        // Run view cache health check every 5 minutes
        $schedule->command('view:health-check --fix')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/view-health-check.log'));
            
        // Run comprehensive cache warmup daily at 3 AM
        $schedule->command('view:cache')
            ->dailyAt('03:00')
            ->withoutOverlapping();
            
        // Monitor login form health every 30 minutes
        $schedule->command('monitor:login-form --alert')
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/login-form-monitor.log'));
            
        // Monitor Cal.com V1 API deprecation daily with alerts
        $schedule->command('calcom:monitor-deprecation --alert --report')
            ->dailyAt('09:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/calcom-deprecation.log'))
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error('Cal.com deprecation monitor failed to run');
            });
        
        // Collect Cal.com metrics every 6 hours
        $schedule->command('calcom:monitor-deprecation')
            ->everySixHours()
            ->withoutOverlapping()
            ->runInBackground();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
