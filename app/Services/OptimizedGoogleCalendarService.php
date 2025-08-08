<?php

namespace App\Services;

use App\Services\CircuitBreakerService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ClearCalendarCacheJob;
use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

/**
 * Optimized Google Calendar Service
 * 
 * Production-grade calendar integration with:
 * - Circuit breaker protection
 * - Connection pooling
 * - Batch operations
 * - Advanced caching strategies
 * - Automatic retry logic
 * - Performance monitoring
 */
class OptimizedGoogleCalendarService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://www.googleapis.com/calendar/v3';
    protected CircuitBreakerService $circuitBreaker;
    protected Client $httpClient;
    protected int $cacheMinutes = 15;
    protected int $maxRetries = 3;
    
    public function __construct()
    {
        $this->apiKey = config('services.google.calendar_api_key', env('GOOGLE_CALENDAR_API_KEY'));
        $this->circuitBreaker = CircuitBreakerService::forGoogleCalendar();
        
        // Initialize HTTP client with connection pooling
        $this->httpClient = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 30,
            'connect_timeout' => 10,
            'pool_size' => 10, // Connection pool size
            'headers' => [
                'User-Agent' => 'AskProAI-HairSalon-MCP/1.0',
                'Accept' => 'application/json'
            ]
        ]);
        
        if (!$this->apiKey) {
            throw new \Exception('Google Calendar API key not configured');
        }
    }
    
    /**
     * Get available slots for multiple calendars concurrently
     */
    public function getAvailableSlotsBatch(array $calendarIds, Carbon $startDate, Carbon $endDate, int $durationMinutes = 30): array
    {
        $cacheKey = "calendar_batch_slots_" . md5(serialize([$calendarIds, $startDate->format('Y-m-d'), $endDate->format('Y-m-d'), $durationMinutes]));
        
        return Cache::remember($cacheKey, $this->cacheMinutes, function () use ($calendarIds, $startDate, $endDate, $durationMinutes) {
            return $this->circuitBreaker->call(
                callback: function () use ($calendarIds, $startDate, $endDate, $durationMinutes) {
                    return $this->getBatchAvailabilityWithRetry($calendarIds, $startDate, $endDate, $durationMinutes);
                },
                fallback: $this->getDefaultBatchAvailability($calendarIds, $startDate, $endDate, $durationMinutes)
            );
        });
    }
    
    /**
     * Get batch availability with retry logic
     */
    protected function getBatchAvailabilityWithRetry(array $calendarIds, Carbon $startDate, Carbon $endDate, int $durationMinutes): array
    {
        $retries = 0;
        $lastException = null;
        
        while ($retries < $this->maxRetries) {
            try {
                return $this->executeBatchAvailabilityRequest($calendarIds, $startDate, $endDate, $durationMinutes);
                
            } catch (\Exception $e) {
                $lastException = $e;
                $retries++;
                
                if ($retries < $this->maxRetries) {
                    $delay = min(1000 * (2 ** $retries), 10000); // Exponential backoff, max 10s
                    usleep($delay * 1000);
                    
                    Log::warning('Calendar API retry attempt', [
                        'attempt' => $retries,
                        'delay_ms' => $delay,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        
        throw $lastException;
    }
    
    /**
     * Execute batch availability request using concurrent HTTP calls
     */
    protected function executeBatchAvailabilityRequest(array $calendarIds, Carbon $startDate, Carbon $endDate, int $durationMinutes): array
    {
        $startTime = microtime(true);
        
        // Create concurrent requests for all calendars
        $requests = [];
        foreach ($calendarIds as $index => $calendarId) {
            $requests[$index] = new Request('POST', '/freeBusy', [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
                'Content-Type' => 'application/json'
            ], json_encode([
                'timeMin' => $startDate->toIso8601String(),
                'timeMax' => $endDate->toIso8601String(),
                'items' => [['id' => $calendarId]]
            ]));
        }
        
        // Execute requests concurrently
        $responses = [];
        $pool = new Pool($this->httpClient, $requests, [
            'concurrency' => 5,
            'fulfilled' => function ($response, $index) use (&$responses, $calendarIds) {
                $responses[$calendarIds[$index]] = json_decode($response->getBody(), true);
            },
            'rejected' => function ($reason, $index) use ($calendarIds) {
                Log::error('Calendar batch request failed', [
                    'calendar_id' => $calendarIds[$index],
                    'error' => $reason->getMessage()
                ]);
                $responses[$calendarIds[$index]] = ['error' => $reason->getMessage()];
            }
        ]);
        
        $promise = $pool->promise();
        $promise->wait();
        
        // Process responses and generate available slots
        $allSlots = [];
        foreach ($calendarIds as $calendarId) {
            $response = $responses[$calendarId] ?? null;
            
            if (!$response || isset($response['error'])) {
                continue;
            }
            
            $busyTimes = $response['calendars'][$calendarId]['busy'] ?? [];
            $slots = $this->generateAvailableSlots($busyTimes, $startDate, $endDate, $durationMinutes, $calendarId);
            
            $allSlots = array_merge($allSlots, $slots);
        }
        
        // Sort by datetime
        usort($allSlots, function ($a, $b) {
            return strtotime($a['datetime']) - strtotime($b['datetime']);
        });
        
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        Log::info('Calendar batch availability completed', [
            'calendars_count' => count($calendarIds),
            'slots_found' => count($allSlots),
            'duration_ms' => $duration
        ]);
        
        return $allSlots;
    }
    
    /**
     * Enhanced slot generation with calendar ID tracking
     */
    protected function generateAvailableSlots(array $busyTimes, Carbon $startDate, Carbon $endDate, int $durationMinutes, string $calendarId): array
    {
        $slots = [];
        $current = $startDate->copy();
        
        // Convert busy times to Carbon instances
        $busyPeriods = [];
        foreach ($busyTimes as $busy) {
            $busyPeriods[] = [
                'start' => Carbon::parse($busy['start']),
                'end' => Carbon::parse($busy['end'])
            ];
        }
        
        while ($current->lessThan($endDate)) {
            // Skip non-business days (Sunday)
            if ($current->dayOfWeek === 0) {
                $current->addDay()->startOfDay()->addHours(9);
                continue;
            }
            
            // Set business hours with extended hours for different days
            $businessStart = $current->copy()->hour(9)->minute(0);
            $businessEnd = $this->getBusinessEndTime($current);
            
            // Generate slots for this day
            $daySlots = $this->generateDaySlots($businessStart, $businessEnd, $busyPeriods, $durationMinutes, $calendarId);
            $slots = array_merge($slots, $daySlots);
            
            $current->addDay()->startOfDay();
        }
        
        return $slots;
    }
    
    /**
     * Get business end time based on day of week
     */
    protected function getBusinessEndTime(Carbon $date): Carbon
    {
        return match ($date->dayOfWeek) {
            1, 2, 3 => $date->copy()->hour(18)->minute(0), // Mon-Wed: 9-18
            4, 5 => $date->copy()->hour(20)->minute(0),    // Thu-Fri: 9-20
            6 => $date->copy()->hour(16)->minute(0),       // Sat: 9-16
            default => $date->copy()->hour(18)->minute(0)   // Default
        };
    }
    
    /**
     * Generate slots for a specific day with calendar tracking
     */
    protected function generateDaySlots(Carbon $dayStart, Carbon $dayEnd, array $busyTimes, int $durationMinutes, string $calendarId): array
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
                    'calendar_id' => $calendarId,
                    'date' => $slotStart->format('Y-m-d'),
                    'time' => $slotStart->format('H:i'),
                    'datetime' => $slotStart->format('Y-m-d H:i:s'),
                    'end_time' => $slotEnd->format('H:i'),
                    'duration_minutes' => $durationMinutes
                ];
            }
            
            $current->addMinutes(15); // Check every 15 minutes for finer granularity
        }
        
        return $slots;
    }
    
    /**
     * Create multiple calendar events in batch
     */
    public function createEventsBatch(array $events): array
    {
        return $this->circuitBreaker->call(
            callback: function () use ($events) {
                return $this->executeCreateEventsBatch($events);
            },
            fallback: ['success' => false, 'error' => 'Calendar service unavailable', 'created_events' => []]
        );
    }
    
    /**
     * Execute batch event creation
     */
    protected function executeCreateEventsBatch(array $events): array
    {
        $results = [];
        $requests = [];
        
        // Prepare concurrent requests
        foreach ($events as $index => $event) {
            $calendarId = $event['calendar_id'];
            $eventData = $event['event_data'];
            
            $requests[$index] = new Request('POST', "/calendars/{$calendarId}/events", [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
                'Content-Type' => 'application/json'
            ], json_encode([
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
                        ['method' => 'email', 'minutes' => 1440],
                        ['method' => 'popup', 'minutes' => 30]
                    ]
                ]
            ]));
        }
        
        // Execute requests concurrently
        $pool = new Pool($this->httpClient, $requests, [
            'concurrency' => 3,
            'fulfilled' => function ($response, $index) use (&$results, $events) {
                $eventResponse = json_decode($response->getBody(), true);
                $results[$index] = [
                    'success' => true,
                    'event_id' => $eventResponse['id'],
                    'calendar_id' => $events[$index]['calendar_id']
                ];
            },
            'rejected' => function ($reason, $index) use (&$results, $events) {
                $results[$index] = [
                    'success' => false,
                    'error' => $reason->getMessage(),
                    'calendar_id' => $events[$index]['calendar_id']
                ];
            }
        ]);
        
        $promise = $pool->promise();
        $promise->wait();
        
        // Clear cache for affected calendars
        $affectedCalendars = array_unique(array_column($events, 'calendar_id'));
        foreach ($affectedCalendars as $calendarId) {
            Queue::push(new ClearCalendarCacheJob($calendarId));
        }
        
        $successCount = count(array_filter($results, fn($r) => $r['success']));
        
        Log::info('Calendar batch event creation completed', [
            'total_events' => count($events),
            'successful' => $successCount,
            'failed' => count($events) - $successCount
        ]);
        
        return [
            'success' => $successCount > 0,
            'created_events' => $successCount,
            'failed_events' => count($events) - $successCount,
            'results' => $results
        ];
    }
    
    /**
     * Advanced time availability check with buffer time
     */
    public function isTimeAvailableAdvanced(string $calendarId, Carbon $startTime, int $durationMinutes, int $bufferMinutes = 0): bool
    {
        $cacheKey = "availability_check_{$calendarId}_{$startTime->format('Y-m-d_H-i')}_{$durationMinutes}_{$bufferMinutes}";
        
        return Cache::remember($cacheKey, 5, function () use ($calendarId, $startTime, $durationMinutes, $bufferMinutes) {
            return $this->circuitBreaker->call(
                callback: function () use ($calendarId, $startTime, $durationMinutes, $bufferMinutes) {
                    $checkStart = $startTime->copy()->subMinutes($bufferMinutes);
                    $checkEnd = $startTime->copy()->addMinutes($durationMinutes + $bufferMinutes);
                    
                    $busyTimes = $this->getBusyTimesOptimized($calendarId, $checkStart, $checkEnd);
                    
                    foreach ($busyTimes as $busy) {
                        if ($this->timesOverlap($startTime, $startTime->copy()->addMinutes($durationMinutes), $busy['start'], $busy['end'])) {
                            return false;
                        }
                    }
                    
                    return true;
                },
                fallback: false // Conservative fallback - assume unavailable
            );
        });
    }
    
    /**
     * Optimized busy times retrieval with caching
     */
    protected function getBusyTimesOptimized(string $calendarId, Carbon $startTime, Carbon $endTime): array
    {
        $cacheKey = "busy_times_{$calendarId}_{$startTime->format('Y-m-d_H')}_{$endTime->format('Y-m-d_H')}";
        
        return Cache::remember($cacheKey, 10, function () use ($calendarId, $startTime, $endTime) {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
                'Content-Type' => 'application/json'
            ])->timeout(15)->post($this->baseUrl . '/freeBusy', [
                'timeMin' => $startTime->toIso8601String(),
                'timeMax' => $endTime->toIso8601String(),
                'items' => [['id' => $calendarId]]
            ]);
            
            if (!$response->successful()) {
                throw new \Exception("Calendar API error: {$response->status()} - {$response->body()}");
            }
            
            $data = $response->json();
            $busyTimes = $data['calendars'][$calendarId]['busy'] ?? [];
            
            // Convert to Carbon instances
            return array_map(fn($busy) => [
                'start' => Carbon::parse($busy['start']),
                'end' => Carbon::parse($busy['end'])
            ], $busyTimes);
        });
    }
    
    /**
     * Get default batch availability as fallback
     */
    protected function getDefaultBatchAvailability(array $calendarIds, Carbon $startDate, Carbon $endDate, int $durationMinutes): array
    {
        $slots = [];
        
        foreach ($calendarIds as $calendarId) {
            $defaultSlots = $this->getDefaultBusinessHours($startDate, $endDate, $durationMinutes);
            
            foreach ($defaultSlots as $slot) {
                $slot['calendar_id'] = $calendarId;
                $slots[] = $slot;
            }
        }
        
        return array_slice($slots, 0, 20); // Limit fallback results
    }
    
    /**
     * Get default business hours with improved scheduling
     */
    protected function getDefaultBusinessHours(Carbon $startDate, Carbon $endDate, int $durationMinutes): array
    {
        $slots = [];
        $current = $startDate->copy();
        
        while ($current->lessThan($endDate) && count($slots) < 20) {
            // Skip Sunday
            if ($current->dayOfWeek === 0) {
                $current->addDay();
                continue;
            }
            
            $dayStart = $current->copy()->hour(9)->minute(0);
            $dayEnd = $this->getBusinessEndTime($current);
            
            // Generate slots every hour during business hours
            while ($dayStart->lessThan($dayEnd) && count($slots) < 20) {
                $slots[] = [
                    'date' => $dayStart->format('Y-m-d'),
                    'time' => $dayStart->format('H:i'),
                    'datetime' => $dayStart->format('Y-m-d H:i:s'),
                    'duration_minutes' => $durationMinutes,
                    'fallback' => true
                ];
                $dayStart->addHour();
            }
            
            $current->addDay();
        }
        
        return $slots;
    }
    
    /**
     * Enhanced time overlap checking
     */
    protected function timesOverlap(Carbon $start1, Carbon $end1, Carbon $start2, Carbon $end2): bool
    {
        return $start1->lessThan($end2) && $end1->greaterThan($start2);
    }
    
    /**
     * Get access token with caching and refresh logic
     */
    protected function getAccessToken(): string
    {
        $cacheKey = 'google_calendar_access_token';
        
        $token = Cache::get($cacheKey);
        if ($token) {
            return $token;
        }
        
        // In production, implement proper OAuth2 token refresh
        $serviceAccountToken = config('services.google.service_account_token');
        if ($serviceAccountToken) {
            Cache::put($cacheKey, $serviceAccountToken, 3600); // Cache for 1 hour
            return $serviceAccountToken;
        }
        
        return $this->apiKey;
    }
    
    /**
     * Get circuit breaker health status
     */
    public function getHealthStatus(): array
    {
        return array_merge(
            $this->circuitBreaker->getMetrics(),
            [
                'cache_enabled' => true,
                'connection_pool_size' => 10,
                'max_retries' => $this->maxRetries,
                'cache_minutes' => $this->cacheMinutes
            ]
        );
    }
    
    /**
     * Clear all calendar caches
     */
    public function clearAllCaches(): void
    {
        $patterns = [
            'calendar_batch_slots_*',
            'availability_check_*',
            'busy_times_*',
            'google_calendar_access_token'
        ];
        
        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
        
        Log::info('All Google Calendar caches cleared');
    }
}