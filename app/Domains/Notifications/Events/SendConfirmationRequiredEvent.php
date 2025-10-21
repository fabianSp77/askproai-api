<?php

namespace App\Domains\Notifications\Events;

use App\Shared\Events\DomainEvent;

/**
 * SendConfirmationRequiredEvent
 *
 * Fired when a confirmation notification should be sent.
 * Decouples appointment creation from notification delivery.
 *
 * LISTENERS:
 * - EmailConfirmationListener
 * - SmsConfirmationListener
 *
 * @author Phase 5 Architecture Refactoring
 * @date 2025-10-18
 */
class SendConfirmationRequiredEvent extends DomainEvent
{
    public function __construct(
        public string $appointmentId,
        public string $customerId,
        public string $customerEmail,
        public string $customerPhone,
        public array $appointmentDetails,
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
            'customerEmail' => $this->customerEmail,
            'customerPhone' => $this->customerPhone,
            'appointmentDetails' => $this->appointmentDetails,
        ];
    }
}
