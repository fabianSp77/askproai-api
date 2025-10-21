<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

/**
 * Retell Call-Specific Rate Limiter
 *
 * Prevents a single active Retell call from making too many API requests.
 * This protects against:
 * - Malicious loops in AI conversation logic
 * - Accidental infinite recursion
 * - DoS via single call spamming
 *
 * Rate limits are per call_id, not per IP/user.
 */
class RetellCallRateLimiter
{
    /**
     * Rate limit configuration for Retell calls
     */
    private const LIMITS = [
        // Maximum function calls per active call (lifetime)
        'total_per_call' => 50,

        // Maximum function calls per minute per call
        'per_minute' => 20,

        // Maximum same function calls per call (prevent loops)
        'same_function_per_call' => 10,

        // Cooldown period after limit exceeded (seconds)
        'cooldown' => 300, // 5 minutes
    ];

    /**
     * Handle an incoming request
     */
    public function handle(Request $request, Closure $next): Response
    {
        // IMPORTANT: This middleware is DISABLED for now because it was blocking Retell function calls
        // Rate limiting is handled by the throttle middleware only
        //
        // The issue: Retell doesn't send call_id in the function call parameters,
        // so we can't validate it. Throttle middleware provides enough rate limiting.

        // Just pass through - no blocking!
        return $next($request);
    }

    /**
     * Extract call_id from request
     *
     * Retell can send call_id in multiple places:
     * - Top-level JSON: {"call_id": "call_xxx", ...}
     * - Nested in function args: {"args": {"call_id": "call_xxx"}, ...}
     * - As HTTP header: X-Call-Id
     * - Also check in parameters passed to function handlers
     */
    private function extractCallId(Request $request): ?string
    {
        // Try direct top-level input first (most common from Retell)
        $callId = $request->input('call_id');
        if ($callId) {
            Log::debug('✅ call_id extracted from top-level input', ['call_id' => $callId]);
            return $callId;
        }

        // Try nested in args/parameters
        $callId = $request->input('args.call_id') ?? $request->input('parameters.call_id');
        if ($callId) {
            Log::debug('✅ call_id extracted from nested input', ['call_id' => $callId]);
            return $callId;
        }

        // Try from headers (Retell sometimes sends it there)
        $callId = $request->header('X-Call-Id') ?? $request->header('X-Retell-Call-Id');
        if ($callId) {
            Log::debug('✅ call_id extracted from headers', ['call_id' => $callId]);
            return $callId;
        }

        // Last resort: try parsing the raw JSON body directly
        // This handles cases where Laravel's input() might not parse correctly
        try {
            $body = $request->getContent();
            if (!empty($body)) {
                $decoded = json_decode($body, true);
                if (is_array($decoded) && isset($decoded['call_id'])) {
                    Log::debug('✅ call_id extracted from raw JSON body', ['call_id' => $decoded['call_id']]);
                    return $decoded['call_id'];
                }
            }
        } catch (\Exception $e) {
            Log::warning('Could not parse request body for call_id', ['error' => $e->getMessage()]);
        }

        // If we still don't have call_id, log it for debugging
        Log::warning('⚠️  Could not extract call_id from request', [
            'path' => $request->path(),
            'method' => $request->method(),
            'top_level_input' => $request->all() ? array_keys($request->all()) : [],
            'headers_sample' => array_slice($request->headers->all(), 0, 5, true),
        ]);

        return null;
    }

    /**
     * Extract function name from request
     */
    private function extractFunctionName(Request $request): string
    {
        return $request->input('function_name')
            ?? $request->input('function')
            ?? $request->path();
    }

    /**
     * Check if call is blocked (in cooldown)
     */
    private function isCallBlocked(string $callId): bool
    {
        $blockKey = "retell_call_blocked:{$callId}";
        return Cache::has($blockKey);
    }

    /**
     * Check all rate limits for this call
     */
    private function checkRateLimits(string $callId, string $functionName): array
    {
        // Get current counters
        $totalKey = "retell_call_total:{$callId}";
        $minuteKey = "retell_call_minute:{$callId}";
        $functionKey = "retell_call_func:{$callId}:{$functionName}";

        $totalCount = (int) Cache::get($totalKey, 0);
        $minuteCount = (int) Cache::get($minuteKey, 0);
        $functionCount = (int) Cache::get($functionKey, 0);

        // Check limits
        $checks = [
            'total' => [
                'current' => $totalCount,
                'limit' => self::LIMITS['total_per_call'],
                'exceeded' => $totalCount >= self::LIMITS['total_per_call'],
            ],
            'per_minute' => [
                'current' => $minuteCount,
                'limit' => self::LIMITS['per_minute'],
                'exceeded' => $minuteCount >= self::LIMITS['per_minute'],
            ],
            'same_function' => [
                'current' => $functionCount,
                'limit' => self::LIMITS['same_function_per_call'],
                'exceeded' => $functionCount >= self::LIMITS['same_function_per_call'],
            ],
        ];

        // Determine if request is allowed
        $allowed = !$checks['total']['exceeded']
            && !$checks['per_minute']['exceeded']
            && !$checks['same_function']['exceeded'];

        return [
            'allowed' => $allowed,
            'checks' => $checks,
            'call_id' => $callId,
            'function' => $functionName,
        ];
    }

    /**
     * Increment rate limit counters
     */
    private function incrementCounters(string $callId, string $functionName): void
    {
        $prefix = config('cache.prefix');

        // Total counter (expires after 30 minutes of inactivity)
        $totalKey = "retell_call_total:{$callId}";
        Cache::increment($totalKey);
        Redis::expire($prefix . $totalKey, 1800); // 30 minutes

        // Per-minute counter
        $minuteKey = "retell_call_minute:{$callId}";
        if (!Cache::has($minuteKey)) {
            Cache::put($minuteKey, 1, 60); // 1 minute TTL
        } else {
            Cache::increment($minuteKey);
        }

        // Per-function counter
        $functionKey = "retell_call_func:{$callId}:{$functionName}";
        Cache::increment($functionKey);
        Redis::expire($prefix . $functionKey, 1800); // 30 minutes
    }

    /**
     * Handle blocked call
     */
    private function handleBlockedCall(string $callId): Response
    {
        Log::warning('Blocked Retell call attempted request', [
            'call_id' => $callId,
            'reason' => 'Call in cooldown period',
        ]);

        return response()->json([
            'status' => 'error',
            'message' => 'This call has been temporarily blocked due to excessive requests. Please try again later.',
            'error_code' => 'call_rate_limit_exceeded',
        ], 429);
    }

    /**
     * Handle rate limit exceeded
     */
    private function handleRateLimitExceeded(string $callId, string $functionName, array $limitCheck): Response
    {
        // Determine which limit was exceeded
        $exceeded = [];
        foreach ($limitCheck['checks'] as $type => $check) {
            if ($check['exceeded']) {
                $exceeded[] = "{$type} ({$check['current']}/{$check['limit']})";
            }
        }

        Log::warning('Retell call rate limit exceeded', [
            'call_id' => $callId,
            'function' => $functionName,
            'limits_exceeded' => $exceeded,
            'checks' => $limitCheck['checks'],
        ]);

        // Block call for cooldown period
        $this->blockCall($callId);

        return response()->json([
            'status' => 'error',
            'message' => 'Rate limit exceeded for this call. Please reduce request frequency.',
            'error_code' => 'call_rate_limit_exceeded',
            'limits_exceeded' => $exceeded,
        ], 429);
    }

    /**
     * Block call temporarily
     */
    private function blockCall(string $callId): void
    {
        $blockKey = "retell_call_blocked:{$callId}";

        Cache::put($blockKey, [
            'blocked_at' => now()->toIso8601String(),
            'reason' => 'Rate limit exceeded',
        ], self::LIMITS['cooldown']);

        Log::critical('Retell call blocked', [
            'call_id' => $callId,
            'cooldown_seconds' => self::LIMITS['cooldown'],
            'blocked_until' => now()->addSeconds(self::LIMITS['cooldown'])->toIso8601String(),
        ]);
    }

    /**
     * Add rate limit headers to response
     */
    private function addRateLimitHeaders(Response $response, string $callId, array $limitCheck): void
    {
        $totalCheck = $limitCheck['checks']['total'];

        $response->headers->set('X-Call-RateLimit-Limit', $totalCheck['limit']);
        $response->headers->set('X-Call-RateLimit-Remaining', max(0, $totalCheck['limit'] - $totalCheck['current']));
        $response->headers->set('X-Call-Id', $callId);
    }

    /**
     * Get rate limit status for call (for debugging)
     */
    public static function getCallStatus(string $callId): array
    {
        $totalKey = "retell_call_total:{$callId}";
        $minuteKey = "retell_call_minute:{$callId}";
        $blockKey = "retell_call_blocked:{$callId}";

        return [
            'call_id' => $callId,
            'total_requests' => (int) Cache::get($totalKey, 0),
            'requests_this_minute' => (int) Cache::get($minuteKey, 0),
            'is_blocked' => Cache::has($blockKey),
            'block_info' => Cache::get($blockKey),
        ];
    }
}
