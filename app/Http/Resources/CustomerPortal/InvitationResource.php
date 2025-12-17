<?php

namespace App\Http\Resources\CustomerPortal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Invitation Resource for Customer Portal
 *
 * TRANSFORMATION:
 * - Invitation status and metadata
 * - Company information
 * - Role information
 * - Expiry information
 *
 * SECURITY:
 * - Token never exposed in responses
 * - Only non-sensitive information
 */
class InvitationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'email' => $this->email,
            'phone' => $this->metadata['phone'] ?? null,
            'status' => $this->getStatus(),
            'expires_at' => $this->expires_at->toIso8601String(),
            'expires_at_human' => $this->expires_at->locale('de')->diffForHumans(),
            'is_expired' => $this->isExpired(),
            'is_accepted' => !is_null($this->accepted_at),

            // Company information
            'company' => [
                'id' => $this->company->id,
                'name' => $this->company->name,
            ],

            // Role information
            'role' => [
                'id' => $this->role->id,
                'name' => $this->role->name,
                'display_name' => $this->getRoleDisplayName(),
            ],

            // Metadata
            'invited_at' => $this->created_at->toIso8601String(),
            'invited_by' => $this->inviter->name,
        ];
    }

    /**
     * Get invitation status
     */
    private function getStatus(): string
    {
        if ($this->accepted_at) {
            return 'accepted';
        }

        if ($this->isExpired()) {
            return 'expired';
        }

        return 'pending';
    }

    /**
     * Get human-readable role display name
     */
    private function getRoleDisplayName(): string
    {
        return match($this->role->name) {
            'viewer' => 'Betrachter',
            'operator' => 'Bearbeiter',
            'manager' => 'Verwalter',
            'owner' => 'Inhaber',
            'admin' => 'Administrator',
            'company_manager' => 'Filialleiter',
            'company_staff' => 'Mitarbeiter',
            default => ucfirst($this->role->name),
        };
    }
}
