<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CalcomEventMap;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;

/**
 * CalcomChildEventTypeResolver
 *
 * Resolves Cal.com MANAGED event type parent IDs to bookable child event type IDs.
 *
 * PROBLEM:
 * Cal.com creates MANAGED event types as parent templates that CANNOT be booked directly.
 * Error: "Event type with id=X is the parent managed event type that can't be booked.
 *         You have to provide the child event type id"
 *
 * SOLUTION:
 * This service fetches child event type IDs from Cal.com and caches them for performance.
 * Each staff member assigned to a MANAGED event type gets a unique child event type ID.
 *
 * USAGE:
 * ```php
 * $resolver = new CalcomChildEventTypeResolver($company);
 * $childId = $resolver->resolveChildEventTypeId($parentEventTypeId, $staffId);
 * // Use $childId for booking instead of parent ID
 * ```
 *
 * @see https://cal.com/docs/api-reference/v2/event-types
 * @created 2025-11-22
 */
class CalcomChildEventTypeResolver
{
    private CalcomV2Client $calcom;
    private Company $company;

    /**
     * Cache TTL for child event type resolution (24 hours)
     * Child event types rarely change, so we can cache aggressively
     */
    private const CACHE_TTL_SECONDS = 86400;

    /**
     * Max retries for Cal.com API calls
     */
    private const MAX_RETRIES = 3;

    public function __construct(Company $company)
    {
        $this->company = $company;
        $this->calcom = new CalcomV2Client($company);
    }

    /**
     * Resolve parent event type ID to bookable child event type ID for specific staff
     *
     * @param int $parentEventTypeId Parent/managed event type ID from Cal.com
     * @param string $staffId Staff UUID from our system
     * @return int|null Child event type ID for booking, or null if not found
     * @throws \RuntimeException if Cal.com API fails after retries
     */
    public function resolveChildEventTypeId(int $parentEventTypeId, string $staffId): ?int
    {
        $cacheKey = "calcom_child_event_type:{$this->company->id}:{$parentEventTypeId}:{$staffId}";

        // Try cache first
        $cachedChildId = Cache::get($cacheKey);
        if ($cachedChildId !== null) {
            Log::debug("🔍 Child event type resolved from cache", [
                'parent_id' => $parentEventTypeId,
                'staff_id' => $staffId,
                'child_id' => $cachedChildId,
                'source' => 'cache'
            ]);
            return $cachedChildId;
        }

        // Fetch from Cal.com API
        try {
            $childId = $this->fetchChildEventTypeId($parentEventTypeId, $staffId);

            if ($childId) {
                // Cache the result
                Cache::put($cacheKey, $childId, self::CACHE_TTL_SECONDS);

                Log::info("✅ Child event type resolved from Cal.com API", [
                    'parent_id' => $parentEventTypeId,
                    'staff_id' => $staffId,
                    'child_id' => $childId,
                    'source' => 'api',
                    'cached_for' => self::CACHE_TTL_SECONDS . 's'
                ]);

                return $childId;
            }

            Log::warning("⚠️ No child event type found for staff", [
                'parent_id' => $parentEventTypeId,
                'staff_id' => $staffId,
                'reason' => 'Staff may not be assigned to this event type'
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error("❌ Failed to resolve child event type", [
                'parent_id' => $parentEventTypeId,
                'staff_id' => $staffId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Fetch child event type ID from Cal.com API with retry logic
     *
     * @param int $parentEventTypeId
     * @param string $staffId
     * @return int|null
     * @throws \RuntimeException if all retries fail
     */
    private function fetchChildEventTypeId(int $parentEventTypeId, string $staffId): ?int
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                // Get event type details from Cal.com
                $response = $this->calcom->getEventType($parentEventTypeId);

                if (!$response->successful()) {
                    throw new \RuntimeException(
                        "Cal.com API error: " . $response->status() . " " . $response->body()
                    );
                }

                $eventType = $response->json('data');

                // Check if this is a MANAGED event type (case-insensitive)
                $schedulingType = strtoupper($eventType['schedulingType'] ?? '');
                if ($schedulingType !== 'MANAGED') {
                    // Not a managed event type - can use parent ID directly
                    Log::debug("📝 Event type is not MANAGED, using parent ID", [
                        'event_type_id' => $parentEventTypeId,
                        'scheduling_type' => $eventType['schedulingType'] ?? 'N/A'
                    ]);
                    return $parentEventTypeId;
                }

                // Check if this IS already a child event type (has no parentEventTypeId and no children)
                if (!isset($eventType['parentEventTypeId']) || $eventType['parentEventTypeId'] === null) {
                    // CRITICAL FIX (2025-11-24): Check if this MANAGED type has children
                    // If it has children, it's a PARENT type and we need to find the child
                    // If it has NO children AND has hosts, it's a standalone MANAGED type (can be booked directly)
                    $children = $eventType['children'] ?? [];

                    if (!empty($children)) {
                        // This is a PARENT managed event type with children
                        // Do NOT return parent ID - continue to search for child below
                        Log::debug("📝 MANAGED parent has children, need to find matching child", [
                            'event_type_id' => $parentEventTypeId,
                            'children_count' => count($children)
                        ]);
                        // Fall through to child search logic below
                    } else {
                        // No children - this is a standalone MANAGED type
                        // Check if the staff is assigned as a host
                        $hosts = $eventType['hosts'] ?? [];
                        $staffCalcomUserId = $this->getStaffCalcomUserId($staffId);

                        foreach ($hosts as $host) {
                            if (isset($host['userId']) && $host['userId'] === $staffCalcomUserId) {
                                Log::debug("📝 Standalone MANAGED type (no children), staff is host, using it directly", [
                                    'event_type_id' => $parentEventTypeId,
                                    'staff_calcom_user_id' => $staffCalcomUserId,
                                    'hosts' => array_column($hosts, 'userId')
                                ]);
                                return $parentEventTypeId;
                            }
                        }

                        Log::warning("⚠️ MANAGED event type has no parent and no children, but staff is not a host", [
                            'event_type_id' => $parentEventTypeId,
                            'staff_id' => $staffId,
                            'staff_calcom_user_id' => $staffCalcomUserId,
                            'hosts' => array_column($hosts, 'userId')
                        ]);
                        return null;
                    }
                }

                // Get staff's Cal.com user ID
                $staffCalcomUserId = $this->getStaffCalcomUserId($staffId);
                if (!$staffCalcomUserId) {
                    Log::warning("⚠️ Staff has no Cal.com user ID mapping", [
                        'staff_id' => $staffId,
                        'parent_event_type_id' => $parentEventTypeId
                    ]);
                    return null;
                }

                // Find child event type for this staff member
                $children = $eventType['children'] ?? [];
                foreach ($children as $child) {
                    // Match by Cal.com user ID
                    if (isset($child['userId']) && $child['userId'] === $staffCalcomUserId) {
                        return $child['id'];
                    }

                    // Fallback: Match by username if available
                    if (isset($child['username']) && $this->matchesStaffUsername($child['username'], $staffId)) {
                        return $child['id'];
                    }
                }

                // No matching child found
                Log::warning("⚠️ No child event type found for staff in children array", [
                    'parent_id' => $parentEventTypeId,
                    'staff_id' => $staffId,
                    'staff_calcom_user_id' => $staffCalcomUserId,
                    'children_count' => count($children),
                    'children_user_ids' => array_column($children, 'userId')
                ]);

                return null;

            } catch (\Exception $e) {
                $lastException = $e;

                if ($attempt < self::MAX_RETRIES) {
                    $delay = $attempt * 1000; // Exponential backoff: 1s, 2s, 3s
                    Log::warning("⏳ Retrying child event type resolution", [
                        'attempt' => $attempt,
                        'max_retries' => self::MAX_RETRIES,
                        'delay_ms' => $delay,
                        'error' => $e->getMessage()
                    ]);
                    usleep($delay * 1000);
                    continue;
                }
            }
        }

        // All retries failed
        throw new \RuntimeException(
            "Failed to resolve child event type after " . self::MAX_RETRIES . " attempts: " .
            ($lastException ? $lastException->getMessage() : 'Unknown error')
        );
    }

    /**
     * Get staff member's Cal.com user ID
     *
     * @param string $staffId Staff UUID from our system
     * @return int|null Cal.com user ID
     */
    private function getStaffCalcomUserId(string $staffId): ?int
    {
        // Get staff's Cal.com user ID from staff table or mapping table
        $staff = \App\Models\Staff::find($staffId);
        if (!$staff) {
            return null;
        }

        // Check if staff has calcom_user_id field
        if (isset($staff->calcom_user_id)) {
            return $staff->calcom_user_id;
        }

        // Fallback: Try to find in CalcomHostMapping
        $hostMapping = \App\Models\CalcomHostMapping::where('staff_id', $staffId)->first();
        if ($hostMapping && isset($hostMapping->calcom_host_id)) {
            return $hostMapping->calcom_host_id;
        }

        return null;
    }

    /**
     * Check if Cal.com username matches our staff member
     *
     * @param string $calcomUsername Username from Cal.com child event type
     * @param string $staffId Our staff UUID
     * @return bool
     */
    private function matchesStaffUsername(string $calcomUsername, string $staffId): bool
    {
        $staff = \App\Models\Staff::find($staffId);
        if (!$staff) {
            return false;
        }

        // Compare username (normalized to lowercase, spaces removed)
        $normalizedCalcomUsername = strtolower(str_replace([' ', '-', '_'], '', $calcomUsername));
        $normalizedStaffName = strtolower(str_replace([' ', '-', '_'], '', $staff->name));

        return $normalizedCalcomUsername === $normalizedStaffName;
    }

    /**
     * Invalidate cached child event type ID
     *
     * Use this when event type configuration changes in Cal.com
     *
     * @param int $parentEventTypeId
     * @param string $staffId
     * @return void
     */
    public function invalidateCache(int $parentEventTypeId, string $staffId): void
    {
        $cacheKey = "calcom_child_event_type:{$this->company->id}:{$parentEventTypeId}:{$staffId}";
        Cache::forget($cacheKey);

        Log::info("🗑️ Invalidated child event type cache", [
            'parent_id' => $parentEventTypeId,
            'staff_id' => $staffId
        ]);
    }

    /**
     * Bulk resolve and update CalcomEventMap records with child event type IDs
     *
     * @param int $parentEventTypeId
     * @return array ['success' => int, 'failed' => int, 'skipped' => int]
     */
    public function bulkResolveAndUpdate(int $parentEventTypeId): array
    {
        $stats = ['success' => 0, 'failed' => 0, 'skipped' => 0];

        $mappings = CalcomEventMap::where('event_type_id', $parentEventTypeId)
            ->whereNotNull('staff_id')
            ->get();

        foreach ($mappings as $mapping) {
            try {
                // Skip if already has child ID
                if ($mapping->child_event_type_id) {
                    $stats['skipped']++;
                    continue;
                }

                $childId = $this->resolveChildEventTypeId($parentEventTypeId, $mapping->staff_id);

                if ($childId) {
                    $mapping->update([
                        'child_event_type_id' => $childId,
                        'child_resolved_at' => now()
                    ]);
                    $stats['success']++;
                } else {
                    $stats['failed']++;
                }

            } catch (\Exception $e) {
                Log::error("Failed to resolve child ID for mapping", [
                    'mapping_id' => $mapping->id,
                    'parent_id' => $parentEventTypeId,
                    'staff_id' => $mapping->staff_id,
                    'error' => $e->getMessage()
                ]);
                $stats['failed']++;
            }
        }

        return $stats;
    }
}
