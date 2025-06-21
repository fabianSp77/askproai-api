<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Security\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class AdaptiveRateLimitMiddleware
{
    private RateLimiter $rateLimiter;

    public function __construct(RateLimiter $rateLimiter)
    {
        $this->rateLimiter = $rateLimiter;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->resolveRequestKey($request);
        $endpoint = $request->path();

        if ($this->rateLimiter->tooManyAttempts($key, $endpoint)) {
            return $this->buildResponse($key, $endpoint);
        }

        $this->rateLimiter->hit($key, $endpoint);

        $response = $next($request);

        return $this->addHeaders($response, $key, $endpoint);
    }

    /**
     * Resolve the request key
     */
    protected function resolveRequestKey(Request $request): string
    {
        if ($user = $request->user()) {
            return 'user:' . $user->id;
        }

        return 'ip:' . $request->ip();
    }

    /**
     * Build rate limit exceeded response
     */
    protected function buildResponse(string $key, string $endpoint): Response
    {
        $retryAfter = $this->rateLimiter->availableIn($key);

        return response()->json([
            'error' => 'Too Many Requests',
            'message' => 'Rate limit exceeded. Please try again later.',
            'retry_after' => $retryAfter
        ], 429)->withHeaders([
            'Retry-After' => $retryAfter,
            'X-RateLimit-Limit' => $this->rateLimiter->getLimit($endpoint)['requests'] ?? 60,
            'X-RateLimit-Remaining' => 0,
        ]);
    }

    /**
     * Add rate limit headers to response
     */
    protected function addHeaders(Response $response, string $key, string $endpoint): Response
    {
        if ($response instanceof \Illuminate\Http\Response || $response instanceof \Symfony\Component\HttpFoundation\Response) {
            $response->headers->add([
                'X-RateLimit-Limit' => $this->rateLimiter->getLimit($endpoint)['requests'] ?? 60,
                'X-RateLimit-Remaining' => $this->rateLimiter->remaining($key, $endpoint),
            ]);
        }

        return $response;
    }
}