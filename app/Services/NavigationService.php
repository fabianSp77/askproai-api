<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;

class NavigationService
{
    /**
     * Navigation group definitions with German labels
     */
    const GROUPS = [
        'dashboard' => [
            'label' => 'Dashboard',
            'sort' => 0,
            'icon' => 'heroicon-o-home',
        ],
        'daily_operations' => [
            'label' => 'Täglicher Betrieb',
            'sort' => 100,
            'icon' => 'heroicon-o-calendar-days',
        ],
        'staff_services' => [
            'label' => 'Personal & Services',
            'sort' => 200,
            'icon' => 'heroicon-o-user-group',
        ],
        'company_structure' => [
            'label' => 'Unternehmensstruktur',
            'sort' => 300,
            'icon' => 'heroicon-o-building-office',
        ],
        'setup_config' => [
            'label' => 'Einrichtung & Konfiguration',
            'sort' => 400,
            'icon' => 'heroicon-o-cog-6-tooth',
        ],
        'billing' => [
            'label' => 'Abrechnung',
            'sort' => 500,
            'icon' => 'heroicon-o-banknotes',
        ],
        'system_monitoring' => [
            'label' => 'System & Überwachung',
            'sort' => 600,
            'icon' => 'heroicon-o-cpu-chip',
        ],
        'administration' => [
            'label' => 'Verwaltung',
            'sort' => 700,
            'icon' => 'heroicon-o-shield-check',
        ],
    ];

    /**
     * Resource to group mapping
     */
    const RESOURCE_GROUPS = [
        // Dashboard
        'dashboard' => 'dashboard',
        'executive-dashboard' => 'dashboard',
        'operational-dashboard' => 'dashboard',
        'operations-dashboard' => 'dashboard',
        'roi-dashboard' => 'dashboard',
        
        // Täglicher Betrieb
        'appointment' => 'daily_operations',
        'call' => 'daily_operations',
        'customer' => 'daily_operations',
        'live-appointment-board' => 'daily_operations',
        'live-call-monitor' => 'daily_operations',
        
        // Personal & Services
        'staff' => 'staff_services',
        'service' => 'staff_services',
        'calcom-event-type' => 'staff_services',
        'event-type-management' => 'staff_services',
        'staff-event-assignment' => 'staff_services',
        'staff-event-assignment-modern' => 'staff_services',
        'working-hours' => 'staff_services',
        
        // Unternehmensstruktur
        'company' => 'company_structure',
        'branch' => 'company_structure',
        'phone-number' => 'company_structure',
        'business-hours-template' => 'company_structure',
        
        // Einrichtung & Konfiguration
        'quick-setup-wizard' => 'setup_config',
        'onboarding-wizard' => 'setup_config',
        'event-type-import-wizard' => 'setup_config',
        'calcom-sync-status' => 'setup_config',
        'calcom-api-test' => 'setup_config',
        'calcom-live-test' => 'setup_config',
        'calcom-complete-test' => 'setup_config',
        'webhook-monitor' => 'setup_config',
        
        // Abrechnung
        'invoice' => 'billing',
        'company-pricing' => 'billing',
        'pricing-calculator' => 'billing',
        'tax-configuration' => 'billing',
        'customer-portal-management' => 'billing',
        
        // System & Überwachung
        'system-health-simple' => 'system_monitoring',
        'system-cockpit-simple' => 'system_monitoring',
        'ultimate-system-cockpit' => 'system_monitoring',
        'ultimate-system-cockpit-optimized' => 'system_monitoring',
        'api-health-monitor' => 'system_monitoring',
        'system-monitoring' => 'system_monitoring',
        'system-improvements' => 'system_monitoring',
        'event-analytics-dashboard' => 'system_monitoring',
        
        // Verwaltung
        'user' => 'administration',
        'tenant' => 'administration',
        'gdpr-request' => 'administration',
        'knowledge-base-manager' => 'administration',
        'mcp-dashboard' => 'administration',
    ];

    /**
     * Permission requirements per group
     */
    const GROUP_PERMISSIONS = [
        'dashboard' => null, // Available to all authenticated users
        'daily_operations' => null, // Available to all authenticated users
        'staff_services' => 'manage_staff',
        'company_structure' => 'manage_company',
        'setup_config' => 'manage_settings',
        'billing' => 'manage_billing',
        'system_monitoring' => 'view_system_health',
        'administration' => 'super_admin',
    ];

    /**
     * Get navigation group for a resource
     *
     * @param string $resource Resource identifier
     * @return string|null
     */
    public static function getResourceGroup(string $resource): ?string
    {
        $key = strtolower(str_replace(['Resource', 'Page'], '', class_basename($resource)));
        $key = str_replace('_', '-', $key);
        
        return self::RESOURCE_GROUPS[$key] ?? null;
    }

    /**
     * Get navigation group label
     *
     * @param string $group Group identifier
     * @return string
     */
    public static function getGroupLabel(string $group): string
    {
        return self::GROUPS[$group]['label'] ?? ucfirst($group);
    }

    /**
     * Get navigation group sort order
     *
     * @param string $group Group identifier
     * @return int
     */
    public static function getGroupSort(string $group): int
    {
        return self::GROUPS[$group]['sort'] ?? 999;
    }

    /**
     * Get navigation group icon
     *
     * @param string $group Group identifier
     * @return string
     */
    public static function getGroupIcon(string $group): string
    {
        return self::GROUPS[$group]['icon'] ?? 'heroicon-o-folder';
    }

    /**
     * Get sort order for a resource within its group
     *
     * @param string $resource Resource identifier
     * @param int $default Default sort order
     * @return int
     */
    public static function getResourceSort(string $resource, int $default = 10): int
    {
        // Define specific sort orders for resources within groups
        $resourceSorts = [
            // Dashboard group
            'dashboard' => 1,
            'executive-dashboard' => 2,
            'operational-dashboard' => 3,
            'operations-dashboard' => 4,
            'roi-dashboard' => 5,
            
            // Daily operations
            'appointment' => 1,
            'call' => 2,
            'customer' => 3,
            'live-appointment-board' => 10,
            'live-call-monitor' => 11,
            
            // Staff & Services
            'staff' => 1,
            'service' => 2,
            'calcom-event-type' => 3,
            'event-type-management' => 4,
            'working-hours' => 5,
            'staff-event-assignment' => 10,
            'staff-event-assignment-modern' => 11,
            
            // Company structure
            'company' => 1,
            'branch' => 2,
            'phone-number' => 3,
            'business-hours-template' => 10,
            
            // Setup & Config
            'quick-setup-wizard' => 1,
            'onboarding-wizard' => 2,
            'event-type-import-wizard' => 3,
            'calcom-sync-status' => 10,
            'webhook-monitor' => 20,
            
            // Billing
            'invoice' => 1,
            'company-pricing' => 2,
            'pricing-calculator' => 3,
            'tax-configuration' => 4,
            'customer-portal-management' => 10,
            
            // System monitoring
            'system-health-simple' => 1,
            'system-cockpit-simple' => 2,
            'api-health-monitor' => 3,
            'system-monitoring' => 10,
            'system-improvements' => 11,
            'event-analytics-dashboard' => 20,
            
            // Administration
            'user' => 1,
            'tenant' => 2,
            'gdpr-request' => 3,
            'knowledge-base-manager' => 10,
            'mcp-dashboard' => 20,
        ];
        
        $key = strtolower(str_replace(['Resource', 'Page'], '', class_basename($resource)));
        $key = str_replace('_', '-', $key);
        
        return $resourceSorts[$key] ?? $default;
    }

    /**
     * Check if user has permission to view a navigation group
     *
     * @param string $group Group identifier
     * @param \App\Models\User|null $user User to check (defaults to current user)
     * @return bool
     */
    public static function canViewGroup(string $group, $user = null): bool
    {
        $user = $user ?? Auth::user();
        
        if (!$user) {
            return false;
        }
        
        $permission = self::GROUP_PERMISSIONS[$group] ?? null;
        
        // If no permission required, allow access
        if ($permission === null) {
            return true;
        }
        
        // Check if user has the required permission
        return $user->can($permission);
    }

    /**
     * Get all visible groups for a user
     *
     * @param \App\Models\User|null $user User to check (defaults to current user)
     * @return array
     */
    public static function getVisibleGroups($user = null): array
    {
        $visibleGroups = [];
        
        foreach (self::GROUPS as $key => $group) {
            if (self::canViewGroup($key, $user)) {
                $visibleGroups[$key] = $group;
            }
        }
        
        return $visibleGroups;
    }

    /**
     * Get navigation structure for use in Filament resources
     *
     * @param string $resourceClass The resource class name
     * @return array
     */
    public static function getNavigationForResource(string $resourceClass): array
    {
        $group = self::getResourceGroup($resourceClass);
        
        if (!$group) {
            return [
                'group' => null,
                'sort' => 999,
                'icon' => 'heroicon-o-rectangle-stack',
            ];
        }
        
        return [
            'group' => self::getGroupLabel($group),
            'sort' => self::getGroupSort($group) + self::getResourceSort($resourceClass),
            'icon' => self::getGroupIcon($group),
        ];
    }

    /**
     * Register navigation groups with Filament
     * Call this in a service provider
     */
    public static function registerWithFilament(): void
    {
        // This method can be called in AppServiceProvider or FilamentServiceProvider
        // to set up navigation group ordering
        $groupOrder = [];
        
        foreach (self::GROUPS as $key => $group) {
            $groupOrder[$group['label']] = $group['sort'];
        }
        
        // Store in config for use by Filament
        config(['filament.navigation.groups' => $groupOrder]);
    }

    /**
     * Get breadcrumb structure for a resource
     *
     * @param string $resourceClass The resource class name
     * @param array $additionalCrumbs Additional breadcrumbs to append
     * @return array
     */
    public static function getBreadcrumbs(string $resourceClass, array $additionalCrumbs = []): array
    {
        $group = self::getResourceGroup($resourceClass);
        $breadcrumbs = [];
        
        if ($group) {
            $breadcrumbs[] = [
                'label' => self::getGroupLabel($group),
                'icon' => self::getGroupIcon($group),
            ];
        }
        
        return array_merge($breadcrumbs, $additionalCrumbs);
    }

    /**
     * Get a consistent label for common actions
     *
     * @param string $action The action name
     * @return string
     */
    public static function getActionLabel(string $action): string
    {
        $labels = [
            'create' => 'Erstellen',
            'edit' => 'Bearbeiten',
            'delete' => 'Löschen',
            'view' => 'Anzeigen',
            'list' => 'Liste',
            'save' => 'Speichern',
            'cancel' => 'Abbrechen',
            'back' => 'Zurück',
            'next' => 'Weiter',
            'previous' => 'Zurück',
            'finish' => 'Fertigstellen',
            'search' => 'Suchen',
            'filter' => 'Filtern',
            'export' => 'Exportieren',
            'import' => 'Importieren',
            'refresh' => 'Aktualisieren',
            'duplicate' => 'Duplizieren',
            'archive' => 'Archivieren',
            'restore' => 'Wiederherstellen',
        ];
        
        return $labels[$action] ?? ucfirst($action);
    }
}