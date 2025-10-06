<?php

namespace App\Events\Appointments;

use App\Models\Appointment;
use App\ValueObjects\PolicyResult;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a policy violation is detected
 *
 * This event triggers:
 * - TriggerPolicyEnforcement listener (logging, alerting)
 * - Potential escalation to managers
 * - Compliance audit trail
 */
class AppointmentPolicyViolation
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Appointment $appointment,
        public readonly PolicyResult $policyResult,
        public readonly string $attemptedAction, // 'cancel' or 'reschedule'
        public readonly ?string $source = 'retell_ai' // Where violation occurred
    ) {
        // Eager load relationships
        $this->appointment->loadMissing(['customer', 'service', 'staff', 'branch', 'company']);
    }

    /**
     * Get violation severity
     */
    public function getSeverity(): string
    {
        // Determine severity based on reason
        if (str_contains($this->policyResult->reason, 'quota exceeded')) {
            return 'high';
        }

        if (str_contains($this->policyResult->reason, 'deadline missed')) {
            return 'medium';
        }

        return 'low';
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
            'company_id' => $this->appointment->company_id,
            'attempted_action' => $this->attemptedAction,
            'violation_reason' => $this->policyResult->reason,
            'severity' => $this->getSeverity(),
            'policy_details' => $this->policyResult->details,
            'source' => $this->source,
        ];
    }
}
