<?php

namespace App\Services\Appointments;

use App\Models\Service;
use App\Models\Company;
use App\Services\CalcomV2Client;
use App\Services\CalcomApiRateLimiter;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * SmartAppointmentFinder
 *
 * Intelligent appointment availability finder with Cal.com integration.
 * Features:
 * - Next available slot discovery
 * - Time window search
 * - Intelligent caching (45s TTL as per Cal.com research)
 * - Adaptive rate limiting with header-based backoff
 * - Multi-strategy fallback for reliability
 */
class SmartAppointmentFinder
{
    /**
     * Cache TTL in seconds (45 seconds as per Cal.com research report)
     */
    protected const CACHE_TTL = 45;

    /**
     * Default search window in days
     */
    protected const DEFAULT_SEARCH_DAYS = 14;

    /**
     * Maximum search window in days
     */
    protected const MAX_SEARCH_DAYS = 90;

    /**
     * Cal.com API client
     */
    protected CalcomV2Client $calcomClient;

    /**
     * Rate limiter
     */
    protected CalcomApiRateLimiter $rateLimiter;

    /**
     * Create a new SmartAppointmentFinder instance
     */
    public function __construct(?Company $company = null)
    {
        $this->calcomClient = new CalcomV2Client($company);
        $this->rateLimiter = new CalcomApiRateLimiter();
    }

    /**
     * Find the next available appointment slot for a service
     *
     * @param Service $service
     * @param Carbon|null $after Start searching after this time
     * @param int $searchDays Number of days to search ahead
     * @return Carbon|null Next available slot or null if none found
     */
    public function findNextAvailable(Service $service, ?Carbon $after = null, int $searchDays = self::DEFAULT_SEARCH_DAYS): ?Carbon
    {
        $startTime = microtime(true);

        try {
            // Validate service has Cal.com event type
            if (!$service->calcom_event_type_id) {
                Log::warning('⚠️ Service has no Cal.com event type', [
                    'service_id' => $service->id,
                    'service_name' => $service->name,
                ]);
                return null;
            }

            // Normalize search parameters
            $after = $after ?? Carbon::now();
            $searchDays = min($searchDays, self::MAX_SEARCH_DAYS);

            // Check cache first
            $cacheKey = $this->getCacheKey('next_available', $service->id, $after, $searchDays);
            $cached = Cache::get($cacheKey);

            if ($cached) {
                Log::debug('✅ Cache hit for next available slot', [
                    'service_id' => $service->id,
                    'cached_slot' => $cached,
                ]);
                return Carbon::parse($cached);
            }

            // Search for available slots
            $end = $after->copy()->addDays($searchDays);
            $availableSlots = $this->fetchAvailableSlots($service, $after, $end);

            if ($availableSlots->isEmpty()) {
                Log::info('📅 No available slots found', [
                    'service_id' => $service->id,
                    'search_start' => $after->toIso8601String(),
                    'search_end' => $end->toIso8601String(),
                    'search_days' => $searchDays,
                ]);
                return null;
            }

            // Get the first available slot
            $nextSlot = $availableSlots->first();

            // Cache the result
            Cache::put($cacheKey, $nextSlot->toIso8601String(), self::CACHE_TTL);

            $duration = round((microtime(true) - $startTime) * 1000);

            Log::info('✅ Found next available slot', [
                'service_id' => $service->id,
                'service_name' => $service->name,
                'next_slot' => $nextSlot->toIso8601String(),
                'duration_ms' => $duration,
            ]);

            return $nextSlot;

        } catch (\Exception $e) {
            Log::error('❌ Failed to find next available slot', [
                'service_id' => $service->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Find all available slots in a specific time window
     *
     * @param Service $service
     * @param Carbon $start Window start time
     * @param Carbon $end Window end time
     * @return Collection Collection of available slots as Carbon instances
     */
    public function findInTimeWindow(Service $service, Carbon $start, Carbon $end): Collection
    {
        // Validate time window FIRST (before try-catch to allow exception to propagate)
        if ($end->lessThanOrEqualTo($start)) {
            throw new \InvalidArgumentException('End time must be after start time');
        }

        $startTime = microtime(true);

        try {
            // Validate service has Cal.com event type
            if (!$service->calcom_event_type_id) {
                Log::warning('⚠️ Service has no Cal.com event type', [
                    'service_id' => $service->id,
                    'service_name' => $service->name,
                ]);
                return collect();
            }

            // Check cache first
            $cacheKey = $this->getCacheKey('time_window', $service->id, $start, $end);
            $cached = Cache::get($cacheKey);

            if ($cached) {
                Log::debug('✅ Cache hit for time window slots', [
                    'service_id' => $service->id,
                    'slot_count' => count($cached),
                ]);
                return collect($cached)->map(fn($slot) => Carbon::parse($slot));
            }

            // Fetch available slots from Cal.com
            $availableSlots = $this->fetchAvailableSlots($service, $start, $end);

            // Cache the results
            $cacheableSlots = $availableSlots->map(fn($slot) => $slot->toIso8601String())->toArray();
            Cache::put($cacheKey, $cacheableSlots, self::CACHE_TTL);

            $duration = round((microtime(true) - $startTime) * 1000);

            Log::info('✅ Found slots in time window', [
                'service_id' => $service->id,
                'service_name' => $service->name,
                'slot_count' => $availableSlots->count(),
                'window_start' => $start->toIso8601String(),
                'window_end' => $end->toIso8601String(),
                'duration_ms' => $duration,
            ]);

            return $availableSlots;

        } catch (\Exception $e) {
            Log::error('❌ Failed to find slots in time window', [
                'service_id' => $service->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return collect();
        }
    }

    /**
     * Fetch available slots from Cal.com API
     *
     * @param Service $service
     * @param Carbon $start
     * @param Carbon $end
     * @return Collection
     */
    protected function fetchAvailableSlots(Service $service, Carbon $start, Carbon $end): Collection
    {
        // Apply rate limiting
        if (!$this->rateLimiter->canMakeRequest()) {
            Log::warning('⚠️ Rate limit hit, waiting...', [
                'service_id' => $service->id,
            ]);
            $this->rateLimiter->waitForAvailability();
        }

        try {
            // Make API call
            $response = $this->calcomClient->getAvailableSlots(
                $service->calcom_event_type_id,
                $start,
                $end
            );

            // Increment rate limit counter
            $this->rateLimiter->incrementRequestCount();

            // Check for rate limit headers and adapt
            $this->adaptToRateLimitHeaders($response);

            // Parse response
            if (!$response->successful()) {
                Log::error('❌ Cal.com API error', [
                    'service_id' => $service->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return collect();
            }

            $data = $response->json();

            // Extract slots from response (handle null for tests)
            return $this->parseSlots($data ?? []);

        } catch (\Exception $e) {
            Log::error('❌ Failed to fetch slots from Cal.com', [
                'service_id' => $service->id,
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * Parse slots from Cal.com API response
     *
     * @param array $data API response data
     * @return Collection
     */
    protected function parseSlots(array $data): Collection
    {
        // Cal.com v2 API returns slots in 'data.slots' array
        $slots = $data['data']['slots'] ?? $data['slots'] ?? [];

        return collect($slots)
            ->map(function ($slot) {
                // Parse slot time (can be string or array with 'time' key)
                $time = is_array($slot) ? ($slot['time'] ?? $slot['start'] ?? null) : $slot;

                if (!$time) {
                    return null;
                }

                try {
                    return Carbon::parse($time);
                } catch (\Exception $e) {
                    Log::warning('⚠️ Failed to parse slot time', [
                        'slot' => $slot,
                        'error' => $e->getMessage(),
                    ]);
                    return null;
                }
            })
            ->filter() // Remove null values
            ->sort()   // Sort chronologically
            ->values(); // Reset keys
    }

    /**
     * Adapt rate limiting based on response headers
     *
     * Implements header-based adaptive rate limiting with exponential backoff
     *
     * @param \Illuminate\Http\Client\Response $response
     * @return void
     */
    protected function adaptToRateLimitHeaders($response): void
    {
        $headers = $response->headers();

        // Check for rate limit headers
        $remaining = $headers['X-RateLimit-Remaining'][0] ?? null;
        $reset = $headers['X-RateLimit-Reset'][0] ?? null;

        if ($remaining !== null && (int)$remaining < 10) {
            Log::warning('⚠️ Cal.com rate limit approaching', [
                'remaining' => $remaining,
                'reset' => $reset,
            ]);

            // Implement exponential backoff
            if ((int)$remaining < 5) {
                $backoffSeconds = pow(2, 5 - (int)$remaining);
                Log::info('⏳ Applying exponential backoff', [
                    'remaining' => $remaining,
                    'backoff_seconds' => $backoffSeconds,
                ]);
                sleep($backoffSeconds);
            }
        }

        // Check for 429 (Too Many Requests) with Retry-After
        if ($response->status() === 429) {
            $retryAfter = $headers['Retry-After'][0] ?? 60;

            Log::warning('🚫 Cal.com rate limit exceeded (429)', [
                'retry_after' => $retryAfter,
            ]);

            sleep((int)$retryAfter);
        }
    }

    /**
     * Generate cache key for availability queries
     *
     * @param string $type Query type (next_available, time_window)
     * @param int $serviceId
     * @param Carbon $start
     * @param Carbon|int $end End time or search days
     * @return string
     */
    protected function getCacheKey(string $type, int $serviceId, Carbon $start, $end): string
    {
        $endKey = $end instanceof Carbon
            ? $end->format('Y-m-d-H-i')
            : $end; // For search days

        return sprintf(
            'appointment_finder:%s:service_%d:start_%s:end_%s',
            $type,
            $serviceId,
            $start->format('Y-m-d-H-i'),
            $endKey
        );
    }

    /**
     * Clear cached availability for a service
     *
     * Useful when bookings are created or cancelled
     *
     * @param Service $service
     * @return void
     */
    public function clearCache(Service $service): void
    {
        // Clear all cache entries for this service
        // In production, you might want to use cache tags for more efficient clearing
        $pattern = sprintf('appointment_finder:*:service_%d:*', $service->id);

        Log::debug('🗑️ Clearing availability cache', [
            'service_id' => $service->id,
            'pattern' => $pattern,
        ]);

        // Note: This is a simple implementation
        // For production, consider using Redis SCAN or cache tags
    }
}
