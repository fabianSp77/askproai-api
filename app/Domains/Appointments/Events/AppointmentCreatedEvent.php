<?php

namespace App\Domains\Appointments\Events;

use App\Shared\Events\DomainEvent;

/**
 * AppointmentCreatedEvent
 *
 * Fired when a new appointment is successfully created.
 * Listeners can react by sending confirmations, syncing to Cal.com, etc.
 *
 * LISTENERS:
 * - SendConfirmationEmailListener
 * - CalcomSyncListener
 * - AnalyticsEventListener
 * - AuditEventListener
 *
 * @author Phase 5 Architecture Refactoring
 * @date 2025-10-18
 */
class AppointmentCreatedEvent extends DomainEvent
{
    public function __construct(
        public string $appointmentId,
        public string $customerId,
        public string $staffId,
        public string $serviceId,
        public string $branchId,
        public \DateTime $appointmentStart,
        public \DateTime $appointmentEnd,
        public string $source = 'voice_ai', // 'voice_ai', 'web', 'api', etc
        string $correlationId = null,
        string $causedBy = null,
        array $metadata = []
    ) {
        parent::__construct(
            aggregateId: $appointmentId,
            correlationId: $correlationId,
            causedBy: $causedBy,
            metadata: array_merge($metadata, ['source' => $source])
        );
    }

    protected function getPayload(): array
    {
        return [
            'appointmentId' => $this->appointmentId,
            'customerId' => $this->customerId,
            'staffId' => $this->staffId,
            'serviceId' => $this->serviceId,
            'branchId' => $this->branchId,
            'appointmentStart' => $this->appointmentStart->toIso8601String(),
            'appointmentEnd' => $this->appointmentEnd->toIso8601String(),
            'source' => $this->source,
        ];
    }
}
