<?php

namespace App\Gateway\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Gateway\RateLimit\AdvancedRateLimiter;

class RateLimitingGateway
{
    private AdvancedRateLimiter $rateLimiter;

    public function __construct(AdvancedRateLimiter $rateLimiter)
    {
        $this->rateLimiter = $rateLimiter;
    }

    /**
     * Handle rate limiting for the request
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if rate limiting is enabled
        if (!config('gateway.rate_limiting.enabled', true)) {
            return $next($request);
        }

        // Check rate limits
        $result = $this->rateLimiter->checkLimits($request);

        if (!$result->allowed) {
            return $this->buildRateLimitResponse($result);
        }

        // Process request
        $response = $next($request);

        // Add rate limit headers to response
        return $this->addRateLimitHeaders($response, $result);
    }

    /**
     * Build rate limit exceeded response
     */
    private function buildRateLimitResponse($result): Response
    {
        return response()->json([
            'error' => 'Too Many Requests',
            'message' => 'Rate limit exceeded. Please try again later.',
            'retry_after' => $result->retryAfter,
            'limit' => $result->limit,
            'remaining' => 0,
        ], 429)->withHeaders([
            'Retry-After' => $result->retryAfter,
            'X-RateLimit-Limit' => $result->limit,
            'X-RateLimit-Remaining' => 0,
            'X-RateLimit-Reset' => now()->addSeconds($result->retryAfter)->timestamp,
        ]);
    }

    /**
     * Add rate limit headers to successful response
     */
    private function addRateLimitHeaders(Response $response, $result): Response
    {
        if ($result->limit !== null) {
            $response->headers->set('X-RateLimit-Limit', $result->limit);
        }
        
        if ($result->remaining !== null) {
            $response->headers->set('X-RateLimit-Remaining', $result->remaining);
        }

        return $response;
    }
}