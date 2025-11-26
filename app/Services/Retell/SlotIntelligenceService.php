<?php

namespace App\Services\Retell;

use App\Models\Service;
use App\Services\CalcomV2Client;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * SlotIntelligenceService
 *
 * Provides intelligent slot management for Retell AI calls:
 * - Pre-loads slots at call start for faster responses
 * - Fuzzy time matching (±30min tolerance)
 * - Vague time period resolution ("vormittags" → 09:00-12:00)
 * - Positive response framing
 *
 * @since 2025-11-25
 */
class SlotIntelligenceService
{
    protected CalcomV2Client $calcomClient;

    /**
     * Cache TTL in seconds (15 minutes = typical max call duration)
     */
    protected const CACHE_TTL_SECONDS = 900;

    /**
     * Fuzzy match tolerance in minutes
     */
    protected const FUZZY_TOLERANCE_MINUTES = 30;

    /**
     * Time period mappings (German → time ranges in 24h format)
     *
     * IMPORTANT: Order matters! Longer/more specific terms must come first
     * so "nachmittags" matches before "mittags" (substring)
     */
    protected const TIME_PERIODS = [
        // Long/specific terms first (to prevent substring matches)
        'nachmittags' => ['start' => '13:00', 'end' => '17:00', 'label' => 'nachmittags'],
        'vormittags' => ['start' => '09:00', 'end' => '12:00', 'label' => 'vormittags'],
        // Shorter terms after
        'früh' => ['start' => '06:00', 'end' => '09:00', 'label' => 'früh morgens'],
        'morgens' => ['start' => '06:00', 'end' => '10:00', 'label' => 'morgens'],
        'mittags' => ['start' => '11:00', 'end' => '14:00', 'label' => 'mittags'],
        'abends' => ['start' => '17:00', 'end' => '21:00', 'label' => 'abends'],
        'spät' => ['start' => '19:00', 'end' => '22:00', 'label' => 'spät abends'],
    ];

    /**
     * Keywords that indicate vague time requests
     */
    protected const VAGUE_TIME_KEYWORDS = [
        'früh', 'morgens', 'vormittags', 'mittags',
        'nachmittags', 'abends', 'spät', 'am morgen',
        'am vormittag', 'am nachmittag', 'am abend',
    ];

    public function __construct(CalcomV2Client $calcomClient)
    {
        $this->calcomClient = $calcomClient;
    }

    /**
     * Get the cache key for call slots
     */
    protected function getCacheKey(string $callId, string $date, int $serviceId): string
    {
        return "call:{$callId}:slots:{$date}:svc:{$serviceId}";
    }

    /**
     * Get the cache key for all loaded dates
     */
    protected function getLoadedDatesKey(string $callId, int $serviceId): string
    {
        return "call:{$callId}:loaded_dates:svc:{$serviceId}";
    }

    /**
     * Pre-load slots for a call (typically called at call start or first availability check)
     *
     * @param string $callId Retell call ID
     * @param Service $service The service being booked
     * @param int $daysAhead Number of days to pre-load (default: 7)
     * @return array Summary of loaded slots
     */
    public function preloadSlotsForCall(string $callId, Service $service, int $daysAhead = 7): array
    {
        $startTime = microtime(true);
        $loadedSlots = [];
        $totalSlots = 0;

        $eventTypeId = $service->calcom_event_type_id;
        if (!$eventTypeId) {
            Log::warning('🔴 SlotIntelligence: Service has no Cal.com event type', [
                'service_id' => $service->id,
                'service_name' => $service->name,
            ]);
            return ['success' => false, 'error' => 'No Cal.com event type configured'];
        }

        // Check if we already have loaded data for this call
        $loadedDatesKey = $this->getLoadedDatesKey($callId, $service->id);
        $alreadyLoaded = Cache::get($loadedDatesKey, []);

        if (!empty($alreadyLoaded)) {
            Log::info('📦 SlotIntelligence: Slots already pre-loaded', [
                'call_id' => $callId,
                'dates_loaded' => count($alreadyLoaded),
            ]);
            return [
                'success' => true,
                'cached' => true,
                'dates_loaded' => $alreadyLoaded,
            ];
        }

        // Load slots for each day
        $startDate = Carbon::now('Europe/Berlin')->startOfDay();
        $endDate = $startDate->copy()->addDays($daysAhead);

        Log::info('🔄 SlotIntelligence: Pre-loading slots', [
            'call_id' => $callId,
            'service_id' => $service->id,
            'event_type_id' => $eventTypeId,
            'date_range' => $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d'),
        ]);

        try {
            // Single API call for entire date range
            $response = $this->calcomClient->getAvailableSlots(
                $eventTypeId,
                $startDate,  // Carbon object
                $endDate     // Carbon object
            );

            if (!$response->successful()) {
                Log::error('🔴 SlotIntelligence: Cal.com API error', [
                    'call_id' => $callId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return ['success' => false, 'error' => 'Cal.com API error: ' . $response->status()];
            }

            $slotsResponse = $response->json();

            // Cal.com V2 API wraps response in 'data' key
            $slotsData = $slotsResponse['data']['slots'] ?? $slotsResponse['slots'] ?? null;

            if ($slotsData && is_array($slotsData)) {
                foreach ($slotsData as $dateStr => $daySlots) {
                    if (empty($daySlots)) continue;

                    // Convert UTC slots to Berlin time and store
                    $berlinSlots = $this->convertSlotsToBerlin($daySlots);

                    $cacheKey = $this->getCacheKey($callId, $dateStr, $service->id);
                    Cache::put($cacheKey, $berlinSlots, self::CACHE_TTL_SECONDS);

                    $loadedSlots[$dateStr] = count($berlinSlots);
                    $totalSlots += count($berlinSlots);
                }
            }

            // Store list of loaded dates
            Cache::put($loadedDatesKey, array_keys($loadedSlots), self::CACHE_TTL_SECONDS);

            $duration = round((microtime(true) - $startTime) * 1000);

            Log::info('✅ SlotIntelligence: Pre-load complete', [
                'call_id' => $callId,
                'total_slots' => $totalSlots,
                'days_loaded' => count($loadedSlots),
                'duration_ms' => $duration,
            ]);

            return [
                'success' => true,
                'total_slots' => $totalSlots,
                'days_loaded' => count($loadedSlots),
                'slots_per_day' => $loadedSlots,
                'duration_ms' => $duration,
            ];

        } catch (\Exception $e) {
            Log::error('🔴 SlotIntelligence: Pre-load failed', [
                'call_id' => $callId,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Convert Cal.com slots to Berlin timezone
     *
     * Note: Cal.com may return times with timezone offset (e.g., +01:00)
     * or in UTC. Carbon handles both formats automatically.
     */
    protected function convertSlotsToBerlin(array $slots): array
    {
        return collect($slots)->map(function ($slot) {
            // Parse the time - Carbon handles both UTC and offset formats
            $slotTime = Carbon::parse($slot['time']);

            // Ensure we're in Berlin timezone for consistent display
            $berlinTime = $slotTime->setTimezone('Europe/Berlin');

            return [
                'time_original' => $slot['time'],
                'time_berlin' => $berlinTime->format('Y-m-d\TH:i:s'),
                'time_display' => $berlinTime->format('H:i'),
                'date' => $berlinTime->format('Y-m-d'),
            ];
        })->toArray();
    }

    /**
     * Get cached slots for a specific date
     */
    public function getCachedSlots(string $callId, string $date, int $serviceId): ?array
    {
        $cacheKey = $this->getCacheKey($callId, $date, $serviceId);
        return Cache::get($cacheKey);
    }

    /**
     * Check if a vague time period was requested
     *
     * @param string $timeInput User's time input (e.g., "vormittags", "10 Uhr")
     * @return array|null Time period info or null if specific time
     */
    public function detectVagueTimePeriod(string $timeInput): ?array
    {
        $normalized = mb_strtolower(trim($timeInput));

        foreach (self::TIME_PERIODS as $keyword => $period) {
            if (str_contains($normalized, $keyword)) {
                return [
                    'keyword' => $keyword,
                    'start' => $period['start'],
                    'end' => $period['end'],
                    'label' => $period['label'],
                ];
            }
        }

        // Check compound phrases
        if (str_contains($normalized, 'am morgen')) {
            return self::TIME_PERIODS['morgens'];
        }
        if (str_contains($normalized, 'am vormittag')) {
            return self::TIME_PERIODS['vormittags'];
        }
        if (str_contains($normalized, 'am nachmittag')) {
            return self::TIME_PERIODS['nachmittags'];
        }
        if (str_contains($normalized, 'am abend')) {
            return self::TIME_PERIODS['abends'];
        }

        return null;
    }

    /**
     * Find slots matching a vague time period
     *
     * @param string $callId Retell call ID
     * @param string $date Target date (Y-m-d)
     * @param int $serviceId Service ID
     * @param array $timePeriod Time period from detectVagueTimePeriod()
     * @param int $maxResults Maximum slots to return (default: 3)
     * @return array Matching slots
     */
    public function findSlotsInTimePeriod(
        string $callId,
        string $date,
        int $serviceId,
        array $timePeriod,
        int $maxResults = 3
    ): array {
        $slots = $this->getCachedSlots($callId, $date, $serviceId);

        if (empty($slots)) {
            Log::info('📭 SlotIntelligence: No cached slots for date', [
                'call_id' => $callId,
                'date' => $date,
            ]);
            return [];
        }

        $startTime = Carbon::createFromFormat('H:i', $timePeriod['start']);
        $endTime = Carbon::createFromFormat('H:i', $timePeriod['end']);

        $matching = collect($slots)->filter(function ($slot) use ($startTime, $endTime) {
            $slotTime = Carbon::createFromFormat('H:i', $slot['time_display']);
            return $slotTime->between($startTime, $endTime);
        })->take($maxResults)->values()->toArray();

        Log::info('🔍 SlotIntelligence: Time period search', [
            'call_id' => $callId,
            'period' => $timePeriod['label'],
            'range' => $timePeriod['start'] . '-' . $timePeriod['end'],
            'found' => count($matching),
        ]);

        return $matching;
    }

    /**
     * Fuzzy match: Find the closest available slot to requested time
     *
     * @param string $callId Retell call ID
     * @param string $date Target date (Y-m-d)
     * @param int $serviceId Service ID
     * @param string $requestedTime Requested time (H:i format)
     * @param int $toleranceMinutes Max minutes difference (default: 30)
     * @return array Match result with slot info and match quality
     */
    public function findClosestSlot(
        string $callId,
        string $date,
        int $serviceId,
        string $requestedTime,
        int $toleranceMinutes = null
    ): array {
        $toleranceMinutes = $toleranceMinutes ?? self::FUZZY_TOLERANCE_MINUTES;
        $slots = $this->getCachedSlots($callId, $date, $serviceId);

        if (empty($slots)) {
            return [
                'found' => false,
                'exact' => false,
                'reason' => 'no_cached_slots',
            ];
        }

        $requestedCarbon = Carbon::createFromFormat('H:i', $requestedTime);
        $exactMatch = null;
        $closestMatch = null;
        $closestDiff = PHP_INT_MAX;

        foreach ($slots as $slot) {
            $slotTime = Carbon::createFromFormat('H:i', $slot['time_display']);
            $diffMinutes = abs($requestedCarbon->diffInMinutes($slotTime));

            // Exact match
            if ($diffMinutes === 0) {
                $exactMatch = $slot;
                break;
            }

            // Track closest within tolerance
            if ($diffMinutes <= $toleranceMinutes && $diffMinutes < $closestDiff) {
                $closestDiff = $diffMinutes;
                $closestMatch = $slot;
            }
        }

        if ($exactMatch) {
            Log::info('✅ SlotIntelligence: Exact time match', [
                'call_id' => $callId,
                'requested' => $requestedTime,
                'found' => $exactMatch['time_display'],
            ]);

            return [
                'found' => true,
                'exact' => true,
                'slot' => $exactMatch,
                'difference_minutes' => 0,
                'response_type' => 'positive_exact',
            ];
        }

        if ($closestMatch) {
            Log::info('🎯 SlotIntelligence: Fuzzy match found', [
                'call_id' => $callId,
                'requested' => $requestedTime,
                'found' => $closestMatch['time_display'],
                'diff_minutes' => $closestDiff,
            ]);

            return [
                'found' => true,
                'exact' => false,
                'slot' => $closestMatch,
                'difference_minutes' => $closestDiff,
                'response_type' => $closestDiff <= 15 ? 'positive_close' : 'positive_alternative',
            ];
        }

        Log::info('❌ SlotIntelligence: No slot within tolerance', [
            'call_id' => $callId,
            'requested' => $requestedTime,
            'tolerance' => $toleranceMinutes,
        ]);

        return [
            'found' => false,
            'exact' => false,
            'reason' => 'no_slot_within_tolerance',
        ];
    }

    /**
     * Get next N available slots for a date (for offering alternatives)
     *
     * @param string $callId Retell call ID
     * @param string $date Target date (Y-m-d)
     * @param int $serviceId Service ID
     * @param int $count Number of slots to return
     * @param string|null $afterTime Only return slots after this time (H:i)
     * @return array Available slots
     */
    public function getNextAvailableSlots(
        string $callId,
        string $date,
        int $serviceId,
        int $count = 3,
        ?string $afterTime = null
    ): array {
        $slots = $this->getCachedSlots($callId, $date, $serviceId);

        if (empty($slots)) {
            return [];
        }

        $collection = collect($slots);

        if ($afterTime) {
            $afterCarbon = Carbon::createFromFormat('H:i', $afterTime);
            $collection = $collection->filter(function ($slot) use ($afterCarbon) {
                $slotTime = Carbon::createFromFormat('H:i', $slot['time_display']);
                return $slotTime->gt($afterCarbon);
            });
        }

        return $collection->take($count)->values()->toArray();
    }

    /**
     * Generate a positive response message
     *
     * @param array $matchResult Result from findClosestSlot()
     * @param string $date Date in German format (e.g., "Freitag, 28.11.")
     * @return array Response with message and slots
     */
    public function generatePositiveResponse(array $matchResult, string $dateDisplay): array
    {
        if (!$matchResult['found']) {
            return [
                'positive' => false,
                'message' => "An diesem Tag habe ich leider keine freien Termine mehr.",
                'alternatives_needed' => true,
            ];
        }

        $slot = $matchResult['slot'];
        $time = $slot['time_display'];

        switch ($matchResult['response_type']) {
            case 'positive_exact':
                // Exact match - very positive
                return [
                    'positive' => true,
                    'message' => "Ja, um {$time} Uhr habe ich einen Termin für Sie frei!",
                    'slot' => $slot,
                    'alternatives_needed' => false,
                ];

            case 'positive_close':
                // Very close match (≤15 min) - treat as available
                return [
                    'positive' => true,
                    'message' => "Ja, ich hätte um {$time} Uhr einen Termin für Sie!",
                    'slot' => $slot,
                    'alternatives_needed' => false,
                ];

            case 'positive_alternative':
                // Within tolerance but not very close - still positive but mention alternative
                return [
                    'positive' => true,
                    'message' => "Ich hätte um {$time} Uhr einen Termin für Sie.",
                    'slot' => $slot,
                    'alternatives_needed' => false,
                ];

            default:
                return [
                    'positive' => false,
                    'message' => "Da habe ich leider nichts frei.",
                    'alternatives_needed' => true,
                ];
        }
    }

    /**
     * Generate response for vague time period request
     *
     * @param array $slots Available slots in the time period
     * @param array $timePeriod Time period info
     * @param string $dateDisplay Date in German format
     * @return array Response with message and slots
     */
    public function generateTimePeriodResponse(array $slots, array $timePeriod, string $dateDisplay): array
    {
        if (empty($slots)) {
            return [
                'positive' => false,
                'message' => "{$timePeriod['label']} habe ich leider keine freien Termine mehr.",
                'slots' => [],
                'alternatives_needed' => true,
            ];
        }

        $count = count($slots);
        $times = array_map(fn($s) => $s['time_display'] . ' Uhr', $slots);

        if ($count === 1) {
            return [
                'positive' => true,
                'message' => "{$timePeriod['label']} hätte ich um {$times[0]} einen Termin für Sie.",
                'slots' => $slots,
                'alternatives_needed' => false,
            ];
        }

        // Format multiple times nicely: "10:00, 10:30 und 11:00 Uhr"
        $lastTime = array_pop($times);
        $timeList = implode(', ', $times) . ' und ' . $lastTime;

        return [
            'positive' => true,
            'message' => "{$timePeriod['label']} hätte ich um {$timeList} freie Termine.",
            'slots' => $slots,
            'alternatives_needed' => false,
        ];
    }

    /**
     * Main entry point: Check availability with intelligence
     *
     * Combines pre-loading, fuzzy matching, and positive responses
     *
     * @param string $callId Retell call ID
     * @param Service $service Service being booked
     * @param string $date Target date (Y-m-d)
     * @param string $timeInput User's time input (specific time or vague period)
     * @return array Intelligent availability response
     */
    public function checkAvailabilityIntelligent(
        string $callId,
        Service $service,
        string $date,
        string $timeInput
    ): array {
        // Ensure slots are pre-loaded
        $this->preloadSlotsForCall($callId, $service);

        // Parse date for display
        $dateCarbon = Carbon::parse($date);
        $dateDisplay = $dateCarbon->locale('de')->isoFormat('dddd, D.M.');

        // Check if this is a vague time period request
        $timePeriod = $this->detectVagueTimePeriod($timeInput);

        if ($timePeriod) {
            // Vague time period: "vormittags", "nachmittags", etc.
            $slots = $this->findSlotsInTimePeriod(
                $callId,
                $date,
                $service->id,
                $timePeriod,
                3 // Return up to 3 options
            );

            $response = $this->generateTimePeriodResponse($slots, $timePeriod, $dateDisplay);
            $response['type'] = 'time_period';
            $response['time_period'] = $timePeriod;
            return $response;
        }

        // Specific time requested - extract time from input
        $requestedTime = $this->parseTimeFromInput($timeInput);

        if (!$requestedTime) {
            return [
                'positive' => false,
                'message' => 'Die Uhrzeitangabe konnte ich nicht verstehen.',
                'type' => 'parse_error',
            ];
        }

        // Fuzzy match to find closest slot
        $matchResult = $this->findClosestSlot(
            $callId,
            $date,
            $service->id,
            $requestedTime
        );

        $response = $this->generatePositiveResponse($matchResult, $dateDisplay);
        $response['type'] = 'specific_time';
        $response['requested_time'] = $requestedTime;
        $response['match_result'] = $matchResult;

        // If no match within tolerance, get next available slots as alternatives
        if (!$matchResult['found'] || $response['alternatives_needed']) {
            $response['alternatives'] = $this->getNextAvailableSlots(
                $callId,
                $date,
                $service->id,
                3,
                $requestedTime
            );
        }

        return $response;
    }

    /**
     * Parse time from user input (handles various German formats)
     *
     * @param string $input User input like "10 Uhr", "10:30", "zehn Uhr"
     * @return string|null Time in H:i format or null
     */
    protected function parseTimeFromInput(string $input): ?string
    {
        $normalized = mb_strtolower(trim($input));

        // Remove common words
        $normalized = str_replace(['uhr', 'um', 'gegen', 'etwa', 'ungefähr', 'so'], '', $normalized);
        $normalized = trim($normalized);

        // Try numeric patterns first
        if (preg_match('/(\d{1,2})[:\.](\d{2})/', $normalized, $matches)) {
            return sprintf('%02d:%02d', (int)$matches[1], (int)$matches[2]);
        }

        if (preg_match('/(\d{1,2})/', $normalized, $matches)) {
            return sprintf('%02d:00', (int)$matches[1]);
        }

        // German word numbers
        $wordNumbers = [
            'sechs' => 6, 'sieben' => 7, 'acht' => 8, 'neun' => 9, 'zehn' => 10,
            'elf' => 11, 'zwölf' => 12, 'eins' => 13, 'zwei' => 14, 'drei' => 15,
            'vier' => 16, 'fünf' => 17, 'achtzehn' => 18, 'neunzehn' => 19, 'zwanzig' => 20,
        ];

        foreach ($wordNumbers as $word => $hour) {
            if (str_contains($normalized, $word)) {
                return sprintf('%02d:00', $hour);
            }
        }

        return null;
    }

    /**
     * Clear cached slots for a call (call this when call ends)
     */
    public function clearCallCache(string $callId): void
    {
        // We rely on TTL for cleanup, but this can be called explicitly
        Log::info('🧹 SlotIntelligence: Cache will expire naturally', [
            'call_id' => $callId,
        ]);
    }
}
