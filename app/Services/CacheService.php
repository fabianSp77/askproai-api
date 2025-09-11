<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Branch;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheService
{
    /**
     * Cache duration constants (in seconds)
     */
    public const TENANT_SETTINGS_TTL = 3600;    // 1 hour
    public const USER_PERMISSIONS_TTL = 600;     // 10 minutes
    public const SERVICE_LIST_TTL = 86400;      // 1 day
    public const STAFF_LIST_TTL = 3600;         // 1 hour
    public const BRANCH_LIST_TTL = 7200;        // 2 hours
    public const DASHBOARD_STATS_TTL = 300;     // 5 minutes

    /**
     * Cache key prefix to avoid collisions
     */
    private const CACHE_PREFIX = 'askproai:';

    /**
     * Get tenant settings with caching.
     */
    public function getTenantSettings(string $tenantId): ?array
    {
        $cacheKey = self::CACHE_PREFIX . "tenant:settings:{$tenantId}";

        return Cache::remember($cacheKey, self::TENANT_SETTINGS_TTL, function () use ($tenantId) {
            $tenant = Tenant::find($tenantId);
            
            if (!$tenant) {
                return null;
            }

            return [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'timezone' => $tenant->timezone ?? 'Europe/Berlin',
                'language' => $tenant->language ?? 'de',
                'settings' => $tenant->settings ?? [],
                'balance_cents' => $tenant->balance_cents ?? 0,
                'calcom_team_slug' => $tenant->calcom_team_slug,
                'is_active' => $tenant->is_active ?? true,
                'trial_ends_at' => $tenant->trial_ends_at?->toISOString(),
            ];
        });
    }

    /**
     * Get user permissions with caching.
     */
    public function getUserPermissions(int $userId): array
    {
        $cacheKey = self::CACHE_PREFIX . "user:permissions:{$userId}";

        return Cache::remember($cacheKey, self::USER_PERMISSIONS_TTL, function () use ($userId) {
            $user = User::with(['roles.permissions', 'permissions'])->find($userId);
            
            if (!$user) {
                return [];
            }

            // Get direct permissions
            $directPermissions = $user->permissions->pluck('name')->toArray();
            
            // Get role-based permissions
            $rolePermissions = $user->roles
                ->flatMap(function ($role) {
                    return $role->permissions->pluck('name');
                })
                ->unique()
                ->toArray();

            return [
                'user_id' => $userId,
                'tenant_id' => $user->tenant_id,
                'role' => $user->role,
                'is_active' => $user->is_active,
                'direct_permissions' => $directPermissions,
                'role_permissions' => $rolePermissions,
                'all_permissions' => array_unique(array_merge($directPermissions, $rolePermissions)),
                'cached_at' => now()->toISOString(),
            ];
        });
    }

    /**
     * Get services list for a tenant with caching.
     */
    public function getServicesList(string $tenantId, bool $activeOnly = true): array
    {
        $cacheKey = self::CACHE_PREFIX . "tenant:{$tenantId}:services" . ($activeOnly ? ':active' : ':all');

        return Cache::remember($cacheKey, self::SERVICE_LIST_TTL, function () use ($tenantId, $activeOnly) {
            $query = Service::where('tenant_id', $tenantId)
                ->select('id', 'name', 'description', 'duration_minutes', 'price_cents', 'created_at');

            if ($activeOnly) {
                $query->where('is_active', true);
            }

            return $query->orderBy('name')
                ->get()
                ->map(function ($service) {
                    return [
                        'id' => $service->id,
                        'name' => $service->name,
                        'description' => $service->description,
                        'duration_minutes' => $service->duration_minutes,
                        'price_cents' => $service->price_cents,
                        'price_formatted' => number_format($service->price_cents / 100, 2) . ' €',
                        'duration_formatted' => $service->duration_minutes . ' min',
                        'created_at' => $service->created_at->toISOString(),
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get staff list for a tenant with caching.
     */
    public function getStaffList(string $tenantId, bool $activeOnly = true): array
    {
        $cacheKey = self::CACHE_PREFIX . "tenant:{$tenantId}:staff" . ($activeOnly ? ':active' : ':all');

        return Cache::remember($cacheKey, self::STAFF_LIST_TTL, function () use ($tenantId, $activeOnly) {
            $query = Staff::with('home_branch:id,name')
                ->where('tenant_id', $tenantId)
                ->select('id', 'name', 'email', 'phone', 'home_branch_id', 'created_at');

            if ($activeOnly) {
                $query->where('is_active', true);
            }

            return $query->orderBy('name')
                ->get()
                ->map(function ($staff) {
                    return [
                        'id' => $staff->id,
                        'name' => $staff->name,
                        'email' => $staff->email,
                        'phone' => $staff->phone,
                        'home_branch' => $staff->home_branch ? [
                            'id' => $staff->home_branch->id,
                            'name' => $staff->home_branch->name,
                        ] : null,
                        'created_at' => $staff->created_at->toISOString(),
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get branches list for a tenant with caching.
     */
    public function getBranchesList(string $tenantId, bool $activeOnly = true): array
    {
        $cacheKey = self::CACHE_PREFIX . "tenant:{$tenantId}:branches" . ($activeOnly ? ':active' : ':all');

        return Cache::remember($cacheKey, self::BRANCH_LIST_TTL, function () use ($tenantId, $activeOnly) {
            $query = Branch::where('tenant_id', $tenantId)
                ->select('id', 'name', 'address', 'phone', 'created_at');

            if ($activeOnly) {
                $query->where('is_active', true);
            }

            return $query->orderBy('name')
                ->get()
                ->map(function ($branch) {
                    return [
                        'id' => $branch->id,
                        'name' => $branch->name,
                        'address' => $branch->address,
                        'phone' => $branch->phone,
                        'created_at' => $branch->created_at->toISOString(),
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get dashboard stats with caching.
     */
    public function getDashboardStats(string $tenantId): array
    {
        $cacheKey = self::CACHE_PREFIX . "tenant:{$tenantId}:dashboard:stats";

        return Cache::remember($cacheKey, self::DASHBOARD_STATS_TTL, function () use ($tenantId) {
            // Import models within the closure to avoid early binding
            $callModel = app(\App\Models\Call::class);
            $customerModel = app(\App\Models\Customer::class);
            $appointmentModel = app(\App\Models\Appointment::class);

            $totalCalls = $callModel->where('tenant_id', $tenantId)->count();
            $successfulCalls = $callModel->where('tenant_id', $tenantId)
                ->where('call_successful', true)
                ->count();
            
            $totalCustomers = $customerModel->where('tenant_id', $tenantId)->count();
            
            $todayAppointments = $appointmentModel->where('tenant_id', $tenantId)
                ->whereDate('starts_at', today())
                ->count();
            
            $avgDuration = $callModel->where('tenant_id', $tenantId)
                ->where('call_successful', true)
                ->avg('duration_sec');

            return [
                'total_calls' => $totalCalls,
                'successful_calls' => $successfulCalls,
                'success_rate' => $totalCalls > 0 ? round(($successfulCalls / $totalCalls) * 100, 1) : 0,
                'total_customers' => $totalCustomers,
                'today_appointments' => $todayAppointments,
                'avg_call_duration' => $avgDuration ? round($avgDuration, 0) : 0,
                'avg_call_duration_formatted' => $avgDuration 
                    ? gmdate('i:s', (int) $avgDuration) 
                    : '0:00',
                'cached_at' => now()->toISOString(),
            ];
        });
    }

    /**
     * Invalidate tenant-related cache entries.
     */
    public function invalidateTenantCache(string $tenantId): void
    {
        $patterns = [
            "tenant:settings:{$tenantId}",
            "tenant:{$tenantId}:services:active",
            "tenant:{$tenantId}:services:all", 
            "tenant:{$tenantId}:staff:active",
            "tenant:{$tenantId}:staff:all",
            "tenant:{$tenantId}:branches:active",
            "tenant:{$tenantId}:branches:all",
            "tenant:{$tenantId}:dashboard:stats",
        ];

        foreach ($patterns as $pattern) {
            $key = self::CACHE_PREFIX . $pattern;
            Cache::forget($key);
        }

        Log::info('Cache invalidated for tenant', ['tenant_id' => $tenantId, 'patterns' => $patterns]);
    }

    /**
     * Invalidate user-related cache entries.
     */
    public function invalidateUserCache(int $userId): void
    {
        $key = self::CACHE_PREFIX . "user:permissions:{$userId}";
        Cache::forget($key);

        Log::info('Cache invalidated for user', ['user_id' => $userId]);
    }

    /**
     * Warm up cache for a tenant (preload frequently accessed data).
     */
    public function warmupTenantCache(string $tenantId): array
    {
        $startTime = microtime(true);

        // Preload all cached data
        $results = [
            'tenant_settings' => $this->getTenantSettings($tenantId),
            'services_list' => $this->getServicesList($tenantId),
            'staff_list' => $this->getStaffList($tenantId),
            'branches_list' => $this->getBranchesList($tenantId),
            'dashboard_stats' => $this->getDashboardStats($tenantId),
        ];

        $executionTime = round((microtime(true) - $startTime) * 1000, 2);

        Log::info('Cache warmed up for tenant', [
            'tenant_id' => $tenantId,
            'execution_time' => $executionTime . 'ms',
            'cached_items' => count($results),
        ]);

        return [
            'success' => true,
            'tenant_id' => $tenantId,
            'execution_time' => $executionTime,
            'cached_items' => array_keys($results),
        ];
    }

    /**
     * Get cache statistics.
     */
    public function getCacheStats(): array
    {
        // This is a basic implementation - in production you might want to use Redis commands
        // to get more detailed cache statistics
        return [
            'driver' => config('cache.default'),
            'prefix' => self::CACHE_PREFIX,
            'ttl_settings' => [
                'tenant_settings' => self::TENANT_SETTINGS_TTL . 's',
                'user_permissions' => self::USER_PERMISSIONS_TTL . 's',
                'service_list' => self::SERVICE_LIST_TTL . 's',
                'staff_list' => self::STAFF_LIST_TTL . 's',
                'branch_list' => self::BRANCH_LIST_TTL . 's',
                'dashboard_stats' => self::DASHBOARD_STATS_TTL . 's',
            ],
            'memory_usage' => $this->formatBytes(memory_get_peak_usage(true)),
        ];
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}