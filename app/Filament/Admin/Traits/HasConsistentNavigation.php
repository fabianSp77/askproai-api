<?php

namespace App\Filament\Admin\Traits;

use App\Services\NavigationService;

trait HasConsistentNavigation
{
    /**
     * Get the navigation group for this resource/page
     */
    public static function getNavigationGroup(): ?string
    {
        $navigation = NavigationService::getNavigationForResource(static::class);
        return $navigation['group'] ?? parent::getNavigationGroup();
    }
    
    /**
     * Get the navigation sort order
     */
    public static function getNavigationSort(): ?int
    {
        $navigation = NavigationService::getNavigationForResource(static::class);
        return $navigation['sort'] ?? parent::getNavigationSort();
    }
    
    /**
     * Get the navigation icon
     */
    public static function getNavigationIcon(): ?string
    {
        $navigation = NavigationService::getNavigationForResource(static::class);
        return $navigation['icon'] ?? parent::getNavigationIcon();
    }
    
    /**
     * Check if this resource should be visible in navigation
     */
    public static function shouldRegisterNavigation(): bool
    {
        $resourceClass = str_replace('\\', '-', static::class);
        $resourceKey = strtolower(str_replace(['App-Filament-Admin-Resources-', 'App-Filament-Admin-Pages-', 'Resource', 'Page'], '', $resourceClass));
        
        // Check if user has permission
        $user = auth()->user();
        if (!$user) {
            return false;
        }
        
        $group = NavigationService::getResourceGroup(static::class);
        
        if ($group && !NavigationService::canViewGroup($group, $user)) {
            return false;
        }
        
        // Call parent method if it exists
        if (method_exists(parent::class, 'shouldRegisterNavigation')) {
            return parent::shouldRegisterNavigation();
        }
        
        return true;
    }
    
    /**
     * Get breadcrumbs for this resource
     */
    public function getBreadcrumbs(): array
    {
        return NavigationService::getBreadcrumbs(static::class);
    }
    
    /**
     * Get consistent action labels in German
     */
    protected static function getActionLabel(string $action): string
    {
        return NavigationService::getActionLabel($action);
    }
    
    /**
     * Get the navigation badge (for counts, status, etc.)
     */
    public static function getNavigationBadge(): ?string
    {
        // Override in resource to add badges
        return null;
    }
    
    /**
     * Get the navigation badge color
     */
    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
    
    /**
     * Helper to get company context for multi-company aware resources
     */
    protected static function getCompanyContext(): ?string
    {
        $user = auth()->user();
        if ($user && $user->company) {
            return $user->company->name;
        }
        return null;
    }
    
    /**
     * Helper to check if resource is in multi-company mode
     */
    protected static function isMultiCompanyMode(): bool
    {
        return \App\Models\Company::count() > 1;
    }
}