<?php

namespace App\Gateway\RateLimit;

use Illuminate\Http\Request;
use Illuminate\Contracts\Cache\Repository as CacheInterface;
use Illuminate\Support\Facades\Log;

class AdvancedRateLimiter
{
    private CacheInterface $cache;
    private array $configs;

    public function __construct(CacheInterface $cache, array $configs = [])
    {
        $this->cache = $cache;
        $this->configs = $configs;
    }

    /**
     * Check if the request exceeds rate limits
     */
    public function checkLimits(Request $request): RateLimitResult
    {
        $user = $request->user();
        $endpoint = $this->resolveEndpoint($request);
        
        $checks = [
            // Global rate limit by IP
            $this->checkGlobalLimit($request->ip()),
            
            // User-specific limit
            $user ? $this->checkUserLimit($user->id) : null,
            
            // Company-specific limit  
            $user ? $this->checkCompanyLimit($user->company_id) : null,
            
            // Endpoint-specific limit
            $this->checkEndpointLimit($endpoint, $user?->id ?? $request->ip()),
            
            // Resource-specific limit (e.g., calls/appointments)
            $this->checkResourceLimit($endpoint, $user?->company_id),
        ];

        $failedChecks = array_filter($checks, fn($check) => $check && !$check->allowed);

        if (!empty($failedChecks)) {
            $mostRestrictive = $this->getMostRestrictiveLimit($failedChecks);
            return $mostRestrictive;
        }

        return new RateLimitResult(true, null, null, null);
    }

    /**
     * Check global IP-based rate limit
     */
    private function checkGlobalLimit(string $ip): RateLimitResult
    {
        $key = "rate_limit:global:ip:{$ip}";
        $limit = $this->configs['global_ip_limit'] ?? ['requests' => 1000, 'window' => 3600];
        
        return $this->checkLimit($key, $limit['requests'], $limit['window']);
    }

    /**
     * Check user-specific rate limit
     */
    private function checkUserLimit(int $userId): RateLimitResult
    {
        $key = "rate_limit:user:{$userId}";
        $limit = $this->configs['user_limit'] ?? ['requests' => 500, 'window' => 3600];
        
        return $this->checkLimit($key, $limit['requests'], $limit['window']);
    }

    /**
     * Check company-specific rate limit
     */
    private function checkCompanyLimit(int $companyId): RateLimitResult
    {
        $key = "rate_limit:company:{$companyId}";
        $tier = $this->getCompanyTier($companyId);
        $baseLimit = $this->configs['company_limit'] ?? ['requests' => 2000, 'window' => 3600];
        
        $multiplier = $this->configs['tier_multipliers'][$tier] ?? 1.0;
        $limit = [
            'requests' => (int) ($baseLimit['requests'] * $multiplier),
            'window' => $baseLimit['window']
        ];
        
        return $this->checkLimit($key, $limit['requests'], $limit['window']);
    }

    /**
     * Check endpoint-specific rate limit
     */
    private function checkEndpointLimit(string $endpoint, string $identifier): RateLimitResult
    {
        $key = "rate_limit:endpoint:{$endpoint}:{$identifier}";
        $limit = $this->getEndpointConfig($endpoint);
        
        return $this->checkLimit($key, $limit['requests'], $limit['window']);
    }

    /**
     * Check resource-specific rate limit
     */
    private function checkResourceLimit(string $endpoint, ?int $companyId): ?RateLimitResult
    {
        if (!$companyId) {
            return null;
        }

        $resourceType = $this->extractResourceType($endpoint);
        if (!$resourceType) {
            return null;
        }

        $key = "rate_limit:resource:{$resourceType}:company:{$companyId}";
        $limit = $this->getResourceConfig($resourceType, $companyId);
        
        return $this->checkLimit($key, $limit['requests'], $limit['window']);
    }

    /**
     * Check individual rate limit
     */
    private function checkLimit(string $key, int $maxRequests, int $windowSeconds): RateLimitResult
    {
        $current = $this->cache->get($key, 0);
        $remaining = max(0, $maxRequests - $current);
        
        if ($current >= $maxRequests) {
            $retryAfter = $this->cache->get($key . ':expires', $windowSeconds);
            
            return new RateLimitResult(
                false,
                $maxRequests,
                0,
                $retryAfter
            );
        }

        // Increment counter
        if ($current === 0) {
            $this->cache->put($key, 1, $windowSeconds);
            $this->cache->put($key . ':expires', $windowSeconds, $windowSeconds);
        } else {
            $this->cache->increment($key);
        }

        return new RateLimitResult(
            true,
            $maxRequests,
            $remaining - 1,
            null
        );
    }

    /**
     * Get configuration for specific endpoint
     */
    private function getEndpointConfig(string $endpoint): array
    {
        $endpointConfigs = $this->configs['endpoint_limits'] ?? [];
        
        // Try exact match first
        if (isset($endpointConfigs[$endpoint])) {
            return $endpointConfigs[$endpoint];
        }

        // Try pattern matching
        foreach ($endpointConfigs as $pattern => $config) {
            if ($this->matchesPattern($endpoint, $pattern)) {
                return $config;
            }
        }

        // Default limits
        return $this->configs['default_limits'] ?? ['requests' => 60, 'window' => 3600];
    }

    /**
     * Get configuration for specific resource type
     */
    private function getResourceConfig(string $resourceType, int $companyId): array
    {
        $tier = $this->getCompanyTier($companyId);
        $baseConfig = $this->configs['resource_limits'][$resourceType] ?? ['requests' => 100, 'window' => 3600];
        
        $multiplier = $this->configs['tier_multipliers'][$tier] ?? 1.0;
        
        return [
            'requests' => (int) ($baseConfig['requests'] * $multiplier),
            'window' => $baseConfig['window']
        ];
    }

    /**
     * Resolve endpoint path for rate limiting
     */
    private function resolveEndpoint(Request $request): string
    {
        $path = $request->path();
        
        // Normalize paths with IDs
        $path = preg_replace('/\/\d+(?=\/|$)/', '/{id}', $path);
        $path = preg_replace('/\/[a-f0-9-]{36}(?=\/|$)/', '/{uuid}', $path);
        
        return $path;
    }

    /**
     * Extract resource type from endpoint
     */
    private function extractResourceType(string $endpoint): ?string
    {
        if (preg_match('/business\/api\/([^\/]+)/', $endpoint, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    /**
     * Get company tier for rate limit calculations
     */
    private function getCompanyTier(?int $companyId): string
    {
        if (!$companyId) {
            return 'free';
        }

        // This would typically query the database or cache
        // For now, return a default tier
        return cache()->remember(
            "company_tier:{$companyId}",
            3600,
            function () use ($companyId) {
                $company = \App\Models\Company::find($companyId);
                return $company?->subscription_tier ?? 'free';
            }
        );
    }

    /**
     * Check if endpoint matches pattern
     */
    private function matchesPattern(string $endpoint, string $pattern): bool
    {
        $pattern = str_replace(['*', '{id}', '{uuid}'], ['.*', '\d+', '[a-f0-9-]{36}'], $pattern);
        return preg_match("#^{$pattern}$#", $endpoint);
    }

    /**
     * Get the most restrictive rate limit from failed checks
     */
    private function getMostRestrictiveLimit(array $failedChecks): RateLimitResult
    {
        return array_reduce($failedChecks, function ($mostRestrictive, $check) {
            if (!$mostRestrictive || $check->retryAfter < $mostRestrictive->retryAfter) {
                return $check;
            }
            return $mostRestrictive;
        });
    }

    /**
     * Get rate limiter status for monitoring
     */
    public function getStatus(?int $companyId = null): array
    {
        $status = [
            'enabled' => true,
            'global_limits' => $this->configs['global_ip_limit'] ?? null,
            'default_limits' => $this->configs['default_limits'] ?? null,
        ];

        if ($companyId) {
            $tier = $this->getCompanyTier($companyId);
            $status['company_tier'] = $tier;
            $status['tier_multiplier'] = $this->configs['tier_multipliers'][$tier] ?? 1.0;
        }

        return $status;
    }

    /**
     * Reset rate limits for a specific key (admin function)
     */
    public function resetLimits(string $key): bool
    {
        $this->cache->forget($key);
        $this->cache->forget($key . ':expires');
        
        Log::info('Rate limits reset', ['key' => $key]);
        
        return true;
    }
}

class RateLimitResult
{
    public function __construct(
        public bool $allowed,
        public ?int $limit,
        public ?int $remaining,
        public ?int $retryAfter
    ) {}

    public function toArray(): array
    {
        return [
            'allowed' => $this->allowed,
            'limit' => $this->limit,
            'remaining' => $this->remaining,
            'retry_after' => $this->retryAfter,
        ];
    }
}