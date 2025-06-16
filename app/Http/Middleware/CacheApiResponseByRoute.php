<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CacheApiResponseByRoute
{
    /**
     * Route-specific cache TTL configuration (in seconds)
     */
    protected array $routeCacheTTL = [
        // Services endpoints - cache for 1 hour
        'api/services' => 3600,
        'api/services/*' => 3600,
        
        // Event types - cache for 5 minutes
        'api/event-types' => 300,
        'api/event-types/*' => 300,
        
        // Availability checks - cache for 2 minutes
        'api/availability' => 120,
        'api/availability/*' => 120,
        
        // Staff schedules - cache for 5 minutes
        'api/staff/*/schedule' => 300,
        'api/staff/*/availability' => 120,
        
        // Company settings - cache for 30 minutes
        'api/company/settings' => 1800,
        'api/company/*/settings' => 1800,
        
        // Branches - cache for 15 minutes
        'api/branches' => 900,
        'api/branches/*' => 900,
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only cache GET requests
        if ($request->method() !== 'GET') {
            return $next($request);
        }

        // Get TTL for this route
        $ttl = $this->getTTLForRoute($request);
        
        // If no TTL configured, don't cache
        if ($ttl === null) {
            return $next($request);
        }

        // Skip caching for authenticated requests if they have user-specific data
        $skipForAuth = $this->shouldSkipForAuthenticatedUser($request);
        if ($skipForAuth && $request->user()) {
            return $next($request);
        }

        // Generate cache key
        $cacheKey = $this->generateCacheKey($request);

        // Try to get from cache
        $cachedData = Cache::get($cacheKey);
        if ($cachedData) {
            Log::debug('API response served from route cache', [
                'route' => $request->path(),
                'cache_key' => $cacheKey,
                'ttl' => $ttl
            ]);

            return $this->buildCachedResponse($cachedData);
        }

        // Get fresh response
        $response = $next($request);

        // Cache successful responses
        if ($this->shouldCacheResponse($response)) {
            $this->cacheResponse($cacheKey, $response, $ttl);
            
            // Add cache headers
            $response->headers->set('X-Cache', 'MISS');
            $response->headers->set('X-Cache-TTL', (string) $ttl);
            $response->headers->set('Cache-Control', "public, max-age={$ttl}");
        }

        return $response;
    }

    /**
     * Get TTL for the current route
     */
    protected function getTTLForRoute(Request $request): ?int
    {
        $path = $request->path();
        
        // Check exact match first
        if (isset($this->routeCacheTTL[$path])) {
            return $this->routeCacheTTL[$path];
        }

        // Check wildcard matches
        foreach ($this->routeCacheTTL as $pattern => $ttl) {
            if (str_contains($pattern, '*')) {
                $regex = str_replace('*', '.*', $pattern);
                if (preg_match("#^{$regex}$#", $path)) {
                    return $ttl;
                }
            }
        }

        return null;
    }

    /**
     * Check if caching should be skipped for authenticated users
     */
    protected function shouldSkipForAuthenticatedUser(Request $request): bool
    {
        // Routes that contain user-specific data
        $userSpecificRoutes = [
            'api/user/*',
            'api/my/*',
            'api/profile',
        ];

        $path = $request->path();
        foreach ($userSpecificRoutes as $pattern) {
            $regex = str_replace('*', '.*', $pattern);
            if (preg_match("#^{$regex}$#", $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate cache key for the request
     */
    protected function generateCacheKey(Request $request): string
    {
        $parts = [
            'api_route_cache',
            $request->method(),
            $request->path(),
        ];

        // Add query parameters
        $queryParams = $request->query();
        if (!empty($queryParams)) {
            ksort($queryParams);
            $parts[] = md5(json_encode($queryParams));
        }

        // Add tenant/company context
        if ($request->hasHeader('X-Company-ID')) {
            $parts[] = 'company:' . $request->header('X-Company-ID');
        } elseif ($request->user() && $request->user()->company_id) {
            $parts[] = 'company:' . $request->user()->company_id;
        }

        // Add locale if present
        if ($request->hasHeader('Accept-Language')) {
            $parts[] = 'lang:' . substr($request->header('Accept-Language'), 0, 2);
        }

        return implode(':', $parts);
    }

    /**
     * Check if response should be cached
     */
    protected function shouldCacheResponse(Response $response): bool
    {
        // Only cache successful responses
        if (!in_array($response->getStatusCode(), [200, 203, 204, 206, 300, 301, 304])) {
            return false;
        }

        // Only cache JSON responses
        $contentType = $response->headers->get('Content-Type', '');
        if (!str_contains($contentType, 'application/json')) {
            return false;
        }

        // Don't cache if response has no-cache directive
        $cacheControl = $response->headers->get('Cache-Control', '');
        if (str_contains($cacheControl, 'no-cache') || str_contains($cacheControl, 'no-store')) {
            return false;
        }

        return true;
    }

    /**
     * Cache the response
     */
    protected function cacheResponse(string $cacheKey, Response $response, int $ttl): void
    {
        $data = [
            'content' => $response->getContent(),
            'status' => $response->getStatusCode(),
            'headers' => $response->headers->all(),
            'cached_at' => now()->toIso8601String(),
        ];

        Cache::put($cacheKey, $data, $ttl);

        Log::debug('API response cached', [
            'cache_key' => $cacheKey,
            'ttl' => $ttl,
            'size' => strlen($response->getContent())
        ]);
    }

    /**
     * Build response from cached data
     */
    protected function buildCachedResponse(array $cachedData): Response
    {
        $response = response(
            $cachedData['content'],
            $cachedData['status'],
            $cachedData['headers']
        );

        // Update cache headers
        $response->headers->set('X-Cache', 'HIT');
        $response->headers->set('X-Cached-At', $cachedData['cached_at']);

        return $response;
    }
}