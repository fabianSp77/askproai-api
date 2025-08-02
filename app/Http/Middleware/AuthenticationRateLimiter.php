<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class AuthenticationRateLimiter
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Generate a unique key based on IP and route
        $key = $this->resolveRequestSignature($request);
        
        // Different limits for different authentication types
        $maxAttempts = $this->getMaxAttempts($request);
        $decayMinutes = $this->getDecayMinutes($request);
        
        // Check if rate limit exceeded
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            // Log the rate limit violation
            \Log::warning('Authentication rate limit exceeded', [
                'ip' => $request->ip(),
                'route' => $request->route()?->getName(),
                'user_agent' => $request->userAgent(),
                'attempts' => RateLimiter::attempts($key),
            ]);
            
            // Return rate limit response
            return $this->buildRateLimitResponse($request, $key, $maxAttempts);
        }
        
        // Increment the attempts
        RateLimiter::hit($key, $decayMinutes * 60);
        
        // Process the request
        $response = $next($request);
        
        return $this->addRateLimitHeaders(
            $response, 
            $maxAttempts,
            RateLimiter::remaining($key, $maxAttempts),
            RateLimiter::availableIn($key)
        );
    }
    
    /**
     * Generate a unique signature for the request.
     */
    protected function resolveRequestSignature(Request $request): string
    {
        // Include route name to separate rate limits per endpoint
        $route = $request->route()?->getName() ?? $request->path();
        
        // For authenticated requests, include user ID
        if ($request->user()) {
            return sha1($route . '|' . $request->user()->id);
        }
        
        // For unauthenticated requests, use IP + User-Agent
        return sha1($route . '|' . $request->ip() . '|' . $request->userAgent());
    }
    
    /**
     * Get max attempts based on route.
     */
    protected function getMaxAttempts(Request $request): int
    {
        $route = $request->route()?->getName() ?? '';
        
        // Emergency login - very restrictive
        if (str_contains($request->path(), 'emergency')) {
            return 2; // Only 2 attempts
        }
        
        // Admin login - restrictive
        if (str_contains($route, 'admin') || str_contains($request->path(), 'admin')) {
            return 3; // 3 attempts
        }
        
        // Business portal login - moderate
        if (str_contains($route, 'business') || str_contains($route, 'portal')) {
            return 5; // 5 attempts
        }
        
        // API authentication - more lenient
        if (str_contains($request->path(), 'api/')) {
            return 10; // 10 attempts
        }
        
        // Default
        return 5;
    }
    
    /**
     * Get decay time in minutes based on route.
     */
    protected function getDecayMinutes(Request $request): int
    {
        $route = $request->route()?->getName() ?? '';
        
        // Emergency login - long lockout
        if (str_contains($request->path(), 'emergency')) {
            return 60; // 1 hour lockout
        }
        
        // Admin login - medium lockout
        if (str_contains($route, 'admin') || str_contains($request->path(), 'admin')) {
            return 30; // 30 minutes
        }
        
        // Business portal - standard lockout
        if (str_contains($route, 'business') || str_contains($route, 'portal')) {
            return 15; // 15 minutes
        }
        
        // API - shorter lockout
        if (str_contains($request->path(), 'api/')) {
            return 5; // 5 minutes
        }
        
        // Default
        return 15;
    }
    
    /**
     * Build the rate limit response.
     */
    protected function buildRateLimitResponse(Request $request, string $key, int $maxAttempts): Response
    {
        $seconds = RateLimiter::availableIn($key);
        
        // Different response based on request type
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Too many login attempts. Please try again later.',
                'errors' => [
                    'email' => ['Too many login attempts. Please try again in ' . ceil($seconds / 60) . ' minutes.']
                ],
                'retry_after' => $seconds,
            ], 429);
        }
        
        // For web requests, redirect back with error
        return redirect()
            ->back()
            ->withInput($request->only('email', 'username'))
            ->withErrors([
                'email' => 'Too many login attempts. Please try again in ' . ceil($seconds / 60) . ' minutes.'
            ]);
    }
    
    /**
     * Add rate limit headers to response.
     */
    protected function addRateLimitHeaders(Response $response, int $maxAttempts, int $remainingAttempts, int $retryAfter = null): Response
    {
        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', max(0, $remainingAttempts));
        
        if ($retryAfter) {
            $response->headers->set('Retry-After', $retryAfter);
            $response->headers->set('X-RateLimit-Reset', time() + $retryAfter);
        }
        
        return $response;
    }
}