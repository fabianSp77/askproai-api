<?php

namespace App\Services;

use App\Models\Service;
use App\Models\TeamEventTypeMapping;
use App\Models\CalcomHostMapping;
use App\Models\Staff;
use Illuminate\Support\Collection;

/**
 * CalcomServiceHostsResolver
 *
 * Resolves available Cal.com hosts for a given service and shows their availability
 * This prevents manual staff selection by automatically pulling from Cal.com
 *
 * Flow:
 * 1. Service has calcom_event_type_id
 * 2. Find TeamEventTypeMapping with that event_type_id
 * 3. Extract hosts array from the mapping
 * 4. Map each host to local Staff (via CalcomHostMapping)
 * 5. Show which services each host/staff handles
 */
class CalcomServiceHostsResolver
{
    /**
     * Get all available Cal.com hosts for a service
     *
     * @param Service $service
     * @return Collection of hosts with staff mapping and availability
     */
    public function resolveHostsForService(Service $service): Collection
    {
        // Service must have Cal.com event type
        if (!$service->calcom_event_type_id) {
            return collect();
        }

        // Find the TeamEventTypeMapping for this service
        $mapping = TeamEventTypeMapping::where('calcom_event_type_id', $service->calcom_event_type_id)
            ->where('company_id', $service->company_id)
            ->first();

        if (!$mapping || !$mapping->hosts) {
            return collect();
        }

        // Map each Cal.com host to our internal staff
        return collect($mapping->hosts)->map(function ($calcomHost) use ($service) {
            return $this->enrichHostWithStaffData($calcomHost, $service);
        });
    }

    /**
     * Enrich a Cal.com host with local staff information
     *
     * @param array $calcomHost Cal.com host data from TeamEventTypeMapping.hosts
     * @param Service $service
     * @return array
     */
    private function enrichHostWithStaffData(array $calcomHost, Service $service): array
    {
        // Try to find local staff mapping for this Cal.com host
        $hostMapping = CalcomHostMapping::where('company_id', $service->company_id)
            ->where('calcom_host_id', $calcomHost['userId'] ?? $calcomHost['id'] ?? null)
            ->where('is_active', true)
            ->first();

        $staff = $hostMapping?->staff()->first();

        return [
            'calcom_id' => $calcomHost['userId'] ?? $calcomHost['id'],
            'calcom_name' => $calcomHost['name'] ?? 'Unknown',
            'calcom_email' => $calcomHost['email'] ?? null,
            'calcom_username' => $calcomHost['username'] ?? null,
            'calcom_avatar' => $calcomHost['avatarUrl'] ?? null,
            'calcom_role' => $calcomHost['role'] ?? 'member',

            // Local staff mapping
            'staff_id' => $staff?->id,
            'staff_name' => $staff?->name,
            'is_mapped' => $staff !== null,
            'mapping_confidence' => $hostMapping?->confidence_score ?? 0,

            // Availability info
            'is_available_for_service' => $this->isHostAvailableForService($staff, $service),
            'available_services' => $staff ? $this->getAvailableServices($staff) : [],
        ];
    }

    /**
     * Check if a staff member is available for a specific service
     */
    private function isHostAvailableForService(?Staff $staff, Service $service): bool
    {
        if (!$staff) {
            return false;
        }

        // Check if staff is attached to this service
        return $staff->services()
            ->where('service_id', $service->id)
            ->where('is_active', true)
            ->where('can_book', true)
            ->exists();
    }

    /**
     * Get all services a staff member is available for
     */
    private function getAvailableServices(Staff $staff): Collection
    {
        return $staff->services()
            ->where('is_active', true)
            ->where('can_book', true)
            ->get(['id', 'name', 'duration_minutes'])
            ->map(fn ($service) => [
                'id' => $service->id,
                'name' => $service->name,
                'duration' => $service->duration_minutes,
            ]);
    }

    /**
     * Auto-sync: Create/update CalcomHostMapping for all hosts of a service
     *
     * @param Service $service
     * @return int Number of mappings created/updated
     */
    public function autoSyncHostMappings(Service $service): int
    {
        $hosts = $this->resolveHostsForService($service);
        $count = 0;

        foreach ($hosts as $host) {
            if (!$host['is_mapped']) {
                // Try to find or create staff with matching email/name
                $staff = $this->findOrCreateStaffForHost($host, $service);

                if ($staff) {
                    // Create the mapping
                    CalcomHostMapping::updateOrCreate(
                        [
                            'company_id' => $service->company_id,
                            'calcom_host_id' => $host['calcom_id'],
                        ],
                        [
                            'staff_id' => $staff->id,
                            'calcom_name' => $host['calcom_name'],
                            'calcom_email' => $host['calcom_email'],
                            'calcom_username' => $host['calcom_username'],
                            'calcom_timezone' => $host['calcom_timezone'] ?? null,
                            'mapping_source' => 'auto_service_sync',
                            'confidence_score' => 85,
                            'is_active' => true,
                            'last_synced_at' => now(),
                        ]
                    );

                    // Attach staff to service if not already attached
                    if (!$staff->services()->where('service_id', $service->id)->exists()) {
                        $staff->services()->attach($service->id, [
                            'is_primary' => false,
                            'can_book' => true,
                            'is_active' => true,
                        ]);
                    }

                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Find or create staff for a Cal.com host
     */
    private function findOrCreateStaffForHost(array $host, Service $service): ?Staff
    {
        // Try to find by email first
        if ($host['calcom_email']) {
            $staff = Staff::where('company_id', $service->company_id)
                ->where('email', $host['calcom_email'])
                ->first();

            if ($staff) {
                return $staff;
            }
        }

        // Try to find by name match
        if ($host['calcom_name']) {
            $staff = Staff::where('company_id', $service->company_id)
                ->where('name', $host['calcom_name'])
                ->first();

            if ($staff) {
                return $staff;
            }
        }

        // If not found, don't auto-create - let admin decide
        return null;
    }

    /**
     * Get a summary of Cal.com hosts status for a service
     */
    public function getHostsSummary(Service $service): array
    {
        $hosts = $this->resolveHostsForService($service);

        return [
            'total_hosts' => $hosts->count(),
            'mapped_hosts' => $hosts->where('is_mapped', true)->count(),
            'unmapped_hosts' => $hosts->where('is_mapped', false)->count(),
            'available_for_service' => $hosts->where('is_available_for_service', true)->count(),
            'hosts' => $hosts,
        ];
    }
}
