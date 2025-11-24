<?php

namespace App\Events\Appointments;

use App\Models\CallbackRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when customer requests a callback
 *
 * This event triggers:
 * - AssignCallbackToStaff listener (auto-assignment logic)
 * - SendCallbackConfirmation notification
 * - CRM integration updates
 */
class CallbackRequested
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly CallbackRequest $callbackRequest,
        public readonly ?string $preferredTime = null,
        public readonly ?string $topic = null
    ) {
        // Eager load relationships
        $this->callbackRequest->loadMissing(['customer', 'branch', 'service', 'assignedTo']);
    }

    /**
     * Get priority level based on request details
     */
    public function getPriority(): string
    {
        // High priority if urgent topic or VIP customer
        if ($this->topic && str_contains(strtolower($this->topic), 'urgent')) {
            return 'high';
        }

        // Check if customer has active appointments
        $hasActiveAppointments = $this->callbackRequest->customer
            ?->appointments()
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->exists();

        return $hasActiveAppointments ? 'medium' : 'normal';
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
            'call_id' => $this->callbackRequest->metadata['call_id'] ?? null,
            'preferred_time' => $this->preferredTime,
            'topic' => $this->topic,
            'priority' => $this->getPriority(),
            'requested_at' => $this->callbackRequest->created_at->toIso8601String(),
        ];
    }
}
