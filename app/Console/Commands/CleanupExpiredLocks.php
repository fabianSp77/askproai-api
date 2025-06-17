<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Locking\TimeSlotLockManager;

class CleanupExpiredLocks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'locks:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired appointment time slot locks';

    /**
     * Execute the console command.
     */
    public function handle(TimeSlotLockManager $lockManager): int
    {
        $this->info('Cleaning up expired appointment locks...');
        
        $cleanedCount = $lockManager->cleanupExpiredLocks();
        
        if ($cleanedCount > 0) {
            $this->info("Successfully cleaned up {$cleanedCount} expired locks.");
        } else {
            $this->info('No expired locks found.');
        }
        
        // Also show current lock statistics
        $stats = $lockManager->getLockStatistics();
        
        $this->newLine();
        $this->info('Current Lock Statistics:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Active Locks', $stats['total_active_locks']],
                ['Total Expired Locks', $stats['total_expired_locks']],
                ['Average Lock Duration (minutes)', $stats['average_lock_duration'] ?? 'N/A'],
            ]
        );
        
        if (!empty($stats['locks_by_branch'])) {
            $this->newLine();
            $this->info('Active Locks by Branch:');
            $branchData = [];
            foreach ($stats['locks_by_branch'] as $branchId => $count) {
                $branchData[] = ["Branch ID: {$branchId}", $count];
            }
            $this->table(['Branch', 'Active Locks'], $branchData);
        }
        
        return Command::SUCCESS;
    }
}