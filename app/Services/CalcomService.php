<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

class CalcomService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected ?string $eventTypeId;
    protected int $maxRetries;
    protected string $timezone;
    protected string $language;
    protected bool $useV2;

    public function __construct()
    {
        $this->baseUrl     = rtrim(config('services.calcom.base_url', 'https://api.cal.com/v1'), '/');
        $this->apiKey      = config('services.calcom.api_key', '');
        $this->eventTypeId = config('services.calcom.event_type_id');
        $this->maxRetries  = config('services.calcom.max_retries', 3);
        $this->timezone    = config('services.calcom.timezone', 'Europe/Berlin');
        $this->language    = config('services.calcom.language', 'de');
        
        // Determine API version from base URL
        $this->useV2 = str_contains($this->baseUrl, '/v2');
        
        // Log deprecation warning for V1
        if (!$this->useV2 && config('services.calcom.hybrid_mode')) {
            Log::warning('[CalcomService] ⚠️ Using Cal.com V1 API for event types. V1 will be deprecated end of 2025. Using hybrid mode: V1 for event types, V2 for bookings.');
        }
        
        // Validate required configuration
        if (empty($this->apiKey)) {
            Log::warning('[CalcomService] API key not configured. Service will not work properly.');
            // Don't throw exception to allow app to function without Cal.com
        }
    }

    /**
     * Get event type details from Cal.com
     */
    public function getEventType(int $eventTypeId): array
    {
        // For V1, we need to get all event types and filter
        // Direct event type access often requires team context
        try {
            // First try direct access
            $url = $this->baseUrl . "/event-types/{$eventTypeId}";
            $response = $this->makeApiCall('GET', $url);
            
            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::warning("[CalcomService] Direct event type access failed, trying list approach", [
                'event_type_id' => $eventTypeId,
                'error' => $e->getMessage()
            ]);
        }
        
        // Fallback: Get all event types and filter
        try {
            $username = config('services.calcom.username', 'askproai');
            $url = $this->baseUrl . "/event-types?username={$username}";
            
            $response = $this->makeApiCall('GET', $url);
            
            if (!$response->successful()) {
                throw new \Exception("Failed to fetch event types: " . $response->body());
            }
            
            $result = $response->json();
            
            // Handle different response formats
            $eventTypes = isset($result['event_types']) ? $result['event_types'] : 
                         (isset($result['data']) ? $result['data'] : $result);
            
            // If it's a single object instead of array, convert it
            if (isset($eventTypes['id'])) {
                $eventTypes = [$eventTypes];
            }
            
            // Find the specific event type
            if (is_array($eventTypes)) {
                foreach ($eventTypes as $eventType) {
                    if (isset($eventType['id']) && $eventType['id'] == $eventTypeId) {
                        return $eventType;
                    }
                }
            }
            
            // If not found, log what we received
            Log::warning("[CalcomService] Event type {$eventTypeId} not found. Response structure:", [
                'has_event_types' => isset($result['event_types']),
                'has_data' => isset($result['data']),
                'is_array' => is_array($result),
                'keys' => is_array($result) ? array_keys($result) : 'not_array'
            ]);
            
            throw new \Exception("Event type {$eventTypeId} not found in available event types");
            
        } catch (\Exception $e) {
            throw new \Exception("Failed to fetch event type: " . $e->getMessage());
        }
    }

    /**
     * Create booking with automatic event type lookup and end time calculation
     */
    public function createBookingFromCall(array $requestData): array
    {
        try {
            // Get event type details to determine duration
            $eventTypeInfo = $this->getEventType($requestData['eventTypeId']);
            $duration = $eventTypeInfo['length'] ?? 30;

            // Calculate end time
            $startTime = new \DateTime($requestData['start']);
            $endTime = clone $startTime;
            $endTime->modify("+{$duration} minutes");

            // Prepare booking payload
            $bookingData = [
                'eventTypeId' => (int)$requestData['eventTypeId'],
                'start' => $requestData['start'],
                'end' => $endTime->format('Y-m-d\TH:i:s.000\Z'),
                'attendees' => [
                    [
                        'email' => $requestData['email'],
                        'name' => $requestData['name'],
                        'timeZone' => $this->timezone,
                        'language' => $this->language
                    ]
                ],
                'timeZone' => $this->timezone,
                'language' => $this->language
            ];

            Log::info('[CalcomService] Creating booking', [
                'event_type_id' => $bookingData['eventTypeId'],
                'attendee_count' => count($bookingData['attendees']),
                'timezone' => $bookingData['timeZone']
            ]);

            // Make booking request
            $response = $this->makeApiCall('POST', $this->baseUrl . '/bookings', $bookingData);

            if (!$response->successful()) {
                Log::error('[CalcomService] Booking failed:', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                
                throw new \Exception("Failed to create booking: " . $response->body());
            }

            $bookingResult = $response->json();
            Log::info('[CalcomService] Booking created successfully', [
                'booking_id' => $bookingResult['id'] ?? 'unknown',
                'status' => $bookingResult['status'] ?? 'unknown'
            ]);

            return $bookingResult;

        } catch (\Exception $e) {
            Log::error('[CalcomService] Exception during booking:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Check availability for a given event type
     * V1 API Implementation - requires username or userId
     */
    public function checkAvailability(array $params): array
    {
        try {
            // V1 requires either username or userId
            $queryParams = [
                'dateFrom' => $params['dateFrom'],
                'dateTo' => $params['dateTo'],
                'eventTypeId' => $params['eventTypeId']
            ];
            
            // Add username or userId (V1 requires one of these)
            if (!empty($params['userId'])) {
                $queryParams['userId'] = $params['userId'];
            } else {
                // Use configured username as fallback
                $queryParams['username'] = config('services.calcom.username', 'askproai');
            }
            
            $url = $this->baseUrl . '/availability?' . http_build_query($queryParams);

            Log::info('[CalcomService] Checking availability', [
                'event_type_id' => $params['eventTypeId'],
                'date_from' => $params['dateFrom'],
                'date_to' => $params['dateTo']
            ]);

            $response = $this->makeApiCall('GET', $url);

            if (!$response->successful()) {
                Log::error('[CalcomService] Availability check failed:', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                
                throw new \Exception("Failed to check availability: " . $response->body());
            }

            $availabilityData = $response->json();
            
            Log::info('[CalcomService] Availability check successful', [
                'slots_count' => count($availabilityData['slots'] ?? [])
            ]);

            return $availabilityData;

        } catch (\Exception $e) {
            Log::error('[CalcomService] Exception during availability check:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Legacy method for backward compatibility
     */
    public function createBooking(array $bookingDetails): Response
    {
        $payload = [
            'eventTypeId' => (int)($bookingDetails['eventTypeId'] ?? $this->eventTypeId),
            'start'       => $bookingDetails['startTime'],
            'end'         => $bookingDetails['endTime'],
            'timeZone'    => $bookingDetails['timeZone'] ?? $this->timezone,
            'language'    => $bookingDetails['language'] ?? $this->language,
            'metadata'    => (object)[],
            'responses'   => [
                'name'  => $bookingDetails['name'],
                'email' => $bookingDetails['email'],
            ],
        ];

        Log::channel('calcom')->debug('[Cal.com] Creating booking', [
            'event_type_id' => $payload['eventTypeId'],
            'timezone' => $payload['timeZone'],
            'has_responses' => !empty($payload['responses'])
        ]);

        return $this->makeApiCall('POST', $this->baseUrl . '/bookings', $payload);
    }

    /**
     * Make API call with retry logic - Supports both V1 and V2
     */
    private function makeApiCall(string $method, string $url, array $data = null): Response
    {
        $attempt = 0;
        $lastResponse = null;

        // Check if API key is configured
        if (empty($this->apiKey)) {
            throw new \Exception('Cal.com API key is not configured. Please set CALCOM_API_KEY in .env');
        }

        $apiVersion = $this->useV2 ? 'V2' : 'V1';
        Log::debug("[CalcomService] Preparing {$apiVersion} API call: {$method} {$url}");

        while ($attempt < $this->maxRetries) {
            $attempt++;

            try {
                Log::debug("[CalcomService] API Call (attempt {$attempt}): {$method} {$url}");

                if ($this->useV2) {
                    // V2: Bearer authentication with headers
                    $http = Http::acceptJson()
                        ->timeout(30)
                        ->withHeaders([
                            'Authorization' => 'Bearer ' . $this->apiKey,
                            'cal-api-version' => '2024-08-13',
                            'Content-Type' => 'application/json'
                        ]);
                    
                    $finalUrl = $url;
                } else {
                    // V1: Query parameter authentication
                    $http = Http::acceptJson()->timeout(30);
                    
                    // Add API key to URL for V1
                    $separator = str_contains($url, '?') ? '&' : '?';
                    $finalUrl = $url . $separator . 'apiKey=' . $this->apiKey;
                }

                $response = match(strtoupper($method)) {
                    'GET' => $http->get($finalUrl),
                    'POST' => $http->post($finalUrl, $data ?? []),
                    'PUT' => $http->put($finalUrl, $data ?? []),
                    'DELETE' => $http->delete($finalUrl),
                    default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}")
                };

                // If successful, return immediately
                if ($response->successful()) {
                    Log::debug("[CalcomService] {$apiVersion} API Call successful", [
                        'status' => $response->status(),
                        'attempt' => $attempt
                    ]);
                    
                    return $response;
                }

                // Store last response for error handling
                $lastResponse = $response;
                
                // Check for authentication errors
                if ($response->status() === 401) {
                    $body = $response->json();
                    $errorMsg = $body['error']['message'] ?? $body['error'] ?? 'Unauthorized';
                    Log::error("[CalcomService] Authentication failed: {$errorMsg}");
                    Log::error("[CalcomService] Please check your API key in .env file");
                    throw new \Exception("Cal.com authentication failed: {$errorMsg}. Please check your API key.");
                }
                
                Log::warning("[CalcomService] {$apiVersion} API Call failed (attempt {$attempt})", [
                    'status' => $response->status(),
                    'error_type' => $this->getErrorType($response->status())
                ]);

                // Wait between retries (exponential backoff)
                if ($attempt < $this->maxRetries) {
                    $waitTime = pow(2, $attempt - 1) * 100000; // 100ms, 200ms, 400ms...
                    usleep($waitTime);
                }

            } catch (\Exception $e) {
                Log::warning("[CalcomService] {$apiVersion} API Call exception (attempt {$attempt}): " . $e->getMessage());
                
                if ($attempt < $this->maxRetries && !str_contains($e->getMessage(), 'authentication failed')) {
                    $waitTime = pow(2, $attempt - 1) * 100000;
                    usleep($waitTime);
                } else {
                    throw $e;
                }
            }
        }

        // All retries failed, return last response or throw exception
        if ($lastResponse) {
            return $lastResponse;
        }

        throw new \Exception("Cal.com {$apiVersion} API call failed after {$this->maxRetries} attempts");
    }

    /**
     * Get error type from HTTP status for logging
     */
    private function getErrorType(int $status): string
    {
        return match($status) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            422 => 'Validation Error',
            429 => 'Rate Limited',
            500 => 'Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            default => "HTTP {$status}"
        };
    }
}
