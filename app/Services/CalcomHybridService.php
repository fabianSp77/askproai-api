<?php

namespace App\Services;

use App\Services\CalcomService;
use App\Services\Calcom\CalcomV2Service;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

/**
 * Hybrid Cal.com Service
 * 
 * Uses V2 API for availability checking (works well)
 * Uses V1 API for booking creation (works reliably)
 * 
 * This solves the issue where V2 booking fails but V1 works
 */
class CalcomHybridService
{
    protected CalcomService $v1Service;
    protected CalcomV2Service $v2Service;
    protected string $apiKey;
    protected string $baseUrlV1 = 'https://api.cal.com/v1';
    protected string $baseUrlV2 = 'https://api.cal.com/v2';
    
    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? config('services.calcom.api_key');
        
        // Initialize both services
        $this->v1Service = new CalcomService($this->apiKey);
        $this->v2Service = new CalcomV2Service($this->apiKey);
        
        Log::info('[CalcomHybrid] Service initialized', [
            'has_api_key' => !empty($this->apiKey),
            'availability_provider' => $this->getAvailabilityProvider(),
            'booking_provider' => $this->getBookingProvider()
        ]);
    }
    
    /**
     * Get configured availability provider
     */
    protected function getAvailabilityProvider(): string
    {
        return env('CAL_AVAILABILITY_PROVIDER', 'v2'); // Default to V2 (works well)
    }
    
    /**
     * Get configured booking provider
     */
    protected function getBookingProvider(): string
    {
        return env('CAL_BOOKING_PROVIDER', 'v1'); // Default to V1 (reliable)
    }
    
    /**
     * Check availability using configured provider
     */
    public function checkAvailability(
        int $eventTypeId,
        Carbon $startDate,
        Carbon $endDate,
        ?string $timezone = 'Europe/Berlin'
    ): array {
        try {
            $provider = $this->getAvailabilityProvider();
            
            Log::info('[CalcomHybrid] Checking availability', [
                'provider' => $provider,
                'event_type_id' => $eventTypeId,
                'start' => $startDate->toIso8601String(),
                'end' => $endDate->toIso8601String(),
                'timezone' => $timezone
            ]);
            
            // Use configured provider (default V2 works well)
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'cal-api-version' => '2024-08-13'
            ])->get("{$this->baseUrlV2}/slots/available", [
                'eventTypeId' => $eventTypeId,
                'startTime' => $startDate->toIso8601String(),
                'endTime' => $endDate->toIso8601String(),
                'timeZone' => $timezone
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('[CalcomHybrid] V2 availability check successful', [
                    'slots_count' => count($data['data']['slots'] ?? [])
                ]);
                
                return [
                    'success' => true,
                    'slots' => $data['data']['slots'] ?? [],
                    'message' => 'Availability fetched successfully'
                ];
            }
            
            Log::error('[CalcomHybrid] V2 availability check failed', [
                'status' => $response->status(),
                'error' => $response->json()
            ]);
            
            return [
                'success' => false,
                'slots' => [],
                'message' => 'Failed to fetch availability',
                'error' => $response->json()
            ];
            
        } catch (\Exception $e) {
            Log::error('[CalcomHybrid] Exception checking availability', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'slots' => [],
                'message' => 'Exception checking availability',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Create booking using V1 API (works reliably)
     */
    public function createBooking(array $bookingData): array
    {
        try {
            // Ensure eventTypeId is an integer
            if (isset($bookingData['eventTypeId'])) {
                $bookingData['eventTypeId'] = (int) $bookingData['eventTypeId'];
            }
            
            Log::info('[CalcomHybrid] Creating booking with V1', [
                'event_type_id' => $bookingData['eventTypeId'] ?? null,
                'start' => $bookingData['start'] ?? null,
                'name' => $bookingData['name'] ?? null
            ]);
            
            // Prepare V1 compatible data
            $v1Data = [
                'eventTypeId' => $bookingData['eventTypeId'],
                'start' => $bookingData['start'],
                'end' => $bookingData['end'] ?? null,
                'name' => $bookingData['name'],
                'email' => $bookingData['email'],
                'timeZone' => $bookingData['timeZone'] ?? 'Europe/Berlin',
                'language' => $bookingData['language'] ?? 'de',
                'metadata' => $bookingData['metadata'] ?? []
            ];
            
            // Add optional fields
            if (isset($bookingData['phone'])) {
                $v1Data['phone'] = $bookingData['phone'];
            }
            if (isset($bookingData['notes'])) {
                $v1Data['notes'] = $bookingData['notes'];
            }
            if (isset($bookingData['location'])) {
                $v1Data['location'] = $bookingData['location'];
            }
            
            // Use V1 API with query parameter authentication
            $response = Http::withHeaders([
                'Content-Type' => 'application/json'
            ])->post("{$this->baseUrlV1}/bookings?apiKey={$this->apiKey}", $v1Data);
            
            if ($response->successful()) {
                $responseData = $response->json();
                
                Log::info('[CalcomHybrid] V1 booking created successfully', [
                    'booking_id' => $responseData['id'] ?? null,
                    'booking_uid' => $responseData['uid'] ?? null
                ]);
                
                return [
                    'success' => true,
                    'data' => $responseData,
                    'message' => 'Booking created successfully'
                ];
            }
            
            Log::error('[CalcomHybrid] V1 booking creation failed', [
                'status' => $response->status(),
                'error' => $response->json()
            ]);
            
            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to create booking',
                'error' => $response->json()
            ];
            
        } catch (\Exception $e) {
            Log::error('[CalcomHybrid] Exception creating booking', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'data' => null,
                'message' => 'Exception creating booking',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Cancel booking using V1 API
     */
    public function cancelBooking(string $bookingId, ?string $reason = null): array
    {
        try {
            Log::info('[CalcomHybrid] Cancelling booking with V1', [
                'booking_id' => $bookingId,
                'reason' => $reason
            ]);
            
            $data = [];
            if ($reason) {
                $data['cancellationReason'] = $reason;
            }
            
            $response = Http::withHeaders([
                'Content-Type' => 'application/json'
            ])->delete("{$this->baseUrlV1}/bookings/{$bookingId}?apiKey={$this->apiKey}", $data);
            
            if ($response->successful()) {
                Log::info('[CalcomHybrid] Booking cancelled successfully', [
                    'booking_id' => $bookingId
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Booking cancelled successfully'
                ];
            }
            
            Log::error('[CalcomHybrid] Failed to cancel booking', [
                'booking_id' => $bookingId,
                'status' => $response->status(),
                'error' => $response->json()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to cancel booking',
                'error' => $response->json()
            ];
            
        } catch (\Exception $e) {
            Log::error('[CalcomHybrid] Exception cancelling booking', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Exception cancelling booking',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get booking details using V2 API
     */
    public function getBooking(string $bookingId): array
    {
        try {
            // Try V2 first
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'cal-api-version' => '2024-08-13'
            ])->get("{$this->baseUrlV2}/bookings/{$bookingId}");
            
            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()['data'] ?? $response->json(),
                    'message' => 'Booking fetched successfully'
                ];
            }
            
            // Fallback to V1
            $v1Response = Http::get("{$this->baseUrlV1}/bookings/{$bookingId}?apiKey={$this->apiKey}");
            
            if ($v1Response->successful()) {
                return [
                    'success' => true,
                    'data' => $v1Response->json(),
                    'message' => 'Booking fetched successfully (V1)'
                ];
            }
            
            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to fetch booking',
                'error' => $response->json()
            ];
            
        } catch (\Exception $e) {
            Log::error('[CalcomHybrid] Exception fetching booking', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'data' => null,
                'message' => 'Exception fetching booking',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Reschedule booking using V1 API
     */
    public function rescheduleBooking(
        string $bookingId,
        Carbon $newStart,
        ?Carbon $newEnd = null,
        ?string $reason = null
    ): array {
        try {
            Log::info('[CalcomHybrid] Rescheduling booking with V1', [
                'booking_id' => $bookingId,
                'new_start' => $newStart->toIso8601String(),
                'reason' => $reason
            ]);
            
            $data = [
                'start' => $newStart->toIso8601String(),
                'end' => $newEnd ? $newEnd->toIso8601String() : null,
                'reason' => $reason
            ];
            
            $response = Http::withHeaders([
                'Content-Type' => 'application/json'
            ])->patch("{$this->baseUrlV1}/bookings/{$bookingId}?apiKey={$this->apiKey}", $data);
            
            if ($response->successful()) {
                Log::info('[CalcomHybrid] Booking rescheduled successfully', [
                    'booking_id' => $bookingId
                ]);
                
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'message' => 'Booking rescheduled successfully'
                ];
            }
            
            Log::error('[CalcomHybrid] Failed to reschedule booking', [
                'booking_id' => $bookingId,
                'status' => $response->status(),
                'error' => $response->json()
            ]);
            
            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to reschedule booking',
                'error' => $response->json()
            ];
            
        } catch (\Exception $e) {
            Log::error('[CalcomHybrid] Exception rescheduling booking', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'data' => null,
                'message' => 'Exception rescheduling booking',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get event types using V2 API
     */
    public function getEventTypes(): array
    {
        try {
            // Use V2 for fetching event types
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'cal-api-version' => '2024-08-13'
            ])->get("{$this->baseUrlV2}/event-types");
            
            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()['data'] ?? [],
                    'message' => 'Event types fetched successfully'
                ];
            }
            
            // Fallback to V1
            return $this->v1Service->getEventTypes();
            
        } catch (\Exception $e) {
            Log::error('[CalcomHybrid] Exception fetching event types', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'data' => [],
                'message' => 'Exception fetching event types',
                'error' => $e->getMessage()
            ];
        }
    }
}