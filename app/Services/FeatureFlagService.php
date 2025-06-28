<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FeatureFlagService
{
    private const CACHE_TTL = 300; // 5 minutes
    private const CACHE_PREFIX = 'feature_flag:';
    
    /**
     * Check if a feature is enabled
     */
    public function isEnabled(string $key, ?string $companyId = null, bool $trackUsage = true): bool
    {
        try {
            // Check cache first
            $cacheKey = $this->getCacheKey($key, $companyId);
            $cached = Cache::get($cacheKey);
            
            if ($cached !== null) {
                if ($trackUsage) {
                    $this->trackUsage($key, $companyId, $cached['result'], $cached['reason']);
                }
                return $cached['result'];
            }
            
            // Check company override first
            if ($companyId) {
                $override = DB::table('feature_flag_overrides')
                    ->where('feature_key', $key)
                    ->where('company_id', $companyId)
                    ->first();
                    
                if ($override) {
                    $result = (bool) $override->enabled;
                    $this->cacheResult($cacheKey, $result, 'override');
                    
                    if ($trackUsage) {
                        $this->trackUsage($key, $companyId, $result, 'override');
                    }
                    
                    return $result;
                }
            }
            
            // Check global flag
            $flag = DB::table('feature_flags')
                ->where('key', $key)
                ->first();
                
            if (!$flag) {
                // Default to disabled for unknown flags
                $this->cacheResult($cacheKey, false, 'not_found');
                
                if ($trackUsage) {
                    $this->trackUsage($key, $companyId, false, 'not_found');
                }
                
                return false;
            }
            
            // Check if globally enabled
            if (!$flag->enabled) {
                $this->cacheResult($cacheKey, false, 'globally_disabled');
                
                if ($trackUsage) {
                    $this->trackUsage($key, $companyId, false, 'globally_disabled');
                }
                
                return false;
            }
            
            // Check rollout percentage
            $rolloutPercentage = (int) $flag->rollout_percentage;
            if ($rolloutPercentage < 100 && $companyId) {
                $result = $this->isInRollout($companyId, $rolloutPercentage);
                $reason = $result ? 'rollout_included' : 'rollout_excluded';
                
                $this->cacheResult($cacheKey, $result, $reason);
                
                if ($trackUsage) {
                    $this->trackUsage($key, $companyId, $result, $reason);
                }
                
                return $result;
            }
            
            // Flag is enabled
            $this->cacheResult($cacheKey, true, 'globally_enabled');
            
            if ($trackUsage) {
                $this->trackUsage($key, $companyId, true, 'globally_enabled');
            }
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Feature flag evaluation failed', [
                'key' => $key,
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);
            
            // Default to disabled on error
            return false;
        }
    }
    
    /**
     * Create or update a feature flag
     */
    public function createOrUpdate(array $data): void
    {
        $key = $data['key'];
        
        DB::table('feature_flags')->updateOrInsert(
            ['key' => $key],
            [
                'name' => $data['name'] ?? $key,
                'description' => $data['description'] ?? null,
                'enabled' => $data['enabled'] ?? false,
                'rollout_percentage' => $data['rollout_percentage'] ?? '0',
                'metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null,
                'enabled_at' => $data['enabled'] ? now() : null,
                'disabled_at' => !$data['enabled'] ? now() : null,
                'updated_at' => now()
            ]
        );
        
        // Clear cache
        $this->clearCache($key);
    }
    
    /**
     * Set company override
     */
    public function setOverride(string $key, string $companyId, bool $enabled, ?string $reason = null): void
    {
        DB::table('feature_flag_overrides')->updateOrInsert(
            [
                'feature_key' => $key,
                'company_id' => $companyId
            ],
            [
                'enabled' => $enabled,
                'reason' => $reason,
                'created_by' => auth()->id(),
                'updated_at' => now()
            ]
        );
        
        // Clear cache
        $this->clearCache($key, $companyId);
    }
    
    /**
     * Remove company override
     */
    public function removeOverride(string $key, string $companyId): void
    {
        DB::table('feature_flag_overrides')
            ->where('feature_key', $key)
            ->where('company_id', $companyId)
            ->delete();
            
        // Clear cache
        $this->clearCache($key, $companyId);
    }
    
    /**
     * Get all feature flags
     */
    public function getAllFlags(): array
    {
        return DB::table('feature_flags')
            ->orderBy('name')
            ->get()
            ->map(function ($flag) {
                $flag->metadata = json_decode($flag->metadata, true);
                return $flag;
            })
            ->toArray();
    }
    
    /**
     * Get company overrides
     */
    public function getCompanyOverrides(string $companyId): array
    {
        return DB::table('feature_flag_overrides')
            ->where('company_id', $companyId)
            ->join('feature_flags', 'feature_flag_overrides.feature_key', '=', 'feature_flags.key')
            ->select('feature_flag_overrides.*', 'feature_flags.name', 'feature_flags.description')
            ->get()
            ->toArray();
    }
    
    /**
     * Get usage statistics
     */
    public function getUsageStats(string $key, int $hours = 24): array
    {
        $since = now()->subHours($hours);
        
        return [
            'total_evaluations' => DB::table('feature_flag_usage')
                ->where('feature_key', $key)
                ->where('created_at', '>=', $since)
                ->count(),
                
            'enabled_count' => DB::table('feature_flag_usage')
                ->where('feature_key', $key)
                ->where('created_at', '>=', $since)
                ->where('result', true)
                ->count(),
                
            'by_reason' => DB::table('feature_flag_usage')
                ->where('feature_key', $key)
                ->where('created_at', '>=', $since)
                ->groupBy('evaluation_reason')
                ->selectRaw('evaluation_reason, COUNT(*) as count')
                ->pluck('count', 'evaluation_reason')
                ->toArray(),
                
            'unique_companies' => DB::table('feature_flag_usage')
                ->where('feature_key', $key)
                ->where('created_at', '>=', $since)
                ->distinct('company_id')
                ->count('company_id')
        ];
    }
    
    /**
     * Batch check multiple flags
     */
    public function areEnabled(array $keys, ?string $companyId = null): array
    {
        $results = [];
        
        foreach ($keys as $key) {
            $results[$key] = $this->isEnabled($key, $companyId, false);
        }
        
        // Track batch usage
        $this->trackBatchUsage($results, $companyId);
        
        return $results;
    }
    
    /**
     * Emergency kill switch - disable all features
     */
    public function emergencyDisableAll(string $reason): void
    {
        // Use parameterized query for JSON update
        DB::statement(
            "UPDATE feature_flags 
             SET enabled = ?, 
                 disabled_at = ?, 
                 metadata = JSON_SET(COALESCE(metadata, '{}'), '$.emergency_reason', ?)
             WHERE enabled = ?",
            [false, now(), $reason, true]
        );
            
        // Clear all cache
        Cache::tags(['feature_flags'])->flush();
        
        Log::critical('Emergency feature flag disable triggered', [
            'reason' => $reason,
            'by' => auth()->id()
        ]);
    }
    
    /**
     * Check if company is in rollout percentage
     */
    private function isInRollout(string $companyId, int $percentage): bool
    {
        // Use consistent hashing for stable rollout
        $hash = crc32($companyId);
        return ($hash % 100) < $percentage;
    }
    
    /**
     * Track feature flag usage
     */
    private function trackUsage(string $key, ?string $companyId, bool $result, string $reason): void
    {
        try {
            DB::table('feature_flag_usage')->insert([
                'feature_key' => $key,
                'company_id' => $companyId,
                'user_id' => auth()->id(),
                'result' => $result,
                'evaluation_reason' => $reason,
                'created_at' => now()
            ]);
        } catch (\Exception $e) {
            // Don't let tracking errors affect feature flag evaluation
            Log::warning('Failed to track feature flag usage', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Track batch usage
     */
    private function trackBatchUsage(array $results, ?string $companyId): void
    {
        $records = [];
        
        foreach ($results as $key => $enabled) {
            $records[] = [
                'feature_key' => $key,
                'company_id' => $companyId,
                'user_id' => auth()->id(),
                'result' => $enabled,
                'evaluation_reason' => 'batch',
                'created_at' => now()
            ];
        }
        
        try {
            DB::table('feature_flag_usage')->insert($records);
        } catch (\Exception $e) {
            Log::warning('Failed to track batch feature flag usage', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get cache key
     */
    private function getCacheKey(string $key, ?string $companyId): string
    {
        return self::CACHE_PREFIX . $key . ':' . ($companyId ?? 'global');
    }
    
    /**
     * Cache result
     */
    private function cacheResult(string $cacheKey, bool $result, string $reason): void
    {
        Cache::put($cacheKey, [
            'result' => $result,
            'reason' => $reason
        ], self::CACHE_TTL);
    }
    
    /**
     * Clear cache
     */
    private function clearCache(string $key, ?string $companyId = null): void
    {
        if ($companyId) {
            Cache::forget($this->getCacheKey($key, $companyId));
        } else {
            // Clear all company-specific caches for this key
            Cache::tags(['feature_flags'])->flush();
        }
    }
}