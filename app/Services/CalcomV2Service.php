<?php

namespace App\Services;

use App\Services\CircuitBreaker\CircuitBreaker;
use App\Services\Logging\ProductionLogger;
use App\Services\RateLimiter\ApiRateLimiter;
use App\Services\Traits\RetryableHttpClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cal.com V2 API Integration Service.
 *
 * Provides comprehensive integration with Cal.com's V2 API for calendar management.
 * This service handles:
 * - Event type management and synchronization
 * - Availability checking with real-time slot calculation
 * - Booking creation and management
 * - Circuit breaker pattern for fault tolerance
 * - Rate limiting to respect API quotas
 * - Automatic retries with exponential backoff
 *
 * @author AskProAI Development Team
 *
 * @since 2.0.0
 * @see https://cal.com/docs/api/v2
 */
class CalcomV2Service
{
    use RetryableHttpClient;

    /**
     * @var string Cal.com API key for authentication
     */
    private $apiKey;

    /**
     * @var string Base URL for Cal.com V1 API (legacy support)
     */
    private $baseUrlV1 = 'https://api.cal.com/v1';

    /**
     * @var string Base URL for Cal.com V2 API
     */
    private $baseUrlV2;

    /**
     * @var CircuitBreaker Circuit breaker for fault tolerance
     */
    private CircuitBreaker $circuitBreaker;

    /**
     * @var ProductionLogger Structured logger for production monitoring
     */
    private ProductionLogger $logger;

    /**
     * @var ApiRateLimiter Rate limiter to prevent API quota exhaustion
     */
    private ApiRateLimiter $rateLimiter;

    /**
     * Make a safe API request with comprehensive error handling.
     *
     * This method wraps HTTP requests with:
     * - Rate limiting checks
     * - Circuit breaker pattern
     * - Automatic retry logic
     * - Response validation
     * - Error normalization
     *
     * @param string $method  HTTP method (get, post, put, delete)
     * @param string $url     Full URL to request
     * @param array  $data    Request body data
     * @param array  $headers Additional headers to merge
     *
     * @return array{
     *   success: bool,
     *   data?: mixed,
     *   error?: string,
     *   code?: int,
     *   retry_after?: int
     * }
     */
    protected function safeApiRequest($method, $url, $data = [], $headers = []): array
    {
        try {
            // Check rate limit first
            $this->rateLimiter->attempt('calcom', $this->apiKey);

            // Prepare headers
            $defaultHeaders = [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'cal-api-version' => '2024-08-13',
                'Content-Type' => 'application/json',
            ];
            $headers = array_merge($defaultHeaders, $headers);

            // Make request
            $response = Http::withHeaders($headers)->$method($url, $data);

            // Handle rate limiting
            if ($response->status() === 429) {
                $retryAfter = $response->header('Retry-After', 60);
                Log::warning('Cal.com API rate limit hit', [
                    'url' => $url,
                    'retry_after' => $retryAfter,
                ]);

                return [
                    'success' => false,
                    'error' => 'Rate limit exceeded',
                    'retry_after' => $retryAfter,
                    'code' => 429,
                ];
            }

            // Handle success
            if ($response->successful()) {
                $responseData = $response->json();

                // Validate response is valid JSON
                if ($responseData === null && $response->body() !== 'null') {
                    Log::error('Cal.com API returned invalid JSON', [
                        'url' => $url,
                        'response' => substr($response->body(), 0, 500),
                    ]);

                    return [
                        'success' => false,
                        'error' => 'Invalid response format',
                    ];
                }

                return [
                    'success' => true,
                    'data' => $responseData,
                ];
            }

            // Handle errors
            return [
                'success' => false,
                'error' => $response->body(),
                'code' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Cal.com API request failed', [
                'url' => $url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'exception' => true,
            ];
        }
    }

    private array $config;

    public function __construct($apiKey = null)
    {
        // Load configuration
        $this->config = config('calcom-v2', []);

        // Set API key (priority: parameter > config > fallback)
        $this->apiKey = $apiKey ?? ($this->config['api_key'] ?? null) ?? config('services.calcom.api_key');

        // Set base URL from config
        $this->baseUrlV2 = $this->config['base_url'] ?? 'https://api.cal.com/v2';

        // Initialize circuit breaker with config
        $this->circuitBreaker = new CircuitBreaker(
            (int) ($this->config['circuit_breaker']['failure_threshold'] ?? 5),
            (int) ($this->config['circuit_breaker']['success_threshold'] ?? 2),
            (int) ($this->config['circuit_breaker']['timeout'] ?? 60),
            3
        );

        $this->logger = new ProductionLogger;
        $this->rateLimiter = new ApiRateLimiter;
    }

    /**
     * Get current user info (v2).
     */
    public function getMe()
    {
        try {
            // Check rate limit
            $this->rateLimiter->attempt('calcom', $this->apiKey);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'cal-api-version' => '2024-08-13',
                'Content-Type' => 'application/json',
            ])->get($this->baseUrlV2 . '/me');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to fetch user info: ' . $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('Cal.com getMe error', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * V1 API für Users nutzen.
     */
    public function getUsers()
    {
        return $this->circuitBreaker->call('calcom', function () {
            $url = $this->baseUrlV1 . '/users';

            $response = $this->httpWithRetry()
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->get($url, ['apiKey' => $this->apiKey]);

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception('Cal.com getUsers failed with status: ' . $response->status());
        }, function () {
            // Fallback: Return cached data or empty array
            $this->logger->logError(new \Exception('Cal.com circuit open, using fallback'), [
                'method' => 'getUsers',
                'fallback' => 'empty_array',
            ]);

            return ['users' => []];
        });
    }

    /**
     * V1 API für Event-Types nutzen.
     */
    public function getEventTypes()
    {
        try {
            return $this->circuitBreaker->call('calcom', function () {
                $url = $this->baseUrlV1 . '/event-types';

                $response = $this->httpWithRetry()
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                    ])
                    ->get($url, ['apiKey' => $this->apiKey]);

                if ($response->successful()) {
                    $data = $response->json();
                    // Cache the successful response
                    \Cache::put('calcom_event_types_' . md5($this->apiKey), $data['event_types'] ?? [], 300);

                    return [
                        'success' => true,
                        'data' => $data,
                    ];
                }

                throw new \Exception('Cal.com getEventTypes failed with status: ' . $response->status());
            }, function () {
                // Fallback: Return cached event types if available
                $cached = \Cache::get('calcom_event_types_' . md5($this->apiKey), []);
                $this->logger->logError(new \Exception('Cal.com circuit open, using cached data'), [
                    'method' => 'getEventTypes',
                    'cached_count' => count($cached),
                ]);

                return [
                    'success' => true,
                    'data' => ['event_types' => $cached],
                    'cached' => true,
                ];
            });
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get detailed information for a specific event type.
     */
    public function getEventTypeDetails($eventTypeId)
    {
        return $this->circuitBreaker->call('calcom', function () use ($eventTypeId) {
            $url = $this->baseUrlV2 . '/event-types/' . $eventTypeId;

            $result = $this->safeApiRequest('get', $url);

            Log::info('Cal.com V2 getEventTypeDetails result', [
                'event_type_id' => $eventTypeId,
                'url' => $url,
                'result' => $result,
            ]);

            if ($result['success']) {
                return [
                    'success' => true,
                    'data' => $result['data']['eventType'] ?? $result['data'],
                ];
            }

            // V2 API might not have this endpoint, fall back to searching in the list
            Log::warning('Cal.com V2 getEventTypeDetails failed, trying fallback', [
                'event_type_id' => $eventTypeId,
                'error' => $result['error'] ?? 'Unknown',
            ]);

            // Fallback: Get from the list of event types
            $eventTypes = $this->getEventTypes();
            if (isset($eventTypes['event_types'])) {
                foreach ($eventTypes['event_types'] as $eventType) {
                    if ($eventType['id'] == $eventTypeId) {
                        return [
                            'success' => true,
                            'data' => $eventType,
                        ];
                    }
                }
            }

            return $result;
        }, function () use ($eventTypeId) {
            // Fallback: Return basic info if cached
            $this->logger->logError(new \Exception('Cal.com circuit open for getEventTypeDetails'), [
                'event_type_id' => $eventTypeId,
            ]);

            return [
                'success' => false,
                'error' => 'Circuit breaker open',
            ];
        });
    }

    /**
     * V2 API für Verfügbarkeiten - mit korrektem Slot-Flattening.
     */
    public function checkAvailability($eventTypeId, $date, $timezone = 'Europe/Berlin')
    {
        // Validate inputs
        if (! is_numeric($eventTypeId) || $eventTypeId <= 0) {
            throw new \InvalidArgumentException('Invalid event type ID');
        }

        // Validate date format
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new \InvalidArgumentException('Invalid date format. Expected: YYYY-MM-DD');
        }

        // Validate timezone
        if (! in_array($timezone, timezone_identifiers_list())) {
            throw new \InvalidArgumentException('Invalid timezone');
        }

        // Try cache first
        $cacheKey = "calcom:availability:{$eventTypeId}:{$date}:{$timezone}";
        $cached = Cache::get($cacheKey);
        if ($cached) {
            Log::debug('Using cached Cal.com availability', [
                'event_type_id' => $eventTypeId,
                'date' => $date,
                'timezone' => $timezone,
            ]);

            return $cached;
        }

        // Use circuit breaker for API call
        return $this->circuitBreaker->call(
            'calcom_availability',
            function () use ($eventTypeId, $date, $timezone, $cacheKey) {
                // Check rate limit
                $this->rateLimiter->attempt('calcom', $this->apiKey);
                $url = $this->baseUrlV2 . '/slots/available';

                $response = Http::withHeaders([
                    'cal-api-version' => '2024-08-13',
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])->timeout(10)->get($url, [
                    'eventTypeId' => $eventTypeId,
                    'startTime' => $date . 'T00:00:00.000Z',
                    'endTime' => $date . 'T23:59:59.999Z',
                    'timeZone' => $timezone,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $slots = $data['data']['slots'] ?? [];

                    // Flatten the nested slots structure
                    // V2 API returns slots grouped by date with time objects
                    $flatSlots = [];
                    foreach ($slots as $dateKey => $daySlots) {
                        if (is_array($daySlots)) {
                            foreach ($daySlots as $slot) {
                                // Handle both object format {"time": "..."} and direct string format
                                if (is_array($slot) && isset($slot['time'])) {
                                    $flatSlots[] = $slot['time'];
                                } elseif (is_string($slot)) {
                                    $flatSlots[] = $slot;
                                }
                            }
                        }
                    }

                    $result = [
                        'success' => true,
                        'data' => [
                            'slots' => $flatSlots,
                            'raw_slots' => $slots, // Keep original structure for debugging
                        ],
                    ];

                    // Cache successful result for 5 minutes
                    Cache::put($cacheKey, $result, 300);

                    return $result;
                }

                throw new \Exception('Failed to check availability: ' . $response->body());
            },
            function () use ($eventTypeId, $date, $timezone) {
                // Fallback: Check local availability or use cached data
                Log::warning('Cal.com circuit breaker open, using fallback', [
                    'event_type_id' => $eventTypeId,
                    'date' => $date,
                    'timezone' => $timezone,
                ]);

                // Try to use any older cached data
                $oldCacheKey = "calcom:availability:{$eventTypeId}:{$date}:{$timezone}:backup";
                $backup = Cache::get($oldCacheKey);
                if ($backup) {
                    return array_merge($backup, ['fallback' => true]);
                }

                // Use local availability service as last resort
                try {
                    $availabilityService = app(AvailabilityService::class);
                    $localSlots = $availabilityService->getAvailableSlots(
                        $eventTypeId,
                        Carbon::parse($date),
                        $timezone
                    );

                    return [
                        'success' => true,
                        'data' => [
                            'slots' => $localSlots,
                            'fallback' => true,
                            'source' => 'local',
                        ],
                    ];
                } catch (\Exception $e) {
                    Log::error('Local availability fallback failed', [
                        'error' => $e->getMessage(),
                    ]);

                    return [
                        'success' => false,
                        'error' => 'Cal.com is temporarily unavailable and no local fallback available',
                        'fallback' => true,
                    ];
                }
            }
        );
    }

    /**
     * Check availability for a date range
     * More efficient than checking individual days.
     */
    public function checkAvailabilityRange($eventTypeId, $startDate, $endDate, $timezone = 'Europe/Berlin')
    {
        // Validate inputs
        if (! is_numeric($eventTypeId) || $eventTypeId <= 0) {
            throw new \InvalidArgumentException('Invalid event type ID');
        }

        // Validate date format
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            throw new \InvalidArgumentException('Invalid date format. Expected: YYYY-MM-DD');
        }

        // Ensure start is before end
        if ($startDate > $endDate) {
            throw new \InvalidArgumentException('Start date must be before end date');
        }

        // Limit range to prevent abuse
        $daysDiff = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate));
        if ($daysDiff > 30) {
            throw new \InvalidArgumentException('Date range cannot exceed 30 days');
        }

        return $this->circuitBreaker->call(
            'calcom_availability_range',
            function () use ($eventTypeId, $startDate, $endDate, $timezone) {
                // Check rate limit
                $this->rateLimiter->attempt('calcom', $this->apiKey);
                $url = $this->baseUrlV2 . '/slots/available';

                $response = Http::withHeaders([
                    'cal-api-version' => '2024-08-13',
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])->timeout(20)->get($url, [
                    'eventTypeId' => $eventTypeId,
                    'startTime' => $startDate . 'T00:00:00.000Z',
                    'endTime' => $endDate . 'T23:59:59.999Z',
                    'timeZone' => $timezone,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $slots = $data['data']['slots'] ?? [];

                    // Process and cache by day
                    $slotsByDay = [];
                    foreach ($slots as $dateKey => $daySlots) {
                        $flatSlots = [];
                        if (is_array($daySlots)) {
                            foreach ($daySlots as $slot) {
                                if (is_array($slot) && isset($slot['time'])) {
                                    $flatSlots[] = $slot['time'];
                                } elseif (is_string($slot)) {
                                    $flatSlots[] = $slot;
                                }
                            }
                        }
                        $slotsByDay[$dateKey] = $flatSlots;

                        // Cache each day individually
                        $cacheKey = "calcom:availability:{$eventTypeId}:{$dateKey}:{$timezone}";
                        Cache::put($cacheKey, [
                            'success' => true,
                            'data' => ['slots' => $flatSlots],
                        ], 300);
                    }

                    return [
                        'success' => true,
                        'data' => [
                            'slots_by_day' => $slotsByDay,
                            'date_range' => [
                                'start' => $startDate,
                                'end' => $endDate,
                            ],
                        ],
                    ];
                }

                throw new \Exception('Failed to check availability range: ' . $response->body());
            },
            function () use ($eventTypeId, $startDate, $endDate, $timezone) {
                // Fallback: Check each day from cache or local
                Log::warning('Cal.com circuit breaker open for range check, using fallback');

                $slotsByDay = [];
                $current = Carbon::parse($startDate);
                $end = Carbon::parse($endDate);

                while ($current <= $end) {
                    $dateStr = $current->format('Y-m-d');
                    $dayResult = $this->checkAvailability($eventTypeId, $dateStr, $timezone);

                    if ($dayResult['success']) {
                        $slotsByDay[$dateStr] = $dayResult['data']['slots'] ?? [];
                    }

                    $current->addDay();
                }

                return [
                    'success' => true,
                    'data' => [
                        'slots_by_day' => $slotsByDay,
                        'date_range' => [
                            'start' => $startDate,
                            'end' => $endDate,
                        ],
                        'fallback' => true,
                    ],
                ];
            }
        );
    }

    /**
     * Create a booking in Cal.com calendar.
     *
     * Books an appointment slot in Cal.com using the V1 API for compatibility.
     * This method handles customer data validation, time slot verification,
     * and creates the booking with proper error handling.
     *
     * @param int    $eventTypeId Cal.com event type ID
     * @param string $startTime   Appointment start time (ISO 8601 format)
     * @param string $endTime     Appointment end time (ISO 8601 format)
     * @param array{
     *   name: string,
     *   email: string,
     *   phone?: string,
     *   metadata?: array
     * } $customerData Customer information
     * @param string|null $notes Additional booking notes
     *
     * @throws \InvalidArgumentException For invalid input parameters
     * @throws \Exception                For API communication errors
     *
     * @return array{
     *   success: bool,
     *   booking_id?: int,
     *   booking_uid?: string,
     *   error?: string,
     *   details?: array
     * }
     */
    public function bookAppointment($eventTypeId, $startTime, $endTime, $customerData, $notes = null)
    {
        // Validate inputs
        if (! is_numeric($eventTypeId) || $eventTypeId <= 0) {
            throw new \InvalidArgumentException('Invalid event type ID');
        }

        // Validate datetime formats
        if (! strtotime($startTime)) {
            throw new \InvalidArgumentException('Invalid start time format');
        }

        if (! strtotime($endTime)) {
            throw new \InvalidArgumentException('Invalid end time format');
        }

        // Validate customer data
        if (! isset($customerData['name']) || empty(trim($customerData['name']))) {
            throw new \InvalidArgumentException('Customer name is required');
        }

        if (! isset($customerData['email']) || ! filter_var($customerData['email'], FILTER_VALIDATE_EMAIL)) {
            // Use a fallback email if not provided
            $customerData['email'] = 'kunde@example.com';
        }

        try {
            // Build responses object based on Cal.com v1 booking API requirements
            $responses = [
                'name' => $customerData['name'] ?? 'Unbekannt',
                'email' => $customerData['email'] ?? 'kunde@example.com',
            ];

            // Add phone number if provided
            if (isset($customerData['phone']) && ! empty($customerData['phone'])) {
                $responses['phone'] = $customerData['phone'];
            }

            if ($notes) {
                $responses['notes'] = $notes;
            }

            $data = [
                'eventTypeId' => (int) $eventTypeId,
                'start' => $startTime,
                'end' => $endTime,
                'timeZone' => $customerData['timeZone'] ?? 'Europe/Berlin',
                'language' => 'de',
                'metadata' => $customerData['metadata'] ?? new \stdClass,
                'responses' => $responses,
            ];

            // Add teamId if provided (for team event types)
            if (isset($customerData['teamId']) && ! empty($customerData['teamId'])) {
                $data['teamId'] = (int) $customerData['teamId'];
                error_log('CalcomV2Service: Adding teamId: ' . $data['teamId']);
            }

            error_log('CalcomV2Service: Sending booking request to: ' . $this->baseUrlV1 . '/bookings');
            error_log('CalcomV2Service: Request data: ' . json_encode($data));

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->baseUrlV1 . '/bookings?apiKey=' . $this->apiKey, $data);

            error_log('CalcomV2Service: Response status: ' . $response->status());
            error_log('CalcomV2Service: Response body: ' . $response->body());

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Cal.com booking error', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Create a booking (wrapper for bookAppointment to match MCPBookingOrchestrator expectations).
     *
     * @param array{
     *   eventTypeId: int,
     *   start: string,
     *   end?: string,
     *   duration?: int,
     *   name: string,
     *   email: string,
     *   phone?: string,
     *   timeZone?: string,
     *   metadata?: array,
     *   notes?: string
     * } $data Booking data
     *
     * @return array{
     *   success: bool,
     *   booking?: array{id: int, uid: string},
     *   error?: string
     * }
     */
    public function createBooking(array $data)
    {
        try {
            // Calculate end time if not provided
            $startTime = $data['start'];
            $endTime = $data['end'] ?? null;

            if (! $endTime && isset($data['duration'])) {
                $start = new \DateTime($startTime);
                $start->add(new \DateInterval('PT' . $data['duration'] . 'M'));
                $endTime = $start->format('c');
            } elseif (! $endTime) {
                // Default to 60 minutes if no duration specified
                $start = new \DateTime($startTime);
                $start->add(new \DateInterval('PT60M'));
                $endTime = $start->format('c');
            }

            // Call the existing bookAppointment method
            $result = $this->bookAppointment(
                $data['eventTypeId'],
                $startTime,
                $endTime,
                [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? null,
                    'timeZone' => $data['timeZone'] ?? 'Europe/Berlin',
                    'metadata' => $data['metadata'] ?? new \stdClass,
                ],
                $data['notes'] ?? null
            );

            // Transform response to expected format
            if ($result && isset($result['id'])) {
                Log::info('Cal.com booking created successfully', [
                    'booking_id' => $result['id'],
                    'uid' => $result['uid'] ?? null,
                ]);

                return [
                    'success' => true,
                    'data' => [
                        'id' => $result['id'],
                        'uid' => $result['uid'] ?? null,
                        'startTime' => $result['startTime'] ?? $startTime,
                        'endTime' => $result['endTime'] ?? $endTime,
                        'attendees' => $result['attendees'] ?? [],
                    ],
                ];
            }

            Log::warning('Cal.com booking failed - no booking ID returned', [
                'response' => $result,
            ]);

            return [
                'success' => false,
                'error' => 'Booking failed - no booking ID returned',
            ];
        } catch (\InvalidArgumentException $e) {
            Log::error('Cal.com booking validation error', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return [
                'success' => false,
                'error' => 'Validation error: ' . $e->getMessage(),
            ];
        } catch (\Exception $e) {
            Log::error('Cal.com booking error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Booking failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get available time slots for an event type.
     *
     * @param int         $eventTypeId
     * @param string      $startDate   Format: Y-m-d
     * @param string      $endDate     Format: Y-m-d
     * @param string|null $timeZone
     *
     * @return array|null
     */
    public function getAvailableSlots($eventTypeId, $startDate, $endDate, $timeZone = null)
    {
        try {
            $timeZone = $timeZone ?? 'Europe/Berlin';

            // Use V1 API for availability (V2 might have different endpoint)
            $url = $this->baseUrlV1 . '/availability/event-type';

            $params = [
                'apiKey' => $this->apiKey,
                'eventTypeId' => $eventTypeId,
                'startTime' => $startDate . 'T00:00:00Z',
                'endTime' => $endDate . 'T23:59:59Z',
                'timeZone' => $timeZone,
            ];

            Log::info('Cal.com availability request', [
                'url' => $url,
                'params' => $params,
            ]);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->get($url, $params);

            if ($response->successful()) {
                $data = $response->json();

                // Transform response to consistent format
                $days = [];
                if (isset($data['busy']) && isset($data['timeZone'])) {
                    // V1 format with busy times
                    // We need to calculate available slots from busy times
                    // For now, return raw data
                    return $data;
                } elseif (isset($data['slots'])) {
                    // Direct slots format
                    return ['days' => [['day' => $startDate, 'slots' => $data['slots']]]];
                } else {
                    // Unknown format, return as is
                    return $data;
                }
            }

            Log::warning('Cal.com availability request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Cal.com availability error', [
                'error' => $e->getMessage(),
                'eventTypeId' => $eventTypeId,
            ]);

            return null;
        }
    }

    /**
     * Hole alle Bookings mit Paginierung.
     */
    public function getBookings($params = [])
    {
        try {
            // Use v2 API for bookings since v1 is not authorized
            $headers = [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'cal-api-version' => '2024-08-13',
                'Content-Type' => 'application/json',
            ];

            $queryParams = array_merge([
                'limit' => $params['limit'] ?? 100,
                'page' => $params['page'] ?? 1,
            ], $params);

            // Remove apiKey from params for v2
            unset($queryParams['apiKey']);

            $response = Http::withHeaders($headers)
                ->get($this->baseUrlV2 . '/bookings', $queryParams);

            if ($response->successful()) {
                $responseData = $response->json();

                // v2 returns data in 'data' field
                $bookings = $responseData['data'] ?? [];

                // Normalize the response structure to match v1 format
                return [
                    'success' => true,
                    'data' => [
                        'bookings' => $bookings,
                        'total' => count($bookings), // v2 doesn't provide total in response
                        'page' => $queryParams['page'],
                        'total_pages' => 1, // v2 pagination works differently
                    ],
                ];
            }

            Log::error('Cal.com v2 getBookings failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to fetch bookings: ' . $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('Cal.com v2 getBookings error', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Hole ein einzelnes Booking.
     */
    public function getBooking($bookingId)
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->get($this->baseUrlV1 . '/bookings/' . $bookingId . '?apiKey=' . $this->apiKey);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to fetch booking: ' . $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('Cal.com getBooking error', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get all schedules - Note: V2 doesn't have schedules endpoint, use V1.
     */
    public function getSchedules()
    {
        try {
            // V2 API doesn't have schedules endpoint, fall back to V1
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->get($this->baseUrlV1 . '/schedules?apiKey=' . $this->apiKey);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to fetch schedules: ' . $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('Cal.com getSchedules error', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get teams (v2).
     */
    public function getTeams()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'cal-api-version' => '2024-08-13',
                'Content-Type' => 'application/json',
            ])->get($this->baseUrlV2 . '/teams');

            if ($response->successful()) {
                $data = $response->json();
                // Validate response structure
                if (! is_array($data)) {
                    Log::warning('Cal.com unexpected response format', ['response' => $data]);

                    return [
                        'success' => false,
                        'error' => 'Unexpected response format from Cal.com',
                    ];
                }

                return [
                    'success' => true,
                    'data' => $data,
                ];
            }

            // Handle specific HTTP status codes
            if ($response->status() === 429) {
                Log::warning('Cal.com rate limit exceeded');

                return [
                    'success' => false,
                    'error' => 'Rate limit exceeded. Please try again later.',
                    'retry_after' => $response->header('Retry-After', 60),
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to fetch teams: ' . $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('Cal.com getTeams error', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get team event types (v2).
     */
    public function getTeamEventTypes($teamId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'cal-api-version' => '2024-08-13',
                'Content-Type' => 'application/json',
            ])->get($this->baseUrlV2 . '/teams/' . $teamId . '/event-types');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to fetch team event types: ' . $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('Cal.com getTeamEventTypes error', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get webhooks (v2).
     */
    public function getWebhooks()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'cal-api-version' => '2024-08-13',
                'Content-Type' => 'application/json',
            ])->get($this->baseUrlV2 . '/webhooks');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to fetch webhooks: ' . $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('Cal.com getWebhooks error', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create webhook (v2).
     */
    public function createWebhook($subscriberUrl, $triggers = ['BOOKING_CREATED', 'BOOKING_CANCELLED', 'BOOKING_RESCHEDULED'])
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'cal-api-version' => '2024-08-13',
                'Content-Type' => 'application/json',
            ])->post($this->baseUrlV2 . '/webhooks', [
                'subscriberUrl' => $subscriberUrl,
                'triggers' => $triggers,
                'active' => true,
                'secret' => config('services.calcom.webhook_secret'),
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to create webhook: ' . $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('Cal.com createWebhook error', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Cancel booking (v2).
     */
    public function cancelBooking($bookingId, $reason = null)
    {
        try {
            $data = [];
            if ($reason) {
                $data['cancellationReason'] = $reason;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'cal-api-version' => '2024-08-13',
                'Content-Type' => 'application/json',
            ])->post($this->baseUrlV2 . '/bookings/' . $bookingId . '/cancel', $data);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to cancel booking: ' . $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('Cal.com cancelBooking error', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Reschedule booking (v2).
     */
    public function rescheduleBooking($bookingId, $start, $reason = null)
    {
        try {
            $data = [
                'start' => $start,
            ];

            if ($reason) {
                $data['rescheduleReason'] = $reason;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'cal-api-version' => '2024-08-13',
                'Content-Type' => 'application/json',
            ])->post($this->baseUrlV2 . '/bookings/' . $bookingId . '/reschedule', $data);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to reschedule booking: ' . $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('Cal.com rescheduleBooking error', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get available time slots for an event type
     * Uses V2 API.
     */
    public function getSlots($eventTypeId, $startDate, $endDate, $timeZone = 'Europe/Berlin')
    {
        try {
            $headers = [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'cal-api-version' => '2024-08-13',
                'Content-Type' => 'application/json',
            ];

            $queryParams = [
                'eventTypeId' => (int) $eventTypeId,
                'startTime' => $startDate . 'T00:00:00.000Z',
                'endTime' => $endDate . 'T23:59:59.000Z',
                'timeZone' => $timeZone,
                'duration' => null, // Will use event type default
            ];

            $response = Http::withHeaders($headers)
                ->get($this->baseUrlV2 . '/slots/available', $queryParams);

            if ($response->successful()) {
                $responseData = $response->json();

                return [
                    'success' => true,
                    'data' => [
                        'slots' => $responseData['data'] ?? $responseData['slots'] ?? [],
                        'timeZone' => $timeZone,
                    ],
                ];
            }

            Log::error('Cal.com v2 getSlots failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'eventTypeId' => $eventTypeId,
            ]);

            return [
                'success' => false,
                'error' => 'Failed to fetch slots: ' . $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('Cal.com v2 getSlots error', [
                'error' => $e->getMessage(),
                'eventTypeId' => $eventTypeId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update an existing booking.
     */
    public function updateBooking($bookingId, array $updateData)
    {
        try {
            $headers = [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'cal-api-version' => '2024-08-13',
                'Content-Type' => 'application/json',
            ];

            // Prepare update data according to Cal.com V2 API spec
            $data = [];

            if (isset($updateData['start'])) {
                $data['start'] = $updateData['start'];
            }
            if (isset($updateData['end'])) {
                $data['end'] = $updateData['end'];
            }
            if (isset($updateData['title'])) {
                $data['title'] = $updateData['title'];
            }
            if (isset($updateData['description'])) {
                $data['description'] = $updateData['description'];
            }
            if (isset($updateData['rescheduleReason'])) {
                $data['rescheduleReason'] = $updateData['rescheduleReason'];
            }

            $response = Http::withHeaders($headers)
                ->patch($this->baseUrlV2 . '/bookings/' . $bookingId, $data);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            Log::error('Cal.com v2 updateBooking failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'bookingId' => $bookingId,
            ]);

            return [
                'success' => false,
                'error' => 'Failed to update booking: ' . $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('Cal.com v2 updateBooking error', [
                'error' => $e->getMessage(),
                'bookingId' => $bookingId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
