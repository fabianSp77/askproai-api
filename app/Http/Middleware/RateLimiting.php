<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RateLimiting
{
    protected RateLimiter $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle an incoming request with advanced rate limiting
     */
    public function handle(Request $request, Closure $next, string $type = 'default'): Response
    {
        $key = $this->resolveRequestKey($request, $type);
        $maxAttempts = $this->getMaxAttempts($type);
        $decayMinutes = $this->getDecayMinutes($type);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return $this->buildResponse($request, $key, $maxAttempts);
        }

        $this->limiter->hit($key, $decayMinutes * 60);

        $response = $next($request);

        return $this->addHeaders(
            $response,
            $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts)
        );
    }

    /**
     * Resolve the request key for rate limiting
     */
    protected function resolveRequestKey(Request $request, string $type): string
    {
        $identifier = $request->user()?->id ?: $request->ip();

        return sprintf(
            'rate_limit:%s:%s:%s',
            $type,
            sha1($identifier),
            $request->fingerprint()
        );
    }

    /**
     * Get max attempts based on type
     */
    protected function getMaxAttempts(string $type): int
    {
        return match($type) {
            'auth' => 5,        // 5 attempts for authentication
            'api' => 60,        // 60 requests per minute for API
            'admin' => 100,     // 100 requests per minute for admin
            'webhook' => 30,    // 30 webhook calls per minute
            default => 60,      // Default 60 requests per minute
        };
    }

    /**
     * Get decay minutes based on type
     */
    protected function getDecayMinutes(string $type): int
    {
        return match($type) {
            'auth' => 15,       // 15 minutes for auth attempts
            'webhook' => 1,     // 1 minute for webhooks
            default => 1,       // Default 1 minute
        };
    }

    /**
     * Calculate remaining attempts
     */
    protected function calculateRemainingAttempts(string $key, int $maxAttempts): int
    {
        return max(0, $maxAttempts - $this->limiter->attempts($key));
    }

    /**
     * Build the rate limit response
     */
    protected function buildResponse(Request $request, string $key, int $maxAttempts): Response
    {
        $retryAfter = $this->limiter->availableIn($key);

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Too Many Requests',
                'message' => 'Rate limit exceeded. Please try again later.',
                'retry_after' => $retryAfter,
            ], 429);
        }

        abort(429, 'Too Many Requests');
    }

    /**
     * Add rate limit headers to the response
     */
    protected function addHeaders(Response $response, int $maxAttempts, int $remainingAttempts): Response
    {
        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', $remainingAttempts);

        if ($remainingAttempts === 0) {
            $response->headers->set('X-RateLimit-Reset', time() + 60);
        }

        return $response;
    }
}