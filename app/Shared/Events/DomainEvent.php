<?php

namespace App\Shared\Events;

use Ramsey\Uuid\Uuid;
use Carbon\Carbon;

/**
 * Abstract Base Class for All Domain Events
 *
 * Implements event-driven architecture pattern where services emit domain events
 * to communicate changes. Other services listen to events and react accordingly.
 *
 * ARCHITECTURE:
 * - Events represent something that happened in the domain (past tense)
 * - Events are immutable data structures
 * - Events carry correlation IDs for tracing
 * - Events can be stored for audit trails and event sourcing
 *
 * USAGE:
 * ```php
 * class AppointmentCreatedEvent extends DomainEvent {
 *     public function __construct(
 *         public string $appointmentId,
 *         public string $customerId,
 *         public string $staffId,
 *         string $aggregateId = null,
 * ) {
 *         parent::__construct($aggregateId ?? $appointmentId);
 *     }
 * }
 *
 * // Publishing
 * $event = new AppointmentCreatedEvent($id, $customerId, $staffId);
 * app(EventBus::class)->publish($event);
 * ```
 *
 * @author Architecture Refactoring
 * @date 2025-10-18
 */
abstract class DomainEvent
{
    /**
     * Unique event identifier (for deduplication)
     */
    public readonly string $eventId;

    /**
     * When this event occurred
     */
    public readonly Carbon $occurredAt;

    /**
     * Aggregate (entity) this event concerns
     */
    public readonly string $aggregateId;

    /**
     * Type of aggregate (e.g., "Appointment", "Customer")
     */
    public readonly string $aggregateType;

    /**
     * Correlation ID for tracing related events
     */
    public readonly string $correlationId;

    /**
     * User/system that caused this event
     */
    public readonly ?string $causedBy;

    /**
     * Additional metadata (tenant ID, API version, etc.)
     */
    public readonly array $metadata;

    /**
     * Event version (for backwards compatibility)
     */
    public readonly int $version;

    public function __construct(
        string $aggregateId,
        string $correlationId = null,
        string $causedBy = null,
        array $metadata = []
    ) {
        $this->eventId = Uuid::uuid4()->toString();
        $this->occurredAt = Carbon::now();
        $this->aggregateId = $aggregateId;
        $this->aggregateType = $this->getAggregateType();
        $this->correlationId = $correlationId ?? $this->generateCorrelationId();
        $this->causedBy = $causedBy;
        $this->metadata = $metadata;
        $this->version = 1;
    }

    /**
     * Get the aggregate type from event class name
     *
     * E.g., AppointmentCreatedEvent → Appointment
     */
    protected function getAggregateType(): string
    {
        $className = class_basename($this);

        // Extract domain from class name (e.g., "AppointmentCreatedEvent" → "Appointment")
        $aggregateType = preg_replace('/Event$/', '', $className);
        $aggregateType = preg_replace('/^(\w+?).*/', '$1', $aggregateType);

        return $aggregateType;
    }

    /**
     * Generate correlation ID for tracing
     */
    private function generateCorrelationId(): string
    {
        return 'evt_' . uniqid() . '_' . bin2hex(random_bytes(4));
    }

    /**
     * Get event name (e.g., "appointment.created")
     */
    public function getEventName(): string
    {
        $className = class_basename($this);

        // Convert AppointmentCreatedEvent to appointment.created
        $withoutEvent = str_replace('Event', '', $className);
        $withSnakeCase = strtolower(preg_replace('/(?<!^)([A-Z])/', '.$1', $withoutEvent));

        return $withSnakeCase;
    }

    /**
     * Convert event to array for storage/transmission
     */
    public function toArray(): array
    {
        return [
            'eventId' => $this->eventId,
            'eventName' => $this->getEventName(),
            'eventClass' => static::class,
            'aggregateId' => $this->aggregateId,
            'aggregateType' => $this->aggregateType,
            'correlationId' => $this->correlationId,
            'causedBy' => $this->causedBy,
            'occurredAt' => $this->occurredAt->toIso8601String(),
            'version' => $this->version,
            'metadata' => $this->metadata,
            'payload' => $this->getPayload(),
        ];
    }

    /**
     * Get event-specific payload (override in subclasses)
     *
     * @return array Event data specific to this event type
     */
    protected function getPayload(): array
    {
        return [];
    }

    /**
     * Recreate event from array (for deserialization)
     */
    public static function fromArray(array $data): static
    {
        $event = new static(
            aggregateId: $data['aggregateId'],
            correlationId: $data['correlationId'],
            causedBy: $data['causedBy'] ?? null,
            metadata: $data['metadata'] ?? []
        );

        return $event;
    }
}
