<?php

namespace App\Services\Strategies;

/**
 * Data Transfer Object for host matching context
 * Provides tenant isolation and booking context for matching strategies
 */
class HostMatchContext
{
    public function __construct(
        public int $companyId,              // Required: tenant isolation
        public ?string $branchId = null,    // Optional: branch-level filtering
        public ?int $serviceId = null,      // Optional: service context
        public ?array $calcomBooking = null // Optional: full Cal.com booking data
    ) {}
}
