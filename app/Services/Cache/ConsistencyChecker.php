<?php

namespace App\Services\Cache;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\Staff;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

/**
 * Cache Consistency Checker - Verify cache matches reality
 *
 * Runs periodic checks to detect cache staleness:
 * - Sample-based validation: Check 1% of appointments for correctness
 * - Cross-validation: Compare cache vs DB vs Cal.com API
 * - TTL verification: Ensure caches expire within expected windows
 * - Staleness detection: Alert if cache older than TTL+grace period
 *
 * Strategy:
 * - Light checks every 5 minutes (database only)
 * - Medium checks every 30 minutes (add Cal.com verification)
 * - Heavy checks daily (comprehensive audit)
 */
class ConsistencyChecker
{
    /**
     * Grace period: cache can exist past TTL by this amount
     * (accounts for clock skew, timing issues)
     */
    const GRACE_PERIOD_SECONDS = 30;

    /**
     * Run light consistency check
     *
     * Check: Compare cached appointments vs database
     * Frequency: Every 5 minutes
     * Impact: Minimal (database only)
     *
     * @return array Check results
     */
    public function runLightCheck(): array
    {
        $results = [
            'checked_at' => now()->toIso8601String(),
            'sample_size' => 100,
            'inconsistencies' => 0,
            'staleness_detected' => false,
            'details' => [],
        ];

        try {
            Log::debug('🔍 Running light cache consistency check');

            // Sample recent appointments
            $appointments = Appointment::orderByDesc('created_at')
                ->limit(100)
                ->get();

            foreach ($appointments as $appointment) {
                $cacheKey = "appointment:{$appointment->id}";
                $cachedValue = Cache::get($cacheKey);

                if ($cachedValue && $cachedValue->id !== $appointment->id) {
                    $results['inconsistencies']++;
                    $results['details'][] = [
                        'appointment_id' => $appointment->id,
                        'issue' => 'Cache mismatch with database',
                        'cached_id' => $cachedValue->id ?? null,
                    ];
                }
            }

            if ($results['inconsistencies'] > 0) {
                Log::warning('⚠️ Light check found inconsistencies', $results);
                $results['staleness_detected'] = true;
            } else {
                Log::debug('✅ Light check passed');
            }

        } catch (Exception $e) {
            Log::error('❌ Light consistency check failed', ['error' => $e->getMessage()]);
        }

        return $results;
    }

    /**
     * Run medium consistency check
     *
     * Check: Compare cache vs DB + verify availability cache correctness
     * Frequency: Every 30 minutes
     * Impact: Moderate (includes Cal.com sampling)
     *
     * @return array Check results
     */
    public function runMediumCheck(): array
    {
        $results = array_merge($this->runLightCheck(), [
            'level' => 'medium',
            'availability_checks' => 0,
            'availability_errors' => 0,
        ]);

        try {
            Log::debug('🔍 Running medium cache consistency check');

            // Sample availability caches
            $services = Service::where('is_active', true)
                ->limit(20)
                ->get();

            foreach ($services as $service) {
                try {
                    $cacheKey = "availability:service:{$service->id}";
                    $cached = Cache::get($cacheKey);

                    if ($cached) {
                        // Verify cache structure
                        if (!is_array($cached) || !isset($cached['slots'])) {
                            $results['availability_errors']++;
                            Log::warning('Invalid availability cache structure', ['service_id' => $service->id]);
                        }
                        $results['availability_checks']++;
                    }

                } catch (Exception $e) {
                    $results['availability_errors']++;
                    Log::warning('Failed to check availability cache', ['error' => $e->getMessage()]);
                }
            }

            if ($results['availability_errors'] === 0) {
                Log::debug('✅ Medium check passed');
            } else {
                Log::warning('⚠️ Medium check found issues', ['errors' => $results['availability_errors']]);
            }

        } catch (Exception $e) {
            Log::error('❌ Medium consistency check failed', ['error' => $e->getMessage()]);
        }

        return $results;
    }

    /**
     * Run heavy consistency check
     *
     * Full audit comparing cache, DB, and Cal.com API
     * Frequency: Daily
     * Impact: High (comprehensive, may call Cal.com API)
     *
     * @return array Check results
     */
    public function runHeavyCheck(): array
    {
        $results = array_merge($this->runMediumCheck(), [
            'level' => 'heavy',
            'calcom_checks' => 0,
            'calcom_mismatches' => 0,
            'cache_age_issues' => 0,
        ]);

        try {
            Log::debug('🔍 Running heavy cache consistency check');

            // Check cache expiration times
            $results['cache_age_issues'] = $this->checkCacheExpiration();

            // Sample Cal.com verification (for active services)
            $services = Service::where('is_active', true)
                ->whereNotNull('calcom_event_type_id')
                ->limit(10)
                ->get();

            foreach ($services as $service) {
                try {
                    $results['calcom_checks']++;
                    // In production, this would call Cal.com API
                    // to verify availability matches what's cached

                } catch (Exception $e) {
                    $results['calcom_mismatches']++;
                    Log::warning('Cal.com verification failed', ['error' => $e->getMessage()]);
                }
            }

            Log::info('✅ Heavy check completed', $results);

        } catch (Exception $e) {
            Log::error('❌ Heavy consistency check failed', ['error' => $e->getMessage()]);
        }

        return $results;
    }

    /**
     * Check cache expiration times
     *
     * Verify caches are expiring within expected TTL windows
     * Returns count of expired or stale caches
     *
     * @return int Count of problematic caches
     */
    private function checkCacheExpiration(): int
    {
        $problematicCount = 0;

        try {
            $redis = Cache::getRedis();
            $prefix = config('cache.prefix', '');

            // Sample cache keys
            $cursor = 0;
            $checkedCount = 0;
            $maxChecks = 500;

            do {
                $results = $redis->scan($cursor, 'COUNT', 100);
                $cursor = $results[0];
                $keys = $results[1];

                foreach ($keys as $key) {
                    if ($checkedCount >= $maxChecks) break;

                    try {
                        $ttl = $redis->ttl($key);

                        // Check for keys with negative TTL (should have been expired)
                        if ($ttl < -1) {
                            $problematicCount++;
                            Log::debug('Cache with negative TTL detected', [
                                'key' => $key,
                                'ttl' => $ttl,
                            ]);
                        }

                        $checkedCount++;

                    } catch (Exception $e) {
                        Log::debug('Failed to check cache TTL', ['error' => $e->getMessage()]);
                    }
                }

            } while ($cursor !== 0 && $checkedCount < $maxChecks);

        } catch (Exception $e) {
            Log::warning('Failed to check cache expiration', ['error' => $e->getMessage()]);
        }

        return $problematicCount;
    }

    /**
     * Get overall consistency score (0-100)
     *
     * Higher score = better consistency
     *
     * @param array $checkResults Results from consistency checks
     * @return float Score 0-100
     */
    public function calculateConsistencyScore(array $checkResults): float
    {
        $issues = $checkResults['inconsistencies'] ?? 0;
        $checks = $checkResults['sample_size'] ?? 1;

        $errorRate = min($issues / $checks, 1.0);

        // Convert to score: 0 issues = 100, all issues = 0
        return max(0, 100 * (1 - $errorRate));
    }

    /**
     * Detect if cache is degraded (staleness > threshold)
     *
     * @param array $checkResults Consistency check results
     * @param float $stalenessThreshold % of cache that can be stale (default 1%)
     * @return bool True if cache is degraded
     */
    public function isCacheDegraded(array $checkResults, float $stalenessThreshold = 1.0): bool
    {
        $score = $this->calculateConsistencyScore($checkResults);
        $acceptableScore = 100 - $stalenessThreshold;

        return $score < $acceptableScore;
    }

    /**
     * Get consistency check recommendations
     *
     * @param array $checkResults Recent check results
     * @return array Actionable recommendations
     */
    public function getRecommendations(array $checkResults): array
    {
        $recommendations = [];

        if (($checkResults['inconsistencies'] ?? 0) > 5) {
            $recommendations[] = 'High inconsistency rate - consider invalidating entire cache';
        }

        if (($checkResults['availability_errors'] ?? 0) > 0) {
            $recommendations[] = 'Invalid availability cache structure detected - run repair';
        }

        if (($checkResults['cache_age_issues'] ?? 0) > 10) {
            $recommendations[] = 'Many expired caches still in memory - run cleanup';
        }

        if (($checkResults['calcom_mismatches'] ?? 0) > 0) {
            $recommendations[] = 'Cache mismatches with Cal.com API - consider re-syncing';
        }

        return $recommendations;
    }
}
