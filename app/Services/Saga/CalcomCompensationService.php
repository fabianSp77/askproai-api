<?php

namespace App\Services\Saga;

use App\Services\CalcomV2Service;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Compensation handlers for Cal.com API operations
 *
 * Handles rollback of Cal.com bookings when local operations fail
 * and rollback of local changes when Cal.com sync fails.
 */
class CalcomCompensationService
{
    public function __construct(
        private CalcomV2Service $calcomService
    ) {}

    /**
     * Cancel a Cal.com booking (compensate for failed local record creation)
     *
     * Called when:
     * - Cal.com booking created successfully
     * - But local appointment record failed to create
     * - Need to remove the Cal.com booking to prevent orphaned bookings
     *
     * @param array $bookingData Cal.com booking response data
     * @throws Exception If cancellation fails
     */
    public function cancelCalcomBooking(array $bookingData): void
    {
        $bookingId = $bookingData['id'] ?? $bookingData['booking_id'] ?? null;
        if (!$bookingId) {
            throw new Exception('Cannot cancel Cal.com booking: no booking ID provided');
        }

        Log::channel('saga')->warning('🗑️ Compensating: Canceling Cal.com booking', [
            'booking_id' => $bookingId,
            'reason' => 'Local appointment record creation failed',
        ]);

        try {
            // Attempt to cancel booking in Cal.com
            $response = $this->calcomService->cancelBooking($bookingId);

            if ($response->successful()) {
                Log::channel('saga')->info('✅ Cal.com booking canceled successfully', [
                    'booking_id' => $bookingId,
                ]);
            } else {
                throw new Exception("Cal.com cancellation failed: {$response->status()} - {$response->body()}");
            }

        } catch (Exception $e) {
            Log::channel('saga')->error('🚨 CRITICAL: Failed to cancel Cal.com booking', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage(),
                'action_required' => 'Manual cleanup needed - booking exists in Cal.com but not in local DB',
            ]);

            // Re-throw to trigger saga compensation failure
            throw $e;
        }
    }

    /**
     * Cancel multiple Cal.com bookings (compensate for failed composite booking)
     *
     * Called when:
     * - Multiple Cal.com bookings created for composite appointment
     * - But later step (creating local composite record) failed
     * - Need to cancel all previously created bookings
     *
     * @param array $bookingIds Array of Cal.com booking IDs
     * @throws Exception If any cancellation fails
     */
    public function cancelCompositeBookings(array $bookingIds): void
    {
        $failedCancellations = [];

        foreach ($bookingIds as $bookingId) {
            try {
                Log::channel('saga')->info('⏮️ Compensating: Canceling composite segment', [
                    'booking_id' => $bookingId,
                ]);

                $response = $this->calcomService->cancelBooking($bookingId);

                if (!$response->successful()) {
                    throw new Exception("{$response->status()} - {$response->body()}");
                }

            } catch (Exception $e) {
                Log::channel('saga')->error('❌ Failed to cancel composite segment', [
                    'booking_id' => $bookingId,
                    'error' => $e->getMessage(),
                ]);
                $failedCancellations[$bookingId] = $e;
            }
        }

        if (!empty($failedCancellations)) {
            throw new Exception(
                'Failed to cancel ' . count($failedCancellations) . ' composite bookings'
            );
        }
    }

    /**
     * Update Cal.com booking metadata (for sync compensation)
     *
     * Called when:
     * - Local appointment status updated successfully
     * - But Cal.com sync failed
     * - Attempt to restore metadata to previous state
     *
     * @param int $bookingId Cal.com booking ID
     * @param array $metadata Metadata to restore
     * @throws Exception If update fails
     */
    public function restoreBookingMetadata(int $bookingId, array $metadata): void
    {
        Log::channel('saga')->info('🔄 Compensating: Restoring Cal.com booking metadata', [
            'booking_id' => $bookingId,
            'metadata_keys' => array_keys($metadata),
        ]);

        try {
            $response = $this->calcomService->updateBookingMetadata($bookingId, $metadata);

            if ($response->successful()) {
                Log::channel('saga')->info('✅ Booking metadata restored', [
                    'booking_id' => $bookingId,
                ]);
            } else {
                throw new Exception("Metadata restore failed: {$response->status()}");
            }

        } catch (Exception $e) {
            Log::channel('saga')->error('❌ Failed to restore Cal.com booking metadata', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
