<?php

namespace App\Services\CustomerPortal;

use App\Models\Appointment;
use Carbon\Carbon;

/**
 * Reschedule Result DTO
 *
 * Immutable data transfer object for reschedule operation results
 */
readonly class RescheduleResult
{
    public function __construct(
        public bool $success,
        public Appointment $appointment,
        public Carbon $oldStartTime,
        public Carbon $newStartTime,
        public ?string $calcomBookingId = null,
        public ?string $message = null,
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'appointment' => [
                'id' => $this->appointment->id,
                'customer_name' => $this->appointment->customer_name,
                'service' => $this->appointment->service?->name,
                'staff' => $this->appointment->staff?->name,
                'old_time' => $this->oldStartTime->toIso8601String(),
                'new_time' => $this->newStartTime->toIso8601String(),
                'duration_minutes' => $this->appointment->duration_minutes,
                'version' => $this->appointment->version,
            ],
            'calcom_booking_id' => $this->calcomBookingId,
            'message' => $this->message ?? 'Appointment rescheduled successfully.',
        ];
    }
}
