<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sessions:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired sessions from the database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $expired = now()->subMinutes(config('session.lifetime'))->timestamp;
        
        $deleted = DB::table('sessions')
            ->where('last_activity', '<', $expired)
            ->delete();
            
        $this->info("Cleaned up {$deleted} expired sessions.");
        
        return Command::SUCCESS;
    }
}