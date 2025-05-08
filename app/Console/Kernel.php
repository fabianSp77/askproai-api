<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Hier alle eigenen Artisan-Befehle registrieren.
     *
     * @var array<int, class-string>
     */
    protected $commands = [
        \App\Console\Commands\SendStripeUsage::class,
    ];

    /**
     * Artisan Commands, die automatisch getimt laufen sollen.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Beispiel:
        // $schedule->command('horizon:snapshot')->hourly();
    }

    /**
     * Commands aus `app/Console/Commands` automatisch finden.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
