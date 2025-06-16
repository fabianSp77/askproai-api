<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheService
{
    // Cache TTL constants (in seconds)
    const TTL_EVENT_TYPES = 300;        // 5 minutes
    const TTL_CUSTOMER_LOOKUP = 600;    // 10 minutes
    const TTL_AVAILABILITY = 120;       // 2 minutes
    const TTL_COMPANY_SETTINGS = 1800;  // 30 minutes
    const TTL_STAFF_SCHEDULES = 300;    // 5 minutes
    const TTL_SERVICE_LISTS = 3600;     // 1 hour
    
    // Cache key prefixes
    const KEY_EVENT_TYPES = 'event_types';
    const KEY_CUSTOMER = 'customer';
    const KEY_AVAILABILITY = 'availability';
    const KEY_COMPANY_SETTINGS = 'company_settings';
    const KEY_STAFF_SCHEDULES = 'staff_schedules';
    const KEY_SERVICE_LISTS = 'service_lists';
    
    // Cache tags
    const TAG_COMPANY = 'company';
    const TAG_STAFF = 'staff';
    const TAG_CUSTOMER = 'customer';
    const TAG_APPOINTMENTS = 'appointments';

    /**
     * Get Cal.com event types with caching
     */
    public function getEventTypes(int $companyId, callable $callback)
    {
        $key = $this->buildKey(self::KEY_EVENT_TYPES, $companyId);
        $tags = [$this->buildTag(self::TAG_COMPANY, $companyId)];
        
        return $this->remember($key, self::TTL_EVENT_TYPES, $callback, $tags);
    }

    /**
     * Get customer by phone with caching
     */
    public function getCustomerByPhone(string $phone, int $companyId, callable $callback)
    {
        $key = $this->buildKey(self::KEY_CUSTOMER, 'phone', $phone, $companyId);
        $tags = [
            $this->buildTag(self::TAG_COMPANY, $companyId),
            $this->buildTag(self::TAG_CUSTOMER, $phone)
        ];
        
        return $this->remember($key, self::TTL_CUSTOMER_LOOKUP, $callback, $tags);
    }

    /**
     * Get appointment availability with caching
     */
    public function getAvailability(int $staffId, string $date, callable $callback)
    {
        $key = $this->buildKey(self::KEY_AVAILABILITY, $staffId, $date);
        $tags = [
            $this->buildTag(self::TAG_STAFF, $staffId),
            $this->buildTag(self::TAG_APPOINTMENTS, $date)
        ];
        
        return $this->remember($key, self::TTL_AVAILABILITY, $callback, $tags);
    }

    /**
     * Get company settings with caching
     */
    public function getCompanySettings(int $companyId, callable $callback)
    {
        $key = $this->buildKey(self::KEY_COMPANY_SETTINGS, $companyId);
        $tags = [$this->buildTag(self::TAG_COMPANY, $companyId)];
        
        return $this->remember($key, self::TTL_COMPANY_SETTINGS, $callback, $tags);
    }

    /**
     * Get staff schedules with caching
     */
    public function getStaffSchedules(int $staffId, callable $callback)
    {
        $key = $this->buildKey(self::KEY_STAFF_SCHEDULES, $staffId);
        $tags = [$this->buildTag(self::TAG_STAFF, $staffId)];
        
        return $this->remember($key, self::TTL_STAFF_SCHEDULES, $callback, $tags);
    }

    /**
     * Get service lists with caching
     */
    public function getServiceLists(int $companyId, ?int $branchId = null, callable $callback)
    {
        $key = $this->buildKey(self::KEY_SERVICE_LISTS, $companyId, $branchId);
        $tags = [$this->buildTag(self::TAG_COMPANY, $companyId)];
        
        return $this->remember($key, self::TTL_SERVICE_LISTS, $callback, $tags);
    }

    /**
     * Clear cache by company
     */
    public function clearCompanyCache(int $companyId): void
    {
        $tag = $this->buildTag(self::TAG_COMPANY, $companyId);
        $this->flushByTags([$tag]);
        
        Log::info('Cleared company cache', ['company_id' => $companyId]);
    }

    /**
     * Clear cache by staff
     */
    public function clearStaffCache(int $staffId): void
    {
        $tag = $this->buildTag(self::TAG_STAFF, $staffId);
        $this->flushByTags([$tag]);
        
        Log::info('Cleared staff cache', ['staff_id' => $staffId]);
    }

    /**
     * Clear cache by customer
     */
    public function clearCustomerCache(string $identifier): void
    {
        $tag = $this->buildTag(self::TAG_CUSTOMER, $identifier);
        $this->flushByTags([$tag]);
        
        Log::info('Cleared customer cache', ['identifier' => $identifier]);
    }

    /**
     * Clear appointments cache by date
     */
    public function clearAppointmentsCache(string $date): void
    {
        $tag = $this->buildTag(self::TAG_APPOINTMENTS, $date);
        $this->flushByTags([$tag]);
        
        Log::info('Cleared appointments cache', ['date' => $date]);
    }

    /**
     * Clear all event types cache
     */
    public function clearEventTypesCache(): void
    {
        $pattern = $this->buildKey(self::KEY_EVENT_TYPES, '*');
        $this->forgetPattern($pattern);
        
        Log::info('Cleared all event types cache');
    }

    /**
     * Remember with cache tags support
     */
    protected function remember(string $key, int $ttl, callable $callback, array $tags = [])
    {
        // Check if cache driver supports tagging
        if ($this->supportsTags()) {
            return Cache::tags($tags)->remember($key, $ttl, $callback);
        }
        
        // Fallback for drivers that don't support tagging
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Flush cache by tags
     */
    protected function flushByTags(array $tags): void
    {
        if ($this->supportsTags()) {
            Cache::tags($tags)->flush();
        } else {
            // For drivers that don't support tagging, we need to handle differently
            Log::warning('Cache driver does not support tagging, using pattern-based clearing');
        }
    }

    /**
     * Forget cache keys by pattern (for non-tagging drivers)
     */
    protected function forgetPattern(string $pattern): void
    {
        // This is a simplified implementation - in production you might want
        // to use Redis SCAN or similar for better performance
        $keys = Cache::getRedis()->keys($pattern);
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Check if current cache driver supports tagging
     */
    protected function supportsTags(): bool
    {
        $driver = config('cache.default');
        return in_array($driver, ['redis', 'memcached', 'dynamodb', 'octane']);
    }

    /**
     * Build cache key
     */
    protected function buildKey(string ...$parts): string
    {
        return implode(':', array_filter($parts));
    }

    /**
     * Build cache tag
     */
    protected function buildTag(string $type, $id): string
    {
        return $type . ':' . $id;
    }
}