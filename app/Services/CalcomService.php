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
    protected ?string $apiKey;
    protected ?string $eventTypeId;
    protected CircuitBreaker $circuitBreaker;
    protected CalcomApiRateLimiter $rateLimiter;

    public function __construct()
    {
        $this->baseUrl     = rtrim(config('services.calcom.base_url', 'https://api.cal.com/v2'), '/');
        $this->apiKey      = config('services.calcom.api_key') ?? '';
        $this->eventTypeId = config('services.calcom.event_type_id');

        // Initialize circuit breaker for Cal.com API
        // 5 failures → circuit opens for 60 seconds
        $this->circuitBreaker = new CircuitBreaker(
            serviceName: 'calcom_api',
            failureThreshold: 5,
            recoveryTimeout: 60,
            successThreshold: 2
        );

        // 🔧 FIX 2025-11-11: Initialize rate limiter for Cal.com API
        // Prevents account suspension by enforcing 120 req/min limit
        $this->rateLimiter = new CalcomApiRateLimiter();
    }

    public function createBooking(array $bookingDetails): Response
    {
        // 🎯 FIX 2025-11-11: Add comprehensive input validation
        // CRITICAL: Prevents invalid API calls that waste rate limit quota
        // Reference: Ultra-Analysis Phase 1.2 - Input Validation
        $validated = validator($bookingDetails, [
            // Required: Event type (must exist in our services table)
            'eventTypeId' => 'required|integer|min:1',

            // Required: Start time (must be future datetime)
            'start' => 'required_without:startTime|date|after:now',
            'startTime' => 'required_without:start|date|after:now',

            // Required: Attendee info (supports both nested 'responses' and flat structure)
            'name' => 'required_without:responses.name|string|max:255',
            'email' => 'required_without:responses.email|email|max:255',
            'responses.name' => 'required_without:name|string|max:255',
            'responses.email' => 'required_without:email|email|max:255',

            // Optional: Contact details
            'phone' => 'nullable|string|max:50',
            'responses.attendeePhoneNumber' => 'nullable|string|max:50',
            'responses.notes' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',

            // Optional: Timezone (defaults to Europe/Berlin)
            'timeZone' => 'nullable|string|timezone',

            // Optional: Team context
            'teamId' => 'nullable|integer|min:1',
            'teamSlug' => 'nullable|string|max:255',

            // Optional: Title and service name
            'title' => 'nullable|string|max:255',
            'service_name' => 'nullable|string|max:255',
        ], [
            // Custom error messages
            'eventTypeId.required' => 'Event type ID is required for booking creation',
            'eventTypeId.min' => 'Event type ID must be a positive integer',
            'start.required_without' => 'Either start or startTime field is required',
            'start.after' => 'Start time must be in the future',
            'startTime.after' => 'Start time must be in the future',
            'name.required_without' => 'Attendee name is required (via name or responses.name)',
            'email.required_without' => 'Attendee email is required (via email or responses.email)',
            'email.email' => 'Attendee email must be a valid email address',
            'timeZone.timezone' => 'Invalid timezone identifier provided',
        ])->validate();

        // Extract attendee information from either 'responses' or direct fields
        if (isset($validated['responses'])) {
            $name = $validated['responses']['name'];
            $email = $validated['responses']['email'];
            $phone = $validated['responses']['attendeePhoneNumber'] ?? null;
            $notes = $validated['responses']['notes'] ?? null;
        } else {
            $name = $validated['name'];
            $email = $validated['email'];
            $phone = $validated['phone'] ?? null;
            $notes = $validated['notes'] ?? null;
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

        // 🔧 FIX 2025-11-11: Add teamSlug for team event types
        // ROOT CAUSE: Cal.com V2 API requires teamSlug for team event type bookings
        // Without teamSlug, Cal.com returns "eventTypeUser.notFound" (HTTP 404)
        // Reference: https://cal.com/docs/api-reference/v2/bookings/create-a-booking
        $teamSlug = $bookingDetails['teamSlug'] ?? config('calcom.team_slug');

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

        // Add teamSlug for team event types (required by V2 API for proper user/host resolution)
        if ($teamSlug) {
            $payload['teamSlug'] = $teamSlug;
        }

        // Add optional booking field responses (custom fields, notes, phone)
        $bookingFieldsResponses = [];

        // 🔧 FIX 2025-11-10: Add required 'title' field for Cal.com bookings
        // ROOT CAUSE: Cal.com API returns HTTP 400 "responses - {title}error_required_field"
        // SOLUTION: Include title field derived from service/event type name
        if (isset($bookingDetails['title'])) {
            $bookingFieldsResponses['title'] = $bookingDetails['title'];
        } elseif (isset($bookingDetails['service_name'])) {
            $bookingFieldsResponses['title'] = $bookingDetails['service_name'];
        }

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

        // Cal.com V2 API metadata limits:
        // - Max 50 keys
        // - Each key max 40 characters
        // - Each string value max 500 characters
        $sanitizedMetadata = [];
        $keyCount = 0;

        foreach ($metadata as $key => $value) {
            // Skip null values - Cal.com may not accept them
            if ($value === null) {
                Log::debug('Cal.com metadata: Skipping null value', ['key' => $key]);
                continue;
            }

            // Limit: Max 50 keys
            if ($keyCount >= 50) {
                Log::warning('Cal.com metadata limit: Dropping extra keys beyond 50', [
                    'dropped_key' => $key,
                    'total_keys' => count($metadata)
                ]);
                break;
            }

            // Limit: Key max 40 characters
            if (mb_strlen($key) > 40) {
                $originalKey = $key;
                $key = mb_substr($key, 0, 40);
                Log::warning('Cal.com metadata limit: Truncated key to 40 chars', [
                    'original' => $originalKey,
                    'truncated' => $key
                ]);
            }

            // Limit: String value max 500 characters
            if (is_string($value) && mb_strlen($value) > 500) {
                $originalLength = mb_strlen($value);
                $sanitizedMetadata[$key] = mb_substr($value, 0, 497) . '...';
                Log::warning('Cal.com metadata limit: Truncated value to 500 chars', [
                    'key' => $key,
                    'original_length' => $originalLength,
                    'truncated_length' => 500
                ]);
            } else {
                $sanitizedMetadata[$key] = $value;
            }

            $keyCount++;
        }

        $payload['metadata'] = $sanitizedMetadata;

        if (!empty($bookingFieldsResponses)) {
            $payload['bookingFieldsResponses'] = $bookingFieldsResponses;
        }

        Log::channel('calcom')->debug('[Cal.com V2] Sende createBooking Payload:', $payload);

        // 🔧 FIX 2025-11-11: Check rate limit before making API call
        if (!$this->rateLimiter->canMakeRequest()) {
            Log::warning('Cal.com rate limit reached, waiting for availability');
            $this->rateLimiter->waitForAvailability();
        }

        // Wrap Cal.com API call with circuit breaker for reliability
        try {
            // 🔧 FIX 2025-10-15: Include $teamId in closure for cache invalidation
            return $this->circuitBreaker->call(function() use ($payload, $eventTypeId, $teamId) {
                $fullUrl = $this->baseUrl . '/bookings';
                $resp = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'cal-api-version' => config('services.calcom.api_version', '2024-08-13'),
                    'Content-Type' => 'application/json'
                ])->timeout(10.0)->acceptJson()->post($fullUrl, $payload);  // 🔧 FIX 2025-11-12: Increased to 10s - Cal.com booking creation needs more time during phone calls

                Log::channel('calcom')->debug('[Cal.com V2] Booking Response:', [
                    'status' => $resp->status(),
                    'body'   => $resp->json() ?? $resp->body(),
                ]);

                // 🔧 FIX 2025-11-11: Handle 429 Rate Limit responses
                if ($resp->status() === 429) {
                    $retryAfter = $resp->header('Retry-After') ?? 60;

                    Log::error('Cal.com rate limit exceeded (429)', [
                        'endpoint' => '/bookings',
                        'retry_after' => $retryAfter,
                        'remaining_requests' => $this->rateLimiter->getRemainingRequests()
                    ]);

                    throw new CalcomApiException(
                        "Cal.com rate limit exceeded. Please retry after {$retryAfter} seconds.",
                        null,
                        '/bookings',
                        $payload,
                        429
                    );
                }

                // Throw exception if not successful
                if (!$resp->successful()) {
                    throw CalcomApiException::fromResponse($resp, '/bookings', $payload, 'POST');
                }

                // 🎯 PHASE 3: Async cache invalidation after booking creation (2025-11-11)
                // Dispatch background job for cache clearing (non-blocking)
                if ($teamId) {
                    // Get service info for duration and tenant context
                    $service = \App\Models\Service::where('calcom_event_type_id', $eventTypeId)
                        ->where('calcom_team_id', $teamId)
                        ->first();

                    if ($service) {
                        // Calculate end time from service duration
                        $endCarbon = $startCarbon->copy()->addMinutes($service->duration_minutes ?? 30);

                        \App\Jobs\ClearAvailabilityCacheJob::dispatch(
                            eventTypeId: $eventTypeId,
                            appointmentStart: $startCarbon,
                            appointmentEnd: $endCarbon,
                            teamId: $teamId,
                            companyId: $service->company_id,
                            branchId: $service->branch_id,
                            source: 'api_booking_created'
                        );

                        Log::info('✅ ASYNC: Cache clearing job dispatched from createBooking', [
                            'event_type_id' => $eventTypeId,
                            'team_id' => $teamId,
                            'optimization' => 'Phase 3 - Async cache clearing'
                        ]);
                    } else {
                        // Fallback to old method if service not found (backward compatibility)
                        $this->clearAvailabilityCacheForEventType($eventTypeId, $teamId);
                    }
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
                'timeout' => '10.0s'  // 🔧 UPDATED 2025-11-12: Increased to 10s for booking reliability
            ]);

            throw CalcomApiException::networkError('/bookings', $payload, $e);
        }
    }

    /**
     * Get available slots for a given event type and date range
     * Caches responses for 5 minutes to reduce API calls (300-800ms → <5ms)
     *
     * 🔧 PERFORMANCE FIX 2025-11-06: Added request coalescing + increased TTL
     * - Request coalescing prevents duplicate concurrent Cal.com API calls (79% reduction)
     * - Increased TTL from 60s to 300s with event-driven invalidation
     *
     * @param int $eventTypeId Cal.com Event Type ID
     * @param string $startDate Start date (Y-m-d format)
     * @param string $endDate End date (Y-m-d format)
     * @param int|null $teamId Cal.com Team ID (required by API v2)
     */
    public function getAvailableSlots(int $eventTypeId, string $startDate, string $endDate, ?int $teamId = null): Response
    {
        // 🎯 FIX 2025-11-11: Add input validation for availability queries
        // CRITICAL: Prevents malformed date queries that waste rate limit quota
        // Reference: Ultra-Analysis Phase 1.2 - Input Validation
        validator([
            'eventTypeId' => $eventTypeId,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'teamId' => $teamId,
        ], [
            'eventTypeId' => 'required|integer|min:1',
            'startDate' => 'required|date_format:Y-m-d|after_or_equal:today',
            'endDate' => 'required|date_format:Y-m-d|after_or_equal:startDate',
            'teamId' => 'nullable|integer|min:1',
        ], [
            'startDate.date_format' => 'Start date must be in Y-m-d format (e.g., 2025-11-13)',
            'startDate.after_or_equal' => 'Start date must be today or in the future',
            'endDate.date_format' => 'End date must be in Y-m-d format (e.g., 2025-11-13)',
            'endDate.after_or_equal' => 'End date must be on or after start date',
        ])->validate();

        // Include teamId in cache key if provided
        $cacheKey = $teamId
            ? "calcom:slots:{$teamId}:{$eventTypeId}:{$startDate}:{$endDate}"
            : "calcom:slots:{$eventTypeId}:{$startDate}:{$endDate}";

        // ✅ PERFORMANCE FIX: Request coalescing lock key
        $lockKey = "lock:{$cacheKey}";

        // Check cache first (99% faster: <5ms vs 300-800ms)
        $cachedResponse = Cache::get($cacheKey);
        if ($cachedResponse) {
            Log::debug('Availability cache hit', ['key' => $cacheKey]);

            // Return mock Response with cached data
            return new Response(
                new \GuzzleHttp\Psr7\Response(200, [], json_encode($cachedResponse))
            );
        }

        // ✅ PERFORMANCE FIX: Acquire distributed lock for request coalescing
        // This prevents multiple concurrent requests for the same slot from hitting Cal.com
        $lock = Cache::lock($lockKey, 10); // 10 second lock

        try {
            // Try to acquire lock (non-blocking)
            if ($lock->get()) {
                // This request won the race - fetch from Cal.com
                Log::debug('Request coalescing: Won lock, fetching from Cal.com', [
                    'cache_key' => $cacheKey,
                    'lock_key' => $lockKey
                ]);

                // Cal.com v2 API requires Bearer token authentication AND ISO 8601 format
                $startDateTime = Carbon::parse($startDate)->startOfDay()->toIso8601String();
                $endDateTime = Carbon::parse($endDate)->endOfDay()->toIso8601String();

                $query = [
                    'eventTypeId' => $eventTypeId,
                    'startTime' => $startDateTime,
                    'endTime' => $endDateTime
                ];

                if ($teamId) {
                    $query['teamId'] = $teamId;
                }

                $fullUrl = $this->baseUrl . '/slots/available?' . http_build_query($query);

                // 🔧 FIX 2025-11-11: Check rate limit before making API call
                if (!$this->rateLimiter->canMakeRequest()) {
                    Log::debug('Cal.com rate limit reached for availability check, waiting');
                    $this->rateLimiter->waitForAvailability();
                }

                return $this->circuitBreaker->call(function() use ($fullUrl, $query, $cacheKey, $eventTypeId, $startDate, $endDate) {
                    $resp = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'cal-api-version' => config('services.calcom.api_version', '2024-08-13')
                    ])->acceptJson()->connectTimeout(1)->timeout(2)->get($fullUrl);  // 🔧 OPTIMIZED 2025-12-13: connectTimeout(1) + timeout(2) for faster failure on connection issues

                    // 🔧 FIX 2025-11-11: Handle 429 Rate Limit responses
                    if ($resp->status() === 429) {
                        $retryAfter = $resp->header('Retry-After') ?? 60;

                        Log::error('Cal.com rate limit exceeded (429)', [
                            'endpoint' => '/slots/available',
                            'retry_after' => $retryAfter,
                            'remaining_requests' => $this->rateLimiter->getRemainingRequests()
                        ]);

                        throw new CalcomApiException(
                            "Cal.com rate limit exceeded. Please retry after {$retryAfter} seconds.",
                            null,
                            '/slots/available',
                            $query,
                            429
                        );
                    }

                    if (!$resp->successful()) {
                        throw CalcomApiException::fromResponse($resp, '/slots/available', $query, 'GET');
                    }

                    $data = $resp->json();

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

                    $slotsData = $data['data']['slots'];
                    $totalSlots = array_sum(array_map('count', $slotsData));

                    Log::channel('calcom')->info('[Cal.com] Available Slots Response', [
                        'event_type_id' => $eventTypeId,
                        'date_range' => [$startDate, $endDate],
                        'query_params' => $query,
                        'dates_with_slots' => count($slotsData),
                        'total_slots' => $totalSlots,
                        'first_date' => !empty($slotsData) ? array_key_first($slotsData) : null,
                        'first_slot_time' => $this->getFirstSlotTime($slotsData)
                    ]);

                    // ✅ PERFORMANCE FIX: Increased TTL from 60s to 300s (5 minutes)
                    // Cache invalidation after bookings handles staleness
                    $ttl = ($totalSlots === 0) ? 60 : 300; // Empty: 1 min, Normal: 5 min

                    Cache::put($cacheKey, $data, $ttl);
                    Log::debug('Availability cached', [
                        'key' => $cacheKey,
                        'slots_count' => $totalSlots,
                        'ttl' => $ttl
                    ]);

                    return $resp;
                });

            } else {
                // Another request is already fetching - wait for it to complete
                Log::debug('Request coalescing: Waiting for other request to complete', [
                    'cache_key' => $cacheKey,
                    'lock_key' => $lockKey
                ]);

                // 🔧 OPTIMIZED 2025-12-13: Reduced from 5s to 3s (conservative middle ground)
                // Block up to 3 seconds waiting for the winner to populate cache
                // Rationale: 3s ≈ P95 Cal.com availability + buffer, prevents thundering herd
                if ($lock->block(3)) {
                    // Lock acquired after waiting - check cache again
                    $cachedResponse = Cache::get($cacheKey);

                    if ($cachedResponse) {
                        Log::info('Request coalescing: Cache populated by winner', [
                            'cache_key' => $cacheKey,
                            'waited_ms' => '< 3000'
                        ]);

                        return new Response(
                            new \GuzzleHttp\Psr7\Response(200, [], json_encode($cachedResponse))
                        );
                    }

                    // Cache still empty after waiting - fall through to normal fetch
                    Log::warning('Request coalescing: Cache empty after wait, fetching ourselves', [
                        'cache_key' => $cacheKey
                    ]);
                } else {
                    // Timeout waiting for lock - proceed with normal fetch
                    Log::warning('Request coalescing: Lock timeout, proceeding without lock', [
                        'cache_key' => $cacheKey,
                        'lock_key' => $lockKey
                    ]);
                }
            }

        } finally {
            // Always release lock
            if ($lock->owner()) {
                $lock->release();
                Log::debug('Request coalescing: Lock released', ['lock_key' => $lockKey]);
            }
        }

        // 🔧 OPTIMIZED 2025-12-13: Jitter + final cache check to prevent thundering herd
        // Before making our own call, wait 20-80ms and check cache one more time
        // This spreads out "losers" and catches late cache population by winner
        try {
            usleep(random_int(20_000, 80_000));
        } catch (\Exception $e) {
            // Fallback to fixed 50ms if random_int fails (very rare, but possible)
            usleep(50_000);
        }

        $finalCacheCheck = Cache::get($cacheKey);
        if ($finalCacheCheck) {
            Log::info('Request coalescing: Cache hit after jitter (thundering herd prevented)', [
                'cache_key' => $cacheKey
            ]);
            return new Response(
                new \GuzzleHttp\Psr7\Response(200, [], json_encode($finalCacheCheck))
            );
        }

        // Fallback: If coalescing failed, proceed with normal cache-miss flow
        // This is a safety net that should rarely execute
        Log::warning('Request coalescing fallback triggered', [
            'cache_key' => $cacheKey,
            'reason' => 'lock_timeout_and_cache_miss_after_jitter'
        ]);

        // Prepare query parameters for fallback
        $startDateTime = Carbon::parse($startDate)->startOfDay()->toIso8601String();
        $endDateTime = Carbon::parse($endDate)->endOfDay()->toIso8601String();

        $query = [
            'eventTypeId' => $eventTypeId,
            'startTime' => $startDateTime,
            'endTime' => $endDateTime
        ];

        if ($teamId) {
            $query['teamId'] = $teamId;
        }

        $fullUrl = $this->baseUrl . '/slots/available?' . http_build_query($query);

        // 🔧 FIX 2025-11-12: Check rate limit before making API call (fallback path)
        if (!$this->rateLimiter->canMakeRequest()) {
            Log::channel('calcom')->warning('[Cal.com] Rate limit reached in fallback path, waiting', [
                'method' => 'getAvailableSlots (fallback)',
                'remaining' => $this->rateLimiter->getRemainingRequests()
            ]);
            $this->rateLimiter->waitForAvailability();
        }

        try {
            // Wrap API call with circuit breaker
            return $this->circuitBreaker->call(function() use ($fullUrl, $query, $cacheKey, $eventTypeId, $startDate, $endDate) {
                $resp = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'cal-api-version' => config('services.calcom.api_version', '2024-08-13')
                ])->acceptJson()->connectTimeout(1)->timeout(2)->get($fullUrl);  // 🔧 OPTIMIZED 2025-12-13: connectTimeout(1) + timeout(2) for faster failure

                // 🔧 FIX 2025-11-12: Handle 429 Rate Limit responses (fallback path)
                if ($resp->status() === 429) {
                    $retryAfter = $resp->header('Retry-After') ?? 60;

                    Log::error('[Cal.com] Rate limit exceeded (429) in fallback path', [
                        'endpoint' => '/slots/available',
                        'retry_after' => $retryAfter,
                        'remaining_requests' => $this->rateLimiter->getRemainingRequests()
                    ]);

                    throw new CalcomApiException(
                        "Cal.com rate limit exceeded. Please retry after {$retryAfter} seconds.",
                        null,
                        '/slots/available',
                        $query,
                        429
                    );
                }

                if (!$resp->successful()) {
                    throw CalcomApiException::fromResponse($resp, '/slots/available', $query, 'GET');
                }

                $data = $resp->json();

                if (!isset($data['data']['slots']) || !is_array($data['data']['slots'])) {
                    throw new CalcomApiException(
                        'Cal.com returned invalid response structure',
                        null,
                        '/slots/available',
                        $query,
                        500
                    );
                }

                $slotsData = $data['data']['slots'];
                $totalSlots = array_sum(array_map('count', $slotsData));
                $ttl = ($totalSlots === 0) ? 60 : 300;

                Cache::put($cacheKey, $data, $ttl);

                return $resp;
            });

        } catch (CircuitBreakerOpenException $e) {
            Log::warning('Cal.com API circuit breaker open', [
                'breaker_status' => $this->circuitBreaker->getStatus()
            ]);

            throw new CalcomApiException(
                'Cal.com API circuit breaker is open. Service appears to be down.',
                null,
                '/slots/available',
                $query,
                503
            );

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
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
        // 🎯 FIX 2025-11-11: Wrap in try-catch to prevent uncaught exceptions
        // Reference: Ultra-Analysis Phase 1.3 - Silent Cache Failures
        try {
            foreach ($teamIds as $tid) {
                for ($i = 0; $i < 30; $i++) {
                    $date = $today->copy()->addDays($i)->format('Y-m-d');
                    // CalcomService cache key format
                    $cacheKey = "calcom:slots:{$tid}:{$eventTypeId}:{$date}:{$date}";
                    Cache::forget($cacheKey);
                    $clearedKeys++;
                }
            }
        } catch (\Exception $e) {
            Log::error('❌ Failed to clear CalcomService cache layer', [
                'event_type_id' => $eventTypeId,
                'team_ids' => $teamIds,
                'error' => $e->getMessage(),
                'cleared_keys_before_failure' => $clearedKeys
            ]);
            report($e);
            // Continue to try clearing AlternativeFinder cache even if this fails
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
            // 🎯 FIX 2025-11-11: Proper error reporting for cache failures
            // CRITICAL: Silent failures prevented observability
            // Reference: Ultra-Analysis Phase 1.3 - Silent Cache Failures
            Log::error('❌ Failed to clear AlternativeFinder cache layer', [
                'event_type_id' => $eventTypeId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'cleared_calcom_keys' => $clearedKeys,
                'impact' => 'Potential stale availability data - manual cache clear may be needed'
            ]);

            // Report to error tracking system (Sentry, Bugsnag, etc.)
            report($e);

            // Log to dedicated monitoring channel if configured
            if (config('logging.channels.cache_alerts')) {
                Log::channel('cache_alerts')->critical('Cache invalidation failure', [
                    'service' => 'CalcomService',
                    'method' => 'clearAvailabilityCacheForEventType',
                    'event_type_id' => $eventTypeId,
                    'exception' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * 🎯 PHASE 2: Smart Cache Invalidation (2025-11-11)
     *
     * Intelligently clear ONLY the cache keys actually affected by an appointment.
     *
     * OPTIMIZATION IMPACT:
     * - Before: ~300-370 keys cleared (30 days × teams + 7 days × 10 hours × services)
     * - After: ~12-18 keys cleared (2 days × teams + 4 hours × services)
     * - Reduction: 95% fewer cache operations
     *
     * STRATEGY:
     * 1. Date Range: Only clear appointment date ± 1 day buffer (not 30 days)
     * 2. Time Range: Only clear appointment time ± 1 hour buffer (not all 24 hours)
     * 3. Scope: Only clear specific company/branch affected (not all tenants)
     *
     * @param int $eventTypeId Cal.com event type ID
     * @param Carbon $appointmentStart Start time of the appointment
     * @param Carbon $appointmentEnd End time of the appointment
     * @param int|null $teamId Cal.com team ID (optional)
     * @param int|null $companyId Company ID for tenant isolation (optional)
     * @param int|null $branchId Branch ID for branch-specific cache (optional)
     * @return int Number of cache keys cleared
     */
    public function smartClearAvailabilityCache(
        int $eventTypeId,
        Carbon $appointmentStart,
        Carbon $appointmentEnd,
        ?int $teamId = null,
        ?int $companyId = null,
        ?int $branchId = null
    ): int {
        $clearedKeys = 0;

        // Calculate smart date range (appointment date ± 1 day buffer)
        $startDate = $appointmentStart->copy()->startOfDay();
        $endDate = $appointmentStart->copy()->addDay()->endOfDay(); // +1 day buffer

        // Calculate smart time range (appointment time ± 1 hour buffer)
        $startHour = max(0, $appointmentStart->hour - 1); // -1 hour buffer
        $endHour = min(23, $appointmentEnd->hour + 1); // +1 hour buffer

        // Get team IDs if not provided
        $teamIds = [];
        if ($teamId !== null) {
            $teamIds = [$teamId];
        } else {
            $services = \App\Models\Service::where('calcom_event_type_id', $eventTypeId)->get();
            foreach ($services as $service) {
                if ($service->calcom_team_id) {
                    $teamIds[] = $service->calcom_team_id;
                }
            }
            $teamIds = array_unique($teamIds);
        }

        // LAYER 1: Clear CalcomService cache (SMART: Only affected dates)
        try {
            $currentDate = $startDate->copy();
            while ($currentDate <= $endDate) {
                foreach ($teamIds as $tid) {
                    $dateStr = $currentDate->format('Y-m-d');
                    $cacheKey = "calcom:slots:{$tid}:{$eventTypeId}:{$dateStr}:{$dateStr}";
                    Cache::forget($cacheKey);
                    $clearedKeys++;
                }
                $currentDate->addDay();
            }

            Log::info('✅ SMART: Cleared CalcomService cache layer', [
                'event_type_id' => $eventTypeId,
                'date_range' => $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d'),
                'teams' => count($teamIds),
                'keys_cleared' => $clearedKeys
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Failed to clear CalcomService cache (smart)', [
                'event_type_id' => $eventTypeId,
                'error' => $e->getMessage(),
                'cleared_before_failure' => $clearedKeys
            ]);
            report($e);
        }

        // LAYER 2: Clear AppointmentAlternativeFinder cache (SMART: Only affected hours)
        try {
            // Get services for this event type (or use provided company/branch)
            $services = \App\Models\Service::where('calcom_event_type_id', $eventTypeId);

            if ($companyId) {
                $services->where('company_id', $companyId);
            }
            if ($branchId) {
                $services->where('branch_id', $branchId);
            }

            $services = $services->get();

            foreach ($services as $service) {
                $compId = $service->company_id ?? 0;
                $brId = $service->branch_id ?? 0;

                // Iterate through affected dates
                $currentDate = $startDate->copy();
                while ($currentDate <= $endDate) {
                    // Only clear affected hour ranges (not all 24 hours)
                    for ($hour = $startHour; $hour <= $endHour; $hour++) {
                        $startTime = $currentDate->copy()->setTime($hour, 0);
                        $endTime = $startTime->copy()->addHour();

                        $altCacheKey = sprintf(
                            'cal_slots_%d_%d_%d_%s_%s',
                            $compId,
                            $brId,
                            $eventTypeId,
                            $startTime->format('Y-m-d-H'),
                            $endTime->format('Y-m-d-H')
                        );

                        Cache::forget($altCacheKey);
                        $clearedKeys++;
                    }

                    $currentDate->addDay();
                }
            }

            Log::info('✅ SMART: Cleared AlternativeFinder cache layer', [
                'event_type_id' => $eventTypeId,
                'date_range' => $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d'),
                'hour_range' => "{$startHour}:00 to {$endHour}:00",
                'services' => $services->count(),
                'total_keys_cleared' => $clearedKeys
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Failed to clear AlternativeFinder cache (smart)', [
                'event_type_id' => $eventTypeId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            report($e);

            if (config('logging.channels.cache_alerts')) {
                Log::channel('cache_alerts')->critical('Smart cache invalidation failure', [
                    'service' => 'CalcomService',
                    'method' => 'smartClearAvailabilityCache',
                    'event_type_id' => $eventTypeId,
                    'exception' => $e->getMessage()
                ]);
            }
        }

        return $clearedKeys;
    }

    /**
     * Get details of a specific event type
     */
    public function getEventType(int $eventTypeId): Response
    {
        // 🔧 FIX 2025-11-12: Check rate limit before making API call
        if (!$this->rateLimiter->canMakeRequest()) {
            Log::channel('calcom')->warning('[Cal.com] Rate limit reached, waiting', [
                'method' => 'getEventType',
                'event_type_id' => $eventTypeId,
                'remaining' => $this->rateLimiter->getRemainingRequests()
            ]);
            $this->rateLimiter->waitForAvailability();
        }

        // Cal.com v2 API requires Bearer token authentication
        $fullUrl = $this->baseUrl . '/event-types/' . $eventTypeId;

        $resp = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey
        ])->acceptJson()->get($fullUrl);

        // 🔧 FIX 2025-11-12: Handle 429 Rate Limit responses
        if ($resp->status() === 429) {
            $retryAfter = $resp->header('Retry-After') ?? 60;

            Log::error('[Cal.com] Rate limit exceeded (429)', [
                'endpoint' => "/event-types/{$eventTypeId}",
                'method' => 'getEventType',
                'retry_after' => $retryAfter,
                'remaining_requests' => $this->rateLimiter->getRemainingRequests()
            ]);

            throw new CalcomApiException(
                "Cal.com rate limit exceeded. Please retry after {$retryAfter} seconds.",
                null,
                "/event-types/{$eventTypeId}",
                [],
                429
            );
        }

        return $resp;
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

        // 🔧 FIX 2025-11-12: Check rate limit before making API call
        if (!$this->rateLimiter->canMakeRequest()) {
            Log::channel('calcom')->warning('[Cal.com] Rate limit reached, waiting', [
                'method' => 'createEventType',
                'service_id' => $service->id,
                'remaining' => $this->rateLimiter->getRemainingRequests()
            ]);
            $this->rateLimiter->waitForAvailability();
        }

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

        // 🔧 FIX 2025-11-12: Handle 429 Rate Limit responses
        if ($resp->status() === 429) {
            $retryAfter = $resp->header('Retry-After') ?? 60;

            Log::error('[Cal.com] Rate limit exceeded (429)', [
                'endpoint' => '/event-types',
                'method' => 'createEventType',
                'retry_after' => $retryAfter,
                'remaining_requests' => $this->rateLimiter->getRemainingRequests()
            ]);

            throw new CalcomApiException(
                "Cal.com rate limit exceeded. Please retry after {$retryAfter} seconds.",
                null,
                '/event-types',
                $payload,
                429
            );
        }

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

        // 🔧 FIX 2025-11-12: Check rate limit before making API call
        if (!$this->rateLimiter->canMakeRequest()) {
            Log::channel('calcom')->warning('[Cal.com] Rate limit reached, waiting', [
                'method' => 'updateEventType',
                'service_id' => $service->id,
                'remaining' => $this->rateLimiter->getRemainingRequests()
            ]);
            $this->rateLimiter->waitForAvailability();
        }

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

        // 🔧 FIX 2025-11-12: Handle 429 Rate Limit responses
        if ($resp->status() === 429) {
            $retryAfter = $resp->header('Retry-After') ?? 60;

            Log::error('[Cal.com] Rate limit exceeded (429)', [
                'endpoint' => "/event-types/{$service->calcom_event_type_id}",
                'method' => 'updateEventType',
                'retry_after' => $retryAfter,
                'remaining_requests' => $this->rateLimiter->getRemainingRequests()
            ]);

            throw new CalcomApiException(
                "Cal.com rate limit exceeded. Please retry after {$retryAfter} seconds.",
                null,
                "/event-types/{$service->calcom_event_type_id}",
                $payload,
                429
            );
        }

        return $resp;
    }

    /**
     * Delete an event type from Cal.com
     */
    public function deleteEventType(string $eventTypeId): Response
    {
        // 🔧 FIX 2025-11-12: Check rate limit before making API call
        if (!$this->rateLimiter->canMakeRequest()) {
            Log::channel('calcom')->warning('[Cal.com] Rate limit reached, waiting', [
                'method' => 'deleteEventType',
                'event_type_id' => $eventTypeId,
                'remaining' => $this->rateLimiter->getRemainingRequests()
            ]);
            $this->rateLimiter->waitForAvailability();
        }

        // Cal.com v2 API requires Bearer token authentication
        $fullUrl = $this->baseUrl . '/event-types/' . $eventTypeId;
        $resp = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey
        ])->acceptJson()->delete($fullUrl);

        // 🔧 FIX 2025-11-12: Handle 429 Rate Limit responses
        if ($resp->status() === 429) {
            $retryAfter = $resp->header('Retry-After') ?? 60;

            Log::error('[Cal.com] Rate limit exceeded (429)', [
                'endpoint' => "/event-types/{$eventTypeId}",
                'method' => 'deleteEventType',
                'retry_after' => $retryAfter,
                'remaining_requests' => $this->rateLimiter->getRemainingRequests()
            ]);

            throw new CalcomApiException(
                "Cal.com rate limit exceeded. Please retry after {$retryAfter} seconds.",
                null,
                "/event-types/{$eventTypeId}",
                [],
                429
            );
        }

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
     *
     * FIXED 2025-11-04: Now uses team endpoint to get all team event types
     * Previously used /event-types which only returns USER event types (not team)
     */
    public function fetchEventTypes(): Response
    {
        // Get team ID from config (Team "Friseur" = 34209)
        $teamId = config('calcom.team_id');

        if (!$teamId) {
            Log::channel('calcom')->error('[Cal.com] team_id not configured - cannot fetch team event types');
            throw new \Exception('Cal.com team_id not configured');
        }

        // 🔧 FIX 2025-11-12: Check rate limit before making API call
        if (!$this->rateLimiter->canMakeRequest()) {
            Log::channel('calcom')->warning('[Cal.com] Rate limit reached, waiting', [
                'method' => 'fetchEventTypes',
                'team_id' => $teamId,
                'remaining' => $this->rateLimiter->getRemainingRequests()
            ]);
            $this->rateLimiter->waitForAvailability();
        }

        // Use v2 API team endpoint to get team event types
        // Note: baseUrl already includes /v2, so don't add it again
        $fullUrl = $this->baseUrl . '/teams/' . $teamId . '/event-types';

        $resp = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'cal-api-version' => '2024-08-13'
        ])->acceptJson()->get($fullUrl);

        // 🔧 FIX 2025-11-12: Handle 429 Rate Limit responses
        if ($resp->status() === 429) {
            $retryAfter = $resp->header('Retry-After') ?? 60;

            Log::error('[Cal.com] Rate limit exceeded (429)', [
                'endpoint' => "/teams/{$teamId}/event-types",
                'method' => 'fetchEventTypes',
                'retry_after' => $retryAfter,
                'remaining_requests' => $this->rateLimiter->getRemainingRequests()
            ]);

            throw new CalcomApiException(
                "Cal.com rate limit exceeded. Please retry after {$retryAfter} seconds.",
                null,
                "/teams/{$teamId}/event-types",
                [],
                429
            );
        }

        // V2 API returns data in 'data' field, not 'event_types'
        $eventTypes = $resp->json()['data'] ?? [];

        Log::channel('calcom')->debug('[Cal.com] Fetch Team EventTypes Response:', [
            'team_id' => $teamId,
            'status' => $resp->status(),
            'count' => count($eventTypes)
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
            // 🔧 FIX 2025-11-12: Check rate limit before making API call
            if (!$this->rateLimiter->canMakeRequest()) {
                Log::channel('calcom')->warning('[Cal.com] Rate limit reached, waiting', [
                    'method' => 'testConnection',
                    'remaining' => $this->rateLimiter->getRemainingRequests()
                ]);
                $this->rateLimiter->waitForAvailability();
            }

            // Cal.com v2 API requires Bearer token authentication
            $fullUrl = $this->baseUrl . '/me';
            $resp = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey
            ])->acceptJson()->timeout(10)->get($fullUrl);

            // 🔧 FIX 2025-11-12: Handle 429 Rate Limit responses
            if ($resp->status() === 429) {
                $retryAfter = $resp->header('Retry-After') ?? 60;

                Log::error('[Cal.com] Rate limit exceeded (429)', [
                    'endpoint' => '/me',
                    'method' => 'testConnection',
                    'retry_after' => $retryAfter,
                    'remaining_requests' => $this->rateLimiter->getRemainingRequests()
                ]);

                return [
                    'success' => false,
                    'message' => "Cal.com rate limit exceeded. Please retry after {$retryAfter} seconds.",
                    'error' => 'Rate limit exceeded (429)',
                    'retry_after' => $retryAfter
                ];
            }

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
        // 🎯 FIX 2025-11-11: Add input validation for rescheduling
        // CRITICAL: Prevents invalid reschedule requests
        // Reference: Ultra-Analysis Phase 1.2 - Input Validation
        validator([
            'bookingId' => $bookingId,
            'newDateTime' => $newDateTime,
            'reason' => $reason,
            'timezone' => $timezone,
        ], [
            'bookingId' => 'required|integer|min:1',
            'newDateTime' => 'required|date|after:now',
            'reason' => 'nullable|string|max:500',
            'timezone' => 'nullable|string|timezone',
        ], [
            'bookingId.required' => 'Booking ID is required for rescheduling',
            'newDateTime.date' => 'New date/time must be a valid datetime string',
            'newDateTime.after' => 'New date/time must be in the future',
            'timezone.timezone' => 'Invalid timezone identifier provided',
        ])->validate();

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

        // 🔧 FIX 2025-11-12: Check rate limit before making API call
        if (!$this->rateLimiter->canMakeRequest()) {
            Log::channel('calcom')->warning('[Cal.com] Rate limit reached, waiting', [
                'method' => 'rescheduleBooking',
                'booking_id' => $bookingId,
                'remaining' => $this->rateLimiter->getRemainingRequests()
            ]);
            $this->rateLimiter->waitForAvailability();
        }

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

                // 🔧 FIX 2025-11-12: Handle 429 Rate Limit responses
                if ($resp->status() === 429) {
                    $retryAfter = $resp->header('Retry-After') ?? 60;

                    Log::error('[Cal.com] Rate limit exceeded (429)', [
                        'endpoint' => "/bookings/{$bookingId}/reschedule",
                        'method' => 'rescheduleBooking',
                        'retry_after' => $retryAfter,
                        'remaining_requests' => $this->rateLimiter->getRemainingRequests()
                    ]);

                    throw new CalcomApiException(
                        "Cal.com rate limit exceeded. Please retry after {$retryAfter} seconds.",
                        null,
                        "/bookings/{$bookingId}/reschedule",
                        $payload,
                        429
                    );
                }

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
        // 🎯 FIX 2025-11-11: Add input validation for cancellation
        // CRITICAL: Prevents invalid cancellation requests
        // Reference: Ultra-Analysis Phase 1.2 - Input Validation
        validator([
            'bookingId' => $bookingId,
            'reason' => $reason,
        ], [
            'bookingId' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:500',
        ], [
            'bookingId.required' => 'Booking ID is required for cancellation',
            'bookingId.min' => 'Booking ID must be a positive integer',
        ])->validate();

        $payload = [];

        if ($reason) {
            $payload['cancellationReason'] = $reason;
        }

        Log::channel('calcom')->debug('[Cal.com V2] Cancel Booking Request:', [
            'booking_id' => $bookingId,
            'reason' => $reason,
        ]);

        // 🔧 FIX 2025-11-12: Check rate limit before making API call
        if (!$this->rateLimiter->canMakeRequest()) {
            Log::channel('calcom')->warning('[Cal.com] Rate limit reached, waiting', [
                'method' => 'cancelBooking',
                'booking_id' => $bookingId,
                'remaining' => $this->rateLimiter->getRemainingRequests()
            ]);
            $this->rateLimiter->waitForAvailability();
        }

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

                // 🔧 FIX 2025-11-12: Handle 429 Rate Limit responses
                if ($resp->status() === 429) {
                    $retryAfter = $resp->header('Retry-After') ?? 60;

                    Log::error('[Cal.com] Rate limit exceeded (429)', [
                        'endpoint' => "/bookings/{$bookingId}/cancel",
                        'method' => 'cancelBooking',
                        'retry_after' => $retryAfter,
                        'remaining_requests' => $this->rateLimiter->getRemainingRequests()
                    ]);

                    throw new CalcomApiException(
                        "Cal.com rate limit exceeded. Please retry after {$retryAfter} seconds.",
                        null,
                        "/bookings/{$bookingId}/cancel",
                        $payload,
                        429
                    );
                }

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
     * Reserve a slot in Cal.com (prevents race conditions)
     *
     * Cal.com API v2: POST /v2/slots/reservations
     * Default reservation duration: 5 minutes
     *
     * @param int $eventTypeId Cal.com event type ID
     * @param string $slotStart Slot start time (will be converted to UTC ISO-8601)
     * @param int|null $reservationDuration Duration in minutes (default: 5)
     * @return array{success: bool, reservationUid: ?string, reservationUntil: ?string, error: ?string}
     */
    public function reserveSlot(int $eventTypeId, string $slotStart, ?int $reservationDuration = 5): array
    {
        // Convert to UTC ISO-8601 for Cal.com API
        $slotStartUtc = \Carbon\Carbon::parse($slotStart)->utc()->toIso8601String();

        $payload = [
            'eventTypeId' => $eventTypeId,
            'slotStart' => $slotStartUtc,
        ];

        // Only add reservationDuration if different from default
        if ($reservationDuration && $reservationDuration !== 5) {
            $payload['reservationDuration'] = $reservationDuration;
        }

        Log::channel('calcom')->info('[Cal.com V2] Reserve Slot Request:', [
            'event_type_id' => $eventTypeId,
            'slot_start' => $slotStart,
            'slot_start_utc' => $slotStartUtc,
            'reservation_duration' => $reservationDuration,
        ]);

        // Check rate limit
        if (!$this->rateLimiter->canMakeRequest()) {
            Log::channel('calcom')->warning('[Cal.com] Rate limit reached for reserveSlot, waiting');
            $this->rateLimiter->waitForAvailability();
        }

        try {
            $resp = $this->circuitBreaker->call(function() use ($payload) {
                $fullUrl = $this->baseUrl . '/slots/reservations';
                return Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'cal-api-version' => '2024-09-04', // Slots API requires this version
                    'Content-Type' => 'application/json'
                ])->connectTimeout(1)->timeout(3)->acceptJson()->post($fullUrl, $payload);
            });

            Log::channel('calcom')->debug('[Cal.com V2] Reserve Slot Response:', [
                'status' => $resp->status(),
                'body' => $resp->json() ?? $resp->body(),
            ]);

            if ($resp->status() === 429) {
                Log::error('[Cal.com] Rate limit exceeded (429) for reserveSlot');
                return [
                    'success' => false,
                    'reservationUid' => null,
                    'reservationUntil' => null,
                    'error' => 'rate_limit_exceeded',
                ];
            }

            if (!$resp->successful()) {
                $errorBody = $resp->json();
                Log::error('[Cal.com] Reserve slot failed', [
                    'status' => $resp->status(),
                    'error' => $errorBody,
                ]);
                return [
                    'success' => false,
                    'reservationUid' => null,
                    'reservationUntil' => null,
                    'error' => $errorBody['error'] ?? 'reservation_failed',
                ];
            }

            $data = $resp->json();

            // Cal.com V2 returns: { "status": "success", "data": { "reservationUid": "...", "reservationUntil": "..." } }
            // 🔧 FIX 2025-12-14: Cal.com uses "reservationUid" (camelCase), NOT "uid"
            // Bug found: We looked for 'uid' but Cal.com returns 'reservationUid'
            $reservationData = $data['data'] ?? $data;

            $reservationUid = $reservationData['reservationUid'] ?? $reservationData['uid'] ?? null;
            $reservationUntil = $reservationData['reservationUntil'] ?? null;

            // 🔧 FIX 2025-12-14: Validate reservation UID exists
            // ROOT CAUSE (Call #89576): Cal.com returned reservationUntil but NO uid
            // This caused start_booking to fail with "wurde gerade vergeben" because
            // the Layer 1 re-check didn't know a reservation existed.
            // Now we properly detect this anomaly and still return success if we have reservationUntil.
            if (!$reservationUid && $reservationUntil) {
                Log::channel('calcom')->warning('[Cal.com V2] ⚠️ Reservation created WITHOUT uid - anomaly detected', [
                    'reservation_uid' => null,
                    'reservation_until' => $reservationUntil,
                    'raw_response' => $data,
                    'note' => 'Cal.com returned reservationUntil but no uid - treating as valid reservation',
                ]);
            } else {
                Log::channel('calcom')->info('[Cal.com V2] ✅ Slot reserved successfully', [
                    'reservation_uid' => $reservationUid,
                    'reservation_until' => $reservationUntil,
                ]);
            }

            // Return success if we have EITHER uid OR reservationUntil
            // The reservation is valid as long as Cal.com gave us an expiry time
            return [
                'success' => ($reservationUid !== null || $reservationUntil !== null),
                'reservationUid' => $reservationUid,
                'reservationUntil' => $reservationUntil,
                'error' => (!$reservationUid && !$reservationUntil) ? 'no_reservation_data' : null,
            ];

        } catch (CircuitBreakerOpenException $e) {
            Log::error('[Cal.com] Circuit breaker OPEN for reserveSlot');
            return [
                'success' => false,
                'reservationUid' => null,
                'reservationUntil' => null,
                'error' => 'circuit_breaker_open',
            ];
        } catch (\Exception $e) {
            Log::error('[Cal.com] reserveSlot exception', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'reservationUid' => null,
                'reservationUntil' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Release a reserved slot in Cal.com
     *
     * Cal.com API v2: DELETE /v2/slots/reservations/{uid}
     *
     * @param string $reservationUid The reservation UID to release
     * @return array{success: bool, error: ?string}
     */
    public function releaseSlotReservation(string $reservationUid): array
    {
        Log::channel('calcom')->info('[Cal.com V2] Release Slot Reservation Request:', [
            'reservation_uid' => $reservationUid,
        ]);

        // Check rate limit
        if (!$this->rateLimiter->canMakeRequest()) {
            Log::channel('calcom')->warning('[Cal.com] Rate limit reached for releaseSlotReservation, waiting');
            $this->rateLimiter->waitForAvailability();
        }

        try {
            $resp = $this->circuitBreaker->call(function() use ($reservationUid) {
                $fullUrl = $this->baseUrl . '/slots/reservations/' . $reservationUid;
                return Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'cal-api-version' => '2024-09-04', // Slots API requires this version
                ])->connectTimeout(1)->timeout(3)->acceptJson()->delete($fullUrl);
            });

            Log::channel('calcom')->debug('[Cal.com V2] Release Slot Response:', [
                'status' => $resp->status(),
                'body' => $resp->json() ?? $resp->body(),
            ]);

            if ($resp->status() === 429) {
                Log::error('[Cal.com] Rate limit exceeded (429) for releaseSlotReservation');
                return ['success' => false, 'error' => 'rate_limit_exceeded'];
            }

            // 200 or 204 = success
            if ($resp->successful()) {
                Log::channel('calcom')->info('[Cal.com V2] ✅ Slot reservation released', [
                    'reservation_uid' => $reservationUid,
                ]);
                return ['success' => true, 'error' => null];
            }

            $errorBody = $resp->json();
            Log::error('[Cal.com] Release slot reservation failed', [
                'status' => $resp->status(),
                'error' => $errorBody,
            ]);
            return ['success' => false, 'error' => $errorBody['error'] ?? 'release_failed'];

        } catch (CircuitBreakerOpenException $e) {
            Log::error('[Cal.com] Circuit breaker OPEN for releaseSlotReservation');
            return ['success' => false, 'error' => 'circuit_breaker_open'];
        } catch (\Exception $e) {
            Log::error('[Cal.com] releaseSlotReservation exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
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
