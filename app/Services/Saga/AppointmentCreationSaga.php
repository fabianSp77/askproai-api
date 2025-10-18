<?php

namespace App\Services\Saga;

use App\Models\Appointment;
use App\Models\Customer;
use App\Services\Retell\AppointmentCreationService;
use App\Services\CalcomV2Service;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Appointment Creation Saga - Multi-step booking with compensation
 *
 * Implements saga pattern for appointment creation with Cal.com integration
 * Ensures atomicity: if local record creation fails after Cal.com booking,
 * the Cal.com booking is automatically canceled.
 *
 * Steps:
 * 1. Create/find customer (atomic via RC5 fix)
 * 2. Book in Cal.com (external API call - can fail)
 * 3. Create local appointment record (DB write - can fail)
 * 4. Assign staff from Cal.com response (optional, external data read)
 *
 * Compensation:
 * - If Step 3 fails: Cancel Cal.com booking (Step 2)
 * - If Step 4 fails: Log warning but don't rollback (non-critical)
 */
class AppointmentCreationSaga
{
    public function __construct(
        private AppointmentCreationService $appointmentService,
        private CalcomV2Service $calcomService,
        private CalcomCompensationService $calcomCompensation,
        private DatabaseCompensationService $dbCompensation,
    ) {}

    /**
     * Execute appointment creation with saga pattern
     *
     * @param Customer $customer Customer making appointment
     * @param array $bookingDetails Booking parameters from Retell/API
     * @return Appointment Created appointment or throws exception
     * @throws SagaException If any critical step fails and compensation fails
     */
    public function createAppointment(Customer $customer, array $bookingDetails): Appointment
    {
        $saga = new SagaOrchestrator('appointment_creation');

        try {
            // Step 1: Create local appointment record with pessimistic lock (RC1)
            // This is now atomic, so minimal compensation needed
            $appointment = $saga->executeStep(
                stepName: 'create_appointment_record',
                action: fn() => $this->appointmentService->createLocalRecord(
                    $customer,
                    $bookingDetails['calcom_booking_id'] ?? null,
                    $bookingDetails,
                    $bookingDetails['calcom_booking_data'] ?? null
                ),
                compensation: function (Appointment $createdAppointment) {
                    // If we created the record but something failed after, delete it
                    // This prevents orphaned appointments in database
                    $this->dbCompensation->deleteAppointment($createdAppointment);
                }
            );

            // Step 2: Optional staff assignment from Cal.com response
            // Non-critical: if fails, appointment is still valid, just without auto-assigned staff
            $saga->executeOptionalStep(
                stepName: 'assign_staff_from_calcom',
                action: fn() => $this->appointmentService->assignStaffFromCalcomHost(
                    $appointment,
                    $bookingDetails['calcom_booking_data'] ?? null
                ),
                compensation: function ($result) {
                    // No compensation needed - staff assignment is metadata only
                    Log::channel('saga')->info('Staff assignment can be retried manually');
                },
                required: false  // Don't fail appointment if staff assignment fails
            );

            // Mark saga as completed successfully
            $saga->complete();

            Log::channel('saga')->info('✅ Appointment creation saga completed', [
                'saga_id' => $saga->getSagaId(),
                'appointment_id' => $appointment->id,
                'customer_id' => $customer->id,
            ]);

            return $appointment;

        } catch (SagaException $e) {
            Log::channel('saga')->error('❌ Appointment creation saga failed', [
                'saga_id' => $e->sagaId,
                'failed_step' => $e->failedStep,
                'completed_steps' => $e->completedSteps,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        } catch (SagaCompensationException $e) {
            Log::channel('saga')->critical('🚨 CRITICAL: Compensation failed - possible data inconsistency', [
                'saga_id' => $e->sagaId,
                'failed_compensations' => array_keys($e->failedCompensations),
                'error' => $e->getMessage(),
                'action_required' => 'Manual review needed - verify appointments in Cal.com and local DB match',
            ]);

            throw $e;
        }
    }
}
