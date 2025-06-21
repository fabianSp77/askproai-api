<?php

namespace App\Services\Navigation;

use Illuminate\Support\Facades\Auth;

class UnifiedNavigationService
{
    /**
     * Navigation group definitions with consistent German labels
     */
    const GROUPS = [
        'dashboard' => [
            'label' => 'Übersicht',
            'sort' => 0,
            'icon' => 'heroicon-o-home',
            'description' => 'Dashboards und Übersichten',
        ],
        'daily_operations' => [
            'label' => 'Täglicher Betrieb',
            'sort' => 100,
            'icon' => 'heroicon-o-calendar-days',
            'description' => 'Termine, Anrufe und Kunden',
        ],
        'staff_services' => [
            'label' => 'Personal & Leistungen',
            'sort' => 200,
            'icon' => 'heroicon-o-user-group',
            'description' => 'Mitarbeiter und Dienstleistungen',
        ],
        'company_structure' => [
            'label' => 'Struktur',
            'sort' => 300,
            'icon' => 'heroicon-o-building-office',
            'description' => 'Unternehmen und Filialen',
        ],
        'billing' => [
            'label' => 'Abrechnung',
            'sort' => 400,
            'icon' => 'heroicon-o-banknotes',
            'description' => 'Rechnungen und Preise',
        ],
        'settings' => [
            'label' => 'Einstellungen',
            'sort' => 500,
            'icon' => 'heroicon-o-cog-6-tooth',
            'description' => 'Konfiguration und Integrationen',
        ],
        'system' => [
            'label' => 'System',
            'sort' => 600,
            'icon' => 'heroicon-o-cpu-chip',
            'description' => 'Überwachung und Verwaltung',
            'permission' => 'view_system_health',
        ],
    ];

    /**
     * Simplified resource mapping with consistent German labels
     */
    const RESOURCE_CONFIG = [
        // Dashboard
        'executive-dashboard' => [
            'group' => 'dashboard',
            'label' => 'Geschäftsführung',
            'sort' => 1,
            'icon' => 'heroicon-o-chart-pie',
            'visible' => ['role:admin', 'role:owner'],
        ],
        'operations-dashboard' => [
            'group' => 'dashboard', 
            'label' => 'Betrieb',
            'sort' => 2,
            'icon' => 'heroicon-o-presentation-chart-line',
        ],
        
        // Daily Operations
        'appointment' => [
            'group' => 'daily_operations',
            'label' => 'Termine',
            'sort' => 1,
            'icon' => 'heroicon-o-calendar',
            'badge' => 'getAppointmentCount',
        ],
        'call' => [
            'group' => 'daily_operations',
            'label' => 'Anrufe',
            'sort' => 2,
            'icon' => 'heroicon-o-phone-arrow-down-left',
            'badge' => 'getCallCount',
        ],
        'customer' => [
            'group' => 'daily_operations',
            'label' => 'Kunden',
            'sort' => 3,
            'icon' => 'heroicon-o-users',
        ],
        
        // Staff & Services
        'staff' => [
            'group' => 'staff_services',
            'label' => 'Mitarbeiter',
            'sort' => 1,
            'icon' => 'heroicon-o-user-group',
        ],
        'service' => [
            'group' => 'staff_services',
            'label' => 'Leistungen',
            'sort' => 2,
            'icon' => 'heroicon-o-briefcase',
        ],
        'working-hours' => [
            'group' => 'staff_services',
            'label' => 'Arbeitszeiten',
            'sort' => 3,
            'icon' => 'heroicon-o-clock',
        ],
        
        // Company Structure
        'company' => [
            'group' => 'company_structure',
            'label' => 'Unternehmen',
            'sort' => 1,
            'icon' => 'heroicon-o-building-office',
            'visible' => ['permission:manage_company'],
        ],
        'branch' => [
            'group' => 'company_structure',
            'label' => 'Filialen',
            'sort' => 2,
            'icon' => 'heroicon-o-building-storefront',
        ],
        'phone-number' => [
            'group' => 'company_structure',
            'label' => 'Telefonnummern',
            'sort' => 3,
            'icon' => 'heroicon-o-phone',
        ],
        
        // Billing
        'invoice' => [
            'group' => 'billing',
            'label' => 'Rechnungen',
            'sort' => 1,
            'icon' => 'heroicon-o-document-text',
        ],
        'pricing-calculator' => [
            'group' => 'billing',
            'label' => 'Preiskalkulator',
            'sort' => 2,
            'icon' => 'heroicon-o-calculator',
        ],
        
        // Settings
        'quick-setup-wizard' => [
            'group' => 'settings',
            'label' => 'Schnelleinrichtung',
            'sort' => 1,
            'icon' => 'heroicon-o-sparkles',
            'highlight' => true,
        ],
        'integration' => [
            'group' => 'settings',
            'label' => 'Integrationen',
            'sort' => 2,
            'icon' => 'heroicon-o-puzzle-piece',
        ],
        
        // System (Admin only)
        'system-health' => [
            'group' => 'system',
            'label' => 'Systemstatus',
            'sort' => 1,
            'icon' => 'heroicon-o-heart',
            'visible' => ['permission:view_system_health'],
        ],
        'user' => [
            'group' => 'system',
            'label' => 'Benutzer',
            'sort' => 2,
            'icon' => 'heroicon-o-user',
            'visible' => ['permission:manage_users'],
        ],
        'tenant' => [
            'group' => 'system',
            'label' => 'Mandanten',
            'sort' => 3,
            'icon' => 'heroicon-o-building-library',
            'visible' => ['role:super_admin'],
        ],
    ];

    /**
     * Quick actions for toolbar
     */
    const QUICK_ACTIONS = [
        'new-appointment' => [
            'label' => 'Neuer Termin',
            'icon' => 'heroicon-o-plus-circle',
            'route' => 'filament.admin.resources.appointments.create',
            'color' => 'success',
        ],
        'new-customer' => [
            'label' => 'Neuer Kunde',
            'icon' => 'heroicon-o-user-plus',
            'route' => 'filament.admin.resources.customers.create',
            'color' => 'primary',
        ],
        'today-appointments' => [
            'label' => 'Heutige Termine',
            'icon' => 'heroicon-o-calendar',
            'route' => 'filament.admin.resources.appointments.index',
            'params' => ['tableFilters[date][date]' => 'today'],
            'badge' => 'getTodayAppointmentCount',
        ],
    ];

    /**
     * Get navigation structure with context awareness
     */
    public static function getNavigation($user = null): array
    {
        $user = $user ?? Auth::user();
        $navigation = [];
        
        // Add company/branch context
        $context = static::getContext($user);
        
        foreach (self::GROUPS as $key => $group) {
            if (static::canViewGroup($key, $user)) {
                $items = static::getGroupItems($key, $user);
                if (!empty($items)) {
                    $navigation[] = [
                        'key' => $key,
                        'label' => $group['label'],
                        'icon' => $group['icon'],
                        'items' => $items,
                        'sort' => $group['sort'],
                    ];
                }
            }
        }
        
        return [
            'context' => $context,
            'groups' => $navigation,
            'quickActions' => static::getQuickActions($user),
        ];
    }

    /**
     * Get user context (company/branch)
     */
    protected static function getContext($user): array
    {
        if (!$user) return [];
        
        return [
            'company' => $user->company ? [
                'id' => $user->company->id,
                'name' => $user->company->name,
            ] : null,
            'branch' => $user->branch ? [
                'id' => $user->branch->id,
                'name' => $user->branch->name,
            ] : null,
            'role' => $user->getRoleNames()->first(),
        ];
    }

    /**
     * Get items for a navigation group
     */
    protected static function getGroupItems(string $group, $user): array
    {
        $items = [];
        
        foreach (self::RESOURCE_CONFIG as $key => $config) {
            if ($config['group'] === $group && static::canViewResource($key, $config, $user)) {
                $items[] = [
                    'key' => $key,
                    'label' => $config['label'],
                    'icon' => $config['icon'] ?? null,
                    'sort' => $config['sort'] ?? 999,
                    'badge' => isset($config['badge']) ? static::getBadgeValue($config['badge'], $user) : null,
                    'highlight' => $config['highlight'] ?? false,
                ];
            }
        }
        
        return collect($items)->sortBy('sort')->values()->toArray();
    }

    /**
     * Check if user can view a resource
     */
    protected static function canViewResource(string $key, array $config, $user): bool
    {
        if (!isset($config['visible'])) {
            return true;
        }
        
        foreach ($config['visible'] as $requirement) {
            if (str_starts_with($requirement, 'role:')) {
                $role = str_replace('role:', '', $requirement);
                if ($user->hasRole($role)) return true;
            } elseif (str_starts_with($requirement, 'permission:')) {
                $permission = str_replace('permission:', '', $requirement);
                if ($user->can($permission)) return true;
            }
        }
        
        return false;
    }

    /**
     * Check if user can view a group
     */
    protected static function canViewGroup(string $group, $user): bool
    {
        $groupConfig = self::GROUPS[$group] ?? null;
        if (!$groupConfig) return false;
        
        if (isset($groupConfig['permission'])) {
            return $user->can($groupConfig['permission']);
        }
        
        return true;
    }

    /**
     * Get quick actions for user
     */
    protected static function getQuickActions($user): array
    {
        $actions = [];
        
        foreach (self::QUICK_ACTIONS as $key => $action) {
            $actions[] = array_merge($action, [
                'key' => $key,
                'badge' => isset($action['badge']) ? static::getBadgeValue($action['badge'], $user) : null,
            ]);
        }
        
        return $actions;
    }

    /**
     * Get badge value dynamically
     */
    protected static function getBadgeValue(string $method, $user): ?string
    {
        // This would call methods on appropriate services
        // For now, return sample data
        return match($method) {
            'getAppointmentCount' => '5',
            'getCallCount' => '12',
            'getTodayAppointmentCount' => '3',
            default => null,
        };
    }

    /**
     * Get breadcrumbs for current page
     */
    public static function getBreadcrumbs(string $resourceKey, array $additional = []): array
    {
        $config = self::RESOURCE_CONFIG[$resourceKey] ?? null;
        if (!$config) return $additional;
        
        $group = self::GROUPS[$config['group']] ?? null;
        
        $breadcrumbs = [
            [
                'label' => 'Dashboard',
                'url' => '/admin',
            ],
        ];
        
        if ($group) {
            $breadcrumbs[] = [
                'label' => $group['label'],
                'icon' => $group['icon'],
            ];
        }
        
        $breadcrumbs[] = [
            'label' => $config['label'],
            'icon' => $config['icon'] ?? null,
        ];
        
        return array_merge($breadcrumbs, $additional);
    }
}