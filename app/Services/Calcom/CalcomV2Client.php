<?php

namespace App\Services\Calcom;

use App\Services\CircuitBreaker\CircuitBreaker;
use App\Services\Logging\StructuredLogger;
use App\Services\Traits\RetryableHttpClient;
use App\Services\Calcom\Exceptions\CalcomApiException;
use App\Services\Calcom\Exceptions\CalcomRateLimitException;
use App\Services\Calcom\Exceptions\CalcomAuthenticationException;
use App\Services\Calcom\Exceptions\CalcomValidationException;
use App\Services\Calcom\DTOs\EventTypeDTO;
use App\Services\Calcom\DTOs\ScheduleDTO;
use App\Services\Calcom\DTOs\SlotDTO;
use App\Services\Calcom\DTOs\BookingDTO;
use App\Services\Calcom\DTOs\AttendeeDTO;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\PendingRequest;

/**
 * Cal.com V2 API Client
 * 
 * Production-ready client for Cal.com V2 API with:
 * - Circuit breaker pattern for fault tolerance
 * - Retry logic with exponential backoff
 * - Response caching for performance
 * - Structured logging for debugging
 * - Type-safe DTOs for responses
 * - Comprehensive error handling
 */
class CalcomV2Client
{
    use RetryableHttpClient;

    private const BASE_URL = 'https://api.cal.com/v2';
    private const DEFAULT_TIMEOUT = 30;
    private const CACHE_PREFIX = 'calcom_v2:';
    
    // Cache TTLs in seconds
    private const CACHE_TTL_EVENT_TYPES = 300; // 5 minutes
    private const CACHE_TTL_SCHEDULES = 300; // 5 minutes  
    private const CACHE_TTL_SLOTS = 60; // 1 minute
    private const CACHE_TTL_BOOKINGS = 0; // No caching for bookings

    private string $apiKey;
    private CircuitBreaker $circuitBreaker;
    private StructuredLogger $logger;
    private array $defaultHeaders;
    private int $timeout;

    public function __construct(string $apiKey = null, int $timeout = self::DEFAULT_TIMEOUT)
    {
        $this->apiKey = $apiKey ?? config('services.calcom.api_key');
        $this->timeout = $timeout;
        $this->circuitBreaker = new CircuitBreaker();
        $this->logger = new StructuredLogger();
        
        $this->defaultHeaders = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'cal-api-version' => '2024-08-13', // Latest stable version
        ];
    }

    /**
     * Make a raw request to the API (for testing and direct access)
     */
    public function request(string $method, string $endpoint, array $options = []): array
    {
        return $this->executeRequest($method, $endpoint, $options);
    }
    
    /**
     * Get all event types
     * GET /api/v2/event-types
     */
    public function getEventTypes(array $filters = []): array
    {
        $cacheKey = $this->getCacheKey('event_types', $filters);
        
        return Cache::remember($cacheKey, self::CACHE_TTL_EVENT_TYPES, function() use ($filters) {
            return $this->executeRequest('GET', '/event-types', [
                'query' => $filters
            ]);
        });
    }

    /**
     * Get all schedules
     * GET /api/v2/schedules
     */
    public function getSchedules(array $filters = []): array
    {
        $cacheKey = $this->getCacheKey('schedules', $filters);
        
        return Cache::remember($cacheKey, self::CACHE_TTL_SCHEDULES, function() use ($filters) {
            return $this->executeRequest('GET', '/schedules', [
                'query' => $filters
            ]);
        });
    }

    /**
     * Get available time slots
     * GET /api/v2/slots/available
     * 
     * @param array $params Required: startTime, endTime, eventTypeId, eventTypeSlug
     */
    public function getAvailableSlots(array $params): array
    {
        $this->validateSlotParams($params);
        
        $cacheKey = $this->getCacheKey('slots', $params);
        
        return Cache::remember($cacheKey, self::CACHE_TTL_SLOTS, function() use ($params) {
            return $this->executeRequest('GET', '/slots/available', [
                'query' => $params
            ]);
        });
    }

    /**
     * Create a new booking
     * POST /api/v2/bookings
     */
    public function createBooking(array $data): BookingDTO
    {
        $this->validateBookingData($data);
        
        $response = $this->executeRequest('POST', '/bookings', [
            'json' => $data
        ]);
        
        // Invalidate slot cache for this event type
        $this->invalidateSlotCache($data['eventTypeId'] ?? null);
        
        return BookingDTO::fromArray($response['data'] ?? $response);
    }

    /**
     * Get all bookings
     * GET /api/v2/bookings
     */
    public function getBookings(array $filters = []): array
    {
        // Never cache booking lists as they change frequently
        return $this->executeRequest('GET', '/bookings', [
            'query' => $filters
        ]);
    }

    /**
     * Get a single booking by UID
     * GET /api/v2/bookings/{uid}
     */
    public function getBooking(string $uid): BookingDTO
    {
        $response = $this->executeRequest('GET', "/bookings/{$uid}");
        return BookingDTO::fromArray($response['data'] ?? $response);
    }

    /**
     * Reschedule a booking
     * PATCH /api/v2/bookings/{uid}/reschedule
     */
    public function rescheduleBooking(string $uid, array $data): BookingDTO
    {
        $this->validateRescheduleData($data);
        
        $response = $this->executeRequest('PATCH', "/bookings/{$uid}/reschedule", [
            'json' => $data
        ]);
        
        // Invalidate slot cache
        $this->invalidateSlotCache();
        
        return BookingDTO::fromArray($response['data'] ?? $response);
    }

    /**
     * Cancel a booking
     * DELETE /api/v2/bookings/{uid}/cancel
     */
    public function cancelBooking(string $uid, array $data = []): array
    {
        $response = $this->executeRequest('DELETE', "/bookings/{$uid}/cancel", [
            'json' => $data
        ]);
        
        // Invalidate slot cache
        $this->invalidateSlotCache();
        
        return $response;
    }

    /**
     * Execute API request with circuit breaker and retry logic
     */
    private function executeRequest(string $method, string $endpoint, array $options = []): array
    {
        $url = self::BASE_URL . $endpoint;
        $requestId = uniqid('calcom_', true);
        
        return $this->circuitBreaker->call('calcom_v2', function() use ($method, $url, $options, $requestId) {
            $startTime = microtime(true);
            
            try {
                // Log request
                Log::debug('Cal.com V2 API Request', [
                    'service' => 'calcom_v2',
                    'method' => $method,
                    'endpoint' => $url,
                    'request_id' => $requestId
                ]);
                
                // Build request
                $request = $this->buildRequest();
                
                // Execute request directly (retry is handled by circuit breaker)
                $response = match(strtoupper($method)) {
                    'GET' => $request->get($url, $options['query'] ?? []),
                    'POST' => $request->post($url, $options['json'] ?? []),
                    'PATCH' => $request->patch($url, $options['json'] ?? []),
                    'DELETE' => $request->delete($url, $options['json'] ?? []),
                    default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}")
                };
                
                // Log response
                $duration = (microtime(true) - $startTime) * 1000;
                $this->logger->logApiResponse('calcom_v2', $response->status(), [
                    'request_id' => $requestId,
                    'duration_ms' => $duration,
                    'success' => $response->successful()
                ]);
                
                // Handle response
                return $this->handleResponse($response, $method, $url);
                
            } catch (\Exception $e) {
                // Log error
                $duration = (microtime(true) - $startTime) * 1000;
                $this->logger->logError($e, [
                    'service' => 'calcom_v2',
                    'request_id' => $requestId,
                    'method' => $method,
                    'endpoint' => $url,
                    'duration_ms' => $duration
                ]);
                
                throw $e;
            }
        }, function() use ($method, $url) {
            // Fallback when circuit is open
            $this->logger->logError(new \Exception('Cal.com V2 circuit breaker open'), [
                'method' => $method,
                'endpoint' => $url
            ]);
            
            throw new CalcomApiException('Cal.com service temporarily unavailable', 503);
        });
    }

    /**
     * Build HTTP request with headers and timeout
     */
    private function buildRequest(): PendingRequest
    {
        return Http::withHeaders($this->defaultHeaders)
            ->timeout($this->timeout)
            ->retry(3, 1000, function($exception) {
                // Retry on network errors or 5xx responses
                if ($exception instanceof \Illuminate\Http\Client\ConnectionException) {
                    return true;
                }
                
                $response = $exception->response ?? null;
                if ($response && $response->serverError()) {
                    return true;
                }
                
                return false;
            });
    }

    /**
     * Handle API response and throw appropriate exceptions
     */
    private function handleResponse(Response $response, string $method, string $endpoint): array
    {
        if ($response->successful()) {
            $data = $response->json();
            
            // Cal.com V2 typically wraps responses in a data key
            if (isset($data['data'])) {
                return $data['data'];
            }
            
            return $data ?: [];
        }
        
        // Handle error responses
        $statusCode = $response->status();
        $errorData = $response->json() ?: [];
        $errorMessage = $errorData['message'] ?? 'Unknown error';
        $errorCode = $errorData['code'] ?? null;
        
        // Log detailed error
        $this->logger->logError(new \Exception("Cal.com API error: {$errorMessage}"), [
            'method' => $method,
            'endpoint' => $endpoint,
            'status_code' => $statusCode,
            'error_code' => $errorCode,
            'response_body' => $response->body()
        ]);
        
        // Throw appropriate exception based on status code
        throw match(true) {
            $statusCode === 401 => new CalcomAuthenticationException($errorMessage, $statusCode),
            $statusCode === 422 => new CalcomValidationException($errorMessage, $statusCode, $errorData['errors'] ?? []),
            $statusCode === 429 => new CalcomRateLimitException($errorMessage, $statusCode, $response->header('Retry-After')),
            $statusCode >= 400 && $statusCode < 500 => new CalcomApiException($errorMessage, $statusCode),
            default => new CalcomApiException("Server error: {$errorMessage}", $statusCode)
        };
    }

    /**
     * Generate cache key for requests
     */
    private function getCacheKey(string $type, array $params = []): string
    {
        $key = self::CACHE_PREFIX . $type;
        
        if (!empty($params)) {
            ksort($params); // Ensure consistent key generation
            $key .= ':' . md5(json_encode($params));
        }
        
        return $key;
    }

    /**
     * Invalidate slot cache for an event type or all slots
     */
    private function invalidateSlotCache(?int $eventTypeId = null): void
    {
        if ($eventTypeId) {
            Cache::tags(['calcom_slots'])->flush();
        } else {
            // Clear all slot caches
            Cache::forget(self::CACHE_PREFIX . 'slots:*');
        }
    }

    /**
     * Validate slot query parameters
     */
    private function validateSlotParams(array $params): void
    {
        $required = ['startTime', 'endTime'];
        $needsOne = ['eventTypeId', 'eventTypeSlug'];
        
        foreach ($required as $field) {
            if (empty($params[$field])) {
                throw new CalcomValidationException("Missing required parameter: {$field}", 422);
            }
        }
        
        $hasIdentifier = false;
        foreach ($needsOne as $field) {
            if (!empty($params[$field])) {
                $hasIdentifier = true;
                break;
            }
        }
        
        if (!$hasIdentifier) {
            throw new CalcomValidationException("Must provide either eventTypeId or eventTypeSlug", 422);
        }
    }

    /**
     * Validate booking data
     */
    private function validateBookingData(array $data): void
    {
        $required = ['start', 'eventTypeId', 'responses', 'metadata'];
        
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new CalcomValidationException("Missing required field: {$field}", 422);
            }
        }
        
        // Validate responses has required fields
        if (!isset($data['responses']['name']) || !isset($data['responses']['email'])) {
            throw new CalcomValidationException("Responses must include name and email", 422);
        }
    }

    /**
     * Validate reschedule data
     */
    private function validateRescheduleData(array $data): void
    {
        if (empty($data['start'])) {
            throw new CalcomValidationException("Missing required field: start", 422);
        }
    }

    /**
     * Sanitize options for logging (remove sensitive data)
     */
    private function sanitizeOptions(array $options): array
    {
        $sanitized = $options;
        
        // Remove sensitive fields from json data
        if (isset($sanitized['json']['responses'])) {
            $sanitized['json']['responses'] = array_map(function($value, $key) {
                if (in_array($key, ['email', 'phone', 'notes'])) {
                    return '[REDACTED]';
                }
                return $value;
            }, $sanitized['json']['responses'], array_keys($sanitized['json']['responses']));
        }
        
        return $sanitized;
    }

    /**
     * Health check for Cal.com API
     */
    public function healthCheck(): array
    {
        try {
            $startTime = microtime(true);
            
            // Try to fetch event types as a health check
            $this->executeRequest('GET', '/event-types', [
                'query' => ['limit' => 1]
            ]);
            
            $duration = (microtime(true) - $startTime) * 1000;
            
            return [
                'status' => 'healthy',
                'response_time_ms' => $duration,
                'circuit_state' => $this->circuitBreaker->getState('calcom_v2')
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'circuit_state' => $this->circuitBreaker->getState('calcom_v2')
            ];
        }
    }

    /**
     * Get client metrics
     */
    public function getMetrics(): array
    {
        return [
            'circuit_breaker' => [
                'state' => $this->circuitBreaker->getState('calcom_v2'),
                'failure_count' => $this->circuitBreaker->getFailureCount('calcom_v2'),
                'success_count' => $this->circuitBreaker->getSuccessCount('calcom_v2'),
                'last_failure_time' => $this->circuitBreaker->getLastFailureTime('calcom_v2'),
            ],
            'cache' => [
                'event_types_ttl' => self::CACHE_TTL_EVENT_TYPES,
                'schedules_ttl' => self::CACHE_TTL_SCHEDULES,
                'slots_ttl' => self::CACHE_TTL_SLOTS,
            ],
            'api_version' => $this->defaultHeaders['cal-api-version']
        ];
    }
}