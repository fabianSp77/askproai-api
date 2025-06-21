<?php

namespace App\Services;

use App\Services\CalcomService;
use App\Services\CalcomV2Service;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

/**
 * Service to handle gradual migration from Cal.com V1 to V2 API
 * 
 * This service provides a unified interface that can switch between
 * V1 and V2 implementations based on configuration or feature flags.
 */
class CalcomMigrationService
{
    protected CalcomService $v1Service;
    protected CalcomV2Service $v2Service;
    protected bool $useV2ByDefault;
    protected array $v2EnabledMethods = [];
    
    public function __construct(CalcomService $v1Service, CalcomV2Service $v2Service)
    {
        $this->v1Service = $v1Service;
        $this->v2Service = $v2Service;
        
        // Load migration configuration
        $this->useV2ByDefault = config('services.calcom.use_v2_api', false);
        $this->v2EnabledMethods = config('services.calcom.v2_enabled_methods', []);
    }
    
    /**
     * Get event types with migration support
     */
    public function getEventTypes(string $teamSlug = null)
    {
        if ($this->shouldUseV2('getEventTypes')) {
            try {
                Log::info('Using Cal.com V2 API for getEventTypes');
                return $this->v2Service->getEventTypes($teamSlug);
            } catch (\Exception $e) {
                Log::error('Cal.com V2 API failed, falling back to V1', [
                    'method' => 'getEventTypes',
                    'error' => $e->getMessage()
                ]);
                
                if ($this->isV2Mandatory('getEventTypes')) {
                    throw $e;
                }
                
                // Fallback to V1
                return $this->v1Service->getEventTypes();
            }
        }
        
        return $this->v1Service->getEventTypes();
    }
    
    /**
     * Get available slots with migration support
     */
    public function getAvailableSlots(
        int $eventTypeId,
        string $startDate,
        string $endDate,
        string $timezone = 'Europe/Berlin'
    ) {
        if ($this->shouldUseV2('getAvailableSlots')) {
            try {
                Log::info('Using Cal.com V2 API for getAvailableSlots');
                
                $slots = $this->v2Service->getSlots($eventTypeId, $startDate, $endDate, $timezone);
                
                // Transform V2 response to match V1 format if needed
                return $this->transformV2SlotsToV1Format($slots);
                
            } catch (\Exception $e) {
                Log::error('Cal.com V2 API failed, falling back to V1', [
                    'method' => 'getAvailableSlots',
                    'error' => $e->getMessage()
                ]);
                
                if ($this->isV2Mandatory('getAvailableSlots')) {
                    throw $e;
                }
                
                // Fallback to V1
                return $this->v1Service->getAvailableSlots($eventTypeId, $startDate, $endDate, $timezone);
            }
        }
        
        return $this->v1Service->getAvailableSlots($eventTypeId, $startDate, $endDate, $timezone);
    }
    
    /**
     * Create booking with migration support
     */
    public function bookAppointment(
        int $eventTypeId,
        string $startTime,
        string $endTime = null,
        array $customerData = [],
        string $notes = null,
        array $metadata = []
    ) {
        if ($this->shouldUseV2('bookAppointment')) {
            try {
                Log::info('Using Cal.com V2 API for bookAppointment');
                
                // V2 doesn't use endTime, it calculates from event type duration
                $attendee = [
                    'name' => $customerData['name'] ?? 'Guest',
                    'email' => $customerData['email'] ?? 'guest@example.com',
                    'phone' => $customerData['phone'] ?? null,
                    'timeZone' => $customerData['timezone'] ?? 'Europe/Berlin',
                ];
                
                $booking = $this->v2Service->bookAppointment(
                    $eventTypeId,
                    $startTime,
                    $attendee,
                    $notes,
                    $metadata
                );
                
                // Transform V2 response to match V1 format if needed
                return $this->transformV2BookingToV1Format($booking);
                
            } catch (\Exception $e) {
                Log::error('Cal.com V2 API failed, falling back to V1', [
                    'method' => 'bookAppointment',
                    'error' => $e->getMessage()
                ]);
                
                if ($this->isV2Mandatory('bookAppointment')) {
                    throw $e;
                }
                
                // Fallback to V1
                return $this->v1Service->bookAppointment(
                    $eventTypeId,
                    $startTime,
                    $endTime,
                    $customerData,
                    $notes
                );
            }
        }
        
        return $this->v1Service->bookAppointment(
            $eventTypeId,
            $startTime,
            $endTime,
            $customerData,
            $notes
        );
    }
    
    /**
     * Cancel booking with migration support
     */
    public function cancelBooking(string $bookingId, string $reason = null)
    {
        if ($this->shouldUseV2('cancelBooking')) {
            try {
                Log::info('Using Cal.com V2 API for cancelBooking');
                return $this->v2Service->cancelBooking($bookingId, $reason);
            } catch (\Exception $e) {
                Log::error('Cal.com V2 API failed, falling back to V1', [
                    'method' => 'cancelBooking',
                    'error' => $e->getMessage()
                ]);
                
                if ($this->isV2Mandatory('cancelBooking')) {
                    throw $e;
                }
                
                // Fallback to V1
                return $this->v1Service->cancelBooking($bookingId, $reason);
            }
        }
        
        return $this->v1Service->cancelBooking($bookingId, $reason);
    }
    
    /**
     * Get booking by ID with migration support
     */
    public function getBooking(string $bookingId)
    {
        if ($this->shouldUseV2('getBooking')) {
            try {
                Log::info('Using Cal.com V2 API for getBooking');
                $booking = $this->v2Service->getBookingById($bookingId);
                return $this->transformV2BookingToV1Format($booking);
            } catch (\Exception $e) {
                Log::error('Cal.com V2 API failed, falling back to V1', [
                    'method' => 'getBooking',
                    'error' => $e->getMessage()
                ]);
                
                if ($this->isV2Mandatory('getBooking')) {
                    throw $e;
                }
                
                // Fallback to V1
                return $this->v1Service->getBookingByUid($bookingId);
            }
        }
        
        return $this->v1Service->getBookingByUid($bookingId);
    }
    
    /**
     * Check if V2 should be used for a specific method
     */
    protected function shouldUseV2(string $method): bool
    {
        // Check if V2 is force-enabled for this method
        if (in_array($method, $this->v2EnabledMethods)) {
            return true;
        }
        
        // Check feature flag
        if (Cache::get("calcom_v2_enabled_{$method}", false)) {
            return true;
        }
        
        // Check global V2 flag
        return $this->useV2ByDefault;
    }
    
    /**
     * Check if V2 is mandatory (no fallback allowed)
     */
    protected function isV2Mandatory(string $method): bool
    {
        $mandatoryMethods = config('services.calcom.v2_mandatory_methods', []);
        return in_array($method, $mandatoryMethods);
    }
    
    /**
     * Transform V2 slots response to V1 format
     */
    protected function transformV2SlotsToV1Format($v2Response): array
    {
        if (!isset($v2Response['data'])) {
            return [];
        }
        
        $slots = [];
        foreach ($v2Response['data'] as $date => $times) {
            foreach ($times as $time) {
                $slots[] = [
                    'time' => $time,
                    'date' => $date,
                    'datetime' => "{$date}T{$time}:00",
                ];
            }
        }
        
        return ['slots' => $slots];
    }
    
    /**
     * Transform V2 booking response to V1 format
     */
    protected function transformV2BookingToV1Format($v2Response): array
    {
        if (!is_array($v2Response) || !isset($v2Response['data'])) {
            return $v2Response;
        }
        
        $data = $v2Response['data'];
        
        return [
            'id' => $data['id'] ?? null,
            'uid' => $data['uid'] ?? null,
            'title' => $data['title'] ?? null,
            'startTime' => $data['start'] ?? null,
            'endTime' => $data['end'] ?? null,
            'attendees' => $data['attendees'] ?? [],
            'location' => $data['location'] ?? null,
            'status' => $data['status'] ?? 'ACCEPTED',
            'metadata' => $data['metadata'] ?? [],
            'eventTypeId' => $data['eventTypeId'] ?? null,
        ];
    }
    
    /**
     * Enable V2 for specific method (for gradual rollout)
     */
    public function enableV2ForMethod(string $method, int $ttl = 3600): void
    {
        Cache::put("calcom_v2_enabled_{$method}", true, $ttl);
        Log::info("Cal.com V2 enabled for method: {$method}", ['ttl' => $ttl]);
    }
    
    /**
     * Disable V2 for specific method (for rollback)
     */
    public function disableV2ForMethod(string $method): void
    {
        Cache::forget("calcom_v2_enabled_{$method}");
        Log::info("Cal.com V2 disabled for method: {$method}");
    }
    
    /**
     * Get migration status
     */
    public function getMigrationStatus(): array
    {
        $methods = [
            'getEventTypes',
            'getAvailableSlots',
            'bookAppointment',
            'cancelBooking',
            'getBooking'
        ];
        
        $status = [
            'global_v2_enabled' => $this->useV2ByDefault,
            'methods' => []
        ];
        
        foreach ($methods as $method) {
            $status['methods'][$method] = [
                'v2_enabled' => $this->shouldUseV2($method),
                'v2_mandatory' => $this->isV2Mandatory($method),
                'cache_override' => Cache::has("calcom_v2_enabled_{$method}")
            ];
        }
        
        return $status;
    }
}