<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Secure Polymorphic Query Helpers
 *
 * SECURITY FIX (SEC-003): Polymorphic Authorization Bypass Prevention
 *
 * Provides secure query helpers for polymorphic relationships with:
 * - Type whitelist validation
 * - Company ownership verification
 * - Cross-tenant isolation
 *
 * Usage:
 * ```php
 * use App\Filament\Concerns\HasSecurePolymorphicQueries;
 *
 * class NotificationAnalyticsWidget extends Widget
 * {
 *     use HasSecurePolymorphicQueries;
 *
 *     protected function getData(): array
 *     {
 *         $configs = $this->securePolymorphicQuery(
 *             PolicyConfiguration::class,
 *             'configurable'
 *         )->get();
 *     }
 * }
 * ```
 */
trait HasSecurePolymorphicQueries
{
    /**
     * Allowed polymorphic types for PolicyConfiguration.configurable
     *
     * SECURITY: Whitelist to prevent unauthorized type injection
     */
    protected array $allowedConfigurableTypes = [
        'App\Models\Company',
        'App\Models\Branch',
        'App\Models\Service',
        'App\Models\Staff',
    ];

    /**
     * Allowed polymorphic types for NotificationQueue.notifiable
     */
    protected array $allowedNotifiableTypes = [
        'App\Models\Customer',
        'App\Models\Staff',
        'App\Models\User',
    ];

    /**
     * Create a secure polymorphic query with company isolation
     *
     * @param string $modelClass The model class to query
     * @param string $polymorphicRelation The polymorphic relationship name
     * @param array|null $allowedTypes Custom whitelist (defaults to configurable types)
     * @return Builder
     */
    protected function securePolymorphicQuery(
        string $modelClass,
        string $polymorphicRelation = 'configurable',
        ?array $allowedTypes = null
    ): Builder {
        $companyId = $this->getCurrentCompanyId();

        // Use custom whitelist or default based on relation name
        $whitelist = $allowedTypes ?? $this->getWhitelistForRelation($polymorphicRelation);

        return $modelClass::query()
            ->where('company_id', $companyId)
            ->whereIn("{$polymorphicRelation}_type", $whitelist);
    }

    /**
     * Get polymorphic type whitelist for a given relation name
     *
     * @param string $relationName
     * @return array
     */
    protected function getWhitelistForRelation(string $relationName): array
    {
        return match ($relationName) {
            'configurable' => $this->allowedConfigurableTypes,
            'notifiable' => $this->allowedNotifiableTypes,
            default => [],
        };
    }

    /**
     * Validate polymorphic type against whitelist
     *
     * @param string $type The polymorphic type to validate
     * @param string $relationName The relation name ('configurable' or 'notifiable')
     * @return bool
     */
    protected function isValidPolymorphicType(string $type, string $relationName = 'configurable'): bool
    {
        $whitelist = $this->getWhitelistForRelation($relationName);
        return in_array($type, $whitelist, true);
    }

    /**
     * Secure polymorphic whereHas query with type validation
     *
     * Example:
     * ```php
     * $stats = AppointmentModificationStat::query()
     *     ->securePolymorphicWhereHas('customer', function ($query) use ($companyId) {
     *         $query->where('company_id', $companyId);
     *     })
     *     ->get();
     * ```
     *
     * @param Builder $query
     * @param string $relation
     * @param callable $callback
     * @param string $polymorphicRelation The polymorphic relation to validate
     * @return Builder
     */
    protected function securePolymorphicWhereHas(
        Builder $query,
        string $relation,
        callable $callback,
        string $polymorphicRelation = 'configurable'
    ): Builder {
        $whitelist = $this->getWhitelistForRelation($polymorphicRelation);

        // Apply type whitelist filter BEFORE relationship query
        return $query->whereHas($relation, function ($q) use ($callback, $polymorphicRelation, $whitelist) {
            // Validate polymorphic type if the relationship has one
            if (method_exists($q->getModel(), $polymorphicRelation)) {
                $q->whereIn("{$polymorphicRelation}_type", $whitelist);
            }

            // Apply user-provided constraints
            $callback($q);
        });
    }

    /**
     * Get current company ID from authenticated user
     *
     * @return int
     * @throws \RuntimeException If no company context available
     */
    protected function getCurrentCompanyId(): int
    {
        $user = auth()->user();

        if (!$user || !$user->company_id) {
            throw new \RuntimeException(
                'No company context available for secure polymorphic query. User must be authenticated with company_id.'
            );
        }

        return $user->company_id;
    }

    /**
     * Validate that a polymorphic entity belongs to the current company
     *
     * @param mixed $polymorphicEntity The entity to validate
     * @return bool
     */
    protected function validatePolymorphicOwnership($polymorphicEntity): bool
    {
        if (!$polymorphicEntity) {
            return false;
        }

        // Check if entity has company_id
        if (!property_exists($polymorphicEntity, 'company_id')) {
            return false;
        }

        $currentCompanyId = $this->getCurrentCompanyId();

        return $polymorphicEntity->company_id === $currentCompanyId;
    }

    /**
     * Filter polymorphic results to only current company's entities
     *
     * Useful for post-query validation when eager loading
     *
     * @param \Illuminate\Support\Collection $results
     * @param string $polymorphicAttribute The attribute containing the polymorphic entity
     * @return \Illuminate\Support\Collection
     */
    protected function filterPolymorphicByCompany($results, string $polymorphicAttribute = 'configurable')
    {
        $companyId = $this->getCurrentCompanyId();

        return $results->filter(function ($item) use ($polymorphicAttribute, $companyId) {
            $entity = $item->$polymorphicAttribute;

            // Validate type is in whitelist
            $type = get_class($entity);
            if (!$this->isValidPolymorphicType($type, $polymorphicAttribute)) {
                return false;
            }

            // Validate entity belongs to current company
            return $entity->company_id === $companyId;
        });
    }

    /**
     * Secure polymorphic count query
     *
     * @param string $modelClass
     * @param string $polymorphicRelation
     * @param array|null $additionalWhere Additional where conditions
     * @return int
     */
    protected function securePolymorphicCount(
        string $modelClass,
        string $polymorphicRelation = 'configurable',
        ?array $additionalWhere = null
    ): int {
        $query = $this->securePolymorphicQuery($modelClass, $polymorphicRelation);

        if ($additionalWhere) {
            foreach ($additionalWhere as $column => $value) {
                $query->where($column, $value);
            }
        }

        return $query->count();
    }
}
