<?php

namespace App\Events\Appointments;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when appointment is successfully rescheduled
 *
 * This event triggers:
 * - SendRescheduleNotifications listener
 * - UpdateModificationStats listener
 * - Calendar sync updates
 */
class AppointmentRescheduled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Appointment $appointment,
        public readonly Carbon $oldStartTime,
        public readonly Carbon $newStartTime,
        public readonly ?string $reason = null,
        public readonly float $fee = 0.0,
        public readonly bool $withinPolicy = true
    ) {
        // Eager load relationships
        $this->appointment->loadMissing(['customer', 'service', 'staff', 'branch', 'company']);
    }

    /**
     * Get time difference in hours
     */
    public function getTimeDiffHours(): float
    {
        return $this->oldStartTime->diffInHours($this->newStartTime, false);
    }

    /**
     * Get event context for logging
     */
    public function getContext(): array
    {
        return [
            'appointment_id' => $this->appointment->id,
            'customer_id' => $this->appointment->customer_id,
            'customer_name' => $this->appointment->customer?->name,
            'old_start_time' => $this->oldStartTime->toIso8601String(),
            'new_start_time' => $this->newStartTime->toIso8601String(),
            'time_diff_hours' => $this->getTimeDiffHours(),
            'reason' => $this->reason,
            'fee' => $this->fee,
            'within_policy' => $this->withinPolicy,
        ];
    }
}
