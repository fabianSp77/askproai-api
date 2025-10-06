<?php

namespace App\Services\Policies;

use App\Models\PolicyConfiguration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Service layer for policy configuration resolution and management
 * Optimizes hierarchy traversal with batch loading and cache warming
 */
class PolicyConfigurationService
{
    private const CACHE_TTL = 300; // 5 minutes
    private const CACHE_PREFIX = 'policy_config';

    /**
     * Resolve policy configuration for an entity
     * Traverses hierarchy: Staff → Service → Branch → Company
     */
    public function resolvePolicy(Model $entity, string $policyType): ?array
    {
        $cacheKey = $this->getCacheKey($entity, $policyType);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($entity, $policyType) {
            return $this->resolvePolicyFromDatabase($entity, $policyType);
        });
    }

    /**
     * Batch resolve policies for multiple entities (optimized)
     */
    public function resolveBatch(Collection $entities, string $policyType): array
    {
        $results = [];
        $entitiesToQuery = [];

        // Check cache first
        foreach ($entities as $entity) {
            $cacheKey = $this->getCacheKey($entity, $policyType);

            if (Cache::has($cacheKey)) {
                $results[$entity->id] = Cache::get($cacheKey);
            } else {
                $entitiesToQuery[] = $entity;
            }
        }

        // Batch query for uncached entities
        if (!empty($entitiesToQuery)) {
            $ids = array_map(fn(Model $e) => $e->id, $entitiesToQuery);
            $type = get_class($entitiesToQuery[0]);

            $configs = PolicyConfiguration::where('configurable_type', $type)
                ->whereIn('configurable_id', $ids)
                ->where('policy_type', $policyType)
                ->get()
                ->keyBy('configurable_id');

            foreach ($entitiesToQuery as $entity) {
                $config = $configs->get($entity->id);
                $result = $config ? $config->config : $this->resolveFromParent($entity, $policyType);

                $cacheKey = $this->getCacheKey($entity, $policyType);
                Cache::put($cacheKey, $result, self::CACHE_TTL);
                $results[$entity->id] = $result;
            }
        }

        return $results;
    }

    /**
     * Warm cache for an entity's policies
     */
    public function warmCache(Model $entity, ?array $policyTypes = null): int
    {
        $types = $policyTypes ?? ['cancellation', 'reschedule', 'recurring'];
        $warmed = 0;

        foreach ($types as $type) {
            $this->resolvePolicy($entity, $type);
            $warmed++;
        }

        return $warmed;
    }

    /**
     * Clear cache for an entity
     */
    public function clearCache(Model $entity, ?string $policyType = null): void
    {
        if ($policyType) {
            $cacheKey = $this->getCacheKey($entity, $policyType);
            Cache::forget($cacheKey);
        } else {
            // Clear all policy types
            foreach (['cancellation', 'reschedule', 'recurring'] as $type) {
                $cacheKey = $this->getCacheKey($entity, $type);
                Cache::forget($cacheKey);
            }
        }
    }

    /**
     * Get all policy configurations for an entity (raw, no hierarchy)
     */
    public function getEntityPolicies(Model $entity): Collection
    {
        return PolicyConfiguration::where('configurable_type', get_class($entity))
            ->where('configurable_id', $entity->id)
            ->get();
    }

    /**
     * Set policy configuration for an entity
     */
    public function setPolicy(Model $entity, string $policyType, array $config, bool $isOverride = false): PolicyConfiguration
    {
        $policy = PolicyConfiguration::updateOrCreate(
            [
                'configurable_type' => get_class($entity),
                'configurable_id' => $entity->id,
                'policy_type' => $policyType,
            ],
            [
                'config' => $config,
                'is_override' => $isOverride,
            ]
        );

        // Clear cache
        $this->clearCache($entity, $policyType);

        return $policy;
    }

    /**
     * Delete policy configuration
     */
    public function deletePolicy(Model $entity, string $policyType): bool
    {
        $deleted = PolicyConfiguration::where('configurable_type', get_class($entity))
            ->where('configurable_id', $entity->id)
            ->where('policy_type', $policyType)
            ->delete();

        if ($deleted) {
            $this->clearCache($entity, $policyType);
        }

        return $deleted > 0;
    }

    /**
     * Resolve from database (no cache)
     */
    private function resolvePolicyFromDatabase(Model $entity, string $policyType): ?array
    {
        // Check entity's own policy
        $policy = PolicyConfiguration::where('configurable_type', get_class($entity))
            ->where('configurable_id', $entity->id)
            ->where('policy_type', $policyType)
            ->first();

        if ($policy) {
            return $policy->config;
        }

        // Traverse hierarchy
        return $this->resolveFromParent($entity, $policyType);
    }

    /**
     * Resolve from parent in hierarchy
     */
    private function resolveFromParent(Model $entity, string $policyType): ?array
    {
        $parent = $this->getParentEntity($entity);

        if (!$parent) {
            return null;
        }

        return $this->resolvePolicy($parent, $policyType);
    }

    /**
     * Get parent entity based on hierarchy
     * Staff → Branch → Company (Staff services handled separately)
     * Service → Branch → Company
     */
    private function getParentEntity(Model $entity): ?Model
    {
        $class = get_class($entity);

        return match ($class) {
            'App\Models\Staff' => $entity->branch ?? null,
            'App\Models\Service' => $entity->branch ?? null,
            'App\Models\Branch' => $entity->company ?? null,
            'App\Models\Company' => null,
            default => null,
        };
    }

    /**
     * Generate cache key
     */
    private function getCacheKey(Model $entity, string $policyType): string
    {
        return sprintf(
            '%s_%s_%d_%s',
            self::CACHE_PREFIX,
            class_basename($entity),
            $entity->id,
            $policyType
        );
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(Model $entity): array
    {
        $stats = ['cached' => 0, 'missing' => 0];

        foreach (['cancellation', 'reschedule', 'recurring'] as $type) {
            $cacheKey = $this->getCacheKey($entity, $type);
            if (Cache::has($cacheKey)) {
                $stats['cached']++;
            } else {
                $stats['missing']++;
            }
        }

        return $stats;
    }
}
