<?php

namespace App\Filament\Admin\Traits;

use App\Services\Navigation\UnifiedNavigationService;

trait ConsistentNavigation
{
    /**
     * Get navigation group with German label
     */
    public static function getNavigationGroup(): ?string
    {
        $key = static::getResourceKey();
        $config = UnifiedNavigationService::RESOURCE_CONFIG[$key] ?? null;
        
        if (!$config) {
            return parent::getNavigationGroup();
        }
        
        $group = UnifiedNavigationService::GROUPS[$config['group']] ?? null;
        return $group['label'] ?? parent::getNavigationGroup();
    }
    
    /**
     * Get navigation label in German
     */
    public static function getNavigationLabel(): string
    {
        $key = static::getResourceKey();
        $config = UnifiedNavigationService::RESOURCE_CONFIG[$key] ?? null;
        
        return $config['label'] ?? parent::getNavigationLabel();
    }
    
    /**
     * Get navigation icon
     */
    public static function getNavigationIcon(): ?string
    {
        $key = static::getResourceKey();
        $config = UnifiedNavigationService::RESOURCE_CONFIG[$key] ?? null;
        
        return $config['icon'] ?? parent::getNavigationIcon();
    }
    
    /**
     * Get navigation sort order
     */
    public static function getNavigationSort(): ?int
    {
        $key = static::getResourceKey();
        $config = UnifiedNavigationService::RESOURCE_CONFIG[$key] ?? null;
        
        if (!$config) {
            return parent::getNavigationSort();
        }
        
        $group = UnifiedNavigationService::GROUPS[$config['group']] ?? null;
        $groupSort = $group['sort'] ?? 999;
        $itemSort = $config['sort'] ?? 999;
        
        return $groupSort + $itemSort;
    }
    
    /**
     * Check if navigation should be registered
     */
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        
        $key = static::getResourceKey();
        $config = UnifiedNavigationService::RESOURCE_CONFIG[$key] ?? null;
        
        if (!$config) {
            return parent::shouldRegisterNavigation();
        }
        
        // Check visibility rules
        if (isset($config['visible'])) {
            foreach ($config['visible'] as $requirement) {
                if (str_starts_with($requirement, 'role:')) {
                    $role = str_replace('role:', '', $requirement);
                    if (!$user->hasRole($role)) return false;
                } elseif (str_starts_with($requirement, 'permission:')) {
                    $permission = str_replace('permission:', '', $requirement);
                    if (!$user->can($permission)) return false;
                }
            }
        }
        
        // Check parent method if exists
        if (method_exists(parent::class, 'shouldRegisterNavigation')) {
            return parent::shouldRegisterNavigation();
        }
        
        return true;
    }
    
    /**
     * Get navigation badge
     */
    public static function getNavigationBadge(): ?string
    {
        $key = static::getResourceKey();
        $config = UnifiedNavigationService::RESOURCE_CONFIG[$key] ?? null;
        
        if (!$config || !isset($config['badge'])) {
            return parent::getNavigationBadge();
        }
        
        // Call badge method
        $method = $config['badge'];
        if (method_exists(static::class, $method)) {
            return (string) static::$method();
        }
        
        return null;
    }
    
    /**
     * Get navigation badge color
     */
    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
    
    /**
     * Get resource key from class name
     */
    protected static function getResourceKey(): string
    {
        $class = class_basename(static::class);
        $key = str_replace(['Resource', 'Page'], '', $class);
        $key = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $key));
        
        return $key;
    }
    
    /**
     * Get model label in German
     */
    public static function getModelLabel(): string
    {
        // FIX: Use the config directly to prevent recursion
        $key = static::getResourceKey();
        $config = UnifiedNavigationService::RESOURCE_CONFIG[$key] ?? null;
        
        return $config['label'] ?? parent::getModelLabel();
    }
    
    /**
     * Get plural model label in German
     */
    public static function getPluralModelLabel(): string
    {
        // FIX: Use the config directly to prevent recursion
        $key = static::getResourceKey();
        $config = UnifiedNavigationService::RESOURCE_CONFIG[$key] ?? null;
        
        return $config['label'] ?? parent::getPluralModelLabel();
    }
}