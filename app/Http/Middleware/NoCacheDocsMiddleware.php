<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NoCacheDocsMiddleware
{
    /**
     * Handle an incoming request and add aggressive no-cache headers
     * to prevent browser caching of 404 errors and auth pages.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Add aggressive no-cache headers to prevent browser caching
        return $response
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, proxy-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
