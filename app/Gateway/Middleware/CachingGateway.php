<?php

namespace App\Gateway\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Gateway\Cache\GatewayCacheManager;

class CachingGateway
{
    private GatewayCacheManager $cache;

    public function __construct(GatewayCacheManager $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Handle caching for the request
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if caching is enabled
        if (!config('gateway.caching.enabled', true)) {
            return $next($request);
        }

        // Use cache manager to get cached response or execute callback
        return $this->cache->get($request, function () use ($request, $next) {
            return $next($request);
        });
    }
}