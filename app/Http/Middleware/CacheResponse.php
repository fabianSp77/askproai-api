<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CacheResponse
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

        // Don't cache if user is authenticated (personalized content)
        if ($request->user()) {
            $companyId = $request->user()->company_id;
            $cacheKey = 'response:' . $companyId . ':' . md5($request->fullUrl());
        } else {
            // For public endpoints
            $cacheKey = 'response:public:' . md5($request->fullUrl());
        }

        // Check if we have a cached response
        $cachedResponse = Cache::get($cacheKey);
        if ($cachedResponse) {
            return response($cachedResponse['content'])
                ->header('X-Cache', 'HIT')
                ->header('Content-Type', $cachedResponse['content_type']);
        }

        // Get the response
        $response = $next($request);

        // Only cache successful responses
        if ($response->isSuccessful()) {
            Cache::put($cacheKey, [
                'content' => $response->getContent(),
                'content_type' => $response->headers->get('Content-Type'),
            ], $ttl);
        }

        return $response->header('X-Cache', 'MISS');
    }
}