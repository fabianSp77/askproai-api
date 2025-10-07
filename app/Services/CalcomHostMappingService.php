<?php

namespace App\Services;

use App\Models\CalcomHostMapping;
use App\Models\Staff;
use App\Services\Strategies\HostMatchingStrategy;
use App\Services\Strategies\EmailMatchingStrategy;
use App\Services\Strategies\NameMatchingStrategy;
use App\Services\Strategies\HostMatchContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Cal.com Host Mapping Service
 *
 * Resolves Cal.com host IDs to internal staff records using matching strategies.
 * Maintains persistent mappings with audit trail for all changes.
 */
class CalcomHostMappingService
{
    /**
     * @var HostMatchingStrategy[]
     */
    protected array $strategies;

    public function __construct(
        protected EmailMatchingStrategy $emailStrategy,
        protected NameMatchingStrategy $nameStrategy
    ) {
        // Sort strategies by priority (highest first)
        $this->strategies = collect([
            $this->emailStrategy,
            $this->nameStrategy,
        ])->sortByDesc(fn($s) => $s->getPriority())->all();
    }

    /**
     * Resolve staff_id from Cal.com host data
     *
     * @param array $hostData Cal.com host object from API response
     * @param HostMatchContext $context Tenant and booking context
     * @return string|null staff_id (UUID) or null if no match found
     */
    public function resolveStaffForHost(array $hostData, HostMatchContext $context): ?string
    {
        $hostId = $hostData['id'] ?? null;

        if (!$hostId) {
            Log::warning('CalcomHostMappingService: Missing host ID in Cal.com response', [
                'host_data' => $hostData
            ]);
            return null;
        }

        // 1. Check existing active mapping
        $mapping = CalcomHostMapping::where('calcom_host_id', $hostId)
            ->where('is_active', true)
            ->with('staff')
            ->first();

        if ($mapping && $this->validateMapping($mapping, $context)) {
            Log::info('CalcomHostMappingService: Using existing mapping', [
                'host_id' => $hostId,
                'staff_id' => $mapping->staff_id,
                'source' => $mapping->mapping_source,
                'confidence' => $mapping->confidence_score
            ]);
            return $mapping->staff_id;
        }

        // 2. Attempt auto-discovery via matching strategies
        $autoThreshold = config('booking.staff_matching.auto_threshold', 75);

        foreach ($this->strategies as $strategy) {
            $matchResult = $strategy->match($hostData, $context);

            if ($matchResult && $matchResult->confidence >= $autoThreshold) {
                Log::info('CalcomHostMappingService: Auto-matched via strategy', [
                    'host_id' => $hostId,
                    'strategy' => get_class($strategy),
                    'staff_id' => $matchResult->staff->id,
                    'confidence' => $matchResult->confidence,
                    'reason' => $matchResult->reason,
                    'threshold' => $autoThreshold
                ]);

                $mapping = $this->createMapping(
                    $matchResult->staff,
                    $hostData,
                    $strategy->getSource(),
                    $matchResult->confidence,
                    $matchResult->metadata
                );

                return $mapping->staff_id;
            }
        }

        Log::warning('CalcomHostMappingService: No staff match found', [
            'host_id' => $hostId,
            'host_email' => $hostData['email'] ?? null,
            'host_name' => $hostData['name'] ?? null,
            'company_id' => $context->companyId
        ]);

        return null;
    }

    /**
     * Create new host-to-staff mapping
     *
     * @param Staff $staff Matched staff record
     * @param array $hostData Cal.com host data
     * @param string $source Mapping source (auto_email, auto_name, etc)
     * @param int $confidence Match confidence score (0-100)
     * @param array $metadata Additional metadata from matching
     * @return CalcomHostMapping Created mapping
     */
    protected function createMapping(
        Staff $staff,
        array $hostData,
        string $source,
        int $confidence,
        array $metadata = []
    ): CalcomHostMapping {
        $mapping = CalcomHostMapping::create([
            'staff_id' => $staff->id,
            'company_id' => $staff->company_id,  // Multi-tenant isolation
            'calcom_host_id' => $hostData['id'],
            'calcom_name' => $hostData['name'] ?? '',
            'calcom_email' => $hostData['email'] ?? '',
            'calcom_username' => $hostData['username'] ?? null,
            'calcom_timezone' => $hostData['timeZone'] ?? null,
            'mapping_source' => $source,
            'confidence_score' => $confidence,
            'last_synced_at' => now(),
            'is_active' => true,
            'metadata' => array_merge($metadata, [
                'auto_created_at' => now()->toISOString(),
                'original_host_data' => $hostData
            ])
        ]);

        // Audit trail
        $mapping->audits()->create([
            'action' => 'auto_matched',
            'new_values' => $mapping->toArray(),
            'changed_at' => now(),
            'reason' => "Auto-matched via {$source} with {$confidence}% confidence"
        ]);

        Log::info('CalcomHostMappingService: Created new mapping', [
            'mapping_id' => $mapping->id,
            'staff_id' => $staff->id,
            'calcom_host_id' => $hostData['id'],
            'source' => $source,
            'confidence' => $confidence
        ]);

        return $mapping;
    }

    /**
     * Validate existing mapping is still valid for current context
     *
     * @param CalcomHostMapping $mapping Existing mapping
     * @param HostMatchContext $context Current context
     * @return bool True if mapping is valid, false otherwise
     */
    protected function validateMapping(CalcomHostMapping $mapping, HostMatchContext $context): bool
    {
        // Check staff is still active
        if (!$mapping->staff->is_active) {
            Log::warning('CalcomHostMappingService: Mapping points to inactive staff', [
                'mapping_id' => $mapping->id,
                'staff_id' => $mapping->staff_id
            ]);
            return false;
        }

        // Check staff belongs to correct company (tenant isolation)
        if ($mapping->staff->company_id !== $context->companyId) {
            Log::warning('CalcomHostMappingService: Mapping company mismatch', [
                'mapping_id' => $mapping->id,
                'expected_company' => $context->companyId,
                'actual_company' => $mapping->staff->company_id
            ]);
            return false;
        }

        return true;
    }

    /**
     * Extract primary host data from Cal.com booking response
     *
     * Cal.com webhook structure varies by event type:
     * - Standard events: data.organizer contains host info
     * - Team events: data.hosts[0] contains primary host
     * - Round Robin: data.organizer is the selected host
     *
     * @param array $calcomResponse Full Cal.com booking response
     * @return array|null Host data or null if not found
     */
    public function extractHostFromBooking(array $calcomResponse): ?array
    {
        // Handle both webhook structures: { data: {...} } or direct {...}
        $bookingData = $calcomResponse['data'] ?? $calcomResponse;

        // Strategy 1: Organizer field (most common for team/round-robin events)
        if (isset($bookingData['organizer']) && !empty($bookingData['organizer'])) {
            $organizer = $bookingData['organizer'];

            Log::info('CalcomHostMapping: Extracted host from organizer field', [
                'email' => $organizer['email'] ?? 'unknown',
                'name' => $organizer['name'] ?? 'unknown',
                'booking_id' => $bookingData['id'] ?? null,
            ]);

            return [
                'email' => $organizer['email'] ?? null,
                'name' => $organizer['name'] ?? null,
                'id' => $organizer['id'] ?? null,
                'timeZone' => $organizer['timeZone'] ?? null,
            ];
        }

        // Strategy 2: Hosts array (team events with explicit host assignment)
        if (isset($bookingData['hosts']) && !empty($bookingData['hosts'])) {
            $host = is_array($bookingData['hosts'][0]) ? $bookingData['hosts'][0] : null;

            if ($host) {
                Log::info('CalcomHostMapping: Extracted host from hosts array', [
                    'email' => $host['email'] ?? 'unknown',
                    'host_count' => count($bookingData['hosts']),
                ]);

                return $host;
            }
        }

        // Strategy 3: Responses metadata (fallback for custom integrations)
        if (isset($bookingData['responses']['hosts'])) {
            $host = $bookingData['responses']['hosts'][0] ?? null;

            if ($host) {
                Log::info('CalcomHostMapping: Extracted host from responses metadata', [
                    'email' => $host['email'] ?? 'unknown',
                ]);

                return $host;
            }
        }

        // No host found - log payload structure for debugging
        Log::warning('CalcomHostMapping: No host found in Cal.com response', [
            'booking_id' => $bookingData['id'] ?? 'unknown',
            'event_type_id' => $bookingData['eventTypeId'] ?? null,
            'available_keys' => array_keys($bookingData),
            'has_organizer' => isset($bookingData['organizer']),
            'has_hosts' => isset($bookingData['hosts']),
            'has_responses' => isset($bookingData['responses']),
        ]);

        return null;
    }
}
