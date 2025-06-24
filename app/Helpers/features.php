<?php

use App\Services\FeatureFlagService;

if (!function_exists('feature')) {
    /**
     * Check if a feature is enabled
     * 
     * @param string $key Feature flag key
     * @param string|null $companyId Company ID for override check
     * @return bool
     */
    function feature(string $key, ?string $companyId = null): bool
    {
        return app(FeatureFlagService::class)->isEnabled($key, $companyId);
    }
}

if (!function_exists('features')) {
    /**
     * Get the feature flag service instance
     * 
     * @return FeatureFlagService
     */
    function features(): FeatureFlagService
    {
        return app(FeatureFlagService::class);
    }
}

if (!function_exists('feature_all')) {
    /**
     * Check multiple features at once
     * 
     * @param array $keys Feature flag keys
     * @param string|null $companyId Company ID for override check
     * @return array
     */
    function feature_all(array $keys, ?string $companyId = null): array
    {
        return app(FeatureFlagService::class)->areEnabled($keys, $companyId);
    }
}