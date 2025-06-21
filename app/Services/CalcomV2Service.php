<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\Traits\RetryableHttpClient;
use App\Services\CircuitBreaker\CircuitBreaker;
use App\Services\Logging\ProductionLogger;
use App\Services\RateLimiter\ApiRateLimiter;

class CalcomV2Service
{
    use RetryableHttpClient;
    
    private $apiKey;
    private $baseUrlV1 = 'https://api.cal.com/v1';
    private $baseUrlV2;
    private CircuitBreaker $circuitBreaker;
    private ProductionLogger $logger;
    private ApiRateLimiter $rateLimiter;
    
    /**
     * Make a safe API request with validation
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
                    'retry_after' => $retryAfter
                ]);
                
                return [
                    'success' => false,
                    'error' => 'Rate limit exceeded',
                    'retry_after' => $retryAfter,
                    'code' => 429
                ];
            }
            
            // Handle success
            if ($response->successful()) {
                $responseData = $response->json();
                
                // Validate response is valid JSON
                if ($responseData === null && $response->body() !== 'null') {
                    Log::error('Cal.com API returned invalid JSON', [
                        'url' => $url,
                        'response' => substr($response->body(), 0, 500)
                    ]);
                    
                    return [
                        'success' => false,
                        'error' => 'Invalid response format'
                    ];
                }
                
                return [
                    'success' => true,
                    'data' => $responseData
                ];
            }
            
            // Handle errors
            return [
                'success' => false,
                'error' => $response->body(),
                'code' => $response->status()
            ];
            
        } catch (\Exception $e) {
            Log::error('Cal.com API request failed', [
                'url' => $url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'exception' => true
            ];
        }
    }
    private array $config;

    public function __construct($apiKey = null)
    {
        // Load configuration
        $this->config = config('calcom-v2');
        
        // Set API key (priority: parameter > config > fallback)
        $this->apiKey = $apiKey ?? $this->config['api_key'] ?? config('services.calcom.api_key');
        
        // Set base URL from config
        $this->baseUrlV2 = $this->config['api_url'] ?? 'https://api.cal.com/v2';
        
        // Initialize circuit breaker with config
        $this->circuitBreaker = new CircuitBreaker(
            (int)($this->config['circuit_breaker']['failure_threshold'] ?? 5),
            (int)($this->config['circuit_breaker']['success_threshold'] ?? 2),
            (int)($this->config['circuit_breaker']['timeout_seconds'] ?? 60),
            3
        );
        
        $this->logger = new ProductionLogger();
        $this->rateLimiter = new ApiRateLimiter();
    }
    
    /**
     * Get current user info (v2)
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
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to fetch user info: ' . $response->body()
            ];
            
        } catch (\Exception $e) {
            Log::error('Cal.com getMe error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * V1 API für Users nutzen
     */
    public function getUsers()
    {
        return $this->circuitBreaker->call('calcom', function() {
            $url = $this->baseUrlV1 . '/users';
            
            $response = $this->httpWithRetry()
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->get($url, ['apiKey' => $this->apiKey]);

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception("Cal.com getUsers failed with status: " . $response->status());
        }, function() {
            // Fallback: Return cached data or empty array
            $this->logger->logError(new \Exception('Cal.com circuit open, using fallback'), [
                'method' => 'getUsers',
                'fallback' => 'empty_array'
            ]);
            return ['users' => []];
        });
    }

    /**
     * V1 API für Event-Types nutzen
     */
    public function getEventTypes()
    {
        return $this->circuitBreaker->call('calcom', function() {
            $url = $this->baseUrlV1 . '/event-types';
            
            $response = $this->httpWithRetry()
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->get($url, ['apiKey' => $this->apiKey]);

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception("Cal.com getEventTypes failed with status: " . $response->status());
        }, function() {
            // Fallback: Return cached event types if available
            $cached = \Cache::get('calcom_event_types_' . md5($this->apiKey), []);
            $this->logger->logError(new \Exception('Cal.com circuit open, using cached data'), [
                'method' => 'getEventTypes',
                'cached_count' => count($cached)
            ]);
            return ['event_types' => $cached];
        });
    }

    /**
     * V2 API für Verfügbarkeiten - mit korrektem Slot-Flattening
     */
    public function checkAvailability($eventTypeId, $date, $timezone = 'Europe/Berlin')
    {
        // Validate inputs
        if (!is_numeric($eventTypeId) || $eventTypeId <= 0) {
            throw new \InvalidArgumentException('Invalid event type ID');
        }
        
        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new \InvalidArgumentException('Invalid date format. Expected: YYYY-MM-DD');
        }
        
        // Validate timezone
        if (!in_array($timezone, timezone_identifiers_list())) {
            throw new \InvalidArgumentException('Invalid timezone');
        }
        
        try {
            // Check rate limit
            $this->rateLimiter->attempt('calcom', $this->apiKey);
            $url = $this->baseUrlV2 . '/slots/available';
            
            $response = Http::withHeaders([
                'cal-api-version' => '2024-08-13',
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->get($url, [
                'eventTypeId' => $eventTypeId,
                'startTime' => $date . 'T00:00:00.000Z',
                'endTime' => $date . 'T23:59:59.999Z',
                'timeZone' => $timezone
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
                
                return [
                    'success' => true,
                    'data' => [
                        'slots' => $flatSlots,
                        'raw_slots' => $slots // Keep original structure for debugging
                    ]
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to check availability: ' . $response->body()
            ];
        } catch (\Exception $e) {
            Log::error('Cal.com availability check error', [
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * V1 API für Buchungen (wie im CalcomService)
     */
    public function bookAppointment($eventTypeId, $startTime, $endTime, $customerData, $notes = null)
    {
        // Validate inputs
        if (!is_numeric($eventTypeId) || $eventTypeId <= 0) {
            throw new \InvalidArgumentException('Invalid event type ID');
        }
        
        // Validate datetime formats
        if (!strtotime($startTime)) {
            throw new \InvalidArgumentException('Invalid start time format');
        }
        
        if (!strtotime($endTime)) {
            throw new \InvalidArgumentException('Invalid end time format');
        }
        
        // Validate customer data
        if (!isset($customerData['name']) || empty(trim($customerData['name']))) {
            throw new \InvalidArgumentException('Customer name is required');
        }
        
        if (!isset($customerData['email']) || !filter_var($customerData['email'], FILTER_VALIDATE_EMAIL)) {
            // Use a fallback email if not provided
            $customerData['email'] = 'kunde@example.com';
        }
        
        try {
            // Build responses object based on Cal.com v1 booking API requirements
            $responses = [
                'name' => $customerData['name'] ?? 'Unbekannt',
                'email' => $customerData['email'] ?? 'kunde@example.com'
            ];

            // Add phone number if provided
            if (isset($customerData['phone']) && !empty($customerData['phone'])) {
                $responses['phone'] = $customerData['phone'];
            }

            if ($notes) {
                $responses['notes'] = $notes;
            }

            $data = [
                'eventTypeId' => (int)$eventTypeId,
                'start' => $startTime,
                'end' => $endTime,
                'timeZone' => $customerData['timeZone'] ?? 'Europe/Berlin',
                'language' => 'de',
                'metadata' => $customerData['metadata'] ?? [],
                'responses' => $responses
            ];

            // Add teamId if provided (for team event types)
            if (isset($customerData['teamId']) && !empty($customerData['teamId'])) {
                $data['teamId'] = (int)$customerData['teamId'];
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
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Hole alle Bookings mit Paginierung
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
                'page' => $params['page'] ?? 1
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
                        'total_pages' => 1 // v2 pagination works differently
                    ]
                ];
            }

            Log::error('Cal.com v2 getBookings failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to fetch bookings: ' . $response->body()
            ];
            
        } catch (\Exception $e) {
            Log::error('Cal.com v2 getBookings error', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Hole ein einzelnes Booking
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
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to fetch booking: ' . $response->body()
            ];
            
        } catch (\Exception $e) {
            Log::error('Cal.com getBooking error', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get all schedules - Note: V2 doesn't have schedules endpoint, use V1
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
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to fetch schedules: ' . $response->body()
            ];
            
        } catch (\Exception $e) {
            Log::error('Cal.com getSchedules error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get teams (v2)
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
                if (!is_array($data)) {
                    Log::warning('Cal.com unexpected response format', ['response' => $data]);
                    return [
                        'success' => false,
                        'error' => 'Unexpected response format from Cal.com'
                    ];
                }
                
                return [
                    'success' => true,
                    'data' => $data
                ];
            }

            // Handle specific HTTP status codes
            if ($response->status() === 429) {
                Log::warning('Cal.com rate limit exceeded');
                return [
                    'success' => false,
                    'error' => 'Rate limit exceeded. Please try again later.',
                    'retry_after' => $response->header('Retry-After', 60)
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to fetch teams: ' . $response->body()
            ];
            
        } catch (\Exception $e) {
            Log::error('Cal.com getTeams error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get team event types (v2)
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
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to fetch team event types: ' . $response->body()
            ];
            
        } catch (\Exception $e) {
            Log::error('Cal.com getTeamEventTypes error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get webhooks (v2)
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
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to fetch webhooks: ' . $response->body()
            ];
            
        } catch (\Exception $e) {
            Log::error('Cal.com getWebhooks error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Create webhook (v2)
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
                'secret' => config('services.calcom.webhook_secret')
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to create webhook: ' . $response->body()
            ];
            
        } catch (\Exception $e) {
            Log::error('Cal.com createWebhook error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get event type with full details - Note: V2 doesn't have this endpoint, use V1
     */
    public function getEventTypeDetails($eventTypeId)
    {
        try {
            // V2 API doesn't have single event-type endpoint, use V1
            // First try to get from the list of event types
            $eventTypes = $this->getEventTypes();
            
            if (isset($eventTypes['event_types'])) {
                foreach ($eventTypes['event_types'] as $eventType) {
                    if ($eventType['id'] == $eventTypeId) {
                        return [
                            'success' => true,
                            'data' => $eventType
                        ];
                    }
                }
            }
            
            return [
                'success' => false,
                'error' => 'Event type not found'
            ];
            
        } catch (\Exception $e) {
            Log::error('Cal.com getEventTypeDetails error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Cancel booking (v2)
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
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to cancel booking: ' . $response->body()
            ];
            
        } catch (\Exception $e) {
            Log::error('Cal.com cancelBooking error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Reschedule booking (v2)
     */
    public function rescheduleBooking($bookingId, $start, $reason = null)
    {
        try {
            $data = [
                'start' => $start
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
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to reschedule booking: ' . $response->body()
            ];
            
        } catch (\Exception $e) {
            Log::error('Cal.com rescheduleBooking error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get available time slots for an event type
     * Uses V2 API
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
                'eventTypeId' => (int)$eventTypeId,
                'startTime' => $startDate . 'T00:00:00.000Z',
                'endTime' => $endDate . 'T23:59:59.000Z',
                'timeZone' => $timeZone,
                'duration' => null // Will use event type default
            ];
            
            $response = Http::withHeaders($headers)
                ->get($this->baseUrlV2 . '/slots/available', $queryParams);

            if ($response->successful()) {
                $responseData = $response->json();
                
                return [
                    'success' => true,
                    'data' => [
                        'slots' => $responseData['data'] ?? $responseData['slots'] ?? [],
                        'timeZone' => $timeZone
                    ]
                ];
            }

            Log::error('Cal.com v2 getSlots failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'eventTypeId' => $eventTypeId
            ]);

            return [
                'success' => false,
                'error' => 'Failed to fetch slots: ' . $response->body()
            ];
            
        } catch (\Exception $e) {
            Log::error('Cal.com v2 getSlots error', [
                'error' => $e->getMessage(),
                'eventTypeId' => $eventTypeId
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Update an existing booking
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
                    'data' => $response->json()
                ];
            }
            
            Log::error('Cal.com v2 updateBooking failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'bookingId' => $bookingId
            ]);
            
            return [
                'success' => false,
                'error' => 'Failed to update booking: ' . $response->body()
            ];
            
        } catch (\Exception $e) {
            Log::error('Cal.com v2 updateBooking error', [
                'error' => $e->getMessage(),
                'bookingId' => $bookingId
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
