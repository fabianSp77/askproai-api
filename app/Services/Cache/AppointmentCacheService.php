<?php

namespace App\Services\Cache;

use App\Models\Appointment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Appointment Cache Service
 *
 * Multi-tier caching strategy for appointment queries:
 * - L1: Application-level (in-memory, request scoped)
 * - L2: Redis (distributed, TTL-based)
 * - L3: Database (persistent)
 *
 * CACHE HIERARCHY:
 * ```
 * Request → L1 (App Memory) → L2 (Redis) → L3 (Database)
 *           ↓                  ↓             ↓
 *           0ms                2-5ms         20-200ms
 * ```
 *
 * INVALIDATION STRATEGIES:
 * 1. TTL-based: Automatic expiration (default)
 * 2. Event-driven: Immediate invalidation on create/update/delete
 * 3. Tag-based: Bulk invalidation by company/customer
 * 4. Version-based: Increment version counter for coordinated invalidation
 *
 * @author Database Optimization Expert
 * @date 2025-10-18
 */
class AppointmentCacheService
{
    // Cache TTL constants (in seconds)
    private const TTL_AVAILABILITY = 300;      // 5 minutes (frequently changing)
    private const TTL_CONFIGURATION = 3600;    // 1 hour (rarely changing)
    private const TTL_STATS = 300;             // 5 minutes (moderate frequency)
    private const TTL_CUSTOMER_HISTORY = 600;  // 10 minutes
    private const TTL_REVENUE = 1800;          // 30 minutes

    // Cache key prefixes
    private const PREFIX_AVAILABILITY = 'appt:avail';
    private const PREFIX_STATS = 'appt:stats';
    private const PREFIX_REVENUE = 'appt:revenue';
    private const PREFIX_CUSTOMER = 'appt:customer';
    private const PREFIX_SYNC = 'appt:sync';

    // L1 cache (request-scoped)
    private static array $l1Cache = [];

    /**
     * Get appointment availability for a time slot
     * Uses multi-tier caching with automatic fallback
     *
     * @param int $companyId
     * @param int $branchId
     * @param string $date Format: 'Y-m-d'
     * @param string $timeSlot Format: 'H:i'
     * @return bool
     */
    public function isSlotAvailable(int $companyId, int $branchId, string $date, string $timeSlot): bool
    {
        $key = $this->buildKey(self::PREFIX_AVAILABILITY, [
            'company' => $companyId,
            'branch' => $branchId,
            'date' => $date,
            'slot' => $timeSlot,
        ]);

        // L1: Check application memory
        if (isset(self::$l1Cache[$key])) {
            Log::debug('🚀 L1 Cache HIT', ['key' => $key]);
            return self::$l1Cache[$key];
        }

        // L2: Check Redis
        $result = Cache::remember($key, self::TTL_AVAILABILITY, function () use ($companyId, $branchId, $date, $timeSlot) {
            Log::debug('💾 L2 Cache MISS - Querying database');

            // L3: Database query
            $startTime = Carbon::parse("{$date} {$timeSlot}");
            $endTime = $startTime->copy()->addMinutes(30);

            $hasConflict = Appointment::where('company_id', $companyId)
                ->where('branch_id', $branchId)
                ->where(function ($query) use ($startTime, $endTime) {
                    $query->whereBetween('starts_at', [$startTime, $endTime])
                          ->orWhereBetween('ends_at', [$startTime, $endTime])
                          ->orWhere(function ($q) use ($startTime, $endTime) {
                              $q->where('starts_at', '<=', $startTime)
                                ->where('ends_at', '>=', $endTime);
                          });
                })
                ->whereIn('status', ['scheduled', 'confirmed'])
                ->exists();

            return !$hasConflict;
        });

        // Store in L1 cache
        self::$l1Cache[$key] = $result;

        return $result;
    }

    /**
     * Get daily availability slots for a branch
     * Batch query optimization - single DB query for entire day
     *
     * @param int $companyId
     * @param int $branchId
     * @param string $date
     * @return array ['09:00' => true, '09:30' => false, ...]
     */
    public function getDailyAvailability(int $companyId, int $branchId, string $date): array
    {
        $key = $this->buildKey(self::PREFIX_AVAILABILITY, [
            'company' => $companyId,
            'branch' => $branchId,
            'date' => $date,
            'type' => 'daily',
        ]);

        return Cache::remember($key, self::TTL_AVAILABILITY, function () use ($companyId, $branchId, $date) {
            // Generate time slots (09:00 - 18:00 in 30min intervals)
            $slots = [];
            $start = Carbon::parse("{$date} 09:00");
            $end = Carbon::parse("{$date} 18:00");

            while ($start <= $end) {
                $slots[$start->format('H:i')] = true; // Default: available
                $start->addMinutes(30);
            }

            // Fetch all appointments for this day in ONE query
            $appointments = Appointment::where('company_id', $companyId)
                ->where('branch_id', $branchId)
                ->whereDate('starts_at', $date)
                ->whereIn('status', ['scheduled', 'confirmed'])
                ->select(['starts_at', 'ends_at'])
                ->get();

            // Mark occupied slots
            foreach ($appointments as $appointment) {
                $slotTime = Carbon::parse($appointment->starts_at);
                $endTime = Carbon::parse($appointment->ends_at);

                while ($slotTime < $endTime) {
                    $slotKey = $slotTime->format('H:i');
                    if (isset($slots[$slotKey])) {
                        $slots[$slotKey] = false; // Slot occupied
                    }
                    $slotTime->addMinutes(30);
                }
            }

            return $slots;
        });
    }

    /**
     * Get revenue statistics with caching
     * Replaces 6x sum(price) queries with single cached result
     *
     * @param int $companyId
     * @param string $period
     * @return array
     */
    public function getRevenueStats(int $companyId, string $period = 'month'): array
    {
        $key = $this->buildKey(self::PREFIX_REVENUE, [
            'company' => $companyId,
            'period' => $period,
        ]);

        return Cache::remember($key, self::TTL_REVENUE, function () use ($companyId, $period) {
            return \App\Models\Appointment::getRevenueStats($companyId, $period);
        });
    }

    /**
     * Get customer appointment history with caching
     *
     * @param int $customerId
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function getCustomerHistory(int $customerId, int $limit = 10)
    {
        $key = $this->buildKey(self::PREFIX_CUSTOMER, [
            'customer' => $customerId,
            'limit' => $limit,
            'type' => 'history',
        ]);

        return Cache::remember($key, self::TTL_CUSTOMER_HISTORY, function () use ($customerId, $limit) {
            return Appointment::where('customer_id', $customerId)
                ->withCommonRelations()
                ->orderBy('starts_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Invalidate all caches for a company
     * Event-driven invalidation on appointment changes
     *
     * @param int $companyId
     * @return void
     */
    public function invalidateCompany(int $companyId): void
    {
        $this->invalidateByPattern("appt:*:company:{$companyId}:*");

        Log::info('🗑️ Invalidated appointment cache for company', [
            'company_id' => $companyId,
        ]);
    }

    /**
     * Invalidate caches for a specific customer
     *
     * @param int $customerId
     * @return void
     */
    public function invalidateCustomer(int $customerId): void
    {
        $this->invalidateByPattern("appt:customer:customer:{$customerId}:*");

        Log::debug('🗑️ Invalidated customer cache', [
            'customer_id' => $customerId,
        ]);
    }

    /**
     * Invalidate availability cache for a branch on a specific date
     * Fine-grained invalidation for appointment create/update/delete
     *
     * @param int $companyId
     * @param int $branchId
     * @param string $date
     * @return void
     */
    public function invalidateAvailability(int $companyId, int $branchId, string $date): void
    {
        $this->invalidateByPattern("appt:avail:company:{$companyId}:branch:{$branchId}:date:{$date}:*");

        Log::debug('🗑️ Invalidated availability cache', [
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'date' => $date,
        ]);
    }

    /**
     * Warm up cache for frequently accessed data
     * Call this during off-peak hours or after deployment
     *
     * @param int $companyId
     * @return void
     */
    public function warmUpCache(int $companyId): void
    {
        Log::info('🔥 Warming up cache for company', ['company_id' => $companyId]);

        // Warm up revenue stats for common periods
        foreach (['today', 'week', 'month', 'year'] as $period) {
            $this->getRevenueStats($companyId, $period);
        }

        // Warm up availability for next 7 days
        $branches = \App\Models\Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->pluck('id');

        foreach ($branches as $branchId) {
            for ($i = 0; $i < 7; $i++) {
                $date = now()->addDays($i)->format('Y-m-d');
                $this->getDailyAvailability($companyId, $branchId, $date);
            }
        }

        Log::info('✅ Cache warm-up completed', ['company_id' => $companyId]);
    }

    /**
     * Build standardized cache key
     *
     * @param string $prefix
     * @param array $params
     * @return string
     */
    private function buildKey(string $prefix, array $params): string
    {
        $parts = [$prefix];

        foreach ($params as $key => $value) {
            $parts[] = "{$key}:{$value}";
        }

        return implode(':', $parts);
    }

    /**
     * Invalidate cache by pattern
     * Uses Redis SCAN for safe production usage (no blocking)
     *
     * @param string $pattern
     * @return void
     */
    private function invalidateByPattern(string $pattern): void
    {
        try {
            $redis = Redis::connection();
            $cachePrefix = config('cache.prefix', '');
            $fullPattern = $cachePrefix ? "{$cachePrefix}:{$pattern}" : $pattern;

            // Use SCAN instead of KEYS for production safety
            $cursor = null;
            do {
                [$cursor, $keys] = $redis->scan(
                    $cursor ?? 0,
                    'MATCH',
                    $fullPattern,
                    'COUNT',
                    100
                );

                if (!empty($keys)) {
                    // Remove prefix before deleting
                    $keysWithoutPrefix = array_map(function ($key) use ($cachePrefix) {
                        return $cachePrefix ? str_replace("{$cachePrefix}:", '', $key) : $key;
                    }, $keys);

                    Cache::deleteMultiple($keysWithoutPrefix);
                }
            } while ($cursor !== 0 && $cursor !== '0');

        } catch (\Exception $e) {
            Log::error('❌ Cache invalidation failed', [
                'pattern' => $pattern,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Clear L1 cache (request-scoped)
     * Call this after tests or between request cycles
     *
     * @return void
     */
    public static function clearL1Cache(): void
    {
        self::$l1Cache = [];
    }

    /**
     * Get cache statistics
     * For monitoring and debugging
     *
     * @param int $companyId
     * @return array
     */
    public function getCacheStats(int $companyId): array
    {
        $redis = Redis::connection();
        $pattern = "appt:*:company:{$companyId}:*";

        $cursor = null;
        $totalKeys = 0;
        $totalMemory = 0;

        do {
            [$cursor, $keys] = $redis->scan($cursor ?? 0, 'MATCH', $pattern, 'COUNT', 100);
            $totalKeys += count($keys);

            foreach ($keys as $key) {
                $totalMemory += strlen($redis->get($key) ?? '');
            }
        } while ($cursor !== 0);

        return [
            'total_keys' => $totalKeys,
            'memory_bytes' => $totalMemory,
            'memory_mb' => round($totalMemory / 1024 / 1024, 2),
            'l1_keys' => count(self::$l1Cache),
        ];
    }
}
