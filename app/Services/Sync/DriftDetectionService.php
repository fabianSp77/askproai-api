<?php

namespace App\Services\Sync;

use App\Models\CalcomEventMap;
use App\Models\ActivityLog;
use App\Services\CalcomV2Client;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DriftDetectionService
{
    private CalcomV2Client $calcom;

    public function __construct(CalcomV2Client $calcom)
    {
        $this->calcom = $calcom;
    }

    /**
     * Detect drift between local and Cal.com event types
     */
    public function detectDrift(): Collection
    {
        Log::info('Starting drift detection');

        // Fetch all event types from Cal.com
        $response = $this->calcom->getEventTypes();

        if (!$response->successful()) {
            Log::error('Failed to fetch event types from Cal.com', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return collect();
        }

        $remoteEventTypes = collect($response->json('data') ?? []);
        $localMappings = CalcomEventMap::with(['service', 'staff', 'branch', 'company'])->get();

        $drifts = collect();

        // Check each local mapping against remote
        foreach ($localMappings as $mapping) {
            $remote = $remoteEventTypes->firstWhere('id', $mapping->event_type_id);

            if (!$remote) {
                // Event type deleted in Cal.com
                $drifts->push([
                    'type' => 'deleted',
                    'mapping' => $mapping,
                    'mapping_id' => $mapping->id,
                    'message' => 'Event Type wurde in Cal.com gelöscht',
                    'severity' => 'high',
                    'detected_at' => now()
                ]);

                $this->updateDriftStatus($mapping, 'deleted', null);
                continue;
            }

            // Check for changes
            $differences = $this->compareEventTypes($mapping, $remote);

            if (!empty($differences)) {
                $drifts->push([
                    'type' => 'modified',
                    'mapping' => $mapping,
                    'mapping_id' => $mapping->id,
                    'remote' => $remote,
                    'differences' => $differences,
                    'message' => 'Event Type wurde extern geändert',
                    'severity' => $this->calculateSeverity($differences),
                    'detected_at' => now()
                ]);

                $this->updateDriftStatus($mapping, 'modified', $remote);
            }
        }

        // Check for new event types not in our system
        $localEventTypeIds = $localMappings->pluck('event_type_id');
        $newEventTypes = $remoteEventTypes->reject(function($remote) use ($localEventTypeIds) {
            return $localEventTypeIds->contains($remote['id']);
        });

        foreach ($newEventTypes as $newEventType) {
            $drifts->push([
                'type' => 'new',
                'remote' => $newEventType,
                'message' => 'Neuer Event Type in Cal.com gefunden',
                'severity' => 'low',
                'detected_at' => now()
            ]);
        }

        // Log to audit
        if ($drifts->isNotEmpty()) {
            $this->logDriftToAudit($drifts);
        }

        Log::info('Drift detection completed', [
            'total_drifts' => $drifts->count(),
            'by_type' => $drifts->groupBy('type')->map->count()
        ]);

        return $drifts;
    }

    /**
     * Compare local mapping with remote event type
     */
    private function compareEventTypes(CalcomEventMap $mapping, array $remote): array
    {
        $differences = [];
        $expectedName = $mapping->event_name_pattern;

        // Check title
        if ($remote['title'] !== $expectedName) {
            $differences['title'] = [
                'expected' => $expectedName,
                'actual' => $remote['title']
            ];
        }

        // Check hidden status
        if ($remote['hidden'] !== $mapping->hidden) {
            $differences['hidden'] = [
                'expected' => $mapping->hidden,
                'actual' => $remote['hidden']
            ];
        }

        // Check slug
        if (isset($remote['slug']) && $mapping->event_type_slug &&
            $remote['slug'] !== $mapping->event_type_slug) {
            $differences['slug'] = [
                'expected' => $mapping->event_type_slug,
                'actual' => $remote['slug']
            ];
        }

        // Check duration if service exists
        if ($mapping->service) {
            $expectedDuration = $mapping->service->duration_minutes;
            if (($remote['lengthInMinutes'] ?? null) !== $expectedDuration) {
                $differences['duration'] = [
                    'expected' => $expectedDuration,
                    'actual' => $remote['lengthInMinutes'] ?? null
                ];
            }
        }

        // Check if guests are disabled
        if (($remote['disableGuests'] ?? false) !== true) {
            $differences['disableGuests'] = [
                'expected' => true,
                'actual' => $remote['disableGuests'] ?? false
            ];
        }

        return $differences;
    }

    /**
     * Update drift status for mapping
     */
    private function updateDriftStatus(CalcomEventMap $mapping, string $driftType, ?array $remoteData): void
    {
        $mapping->update([
            'drift_data' => $remoteData,
            'drift_detected_at' => now(),
            'external_changes' => $mapping->external_changes ?? 'warn',
            'sync_status' => 'drift_detected'
        ]);
    }

    /**
     * Resolve drift for a specific mapping
     */
    public function resolveDrift(int $mappingId, string $resolution): bool
    {
        $mapping = CalcomEventMap::findOrFail($mappingId);

        Log::info('Resolving drift', [
            'mapping_id' => $mappingId,
            'resolution' => $resolution
        ]);

        try {
            switch ($resolution) {
                case 'accept':
                    // Accept external changes
                    $this->acceptExternalChanges($mapping);
                    break;

                case 'reset':
                    // Reset to our configuration
                    $this->resetToOurConfiguration($mapping);
                    break;

                case 'ignore':
                    // Just clear the drift flag
                    $this->clearDriftFlag($mapping);
                    break;

                default:
                    Log::warning('Unknown drift resolution', ['resolution' => $resolution]);
                    return false;
            }

            // Log resolution to audit
            ActivityLog::create([
                'action' => 'drift_resolved',
                'model_type' => 'CalcomEventMap',
                'model_id' => $mapping->id,
                'changes' => [
                    'resolution' => $resolution,
                    'resolved_by' => auth()->id() ?? 'system'
                ],
                'user_id' => auth()->id(),
                'ip_address' => request()->ip()
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to resolve drift', [
                'mapping_id' => $mappingId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Accept external changes from Cal.com
     */
    private function acceptExternalChanges(CalcomEventMap $mapping): void
    {
        if (!$mapping->drift_data) {
            return;
        }

        // Update our mapping to match Cal.com
        $mapping->update([
            'event_type_slug' => $mapping->drift_data['slug'] ?? $mapping->event_type_slug,
            'event_name_pattern' => $mapping->drift_data['title'] ?? $mapping->event_name_pattern,
            'hidden' => $mapping->drift_data['hidden'] ?? $mapping->hidden,
            'drift_data' => null,
            'drift_detected_at' => null,
            'sync_status' => 'synced',
            'last_sync_at' => now()
        ]);

        // Update service duration if changed
        if ($mapping->service && isset($mapping->drift_data['lengthInMinutes'])) {
            $mapping->service->update([
                'duration_minutes' => $mapping->drift_data['lengthInMinutes']
            ]);
        }

        Log::info('Accepted external changes', ['mapping_id' => $mapping->id]);
    }

    /**
     * Reset Cal.com to our configuration
     */
    private function resetToOurConfiguration(CalcomEventMap $mapping): void
    {
        if (!$mapping->service) {
            Log::warning('Cannot reset without service', ['mapping_id' => $mapping->id]);
            return;
        }

        // Prepare update data
        $updateData = [
            'title' => $mapping->event_name_pattern,
            'slug' => \Illuminate\Support\Str::slug($mapping->event_name_pattern),
            'hidden' => true,
            'disableGuests' => true,
            'lengthInMinutes' => $mapping->service->duration_minutes
        ];

        // Update in Cal.com
        $response = $this->calcom->updateEventType($mapping->event_type_id, $updateData);

        if ($response->successful()) {
            $mapping->update([
                'drift_data' => null,
                'drift_detected_at' => null,
                'sync_status' => 'synced',
                'last_sync_at' => now()
            ]);

            Log::info('Reset to our configuration', ['mapping_id' => $mapping->id]);
        } else {
            Log::error('Failed to reset configuration in Cal.com', [
                'mapping_id' => $mapping->id,
                'response' => $response->body()
            ]);
            throw new \Exception('Failed to reset configuration in Cal.com');
        }
    }

    /**
     * Clear drift flag without changes
     */
    private function clearDriftFlag(CalcomEventMap $mapping): void
    {
        $mapping->update([
            'drift_data' => null,
            'drift_detected_at' => null,
            'sync_status' => 'synced',
            'last_sync_at' => now()
        ]);

        Log::info('Cleared drift flag', ['mapping_id' => $mapping->id]);
    }

    /**
     * Calculate severity of differences
     */
    private function calculateSeverity(array $differences): string
    {
        // Critical changes
        if (isset($differences['hidden']) || isset($differences['disableGuests'])) {
            return 'high';
        }

        // Important changes
        if (isset($differences['duration']) || isset($differences['title'])) {
            return 'medium';
        }

        // Minor changes
        return 'low';
    }

    /**
     * Log drift to audit system
     */
    private function logDriftToAudit(Collection $drifts): void
    {
        // Group by severity for summary
        $summary = $drifts->groupBy('severity')->map->count();

        ActivityLog::create([
            'action' => 'drift_detected',
            'model_type' => 'CalcomEventMap',
            'model_id' => null,
            'changes' => [
                'total_drifts' => $drifts->count(),
                'by_type' => $drifts->groupBy('type')->map->count()->toArray(),
                'by_severity' => $summary->toArray(),
                'drifts' => $drifts->map(function($drift) {
                    return [
                        'type' => $drift['type'],
                        'mapping_id' => $drift['mapping_id'] ?? null,
                        'message' => $drift['message'],
                        'severity' => $drift['severity']
                    ];
                })->toArray()
            ],
            'user_id' => null,
            'ip_address' => '127.0.0.1'
        ]);
    }

    /**
     * Get drift summary
     */
    public function getDriftSummary(): array
    {
        $mappingsWithDrift = CalcomEventMap::whereNotNull('drift_detected_at')->count();
        $totalMappings = CalcomEventMap::count();

        $recentDrifts = CalcomEventMap::whereNotNull('drift_detected_at')
            ->where('drift_detected_at', '>=', now()->subDays(7))
            ->count();

        return [
            'total_mappings' => $totalMappings,
            'mappings_with_drift' => $mappingsWithDrift,
            'drift_percentage' => $totalMappings > 0 ? round(($mappingsWithDrift / $totalMappings) * 100, 2) : 0,
            'recent_drifts' => $recentDrifts,
            'policies' => [
                'warn' => CalcomEventMap::where('external_changes', 'warn')->count(),
                'accept' => CalcomEventMap::where('external_changes', 'accept')->count(),
                'reject' => CalcomEventMap::where('external_changes', 'reject')->count()
            ]
        ];
    }

    /**
     * Auto-resolve drifts based on policy
     */
    public function autoResolveDrifts(): int
    {
        $resolved = 0;

        $mappings = CalcomEventMap::whereNotNull('drift_detected_at')
            ->whereIn('external_changes', ['accept', 'reject'])
            ->get();

        foreach ($mappings as $mapping) {
            if ($mapping->external_changes === 'accept') {
                $this->acceptExternalChanges($mapping);
                $resolved++;
            } elseif ($mapping->external_changes === 'reject') {
                try {
                    $this->resetToOurConfiguration($mapping);
                    $resolved++;
                } catch (\Exception $e) {
                    Log::error('Failed to auto-reject drift', [
                        'mapping_id' => $mapping->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        Log::info('Auto-resolved drifts', ['count' => $resolved]);
        return $resolved;
    }
}