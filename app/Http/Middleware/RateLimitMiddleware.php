<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Traits\ApiResponse;

class RateLimitMiddleware
{
    use ApiResponse;

    /**
     * Rate limit configurations per route pattern
     */
    private array $rateLimits = [
        // Authentication endpoints - strict limits
        'api/auth/login' => ['limit' => 5, 'window' => 300], // 5 attempts per 5 minutes
        'api/auth/register' => ['limit' => 3, 'window' => 600], // 3 attempts per 10 minutes
        'api/auth/password/reset' => ['limit' => 3, 'window' => 900], // 3 attempts per 15 minutes

        // Booking endpoints - moderate limits
        'api/v2/bookings' => ['limit' => 30, 'window' => 60], // 30 requests per minute
        'api/v2/bookings/*/reschedule' => ['limit' => 10, 'window' => 60], // 10 reschedules per minute
        'api/v2/bookings/*/cancel' => ['limit' => 10, 'window' => 60], // 10 cancellations per minute

        // Cal.com sync - resource intensive
        'api/v2/calcom/sync/*' => ['limit' => 5, 'window' => 60], // 5 sync operations per minute
        'api/v2/calcom/push' => ['limit' => 10, 'window' => 300], // 10 pushes per 5 minutes

        // Webhook endpoints - high volume allowed
        'webhooks/*' => ['limit' => 100, 'window' => 60], // 100 webhooks per minute

        // Payment endpoints - strict security
        'api/payments/*' => ['limit' => 10, 'window' => 60], // 10 payment attempts per minute
        'api/stripe/*' => ['limit' => 20, 'window' => 60], // 20 Stripe operations per minute

        // Search and listing - moderate limits
        'api/v2/availability/*' => ['limit' => 60, 'window' => 60], // 60 availability checks per minute
        'api/v2/services' => ['limit' => 100, 'window' => 60], // 100 service lists per minute

        // Admin operations - relaxed for internal use
        'api/admin/*' => ['limit' => 200, 'window' => 60], // 200 admin operations per minute

        // Default for all other endpoints
        'default' => ['limit' => 60, 'window' => 60], // 60 requests per minute
    ];

    /**
     * Handle an incoming request
     */
    public function handle(Request $request, Closure $next, ?string $customLimit = null): Response
    {
        // Skip rate limiting for local development (optional)
        if (app()->environment('local') && !config('app.rate_limiting_enabled', true)) {
            return $next($request);
        }

        // Get rate limit configuration
        $config = $this->getRateLimitConfig($request, $customLimit);

        // Build rate limit key
        $key = $this->buildRateLimitKey($request);

        // Check and update rate limit
        $result = $this->checkRateLimit($key, $config);

        if (!$result['allowed']) {
            return $this->handleRateLimitExceeded($request, $result);
        }

        // Add rate limit headers to response
        $response = $next($request);
        return $this->addRateLimitHeaders($response, $result);
    }

    /**
     * Get rate limit configuration for request
     */
    private function getRateLimitConfig(Request $request, ?string $customLimit): array
    {
        // Check for custom limit parameter
        if ($customLimit) {
            [$limit, $window] = explode(',', $customLimit);
            return [
                'limit' => (int) $limit,
                'window' => (int) $window,
            ];
        }

        // Check request path against configurations
        $path = $request->path();

        foreach ($this->rateLimits as $pattern => $config) {
            if ($pattern === 'default') {
                continue;
            }

            // Check if path matches pattern (supporting wildcards)
            if ($this->pathMatches($path, $pattern)) {
                return $config;
            }
        }

        // Return default configuration
        return $this->rateLimits['default'];
    }

    /**
     * Check if path matches pattern with wildcards
     */
    private function pathMatches(string $path, string $pattern): bool
    {
        // Convert pattern to regex
        $regex = str_replace(
            ['*', '/'],
            ['[^/]*', '\/'],
            $pattern
        );

        return (bool) preg_match('/^' . $regex . '$/', $path);
    }

    /**
     * Build unique rate limit key
     */
    private function buildRateLimitKey(Request $request): string
    {
        $identifier = $this->getClientIdentifier($request);
        $route = $request->path();

        return sprintf(
            'rate_limit:%s:%s',
            md5($identifier),
            md5($route)
        );
    }

    /**
     * Get client identifier for rate limiting
     */
    private function getClientIdentifier(Request $request): string
    {
        // Priority order for identification:
        // 1. Authenticated user ID
        if ($request->user()) {
            return 'user:' . $request->user()->id;
        }

        // 2. API key if present
        if ($apiKey = $request->header('X-Api-Key')) {
            return 'api:' . $apiKey;
        }

        // 3. Session ID for web requests
        if ($request->hasSession() && $request->session()->getId()) {
            return 'session:' . $request->session()->getId();
        }

        // 4. IP address as fallback
        return 'ip:' . $request->ip();
    }

    /**
     * Check rate limit and update counter
     */
    private function checkRateLimit(string $key, array $config): array
    {
        $attempts = (int) Cache::get($key, 0);
        $resetTime = Cache::get($key . ':reset');

        // If no reset time, this is first attempt
        if (!$resetTime) {
            $resetTime = now()->addSeconds($config['window']);
            Cache::put($key . ':reset', $resetTime, $resetTime);
        }

        // Check if we're within limits
        if ($attempts < $config['limit']) {
            Cache::increment($key);
            \Illuminate\Support\Facades\Redis::expire(config('cache.prefix') . $key, $config['window']);

            return [
                'allowed' => true,
                'limit' => $config['limit'],
                'remaining' => $config['limit'] - $attempts - 1,
                'reset' => $resetTime->timestamp,
                'attempts' => $attempts + 1,
            ];
        }

        return [
            'allowed' => false,
            'limit' => $config['limit'],
            'remaining' => 0,
            'reset' => $resetTime->timestamp,
            'attempts' => $attempts,
            'retry_after' => $resetTime->diffInSeconds(now()),
        ];
    }

    /**
     * Handle rate limit exceeded
     */
    private function handleRateLimitExceeded(Request $request, array $result): Response
    {
        // Log rate limit violation
        Log::warning('Rate limit exceeded', [
            'ip' => $request->ip(),
            'user_id' => $request->user()?->id,
            'path' => $request->path(),
            'method' => $request->method(),
            'attempts' => $result['attempts'],
            'limit' => $result['limit'],
        ]);

        // Check for potential abuse
        $this->checkForAbuse($request, $result);

        // Return rate limit error response
        $response = response()->json([
            'success' => false,
            'status_code' => 429,
            'message' => 'Too many requests. Please slow down.',
            'error' => 'rate_limit_exceeded',
            'retry_after' => $result['retry_after'],
        ], 429);

        return $this->addRateLimitHeaders($response, $result);
    }

    /**
     * Check for potential abuse patterns
     */
    private function checkForAbuse(Request $request, array $result): void
    {
        $abuseKey = 'abuse:' . $this->getClientIdentifier($request);
        $violations = (int) Cache::get($abuseKey, 0);

        // Increment violation counter
        Cache::increment($abuseKey);
        \Illuminate\Support\Facades\Redis::expire(config('cache.prefix') . $abuseKey, 3600); // Track for 1 hour

        // If multiple violations, flag for review
        if ($violations > 10) {
            Log::critical('Potential API abuse detected', [
                'identifier' => $this->getClientIdentifier($request),
                'violations' => $violations,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'path' => $request->path(),
            ]);

            // Here you could implement IP blocking or other security measures
            $this->blockAbusiveClient($request);
        }
    }

    /**
     * Block abusive client
     */
    private function blockAbusiveClient(Request $request): void
    {
        $blockKey = 'blocked:' . $this->getClientIdentifier($request);

        // Block for 24 hours
        Cache::put($blockKey, [
            'blocked_at' => now(),
            'reason' => 'Excessive rate limit violations',
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ], now()->addHours(24));

        // Send alert
        Log::critical('Client blocked due to API abuse', [
            'identifier' => $this->getClientIdentifier($request),
            'ip' => $request->ip(),
            'blocked_until' => now()->addHours(24),
        ]);
    }

    /**
     * Add rate limit headers to response
     */
    private function addRateLimitHeaders(Response $response, array $result): Response
    {
        $response->headers->set('X-RateLimit-Limit', $result['limit']);
        $response->headers->set('X-RateLimit-Remaining', max(0, $result['remaining']));
        $response->headers->set('X-RateLimit-Reset', $result['reset']);

        if (isset($result['retry_after'])) {
            $response->headers->set('Retry-After', $result['retry_after']);
        }

        return $response;
    }

    /**
     * Reset rate limits for specific identifier (admin use)
     */
    public function resetRateLimit(string $identifier): bool
    {
        $pattern = 'rate_limit:' . md5($identifier) . ':*';
        $keys = Cache::getRedis()->keys($pattern);

        foreach ($keys as $key) {
            Cache::forget(str_replace(config('cache.prefix') . ':', '', $key));
        }

        Log::info('Rate limits reset', ['identifier' => $identifier]);

        return true;
    }

    /**
     * Get current rate limit status for identifier
     */
    public function getRateLimitStatus(string $identifier, string $route = '*'): array
    {
        $status = [];

        if ($route === '*') {
            // Get all routes for this identifier
            $pattern = 'rate_limit:' . md5($identifier) . ':*';
            $keys = Cache::getRedis()->keys($pattern);

            foreach ($keys as $key) {
                $cleanKey = str_replace(config('cache.prefix') . ':', '', $key);
                $attempts = Cache::get($cleanKey, 0);
                $resetTime = Cache::get($cleanKey . ':reset');

                $status[] = [
                    'route' => $key,
                    'attempts' => $attempts,
                    'reset_at' => $resetTime?->toIso8601String(),
                ];
            }
        } else {
            // Get specific route
            $key = sprintf('rate_limit:%s:%s', md5($identifier), md5($route));
            $attempts = Cache::get($key, 0);
            $resetTime = Cache::get($key . ':reset');

            $status = [
                'route' => $route,
                'attempts' => $attempts,
                'reset_at' => $resetTime?->toIso8601String(),
            ];
        }

        return $status;
    }
}