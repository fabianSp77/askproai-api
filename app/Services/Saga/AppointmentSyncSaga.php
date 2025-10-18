<?php

namespace App\Services\Saga;

use App\Models\Appointment;
use App\Services\CalcomV2Service;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

/**
 * Appointment Sync Saga - Multi-step Cal.com synchronization with compensation
 *
 * Implements saga pattern for syncing appointment changes to Cal.com
 * Ensures atomicity: if local status update fails after Cal.com API call succeeds,
 * the system either retries or marks for manual review.
 *
 * Steps:
 * 1. Lock appointment row (already in place via RC3)
 * 2. Call Cal.com API (create/update/cancel - can fail)
 * 3. Update local appointment status and metadata (DB - can fail)
 * 4. Invalidate cache (cleanup - non-critical)
 *
 * Compensation:
 * - If Step 3 fails: Store error state, mark for retry/manual review
 * - If Step 4 fails: Log warning but don't fail (cache is non-critical)
 */
class AppointmentSyncSaga
{
    public function __construct(
        private CalcomV2Service $calcomService,
        private CalcomCompensationService $calcomCompensation,
        private DatabaseCompensationService $dbCompensation,
    ) {}

    /**
     * Execute appointment sync with saga pattern
     *
     * @param Appointment $appointment Appointment to sync (should be locked via lockForUpdate)
     * @param string $action Action to perform: 'create', 'update', 'cancel', 'reschedule'
     * @param array $syncData Data for Cal.com API call
     * @return array Sync result with status and response
     * @throws SagaException If critical steps fail
     */
    public function syncAppointmentToCalcom(
        Appointment $appointment,
        string $action,
        array $syncData
    ): array {
        $saga = new SagaOrchestrator("appointment_sync_{$action}");

        try {
            // Step 1: Make Cal.com API call
            $calcomResponse = $saga->executeStep(
                stepName: 'call_calcom_api',
                action: fn() => $this->makeCalcomCall($action, $syncData),
                compensation: function (array $response) {
                    // If Cal.com call succeeded but local update fails, no direct compensation
                    // because the Cal.com state is correct - just local DB is stale
                    // The appointment will be marked with error status and flagged for retry
                    Log::channel('saga')->info('Cal.com API succeeded, but local update will fail', [
                        'compensation' => 'Will mark appointment with error status for manual review',
                    ]);
                }
            );

            // Store original status in case we need to revert
            $originalStatus = $appointment->calcom_sync_status;
            $originalMetadata = $appointment->metadata;

            // Step 2: Update local appointment status and sync metadata
            $updated = $saga->executeStep(
                stepName: 'update_local_status',
                action: fn() => $this->updateAppointmentStatus($appointment, $calcomResponse, $action),
                compensation: function (array $updated) use ($appointment, $originalStatus, $originalMetadata) {
                    // Revert appointment status if local update failed
                    $this->dbCompensation->revertAppointmentStatus($appointment, $originalStatus);

                    Log::channel('saga')->warning('Reverted appointment status due to failed sync', [
                        'appointment_id' => $appointment->id,
                        'previous_status' => $originalStatus,
                    ]);
                }
            );

            // Step 3: Invalidate relevant caches (non-critical, won't trigger compensation on failure)
            $saga->executeOptionalStep(
                stepName: 'invalidate_cache',
                action: fn() => $this->invalidateSyncCache($appointment),
                compensation: function () {
                    // Cache invalidation is idempotent, no compensation needed
                },
                required: false
            );

            // Mark saga as completed successfully
            $saga->complete();

            Log::channel('saga')->info('✅ Appointment sync saga completed', [
                'saga_id' => $saga->getSagaId(),
                'appointment_id' => $appointment->id,
                'action' => $action,
                'calcom_status' => $calcomResponse['status'] ?? 'unknown',
            ]);

            return [
                'success' => true,
                'saga_id' => $saga->getSagaId(),
                'calcom_response' => $calcomResponse,
                'local_update' => $updated,
            ];

        } catch (SagaException $e) {
            Log::channel('saga')->warning('❌ Appointment sync saga failed', [
                'saga_id' => $e->sagaId,
                'failed_step' => $e->failedStep,
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
                'action' => 'Will retry on next sync cycle',
            ]);

            // Mark appointment for retry instead of hard failing
            $appointment->update([
                'calcom_sync_status' => 'error',
                'last_sync_attempt_at' => now(),
                'sync_error_message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'saga_id' => $e->sagaId,
                'error' => $e->getMessage(),
                'will_retry' => true,
            ];

        } catch (SagaCompensationException $e) {
            Log::channel('saga')->critical('🚨 CRITICAL: Sync compensation failed - data inconsistency risk', [
                'saga_id' => $e->sagaId,
                'appointment_id' => $appointment->id,
                'failed_compensations' => array_keys($e->failedCompensations),
                'action_required' => 'Manual review - appointment may be inconsistent between Cal.com and local DB',
            ]);

            // Mark for manual review
            $appointment->update([
                'calcom_sync_status' => 'manual_review_required',
                'sync_error_message' => 'Critical sync error - requires manual review',
            ]);

            throw $e;
        }
    }

    /**
     * Execute the actual Cal.com API call
     * @throws Exception If API call fails
     */
    private function makeCalcomCall(string $action, array $syncData): array
    {
        $response = match ($action) {
            'create' => $this->calcomService->createBooking($syncData),
            'cancel' => $this->calcomService->cancelBooking($syncData['booking_id'] ?? null),
            'reschedule' => $this->calcomService->updateBooking($syncData),
            default => throw new Exception("Unknown sync action: {$action}"),
        };

        if (!$response->successful()) {
            throw new Exception(
                "Cal.com API call failed ({$response->status()}): {$response->body()}"
            );
        }

        return $response->json();
    }

    /**
     * Update appointment status after successful Cal.com sync
     * @throws Exception If update fails
     */
    private function updateAppointmentStatus(
        Appointment $appointment,
        array $calcomResponse,
        string $action
    ): array {
        $updates = [
            'calcom_sync_status' => 'synced',
            'sync_verified_at' => now(),
            'last_sync_attempt_at' => now(),
            'sync_error_message' => null,
        ];

        // Update appointment based on action
        if ($action === 'cancel') {
            $updates['status'] = 'cancelled';
        } elseif ($action === 'reschedule') {
            $updates['status'] = 'rescheduled';
        }

        // Store Cal.com response metadata
        $metadata = $appointment->metadata ?? [];
        $metadata['last_calcom_response'] = [
            'timestamp' => now()->toIso8601String(),
            'action' => $action,
            'response' => $calcomResponse,
        ];
        $updates['metadata'] = $metadata;

        $appointment->update($updates);

        return $updates;
    }

    /**
     * Invalidate caches related to this appointment's sync
     */
    private function invalidateSyncCache(Appointment $appointment): void
    {
        $cacheKeys = [
            "appointment:{$appointment->id}",
            "availability:company:{$appointment->company_id}",
            "availability:branch:{$appointment->branch_id}",
            "staff:{$appointment->staff_id}:availability",
        ];

        $this->dbCompensation->invalidateCache($cacheKeys);
    }
}
