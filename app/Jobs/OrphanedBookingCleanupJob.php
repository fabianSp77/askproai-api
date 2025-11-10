<?php

namespace App\Jobs;

use App\Services\CalcomService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Orphaned Booking Cleanup Job
 *
 * SAGA Pattern Compensation: Cleans up Cal.com bookings that were created
 * but failed to save to local database.
 *
 * PRIORITY: P0 - CRITICAL (Part of SAGA pattern implementation)
 * CREATED: 2025-11-05
 * ISSUE: 67% booking failure rate due to orphaned Cal.com bookings
 *
 * This job is dispatched when:
 * 1. Cal.com booking succeeds
 * 2. Local DB save fails
 * 3. SAGA compensation (cancel booking) also fails
 * 4. Result: Orphaned booking in Cal.com that needs manual cleanup
 *
 * Retry Strategy:
 * - Max attempts: 5
 * - Delay: Exponential backoff (1min, 2min, 4min, 8min, 16min)
 * - Total retry window: ~30 minutes
 */
class OrphanedBookingCleanupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Cal.com booking ID to clean up
     *
     * @var string
     */
    public string $calcomBookingId;

    /**
     * Original error message that caused the orphan
     *
     * @var string|null
     */
    public ?string $originalError;

    /**
     * Metadata for debugging
     *
     * @var array
     */
    public array $metadata;

    /**
     * Maximum number of retry attempts
     *
     * @var int
     */
    public $tries = 5;

    /**
     * Backoff delay in seconds (exponential)
     *
     * @var array
     */
    public $backoff = [60, 120, 240, 480, 960]; // 1min, 2min, 4min, 8min, 16min

    /**
     * Timeout for the job (seconds)
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Create a new job instance
     *
     * @param string $calcomBookingId Cal.com booking UID to cancel
     * @param string|null $originalError Original error that caused orphan
     * @param array $metadata Additional context for debugging
     */
    public function __construct(
        string $calcomBookingId,
        ?string $originalError = null,
        array $metadata = []
    ) {
        $this->calcomBookingId = $calcomBookingId;
        $this->originalError = $originalError;
        $this->metadata = $metadata;

        // Set queue name for monitoring
        $this->onQueue('high'); // High priority queue for data consistency
    }

    /**
     * Execute the job
     *
     * @param CalcomService $calcomService
     * @return void
     */
    public function handle(CalcomService $calcomService): void
    {
        $attemptNumber = $this->attempts();

        Log::info('🔄 OrphanedBookingCleanupJob: Starting cleanup attempt', [
            'calcom_booking_id' => $this->calcomBookingId,
            'attempt' => $attemptNumber,
            'max_attempts' => $this->tries,
            'original_error' => $this->originalError,
            'metadata' => $this->metadata
        ]);

        try {
            // STEP 1: Check if booking still exists in Cal.com
            $booking = $calcomService->getBooking($this->calcomBookingId);

            if (!$booking->successful()) {
                if ($booking->status() === 404) {
                    // Booking already deleted (success!)
                    Log::info('✅ OrphanedBookingCleanupJob: Booking already deleted', [
                        'calcom_booking_id' => $this->calcomBookingId,
                        'attempt' => $attemptNumber,
                        'result' => 'booking_not_found'
                    ]);
                    return; // Job successful - booking no longer exists
                }

                // Other error (network, API down, etc.) - retry
                Log::warning('⚠️ OrphanedBookingCleanupJob: Could not fetch booking', [
                    'calcom_booking_id' => $this->calcomBookingId,
                    'status' => $booking->status(),
                    'attempt' => $attemptNumber,
                    'action' => 'will_retry'
                ]);
                throw new \Exception("Cal.com API error: HTTP {$booking->status()}");
            }

            $bookingData = $booking->json()['data'] ?? $booking->json();

            // STEP 2: Check if booking is already cancelled
            $status = $bookingData['status'] ?? 'unknown';
            if (in_array($status, ['cancelled', 'rejected'])) {
                Log::info('✅ OrphanedBookingCleanupJob: Booking already cancelled', [
                    'calcom_booking_id' => $this->calcomBookingId,
                    'status' => $status,
                    'attempt' => $attemptNumber,
                    'result' => 'already_cancelled'
                ]);
                return; // Job successful - booking already cancelled
            }

            // STEP 3: Check if booking is in the past (skip cancellation)
            if (isset($bookingData['start'])) {
                $startTime = Carbon::parse($bookingData['start']);
                if ($startTime->isPast()) {
                    Log::warning('⚠️ OrphanedBookingCleanupJob: Booking is in the past, cannot cancel', [
                        'calcom_booking_id' => $this->calcomBookingId,
                        'start_time' => $startTime->toIso8601String(),
                        'age_hours' => $startTime->diffInHours(now()),
                        'action' => 'manual_review_required'
                    ]);
                    // Don't retry - past bookings need manual review
                    return;
                }
            }

            // STEP 4: Attempt to cancel the booking
            $cancellationReason = sprintf(
                'Orphaned booking cleanup (Attempt %d/%d): Database save failed. Original error: %s',
                $attemptNumber,
                $this->tries,
                substr($this->originalError ?? 'Unknown error', 0, 100)
            );

            Log::info('🔄 OrphanedBookingCleanupJob: Attempting to cancel booking', [
                'calcom_booking_id' => $this->calcomBookingId,
                'attempt' => $attemptNumber,
                'reason' => $cancellationReason
            ]);

            $cancelResponse = $calcomService->cancelBooking(
                $this->calcomBookingId,
                $cancellationReason
            );

            if ($cancelResponse->successful()) {
                Log::info('✅ OrphanedBookingCleanupJob: Booking cancelled successfully', [
                    'calcom_booking_id' => $this->calcomBookingId,
                    'attempt' => $attemptNumber,
                    'result' => 'cancelled'
                ]);
                // Job successful
                return;
            }

            // Cancellation failed - log and retry
            Log::error('❌ OrphanedBookingCleanupJob: Cancellation failed', [
                'calcom_booking_id' => $this->calcomBookingId,
                'attempt' => $attemptNumber,
                'status' => $cancelResponse->status(),
                'response' => $cancelResponse->json(),
                'action' => $attemptNumber < $this->tries ? 'will_retry' : 'failed_permanently'
            ]);

            throw new \Exception("Cal.com cancellation failed: HTTP {$cancelResponse->status()}");

        } catch (\Exception $e) {
            Log::error('❌ OrphanedBookingCleanupJob: Exception during cleanup', [
                'calcom_booking_id' => $this->calcomBookingId,
                'attempt' => $attemptNumber,
                'max_attempts' => $this->tries,
                'error' => $e->getMessage(),
                'action' => $attemptNumber < $this->tries ? 'will_retry' : 'failed_permanently'
            ]);

            // Re-throw to trigger retry
            throw $e;
        }
    }

    /**
     * Handle job failure (after all retries exhausted)
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('🚨 OrphanedBookingCleanupJob: FAILED PERMANENTLY after all retries', [
            'calcom_booking_id' => $this->calcomBookingId,
            'attempts' => $this->tries,
            'original_error' => $this->originalError,
            'final_error' => $exception->getMessage(),
            'metadata' => $this->metadata,
            'impact' => 'MANUAL INTERVENTION REQUIRED',
            'action_required' => [
                '1. Login to Cal.com dashboard',
                '2. Search for booking ID: ' . $this->calcomBookingId,
                '3. Cancel booking manually',
                '4. Document in incident log'
            ]
        ]);

        // TODO: Send alert to operations team (Slack, email, etc.)
        // NotifyOperationsTeam::dispatch([
        //     'type' => 'orphaned_booking_cleanup_failed',
        //     'calcom_booking_id' => $this->calcomBookingId,
        //     'error' => $exception->getMessage()
        // ]);

        // TODO: Create incident ticket automatically
        // CreateIncidentTicket::dispatch([
        //     'title' => 'Orphaned Cal.com Booking Cleanup Failed',
        //     'description' => sprintf(
        //         'Failed to cleanup orphaned booking %s after %d attempts',
        //         $this->calcomBookingId,
        //         $this->tries
        //     ),
        //     'severity' => 'high',
        //     'category' => 'data_consistency'
        // ]);
    }
}
