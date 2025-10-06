<?php

namespace App\Events\Appointments;

use App\Models\CallbackRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when callback request is escalated
 *
 * Escalation triggers:
 * - Callback not handled within SLA timeframe
 * - Customer requested manager/supervisor
 * - Complex issue requiring senior staff
 *
 * This event triggers:
 * - NotifyManagers listener
 * - UpdateEscalationStats
 * - CRM escalation logging
 */
class CallbackEscalated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly CallbackRequest $callbackRequest,
        public readonly string $reason,
        public readonly ?string $escalationType = 'auto', // 'auto', 'manual', 'sla_breach'
        public readonly ?string $escalatedToStaffId = null
    ) {
        // Eager load relationships
        $this->callbackRequest->loadMissing([
            'customer',
            'branch',
            'service',
            'assignedTo',
            'escalations'
        ]);
    }

    /**
     * Get escalation level (1st, 2nd, 3rd tier)
     */
    public function getEscalationLevel(): int
    {
        $escalationCount = $this->callbackRequest->escalations()->count();
        return min($escalationCount + 1, 3); // Max 3 levels
    }

    /**
     * Check if SLA breach
     */
    public function isSlaBreach(): bool
    {
        return $this->escalationType === 'sla_breach' ||
               str_contains(strtolower($this->reason), 'sla');
    }

    /**
     * Get event context for logging
     */
    public function getContext(): array
    {
        return [
            'callback_request_id' => $this->callbackRequest->id,
            'customer_id' => $this->callbackRequest->customer_id,
            'customer_name' => $this->callbackRequest->customer?->name,
            'assigned_staff_id' => $this->callbackRequest->assigned_to,
            'escalated_to_staff_id' => $this->escalatedToStaffId,
            'escalation_reason' => $this->reason,
            'escalation_type' => $this->escalationType,
            'escalation_level' => $this->getEscalationLevel(),
            'is_sla_breach' => $this->isSlaBreach(),
            'created_at' => $this->callbackRequest->created_at->toIso8601String(),
        ];
    }
}
