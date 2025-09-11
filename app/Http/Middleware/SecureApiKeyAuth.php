<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class SecureApiKeyAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $this->extractApiKey($request);

        if (!$apiKey) {
            Log::warning('API request without key', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'path' => $request->path()
            ]);

            return response()->json([
                'error' => 'API key required',
                'message' => 'Please provide a valid API key in Authorization header'
            ], 401);
        }

        // Rate limiting per IP for brute force protection
        $ipRateLimitKey = 'api_auth:' . $request->ip();
        if (RateLimiter::tooManyAttempts($ipRateLimitKey, 10)) {
            Log::warning('API key brute force attempt', [
                'ip' => $request->ip(),
                'attempts' => RateLimiter::attempts($ipRateLimitKey)
            ]);

            return response()->json([
                'error' => 'Too many attempts',
                'message' => 'Rate limit exceeded. Please try again later.'
            ], 429);
        }

        // Verify API key (this will check hash)
        $tenant = Tenant::findByApiKey($apiKey);

        if (!$tenant) {
            RateLimiter::hit($ipRateLimitKey, 300); // 5 minute penalty

            Log::warning('Invalid API key used', [
                'key_prefix' => substr($apiKey, 0, 8) . '...',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return response()->json([
                'error' => 'Invalid API key',
                'message' => 'The provided API key is not valid'
            ], 401);
        }

        // Success - clear rate limit and set tenant context
        RateLimiter::clear($ipRateLimitKey);
        
        // Per-API-key rate limiting
        $apiKeyRateLimit = config('security.api_key_rate_limit', 100);
        $apiRateLimitKey = 'api_key:' . substr($apiKey, 0, 8);
        
        if (RateLimiter::tooManyAttempts($apiRateLimitKey, $apiKeyRateLimit)) {
            Log::warning('API key rate limit exceeded', [
                'tenant_id' => $tenant->id,
                'key_prefix' => substr($apiKey, 0, 8) . '...',
                'limit' => $apiKeyRateLimit
            ]);

            return response()->json([
                'error' => 'API rate limit exceeded',
                'message' => 'Your API key has exceeded the rate limit'
            ], 429);
        }
        
        RateLimiter::hit($apiRateLimitKey, 3600); // 1 hour window
        
        // Set authenticated tenant in request
        $request->attributes->set('authenticated_tenant', $tenant);

        Log::info('API key authenticated', [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'ip' => $request->ip()
        ]);

        return $next($request);
    }

    private function extractApiKey(Request $request): ?string
    {
        // Try Authorization header first (Bearer token)
        $authHeader = $request->header('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }

        // Log deprecated header attempts
        if ($request->header('X-API-Key')) {
            Log::warning('Deprecated X-API-Key header rejected', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'message' => 'Use Authorization: Bearer header instead'
            ]);
        }

        // Log insecure query parameter attempts
        if ($request->query('api_key')) {
            Log::warning('API key in query string rejected (security risk)', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'message' => 'API keys in URLs are insecure and logged'
            ]);
        }

        return null;
    }
}