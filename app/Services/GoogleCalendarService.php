<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * Google Calendar Service for Hair Salon Appointments
 * 
 * Handles integration with Google Calendar API for:
 * - Checking staff availability
 * - Creating/updating appointments
 * - Managing calendar events
 * - Handling multi-block appointments with breaks
 */
class GoogleCalendarService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://www.googleapis.com/calendar/v3';
    protected int $cacheMinutes = 15; // Cache availability for 15 minutes
    
    public function __construct()
    {
        $this->apiKey = config('services.google.calendar_api_key', env('GOOGLE_CALENDAR_API_KEY'));
        
        if (!$this->apiKey) {
            throw new \Exception('Google Calendar API key not configured');
        }
    }
    
    /**
     * Get available time slots for a calendar
     */
    public function getAvailableSlots(string $calendarId, Carbon $startDate, Carbon $endDate, int $durationMinutes = 30): array
    {
        $cacheKey = "calendar_slots_{$calendarId}_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}_{$durationMinutes}";
        
        return Cache::remember($cacheKey, $this->cacheMinutes, function () use ($calendarId, $startDate, $endDate, $durationMinutes) {
            try {
                // Get busy times from Google Calendar
                $busyTimes = $this->getBusyTimes($calendarId, $startDate, $endDate);
                
                // Generate available slots
                $availableSlots = $this->generateAvailableSlots($busyTimes, $startDate, $endDate, $durationMinutes);
                
                return $availableSlots;
                
            } catch (\Exception $e) {
                Log::error('GoogleCalendarService::getAvailableSlots error', [
                    'calendar_id' => $calendarId,
                    'error' => $e->getMessage()
                ]);
                
                // Return default business hours as fallback
                return $this->getDefaultBusinessHours($startDate, $endDate, $durationMinutes);
            }
        });
    }
    
    /**
     * Get busy times from Google Calendar
     */
    protected function getBusyTimes(string $calendarId, Carbon $startDate, Carbon $endDate): array
    {
        $url = "{$this->baseUrl}/freeBusy";
        
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->getAccessToken()}",
            'Content-Type' => 'application/json'
        ])->post($url, [
            'timeMin' => $startDate->toIso8601String(),
            'timeMax' => $endDate->toIso8601String(),
            'items' => [
                ['id' => $calendarId]
            ]
        ]);
        
        if (!$response->successful()) {
            throw new \Exception("Google Calendar API error: " . $response->body());
        }
        
        $data = $response->json();
        $busyTimes = $data['calendars'][$calendarId]['busy'] ?? [];
        
        // Convert to Carbon instances
        $busyPeriods = [];
        foreach ($busyTimes as $busy) {
            $busyPeriods[] = [
                'start' => Carbon::parse($busy['start']),
                'end' => Carbon::parse($busy['end'])
            ];
        }
        
        return $busyPeriods;
    }
    
    /**
     * Generate available slots based on busy times
     */
    protected function generateAvailableSlots(array $busyTimes, Carbon $startDate, Carbon $endDate, int $durationMinutes): array
    {
        $slots = [];
        $current = $startDate->copy();
        
        while ($current->lessThan($endDate)) {
            // Skip non-business days (Sunday)
            if ($current->dayOfWeek === 0) {
                $current->addDay()->startOfDay()->addHours(9);
                continue;
            }
            
            // Set business hours
            $businessStart = $current->copy()->hour(9)->minute(0);
            $businessEnd = $current->copy()->hour($current->dayOfWeek === 6 ? 16 : ($current->dayOfWeek >= 4 ? 20 : 18))->minute(0);
            
            // Generate slots for this day
            $daySlots = $this->generateDaySlots($businessStart, $businessEnd, $busyTimes, $durationMinutes);
            $slots = array_merge($slots, $daySlots);
            
            $current->addDay()->startOfDay();
        }
        
        return $slots;
    }
    
    /**
     * Generate slots for a specific day
     */
    protected function generateDaySlots(Carbon $dayStart, Carbon $dayEnd, array $busyTimes, int $durationMinutes): array
    {
        $slots = [];
        $current = $dayStart->copy();
        
        while ($current->copy()->addMinutes($durationMinutes)->lessThanOrEqualTo($dayEnd)) {
            $slotStart = $current->copy();
            $slotEnd = $current->copy()->addMinutes($durationMinutes);
            
            // Check if this slot conflicts with any busy time
            $isAvailable = true;
            foreach ($busyTimes as $busy) {
                if ($this->timesOverlap($slotStart, $slotEnd, $busy['start'], $busy['end'])) {
                    $isAvailable = false;
                    break;
                }
            }
            
            if ($isAvailable) {
                $slots[] = [
                    'date' => $slotStart->format('Y-m-d'),
                    'time' => $slotStart->format('H:i'),
                    'datetime' => $slotStart->format('Y-m-d H:i:s')
                ];
            }
            
            $current->addMinutes(30); // Check every 30 minutes
        }
        
        return $slots;
    }
    
    /**
     * Check if two time periods overlap
     */
    protected function timesOverlap(Carbon $start1, Carbon $end1, Carbon $start2, Carbon $end2): bool
    {
        return $start1->lessThan($end2) && $end1->greaterThan($start2);
    }
    
    /**
     * Check if a specific time slot is available
     */
    public function isTimeAvailable(string $calendarId, Carbon $startTime, int $durationMinutes): bool
    {
        try {
            $endTime = $startTime->copy()->addMinutes($durationMinutes);
            $busyTimes = $this->getBusyTimes($calendarId, $startTime->copy()->subHour(), $endTime->copy()->addHour());
            
            foreach ($busyTimes as $busy) {
                if ($this->timesOverlap($startTime, $endTime, $busy['start'], $busy['end'])) {
                    return false;
                }
            }
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('GoogleCalendarService::isTimeAvailable error', [
                'calendar_id' => $calendarId,
                'start_time' => $startTime->toIso8601String(),
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Create a new calendar event
     */
    public function createEvent(string $calendarId, array $eventData): ?string
    {
        try {
            $url = "{$this->baseUrl}/calendars/{$calendarId}/events";
            
            $event = [
                'summary' => $eventData['summary'],
                'description' => $eventData['description'] ?? '',
                'start' => [
                    'dateTime' => $eventData['start'],
                    'timeZone' => 'Europe/Berlin'
                ],
                'end' => [
                    'dateTime' => $eventData['end'],
                    'timeZone' => 'Europe/Berlin'
                ],
                'reminders' => [
                    'useDefault' => false,
                    'overrides' => [
                        ['method' => 'email', 'minutes' => 1440], // 24 hours
                        ['method' => 'popup', 'minutes' => 30]    // 30 minutes
                    ]
                ]
            ];
            
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->getAccessToken()}",
                'Content-Type' => 'application/json'
            ])->post($url, $event);
            
            if (!$response->successful()) {
                throw new \Exception("Failed to create calendar event: " . $response->body());
            }
            
            $eventResponse = $response->json();
            
            // Clear cache for this calendar
            $this->clearCalendarCache($calendarId);
            
            Log::info('Calendar event created successfully', [
                'calendar_id' => $calendarId,
                'event_id' => $eventResponse['id'],
                'summary' => $eventData['summary']
            ]);
            
            return $eventResponse['id'];
            
        } catch (\Exception $e) {
            Log::error('GoogleCalendarService::createEvent error', [
                'calendar_id' => $calendarId,
                'event_data' => $eventData,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }
    
    /**
     * Update an existing calendar event
     */
    public function updateEvent(string $calendarId, string $eventId, array $eventData): bool
    {
        try {
            $url = "{$this->baseUrl}/calendars/{$calendarId}/events/{$eventId}";
            
            $event = [
                'summary' => $eventData['summary'],
                'description' => $eventData['description'] ?? '',
                'start' => [
                    'dateTime' => $eventData['start'],
                    'timeZone' => 'Europe/Berlin'
                ],
                'end' => [
                    'dateTime' => $eventData['end'],
                    'timeZone' => 'Europe/Berlin'
                ]
            ];
            
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->getAccessToken()}",
                'Content-Type' => 'application/json'
            ])->put($url, $event);
            
            if (!$response->successful()) {
                throw new \Exception("Failed to update calendar event: " . $response->body());
            }
            
            // Clear cache for this calendar
            $this->clearCalendarCache($calendarId);
            
            Log::info('Calendar event updated successfully', [
                'calendar_id' => $calendarId,
                'event_id' => $eventId
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('GoogleCalendarService::updateEvent error', [
                'calendar_id' => $calendarId,
                'event_id' => $eventId,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Delete a calendar event
     */
    public function deleteEvent(string $calendarId, string $eventId): bool
    {
        try {
            $url = "{$this->baseUrl}/calendars/{$calendarId}/events/{$eventId}";
            
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->getAccessToken()}"
            ])->delete($url);
            
            if (!$response->successful() && $response->status() !== 404) {
                throw new \Exception("Failed to delete calendar event: " . $response->body());
            }
            
            // Clear cache for this calendar
            $this->clearCalendarCache($calendarId);
            
            Log::info('Calendar event deleted successfully', [
                'calendar_id' => $calendarId,
                'event_id' => $eventId
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('GoogleCalendarService::deleteEvent error', [
                'calendar_id' => $calendarId,
                'event_id' => $eventId,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Get access token (simplified - in production, use OAuth2)
     */
    protected function getAccessToken(): string
    {
        // This is a placeholder. In production, you would:
        // 1. Use OAuth2 flow to get access token
        // 2. Store and refresh tokens as needed
        // 3. Use service account for server-to-server communication
        
        $serviceAccountToken = config('services.google.service_account_token');
        if ($serviceAccountToken) {
            return $serviceAccountToken;
        }
        
        // Fallback to API key for read-only operations
        return $this->apiKey;
    }
    
    /**
     * Clear calendar cache
     */
    protected function clearCalendarCache(string $calendarId): void
    {
        $tags = ["calendar_{$calendarId}"];
        Cache::tags($tags)->flush();
        
        // Also clear general availability cache
        Cache::forget("calendar_slots_{$calendarId}*");
    }
    
    /**
     * Get default business hours as fallback
     */
    protected function getDefaultBusinessHours(Carbon $startDate, Carbon $endDate, int $durationMinutes): array
    {
        $slots = [];
        $current = $startDate->copy();
        
        while ($current->lessThan($endDate)) {
            // Skip Sunday
            if ($current->dayOfWeek === 0) {
                $current->addDay();
                continue;
            }
            
            // Business hours
            $dayStart = $current->copy()->hour(9)->minute(0);
            $dayEnd = $current->copy()->hour($current->dayOfWeek === 6 ? 16 : 18)->minute(0);
            
            // Generate hourly slots
            while ($dayStart->lessThan($dayEnd)) {
                $slots[] = [
                    'date' => $dayStart->format('Y-m-d'),
                    'time' => $dayStart->format('H:i'),
                    'datetime' => $dayStart->format('Y-m-d H:i:s')
                ];
                $dayStart->addMinutes($durationMinutes);
            }
            
            $current->addDay();
        }
        
        return array_slice($slots, 0, 20); // Limit for fallback
    }
    
    /**
     * Get calendar events for debugging
     */
    public function getEvents(string $calendarId, Carbon $startDate, Carbon $endDate): array
    {
        try {
            $url = "{$this->baseUrl}/calendars/{$calendarId}/events";
            
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->getAccessToken()}"
            ])->get($url, [
                'timeMin' => $startDate->toIso8601String(),
                'timeMax' => $endDate->toIso8601String(),
                'orderBy' => 'startTime',
                'singleEvents' => 'true'
            ]);
            
            if (!$response->successful()) {
                throw new \Exception("Failed to get calendar events: " . $response->body());
            }
            
            return $response->json()['items'] ?? [];
            
        } catch (\Exception $e) {
            Log::error('GoogleCalendarService::getEvents error', [
                'calendar_id' => $calendarId,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }
    
    /**
     * Validate calendar access
     */
    public function validateCalendarAccess(string $calendarId): bool
    {
        try {
            $url = "{$this->baseUrl}/calendars/{$calendarId}";
            
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->getAccessToken()}"
            ])->get($url);
            
            return $response->successful();
            
        } catch (\Exception $e) {
            Log::error('GoogleCalendarService::validateCalendarAccess error', [
                'calendar_id' => $calendarId,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
}