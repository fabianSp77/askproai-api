<?php

namespace App\Domains\Appointments\Events;

use App\Shared\Events\DomainEvent;

/**
 * AppointmentCancelledEvent
 *
 * Fired when an appointment is cancelled.
 *
 * LISTENERS:
 * - SendCancellationEmailListener
 * - CalcomCancellationListener
 * - RefundProcessListener
 * - AuditEventListener
 *
 * @author Phase 5 Architecture Refactoring
 * @date 2025-10-18
 */
class AppointmentCancelledEvent extends DomainEvent
{
    public function __construct(
        public string $appointmentId,
        public string $customerId,
        public string $reason = 'customer_requested',
        public bool $refundable = true,
        string $correlationId = null,
        string $causedBy = null,
        array $metadata = []
    ) {
        parent::__construct(
            aggregateId: $appointmentId,
            correlationId: $correlationId,
            causedBy: $causedBy,
            metadata: $metadata
        );
    }

    protected function getPayload(): array
    {
        return [
            'appointmentId' => $this->appointmentId,
            'customerId' => $this->customerId,
            'reason' => $this->reason,
            'refundable' => $this->refundable,
        ];
    }
}
