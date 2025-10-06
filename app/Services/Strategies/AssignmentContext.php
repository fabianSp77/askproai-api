<?php

namespace App\Services\Strategies;

/**
 * Assignment Context DTO
 *
 * Provides context for staff assignment strategies including:
 * - Tenant isolation (company_id)
 * - Service requirements (service_id)
 * - Scheduling constraints (start_at, end_at)
 * - Branch filtering (optional)
 * - Cal.com integration data (optional)
 */
class AssignmentContext
{
    public function __construct(
        public int $companyId,              // Required: tenant isolation
        public int $serviceId,              // Required: service being booked
        public \DateTime $startsAt,         // Required: appointment start time
        public \DateTime $endsAt,           // Required: appointment end time
        public ?string $branchId = null,    // Optional: branch filter
        public ?array $calcomBooking = null,// Optional: Cal.com booking data
        public ?string $calcomHostId = null,// Optional: Cal.com host ID
        public ?int $customerId = null,     // Optional: customer preferences
        public array $metadata = []          // Optional: additional context
    ) {}

    /**
     * Get duration in minutes
     */
    public function getDurationMinutes(): int
    {
        $diff = $this->endsAt->getTimestamp() - $this->startsAt->getTimestamp();
        return (int) ceil($diff / 60);
    }

    /**
     * Check if Cal.com integration is involved
     */
    public function isCalcomBooking(): bool
    {
        return !empty($this->calcomBooking) || !empty($this->calcomHostId);
    }

    /**
     * Get Cal.com host ID if available
     */
    public function getCalcomHostId(): ?string
    {
        return $this->calcomHostId
            ?? $this->calcomBooking['hosts'][0]['id'] ?? null
            ?? $this->calcomBooking['organizer']['id'] ?? null;
    }
}
