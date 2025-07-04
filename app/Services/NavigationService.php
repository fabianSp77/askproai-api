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
        'company_structure' => [
            'label' => 'Unternehmensstruktur',
            'sort' => 200,
            'icon' => 'heroicon-o-building-office',
        ],
        'integrations' => [
            'label' => 'Integrationen',
            'sort' => 250,
            'icon' => 'heroicon-o-puzzle-piece',
        ],
        'setup_config' => [
            'label' => 'Einrichtung & Konfiguration',
            'sort' => 300,
            'icon' => 'heroicon-o-cog-6-tooth',
        ],
        'billing' => [
            'label' => 'Abrechnung',
            'sort' => 400,
            'icon' => 'heroicon-o-banknotes',
        ],
        'reports' => [
            'label' => 'Berichte & Analysen',
            'sort' => 500,
            'icon' => 'heroicon-o-chart-bar',
        ],
        'system_admin' => [
            'label' => 'System & Verwaltung',
            'sort' => 600,
            'icon' => 'heroicon-o-server-stack',
        ],
        'compliance' => [
            'label' => 'Compliance & Sicherheit',
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
        'optimized-operational-dashboard' => 'dashboard',
        
        // Täglicher Betrieb (Daily Operations)
        'appointment' => 'daily_operations',
        'call' => 'daily_operations',
        'customer' => 'daily_operations',
        'live-calls-widget' => 'daily_operations',
        'compact-live-calls-widget' => 'daily_operations',
        
        // Unternehmensstruktur (Company Structure)
        'company' => 'company_structure',
        'branch' => 'company_structure',
        'staff' => 'company_structure',
        'master-service' => 'company_structure',
        'phone-number' => 'company_structure',
        'calcom-event-type' => 'company_structure',
        'working-hour' => 'company_structure',
        'quick-setup-wizard-v2' => 'company_structure',
        
        // Integrationen (Integrations)
        'retell-ultimate-control-center' => 'integrations',
        
        // Einrichtung & Konfiguration (Setup & Configuration)
        'event-type-import-wizard' => 'setup_config',
        'event-type-setup-wizard' => 'setup_config',
        'notification-settings' => 'setup_config',
        'integration-hub' => 'setup_config',
        'calcom-booking-test' => 'setup_config',
        
        // Abrechnung (Billing)
        'invoice' => 'billing',
        'billing-period' => 'billing',
        'subscription' => 'billing',
        'pricing-plan' => 'billing',
        'service-addon' => 'billing',
        'billing-alerts-management' => 'billing',
        'customer-billing-dashboard' => 'billing',
        
        // Berichte & Analysen (Reports & Analytics)
        'reports-and-analytics' => 'reports',
        'customer-insights-widget' => 'reports',
        'agent-performance-widget' => 'reports',
        'subscription-status-widget' => 'reports',
        
        // System & Verwaltung (System & Administration)
        'user' => 'system_admin',
        'circuit-breaker-monitor' => 'system_admin',
        'documentation-hub' => 'system_admin',
        'documentation-health-widget' => 'system_admin',
        'quick-docs' => 'system_admin',
        'quick-docs-enhanced' => 'system_admin',
        'system-monitoring-dashboard' => 'system_admin',
        'mcp-dashboard' => 'system_admin',
        
        // Compliance & Sicherheit (Compliance & Security)
        'gdpr-management' => 'compliance',
        'gdpr-request' => 'compliance',
        'two-factor-authentication' => 'compliance',
    ];

    /**
     * Permission requirements per group
     */
    const GROUP_PERMISSIONS = [
        'dashboard' => null, // Available to all authenticated users
        'daily_operations' => null, // Available to all authenticated users
        'company_structure' => 'manage_company',
        'integrations' => 'manage_settings',
        'setup_config' => 'manage_settings',
        'billing' => 'manage_billing',
        'reports' => 'view_reports',
        'system_admin' => 'view_system_health',
        'compliance' => 'manage_compliance',
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
            'optimized-operational-dashboard' => 4,
            
            // Daily operations
            'appointment' => 1,
            'call' => 2,
            'customer' => 3,
            'live-calls-widget' => 10,
            'compact-live-calls-widget' => 11,
            
            // Company structure
            'company' => 1,
            'branch' => 2,
            'staff' => 3,
            'master-service' => 4,
            'phone-number' => 5,
            'calcom-event-type' => 6,
            'working-hour' => 7,
            'quick-setup-wizard-v2' => 8,
            
            // Integrations
            'retell-ultimate-control-center' => 1,
            
            // Setup & Config
            'event-type-import-wizard' => 3,
            'event-type-setup-wizard' => 4,
            'notification-settings' => 5,
            'integration-hub' => 10,
            'calcom-booking-test' => 20,
            
            // Billing
            'invoice' => 1,
            'billing-period' => 2,
            'subscription' => 3,
            'pricing-plan' => 4,
            'service-addon' => 5,
            'billing-alerts-management' => 10,
            'customer-billing-dashboard' => 11,
            
            // Reports
            'reports-and-analytics' => 1,
            'customer-insights-widget' => 10,
            'agent-performance-widget' => 11,
            'subscription-status-widget' => 12,
            
            // System administration
            'user' => 1,
            'system-monitoring-dashboard' => 2,
            'mcp-dashboard' => 3,
            'circuit-breaker-monitor' => 10,
            'documentation-hub' => 20,
            'documentation-health-widget' => 21,
            'quick-docs' => 22,
            'quick-docs-enhanced' => 23,
            
            // Compliance
            'gdpr-management' => 1,
            'gdpr-request' => 2,
            'two-factor-authentication' => 10,
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