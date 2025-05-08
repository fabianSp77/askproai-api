<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\SendStripeMeterEvent::class,
        // (alter SendStripeUsage bleibt für Legacy-Preise)
    ];

    protected function schedule(Schedule $schedule): void
    {
        // hier später Cron-Jobs eintragen
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
