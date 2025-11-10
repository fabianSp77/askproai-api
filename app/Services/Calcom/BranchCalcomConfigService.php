<?php

namespace App\Services\Calcom;

use App\Models\Branch;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Branch Cal.com Configuration Service
 *
 * Provides Cal.com configuration for specific branches,
 * including event types, team IDs, and default settings.
 */
class BranchCalcomConfigService
{
    /**
     * Get Cal.com configuration for a branch
     */
    public function getBranchConfig(Branch $branch): array
    {
        return [
            'branch_id' => $branch->id,
            'branch_name' => $branch->name,
            'team_id' => config('calcom.team_id'),
            'event_types' => $this->getEventTypes($branch)->toArray(), // Convert Collection to array
            'default_event_type' => $this->getDefaultEventType($branch),
        ];
    }

    /**
     * Get available event types for branch services
     */
    protected function getEventTypes(Branch $branch): Collection
    {
        return Service::where('branch_id', $branch->id)
            ->where('is_active', true)
            ->whereNotNull('calcom_event_type_id')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Service $service) => [
                'id' => $service->calcom_event_type_id,
                'slug' => $service->slug,
                'title' => $service->name,
                'duration' => $service->duration_minutes,
                'price' => $service->price,
                'service_id' => $service->id,
            ]);
    }

    /**
     * Get default event type slug for branch
     */
    protected function getDefaultEventType(Branch $branch): ?string
    {
        $defaultService = Service::where('branch_id', $branch->id)
            ->where('is_active', true)
            ->whereNotNull('calcom_event_type_id')
            ->orderBy('sort_order')
            ->first();

        return $defaultService?->slug;
    }

    /**
     * Get user's default branch config
     */
    public function getUserDefaultConfig(User $user): ?array
    {
        // Case 1: User has specific branch assigned (company_manager)
        if ($user->branch_id && $user->branch) {
            return $this->getBranchConfig($user->branch);
        }

        // Case 2: Get first branch from company
        if ($user->company) {
            $firstBranch = $user->company->branches()->first();
            return $firstBranch ? $this->getBranchConfig($firstBranch) : null;
        }

        return null;
    }

    /**
     * Get all accessible branches for user
     */
    public function getUserBranches(User $user): array
    {
        // If user has a specific branch assigned (company_manager role)
        if ($user->branch_id && $user->branch) {
            return [
                [
                    'id' => $user->branch->id,
                    'name' => $user->branch->name,
                    'is_default' => true,
                ]
            ];
        }

        // If user is company_owner/admin, get all company branches
        if ($user->company) {
            return $user->company->branches()
                ->orderBy('name')
                ->get()
                ->map(fn (Branch $branch) => [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'is_default' => $branch->id === $user->branch_id,
                ])
                ->toArray();
        }

        return [];
    }
}
