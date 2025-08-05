<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Navigation\HasConfiguredNavigation;
use Filament\Resources\Resource;

/**
 * Base Admin Resource
 * 
 * All admin resources should extend this class to get:
 * - Centralized navigation configuration
 * - Consistent permission checking
 * - Tenant-aware queries
 * - Performance optimizations
 */
abstract class BaseAdminResource extends Resource
{
    use HasConfiguredNavigation;

    /**
     * The model the resource corresponds to
     */
    protected static ?string $model = null;

    /**
     * Determine if the user can view any models
     * 
     * @return bool
     */
    public static function canViewAny(): bool
    {
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }

        // Super admin can view all
        if ($user->hasRole('super_admin') || $user->hasRole('Super Admin')) {
            return true;
        }

        // Check navigation configuration permissions
        $config = \App\Filament\Admin\Navigation\NavigationConfig::getResourceConfig(static::class);
        
        if ($config && isset($config['permissions'])) {
            foreach ($config['permissions'] as $permission) {
                if ($user->can($permission)) {
                    return true;
                }
            }
            return false;
        }

        // Default permission check
        $resourceName = static::getPluralModelLabel();
        return $user->can("view_any_{$resourceName}");
    }

    /**
     * Apply tenant scope to queries
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected static function applyTenantScope($query)
    {
        $user = auth()->user();
        
        if (!$user) {
            return $query->whereRaw('1 = 0'); // No results
        }

        // Super admin sees all
        if ($user->hasRole('super_admin') || $user->hasRole('Super Admin')) {
            return $query;
        }

        // Apply company filtering
        if ($user->company_id && $query->getModel()->getTable() !== 'companies') {
            $query->where('company_id', $user->company_id);
        }

        return $query;
    }

    /**
     * Get the model label
     * 
     * @return string
     */
    public static function getModelLabel(): string
    {
        $key = static::getSlug();
        $translationKey = "admin.models.{$key}.singular";
        
        if (__($translationKey) !== $translationKey) {
            return __($translationKey);
        }
        
        return parent::getModelLabel();
    }

    /**
     * Get the plural model label
     * 
     * @return string
     */
    public static function getPluralModelLabel(): string
    {
        $key = static::getSlug();
        $translationKey = "admin.models.{$key}.plural";
        
        if (__($translationKey) !== $translationKey) {
            return __($translationKey);
        }
        
        return parent::getPluralModelLabel();
    }

    /**
     * Get global search results
     * 
     * @param string $search
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getGlobalSearchResults(string $search): \Illuminate\Database\Eloquent\Collection
    {
        $query = static::getGlobalSearchEloquentQuery();
        
        // Apply tenant scope
        $query = static::applyTenantScope($query);
        
        // Limit results for performance
        return $query->limit(20)->get();
    }

    /**
     * Get the breadcrumb
     * 
     * @return string
     */
    public static function getBreadcrumb(): string
    {
        return static::getPluralModelLabel();
    }

    /**
     * Default table configuration for consistency
     * 
     * @return array
     */
    protected static function getDefaultTableConfiguration(): array
    {
        return [
            'defaultSort' => 'created_at',
            'defaultSortDirection' => 'desc',
            'recordsPerPage' => 25,
            'paginated' => true,
            'striped' => true,
            'searchable' => true,
            'searchDebounce' => '500ms',
        ];
    }

    /**
     * Default form configuration
     * 
     * @return array
     */
    protected static function getDefaultFormConfiguration(): array
    {
        return [
            'columns' => 2,
        ];
    }

    /**
     * Register service provider bindings
     * 
     * @return void
     */
    public static function registerBindings(): void
    {
        // Register navigation badge calculator as singleton
        app()->singleton(\App\Filament\Admin\Navigation\NavigationBadgeCalculator::class);
    }
}