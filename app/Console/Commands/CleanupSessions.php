<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sessions:cleanup {--force : Force cleanup even for active sessions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired and orphaned sessions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting session cleanup...');
        
        // Clean expired sessions
        $expiredCount = $this->cleanExpiredSessions();
        $this->info("Removed {$expiredCount} expired sessions.");
        
        // Clean duplicate sessions
        $duplicateCount = $this->cleanDuplicateSessions();
        $this->info("Removed {$duplicateCount} duplicate sessions.");
        
        // Clean orphaned sessions (no user_id but older than 1 hour)
        $orphanedCount = $this->cleanOrphanedSessions();
        $this->info("Removed {$orphanedCount} orphaned sessions.");
        
        // Log cleanup stats
        Log::info('Session cleanup completed', [
            'expired' => $expiredCount,
            'duplicates' => $duplicateCount,
            'orphaned' => $orphanedCount,
            'total' => $expiredCount + $duplicateCount + $orphanedCount,
        ]);
        
        $this->info('Session cleanup completed successfully!');
        
        return Command::SUCCESS;
    }
    
    /**
     * Clean expired sessions based on lifetime
     */
    private function cleanExpiredSessions(): int
    {
        $lifetime = config('session.lifetime', 120);
        
        return DB::table('sessions')
            ->where('last_activity', '<', now()->subMinutes($lifetime)->timestamp)
            ->delete();
    }
    
    /**
     * Clean duplicate sessions for same user
     */
    private function cleanDuplicateSessions(): int
    {
        $duplicates = DB::table('sessions')
            ->select('user_id', DB::raw('COUNT(*) as count'))
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->having('count', '>', 1)
            ->get();
            
        $deleted = 0;
        
        foreach ($duplicates as $duplicate) {
            // Keep only the most recent session
            $sessions = DB::table('sessions')
                ->where('user_id', $duplicate->user_id)
                ->orderBy('last_activity', 'desc')
                ->skip(1)
                ->pluck('id');
                
            $deleted += DB::table('sessions')
                ->whereIn('id', $sessions)
                ->delete();
        }
        
        return $deleted;
    }
    
    /**
     * Clean orphaned sessions
     */
    private function cleanOrphanedSessions(): int
    {
        return DB::table('sessions')
            ->whereNull('user_id')
            ->where('last_activity', '<', now()->subHour()->timestamp)
            ->delete();
    }
}