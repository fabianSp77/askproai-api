<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class SecureApiKeyAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $this->extractApiKey($request);

        if (! $apiKey) {
            Log::warning('API request without key', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'path' => $request->path(),
            ]);

            return response()->json([
                'error' => 'API key required',
                'message' => 'Please provide a valid API key in Authorization header',
            ], 401);
        }

        // Rate limiting per IP for brute force protection
        $rateLimitKey = 'api_auth:'.$request->ip();
        if (RateLimiter::tooManyAttempts($rateLimitKey, 10)) {
            Log::warning('API key brute force attempt', [
                'ip' => $request->ip(),
                'attempts' => RateLimiter::attempts($rateLimitKey),
            ]);

            return response()->json([
                'error' => 'Too many attempts',
                'message' => 'Rate limit exceeded. Please try again later.',
            ], 429);
        }

        // Verify API key (this will check hash)
        $tenant = Tenant::findByApiKey($apiKey);

        if (! $tenant) {
            RateLimiter::hit($rateLimitKey, 300); // 5 minute penalty

            Log::warning('Invalid API key used', [
                'key_prefix' => substr($apiKey, 0, 8).'...',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'error' => 'Invalid API key',
                'message' => 'The provided API key is not valid',
            ], 401);
        }

        // Success - clear rate limit and set tenant context
        RateLimiter::clear($rateLimitKey);

        // Set authenticated tenant in request
        $request->attributes->set('authenticated_tenant', $tenant);

        Log::info('API key authenticated', [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'ip' => $request->ip(),
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

        // Fallback to X-API-Key header (deprecated)
        $apiKeyHeader = $request->header('X-API-Key');
        if ($apiKeyHeader) {
            Log::info('Deprecated X-API-Key header used', [
                'ip' => $request->ip(),
                'message' => 'Please migrate to Authorization: Bearer header',
            ]);

            return $apiKeyHeader;
        }

        // Query parameter (highly discouraged)
        $queryApiKey = $request->query('api_key');
        if ($queryApiKey) {
            Log::warning('API key in query string (insecure)', [
                'ip' => $request->ip(),
                'message' => 'API keys in URLs are logged and cached. Use headers instead.',
            ]);

            return $queryApiKey;
        }

        return null;
    }
}
