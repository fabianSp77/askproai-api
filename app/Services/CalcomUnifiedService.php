<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;
use Carbon\Carbon;

/**
 * Unified Cal.com Service that handles both v1 and v2 API endpoints
 * Provides automatic fallback and version detection
 */
class CalcomUnifiedService
{
    private string $apiKey;
    private string $v1BaseUrl = 'https://api.cal.com/v1';
    private string $v2BaseUrl = 'https://api.cal.com/v2';
    private string $teamSlug;
    private string $apiVersion;
    private bool $enableFallback;
    private string $v2ApiVersion;

    public function __construct()
    {
        $this->apiKey = config('services.calcom.api_key');
        $this->teamSlug = config('services.calcom.team_slug', 'askproai');
        $this->apiVersion = config('services.calcom.api_version', 'v2');
        $this->enableFallback = config('services.calcom.enable_fallback', false);
        $this->v2ApiVersion = config('services.calcom.v2_api_version', '2024-08-13');
    }

    /**
     * Get event types - works with both v1 and v2
     */
    public function getEventTypes()
    {
        try {
            if ($this->apiVersion === 'v2') {
                $response = $this->makeV2Request('/event-types', 'GET');
                
                if ($response->successful()) {
                    return $this->normalizeEventTypesResponse($response->json(), 'v2');
                }
                
                if ($this->enableFallback) {
                    Log::warning('Cal.com v2 event-types failed, falling back to v1');
                    return $this->getEventTypesV1();
                }
            }
            
            return $this->getEventTypesV1();
            
        } catch (\Exception $e) {
            Log::error('Cal.com getEventTypes error', [
                'error' => $e->getMessage(),
                'version' => $this->apiVersion
            ]);
            return null;
        }
    }

    /**
     * Check availability - handles v1 and v2 differences
     */
    public function checkAvailability($eventTypeId, $dateFrom, $dateTo, $timezone = 'Europe/Berlin')
    {
        try {
            if ($this->apiVersion === 'v2') {
                return $this->checkAvailabilityV2($eventTypeId, $dateFrom, $dateTo, $timezone);
            }
            
            return $this->checkAvailabilityV1($eventTypeId, $dateFrom, $dateTo, $timezone);
            
        } catch (\Exception $e) {
            Log::error('Cal.com checkAvailability error', [
                'error' => $e->getMessage(),
                'version' => $this->apiVersion
            ]);
            
            if ($this->enableFallback && $this->apiVersion === 'v2') {
                Log::info('Attempting v1 fallback for availability check');
                return $this->checkAvailabilityV1($eventTypeId, $dateFrom, $dateTo, $timezone);
            }
            
            return null;
        }
    }

    /**
     * Book appointment - unified interface for v1 and v2
     */
    public function bookAppointment($eventTypeId, $startTime, $endTime, $customerData, $notes = null)
    {
        try {
            if ($this->apiVersion === 'v2') {
                return $this->bookAppointmentV2($eventTypeId, $startTime, $customerData, $notes);
            }
            
            return $this->bookAppointmentV1($eventTypeId, $startTime, $endTime, $customerData, $notes);
            
        } catch (\Exception $e) {
            Log::error('Cal.com bookAppointment error', [
                'error' => $e->getMessage(),
                'version' => $this->apiVersion,
                'eventTypeId' => $eventTypeId
            ]);
            
            if ($this->enableFallback && $this->apiVersion === 'v2') {
                Log::info('Attempting v1 fallback for booking');
                return $this->bookAppointmentV1($eventTypeId, $startTime, $endTime, $customerData, $notes);
            }
            
            throw $e;
        }
    }

    /**
     * Get booking details
     */
    public function getBooking($bookingId)
    {
        try {
            if ($this->apiVersion === 'v2') {
                $response = $this->makeV2Request("/bookings/{$bookingId}", 'GET');
            } else {
                $response = $this->makeV1Request("/bookings/{$bookingId}", 'GET');
            }
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('Cal.com getBooking error', [
                'error' => $e->getMessage(),
                'bookingId' => $bookingId
            ]);
            return null;
        }
    }

    /**
     * Cancel booking
     */
    public function cancelBooking($bookingId, $reason = null)
    {
        try {
            $data = $reason ? ['cancellationReason' => $reason] : [];
            
            if ($this->apiVersion === 'v2') {
                $response = $this->makeV2Request("/bookings/{$bookingId}/cancel", 'POST', $data);
            } else {
                $response = $this->makeV1Request("/bookings/{$bookingId}/cancel", 'POST', $data);
            }
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('Cal.com cancelBooking error', [
                'error' => $e->getMessage(),
                'bookingId' => $bookingId
            ]);
            return null;
        }
    }

    // ===== V1 Specific Methods =====

    private function makeV1Request($endpoint, $method = 'GET', $data = null): Response
    {
        $url = $this->v1BaseUrl . $endpoint;
        $params = ['apiKey' => $this->apiKey];
        
        $request = Http::withHeaders(['Content-Type' => 'application/json']);
        
        switch ($method) {
            case 'GET':
                return $request->get($url, $params);
            case 'POST':
                $url .= '?' . http_build_query($params);
                return $request->post($url, $data);
            case 'PUT':
                $url .= '?' . http_build_query($params);
                return $request->put($url, $data);
            case 'DELETE':
                return $request->delete($url, $params);
            default:
                throw new \InvalidArgumentException("Unsupported HTTP method: {$method}");
        }
    }

    private function getEventTypesV1()
    {
        $response = $this->makeV1Request('/event-types', 'GET');
        
        if ($response->successful()) {
            return $this->normalizeEventTypesResponse($response->json(), 'v1');
        }
        
        return null;
    }

    private function checkAvailabilityV1($eventTypeId, $dateFrom, $dateTo, $timezone)
    {
        $response = $this->makeV1Request('/availability', 'GET', [
            'eventTypeId' => $eventTypeId,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'timeZone' => $timezone,
            'teamSlug' => $this->teamSlug
        ]);
        
        if ($response->successful()) {
            return $this->normalizeAvailabilityResponse($response->json(), 'v1');
        }
        
        return null;
    }

    private function bookAppointmentV1($eventTypeId, $startTime, $endTime, $customerData, $notes)
    {
        $data = [
            'eventTypeId' => (int)$eventTypeId,
            'start' => $startTime,
            'timeZone' => 'Europe/Berlin',
            'language' => 'de',
            'metadata' => [
                'source' => 'askproai',
                'via' => 'phone_ai'
            ],
            'responses' => [
                'name' => $customerData['name'] ?? 'Unbekannt',
                'email' => $customerData['email'] ?? 'kunde@example.com',
                'location' => 'phone'
            ]
        ];

        if ($notes) {
            $data['responses']['notes'] = $notes;
        }

        if (!empty($customerData['phone'])) {
            $phoneNote = "Telefon: " . $customerData['phone'];
            $data['responses']['notes'] = isset($data['responses']['notes']) 
                ? $phoneNote . "\n" . $data['responses']['notes']
                : $phoneNote;
        }

        $response = $this->makeV1Request('/bookings', 'POST', $data);
        
        if ($response->successful()) {
            return $this->normalizeBookingResponse($response->json(), 'v1');
        }
        
        return null;
    }

    // ===== V2 Specific Methods =====

    private function makeV2Request($endpoint, $method = 'GET', $data = null): Response
    {
        $url = $this->v2BaseUrl . $endpoint;
        
        $request = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'cal-api-version' => $this->v2ApiVersion,
            'Content-Type' => 'application/json'
        ]);
        
        switch ($method) {
            case 'GET':
                return $request->get($url, $data);
            case 'POST':
                return $request->post($url, $data);
            case 'PUT':
                return $request->put($url, $data);
            case 'DELETE':
                return $request->delete($url);
            default:
                throw new \InvalidArgumentException("Unsupported HTTP method: {$method}");
        }
    }

    private function checkAvailabilityV2($eventTypeId, $dateFrom, $dateTo, $timezone)
    {
        $response = $this->makeV2Request('/slots/available', 'GET', [
            'eventTypeId' => $eventTypeId,
            'startTime' => $dateFrom,
            'endTime' => $dateTo,
            'timeZone' => $timezone
        ]);
        
        if ($response->successful()) {
            return $this->normalizeAvailabilityResponse($response->json(), 'v2');
        }
        
        return null;
    }

    private function bookAppointmentV2($eventTypeId, $startTime, $customerData, $notes)
    {
        $data = [
            'eventTypeId' => (int)$eventTypeId,
            'start' => $startTime,
            'attendee' => [
                'name' => $customerData['name'] ?? 'Unbekannt',
                'email' => $customerData['email'] ?? 'kunde@example.com',
                'timeZone' => $customerData['timezone'] ?? 'Europe/Berlin',
            ],
            'metadata' => [
                'source' => 'askproai',
                'via' => 'phone_ai'
            ],
            'language' => 'de'
        ];

        if ($notes || !empty($customerData['phone'])) {
            $noteContent = $notes ?? '';
            if (!empty($customerData['phone'])) {
                $phoneNote = "Telefon: " . $customerData['phone'];
                $noteContent = $noteContent ? $phoneNote . "\n" . $noteContent : $phoneNote;
            }
            $data['metadata']['notes'] = $noteContent;
        }

        $response = $this->makeV2Request('/bookings', 'POST', $data);
        
        if ($response->successful()) {
            return $this->normalizeBookingResponse($response->json(), 'v2');
        }
        
        return null;
    }

    // ===== Response Normalization =====

    private function normalizeEventTypesResponse($response, $version)
    {
        if ($version === 'v2' && isset($response['data'])) {
            // V2 wraps responses in 'data' key
            return $response['data'];
        }
        
        return $response;
    }

    private function normalizeAvailabilityResponse($response, $version)
    {
        if ($version === 'v2') {
            // V2 has different structure for slots
            if (isset($response['data']['slots'])) {
                $normalizedSlots = [];
                foreach ($response['data']['slots'] as $date => $slots) {
                    foreach ($slots as $slot) {
                        $normalizedSlots[] = [
                            'time' => $slot['time'],
                            'date' => $date
                        ];
                    }
                }
                return ['slots' => $normalizedSlots];
            }
        }
        
        return $response;
    }

    private function normalizeBookingResponse($response, $version)
    {
        if ($version === 'v2' && isset($response['data'])) {
            $booking = $response['data'];
            
            // Normalize to v1-like structure for consistency
            return [
                'id' => $booking['id'] ?? null,
                'uid' => $booking['uid'] ?? null,
                'title' => $booking['title'] ?? null,
                'description' => $booking['description'] ?? null,
                'startTime' => $booking['start'] ?? null,
                'endTime' => $booking['end'] ?? null,
                'attendees' => $booking['attendees'] ?? [],
                'metadata' => $booking['metadata'] ?? [],
                'status' => $booking['status'] ?? 'ACCEPTED',
                'api_version' => 'v2'
            ];
        }
        
        // Add api_version to v1 responses
        if (is_array($response)) {
            $response['api_version'] = 'v1';
        }
        
        return $response;
    }

    /**
     * Test API connectivity and version compatibility
     */
    public function testConnection()
    {
        $results = [
            'v1' => false,
            'v2' => false,
            'recommended_version' => null
        ];
        
        // Test V1
        try {
            $v1Response = $this->makeV1Request('/event-types', 'GET');
            $results['v1'] = $v1Response->successful();
        } catch (\Exception $e) {
            Log::info('Cal.com V1 test failed', ['error' => $e->getMessage()]);
        }
        
        // Test V2
        try {
            $v2Response = $this->makeV2Request('/event-types', 'GET');
            $results['v2'] = $v2Response->successful();
        } catch (\Exception $e) {
            Log::info('Cal.com V2 test failed', ['error' => $e->getMessage()]);
        }
        
        // Recommend version
        if ($results['v2']) {
            $results['recommended_version'] = 'v2';
        } elseif ($results['v1']) {
            $results['recommended_version'] = 'v1';
        }
        
        return $results;
    }
}