<?php

namespace App\Services\Strategies;

use App\Models\Staff;

/**
 * Assignment Result DTO
 *
 * Contains the result of a staff assignment attempt including:
 * - Assigned staff member (or null if failed)
 * - Assignment model used (any_staff, service_staff, manual)
 * - Whether fallback was used
 * - Audit metadata for tracking
 */
class AssignmentResult
{
    public function __construct(
        public ?Staff $staff,               // Assigned staff or null
        public string $model,               // Model used: any_staff, service_staff, manual
        public bool $wasFallback,           // Was fallback model used?
        public string $reason,              // Human-readable reason
        public array $metadata = []          // Audit trail metadata
    ) {}

    /**
     * Check if assignment was successful
     */
    public function isSuccessful(): bool
    {
        return $this->staff !== null;
    }

    /**
     * Check if assignment failed
     */
    public function isFailed(): bool
    {
        return $this->staff === null;
    }

    /**
     * Get staff ID or null
     */
    public function getStaffId(): ?string
    {
        return $this->staff?->id;
    }

    /**
     * Create failed assignment result
     */
    public static function failed(string $model, string $reason, array $metadata = []): self
    {
        return new self(
            staff: null,
            model: $model,
            wasFallback: false,
            reason: $reason,
            metadata: $metadata
        );
    }

    /**
     * Create successful assignment result
     */
    public static function success(
        Staff $staff,
        string $model,
        string $reason,
        bool $wasFallback = false,
        array $metadata = []
    ): self {
        return new self(
            staff: $staff,
            model: $model,
            wasFallback: $wasFallback,
            reason: $reason,
            metadata: $metadata
        );
    }

    /**
     * Convert to appointment metadata array
     */
    public function toAppointmentMetadata(): array
    {
        return [
            'assignment_model_used' => $this->model,
            'was_fallback' => $this->wasFallback,
            'assignment_metadata' => array_merge($this->metadata, [
                'reason' => $this->reason,
                'assigned_at' => now()->toIso8601String(),
                'staff_id' => $this->getStaffId(),
            ]),
        ];
    }
}
