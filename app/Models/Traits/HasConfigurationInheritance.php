<?php

namespace App\Models\Traits;

use App\Models\PolicyConfiguration;
use App\Models\NotificationConfiguration;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Cache;

/**
 * HasConfigurationInheritance Trait
 *
 * Provides hierarchical configuration inheritance for Company, Branch, Service, and Staff models.
 *
 * HIERARCHY RULES:
 * 1. Company → Branch → Service → Staff (4-level hierarchy)
 * 2. Policy configurations: Staff/Service/Branch can override Company defaults
 * 3. Notification configurations: Staff can override ONLY preferences (channels, templates), NOT business policies
 * 4. Configuration resolution traverses up the hierarchy until a value is found
 * 5. Caching is used for performance (5-minute TTL)
 *
 * USAGE:
 * - Company: Root level, defines default policies for entire organization
 * - Branch: Location-specific overrides (e.g., different cancellation policies)
 * - Service: Service-specific requirements (e.g., premium services have stricter policies)
 * - Staff: Personal notification preferences ONLY (cannot override business rules)
 */
trait HasConfigurationInheritance
{
    /**
     * Get all policy configurations for this entity.
     */
    public function policyConfigurations(): MorphMany
    {
        return $this->morphMany(PolicyConfiguration::class, 'configurable');
    }

    /**
     * Get all notification configurations for this entity.
     */
    public function notificationConfigurations(): MorphMany
    {
        return $this->morphMany(NotificationConfiguration::class, 'configurable');
    }

    /**
     * Get effective policy configuration by traversing the hierarchy.
     *
     * Hierarchy: Staff → Service → Branch → Company
     *
     * @param string $policyType The policy type (cancellation, reschedule, recurring)
     * @return array|null The effective policy configuration or null if not found
     */
    public function getEffectivePolicyConfig(string $policyType): ?array
    {
        $cacheKey = $this->getPolicyCacheKey($policyType);

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($policyType) {
            // Try to find policy at current level
            $policy = $this->policyConfigurations()
                ->where('policy_type', $policyType)
                ->first();

            if ($policy) {
                return $policy->config;
            }

            // Traverse up the hierarchy
            $parent = $this->getConfigurationParent();

            if ($parent && method_exists($parent, 'getEffectivePolicyConfig')) {
                return $parent->getEffectivePolicyConfig($policyType);
            }

            return null;
        });
    }

    /**
     * Get effective notification configuration by traversing the hierarchy.
     *
     * Staff level can only override notification preferences (channels, templates),
     * not business policies (timing, retry logic).
     *
     * Hierarchy: Staff → Service → Branch → Company
     *
     * @param string $eventType The notification event type
     * @param string $channel The notification channel (email, sms, whatsapp, push)
     * @return array|null The effective notification configuration or null if not found
     */
    public function getEffectiveNotificationConfig(string $eventType, string $channel): ?array
    {
        $cacheKey = $this->getNotificationCacheKey($eventType, $channel);

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($eventType, $channel) {
            // Try to find configuration at current level
            $config = $this->notificationConfigurations()
                ->where('event_type', $eventType)
                ->where('channel', $channel)
                ->first();

            if ($config) {
                // If this is a Staff-level config, merge with parent business policies
                if ($this instanceof \App\Models\Staff) {
                    $parentConfig = $this->getParentNotificationConfig($eventType, $channel);

                    if ($parentConfig) {
                        // Staff can override: channel, template_override, is_enabled
                        // Staff CANNOT override: retry_count, retry_delay_minutes (business policies)
                        return array_merge($parentConfig, [
                            'channel' => $config->channel,
                            'fallback_channel' => $config->fallback_channel ?? $parentConfig['fallback_channel'] ?? null,
                            'template_override' => $config->template_override ?? $parentConfig['template_override'] ?? null,
                            'is_enabled' => $config->is_enabled,
                            'metadata' => array_merge($parentConfig['metadata'] ?? [], $config->metadata ?? []),
                        ]);
                    }
                }

                return [
                    'channel' => $config->channel,
                    'fallback_channel' => $config->fallback_channel,
                    'is_enabled' => $config->is_enabled,
                    'retry_count' => $config->retry_count,
                    'retry_delay_minutes' => $config->retry_delay_minutes,
                    'template_override' => $config->template_override,
                    'metadata' => $config->metadata ?? [],
                ];
            }

            // Traverse up the hierarchy
            $parent = $this->getConfigurationParent();

            if ($parent && method_exists($parent, 'getEffectiveNotificationConfig')) {
                return $parent->getEffectiveNotificationConfig($eventType, $channel);
            }

            return null;
        });
    }

    /**
     * Set a policy configuration for this entity.
     *
     * @param string $policyType The policy type (cancellation, reschedule, recurring)
     * @param array $config The policy configuration array
     * @param bool $isOverride Whether this overrides a parent policy
     * @return PolicyConfiguration
     */
    public function setPolicyConfig(string $policyType, array $config, bool $isOverride = false): PolicyConfiguration
    {
        $policy = $this->policyConfigurations()->updateOrCreate(
            ['policy_type' => $policyType],
            [
                'config' => $config,
                'is_override' => $isOverride,
            ]
        );

        // Clear cache
        Cache::forget($this->getPolicyCacheKey($policyType));

        return $policy;
    }

    /**
     * Set a notification configuration for this entity.
     *
     * @param string $eventType The notification event type
     * @param string $channel The notification channel
     * @param array $settings The notification settings
     * @return NotificationConfiguration
     */
    public function setNotificationConfig(string $eventType, string $channel, array $settings): NotificationConfiguration
    {
        $config = $this->notificationConfigurations()->updateOrCreate(
            [
                'event_type' => $eventType,
                'channel' => $channel,
            ],
            $settings
        );

        // Clear cache
        Cache::forget($this->getNotificationCacheKey($eventType, $channel));

        return $config;
    }

    /**
     * Get the parent entity in the configuration hierarchy.
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    protected function getConfigurationParent()
    {
        if ($this instanceof \App\Models\Staff) {
            // Staff → Service or Branch
            return $this->service ?? $this->branch;
        }

        if ($this instanceof \App\Models\Service) {
            // Service → Branch or Company
            return $this->branch ?? $this->company;
        }

        if ($this instanceof \App\Models\Branch) {
            // Branch → Company
            return $this->company;
        }

        // Company has no parent
        return null;
    }

    /**
     * Get parent notification config for Staff-level override merging.
     *
     * @param string $eventType
     * @param string $channel
     * @return array|null
     */
    protected function getParentNotificationConfig(string $eventType, string $channel): ?array
    {
        $parent = $this->getConfigurationParent();

        if ($parent && method_exists($parent, 'getEffectiveNotificationConfig')) {
            return $parent->getEffectiveNotificationConfig($eventType, $channel);
        }

        return null;
    }

    /**
     * Generate cache key for policy configuration.
     *
     * @param string $policyType
     * @return string
     */
    protected function getPolicyCacheKey(string $policyType): string
    {
        return sprintf(
            'policy_config:%s:%s:%s',
            get_class($this),
            $this->id,
            $policyType
        );
    }

    /**
     * Generate cache key for notification configuration.
     *
     * @param string $eventType
     * @param string $channel
     * @return string
     */
    protected function getNotificationCacheKey(string $eventType, string $channel): string
    {
        return sprintf(
            'notification_config:%s:%s:%s:%s',
            get_class($this),
            $this->id,
            $eventType,
            $channel
        );
    }

    /**
     * Clear all configuration caches for this entity.
     */
    public function clearConfigurationCache(): void
    {
        // Clear policy caches
        foreach (['cancellation', 'reschedule', 'recurring'] as $policyType) {
            Cache::forget($this->getPolicyCacheKey($policyType));
        }

        // Clear notification caches (would need to iterate through all event types and channels)
        // For simplicity, we can use cache tags in production, but Laravel's file cache doesn't support tags
        // Alternative: Use Cache::flush() cautiously or implement a more sophisticated cache clearing strategy
    }
}
