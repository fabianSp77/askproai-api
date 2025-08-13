<?php

/**
 * Feature flags helper functions
 */

if (!function_exists('feature')) {
    /**
     * Check if a feature is enabled
     *
     * @param string $feature
     * @return bool
     */
    function feature(string $feature): bool
    {
        // For now, all features are enabled
        // In production, this would check against config or database
        return true;
    }
}

if (!function_exists('features')) {
    /**
     * Get all features configuration
     *
     * @return array
     */
    function features(): array
    {
        return [
            'adminv2' => true,
            'portal' => true,
            'api' => true,
        ];
    }
}