<?php

namespace App\Events\Appointments;

use App\Models\Appointment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when appointment is successfully booked
 *
 * This event triggers:
 * - InvalidateSlotsCache listener (CRITICAL: cache invalidation to prevent double-bookings)
 * - SendBookingConfirmation listener (customer notification)
 * - UpdateAvailabilityStats listener (analytics)
 *
 * Fired from:
 * - AppointmentCreationService::createLocalRecord() (Retell phone bookings)
 * - CalcomWebhookController::handleBookingCreated() (Cal.com webhook bookings)
 * - Manual admin bookings via Filament
 */
class AppointmentBooked
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Appointment $appointment
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
            'service_id' => $this->appointment->service_id,
            'service_name' => $this->appointment->service?->name,
            'company_id' => $this->appointment->company_id,
            'branch_id' => $this->appointment->branch_id,
            'starts_at' => $this->appointment->starts_at->toIso8601String(),
            'ends_at' => $this->appointment->ends_at->toIso8601String(),
            'calcom_event_type_id' => $this->appointment->service?->calcom_event_type_id,
            'booking_source' => $this->appointment->booking_source ?? 'unknown',
        ];
    }
}
