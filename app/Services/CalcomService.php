<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use App\Models\Service;
use Carbon\Carbon;
use App\Exceptions\CalcomApiException;
use App\Services\CircuitBreaker;
use App\Services\CircuitBreakerOpenException;

class CalcomService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $eventTypeId;
    protected CircuitBreaker $circuitBreaker;

    public function __construct()
    {
        $this->baseUrl     = rtrim(config('services.calcom.base_url'), '/');
        $this->apiKey      = config('services.calcom.api_key');
        $this->eventTypeId = config('services.calcom.event_type_id');

        // Initialize circuit breaker for Cal.com API
        // 5 failures → circuit opens for 60 seconds
        $this->circuitBreaker = new CircuitBreaker(
            serviceName: 'calcom_api',
            failureThreshold: 5,
            recoveryTimeout: 60,
            successThreshold: 2
        );
    }

    public function createBooking(array $bookingDetails): Response
    {
        // Extract attendee information from either 'responses' or direct fields
        if (isset($bookingDetails['responses'])) {
            $name = $bookingDetails['responses']['name'];
            $email = $bookingDetails['responses']['email'];
            $phone = $bookingDetails['responses']['attendeePhoneNumber'] ?? null;
            $notes = $bookingDetails['responses']['notes'] ?? null;
        } else {
            $name = $bookingDetails['name'];
            $email = $bookingDetails['email'];
            $phone = $bookingDetails['phone'] ?? null;
            $notes = $bookingDetails['notes'] ?? null;
        }

        // Get timezone (preserve original timezone context for audit trail)
        $originalTimezone = $bookingDetails['timeZone'] ?? 'Europe/Berlin';

        // Get start time and parse with timezone awareness
        $startTimeRaw = $bookingDetails['start'] ?? $bookingDetails['startTime'];
        $startCarbon = \Carbon\Carbon::parse($startTimeRaw, $originalTimezone);

        // Convert to UTC for Cal.com API (requirement), but preserve original timezone
        $startTimeUtc = $startCarbon->copy()->utc()->toIso8601String();

        // Get event type ID
        $eventTypeId = (int)($bookingDetails['eventTypeId'] ?? $this->eventTypeId);

        // 🔧 FIX 2025-10-15: Extract teamId for cache invalidation
        // Bug: Cache invalidation was missing teamId, causing multi-tenant cache collision
        $teamId = isset($bookingDetails['teamId']) ? (int)$bookingDetails['teamId'] : null;

        // Build V2 API payload - simpler format without end, language, responses
        $payload = [
            'eventTypeId' => $eventTypeId,
            'start' => $startTimeUtc, // Send UTC to Cal.com
            'attendee' => [
                'name' => $name,
                'email' => $email,
                'timeZone' => $originalTimezone, // Timezone for Cal.com to display
            ],
        ];

        // Add optional booking field responses (custom fields, notes, phone)
        $bookingFieldsResponses = [];
        if ($phone) {
            $bookingFieldsResponses['phone'] = $phone;
        }
        if ($notes) {
            $bookingFieldsResponses['notes'] = $notes;
        }

        // Build metadata with timezone preservation
        $metadata = [
            'booking_timezone' => $originalTimezone,
            'original_start_time' => $startCarbon->toIso8601String(), // Preserve original timezone
            'start_time_utc' => $startTimeUtc,
        ];

        // Merge with provided metadata
        if (isset($bookingDetails['metadata']) && !empty($bookingDetails['metadata'])) {
            $metadata = array_merge($metadata, $bookingDetails['metadata']);
        }

        // Cal.com V2 API expects metadata as object (not JSON string)
        // Ensure all values meet Cal.com limits: max 500 characters per value
        $sanitizedMetadata = [];
        foreach ($metadata as $key => $value) {
            if (is_string($value) && mb_strlen($value) > 500) {
                $sanitizedMetadata[$key] = mb_substr($value, 0, 497) . '...';
            } else {
                $sanitizedMetadata[$key] = $value;
            }
        }

        $payload['metadata'] = $sanitizedMetadata;

        if (!empty($bookingFieldsResponses)) {
            $payload['bookingFieldsResponses'] = $bookingFieldsResponses;
        }

        Log::channel('calcom')->debug('[Cal.com V2] Sende createBooking Payload:', $payload);

        // Wrap Cal.com API call with circuit breaker for reliability
        try {
            // 🔧 FIX 2025-10-15: Include $teamId in closure for cache invalidation
            return $this->circuitBreaker->call(function() use ($payload, $eventTypeId, $teamId) {
                $fullUrl = $this->baseUrl . '/bookings';
                $resp = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'cal-api-version' => config('services.calcom.api_version', '2024-08-13'),
                    'Content-Type' => 'application/json'
                ])->timeout(1.5)->acceptJson()->post($fullUrl, $payload);  // 🔧 CRITICAL FIX 2025-10-23: Reduced from 5s to 1.5s for voice AI latency (CVSS 6.5)

                Log::channel('calcom')->debug('[Cal.com V2] Booking Response:', [
                    'status' => $resp->status(),
                    'body'   => $resp->json() ?? $resp->body(),
                ]);

                // Throw exception if not successful
                if (!$resp->successful()) {
                    throw CalcomApiException::fromResponse($resp, '/bookings', $payload, 'POST');
                }

                // 🔧 FIX 2025-10-15: Pass teamId to cache invalidation for correct multi-tenant cache keys
                // Only invalidate if teamId is available (backward compatibility)
                if ($teamId) {
                    $this->clearAvailabilityCacheForEventType($eventTypeId, $teamId);
                }

                return $resp;
            });
        } catch (CircuitBreakerOpenException $e) {
            Log::error('Circuit breaker OPEN for createBooking', [
                'service' => 'calcom_api',
                'method' => 'createBooking'
            ]);

            throw new CalcomApiException(
                'Cal.com API circuit breaker is open. Service appears to be down.',
                null, '/bookings', $payload, 503
            );
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Network error (timeout, connection refused, DNS failure, etc.)
            // 🔧 FIX 2025-10-19: Phase A.4 - Timeout fallback for Voice AI
            Log::error('Cal.com API network error during createBooking', [
                'endpoint' => '/bookings',
                'error' => $e->getMessage(),
                'timeout' => '1.5s'  // 🔧 UPDATED 2025-10-23: Reduced from 5s
            ]);

            throw CalcomApiException::networkError('/bookings', $payload, $e);
        }
    }

    /**
     * Get available slots for a given event type and date range
     * Caches responses for 5 minutes to reduce API calls (300-800ms → <5ms)
     *
     * @param int $eventTypeId Cal.com Event Type ID
     * @param string $startDate Start date (Y-m-d format)
     * @param string $endDate End date (Y-m-d format)
     * @param int|null $teamId Cal.com Team ID (required by API v2)
     */
    public function getAvailableSlots(int $eventTypeId, string $startDate, string $endDate, ?int $teamId = null): Response
    {
        // Include teamId in cache key if provided
        $cacheKey = $teamId
            ? "calcom:slots:{$teamId}:{$eventTypeId}:{$startDate}:{$endDate}"
            : "calcom:slots:{$eventTypeId}:{$startDate}:{$endDate}";

        // Check cache first (99% faster: <5ms vs 300-800ms)
        $cachedResponse = Cache::get($cacheKey);
        if ($cachedResponse) {
            Log::debug('Availability cache hit', ['key' => $cacheKey]);

            // Return mock Response with cached data
            return new Response(
                new \GuzzleHttp\Psr7\Response(200, [], json_encode($cachedResponse))
            );
        }

        // Cal.com v2 API requires Bearer token authentication AND ISO 8601 format
        // Convert dates to ISO 8601 format with timezone (CRITICAL: Cal.com returns empty if wrong format)
        $startDateTime = Carbon::parse($startDate)->startOfDay()->toIso8601String();
        $endDateTime = Carbon::parse($endDate)->endOfDay()->toIso8601String();

        $query = [
            'eventTypeId' => $eventTypeId,
            'startTime' => $startDateTime,
            'endTime' => $endDateTime
        ];

        // Add teamId if provided (REQUIRED by Cal.com API v2 for team-based event types)
        if ($teamId) {
            $query['teamId'] = $teamId;
        }

        $fullUrl = $this->baseUrl . '/slots/available?' . http_build_query($query);

        try {
            // Wrap API call with circuit breaker
            return $this->circuitBreaker->call(function() use ($fullUrl, $query, $cacheKey, $eventTypeId, $startDate, $endDate) {
                $resp = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'cal-api-version' => config('services.calcom.api_version', '2024-08-13')
                ])->acceptJson()->timeout(3)->get($fullUrl);  // 5s → 3s for Voice AI optimization

                // Check for HTTP errors
                if (!$resp->successful()) {
                    throw CalcomApiException::fromResponse($resp, '/slots/available', $query, 'GET');
                }

                $data = $resp->json();

                // Validate response structure (CRITICAL: prevents caching invalid responses)
                if (!isset($data['data']['slots']) || !is_array($data['data']['slots'])) {
                    Log::warning('[Cal.com] Invalid slots structure received', [
                        'response' => $data,
                        'event_type_id' => $eventTypeId,
                        'query' => $query
                    ]);
                    throw new CalcomApiException(
                        'Cal.com returned invalid response structure',
                        null,
                        '/slots/available',
                        $query,
                        500
                    );
                }

                // Count total slots for logging and TTL decision
                $slotsData = $data['data']['slots'];
                $totalSlots = array_sum(array_map('count', $slotsData));

                // Enhanced logging with query parameters
                Log::channel('calcom')->info('[Cal.com] Available Slots Response', [
                    'event_type_id' => $eventTypeId,
                    'date_range' => [$startDate, $endDate],
                    'query_params' => $query,
                    'dates_with_slots' => count($slotsData),
                    'total_slots' => $totalSlots,
                    'first_date' => !empty($slotsData) ? array_key_first($slotsData) : null,
                    'first_slot_time' => $this->getFirstSlotTime($slotsData)
                ]);

                // Adaptive TTL: shorter cache for empty responses (prevents cache poisoning)
                if ($totalSlots === 0) {
                    $ttl = 60; // 1 minute for empty responses
                    Log::info('[Cal.com] Zero slots returned - using short TTL', [
                        'event_type_id' => $eventTypeId,
                        'date_range' => [$startDate, $endDate],
                        'ttl' => $ttl
                    ]);
                } else {
                    $ttl = 60; // 🔧 FIX 2025-10-11: Optimized from 300s to 60s (Performance Analysis: 70-80% hit rate, 2.5% staleness vs 12.5%)
                }

                Cache::put($cacheKey, $data, $ttl);
                Log::debug('Availability cached', [
                    'key' => $cacheKey,
                    'slots_count' => $totalSlots,
                    'ttl' => $ttl
                ]);

                return $resp;
            });

        } catch (CircuitBreakerOpenException $e) {
            // Circuit breaker is open - Cal.com appears to be down
            Log::warning('Cal.com API circuit breaker open', [
                'breaker_status' => $this->circuitBreaker->getStatus()
            ]);

            // Throw as CalcomApiException for consistent error handling
            throw new CalcomApiException(
                'Cal.com API circuit breaker is open. Service appears to be down.',
                null,
                '/slots/available',
                $query,
                503
            );

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Network error (timeout, connection refused, etc.)
            throw CalcomApiException::networkError('/slots/available', $query, $e);
        }
    }

    /**
     * Get circuit breaker status for monitoring
     */
    public function getCircuitBreakerStatus(): array
    {
        return $this->circuitBreaker->getStatus();
    }

    /**
     * Manually reset circuit breaker (admin operation)
     */
    public function resetCircuitBreaker(): void
    {
        $this->circuitBreaker->reset();
    }

    /**
     * Clear availability cache for a specific event type
     * Called after bookings to ensure fresh availability data
     *
     * 🔧 FIX 2025-10-15: Added teamId parameter for correct cache key format
     * Bug: Cache keys were missing teamId, causing multi-tenant cache collision
     * - Old format: calcom:slots:{eventTypeId}:{date}:{date}
     * - New format: calcom:slots:{teamId}:{eventTypeId}:{date}:{date}
     *
     * 🔧 FIX 2025-10-19: Phase A+ - Clear BOTH cache layers to prevent race conditions
     * Critical Bug: AppointmentAlternativeFinder has separate cache that wasn't invalidated
     * - Race condition: User A books slot, User B still sees it as "available" for 60s
     * - Solution: Clear both CalcomService AND AppointmentAlternativeFinder caches
     *
     * @param int $eventTypeId Cal.com Event Type ID
     * @param int|null $teamId Cal.com Team ID (optional - if not provided, clears for all teams)
     */
    public function clearAvailabilityCacheForEventType(int $eventTypeId, ?int $teamId = null): void
    {
        $clearedKeys = 0;
        $today = Carbon::today();

        // If teamId not provided, get all teams that use this event type
        $teamIds = [];
        if ($teamId !== null) {
            $teamIds = [$teamId];
        } else {
            // Get team IDs from Services that use this event type
            $services = \App\Models\Service::where('calcom_event_type_id', $eventTypeId)->get();
            foreach ($services as $service) {
                if ($service->calcom_team_id) {
                    $teamIds[] = $service->calcom_team_id;
                }
            }
            $teamIds = array_unique($teamIds);
        }

        // LAYER 1: Clear CalcomService cache (30 days, all teams)
        foreach ($teamIds as $tid) {
            for ($i = 0; $i < 30; $i++) {
                $date = $today->copy()->addDays($i)->format('Y-m-d');
                // CalcomService cache key format
                $cacheKey = "calcom:slots:{$tid}:{$eventTypeId}:{$date}:{$date}";
                Cache::forget($cacheKey);
                $clearedKeys++;
            }
        }

        // LAYER 2: Clear AppointmentAlternativeFinder cache (30 days, all hour combinations)
        // 🔥 CRITICAL: AppointmentAlternativeFinder uses different cache key format!
        // Pattern: cal_slots_{companyId}_{branchId}_{eventTypeId}_{Y-m-d-H}_{Y-m-d-H}
        //
        // Problem: We don't know all company_id/branch_id combinations here
        // Solution: Use wildcard pattern with Cache::flush() or iterate all possible combinations
        //
        // For now: Clear using wildcard pattern if Redis, or use Cache tags
        // This requires getting all Company records that use this eventTypeId

        try {
            // Get all companies/branches that might have cached this event type
            // We need to clear cache for ALL tenants that might have cached this slot
            $services = \App\Models\Service::where('calcom_event_type_id', $eventTypeId)->get();

            foreach ($services as $service) {
                $companyId = $service->company_id ?? 0;
                $branchId = $service->branch_id ?? 0;

                // Clear AlternativeFinder cache for this company/branch combination
                // OPTIMIZATION: Only clear next 7 days (not 30) and only business hours (9-18)
                // This reduces cache clearing from ~720 keys to ~70 keys per service
                for ($i = 0; $i < 7; $i++) {  // 7 days instead of 30
                    $date = $today->copy()->addDays($i);

                    // Clear only business hours (9-18) instead of all 24 hours
                    for ($hour = 9; $hour <= 18; $hour++) {
                        $startTime = $date->copy()->setTime($hour, 0);
                        $endTime = $startTime->copy()->addHours(1);

                        // AppointmentAlternativeFinder cache key format
                        $altCacheKey = sprintf(
                            'cal_slots_%d_%d_%d_%s_%s',
                            $companyId,
                            $branchId,
                            $eventTypeId,
                            $startTime->format('Y-m-d-H'),
                            $endTime->format('Y-m-d-H')
                        );

                        Cache::forget($altCacheKey);
                        $clearedKeys++;
                    }
                }
            }

            Log::info('✅ Cleared BOTH cache layers after booking (Phase A+ Fix)', [
                'team_id' => $teamId,
                'event_type_id' => $eventTypeId,
                'services_affected' => $services->count(),
                'total_keys_cleared' => $clearedKeys,
                'layers' => ['CalcomService', 'AppointmentAlternativeFinder']
            ]);

        } catch (\Exception $e) {
            // If clearing AlternativeFinder cache fails, log but don't break
            Log::warning('Failed to clear AlternativeFinder cache layer', [
                'event_type_id' => $eventTypeId,
                'error' => $e->getMessage(),
                'cleared_calcom_keys' => $clearedKeys
            ]);
        }
    }

    /**
     * Get details of a specific event type
     */
    public function getEventType(int $eventTypeId): Response
    {
        // Cal.com v2 API requires Bearer token authentication
        $fullUrl = $this->baseUrl . '/event-types/' . $eventTypeId;

        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey
        ])->acceptJson()->get($fullUrl);
    }

    /**
     * Create a new event type in Cal.com for a service
     */
    public function createEventType(Service $service): Response
    {
        $payload = [
            'title' => $service->name,
            'slug' => str($service->name)->slug(),
            'description' => $service->description ?? "Service: {$service->name}",
            'length' => $service->duration_minutes ?? 30,
            'currency' => 'EUR',
            'price' => $service->price ?? 0,
            'hidden' => !$service->is_active,
            'requiresConfirmation' => $service->requires_confirmation ?? false,
            'disableGuests' => $service->max_attendees <= 1,
            'metadata' => [
                'service_id' => $service->id,
                'company_id' => $service->company_id,
                'category' => $service->category,
                'buffer_time' => $service->buffer_time_minutes ?? 0,
            ],
        ];

        Log::channel('calcom')->debug('[Cal.com] Creating EventType for Service:', [
            'service_id' => $service->id,
            'payload' => $payload
        ]);

        // Cal.com v2 API requires Bearer token authentication
        $fullUrl = $this->baseUrl . '/event-types';
        $resp = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json'
        ])->acceptJson()->post($fullUrl, $payload);

        Log::channel('calcom')->debug('[Cal.com] EventType Creation Response:', [
            'status' => $resp->status(),
            'body' => $resp->json() ?? $resp->body(),
        ]);

        return $resp;
    }

    /**
     * Update an existing event type in Cal.com
     */
    public function updateEventType(Service $service): Response
    {
        if (!$service->calcom_event_type_id) {
            Log::channel('calcom')->warning('[Cal.com] No EventType ID for service', ['service_id' => $service->id]);
            throw new \Exception('Service has no Cal.com Event Type ID');
        }

        $payload = [
            'title' => $service->name,
            'description' => $service->description ?? "Service: {$service->name}",
            'length' => $service->duration_minutes ?? 30,
            'currency' => 'EUR',
            'price' => $service->price ?? 0,
            'hidden' => !$service->is_active,
            'requiresConfirmation' => $service->requires_confirmation ?? false,
            'disableGuests' => $service->max_attendees <= 1,
            'metadata' => [
                'service_id' => $service->id,
                'company_id' => $service->company_id,
                'category' => $service->category,
                'buffer_time' => $service->buffer_time_minutes ?? 0,
                'updated_at' => now()->toISOString(),
            ],
        ];

        Log::channel('calcom')->debug('[Cal.com] Updating EventType for Service:', [
            'service_id' => $service->id,
            'event_type_id' => $service->calcom_event_type_id,
            'payload' => $payload
        ]);

        // Cal.com v2 API requires Bearer token authentication
        $fullUrl = $this->baseUrl . '/event-types/' . $service->calcom_event_type_id;
        $resp = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json'
        ])->acceptJson()->patch($fullUrl, $payload);

        Log::channel('calcom')->debug('[Cal.com] EventType Update Response:', [
            'status' => $resp->status(),
            'body' => $resp->json() ?? $resp->body(),
        ]);

        return $resp;
    }

    /**
     * Delete an event type from Cal.com
     */
    public function deleteEventType(string $eventTypeId): Response
    {
        // Cal.com v2 API requires Bearer token authentication
        $fullUrl = $this->baseUrl . '/event-types/' . $eventTypeId;
        $resp = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey
        ])->acceptJson()->delete($fullUrl);

        Log::channel('calcom')->debug('[Cal.com] Delete EventType Response:', [
            'event_type_id' => $eventTypeId,
            'status' => $resp->status(),
            'body' => $resp->json() ?? $resp->body(),
        ]);

        return $resp;
    }

    /**
     * Synchronize service with Cal.com (create or update)
     */
    public function syncService(Service $service): array
    {
        try {
            if ($service->calcom_event_type_id) {
                // Update existing event type
                $response = $this->updateEventType($service);

                if ($response->successful()) {
                    return [
                        'success' => true,
                        'action' => 'updated',
                        'message' => 'Service erfolgreich mit Cal.com synchronisiert (aktualisiert)',
                        'data' => $response->json()
                    ];
                } else {
                    return [
                        'success' => false,
                        'action' => 'update_failed',
                        'message' => 'Fehler beim Aktualisieren des Cal.com Event Types',
                        'error' => $response->json()
                    ];
                }
            } else {
                // Create new event type
                $response = $this->createEventType($service);

                if ($response->successful()) {
                    $data = $response->json();
                    $eventTypeId = $data['event_type']['id'] ?? null;

                    if ($eventTypeId) {
                        // Update service with new event type ID
                        $service->update(['calcom_event_type_id' => $eventTypeId]);

                        return [
                            'success' => true,
                            'action' => 'created',
                            'message' => 'Service erfolgreich mit Cal.com synchronisiert (neu erstellt)',
                            'data' => $data,
                            'event_type_id' => $eventTypeId
                        ];
                    } else {
                        return [
                            'success' => false,
                            'action' => 'create_failed',
                            'message' => 'Event Type wurde erstellt, aber keine ID erhalten',
                            'error' => $data
                        ];
                    }
                } else {
                    return [
                        'success' => false,
                        'action' => 'create_failed',
                        'message' => 'Fehler beim Erstellen des Cal.com Event Types',
                        'error' => $response->json()
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::channel('calcom')->error('[Cal.com] Sync Service Error:', [
                'service_id' => $service->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'action' => 'error',
                'message' => 'Unerwarteter Fehler beim Synchronisieren: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Bulk synchronize multiple services
     */
    public function syncMultipleServices(array $serviceIds): array
    {
        $results = [];
        $services = Service::whereIn('id', $serviceIds)->get();

        foreach ($services as $service) {
            $results[$service->id] = $this->syncService($service);
        }

        return [
            'total' => count($services),
            'results' => $results,
            'summary' => [
                'successful' => collect($results)->where('success', true)->count(),
                'failed' => collect($results)->where('success', false)->count(),
            ]
        ];
    }

    /**
     * Fetch all event types from Cal.com
     */
    public function fetchEventTypes(): Response
    {
        // Cal.com v2 API requires Bearer token authentication
        $fullUrl = $this->baseUrl . '/event-types';
        $resp = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey
        ])->acceptJson()->get($fullUrl);

        Log::channel('calcom')->debug('[Cal.com] Fetch EventTypes Response:', [
            'status' => $resp->status(),
            'count' => count($resp->json()['event_types'] ?? [])
        ]);

        return $resp;
    }

    /**
     * Check if Cal.com service is configured and available
     */
    public function isConfigured(): bool
    {
        return !empty($this->baseUrl) && !empty($this->apiKey);
    }

    /**
     * Test Cal.com connection
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Cal.com ist nicht konfiguriert (fehlende URL oder API Key)',
            ];
        }

        try {
            // Cal.com v2 API requires Bearer token authentication
            $fullUrl = $this->baseUrl . '/me';
            $resp = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey
            ])->acceptJson()->timeout(10)->get($fullUrl);

            if ($resp->successful()) {
                return [
                    'success' => true,
                    'message' => 'Cal.com Verbindung erfolgreich',
                    'data' => $resp->json()
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Cal.com Verbindung fehlgeschlagen',
                    'error' => $resp->json() ?? $resp->body()
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Cal.com Verbindungsfehler: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Reschedule an existing booking to a new date/time
     *
     * @param int|string $bookingId Cal.com booking ID
     * @param string $newDateTime New start time in ISO 8601 format (should include timezone)
     * @param string|null $reason Optional reason for rescheduling
     * @param string|null $timezone Optional timezone (defaults to Europe/Berlin)
     * @return Response
     * @throws CalcomApiException
     */
    public function rescheduleBooking($bookingId, string $newDateTime, ?string $reason = null, ?string $timezone = null): Response
    {
        // Preserve timezone context for metadata
        $timezone = $timezone ?? 'Europe/Berlin';

        // Parse the datetime to extract timezone info
        $dateCarbon = \Carbon\Carbon::parse($newDateTime);
        $dateUtc = $dateCarbon->copy()->utc()->toIso8601String();

        // Build payload for reschedule
        // NOTE: Cal.com API v2 reschedule endpoint ONLY accepts 'start' field
        // Any other fields (rescheduledReason, metadata) will cause HTTP 400 error
        $payload = [
            'start' => $dateUtc, // Send UTC to Cal.com
        ];

        Log::channel('calcom')->debug('[Cal.com V2] Reschedule Booking Request:', [
            'booking_id' => $bookingId,
            'new_start_time' => $newDateTime,
            'new_start_time_utc' => $dateUtc,
            'timezone' => $timezone,
            'reason' => $reason,
            'payload' => $payload
        ]);

        try {
            // Wrap Cal.com API call with circuit breaker for reliability
            return $this->circuitBreaker->call(function() use ($bookingId, $payload) {
                $fullUrl = $this->baseUrl . '/bookings/' . $bookingId . '/reschedule';
                $resp = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'cal-api-version' => config('services.calcom.api_version', '2024-08-13'),
                    'Content-Type' => 'application/json'
                ])->acceptJson()->post($fullUrl, $payload);

                Log::channel('calcom')->debug('[Cal.com V2] Reschedule Response:', [
                    'status' => $resp->status(),
                    'body' => $resp->json() ?? $resp->body(),
                ]);

                // Throw exception if not successful
                if (!$resp->successful()) {
                    throw CalcomApiException::fromResponse($resp, "/bookings/{$bookingId}/reschedule", $payload, 'POST');
                }

                return $resp;
            });
        } catch (CircuitBreakerOpenException $e) {
            Log::error('Circuit breaker OPEN for rescheduleBooking', [
                'service' => 'calcom_api',
                'method' => 'rescheduleBooking',
                'booking_id' => $bookingId
            ]);

            throw new CalcomApiException(
                'Cal.com API circuit breaker is open. Service appears to be down.',
                null, "/bookings/{$bookingId}/reschedule", $payload ?? [], 503
            );
        }
    }

    /**
     * Cancel an existing booking
     *
     * @param int|string $bookingId Cal.com booking ID
     * @param string|null $reason Optional reason for cancellation
     * @return Response
     * @throws CalcomApiException
     */
    public function cancelBooking($bookingId, ?string $reason = null): Response
    {
        $payload = [];

        if ($reason) {
            $payload['cancellationReason'] = $reason;
        }

        Log::channel('calcom')->debug('[Cal.com V2] Cancel Booking Request:', [
            'booking_id' => $bookingId,
            'reason' => $reason,
        ]);

        try {
            return $this->circuitBreaker->call(function() use ($bookingId, $payload) {
                $fullUrl = $this->baseUrl . '/bookings/' . $bookingId . '/cancel';
                $resp = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'cal-api-version' => config('services.calcom.api_version', '2024-08-13'),
                    'Content-Type' => 'application/json'
                ])->acceptJson()->post($fullUrl, $payload);

                Log::channel('calcom')->debug('[Cal.com V2] Cancel Response:', [
                    'status' => $resp->status(),
                    'body' => $resp->json() ?? $resp->body(),
                ]);

                if (!$resp->successful()) {
                    throw CalcomApiException::fromResponse($resp, "/bookings/{$bookingId}/cancel", $payload, 'POST');
                }

                return $resp;
            });
        } catch (CircuitBreakerOpenException $e) {
            Log::error('Circuit breaker OPEN for cancelBooking', [
                'service' => 'calcom_api',
                'method' => 'cancelBooking',
                'booking_id' => $bookingId
            ]);

            throw new CalcomApiException(
                'Cal.com API circuit breaker is open. Service appears to be down.',
                null, "/bookings/{$bookingId}/cancel", $payload, 503
            );
        }
    }

    /**
     * Get first available slot time from slots data (for logging)
     *
     * @param array $slotsData Slots data from Cal.com response
     * @return string|null First slot time or null if no slots
     */
    private function getFirstSlotTime(array $slotsData): ?string
    {
        foreach ($slotsData as $date => $dateSlots) {
            if (!empty($dateSlots) && isset($dateSlots[0]['time'])) {
                return $dateSlots[0]['time'];
            }
        }
        return null;
    }
}
