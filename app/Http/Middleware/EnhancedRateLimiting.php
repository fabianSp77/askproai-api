<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnhancedRateLimiting
{
    /**
     * Rate limit configurations per endpoint type
     */
    protected array $limits = [
        'auth' => ['attempts' => 5, 'decay' => 300], // 5 attempts per 5 minutes
        'api' => ['attempts' => 60, 'decay' => 60], // 60 per minute
        'webhook' => ['attempts' => 100, 'decay' => 60], // 100 per minute
        'portal' => ['attempts' => 30, 'decay' => 60], // 30 per minute
        'sensitive' => ['attempts' => 3, 'decay' => 600], // 3 per 10 minutes
    ];

    /**
     * Handle an incoming request
     */
    public function handle(Request $request, Closure $next, string $type = 'api'): Response
    {
        $key = $this->resolveRequestKey($request, $type);
        $maxAttempts = $this->limits[$type]['attempts'] ?? 60;
        $decayMinutes = ($this->limits[$type]['decay'] ?? 60) / 60;

        // Check if IP is blocked
        if ($this->isBlocked($request->ip())) {
            Log::warning('Blocked IP attempted access', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'type' => $type,
            ]);
            
            return $this->buildBlockedResponse();
        }

        // Get current attempts
        $attempts = Cache::get($key, 0);

        // Check if rate limited
        if ($attempts >= $maxAttempts) {
            // Check for repeated violations
            $this->recordViolation($request, $type);
            
            Log::warning('Rate limit exceeded', [
                'ip' => $request->ip(),
                'key' => $key,
                'attempts' => $attempts,
                'type' => $type,
                'user_agent' => $request->userAgent(),
            ]);

            return $this->buildRateLimitResponse($key, $maxAttempts);
        }

        // Increment attempts
        Cache::put($key, $attempts + 1, now()->addMinutes($decayMinutes));

        // Add rate limit headers
        $response = $next($request);
        
        return $this->addRateLimitHeaders($response, $maxAttempts - $attempts - 1, $key);
    }

    /**
     * Resolve the request key for rate limiting
     */
    protected function resolveRequestKey(Request $request, string $type): string
    {
        $parts = ['rate_limit', $type];

        // Add user ID if authenticated
        if ($user = $request->user()) {
            $parts[] = 'user';
            $parts[] = $user->id;
        } else {
            // Use IP for non-authenticated requests
            $parts[] = 'ip';
            $parts[] = $request->ip();
        }

        // Add route for granular limiting
        if ($route = $request->route()) {
            $parts[] = 'route';
            $parts[] = md5($route->uri());
        }

        return implode(':', $parts);
    }

    /**
     * Check if IP is blocked
     */
    protected function isBlocked(string $ip): bool
    {
        return Cache::has("blocked_ip:{$ip}");
    }

    /**
     * Record a rate limit violation
     */
    protected function recordViolation(Request $request, string $type): void
    {
        $ip = $request->ip();
        $violationKey = "violations:{$ip}";
        
        $violations = Cache::get($violationKey, 0) + 1;
        Cache::put($violationKey, $violations, now()->addHours(24));

        // Block IP after repeated violations
        if ($violations >= 10) {
            $this->blockIp($ip, 'Repeated rate limit violations');
        }

        // Log to database for monitoring
        try {
            \DB::table('rate_limit_violations')->insert([
                'ip_address' => $ip,
                'endpoint_type' => $type,
                'url' => $request->fullUrl(),
                'user_agent' => $request->userAgent(),
                'violations' => $violations,
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log rate limit violation', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Block an IP address
     */
    protected function blockIp(string $ip, string $reason): void
    {
        Cache::put("blocked_ip:{$ip}", [
            'reason' => $reason,
            'blocked_at' => now(),
        ], now()->addHours(24));

        Log::critical('IP address blocked', [
            'ip' => $ip,
            'reason' => $reason,
        ]);

        // Send alert to administrators
        try {
            \DB::table('security_alerts')->insert([
                'type' => 'ip_blocked',
                'severity' => 'high',
                'message' => "IP {$ip} has been blocked: {$reason}",
                'data' => json_encode(['ip' => $ip, 'reason' => $reason]),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create security alert', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Build rate limit response
     */
    protected function buildRateLimitResponse(string $key, int $maxAttempts): Response
    {
        $retryAfter = Cache::get($key . ':timer', 60);

        return response()->json([
            'error' => 'Too Many Requests',
            'message' => "Rate limit exceeded. Maximum {$maxAttempts} requests allowed.",
            'retry_after' => $retryAfter,
        ], 429)->withHeaders([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => 0,
            'Retry-After' => $retryAfter,
            'X-RateLimit-Reset' => now()->addSeconds($retryAfter)->timestamp,
        ]);
    }

    /**
     * Build blocked response
     */
    protected function buildBlockedResponse(): Response
    {
        return response()->json([
            'error' => 'Forbidden',
            'message' => 'Your IP address has been blocked due to suspicious activity.',
        ], 403);
    }

    /**
     * Add rate limit headers to response
     */
    protected function addRateLimitHeaders(Response $response, int $remaining, string $key): Response
    {
        $limit = $this->limits['api']['attempts'] ?? 60;
        $resetTime = Cache::get($key . ':reset', now()->addMinutes(1)->timestamp);

        $response->headers->set('X-RateLimit-Limit', $limit);
        $response->headers->set('X-RateLimit-Remaining', max(0, $remaining));
        $response->headers->set('X-RateLimit-Reset', $resetTime);

        return $response;
    }
}