<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\OptimizedCacheService;

class CacheInvalidationMiddleware
{
    public function __construct(
        private OptimizedCacheService $cacheService
    ) {}

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only invalidate cache for successful POST/PUT/DELETE requests
        if ($response->isSuccessful() && in_array($request->method(), ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            $this->invalidateCacheByRoute($request);
        }

        return $response;
    }

    private function invalidateCacheByRoute(Request $request): void
    {
        $route = $request->route();
        if (!$route) return;

        $routeName = $route->getName();
        $companyId = auth()->user()?->company_id;

        // Map routes to cache invalidation strategies
        match (true) {
            // Call-related routes
            str_contains($routeName, 'call') || str_contains($request->path(), 'calls') => $this->invalidateCallRelatedCache($companyId),
            
            // Customer-related routes
            str_contains($routeName, 'customer') || str_contains($request->path(), 'customers') => $this->invalidateCustomerRelatedCache($companyId),
            
            // Appointment-related routes
            str_contains($routeName, 'appointment') || str_contains($request->path(), 'appointments') => $this->invalidateAppointmentRelatedCache($companyId),
            
            // Company-related routes
            str_contains($routeName, 'company') || str_contains($request->path(), 'companies') => $this->invalidateCompanyRelatedCache($companyId),
            
            // Webhook routes (Retell, Cal.com, etc.)
            str_contains($request->path(), 'webhook') => $this->invalidateLiveDataCache(),
            
            default => null
        };
    }

    private function invalidateCallRelatedCache(?int $companyId): void
    {
        $this->cacheService->invalidateWidget('live_calls', $companyId);
        $this->cacheService->invalidateWidget('recent_calls', $companyId);
        $this->cacheService->invalidateWidget('call_stats_24h', $companyId);
        $this->cacheService->invalidateWidget('dashboard_stats', $companyId);
    }

    private function invalidateCustomerRelatedCache(?int $companyId): void
    {
        $this->cacheService->invalidateWidget('dashboard_stats', $companyId);
        $this->cacheService->invalidateWidget('stats_overview', $companyId);
        $this->cacheService->invalidateWidget('customer_chart', $companyId);
    }

    private function invalidateAppointmentRelatedCache(?int $companyId): void
    {
        $this->cacheService->invalidateWidget('dashboard_stats', $companyId);
        $this->cacheService->invalidateWidget('appointment_chart', $companyId);
        $this->cacheService->invalidateWidget('recent_calls', $companyId);
    }

    private function invalidateCompanyRelatedCache(?int $companyId): void
    {
        if ($companyId) {
            $this->cacheService->invalidateCompany($companyId);
        }
        $this->cacheService->invalidateWidget('stats_overview', null);
    }

    private function invalidateLiveDataCache(): void
    {
        $this->cacheService->invalidateLiveData();
    }
}