<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Company;
use App\Models\Service;

class CalcomV2Service
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $apiVersion;
    protected ?Company $company;

    public function __construct(?Company $company = null)
    {
        $this->baseUrl = rtrim(config('services.calcom.base_url', 'https://api.cal.com/v2'), '/');
        $this->apiVersion = config('services.calcom.api_version', '2024-08-13');

        // Use company API key if provided, otherwise fall back to system key
        if ($company && $company->calcom_api_key) {
            $this->apiKey = $company->calcom_api_key;
            $this->company = $company;
        } else {
            $this->apiKey = config('services.calcom.api_key');
            $this->company = null;
        }
    }

    /**
     * Create HTTP client with V2 API headers
     *
     * 🔧 FIX 2025-11-13: Changed from protected to public
     * CalcomAvailabilityService needs access to make direct API calls
     */
    public function httpClient(): PendingRequest
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'cal-api-version' => $this->apiVersion,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]);
    }

    /**
     * Fetch all teams accessible by the API key
     */
    public function fetchTeams(): Response
    {
        // Use httpClient() method for proper V2 authentication
        $fullUrl = $this->baseUrl . '/teams';

        $response = $this->httpClient()->get($fullUrl);

        Log::channel('calcom')->debug('[Cal.com V2] Fetched teams', [
            'status' => $response->status(),
            'teams_count' => count($response->json()['teams'] ?? [])
        ]);

        return $response;
    }

    /**
     * Fetch a specific team by ID
     */
    public function fetchTeam(int $teamId): Response
    {
        // Use httpClient() method for proper V2 authentication
        $fullUrl = $this->baseUrl . '/teams/' . $teamId;

        return $this->httpClient()->get($fullUrl);
    }

    /**
     * Fetch event types for a specific team (V2 with V1 fallback)
     */
    public function fetchTeamEventTypes(int $teamId): Response
    {
        // Try V2 first
        $client = $this->httpClient();
        $fullUrl = $this->baseUrl . '/event-types';
        $params = ['teamId' => $teamId];

        $response = $client->get($fullUrl, $params);

        // If V2 fails, try V1 fallback
        if (!$response->successful()) {
            Log::channel('calcom')->warning('[Cal.com] V2 failed, trying V1 fallback for team event types', [
                'v2_status' => $response->status(),
                'v2_error' => $response->json()
            ]);

            $v1Url = str_replace('/v2', '/v1', $this->baseUrl) . '/event-types';
            $response = Http::get($v1Url . '?apiKey=' . $this->apiKey . '&teamId=' . $teamId);
        }

        Log::channel('calcom')->debug('[Cal.com] Fetched team event types', [
            'team_id' => $teamId,
            'status' => $response->status(),
            'event_types_count' => count($response->json()['data'] ?? $response->json()['event_types'] ?? [])
        ]);

        return $response;
    }

    /**
     * Fetch members of a specific team
     */
    public function fetchTeamMembers(int $teamId): Response
    {
        // Use httpClient() method for proper V2 authentication
        $fullUrl = $this->baseUrl . '/teams/' . $teamId . '/members';

        $response = $this->httpClient()->get($fullUrl);

        Log::channel('calcom')->debug('[Cal.com V2] Fetched team members', [
            'team_id' => $teamId,
            'status' => $response->status(),
            'members_count' => count($response->json()['members'] ?? [])
        ]);

        return $response;
    }

    /**
     * Validate that an event type belongs to a specific team
     * Supports both Team Event Types and Child Managed Event Types
     */
    public function validateTeamAccess(int $teamId, int $eventTypeId): bool
    {
        try {
            // Check cache first
            $cacheKey = "team_{$teamId}_event_{$eventTypeId}";
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }

            // Fetch team event types
            $response = $this->fetchTeamEventTypes($teamId);

            if (!$response->successful()) {
                return false;
            }

            $data = $response->json();
            // Handle both V1 and V2 response structures
            $eventTypes = $data['data'] ?? $data['event_types'] ?? [];

            // Check if event type exists in team
            $exists = false;
            foreach ($eventTypes as $eventType) {
                if ($eventType['id'] == $eventTypeId) {
                    $exists = true;
                    break;
                }
            }

            // Fallback: Check if event type has matching teamId (child managed event types)
            // Note: V2 API doesn't support fetching individual event types, use V1 API
            if (!$exists) {
                try {
                    // Use V1 API to fetch individual event type
                    $v1Url = str_replace('/v2', '/v1', $this->baseUrl) . '/event-types/' . $eventTypeId;
                    $eventTypeResp = \Illuminate\Support\Facades\Http::get($v1Url . '?apiKey=' . $this->apiKey);

                    if ($eventTypeResp->successful()) {
                        $eventTypeData = $eventTypeResp->json();
                        $eventTeamId = $eventTypeData['event_type']['teamId'] ?? null;

                        if ($eventTeamId === $teamId) {
                            Log::channel('calcom')->info('[Cal.com V2] Event type validated via teamId field (child managed)', [
                                'event_type_id' => $eventTypeId,
                                'team_id' => $teamId,
                                'scheduling_type' => $eventTypeData['event_type']['schedulingType'] ?? null,
                                'team_slug' => $eventTypeData['event_type']['team']['slug'] ?? null
                            ]);
                            $exists = true;
                        }
                    }
                } catch (\Exception $e) {
                    Log::channel('calcom')->warning('[Cal.com V2] Failed to validate child managed event type', [
                        'event_type_id' => $eventTypeId,
                        'team_id' => $teamId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Cache result for 1 hour
            Cache::put($cacheKey, $exists, 3600);

            return $exists;

        } catch (\Exception $e) {
            Log::channel('calcom')->error('[Cal.com V2] Failed to validate team access', [
                'team_id' => $teamId,
                'event_type_id' => $eventTypeId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Import all event types for a team as services
     */
    public function importTeamEventTypes(Company $company): array
    {
        if (!$company->calcom_team_id) {
            return [
                'success' => false,
                'message' => 'Company has no Cal.com team ID configured'
            ];
        }

        try {
            // Fetch team event types
            $response = $this->fetchTeamEventTypes($company->calcom_team_id);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Failed to fetch team event types',
                    'error' => $response->json()
                ];
            }

            $data = $response->json();
            // Handle both V1 and V2 response structures
            $eventTypes = $data['data'] ?? $data['event_types'] ?? [];

            $imported = 0;
            $updated = 0;
            $failed = 0;
            $results = [];

            foreach ($eventTypes as $eventType) {
                try {
                    $eventTypeId = $eventType['id'];

                    // Check if service already exists
                    $service = Service::where('calcom_event_type_id', $eventTypeId)->first();

                    $serviceData = [
                        'company_id' => $company->id,
                        'name' => $eventType['title'] ?? 'Unnamed Service',
                        'calcom_name' => $eventType['title'] ?? 'Unnamed Service',
                        'slug' => $eventType['slug'] ?? null,
                        'description' => $eventType['description'] ?? null,
                        'duration_minutes' => $eventType['length'] ?? 30,
                        'price' => $eventType['price'] ?? 0,
                        'is_active' => !($eventType['hidden'] ?? false),
                        'is_online' => $this->hasOnlineLocation($eventType['locations'] ?? []),
                        'schedule_id' => $eventType['scheduleId'] ?? null,
                        'minimum_booking_notice' => $eventType['minimumBookingNotice'] ?? 120,
                        'before_event_buffer' => $eventType['beforeEventBuffer'] ?? 0,
                        'buffer_time_minutes' => $eventType['afterEventBuffer'] ?? 0,
                        'requires_confirmation' => $eventType['requiresConfirmation'] ?? false,
                        'disable_guests' => $eventType['disableGuests'] ?? false,
                        'booking_link' => $eventType['link'] ?? null,
                        'locations_json' => $eventType['locations'] ?? null,
                        'metadata_json' => $eventType['metadata'] ?? null,
                        'booking_fields_json' => $eventType['bookingFields'] ?? null,
                        'last_calcom_sync' => now(),
                        'sync_status' => 'synced',
                        'sync_error' => null,
                    ];

                    if ($service) {
                        // Update existing service
                        $service->update($serviceData);
                        $updated++;
                        $results[] = [
                            'event_type_id' => $eventTypeId,
                            'action' => 'updated',
                            'service_id' => $service->id
                        ];
                    } else {
                        // Create new service
                        $serviceData['calcom_event_type_id'] = $eventTypeId;
                        $service = Service::create($serviceData);
                        $imported++;
                        $results[] = [
                            'event_type_id' => $eventTypeId,
                            'action' => 'created',
                            'service_id' => $service->id
                        ];
                    }

                } catch (\Exception $e) {
                    $failed++;
                    $results[] = [
                        'event_type_id' => $eventType['id'] ?? 'unknown',
                        'action' => 'failed',
                        'error' => $e->getMessage()
                    ];
                    Log::channel('calcom')->error('[Cal.com V2] Failed to import event type', [
                        'event_type' => $eventType,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Update company statistics
            $company->update([
                'team_event_type_count' => count($eventTypes),
                'last_team_sync' => now(),
                'team_sync_status' => 'synced',
                'team_sync_error' => null
            ]);

            return [
                'success' => true,
                'message' => "Team event types imported successfully",
                'summary' => [
                    'total' => count($eventTypes),
                    'imported' => $imported,
                    'updated' => $updated,
                    'failed' => $failed
                ],
                'results' => $results
            ];

        } catch (\Exception $e) {
            // Update company with error
            $company->update([
                'team_sync_status' => 'error',
                'team_sync_error' => $e->getMessage(),
                'last_team_sync' => now()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to import team event types',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Sync team members for a company
     */
    public function syncTeamMembers(Company $company): array
    {
        if (!$company->calcom_team_id) {
            return [
                'success' => false,
                'message' => 'Company has no Cal.com team ID configured'
            ];
        }

        try {
            $response = $this->fetchTeamMembers($company->calcom_team_id);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Failed to fetch team members',
                    'error' => $response->json()
                ];
            }

            $data = $response->json();
            $members = $data['members'] ?? [];

            // Clear existing members for this team
            \DB::table('calcom_team_members')
                ->where('company_id', $company->id)
                ->where('calcom_team_id', $company->calcom_team_id)
                ->delete();

            // Insert new members
            $memberRecords = [];
            foreach ($members as $member) {
                $memberRecords[] = [
                    'company_id' => $company->id,
                    'calcom_team_id' => $company->calcom_team_id,
                    'calcom_user_id' => $member['userId'] ?? $member['id'],
                    'email' => $member['email'],
                    'name' => $member['name'] ?? $member['username'] ?? 'Unknown',
                    'username' => $member['username'] ?? null,
                    'role' => $member['role'] ?? 'member',
                    'accepted' => $member['accepted'] ?? true,
                    'is_active' => true,
                    'last_synced_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }

            if (!empty($memberRecords)) {
                \DB::table('calcom_team_members')->insert($memberRecords);
            }

            // Update company member count
            $company->update([
                'team_member_count' => count($members)
            ]);

            return [
                'success' => true,
                'message' => 'Team members synced successfully',
                'members_count' => count($members)
            ];

        } catch (\Exception $e) {
            Log::channel('calcom')->error('[Cal.com V2] Failed to sync team members', [
                'company_id' => $company->id,
                'team_id' => $company->calcom_team_id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to sync team members',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if event type has online location
     */
    private function hasOnlineLocation(array $locations): bool
    {
        foreach ($locations as $location) {
            $type = $location['type'] ?? '';
            if (str_contains($type, 'integrations:') ||
                str_contains($type, 'video') ||
                str_contains($type, 'meet') ||
                str_contains($type, 'zoom')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get team details with caching
     */
    public function getTeamDetails(int $teamId): ?array
    {
        $cacheKey = "team_details_{$teamId}";

        return Cache::remember($cacheKey, 3600, function() use ($teamId) {
            try {
                $response = $this->fetchTeam($teamId);

                if ($response->successful()) {
                    return $response->json()['team'] ?? null;
                }

                return null;
            } catch (\Exception $e) {
                Log::channel('calcom')->error('[Cal.com V2] Failed to get team details', [
                    'team_id' => $teamId,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });
    }

    /**
     * Clear all team-related caches
     */
    public function clearTeamCache(?int $teamId = null): void
    {
        if ($teamId) {
            Cache::forget("team_details_{$teamId}");
            // Clear all event type validations for this team
            Cache::flush(); // In production, use tags instead
        } else {
            // Clear all team caches
            Cache::flush(); // In production, use tags instead
        }
    }
}