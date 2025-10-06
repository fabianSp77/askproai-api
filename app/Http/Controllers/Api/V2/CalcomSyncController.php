<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Services\Sync\DriftDetectionService;
use App\Services\Sync\EventTypeProvisioningService;
use App\Models\Service;
use App\Models\CalcomEventMap;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Api\V2\PushEventTypesRequest;
use App\Traits\ApiResponse;

class CalcomSyncController extends Controller
{
    use ApiResponse;

    private DriftDetectionService $driftService;

    public function __construct(DriftDetectionService $driftService)
    {
        $this->driftService = $driftService;
    }

    /**
     * Push all event types from our model to Cal.com
     */
    public function pushEventTypes(PushEventTypesRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            // Fix N+1 query - eager load all relationships
            $query = CalcomEventMap::with([
                'service',
                'service.company',
                'staff',
                'staff.branch',
                'branch',
                'company'
            ]);

            if ($validated['company_id'] ?? null) {
                $query->where('company_id', $validated['company_id']);
            }

            if ($validated['branch_id'] ?? null) {
                $query->where('branch_id', $validated['branch_id']);
            }

            if ($validated['service_id'] ?? null) {
                $query->where('service_id', $validated['service_id']);
            }

            $mappings = $query->get();

            if (!class_exists(\App\Services\Sync\EventTypeProvisioningService::class)) {
                // Create simple provisioning logic inline
                $pushed = 0;
                $failed = 0;

                foreach ($mappings as $mapping) {
                    try {
                        $calcom = new \App\Services\CalcomV2Client($mapping->company);

                        $eventData = [
                            'name' => $mapping->generateEventName(),
                            'description' => "Service: {$mapping->service->name}",
                            'duration' => $mapping->service->duration_minutes,
                            'hidden' => true
                        ];

                        if ($mapping->event_type_id) {
                            // Update existing
                            $response = $calcom->updateEventType($mapping->event_type_id, $eventData);
                        } else {
                            // Create new
                            $response = $calcom->createEventType($eventData);

                            if ($response->successful()) {
                                $mapping->update([
                                    'event_type_id' => $response->json('data.id'),
                                    'sync_status' => 'synced',
                                    'last_sync_at' => now()
                                ]);
                            }
                        }

                        if ($response->successful()) {
                            $pushed++;
                        } else {
                            $failed++;
                            Log::error('Failed to push event type', [
                                'mapping_id' => $mapping->id,
                                'response' => $response->body()
                            ]);
                        }
                    } catch (\Exception $e) {
                        $failed++;
                        Log::error('Exception pushing event type', [
                            'mapping_id' => $mapping->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                return $this->successResponse([
                    'total' => $mappings->count(),
                    'pushed' => $pushed,
                    'failed' => $failed
                ], "Pushed {$pushed} event types successfully");
            }

            // Use EventTypeProvisioningService if available
            $provisioningService = app(EventTypeProvisioningService::class);
            $result = $provisioningService->pushAllFromModel($mappings);

            return $this->successResponse(
                $result,
                'Event types pushed successfully'
            );

        } catch (\Exception $e) {
            Log::error('Event type push failed', [
                'error' => $e->getMessage()
            ]);

            return $this->serverErrorResponse(
                'Failed to push event types: ' . $e->getMessage()
            );
        }
    }

    /**
     * Detect drift between local and Cal.com
     */
    public function detectDrift(Request $request): JsonResponse
    {
        try {
            $drifts = $this->driftService->detectDrift();

            return response()->json([
                'data' => [
                    'drifts' => $drifts->map(function($drift) {
                        return [
                            'type' => $drift['type'],
                            'mapping_id' => $drift['mapping_id'] ?? null,
                            'message' => $drift['message'],
                            'severity' => $drift['severity'],
                            'differences' => $drift['differences'] ?? null
                        ];
                    }),
                    'summary' => $this->driftService->getDriftSummary()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Drift detection failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to detect drift',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resolve drift for specific mapping
     */
    public function resolveDrift(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mapping_id' => 'required|exists:calcom_event_map,id',
            'resolution' => 'required|in:accept,reset,ignore'
        ]);

        try {
            $success = $this->driftService->resolveDrift(
                $validated['mapping_id'],
                $validated['resolution']
            );

            if ($success) {
                return response()->json([
                    'data' => [
                        'mapping_id' => $validated['mapping_id'],
                        'resolution' => $validated['resolution']
                    ],
                    'message' => 'Drift resolved successfully'
                ]);
            }

            return response()->json([
                'error' => 'Failed to resolve drift'
            ], 500);

        } catch (\Exception $e) {
            Log::error('Drift resolution failed', [
                'mapping_id' => $validated['mapping_id'],
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to resolve drift',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get drift status for UI
     */
    public function driftStatus(): JsonResponse
    {
        try {
            $summary = $this->driftService->getDriftSummary();

            // Fix N+1 query - eager load all needed relationships
            $mappingsWithDrift = CalcomEventMap::with([
                'service',
                'service.company',
                'branch',
                'branch.company',
                'staff'
            ])
                ->whereNotNull('drift_detected_at')
                ->get()
                ->map(function($mapping) {
                    return [
                        'id' => $mapping->id,
                        'service_name' => $mapping->service->name ?? 'Unknown',
                        'branch_name' => $mapping->branch->name ?? 'Unknown',
                        'event_name' => $mapping->event_name_pattern,
                        'drift_type' => $mapping->drift_data ? 'modified' : 'unknown',
                        'detected_at' => $mapping->drift_detected_at,
                        'policy' => $mapping->external_changes
                    ];
                });

            return response()->json([
                'data' => [
                    'summary' => $summary,
                    'mappings_with_drift' => $mappingsWithDrift
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get drift status', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to get drift status',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Auto-resolve drifts based on policy
     */
    public function autoResolve(): JsonResponse
    {
        try {
            $resolved = $this->driftService->autoResolveDrifts();

            return response()->json([
                'data' => [
                    'resolved' => $resolved
                ],
                'message' => "Auto-resolved {$resolved} drifts based on policy"
            ]);

        } catch (\Exception $e) {
            Log::error('Auto-resolve failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to auto-resolve drifts',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}