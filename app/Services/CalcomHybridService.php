<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Cal.com Hybrid Service
 * 
 * Intelligently routes between V1 and V2 APIs based on operation type.
 * This service provides a unified interface during the migration period.
 * 
 * V1 is used for: Event Types, Availability
 * V2 is used for: Bookings, Cancellations, Rescheduling
 * 
 * @deprecated V1 will be sunset end of 2025
 */
class CalcomHybridService
{
    protected CalcomService $v1Service;
    protected CalcomV2Service $v2Service;
    protected bool $hybridMode;
    protected array $metrics = [
        'v1_calls' => 0,
        'v2_calls' => 0,
        'errors' => 0
    ];

    public function __construct()
    {
        $this->v1Service = new CalcomService();
        $this->v2Service = new CalcomV2Service();
        $this->hybridMode = config('services.calcom.hybrid_mode', true);
        
        Log::info('[CalcomHybridService] Initialized in ' . ($this->hybridMode ? 'HYBRID' : 'SINGLE') . ' mode');
    }

    /**
     * Get event type details (V1 only - not available in V2 without platform subscription)
     * 
     * @param int $eventTypeId
     * @return array
     * @throws \Exception
     */
    public function getEventType(int $eventTypeId): array
    {
        $this->logDeprecationWarning('getEventType', 'V1');
        $this->metrics['v1_calls']++;
        
        try {
            return $this->v1Service->getEventType($eventTypeId);
        } catch (\Exception $e) {
            $this->metrics['errors']++;
            Log::error('[CalcomHybridService] Failed to get event type', [
                'event_type_id' => $eventTypeId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Create a booking (V2 preferred, V1 fallback)
     * 
     * @param array $bookingDetails
     * @return array
     * @throws \Exception
     */
    public function createBooking(array $bookingDetails): array
    {
        if ($this->hybridMode) {
            Log::info('[CalcomHybridService] Creating booking via V2 API');
            $this->metrics['v2_calls']++;
            
            try {
                // Try V2 first
                return $this->v2Service->createBooking($bookingDetails);
            } catch (\Exception $e) {
                Log::warning('[CalcomHybridService] V2 booking failed, falling back to V1', [
                    'error' => $e->getMessage()
                ]);
                
                // Fallback to V1
                $this->metrics['v1_calls']++;
                return $this->createBookingV1Fallback($bookingDetails);
            }
        } else {
            // Single mode - use V1
            $this->logDeprecationWarning('createBooking', 'V1');
            $this->metrics['v1_calls']++;
            return $this->createBookingV1Fallback($bookingDetails);
        }
    }

    /**
     * Cancel a booking (V2 only)
     * 
     * @param string $uid Booking UID
     * @param string $reason Cancellation reason
     * @return array
     * @throws \Exception
     */
    public function cancelBooking(string $uid, string $reason = 'Cancelled by user'): array
    {
        Log::info('[CalcomHybridService] Cancelling booking via V2 API');
        $this->metrics['v2_calls']++;
        
        try {
            return $this->v2Service->cancelBooking($uid, $reason);
        } catch (\Exception $e) {
            $this->metrics['errors']++;
            Log::error('[CalcomHybridService] Failed to cancel booking', [
                'uid' => $uid,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Reschedule a booking (V2 only)
     * 
     * @param string $uid Booking UID
     * @param array $newDetails
     * @return array
     * @throws \Exception
     */
    public function rescheduleBooking(string $uid, array $newDetails): array
    {
        Log::info('[CalcomHybridService] Rescheduling booking via V2 API');
        $this->metrics['v2_calls']++;
        
        try {
            return $this->v2Service->rescheduleBooking($uid, $newDetails);
        } catch (\Exception $e) {
            $this->metrics['errors']++;
            Log::error('[CalcomHybridService] Failed to reschedule booking', [
                'uid' => $uid,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get a booking by UID (V2 preferred)
     * 
     * @param string $uid
     * @return array
     * @throws \Exception
     */
    public function getBooking(string $uid): array
    {
        Log::debug('[CalcomHybridService] Fetching booking via V2 API');
        $this->metrics['v2_calls']++;
        
        try {
            return $this->v2Service->getBooking($uid);
        } catch (\Exception $e) {
            $this->metrics['errors']++;
            Log::error('[CalcomHybridService] Failed to get booking', [
                'uid' => $uid,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get all bookings (V2 only)
     * 
     * @param array $filters
     * @return array
     * @throws \Exception
     */
    public function getBookings(array $filters = []): array
    {
        Log::debug('[CalcomHybridService] Fetching bookings via V2 API');
        $this->metrics['v2_calls']++;
        
        try {
            return $this->v2Service->getBookings($filters);
        } catch (\Exception $e) {
            $this->metrics['errors']++;
            Log::error('[CalcomHybridService] Failed to get bookings', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Check availability (V1 only - V2 slots endpoint requires different structure)
     * 
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public function checkAvailability(array $params): array
    {
        $this->logDeprecationWarning('checkAvailability', 'V1');
        $this->metrics['v1_calls']++;
        
        try {
            return $this->v1Service->checkAvailability($params);
        } catch (\Exception $e) {
            $this->metrics['errors']++;
            Log::error('[CalcomHybridService] Failed to check availability', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Create booking from call data (V2 preferred)
     * 
     * @param array $requestData
     * @return array
     * @throws \Exception
     */
    public function createBookingFromCall(array $requestData): array
    {
        if ($this->hybridMode) {
            Log::info('[CalcomHybridService] Creating booking from call via V2 API');
            $this->metrics['v2_calls']++;
            
            try {
                // Prepare data for V2
                $v2Data = $this->transformCallDataForV2($requestData);
                return $this->v2Service->createBooking($v2Data);
            } catch (\Exception $e) {
                Log::warning('[CalcomHybridService] V2 booking from call failed, falling back to V1', [
                    'error' => $e->getMessage()
                ]);
                
                // Fallback to V1
                $this->metrics['v1_calls']++;
                return $this->v1Service->createBookingFromCall($requestData);
            }
        } else {
            $this->logDeprecationWarning('createBookingFromCall', 'V1');
            $this->metrics['v1_calls']++;
            return $this->v1Service->createBookingFromCall($requestData);
        }
    }

    /**
     * Get usage metrics
     * 
     * @return array
     */
    public function getMetrics(): array
    {
        $total = $this->metrics['v1_calls'] + $this->metrics['v2_calls'];
        
        return [
            'total_calls' => $total,
            'v1_calls' => $this->metrics['v1_calls'],
            'v2_calls' => $this->metrics['v2_calls'],
            'v1_percentage' => $total > 0 ? round(($this->metrics['v1_calls'] / $total) * 100, 2) : 0,
            'v2_percentage' => $total > 0 ? round(($this->metrics['v2_calls'] / $total) * 100, 2) : 0,
            'errors' => $this->metrics['errors'],
            'hybrid_mode' => $this->hybridMode,
            'deprecation_date' => '2025-12-31'
        ];
    }

    /**
     * Log metrics to monitoring system
     */
    public function logMetrics(): void
    {
        $metrics = $this->getMetrics();
        
        Log::info('[CalcomHybridService] Usage Metrics', $metrics);
        
        // Alert if still heavily using V1
        if ($metrics['v1_percentage'] > 50) {
            Log::warning('[CalcomHybridService] ⚠️ High V1 API usage detected. Migration to V2 required before 2025-12-31', [
                'v1_percentage' => $metrics['v1_percentage']
            ]);
        }
    }

    /**
     * Transform call data for V2 API
     * 
     * @param array $requestData
     * @return array
     */
    protected function transformCallDataForV2(array $requestData): array
    {
        return [
            'eventTypeId' => $requestData['eventTypeId'],
            'start' => $requestData['start'],
            'attendee' => [
                'name' => $requestData['name'],
                'email' => $requestData['email'],
                'timeZone' => $requestData['timeZone'] ?? config('services.calcom.timezone'),
                'language' => $requestData['language'] ?? config('services.calcom.language')
            ],
            'metadata' => $requestData['metadata'] ?? [],
            'location' => $requestData['location'] ?? null
        ];
    }

    /**
     * Create booking using V1 API (fallback)
     * 
     * @param array $bookingDetails
     * @return array
     * @throws \Exception
     */
    protected function createBookingV1Fallback(array $bookingDetails): array
    {
        // Transform data if needed for V1
        if (isset($bookingDetails['attendee'])) {
            $bookingDetails['name'] = $bookingDetails['attendee']['name'] ?? $bookingDetails['name'];
            $bookingDetails['email'] = $bookingDetails['attendee']['email'] ?? $bookingDetails['email'];
            $bookingDetails['timeZone'] = $bookingDetails['attendee']['timeZone'] ?? $bookingDetails['timeZone'];
        }
        
        // Calculate end time if not provided
        if (!isset($bookingDetails['endTime']) && isset($bookingDetails['start'])) {
            $duration = $bookingDetails['lengthInMinutes'] ?? 30;
            $startTime = new \DateTime($bookingDetails['start']);
            $endTime = clone $startTime;
            $endTime->modify("+{$duration} minutes");
            $bookingDetails['startTime'] = $bookingDetails['start'];
            $bookingDetails['endTime'] = $endTime->format('Y-m-d\TH:i:s.000\Z');
        }
        
        $response = $this->v1Service->createBooking($bookingDetails);
        
        // Transform V1 response to match V2 structure if needed
        if ($response instanceof \Illuminate\Http\Client\Response) {
            $data = $response->json();
            return $data;
        }
        
        return $response;
    }

    /**
     * Log deprecation warning
     * 
     * @param string $method
     * @param string $version
     */
    protected function logDeprecationWarning(string $method, string $version): void
    {
        if ($version === 'V1') {
            Log::warning("[CalcomHybridService] ⚠️ Method '{$method}' using {$version} API. V1 will be deprecated 2025-12-31. Migrate to V2 or enable hybrid mode.");
        }
    }

    /**
     * Check if running in hybrid mode
     * 
     * @return bool
     */
    public function isHybridMode(): bool
    {
        return $this->hybridMode;
    }

    /**
     * Force a specific API version (for testing)
     * 
     * @param bool $useHybrid
     */
    public function setHybridMode(bool $useHybrid): void
    {
        $this->hybridMode = $useHybrid;
        Log::info('[CalcomHybridService] Mode changed to: ' . ($useHybrid ? 'HYBRID' : 'SINGLE'));
    }
}