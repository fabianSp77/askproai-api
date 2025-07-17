<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class PortalCache
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Only cache GET requests
        if ($request->method() !== 'GET') {
            return $next($request);
        }
        
        // Skip caching for admin viewing mode
        if (session('is_admin_viewing')) {
            return $next($request);
        }
        
        // Skip caching if there are flash messages
        if ($request->session()->has('success') || $request->session()->has('error')) {
            return $next($request);
        }
        
        // Generate cache key based on URL and user
        $user = Auth::guard('portal')->user();
        $userId = $user ? $user->id : 'guest';
        $cacheKey = 'portal.page.' . md5($request->fullUrl() . '.' . $userId);
        
        // Check cache
        $cachedContent = Cache::get($cacheKey);
        if ($cachedContent) {
            return response($cachedContent);
        }
        
        // Process request
        $response = $next($request);
        
        // Cache successful HTML responses for 5 minutes
        if ($response->status() === 200 && 
            $response->headers->get('Content-Type') === 'text/html; charset=UTF-8') {
            Cache::put($cacheKey, $response->content(), 300);
        }
        
        return $response;
    }
    
    /**
     * Clear cache for a specific user
     */
    public static function clearUserCache($userId)
    {
        // This would need to be implemented with tagged cache
        // For now, we'll just clear on data changes
    }
}