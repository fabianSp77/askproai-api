<?php

namespace App\Events\Appointments;

use App\Models\Appointment;
use App\Models\Customer;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a customer requests appointment cancellation
 *
 * This event triggers:
 * - SendCancellationNotifications listener
 * - UpdateModificationStats listener
 * - Potential audit logging
 */
class AppointmentCancellationRequested
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Appointment $appointment,
        public readonly ?string $reason,
        public readonly Customer $customer,
        public readonly float $fee = 0.0,
        public readonly bool $withinPolicy = true
    ) {
        // Eager load relationships to avoid N+1 in listeners
        $this->appointment->loadMissing(['service', 'staff', 'branch', 'company']);
    }

    /**
     * Get event context for logging
     */
    public function getContext(): array
    {
        return [
            'appointment_id' => $this->appointment->id,
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'service_name' => $this->appointment->service?->name,
            'starts_at' => $this->appointment->starts_at->toIso8601String(),
            'reason' => $this->reason,
            'fee' => $this->fee,
            'within_policy' => $this->withinPolicy,
        ];
    }
}
