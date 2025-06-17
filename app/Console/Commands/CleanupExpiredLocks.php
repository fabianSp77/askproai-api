<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AppointmentBookingService;
use Illuminate\Support\Facades\Log;

class CleanupExpiredLocks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointments:cleanup-locks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired appointment locks to prevent deadlocks';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $bookingService = new AppointmentBookingService();
        
        $this->info('Cleaning up expired appointment locks...');
        
        try {
            $deletedCount = $bookingService->cleanupExpiredLocks();
            
            $this->info("Successfully deleted {$deletedCount} expired locks.");
            
            Log::info('Cleaned up expired appointment locks', [
                'deleted_count' => $deletedCount,
                'timestamp' => now()
            ]);
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Failed to clean up locks: ' . $e->getMessage());
            
            Log::error('Failed to clean up appointment locks', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }
}
