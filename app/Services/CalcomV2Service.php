<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

/**
 * Cal.com V2 API Service
 * Handles all V2-specific operations (primarily bookings)
 * V1 will be deprecated end of 2025
 */
class CalcomV2Service
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $apiVersion;
    protected int $maxRetries;
    protected string $timezone;
    protected string $language;
    protected ?int $organizationId;
    protected ?string $username;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.calcom.v2_base_url', 'https://api.cal.com/v2'), '/');
        $this->apiKey = config('services.calcom.api_key', '');
        $this->apiVersion = config('services.calcom.v2_api_version', '2024-08-13');
        $this->maxRetries = config('services.calcom.max_retries', 3);
        $this->timezone = config('services.calcom.timezone', 'Europe/Berlin');
        $this->language = config('services.calcom.language', 'de');
        $this->organizationId = config('services.calcom.organization_id');
        $this->username = config('services.calcom.username');
        
        if (empty($this->apiKey)) {
            Log::warning('[CalcomV2Service] API key not configured');
        }
    }

    /**
     * Create a booking using V2 API
     * 
     * @param array $data Booking data
     * @return array
     * @throws \Exception
     */
    public function createBooking(array $data): array
    {
        try {
            // Build booking payload for V2
            $payload = $this->buildBookingPayload($data);
            
            Log::info('[CalcomV2Service] Creating V2 booking', [
                'event_type_id' => $payload['eventTypeId'] ?? null,
                'start' => $payload['start'] ?? null,
            ]);

            $response = $this->makeApiCall('POST', '/bookings', $payload);

            if (!$response->successful()) {
                Log::error('[CalcomV2Service] Booking failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                
                throw new \Exception("Failed to create booking: " . $this->extractErrorMessage($response));
            }

            $result = $response->json();
            
            if ($result['status'] === 'success') {
                Log::info('[CalcomV2Service] Booking created successfully', [
                    'booking_uid' => $result['data']['uid'] ?? 'unknown',
                    'booking_id' => $result['data']['id'] ?? 'unknown'
                ]);
                
                return $result['data'];
            }
            
            throw new \Exception("Booking failed: " . ($result['error']['message'] ?? 'Unknown error'));

        } catch (\Exception $e) {
            Log::error('[CalcomV2Service] Exception during booking', [
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Cancel a booking
     * 
     * @param string $uid Booking UID
     * @param string $reason Cancellation reason
     * @return array
     * @throws \Exception
     */
    public function cancelBooking(string $uid, string $reason): array
    {
        try {
            Log::info('[CalcomV2Service] Cancelling booking', ['uid' => $uid]);

            $response = $this->makeApiCall('POST', "/bookings/{$uid}/cancel", [
                'cancellationReason' => $reason
            ]);

            if (!$response->successful()) {
                throw new \Exception("Failed to cancel booking: " . $this->extractErrorMessage($response));
            }

            $result = $response->json();
            
            if ($result['status'] === 'success') {
                Log::info('[CalcomV2Service] Booking cancelled successfully', ['uid' => $uid]);
                return $result['data'];
            }
            
            throw new \Exception("Cancellation failed: " . ($result['error']['message'] ?? 'Unknown error'));

        } catch (\Exception $e) {
            Log::error('[CalcomV2Service] Exception during cancellation', [
                'uid' => $uid,
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Reschedule a booking
     * 
     * @param string $uid Booking UID
     * @param array $data New booking data
     * @return array
     * @throws \Exception
     */
    public function rescheduleBooking(string $uid, array $data): array
    {
        try {
            $payload = [
                'start' => $data['start'],
                'reschedulingReason' => $data['reason'] ?? 'Rescheduled by user'
            ];

            Log::info('[CalcomV2Service] Rescheduling booking', [
                'uid' => $uid,
                'new_start' => $data['start']
            ]);

            $response = $this->makeApiCall('POST', "/bookings/{$uid}/reschedule", $payload);

            if (!$response->successful()) {
                throw new \Exception("Failed to reschedule booking: " . $this->extractErrorMessage($response));
            }

            $result = $response->json();
            
            if ($result['status'] === 'success') {
                Log::info('[CalcomV2Service] Booking rescheduled successfully', ['uid' => $uid]);
                return $result['data'];
            }
            
            throw new \Exception("Rescheduling failed: " . ($result['error']['message'] ?? 'Unknown error'));

        } catch (\Exception $e) {
            Log::error('[CalcomV2Service] Exception during rescheduling', [
                'uid' => $uid,
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get a booking by UID
     * 
     * @param string $uid Booking UID
     * @return array
     * @throws \Exception
     */
    public function getBooking(string $uid): array
    {
        try {
            Log::debug('[CalcomV2Service] Fetching booking', ['uid' => $uid]);

            $response = $this->makeApiCall('GET', "/bookings/{$uid}");

            if (!$response->successful()) {
                throw new \Exception("Failed to fetch booking: " . $this->extractErrorMessage($response));
            }

            $result = $response->json();
            
            if ($result['status'] === 'success') {
                return $result['data'];
            }
            
            throw new \Exception("Failed to get booking: " . ($result['error']['message'] ?? 'Unknown error'));

        } catch (\Exception $e) {
            Log::error('[CalcomV2Service] Exception fetching booking', [
                'uid' => $uid,
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get all bookings with optional filters
     * 
     * @param array $filters Optional filters
     * @return array
     * @throws \Exception
     */
    public function getBookings(array $filters = []): array
    {
        try {
            $queryParams = http_build_query($filters);
            $endpoint = '/bookings' . ($queryParams ? '?' . $queryParams : '');

            Log::debug('[CalcomV2Service] Fetching bookings', ['filters' => $filters]);

            $response = $this->makeApiCall('GET', $endpoint);

            if (!$response->successful()) {
                throw new \Exception("Failed to fetch bookings: " . $this->extractErrorMessage($response));
            }

            $result = $response->json();
            
            if ($result['status'] === 'success') {
                return $result['data'];
            }
            
            throw new \Exception("Failed to get bookings: " . ($result['error']['message'] ?? 'Unknown error'));

        } catch (\Exception $e) {
            Log::error('[CalcomV2Service] Exception fetching bookings', [
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Build booking payload for V2 API
     * 
     * @param array $data Input data
     * @return array V2-formatted payload
     */
    protected function buildBookingPayload(array $data): array
    {
        // Required fields
        $payload = [
            'start' => $data['start'] ?? $data['startTime'],
            'attendee' => [
                'name' => $data['name'] ?? $data['attendee']['name'],
                'email' => $data['email'] ?? $data['attendee']['email'],
                'timeZone' => $data['timeZone'] ?? $data['attendee']['timeZone'] ?? $this->timezone,
            ]
        ];

        // Add language if provided
        if (isset($data['language']) || isset($data['attendee']['language'])) {
            $payload['attendee']['language'] = $data['language'] ?? $data['attendee']['language'] ?? $this->language;
        }

        // Add phone number if provided
        if (isset($data['phoneNumber']) || isset($data['attendee']['phoneNumber'])) {
            $payload['attendee']['phoneNumber'] = $data['phoneNumber'] ?? $data['attendee']['phoneNumber'];
        }

        // Event type identification - prefer ID over slug
        if (isset($data['eventTypeId'])) {
            $payload['eventTypeId'] = (int)$data['eventTypeId'];
        } elseif (isset($data['eventTypeSlug']) && $this->username) {
            $payload['eventTypeSlug'] = $data['eventTypeSlug'];
            $payload['username'] = $this->username;
            
            if ($this->organizationId) {
                $payload['organizationSlug'] = $data['organizationSlug'] ?? null;
            }
        }

        // Optional fields
        if (isset($data['guests']) && is_array($data['guests'])) {
            $payload['guests'] = $data['guests'];
        }

        if (isset($data['location'])) {
            $payload['location'] = $data['location'];
        }

        if (isset($data['metadata'])) {
            $payload['metadata'] = $data['metadata'];
        }

        if (isset($data['bookingFieldsResponses'])) {
            $payload['bookingFieldsResponses'] = $data['bookingFieldsResponses'];
        }

        if (isset($data['lengthInMinutes'])) {
            $payload['lengthInMinutes'] = (int)$data['lengthInMinutes'];
        }

        return $payload;
    }

    /**
     * Make API call with retry logic
     * 
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array|null $data Request data
     * @return Response
     * @throws \Exception
     */
    protected function makeApiCall(string $method, string $endpoint, array $data = null): Response
    {
        $attempt = 0;
        $lastResponse = null;
        $url = $this->baseUrl . $endpoint;

        if (empty($this->apiKey)) {
            throw new \Exception('Cal.com API key is not configured');
        }

        while ($attempt < $this->maxRetries) {
            $attempt++;

            try {
                Log::debug("[CalcomV2Service] API Call (attempt {$attempt})", [
                    'method' => $method,
                    'endpoint' => $endpoint
                ]);

                $http = Http::acceptJson()
                    ->timeout(30)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'cal-api-version' => $this->apiVersion,
                        'Content-Type' => 'application/json'
                    ]);

                $response = match(strtoupper($method)) {
                    'GET' => $http->get($url),
                    'POST' => $http->post($url, $data ?? []),
                    'PUT' => $http->put($url, $data ?? []),
                    'PATCH' => $http->patch($url, $data ?? []),
                    'DELETE' => $http->delete($url),
                    default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}")
                };

                // Return immediately on success
                if ($response->successful()) {
                    Log::debug("[CalcomV2Service] API Call successful", [
                        'status' => $response->status(),
                        'attempt' => $attempt
                    ]);
                    return $response;
                }

                $lastResponse = $response;

                // Don't retry on client errors (4xx)
                if ($response->status() >= 400 && $response->status() < 500) {
                    Log::warning("[CalcomV2Service] Client error, not retrying", [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);
                    return $response;
                }

                // Wait between retries for server errors
                if ($attempt < $this->maxRetries) {
                    $waitTime = pow(2, $attempt - 1); // Exponential backoff: 1s, 2s, 4s
                    Log::warning("[CalcomV2Service] Server error, retrying in {$waitTime}s", [
                        'status' => $response->status(),
                        'attempt' => $attempt
                    ]);
                    sleep($waitTime);
                }

            } catch (\Exception $e) {
                Log::warning("[CalcomV2Service] API Call exception (attempt {$attempt}): " . $e->getMessage());
                
                if ($attempt < $this->maxRetries) {
                    $waitTime = pow(2, $attempt - 1);
                    sleep($waitTime);
                } else {
                    throw $e;
                }
            }
        }

        // All retries failed
        if ($lastResponse) {
            return $lastResponse;
        }

        throw new \Exception("API call failed after {$this->maxRetries} attempts");
    }

    /**
     * Extract error message from response
     * 
     * @param Response $response
     * @return string
     */
    protected function extractErrorMessage(Response $response): string
    {
        try {
            $body = $response->json();
            
            // V2 error structure
            if (isset($body['error']['message'])) {
                return $body['error']['message'];
            }
            
            // Alternative error structure
            if (isset($body['message'])) {
                return $body['message'];
            }
            
            // Fallback to raw body
            return $response->body();
        } catch (\Exception $e) {
            return "HTTP {$response->status()}: " . substr($response->body(), 0, 200);
        }
    }

    /**
     * Get error type based on HTTP status
     * 
     * @param int $status
     * @return string
     */
    protected function getErrorType(int $status): string
    {
        return match(true) {
            $status === 401 => 'Authentication Error',
            $status === 403 => 'Authorization Error',
            $status === 404 => 'Not Found',
            $status === 429 => 'Rate Limited',
            $status >= 500 => 'Server Error',
            $status >= 400 => 'Client Error',
            default => 'Unknown Error'
        };
    }

    /**
     * Get all event types
     * 
     * @param array $filters Optional filters
     * @return array
     * @throws \Exception
     */
    public function getEventTypes(array $filters = []): array
    {
        try {
            $queryParams = http_build_query($filters);
            $endpoint = '/event-types' . ($queryParams ? '?' . $queryParams : '');

            Log::debug('[CalcomV2Service] Fetching event types', ['filters' => $filters]);

            $response = $this->makeApiCall('GET', $endpoint);

            if (!$response->successful()) {
                throw new \Exception("Failed to fetch event types: " . $this->extractErrorMessage($response));
            }

            $result = $response->json();
            
            if ($result['status'] === 'success') {
                return $result['data'];
            }
            
            throw new \Exception("Failed to get event types: " . ($result['error']['message'] ?? 'Unknown error'));

        } catch (\Exception $e) {
            Log::error('[CalcomV2Service] Exception fetching event types', [
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get a specific event type by ID
     * 
     * @param int $eventTypeId
     * @return array
     * @throws \Exception
     */
    public function getEventType(int $eventTypeId): array
    {
        try {
            Log::debug('[CalcomV2Service] Fetching event type', ['id' => $eventTypeId]);

            $response = $this->makeApiCall('GET', "/event-types/{$eventTypeId}");

            if (!$response->successful()) {
                throw new \Exception("Failed to fetch event type: " . $this->extractErrorMessage($response));
            }

            $result = $response->json();
            
            if ($result['status'] === 'success') {
                return $result['data'];
            }
            
            throw new \Exception("Failed to get event type: " . ($result['error']['message'] ?? 'Unknown error'));

        } catch (\Exception $e) {
            Log::error('[CalcomV2Service] Exception fetching event type', [
                'id' => $eventTypeId,
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get all schedules
     * 
     * @param array $filters Optional filters
     * @return array
     * @throws \Exception
     */
    public function getSchedules(array $filters = []): array
    {
        try {
            $queryParams = http_build_query($filters);
            $endpoint = '/schedules' . ($queryParams ? '?' . $queryParams : '');

            Log::debug('[CalcomV2Service] Fetching schedules', ['filters' => $filters]);

            $response = $this->makeApiCall('GET', $endpoint);

            if (!$response->successful()) {
                throw new \Exception("Failed to fetch schedules: " . $this->extractErrorMessage($response));
            }

            $result = $response->json();
            
            if ($result['status'] === 'success') {
                return $result['data'];
            }
            
            throw new \Exception("Failed to get schedules: " . ($result['error']['message'] ?? 'Unknown error'));

        } catch (\Exception $e) {
            Log::error('[CalcomV2Service] Exception fetching schedules', [
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get available slots for an event type
     * 
     * @param int $eventTypeId
     * @param string $startDate Format: YYYY-MM-DD
     * @param string $endDate Format: YYYY-MM-DD
     * @param array $additionalParams Optional additional parameters
     * @return array
     * @throws \Exception
     */
    public function getAvailableSlots(int $eventTypeId, string $startDate, string $endDate, array $additionalParams = []): array
    {
        try {
            $params = array_merge([
                'startDate' => $startDate,
                'endDate' => $endDate
            ], $additionalParams);
            
            $queryParams = http_build_query($params);
            $endpoint = "/slots/available?eventTypeId={$eventTypeId}&" . $queryParams;

            Log::debug('[CalcomV2Service] Fetching available slots', [
                'eventTypeId' => $eventTypeId,
                'startDate' => $startDate,
                'endDate' => $endDate
            ]);

            $response = $this->makeApiCall('GET', $endpoint);

            if (!$response->successful()) {
                throw new \Exception("Failed to fetch available slots: " . $this->extractErrorMessage($response));
            }

            $result = $response->json();
            
            if ($result['status'] === 'success') {
                return $result['data'];
            }
            
            throw new \Exception("Failed to get available slots: " . ($result['error']['message'] ?? 'Unknown error'));

        } catch (\Exception $e) {
            Log::error('[CalcomV2Service] Exception fetching available slots', [
                'eventTypeId' => $eventTypeId,
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get all users (team members)
     * 
     * @param array $filters Optional filters
     * @return array
     * @throws \Exception
     */
    public function getUsers(array $filters = []): array
    {
        try {
            $queryParams = http_build_query($filters);
            $endpoint = '/users' . ($queryParams ? '?' . $queryParams : '');

            Log::debug('[CalcomV2Service] Fetching users', ['filters' => $filters]);

            $response = $this->makeApiCall('GET', $endpoint);

            if (!$response->successful()) {
                throw new \Exception("Failed to fetch users: " . $this->extractErrorMessage($response));
            }

            $result = $response->json();
            
            if ($result['status'] === 'success') {
                return $result['data'];
            }
            
            throw new \Exception("Failed to get users: " . ($result['error']['message'] ?? 'Unknown error'));

        } catch (\Exception $e) {
            Log::error('[CalcomV2Service] Exception fetching users', [
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get all teams
     * 
     * @param array $filters Optional filters
     * @return array
     * @throws \Exception
     */
    public function getTeams(array $filters = []): array
    {
        try {
            $queryParams = http_build_query($filters);
            $endpoint = '/teams' . ($queryParams ? '?' . $queryParams : '');

            Log::debug('[CalcomV2Service] Fetching teams', ['filters' => $filters]);

            $response = $this->makeApiCall('GET', $endpoint);

            if (!$response->successful()) {
                throw new \Exception("Failed to fetch teams: " . $this->extractErrorMessage($response));
            }

            $result = $response->json();
            
            if ($result['status'] === 'success') {
                return $result['data'];
            }
            
            throw new \Exception("Failed to get teams: " . ($result['error']['message'] ?? 'Unknown error'));

        } catch (\Exception $e) {
            Log::error('[CalcomV2Service] Exception fetching teams', [
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get paginated bookings with cursor-based pagination
     * 
     * @param array $filters Optional filters
     * @param string|null $cursor Pagination cursor
     * @param int $limit Number of items per page
     * @return array
     * @throws \Exception
     */
    public function getBookingsPaginated(array $filters = [], ?string $cursor = null, int $limit = 100): array
    {
        try {
            $params = array_merge($filters, [
                'limit' => $limit
            ]);
            
            if ($cursor) {
                $params['cursor'] = $cursor;
            }
            
            $queryParams = http_build_query($params);
            $endpoint = '/bookings?' . $queryParams;

            Log::debug('[CalcomV2Service] Fetching paginated bookings', [
                'filters' => $filters,
                'cursor' => $cursor,
                'limit' => $limit
            ]);

            $response = $this->makeApiCall('GET', $endpoint);

            if (!$response->successful()) {
                throw new \Exception("Failed to fetch bookings: " . $this->extractErrorMessage($response));
            }

            $result = $response->json();
            
            if ($result['status'] === 'success') {
                return [
                    'data' => $result['data'],
                    'nextCursor' => $result['nextCursor'] ?? null,
                    'hasMore' => isset($result['nextCursor'])
                ];
            }
            
            throw new \Exception("Failed to get bookings: " . ($result['error']['message'] ?? 'Unknown error'));

        } catch (\Exception $e) {
            Log::error('[CalcomV2Service] Exception fetching paginated bookings', [
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get all bookings (handles pagination automatically)
     * 
     * @param array $filters Optional filters
     * @return array All bookings
     * @throws \Exception
     */
    public function getAllBookings(array $filters = []): array
    {
        try {
            $allBookings = [];
            $cursor = null;
            $hasMore = true;
            $pageCount = 0;
            $maxPages = 100; // Safety limit

            while ($hasMore && $pageCount < $maxPages) {
                $pageCount++;
                
                Log::info('[CalcomV2Service] Fetching bookings page', ['page' => $pageCount]);
                
                $result = $this->getBookingsPaginated($filters, $cursor, 100);
                
                if (!empty($result['data'])) {
                    $allBookings = array_merge($allBookings, $result['data']);
                }
                
                $cursor = $result['nextCursor'] ?? null;
                $hasMore = $result['hasMore'] ?? false;
                
                // Rate limiting protection
                if ($hasMore) {
                    usleep(200000); // 200ms delay between requests
                }
            }

            Log::info('[CalcomV2Service] Fetched all bookings', [
                'total' => count($allBookings),
                'pages' => $pageCount
            ]);

            return $allBookings;

        } catch (\Exception $e) {
            Log::error('[CalcomV2Service] Exception fetching all bookings', [
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get organization details
     * 
     * @param int|null $organizationId
     * @return array
     * @throws \Exception
     */
    public function getOrganization(?int $organizationId = null): array
    {
        try {
            $orgId = $organizationId ?? $this->organizationId;
            
            if (!$orgId) {
                throw new \Exception('Organization ID not configured');
            }

            Log::debug('[CalcomV2Service] Fetching organization', ['id' => $orgId]);

            $response = $this->makeApiCall('GET', "/organizations/{$orgId}");

            if (!$response->successful()) {
                throw new \Exception("Failed to fetch organization: " . $this->extractErrorMessage($response));
            }

            $result = $response->json();
            
            if ($result['status'] === 'success') {
                return $result['data'];
            }
            
            throw new \Exception("Failed to get organization: " . ($result['error']['message'] ?? 'Unknown error'));

        } catch (\Exception $e) {
            Log::error('[CalcomV2Service] Exception fetching organization', [
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}