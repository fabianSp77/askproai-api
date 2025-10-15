<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rate Limiting Middleware for Configuration Updates
 *
 * Prevents abuse of configuration endpoints by limiting update frequency.
 * Different rate limits apply to different types of configuration changes.
 *
 * Rate Limits:
 * - Regular configuration updates: 10 requests/minute per user
 * - Sensitive updates (API keys): 3 requests/hour per user
 * - Bulk operations: 1 request/minute per user
 *
 * @package App\Http\Middleware
 */
class ThrottleConfigurationUpdates
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $limitType = 'standard'): Response
    {
        if (!auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();
        $key = $this->resolveRequestKey($user->id, $limitType, $request);
        $maxAttempts = $this->getMaxAttempts($limitType);
        $decaySeconds = $this->getDecaySeconds($limitType);

        // Check if rate limit exceeded
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($key);

            // Log rate limit violation for security monitoring
            logger()->warning('Configuration update rate limit exceeded', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'company_id' => $user->company_id,
                'limit_type' => $limitType,
                'max_attempts' => $maxAttempts,
                'decay_seconds' => $decaySeconds,
                'retry_after' => $retryAfter,
                'ip' => $request->ip(),
                'endpoint' => $request->path(),
                'method' => $request->method(),
            ]);

            return $this->buildRateLimitResponse($retryAfter, $maxAttempts, $decaySeconds);
        }

        // Increment rate limiter
        RateLimiter::hit($key, $decaySeconds);

        // Continue with request
        $response = $next($request);

        // Add rate limit headers to response
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => max(0, $maxAttempts - RateLimiter::attempts($key)),
            'X-RateLimit-Reset' => time() + $decaySeconds,
        ]);

        return $response;
    }

    /**
     * Generate unique rate limit key per user and limit type
     */
    private function resolveRequestKey(int $userId, string $limitType, Request $request): string
    {
        // Include IP for additional security layer
        $ip = $request->ip();

        return "config-updates:{$limitType}:{$userId}:{$ip}";
    }

    /**
     * Get maximum allowed attempts for limit type
     */
    private function getMaxAttempts(string $limitType): int
    {
        return match($limitType) {
            'sensitive' => 3,      // API keys, webhook secrets
            'bulk' => 1,           // Bulk operations
            'standard' => 10,      // Regular config updates
            default => 10,
        };
    }

    /**
     * Get decay time in seconds for limit type
     */
    private function getDecaySeconds(string $limitType): int
    {
        return match($limitType) {
            'sensitive' => 3600,   // 1 hour for sensitive updates
            'bulk' => 60,          // 1 minute for bulk operations
            'standard' => 60,      // 1 minute for standard updates
            default => 60,
        };
    }

    /**
     * Build rate limit exceeded response
     */
    private function buildRateLimitResponse(int $retryAfter, int $maxAttempts, int $decaySeconds): Response
    {
        $minutes = ceil($retryAfter / 60);
        $windowMinutes = ceil($decaySeconds / 60);

        $message = sprintf(
            'Too many configuration updates. You can make %d requests per %d minute%s. Please try again in %d minute%s.',
            $maxAttempts,
            $windowMinutes,
            $windowMinutes === 1 ? '' : 's',
            $minutes,
            $minutes === 1 ? '' : 's'
        );

        return response()->json([
            'message' => $message,
            'retry_after' => $retryAfter,
            'retry_after_minutes' => $minutes,
            'max_attempts' => $maxAttempts,
            'window_seconds' => $decaySeconds,
        ], 429)->headers->add([
            'Retry-After' => $retryAfter,
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => 0,
            'X-RateLimit-Reset' => time() + $retryAfter,
        ]);
    }
}
