<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * CallbackRequestResource
 *
 * API Resource for transforming CallbackRequest models to JSON responses.
 * Includes relationships, computed attributes, and GDPR-compliant data exposure.
 */
class CallbackRequestResource extends JsonResource
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
            'customer_id' => $this->customer_id,
            'customer_name' => $this->customer_name,
            'phone_number' => $this->phone_number, // Consider masking for non-admin users
            'branch_id' => $this->branch_id,
            'service_id' => $this->service_id,
            'staff_id' => $this->staff_id,
            'assigned_to' => $this->assigned_to,
            'preferred_time_window' => $this->preferred_time_window,
            'priority' => $this->priority,
            'status' => $this->status,
            'notes' => $this->notes,
            'metadata' => $this->metadata,

            // Timestamps
            'assigned_at' => $this->assigned_at?->toIso8601String(),
            'contacted_at' => $this->contacted_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),

            // Computed attributes
            'is_overdue' => $this->is_overdue,

            // Relationships (loaded conditionally)
            'customer' => $this->whenLoaded('customer', function () {
                return [
                    'id' => $this->customer->id,
                    'name' => $this->customer->name,
                    'email' => $this->customer->email,
                ];
            }),

            'branch' => $this->whenLoaded('branch', function () {
                return [
                    'id' => $this->branch->id,
                    'name' => $this->branch->name,
                    'address' => $this->branch->address,
                    'phone' => $this->branch->phone,
                ];
            }),

            'service' => $this->whenLoaded('service', function () {
                return [
                    'id' => $this->service->id,
                    'name' => $this->service->name,
                    'duration' => $this->service->duration,
                    'price' => $this->service->price,
                ];
            }),

            'staff' => $this->whenLoaded('staff', function () {
                return [
                    'id' => $this->staff->id,
                    'name' => $this->staff->name,
                ];
            }),

            'assigned_to_staff' => $this->whenLoaded('assignedTo', function () {
                return [
                    'id' => $this->assignedTo->id,
                    'name' => $this->assignedTo->name,
                ];
            }),

            'escalations' => $this->whenLoaded('escalations', function () {
                return $this->escalations->map(function ($escalation) {
                    return [
                        'id' => $escalation->id,
                        'reason' => $escalation->escalation_reason,
                        'escalated_from' => $escalation->escalated_from,
                        'escalated_to' => $escalation->escalated_to,
                        'escalated_at' => $escalation->escalated_at?->toIso8601String(),
                    ];
                });
            }),
        ];
    }
}
