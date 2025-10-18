<?php

namespace App\Services\Saga;

use App\Models\Appointment;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Compensation handlers for database operations
 *
 * Handles rollback of local changes when external operations fail
 */
class DatabaseCompensationService
{
    /**
     * Delete created appointment (compensate for failed Cal.com sync)
     *
     * Called when:
     * - Local appointment record created successfully
     * - But Cal.com sync failed (and can't be recovered)
     * - Need to delete orphaned local record
     *
     * @param Appointment|int $appointment Appointment model or ID
     * @throws Exception If deletion fails
     */
    public function deleteAppointment($appointment): void
    {
        $id = $appointment instanceof Appointment ? $appointment->id : $appointment;

        Log::channel('saga')->warning('🗑️ Compensating: Deleting local appointment', [
            'appointment_id' => $id,
            'reason' => 'Cal.com sync failed - cannot keep orphaned local record',
        ]);

        try {
            $appointment = $appointment instanceof Appointment
                ? $appointment
                : Appointment::findOrFail($id);

            // Soft delete to preserve audit trail
            $appointment->delete();

            Log::channel('saga')->info('✅ Appointment deleted (soft delete)', [
                'appointment_id' => $id,
            ]);

        } catch (Exception $e) {
            Log::channel('saga')->error('🚨 CRITICAL: Failed to delete orphaned appointment', [
                'appointment_id' => $id,
                'error' => $e->getMessage(),
                'action_required' => 'Manual cleanup needed - orphaned appointment in database',
            ]);

            throw $e;
        }
    }

    /**
     * Revert appointment status change (compensate for failed sync)
     *
     * Called when:
     * - Appointment status updated locally
     * - Cal.com sync failed
     * - Need to revert to previous status
     *
     * @param Appointment $appointment
     * @param string $previousStatus Status to revert to
     * @throws Exception If update fails
     */
    public function revertAppointmentStatus(Appointment $appointment, string $previousStatus): void
    {
        Log::channel('saga')->info('⏮️ Compensating: Reverting appointment status', [
            'appointment_id' => $appointment->id,
            'current_status' => $appointment->status,
            'revert_to' => $previousStatus,
        ]);

        try {
            $appointment->update(['status' => $previousStatus]);

            Log::channel('saga')->info('✅ Appointment status reverted', [
                'appointment_id' => $appointment->id,
                'status' => $previousStatus,
            ]);

        } catch (Exception $e) {
            Log::channel('saga')->error('❌ Failed to revert appointment status', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Delete composite appointment and segments (compensate for failed segment booking)
     *
     * Called when:
     * - Composite appointment record created locally
     * - But later segment booking failed (and couldn't be compensated)
     * - Need to rollback entire composite
     *
     * @param int $compositeId Composite appointment ID
     * @throws Exception If deletion fails
     */
    public function deleteCompositeAppointment(int $compositeId): void
    {
        Log::channel('saga')->warning('🗑️ Compensating: Deleting composite appointment', [
            'composite_id' => $compositeId,
            'reason' => 'Composite booking compensation failed',
        ]);

        try {
            // Find composite appointment
            $composite = Appointment::findOrFail($compositeId);

            if (!$composite->is_composite) {
                throw new Exception('Appointment is not composite - cannot use composite deletion');
            }

            // Delete all related segment records
            if (isset($composite->segments) && is_array($composite->segments)) {
                foreach ($composite->segments as $segment) {
                    if (isset($segment['local_appointment_id'])) {
                        Appointment::findOrFail($segment['local_appointment_id'])->delete();
                    }
                }
            }

            // Delete composite parent
            $composite->delete();

            Log::channel('saga')->info('✅ Composite appointment deleted', [
                'composite_id' => $compositeId,
            ]);

        } catch (Exception $e) {
            Log::channel('saga')->error('🚨 CRITICAL: Failed to delete composite appointment', [
                'composite_id' => $compositeId,
                'error' => $e->getMessage(),
                'action_required' => 'Manual cleanup needed - orphaned composite appointment',
            ]);

            throw $e;
        }
    }

    /**
     * Invalidate cache after successful operation (cleanup step)
     *
     * Called as final step to ensure cache is invalidated
     * Less critical than other compensation - failure doesn't rollback
     *
     * @param string|array $cacheKeys Cache key(s) to invalidate
     */
    public function invalidateCache($cacheKeys): void
    {
        $keys = is_array($cacheKeys) ? $cacheKeys : [$cacheKeys];

        try {
            foreach ($keys as $key) {
                cache()->forget($key);
            }

            Log::channel('saga')->debug('🧹 Cache invalidated', [
                'keys_count' => count($keys),
            ]);

        } catch (Exception $e) {
            // Log but don't fail - cache invalidation is non-critical
            Log::channel('saga')->warning('⚠️ Cache invalidation failed (non-critical)', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
