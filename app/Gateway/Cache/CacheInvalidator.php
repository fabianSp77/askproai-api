<?php

namespace App\Gateway\Cache;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class CacheInvalidator
{
    /**
     * Cache invalidation dependencies
     * Maps events to cache patterns that should be invalidated
     */
    private array $dependencies = [
        // Dashboard cache depends on all core data
        'calls.*' => [
            'business/api/dashboard',
            'business/api/analytics/overview',
            'business/api/analytics/calls',
        ],
        'appointments.*' => [
            'business/api/dashboard',
            'business/api/analytics/overview',
            'business/api/analytics/appointments',
            'business/api/calendar',
        ],
        'customers.*' => [
            'business/api/dashboard',
            'business/api/analytics/overview',
            'business/api/analytics/customers',
            'business/api/customers',
        ],
        
        // Specific invalidations
        'appointments.created' => [
            'business/api/appointments',
            'business/api/appointments/calendar',
            'business/api/team/*/availability',
        ],
        'appointments.updated' => [
            'business/api/appointments',
            'business/api/appointments/calendar',
            'business/api/team/*/availability',
        ],
        'appointments.cancelled' => [
            'business/api/appointments',
            'business/api/appointments/calendar',
            'business/api/team/*/availability',
            'business/api/analytics/appointments',
        ],
        
        'calls.created' => [
            'business/api/calls',
            'business/api/analytics/calls',
        ],
        'calls.updated' => [
            'business/api/calls',
            'business/api/calls/*',
        ],
        
        'customers.created' => [
            'business/api/customers',
            'business/api/analytics/customers',
        ],
        'customers.updated' => [
            'business/api/customers',
            'business/api/customers/*',
        ],
        
        'staff.created' => [
            'business/api/team',
            'business/api/settings',
        ],
        'staff.updated' => [
            'business/api/team',
            'business/api/team/*',
            'business/api/settings',
        ],
        
        'company.updated' => [
            'business/api/settings',
            'business/api/settings/company',
        ],
        
        'billing.updated' => [
            'business/api/billing',
            'business/api/billing/*',
        ],
    ];

    /**
     * Invalidate cache based on event
     */
    public function invalidateByEvent(string $event): void
    {
        $patterns = $this->getInvalidationPatterns($event);
        
        foreach ($patterns as $pattern) {
            $this->invalidatePattern($pattern);
        }
        
        if (!empty($patterns)) {
            Log::info('Cache invalidated by event', [
                'event' => $event,
                'patterns' => $patterns,
            ]);
        }
    }

    /**
     * Get invalidation patterns for an event
     */
    private function getInvalidationPatterns(string $event): array
    {
        $patterns = [];
        
        foreach ($this->dependencies as $eventPattern => $cachePatterns) {
            if ($this->matchesEventPattern($event, $eventPattern)) {
                $patterns = array_merge($patterns, $cachePatterns);
            }
        }
        
        return array_unique($patterns);
    }

    /**
     * Check if event matches pattern
     */
    private function matchesEventPattern(string $event, string $pattern): bool
    {
        // Convert wildcard pattern to regex
        $regex = str_replace(['*', '.'], ['.*', '\.'], $pattern);
        return preg_match("/^{$regex}$/", $event);
    }

    /**
     * Invalidate cache by pattern
     */
    private function invalidatePattern(string $pattern): int
    {
        $deleted = 0;
        
        try {
            // Handle wildcard patterns
            if (strpos($pattern, '*') !== false) {
                $deleted += $this->invalidateWildcardPattern($pattern);
            } else {
                $deleted += $this->invalidateExactPattern($pattern);
            }
        } catch (\Exception $e) {
            Log::error('Cache invalidation failed', [
                'pattern' => $pattern,
                'error' => $e->getMessage(),
            ]);
        }
        
        return $deleted;
    }

    /**
     * Invalidate exact pattern
     */
    private function invalidateExactPattern(string $pattern): int
    {
        $deleted = 0;
        
        // Find all cache keys matching this endpoint
        $l1Keys = Redis::keys("l1:gateway_cache:{$pattern}:*");
        $l2Keys = Redis::keys("l2:gateway_cache:{$pattern}:*");
        
        if (!empty($l1Keys)) {
            $deleted += Redis::del($l1Keys);
        }
        
        if (!empty($l2Keys)) {
            $deleted += Redis::del($l2Keys);
        }
        
        return $deleted;
    }

    /**
     * Invalidate wildcard pattern
     */
    private function invalidateWildcardPattern(string $pattern): int
    {
        $deleted = 0;
        
        // Convert to Redis pattern
        $redisPattern = str_replace('*', '*', $pattern);
        
        // Find matching keys in both cache levels
        $l1Keys = Redis::keys("l1:gateway_cache:*{$redisPattern}*");
        $l2Keys = Redis::keys("l2:gateway_cache:*{$redisPattern}*");
        
        if (!empty($l1Keys)) {
            $deleted += Redis::del($l1Keys);
        }
        
        if (!empty($l2Keys)) {
            $deleted += Redis::del($l2Keys);
        }
        
        return $deleted;
    }

    /**
     * Invalidate by company ID
     */
    public function invalidateByCompany(int $companyId): int
    {
        $deleted = 0;
        
        try {
            $l1Keys = Redis::keys("l1:gateway_cache:*:company:{$companyId}:*");
            $l2Keys = Redis::keys("l2:gateway_cache:*:company:{$companyId}:*");
            
            if (!empty($l1Keys)) {
                $deleted += Redis::del($l1Keys);
            }
            
            if (!empty($l2Keys)) {
                $deleted += Redis::del($l2Keys);
            }
            
            Log::info('Cache invalidated by company', [
                'company_id' => $companyId,
                'keys_deleted' => $deleted,
            ]);
        } catch (\Exception $e) {
            Log::error('Company cache invalidation failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);
        }
        
        return $deleted;
    }

    /**
     * Invalidate by user ID
     */
    public function invalidateByUser(int $userId): int
    {
        $deleted = 0;
        
        try {
            $l1Keys = Redis::keys("l1:gateway_cache:*:user:{$userId}:*");
            $l2Keys = Redis::keys("l2:gateway_cache:*:user:{$userId}:*");
            
            if (!empty($l1Keys)) {
                $deleted += Redis::del($l1Keys);
            }
            
            if (!empty($l2Keys)) {
                $deleted += Redis::del($l2Keys);
            }
            
            Log::info('Cache invalidated by user', [
                'user_id' => $userId,
                'keys_deleted' => $deleted,
            ]);
        } catch (\Exception $e) {
            Log::error('User cache invalidation failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
        
        return $deleted;
    }

    /**
     * Add custom invalidation rule
     */
    public function addInvalidationRule(string $event, array $patterns): void
    {
        if (isset($this->dependencies[$event])) {
            $this->dependencies[$event] = array_unique(
                array_merge($this->dependencies[$event], $patterns)
            );
        } else {
            $this->dependencies[$event] = $patterns;
        }
    }

    /**
     * Remove invalidation rule
     */
    public function removeInvalidationRule(string $event): void
    {
        unset($this->dependencies[$event]);
    }

    /**
     * Get all invalidation rules
     */
    public function getInvalidationRules(): array
    {
        return $this->dependencies;
    }

    /**
     * Warm up cache for common endpoints
     */
    public function warmUpCache(int $companyId): void
    {
        $commonEndpoints = [
            'business/api/dashboard',
            'business/api/calls',
            'business/api/appointments',
            'business/api/customers',
            'business/api/analytics/overview',
        ];
        
        foreach ($commonEndpoints as $endpoint) {
            try {
                // This would typically make a request to warm up the cache
                // For now, we'll just log the warming attempt
                Log::info('Cache warming attempted', [
                    'endpoint' => $endpoint,
                    'company_id' => $companyId,
                ]);
            } catch (\Exception $e) {
                Log::warning('Cache warming failed', [
                    'endpoint' => $endpoint,
                    'company_id' => $companyId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Get invalidation statistics
     */
    public function getInvalidationStats(): array
    {
        try {
            return [
                'total_rules' => count($this->dependencies),
                'l1_cache_keys' => Redis::eval('return #redis.call("keys", "l1:*")', 0),
                'l2_cache_keys' => Redis::eval('return #redis.call("keys", "l2:*")', 0),
                'last_invalidation' => Redis::get('cache_invalidation:last_run'),
                'invalidations_today' => Redis::get('cache_invalidation:count:' . date('Y-m-d')) ?? 0,
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }
}