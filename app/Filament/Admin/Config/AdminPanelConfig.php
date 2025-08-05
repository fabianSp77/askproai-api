<?php

namespace App\Filament\Admin\Config;

/**
 * Admin Panel Configuration
 * 
 * Centralized configuration for the Filament admin panel.
 * This class provides a single source of truth for all admin panel settings.
 */
class AdminPanelConfig
{
    /**
     * Navigation groups in display order
     */
    public const NAVIGATION_GROUPS = [
        'daily_operations' => 'Täglicher Betrieb',
        'customer_management' => 'Kundenverwaltung',
        'company_structure' => 'Unternehmensstruktur',
        'integrations' => 'Integrationen',
        'finance_billing' => 'Finanzen & Abrechnung',
        'settings' => 'Einstellungen',
        'system_monitoring' => 'System & Monitoring',
        'development' => 'Entwicklung',
    ];

    /**
     * Resource loading tiers for progressive enhancement
     */
    public const RESOURCE_TIERS = [
        'essential' => [
            \App\Filament\Admin\Resources\UserResource::class,
            \App\Filament\Admin\Resources\CompanyResource::class,
            \App\Filament\Admin\Resources\CallResource::class,
            \App\Filament\Admin\Resources\AppointmentResource::class,
            \App\Filament\Admin\Resources\BranchResource::class,
            \App\Filament\Admin\Resources\CustomerResource::class,
            \App\Filament\Admin\Resources\ServiceResource::class,
            \App\Filament\Admin\Resources\StaffResource::class,
            \App\Filament\Admin\Resources\PrepaidBalanceResource::class,
            \App\Filament\Admin\Resources\InvoiceResource::class,
            \App\Filament\Admin\Resources\BillingPeriodResource::class,
        ],
        'standard' => [
            // Moved to essential
        ],
        'extended' => [
            \App\Filament\Admin\Resources\RetellAgentResource::class,
            \App\Filament\Admin\Resources\UnifiedEventTypeResource::class,
            \App\Filament\Admin\Resources\PhoneNumberResource::class,
            \App\Filament\Admin\Resources\IntegrationResource::class,
            \App\Filament\Admin\Resources\CalcomEventTypeResource::class,
            \App\Filament\Admin\Resources\PromptTemplateResource::class,
            \App\Filament\Admin\Resources\MasterServiceResource::class,
            \App\Filament\Admin\Resources\CompanyPricingResource::class,
            \App\Filament\Admin\Resources\PricingPlanResource::class,
            \App\Filament\Admin\Resources\SubscriptionResource::class,
            \App\Filament\Admin\Resources\PortalUserResource::class,
            \App\Filament\Admin\Resources\WorkingHoursResource::class,
        ],
        'admin' => [
            // SystemLogResource, AuditResource, RoleResource, PermissionResource don't exist yet
        ],
    ];

    /**
     * Widget configuration by category
     */
    public const WIDGET_CATEGORIES = [
        'core' => [
            // \App\Filament\Admin\Widgets\InsightsActionsWidget::class, // Temporarily disabled - widget missing
            // \App\Filament\Admin\Widgets\CompactOperationsWidget::class, // Temporarily disabled - widget missing
        ],
        'stats' => [
            // \App\Filament\Admin\Widgets\StatsOverviewWidget::class, // Temporarily disabled - widget missing
            // \App\Filament\Admin\Widgets\KpiOverviewWidget::class, // Temporarily disabled - widget missing
            // \App\Filament\Admin\Widgets\DailyOverviewWidget::class, // Temporarily disabled - widget missing
            \App\Filament\Admin\Widgets\FinancialIntelligenceWidget::class,
            \App\Filament\Admin\Widgets\BranchPerformanceMatrixWidget::class,
        ],
        'monitoring' => [
            // SystemHealthMonitor, LiveCallMonitor, ApiHealthOverview widgets exist with different names
            // \App\Filament\Admin\Widgets\SystemHealthMonitor::class, // Temporarily disabled
            // \App\Filament\Admin\Widgets\LiveCallMonitor::class, // Temporarily disabled
            // \App\Filament\Admin\Widgets\ApiHealthOverview::class, // Temporarily disabled
        ],
        'activity' => [
            // \App\Filament\Admin\Widgets\RecentActivityWidget::class, // Temporarily disabled
            // \App\Filament\Admin\Widgets\LiveActivityFeedWidget::class, // Temporarily disabled
            // \App\Filament\Admin\Widgets\ActivityLogWidget::class, // Temporarily disabled
        ],
    ];

    /**
     * Page configuration
     */
    public const PAGES = [
        'essential' => [
            \App\Filament\Admin\Pages\Dashboard::class,
            \App\Filament\Admin\Pages\QuickSetupWizard::class,
            \App\Filament\Admin\Pages\QuickSetupWizardV2::class,
        ],
        'extended' => [
            \App\Filament\Admin\Pages\DataSync::class,
            \App\Filament\Admin\Pages\WebhookAnalysis::class,
            \App\Filament\Admin\Pages\WorkingCalls::class,
            \App\Filament\Admin\Pages\SimpleSyncManager::class,
            \App\Filament\Admin\Pages\SecurityAuditDashboard::class,
            // \App\Filament\Admin\Pages\SystemMonitoringDashboard::class, // Missing HasTooltips trait
        ],
    ];

    /**
     * Memory thresholds for resource loading (in bytes)
     */
    public const MEMORY_THRESHOLDS = [
        'minimum' => 268435456,     // 256MB - Essential only
        'standard' => 536870912,    // 512MB - Essential + Standard
        'extended' => 1073741824,   // 1GB - All resources
    ];

    /**
     * Performance settings
     */
    public const PERFORMANCE = [
        'widget_polling_interval' => '30s',
        'max_table_records' => 50,
        'enable_real_time_updates' => true,
        'cache_duration' => 300, // 5 minutes
    ];

    /**
     * Get resources based on available memory
     * 
     * @param int $availableMemory
     * @return array<class-string>
     */
    public static function getResourcesForMemory(int $availableMemory): array
    {
        $resources = self::RESOURCE_TIERS['essential'];

        // If memory is unlimited (-1) or greater than standard threshold
        if ($availableMemory === -1 || $availableMemory >= self::MEMORY_THRESHOLDS['standard']) {
            $resources = array_merge($resources, self::RESOURCE_TIERS['standard']);
        }

        // If memory is unlimited (-1) or greater than extended threshold
        if ($availableMemory === -1 || $availableMemory >= self::MEMORY_THRESHOLDS['extended']) {
            $resources = array_merge($resources, self::RESOURCE_TIERS['extended']);
            
            // Add admin resources only for super admins with enough memory
            if (auth()->user()?->hasRole('Super Admin')) {
                $resources = array_merge($resources, self::RESOURCE_TIERS['admin']);
            }
        }

        return $resources;
    }

    /**
     * Get widgets based on user role and preferences
     * 
     * @param bool $includeMonitoring
     * @return array<class-string>
     */
    public static function getWidgetsForUser(bool $includeMonitoring = true): array
    {
        $widgets = array_merge(
            self::WIDGET_CATEGORIES['core'],
            self::WIDGET_CATEGORIES['stats']
        );

        if ($includeMonitoring && auth()->user()?->can('view_system_monitoring')) {
            $widgets = array_merge($widgets, self::WIDGET_CATEGORIES['monitoring']);
        }

        // Add activity widgets for all users
        $widgets = array_merge($widgets, self::WIDGET_CATEGORIES['activity']);

        return $widgets;
    }

    /**
     * Check if feature is enabled
     * 
     * @param string $feature
     * @return bool
     */
    public static function isFeatureEnabled(string $feature): bool
    {
        $features = [
            'auto_discovery' => env('FILAMENT_AUTO_DISCOVERY', false),
            'emergency_mode' => env('FILAMENT_EMERGENCY_MODE', false),
            'extended_resources' => env('FILAMENT_EXTENDED_RESOURCES', true),
            'real_time_widgets' => env('FILAMENT_REALTIME_WIDGETS', true),
            'debug_mode' => env('FILAMENT_DEBUG_MODE', false),
        ];

        return $features[$feature] ?? false;
    }
}