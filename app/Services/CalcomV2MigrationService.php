<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Cal.com V2 Migration Service
 * 
 * This service provides v2 API compatibility while maintaining
 * backward compatibility with existing v1 implementations.
 */
class CalcomV2MigrationService
{
    private string $baseUrl = 'https://api.cal.com/v2';
    private string $apiVersion = '2024-06-14'; // Correct API version
    
    /**
     * Get default headers for v2 API requests
     */
    private function getHeaders(string $apiKey): array
    {
        return [
            'Authorization' => 'Bearer ' . $apiKey,
            'cal-api-version' => $this->apiVersion,
            'Content-Type' => 'application/json',
        ];
    }
    
    /**
     * Fetch event types using v2 API
     */
    public function getEventTypes(string $apiKey): ?array
    {
        try {
            $response = Http::withHeaders($this->getHeaders($apiKey))
                ->get($this->baseUrl . '/event-types');
            
            if ($response->successful()) {
                $data = $response->json();
                
                // Extract event types from v2 structure
                $eventTypes = [];
                if (isset($data['data']['eventTypeGroups'])) {
                    foreach ($data['data']['eventTypeGroups'] as $group) {
                        if (isset($group['eventTypes'])) {
                            $eventTypes = array_merge($eventTypes, $group['eventTypes']);
                        }
                    }
                }
                
                Log::info('V2 API: Retrieved event types', ['count' => count($eventTypes)]);
                return $eventTypes;
            }
            
            Log::error('V2 API: Failed to fetch event types', [
                'status' => $response->status(),
                'error' => $response->body()
            ]);
            
            return null;
        } catch (\Exception $e) {
            Log::error('V2 API: Exception fetching event types', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Check availability using v2 API
     * 
     * @param string $apiKey
     * @param int $eventTypeId
     * @param string $startDate Format: Y-m-d
     * @param string $endDate Format: Y-m-d
     * @param string|null $timezone
     * @return array|null
     */
    public function checkAvailability(
        string $apiKey,
        int $eventTypeId,
        string $startDate,
        string $endDate,
        ?string $timezone = null
    ): ?array {
        try {
            // Convert dates to ISO8601 format required by v2
            $startDateTime = Carbon::parse($startDate)->startOfDay();
            $endDateTime = Carbon::parse($endDate)->endOfDay();
            
            if (!$timezone) {
                $timezone = config('app.timezone', 'Europe/Berlin');
            }
            
            $params = [
                'eventTypeId' => $eventTypeId,
                'startTime' => $startDateTime->toIso8601String(),
                'endTime' => $endDateTime->toIso8601String(),
                'timeZone' => $timezone,
            ];
            
            $response = Http::withHeaders($this->getHeaders($apiKey))
                ->get($this->baseUrl . '/slots/available', $params);
            
            if ($response->successful()) {
                $data = $response->json();
                
                // Extract slots from v2 response
                $slots = $data['data']['slots'] ?? [];
                
                Log::info('V2 API: Retrieved availability slots', [
                    'eventTypeId' => $eventTypeId,
                    'slots_count' => count($slots)
                ]);
                
                return [
                    'available' => !empty($slots),
                    'slots' => $this->normalizeSlots($slots)
                ];
            }
            
            Log::error('V2 API: Failed to check availability', [
                'status' => $response->status(),
                'error' => $response->body()
            ]);
            
            return null;
        } catch (\Exception $e) {
            Log::error('V2 API: Exception checking availability', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Create a booking using v2 API
     * 
     * @param string $apiKey
     * @param array $bookingData
     * @return array|null
     */
    public function createBooking(string $apiKey, array $bookingData): ?array
    {
        try {
            // Transform v1 format to v2 format
            $v2Data = $this->transformBookingDataToV2($bookingData);
            
            $response = Http::withHeaders($this->getHeaders($apiKey))
                ->post($this->baseUrl . '/bookings', $v2Data);
            
            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('V2 API: Booking created successfully', [
                    'booking_uid' => $data['data']['uid'] ?? null
                ]);
                
                return $data['data'] ?? $data;
            }
            
            Log::error('V2 API: Failed to create booking', [
                'status' => $response->status(),
                'error' => $response->body(),
                'request' => $v2Data
            ]);
            
            return null;
        } catch (\Exception $e) {
            Log::error('V2 API: Exception creating booking', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Transform v1 booking data to v2 format
     */
    private function transformBookingDataToV2(array $v1Data): array
    {
        // Basic v2 structure
        $v2Data = [
            'eventTypeId' => $v1Data['eventTypeId'],
            'start' => $v1Data['start'], // Should already be ISO8601
            'attendee' => [
                'name' => $v1Data['responses']['name'] ?? $v1Data['name'] ?? '',
                'email' => $v1Data['responses']['email'] ?? $v1Data['email'] ?? '',
                'timeZone' => $v1Data['timeZone'] ?? config('app.timezone', 'Europe/Berlin'),
            ],
            'language' => $v1Data['language'] ?? 'de',
        ];
        
        // Add location if provided
        if (isset($v1Data['responses']['location'])) {
            $v2Data['location'] = $v1Data['responses']['location'];
        } elseif (isset($v1Data['location'])) {
            $v2Data['location'] = $v1Data['location'];
        }
        
        // Add custom fields/responses if any
        if (isset($v1Data['responses']) && is_array($v1Data['responses'])) {
            $customFields = [];
            foreach ($v1Data['responses'] as $key => $value) {
                if (!in_array($key, ['name', 'email', 'location'])) {
                    $customFields[$key] = $value;
                }
            }
            if (!empty($customFields)) {
                $v2Data['customFields'] = $customFields;
            }
        }
        
        // Add metadata if provided
        if (isset($v1Data['metadata'])) {
            $v2Data['metadata'] = $v1Data['metadata'];
        }
        
        return $v2Data;
    }
    
    /**
     * Normalize v2 slots to a consistent format
     */
    private function normalizeSlots(array $slots): array
    {
        return array_map(function ($slot) {
            return [
                'time' => $slot['time'] ?? $slot['start'] ?? '',
                'start' => $slot['start'] ?? $slot['time'] ?? '',
                'end' => $slot['end'] ?? null,
                'available' => true,
            ];
        }, $slots);
    }
    
    /**
     * Test API connection and permissions
     */
    public function testConnection(string $apiKey): array
    {
        $results = [
            'api_version' => 'v2',
            'authenticated' => false,
            'endpoints' => []
        ];
        
        // Test authentication
        $response = Http::withHeaders($this->getHeaders($apiKey))
            ->get($this->baseUrl . '/event-types');
        
        $results['authenticated'] = $response->successful();
        $results['auth_status'] = $response->status();
        
        if ($results['authenticated']) {
            // Test various endpoints
            $endpoints = [
                'event-types' => '/event-types',
                'slots' => '/slots/available',
                'bookings' => '/bookings',
            ];
            
            foreach ($endpoints as $name => $endpoint) {
                $testResponse = Http::withHeaders($this->getHeaders($apiKey))
                    ->get($this->baseUrl . $endpoint);
                
                $results['endpoints'][$name] = [
                    'status' => $testResponse->status(),
                    'accessible' => $testResponse->status() !== 403
                ];
            }
        }
        
        return $results;
    }
}