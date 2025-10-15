<?php

namespace App\Listeners;

use App\Events\ConfigurationUpdated;
use App\Events\ConfigurationCreated;
use App\Events\ConfigurationDeleted;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Invalidate configuration caches when changes occur
 *
 * This listener ensures that cached configuration values are cleared
 * whenever a configuration is created, updated, or deleted.
 *
 * @package App\Listeners
 */
class InvalidateConfigurationCache
{
    /**
     * Handle ConfigurationUpdated event
     */
    public function handleUpdated(ConfigurationUpdated $event): void
    {
        $this->invalidateCache($event->getCacheTags(), $event->toArray());
    }

    /**
     * Handle ConfigurationCreated event
     */
    public function handleCreated(ConfigurationCreated $event): void
    {
        $this->invalidateCache($event->getCacheTags(), $event->toArray());
    }

    /**
     * Handle ConfigurationDeleted event
     */
    public function handleDeleted(ConfigurationDeleted $event): void
    {
        $this->invalidateCache($event->getCacheTags(), $event->toArray());
    }

    /**
     * Invalidate cache by tags
     */
    private function invalidateCache(array $tags, array $eventData): void
    {
        try {
            // If Redis is available, use tagged cache
            if (config('cache.default') === 'redis') {
                Cache::tags($tags)->flush();

                Log::info('Configuration cache invalidated (tagged)', [
                    'tags' => $tags,
                    'company_id' => $eventData['company_id'] ?? null,
                    'model_type' => $eventData['model_type'] ?? null,
                ]);
            } else {
                // Fallback: Clear specific cache keys
                foreach ($tags as $tag) {
                    $keys = $this->getCacheKeysForTag($tag);
                    foreach ($keys as $key) {
                        Cache::forget($key);
                    }
                }

                Log::info('Configuration cache invalidated (keys)', [
                    'tags' => $tags,
                    'company_id' => $eventData['company_id'] ?? null,
                ]);
            }

            // Also clear company-specific caches
            if (isset($eventData['company_id'])) {
                $this->clearCompanyCache($eventData['company_id']);
            }
        } catch (\Exception $e) {
            // Log error but don't fail the request
            Log::error('Failed to invalidate configuration cache', [
                'error' => $e->getMessage(),
                'tags' => $tags,
                'event_data' => $eventData,
            ]);
        }
    }

    /**
     * Get cache keys for a given tag
     *
     * This is a fallback for when Redis tagging is not available
     */
    private function getCacheKeysForTag(string $tag): array
    {
        // Parse tag format: "company:123", "config:api_key", "model:PolicyConfiguration:1"
        [$type, $identifier] = explode(':', $tag, 2);

        return match($type) {
            'company' => [
                "company:{$identifier}:config",
                "company:{$identifier}:policies",
                "company:{$identifier}:settings",
            ],
            'config' => [
                "config:{$identifier}",
            ],
            'model' => [
                "model:{$identifier}",
            ],
            default => [],
        };
    }

    /**
     * Clear all caches for a specific company
     */
    private function clearCompanyCache(string $companyId): void
    {
        $cacheKeys = [
            // HasConfigurationInheritance trait caches
            "config:company:{$companyId}",

            // PolicyConfiguration caches
            "policies:company:{$companyId}",

            // Company model caches
            "company:{$companyId}:settings",
            "company:{$companyId}:retell_settings",
            "company:{$companyId}:calcom_settings",

            // Navigation badge cache
            "filament:badge:policy_configurations:{$companyId}",
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }
}
