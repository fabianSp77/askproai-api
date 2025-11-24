<?php

namespace App\Jobs;

use App\Services\Booking\OptimisticReservationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CleanupExpiredReservationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120; // 2 minutes max
    public $tries = 3;

    /**
     * Execute the job.
     */
    public function handle(OptimisticReservationService $reservationService): void
    {
        $startTime = microtime(true);

        try {
            $cleanedCount = $reservationService->cleanupExpired();

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('🧹 Expired reservations cleaned', [
                'count' => $cleanedCount,
                'duration_ms' => $duration,
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Failed to cleanup expired reservations', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
