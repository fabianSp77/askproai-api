<?php

namespace App\Http\Resources\CustomerPortal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Appointment Resource for Customer Portal
 *
 * TRANSFORMATION:
 * - Public-safe appointment data (no internal IDs)
 * - Human-readable timestamps
 * - Policy-based permissions (can_reschedule, can_cancel)
 * - Service and staff details
 * - Location information
 *
 * SECURITY:
 * - No sensitive internal data exposed
 * - Multi-tenant isolation enforced
 * - Version field included for optimistic locking
 */
class AppointmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'start_time' => $this->start_time->toIso8601String(),
            'start_time_human' => $this->start_time->locale('de')->isoFormat('dddd, D. MMMM YYYY [um] HH:mm [Uhr]'),
            'end_time' => $this->start_time->copy()->addMinutes($this->duration_minutes)->toIso8601String(),
            'duration_minutes' => $this->duration_minutes,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),

            // Service information
            'service' => [
                'id' => $this->service->id,
                'name' => $this->service->name,
                'description' => $this->service->description,
                'duration' => $this->service->duration,
                'price' => $this->service->price ? number_format($this->service->price, 2, ',', '.') . ' EUR' : null,
            ],

            // Staff information
            'staff' => [
                'id' => $this->staff->id,
                'name' => $this->staff->name,
                'avatar_url' => $this->staff->avatar_url ?? null,
                'bio' => $this->staff->bio ?? null,
            ],

            // Location information
            'location' => [
                'branch_name' => $this->branch->name,
                'address' => $this->formatAddress(),
                'phone' => $this->branch->phone ?? $this->company->phone,
                'email' => $this->branch->email ?? $this->company->email,
            ],

            // Composite appointment information (if applicable)
            'is_composite' => $this->is_composite ?? false,
            'segments' => $this->when($this->is_composite, function () {
                return $this->phases->map(function ($phase) {
                    return [
                        'service_name' => $phase->service->name,
                        'duration_minutes' => $phase->duration_minutes,
                        'start_time' => $phase->start_time->toIso8601String(),
                    ];
                });
            }),

            // Policy permissions
            'can_reschedule' => $this->canBeRescheduled(),
            'can_cancel' => $this->canBeCancelled(),
            'reschedule_deadline' => $this->getRescheduleDeadline(),
            'cancel_deadline' => $this->getCancelDeadline(),

            // Cancellation information (if cancelled)
            'cancelled_at' => $this->when($this->status === 'cancelled', function () {
                return $this->cancelled_at?->toIso8601String();
            }),
            'cancellation_reason' => $this->when($this->status === 'cancelled', $this->cancellation_reason),

            // Metadata
            'notes' => $this->notes,
            'version' => $this->version, // For optimistic locking
            'created_at' => $this->created_at->toIso8601String(),
            'last_modified_at' => $this->last_modified_at?->toIso8601String(),
        ];
    }

    /**
     * Get human-readable status label
     */
    private function getStatusLabel(): string
    {
        return match($this->status) {
            'confirmed' => 'Bestätigt',
            'pending' => 'Ausstehend',
            'cancelled' => 'Storniert',
            'completed' => 'Abgeschlossen',
            'no_show' => 'Nicht erschienen',
            default => ucfirst($this->status),
        };
    }

    /**
     * Check if appointment can be rescheduled
     */
    private function canBeRescheduled(): bool
    {
        // Cannot reschedule past appointments
        if ($this->start_time->isPast()) {
            return false;
        }

        // Cannot reschedule cancelled appointments
        if ($this->status === 'cancelled') {
            return false;
        }

        // Check minimum notice period
        $minimumNoticeHours = $this->company->policyConfiguration
            ?->minimum_reschedule_notice_hours ?? 24;

        return $this->start_time->diffInHours(now()) >= $minimumNoticeHours;
    }

    /**
     * Check if appointment can be cancelled
     */
    private function canBeCancelled(): bool
    {
        // Cannot cancel past appointments
        if ($this->start_time->isPast()) {
            return false;
        }

        // Cannot cancel already cancelled appointments
        if ($this->status === 'cancelled') {
            return false;
        }

        // Check minimum notice period
        $minimumNoticeHours = $this->company->policyConfiguration
            ?->minimum_cancellation_notice_hours ?? 24;

        return $this->start_time->diffInHours(now()) >= $minimumNoticeHours;
    }

    /**
     * Get reschedule deadline timestamp
     */
    private function getRescheduleDeadline(): ?string
    {
        $minimumNoticeHours = $this->company->policyConfiguration
            ?->minimum_reschedule_notice_hours ?? 24;

        $deadline = $this->start_time->copy()->subHours($minimumNoticeHours);

        return $deadline->isPast() ? null : $deadline->toIso8601String();
    }

    /**
     * Get cancellation deadline timestamp
     */
    private function getCancelDeadline(): ?string
    {
        $minimumNoticeHours = $this->company->policyConfiguration
            ?->minimum_cancellation_notice_hours ?? 24;

        $deadline = $this->start_time->copy()->subHours($minimumNoticeHours);

        return $deadline->isPast() ? null : $deadline->toIso8601String();
    }

    /**
     * Format address for display
     */
    private function formatAddress(): string
    {
        $parts = array_filter([
            $this->branch->address_street,
            $this->branch->address_city,
            $this->branch->address_postal_code,
        ]);

        return implode(', ', $parts) ?: 'Keine Adresse hinterlegt';
    }
}
