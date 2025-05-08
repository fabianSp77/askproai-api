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
        // Später: Cron-Jobs hier eintragen
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
