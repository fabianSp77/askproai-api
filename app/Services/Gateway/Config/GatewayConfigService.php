<?php

namespace App\Services\Gateway\Config;

use App\Models\Company;
use App\Models\PolicyConfiguration;
use Illuminate\Support\Facades\Cache;

/**
 * GatewayConfigService - Gateway configuration management
 *
 * Provides centralized access to company-level gateway configuration
 * including mode settings, hybrid config, and feature enablement.
 *
 * Performance:
 * - Cached policy lookups (~0.5ms cache hit, ~20ms DB query)
 * - Cache TTL: 5 minutes (managed by PolicyConfiguration)
 * - Automatic cache invalidation on policy save/delete
 *
 * @package App\Services\Gateway\Config
 */
class GatewayConfigService
{
    /**
     * Get complete gateway configuration for a company
     *
     * Returns array with:
     * - enabled: Whether gateway mode is enabled (bool)
     * - mode: Gateway mode (appointment|service_desk|hybrid)
     * - hybrid_config: Hybrid mode settings (if applicable)
     * - policy_id: PolicyConfiguration ID (if exists)
     *
     * @param Company $company
     * @return array Gateway configuration
     */
    public function getCompanyGatewayConfig(Company $company): array
    {
        $policy = PolicyConfiguration::getCachedPolicy(
            $company,
            PolicyConfiguration::POLICY_TYPE_GATEWAY_MODE
        );

        if (!$policy) {
            return [
                'enabled' => false,
                'mode' => config('gateway.default_mode', 'appointment'),
                'hybrid_config' => null,
                'policy_id' => null,
            ];
        }

        $config = $policy->getEffectiveConfig();

        return [
            'enabled' => $config['enabled'] ?? false,
            'mode' => $config['mode'] ?? config('gateway.default_mode', 'appointment'),
            'hybrid_config' => $config['mode'] === 'hybrid'
                ? $this->getHybridConfig($company)
                : null,
            'policy_id' => $policy->id,
        ];
    }

    /**
     * Check if gateway mode is enabled for a company
     *
     * Gateway is enabled when:
     * 1. Global feature flag is enabled
     * 2. Company has gateway_mode policy
     * 3. Policy config has enabled=true
     *
     * @param Company $company
     * @return bool
     */
    public function isGatewayEnabled(Company $company): bool
    {
        // Global feature flag check
        if (!config('gateway.mode_enabled', false)) {
            return false;
        }

        $policy = PolicyConfiguration::getCachedPolicy(
            $company,
            PolicyConfiguration::POLICY_TYPE_GATEWAY_MODE
        );

        if (!$policy) {
            return false;
        }

        $config = $policy->getEffectiveConfig();

        return $config['enabled'] ?? false;
    }

    /**
     * Get gateway mode for a company
     *
     * Returns the configured mode or default if not set.
     * Does NOT check if gateway is enabled.
     *
     * @param Company $company
     * @return string Gateway mode (appointment|service_desk|hybrid)
     */
    public function getMode(Company $company): string
    {
        $policy = PolicyConfiguration::getCachedPolicy(
            $company,
            PolicyConfiguration::POLICY_TYPE_GATEWAY_MODE
        );

        if (!$policy) {
            return config('gateway.default_mode', 'appointment');
        }

        $config = $policy->getEffectiveConfig();
        $mode = $config['mode'] ?? config('gateway.default_mode', 'appointment');

        // Validate against allowed modes
        if (!in_array($mode, config('gateway.modes', ['appointment']))) {
            return config('gateway.default_mode', 'appointment');
        }

        return $mode;
    }

    /**
     * Get hybrid mode configuration for a company
     *
     * Returns merged configuration from:
     * 1. Global config/gateway.php hybrid settings (defaults)
     * 2. Company policy hybrid_config overrides
     *
     * @param Company $company
     * @return array Hybrid configuration
     */
    public function getHybridConfig(Company $company): array
    {
        // Start with global defaults
        $hybridConfig = config('gateway.hybrid', [
            'intent_confidence_threshold' => 0.75,
            'fallback_mode' => 'appointment',
        ]);

        $policy = PolicyConfiguration::getCachedPolicy(
            $company,
            PolicyConfiguration::POLICY_TYPE_GATEWAY_MODE
        );

        if (!$policy) {
            return $hybridConfig;
        }

        $config = $policy->getEffectiveConfig();

        // Merge company-specific hybrid settings
        if (isset($config['hybrid_config']) && is_array($config['hybrid_config'])) {
            $hybridConfig = array_merge($hybridConfig, $config['hybrid_config']);
        }

        return $hybridConfig;
    }

    /**
     * Get service desk configuration for a company
     *
     * Returns service desk specific settings from policy.
     * Used in Phase 2 for service desk mode configuration.
     *
     * @param Company $company
     * @return array|null Service desk configuration or null if not set
     */
    public function getServiceDeskConfig(Company $company): ?array
    {
        $policy = PolicyConfiguration::getCachedPolicy(
            $company,
            PolicyConfiguration::POLICY_TYPE_SERVICE_DESK
        );

        if (!$policy) {
            return null;
        }

        return $policy->getEffectiveConfig();
    }

    /**
     * Get ticket routing configuration for a company
     *
     * Returns ticket routing rules from policy.
     * Used in Phase 2 for service desk ticket creation/routing.
     *
     * @param Company $company
     * @return array|null Ticket routing configuration or null if not set
     */
    public function getTicketRoutingConfig(Company $company): ?array
    {
        $policy = PolicyConfiguration::getCachedPolicy(
            $company,
            PolicyConfiguration::POLICY_TYPE_TICKET_ROUTING
        );

        if (!$policy) {
            return null;
        }

        return $policy->getEffectiveConfig();
    }
}
