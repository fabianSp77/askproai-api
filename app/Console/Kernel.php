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