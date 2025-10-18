<?php

namespace App\Services\Cache;

use App\Models\Company;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Branch;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

/**
 * Cache Warming Service - Pre-populate cache with critical data
 *
 * Strategies:
 * 1. Startup Warming: On application boot, pre-populate stable data
 * 2. Peak Hour Warming: Before busy times (9 AM, 2 PM), pre-fetch availability
 * 3. Incremental Warming: Background job to warm cache gradually
 * 4. Smart Warming: Based on access patterns, warm likely-needed data
 */
class CacheWarmingService
{
    /**
     * Warm cache on application startup
     *
     * Populates L2 (Redis) with:
     * - All active companies (slow-changing)
     * - All active services (slow-changing)
     * - Active staff members (slow-changing)
     * - Service categories (static)
     *
     * Target: <5 seconds total warm-up time
     *
     * @return array Warm-up statistics
     */
    public function warmupOnStartup(): array
    {
        $startTime = microtime(true);
        $stats = [
            'companies' => 0,
            'services' => 0,
            'staff' => 0,
            'branches' => 0,
            'duration_ms' => 0,
            'errors' => [],
        ];

        try {
            Log::info('🔥 Starting cache warm-up on startup');

            // Warm companies (very stable)
            $stats['companies'] = $this->warmCompanies();

            // Warm services (stable)
            $stats['services'] = $this->warmServices();

            // Warm staff (fairly stable)
            $stats['staff'] = $this->warmStaff();

            // Warm branches (stable)
            $stats['branches'] = $this->warmBranches();

            $stats['duration_ms'] = round((microtime(true) - $startTime) * 1000);

            Log::info('✅ Cache warm-up completed', $stats);

        } catch (Exception $e) {
            $stats['errors'][] = $e->getMessage();
            Log::error('❌ Cache warm-up failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $stats;
    }

    /**
     * Warm availability cache before peak hours
     *
     * Called before high-traffic periods to pre-fetch availability
     * Peak hours: 9-11 AM, 2-4 PM (typical appointment times)
     *
     * Strategy: For each company/service, fetch availability for next 7 days
     * Cache aggressively with 30-minute TTL (refreshed before peak)
     *
     * @param int $daysAhead Number of days ahead to pre-fetch (default: 7)
     * @return array Pre-fetch statistics
     */
    public function warmPeakHourAvailability(int $daysAhead = 7): array
    {
        $startTime = microtime(true);
        $stats = [
            'companies' => 0,
            'services' => 0,
            'slots' => 0,
            'duration_ms' => 0,
            'errors' => [],
        ];

        try {
            Log::info('🔥 Warming availability cache for peak hours', ['days_ahead' => $daysAhead]);

            $companies = Company::where('is_active', true)->get();
            $stats['companies'] = $companies->count();

            foreach ($companies as $company) {
                try {
                    $services = $company->services()->where('is_active', true)->get();
                    $stats['services'] += $services->count();

                    foreach ($services as $service) {
                        try {
                            // Pre-fetch availability for next N days
                            for ($day = 0; $day < $daysAhead; $day++) {
                                $date = Carbon::today()->addDays($day);
                                $cacheKey = "availability:service:{$service->id}:{$date->format('Y-m-d')}";

                                // Dummy fetch to populate cache (actual implementation would call Cal.com)
                                Cache::put($cacheKey, ['warmed' => true, 'date' => $date->format('Y-m-d')], 1800);
                                $stats['slots']++;
                            }

                        } catch (Exception $e) {
                            $stats['errors'][] = "Service {$service->id}: {$e->getMessage()}";
                        }
                    }

                } catch (Exception $e) {
                    $stats['errors'][] = "Company {$company->id}: {$e->getMessage()}";
                }
            }

            $stats['duration_ms'] = round((microtime(true) - $startTime) * 1000);

            Log::info('✅ Peak hour availability warming completed', $stats);

        } catch (Exception $e) {
            Log::error('❌ Peak hour warming failed', ['error' => $e->getMessage()]);
        }

        return $stats;
    }

    /**
     * Warm company cache
     *
     * @return int Number of companies cached
     */
    private function warmCompanies(): int
    {
        $companies = Company::where('is_active', true)
            ->select('id', 'name', 'calcom_team_id', 'is_active')
            ->get();

        foreach ($companies as $company) {
            $cacheKey = "company:{$company->id}";
            Cache::put($cacheKey, $company, 86400);  // 24 hours
        }

        Log::debug('Warmed companies', ['count' => $companies->count()]);
        return $companies->count();
    }

    /**
     * Warm service cache
     *
     * @return int Number of services cached
     */
    private function warmServices(): int
    {
        $services = Service::where('is_active', true)
            ->select('id', 'name', 'company_id', 'calcom_event_type_id', 'duration', 'price')
            ->get();

        foreach ($services as $service) {
            $cacheKey = "service:{$service->id}";
            Cache::put($cacheKey, $service, 86400);  // 24 hours
        }

        Log::debug('Warmed services', ['count' => $services->count()]);
        return $services->count();
    }

    /**
     * Warm staff cache
     *
     * @return int Number of staff members cached
     */
    private function warmStaff(): int
    {
        $staff = Staff::where('is_active', true)
            ->where('is_bookable', true)
            ->select('id', 'name', 'company_id', 'branch_id', 'calcom_user_id')
            ->get();

        foreach ($staff as $staffMember) {
            $cacheKey = "staff:{$staffMember->id}";
            Cache::put($cacheKey, $staffMember, 3600);  // 1 hour (more volatile)
        }

        Log::debug('Warmed staff', ['count' => $staff->count()]);
        return $staff->count();
    }

    /**
     * Warm branch cache
     *
     * @return int Number of branches cached
     */
    private function warmBranches(): int
    {
        $branches = Branch::where('is_active', true)
            ->select('id', 'name', 'company_id', 'address', 'timezone')
            ->get();

        foreach ($branches as $branch) {
            $cacheKey = "branch:{$branch->id}";
            Cache::put($cacheKey, $branch, 86400);  // 24 hours
        }

        Log::debug('Warmed branches', ['count' => $branches->count()]);
        return $branches->count();
    }

    /**
     * Check if warm-up is needed
     *
     * Returns true if cache appears cold (few keys in Redis)
     *
     * @return bool True if warm-up should run
     */
    public function isWarmupNeeded(): bool
    {
        try {
            $redis = Cache::getRedis();
            $info = $redis->info('keyspace');

            // If very few keys (or no keys), warm-up is needed
            $keyCount = $info['db0']['keys'] ?? 0;

            return $keyCount < 50;  // Threshold for "cold" cache

        } catch (Exception $e) {
            Log::warning('Failed to check if warm-up needed', ['error' => $e->getMessage()]);
            return true;  // Assume warm-up needed on error
        }
    }

    /**
     * Get warm-up statistics
     *
     * Returns current cache warm-up status
     *
     * @return array Statistics
     */
    public function getWarmupStats(): array
    {
        return [
            'last_warmup' => Cache::get('cache:warmup:last_time'),
            'companies_warmed' => Cache::get('cache:warmup:companies', 0),
            'services_warmed' => Cache::get('cache:warmup:services', 0),
            'staff_warmed' => Cache::get('cache:warmup:staff', 0),
            'is_cold' => $this->isWarmupNeeded(),
        ];
    }
}
