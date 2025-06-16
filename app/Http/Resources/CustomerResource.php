<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'birthdate' => $this->birthdate?->format('Y-m-d'),
            'age' => $this->birthdate ? $this->birthdate->age : null,
            'notes' => $this->notes,
            'tags' => $this->tags ?? [],
            'is_blocked' => $this->is_blocked ?? false,
            'blocked_at' => $this->blocked_at?->toIso8601String(),
            'block_reason' => $this->block_reason,
            'no_show_count' => $this->no_show_count ?? 0,
            'appointments_count' => $this->whenCounted('appointments'),
            'last_appointment' => $this->whenLoaded('appointments', function () {
                $last = $this->appointments->sortByDesc('starts_at')->first();
                return $last ? [
                    'id' => $last->id,
                    'starts_at' => $last->starts_at->toIso8601String(),
                    'status' => $last->status,
                ] : null;
            }),
            'next_appointment' => $this->whenLoaded('appointments', function () {
                $next = $this->appointments
                    ->where('starts_at', '>', now())
                    ->sortBy('starts_at')
                    ->first();
                return $next ? [
                    'id' => $next->id,
                    'starts_at' => $next->starts_at->toIso8601String(),
                    'status' => $next->status,
                ] : null;
            }),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}