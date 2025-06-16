<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CacheApiResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, int $ttl = 300): Response
    {
        // Only cache GET requests
        if ($request->method() !== 'GET') {
            return $next($request);
        }

        // Skip caching if user is authenticated (personalized content)
        if ($request->user()) {
            return $next($request);
        }

        // Skip caching if there's a cache-control header
        if ($request->headers->has('Cache-Control') && 
            str_contains($request->headers->get('Cache-Control'), 'no-cache')) {
            return $next($request);
        }

        // Generate cache key based on URL and query parameters
        $cacheKey = $this->generateCacheKey($request);

        // Check if we have a cached response
        $cachedResponse = Cache::get($cacheKey);
        if ($cachedResponse) {
            Log::debug('API response served from cache', [
                'url' => $request->fullUrl(),
                'cache_key' => $cacheKey
            ]);

            // Recreate response from cached data
            return response()->json(
                $cachedResponse['data'],
                $cachedResponse['status'],
                $cachedResponse['headers']
            );
        }

        // Get response from the application
        $response = $next($request);

        // Only cache successful JSON responses
        if ($response->getStatusCode() === 200 && 
            $response->headers->get('Content-Type') === 'application/json') {
            
            // Cache the response data
            $responseData = [
                'data' => json_decode($response->getContent(), true),
                'status' => $response->getStatusCode(),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Cache' => 'HIT',
                    'X-Cache-TTL' => $ttl,
                ]
            ];

            Cache::put($cacheKey, $responseData, $ttl);

            Log::debug('API response cached', [
                'url' => $request->fullUrl(),
                'cache_key' => $cacheKey,
                'ttl' => $ttl
            ]);

            // Add cache headers to response
            $response->headers->set('X-Cache', 'MISS');
            $response->headers->set('X-Cache-TTL', (string) $ttl);
        }

        return $response;
    }

    /**
     * Generate a unique cache key for the request
     */
    protected function generateCacheKey(Request $request): string
    {
        $url = $request->url();
        $queryParams = $request->query();
        
        // Sort query parameters to ensure consistent cache keys
        ksort($queryParams);
        
        // Include company/tenant ID if available
        $tenantId = $request->header('X-Tenant-ID', 'default');
        
        return 'api_response:' . $tenantId . ':' . md5($url . '?' . http_build_query($queryParams));
    }
}