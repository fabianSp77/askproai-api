<?php

namespace App\Services;

use App\Models\Service;
use App\Models\CalcomEventMap;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * CalcomEventTypeManager
 *
 * Manages automated creation and synchronization of Cal.com event types
 * for composite service segments.
 *
 * PURPOSE:
 * - Populates CalcomEventMap table automatically
 * - Creates Cal.com event types for each service segment
 * - Maintains sync between local services and Cal.com
 *
 * USAGE:
 * ```php
 * $manager = new CalcomEventTypeManager($company);
 * $manager->createSegmentEventTypes($service);
 * ```
 */
class CalcomEventTypeManager
{
    private CalcomV2Client $calcom;
    private Company $company;

    public function __construct(Company $company)
    {
        $this->company = $company;
        $this->calcom = new CalcomV2Client($company);
    }

    /**
     * Create Cal.com event types for all segments of a composite service
     *
     * This method:
     * 1. Validates service is composite
     * 2. Creates hidden Cal.com event type for each segment
     * 3. Populates CalcomEventMap with mappings
     * 4. Handles errors gracefully with rollback
     *
     * @param Service $service The composite service
     * @return array Created CalcomEventMap records
     * @throws \Exception if Cal.com API fails
     */
    public function createSegmentEventTypes(Service $service): array
    {
        // Validate composite service
        if (!$service->composite || empty($service->segments)) {
            Log::warning("Service {$service->id} is not composite or has no segments");
            return [];
        }

        // Validate company has Cal.com team ID
        if (!$this->company->calcom_team_id) {
            throw new \Exception("Company must have Cal.com team ID configured");
        }

        Log::info("Creating segment event types for Service {$service->id}", [
            'service_name' => $service->name,
            'segment_count' => count($service->segments)
        ]);

        $createdMappings = [];

        DB::transaction(function() use ($service, &$createdMappings) {
            foreach ($service->segments as $segment) {
                $segmentKey = $segment['key'];
                $segmentName = $segment['name'];
                $duration = $segment['duration'];

                // Check if mapping already exists
                $existingMapping = CalcomEventMap::where('service_id', $service->id)
                    ->where('segment_key', $segmentKey)
                    ->first();

                if ($existingMapping) {
                    Log::info("Mapping already exists for Service {$service->id} Segment {$segmentKey}");
                    $createdMappings[] = $existingMapping;
                    continue;
                }

                // Generate event type name
                $eventTypeName = $this->generateEventTypeName($service, $segment);

                // Create Cal.com event type via API
                try {
                    $response = $this->calcom->createEventType([
                        'name' => $eventTypeName,
                        'description' => "Segment {$segmentName} für {$service->name}",
                        'duration' => $duration
                    ]);

                    $eventTypeId = null;
                    $eventTypeSlug = null;

                    if (!$response->successful()) {
                        $errorBody = $response->json();
                        $errorMessage = $errorBody['error']['message'] ?? 'Unknown error';

                        // Handle "slug already exists" - retrieve existing event type
                        if (str_contains($errorMessage, 'slug already exists')) {
                            Log::info("Event type slug already exists, retrieving existing", [
                                'slug' => Str::slug($eventTypeName)
                            ]);

                            // Get all event types and find the matching one
                            $listResponse = $this->calcom->getEventTypes();
                            if ($listResponse->successful()) {
                                $eventTypes = $listResponse->json('data') ?? $listResponse->json();

                                foreach ($eventTypes as $et) {
                                    if ($et['slug'] === Str::slug($eventTypeName)) {
                                        // IMPORTANT: Check if this event type already belongs to ANOTHER service
                                        $existingMapping = CalcomEventMap::where('event_type_id', $et['id'])->first();

                                        if ($existingMapping && $existingMapping->service_id !== $service->id) {
                                            // Event type belongs to different service - DO NOT reuse!
                                            Log::warning("Event type slug collision detected", [
                                                'event_type_id' => $et['id'],
                                                'slug' => $et['slug'],
                                                'current_service' => $service->id,
                                                'owner_service' => $existingMapping->service_id,
                                                'message' => 'Slug collision - this event type belongs to another service'
                                            ]);

                                            // Throw error to force slug regeneration or manual intervention
                                            throw new \Exception(
                                                "Slug collision: Event type '{$et['slug']}' already belongs to Service {$existingMapping->service_id}. " .
                                                "Cannot reuse for Service {$service->id}. This indicates identical service/segment names."
                                            );
                                        }

                                        // Event type either has no mapping OR belongs to same service - safe to reuse
                                        $eventTypeId = $et['id'];
                                        $eventTypeSlug = $et['slug'];
                                        Log::info("Found existing event type - safe to reuse", [
                                            'event_type_id' => $eventTypeId,
                                            'event_type_slug' => $eventTypeSlug,
                                            'service_id' => $service->id
                                        ]);
                                        break;
                                    }
                                }
                            }

                            if (!$eventTypeId) {
                                throw new \Exception("Event type exists but could not be retrieved");
                            }
                        } else {
                            throw new \Exception("Cal.com API error: " . $response->body());
                        }
                    } else {
                        // Success - parse response
                        $responseData = $response->json();
                        $eventTypeData = $responseData['data'] ?? $responseData;

                        // Handle both object and array responses
                        if (is_array($eventTypeData) && isset($eventTypeData[0])) {
                            // Array response - take first element
                            $eventTypeData = $eventTypeData[0];
                        }

                        if (!isset($eventTypeData['id'])) {
                            throw new \Exception("Cal.com API response missing 'id' field. Response: " . json_encode($responseData));
                        }

                        $eventTypeId = $eventTypeData['id'];
                        $eventTypeSlug = $eventTypeData['slug'] ?? null;
                    }

                    Log::info("Created Cal.com event type", [
                        'event_type_id' => $eventTypeId,
                        'event_type_slug' => $eventTypeSlug,
                        'segment_key' => $segmentKey
                    ]);

                    // Create CalcomEventMap record
                    $mapping = CalcomEventMap::create([
                        'company_id' => $service->company_id,
                        'branch_id' => $service->branch_id,
                        'service_id' => $service->id,
                        'segment_key' => $segmentKey,
                        'staff_id' => null, // Can be set later for staff-specific mappings
                        'event_type_id' => $eventTypeId,
                        'event_type_slug' => $eventTypeSlug,
                        'hidden' => true,
                        'event_name_pattern' => $eventTypeName,
                        'sync_status' => 'synced',
                        'last_sync_at' => now()
                    ]);

                    $createdMappings[] = $mapping;

                } catch (\Exception $e) {
                    Log::error("Failed to create event type for segment {$segmentKey}", [
                        'service_id' => $service->id,
                        'segment_key' => $segmentKey,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);

                    throw $e; // Rollback transaction - don't create incomplete mapping
                }
            }
        });

        Log::info("Successfully created {$service->id} segment event types", [
            'mappings_created' => count($createdMappings)
        ]);

        return $createdMappings;
    }

    /**
     * Generate Cal.com event type name with user-friendly pattern
     *
     * Pattern: SERVICE: SEGMENT (X von Y) - COMPANY
     * Example: Ansatzfärbung komplett: Ansatzfärbung auftragen (1 von 4) - Friseur 1
     *
     * This pattern is designed to be immediately understandable for salon staff:
     * - Service name first (what service is this?)
     * - Segment name (what step?)
     * - Position indicator (which step of how many?)
     * - Company name (which salon?)
     *
     * @param Service $service
     * @param array $segment
     * @return string
     */
    private function generateEventTypeName(Service $service, array $segment): string
    {
        $segments = $service->segments;
        $totalSegments = count($segments);

        // Find position (1-indexed) of current segment
        $position = 0;
        foreach ($segments as $index => $seg) {
            if ($seg['key'] === $segment['key']) {
                $position = $index + 1;
                break;
            }
        }

        // Shorten service name if needed (remove redundant parts like ", waschen, schneiden, föhnen")
        $serviceName = $this->shortenServiceName($service->name);

        // Pattern: SERVICE: SEGMENT (X von Y) - COMPANY
        return sprintf(
            "%s: %s (%d von %d) - %s",
            $serviceName,             // "Ansatzfärbung komplett"
            $segment['name'],         // "Ansatzfärbung auftragen"
            $position,                // 1
            $totalSegments,           // 4
            $this->company->name      // "Friseur 1"
        );
    }

    /**
     * Shorten service name for better readability in calendars
     *
     * Examples:
     * - "Ansatzfärbung, waschen, schneiden, föhnen" → "Ansatzfärbung komplett"
     * - "Ansatz, Längenausgleich, waschen, schneiden, föhnen" → "Ansatz + Längenausgleich"
     * - "Herrenhaarschnitt" → "Herrenhaarschnitt" (unchanged)
     *
     * @param string $name
     * @return string
     */
    private function shortenServiceName(string $name): string
    {
        // Pattern 1: "Name, waschen, schneiden, föhnen" → "Name komplett"
        if (preg_match('/^([^,]+),\s*waschen,\s*schneiden,\s*föhnen/i', $name, $matches)) {
            return trim($matches[1]) . ' komplett';
        }

        // Pattern 2: "Name1, Name2, waschen..." → "Name1 + Name2"
        if (preg_match('/^([^,]+),\s*([^,]+),\s*waschen/i', $name, $matches)) {
            return trim($matches[1]) . ' + ' . trim($matches[2]);
        }

        // No shortening needed
        return $name;
    }

    /**
     * Update existing Cal.com event types when service segments change
     *
     * @param Service $service
     * @return void
     */
    public function updateSegmentEventTypes(Service $service): void
    {
        if (!$service->composite || empty($service->segments)) {
            // If service is no longer composite, delete event type mappings
            $this->deleteSegmentEventTypes($service);
            return;
        }

        $existingMappings = CalcomEventMap::where('service_id', $service->id)->get();
        $currentSegmentKeys = collect($service->segments)->pluck('key')->toArray();

        // Delete mappings for removed segments
        foreach ($existingMappings as $mapping) {
            if (!in_array($mapping->segment_key, $currentSegmentKeys)) {
                Log::info("Deleting event type for removed segment", [
                    'service_id' => $service->id,
                    'segment_key' => $mapping->segment_key,
                    'event_type_id' => $mapping->event_type_id
                ]);

                // Delete from Cal.com
                if ($mapping->event_type_id) {
                    $this->calcom->deleteEventType($mapping->event_type_id);
                }

                // Delete mapping
                $mapping->delete();
            }
        }

        // Create mappings for new segments
        $existingKeys = $existingMappings->pluck('segment_key')->toArray();
        $newSegments = array_filter($service->segments, function($segment) use ($existingKeys) {
            return !in_array($segment['key'], $existingKeys);
        });

        if (!empty($newSegments)) {
            // Temporarily set segments to only new ones for creation
            $originalSegments = $service->segments;
            $service->segments = $newSegments;
            $this->createSegmentEventTypes($service);
            $service->segments = $originalSegments;
        }

        // Update duration for existing segments
        foreach ($service->segments as $segment) {
            $mapping = $existingMappings->firstWhere('segment_key', $segment['key']);
            if ($mapping && $mapping->event_type_id) {
                $this->calcom->updateEventType($mapping->event_type_id, [
                    'lengthInMinutes' => $segment['duration']
                ]);

                $mapping->update([
                    'last_sync_at' => now(),
                    'sync_status' => 'synced'
                ]);
            }
        }
    }

    /**
     * Delete all Cal.com event types for a service
     *
     * @param Service $service
     * @return void
     */
    public function deleteSegmentEventTypes(Service $service): void
    {
        $mappings = CalcomEventMap::where('service_id', $service->id)->get();

        foreach ($mappings as $mapping) {
            if ($mapping->event_type_id) {
                try {
                    $this->calcom->deleteEventType($mapping->event_type_id);
                    Log::info("Deleted Cal.com event type {$mapping->event_type_id}");
                } catch (\Exception $e) {
                    Log::error("Failed to delete event type {$mapping->event_type_id}: " . $e->getMessage());
                }
            }

            $mapping->delete();
        }
    }

    /**
     * Sync existing Cal.com event types with local service configuration
     *
     * Detects drift between local services and Cal.com event types
     *
     * @param Service $service
     * @return array Drift information
     */
    public function detectDrift(Service $service): array
    {
        $mappings = CalcomEventMap::where('service_id', $service->id)->get();
        $driftDetected = [];

        foreach ($mappings as $mapping) {
            if (!$mapping->event_type_id) {
                continue;
            }

            try {
                $response = $this->calcom->getEventType($mapping->event_type_id);

                if (!$response->successful()) {
                    $driftDetected[] = [
                        'mapping_id' => $mapping->id,
                        'segment_key' => $mapping->segment_key,
                        'issue' => 'Event type not found in Cal.com',
                        'severity' => 'critical'
                    ];

                    $mapping->update([
                        'drift_detected_at' => now(),
                        'drift_data' => ['error' => 'Event type deleted in Cal.com']
                    ]);

                    continue;
                }

                $eventTypeData = $response->json('data');
                $segment = collect($service->segments)->firstWhere('key', $mapping->segment_key);

                if (!$segment) {
                    continue;
                }

                // Check duration mismatch
                if ($eventTypeData['lengthInMinutes'] !== $segment['duration']) {
                    $driftDetected[] = [
                        'mapping_id' => $mapping->id,
                        'segment_key' => $mapping->segment_key,
                        'issue' => 'Duration mismatch',
                        'expected' => $segment['duration'],
                        'actual' => $eventTypeData['lengthInMinutes'],
                        'severity' => 'warning'
                    ];

                    $mapping->update([
                        'drift_detected_at' => now(),
                        'drift_data' => [
                            'duration_mismatch' => [
                                'local' => $segment['duration'],
                                'calcom' => $eventTypeData['lengthInMinutes']
                            ]
                        ]
                    ]);
                }

            } catch (\Exception $e) {
                Log::error("Drift detection failed for mapping {$mapping->id}: " . $e->getMessage());
            }
        }

        return $driftDetected;
    }
}
