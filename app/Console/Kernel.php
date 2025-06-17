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
        // Send appointment reminders every 5 minutes
        $schedule->command('appointments:send-reminders')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();
            
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
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}