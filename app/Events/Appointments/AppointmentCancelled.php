<?php

namespace App\Events\Appointments;

use App\Models\Appointment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when appointment is cancelled
 *
 * CRITICAL: Triggers cache invalidation to free up the cancelled slot
 * making it available for booking again
 *
 * This event triggers:
 * - InvalidateSlotsCache listener (restore availability)
 * - SendCancellationNotifications listener (notify customer and staff)
 * - UpdateModificationStats listener (track cancellation metrics)
 *
 * Fired from:
 * - CalcomWebhookController::handleBookingCancelled() (Cal.com cancellations)
 * - AppointmentResource::cancel() (admin cancellations)
 * - Automated cancellation policies
 */
class AppointmentCancelled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Appointment $appointment,
        public readonly ?string $reason = null,
        public readonly ?string $cancelledBy = 'customer'
    ) {
        // Eager load relationships to prevent N+1 queries in listeners
        $this->appointment->loadMissing(['service', 'customer', 'branch', 'company']);
    }

    /**
     * Get event context for logging and monitoring
     */
    public function getContext(): array
    {
        return [
            'appointment_id' => $this->appointment->id,
            'customer_id' => $this->appointment->customer_id,
            'customer_name' => $this->appointment->customer?->name,
            'starts_at' => $this->appointment->starts_at->toIso8601String(),
            'service_id' => $this->appointment->service_id,
            'service_name' => $this->appointment->service?->name,
            'calcom_event_type_id' => $this->appointment->service?->calcom_event_type_id,
            'company_id' => $this->appointment->company_id,
            'branch_id' => $this->appointment->branch_id,
            'reason' => $this->reason,
            'cancelled_by' => $this->cancelledBy,
            'cancellation_source' => $this->appointment->cancellation_source ?? 'unknown',
        ];
    }
}
