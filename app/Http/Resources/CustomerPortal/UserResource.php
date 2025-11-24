<?php

namespace App\Http\Resources\CustomerPortal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * User Resource for Customer Portal
 *
 * TRANSFORMATION:
 * - Customer profile data
 * - Company information
 * - Role information (if applicable)
 *
 * SECURITY:
 * - No password or sensitive data exposed
 * - Multi-tenant isolation enforced
 */
class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),

            // Customer information
            'customer' => $this->when($this->customer_id, function () {
                return [
                    'id' => $this->customer->id,
                    'name' => $this->customer->name,
                    'phone' => $this->customer->phone,
                    'email' => $this->customer->email,
                ];
            }),

            // Company information
            'company' => [
                'id' => $this->company->id,
                'name' => $this->company->name,
                'email' => $this->company->email,
                'phone' => $this->company->phone,
            ],

            // Role information (if applicable)
            'role' => $this->when($this->roles->isNotEmpty(), function () {
                return $this->roles->first()->name;
            }),

            // Timestamps
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
