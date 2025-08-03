<?php

return [
    Livewire\LivewireServiceProvider::class, // Load Livewire first
    App\Providers\AppServiceProvider::class,
    App\Providers\SessionFixServiceProvider::class, // MUST be before AuthServiceProvider
    App\Providers\PortalSessionServiceProvider::class, // Configure portal sessions
    App\Providers\AuthServiceProvider::class,
    App\Providers\CompanyContextServiceProvider::class, // Set company context after auth
    App\Providers\BroadcastServiceProvider::class,
    App\Providers\CalcomMigrationServiceProvider::class,
    App\Providers\CircuitBreakerServiceProvider::class,
    App\Providers\DatabasePoolServiceProvider::class,
    App\Providers\DatabaseServiceProvider::class,
    App\Providers\EventServiceProvider::class,
    App\Providers\FilamentBadgeServiceProvider::class,
    // App\Providers\FilamentSafeFixesServiceProvider::class, // Disabled - using consolidated JS modules
    App\Providers\Filament\AdminPanelProvider::class,
    App\Providers\Filament\BusinessPanelProvider::class, // Business portal panel
    App\Providers\FilamentServiceProvider::class, // Global Filament customizations
    App\Providers\FilamentCompanyContextProvider::class, // Must be after AdminPanelProvider
    App\Providers\FortifyServiceProvider::class,
    App\Providers\GdprServiceProvider::class,
    App\Providers\HorizonServiceProvider::class,
    App\Providers\IntegrationServiceProvider::class,
    App\Providers\KnowledgeServiceProvider::class,
    App\Providers\LockingServiceProvider::class,
    App\Providers\LoggingServiceProvider::class,
    App\Providers\LivewirePerformanceServiceProvider::class,
    App\Providers\MCPServiceProvider::class,
    App\Providers\MemoryBankServiceProvider::class,
    App\Providers\MonitoringServiceProvider::class,
    App\Providers\PerformanceServiceProvider::class,
    App\Providers\ResendMailServiceProvider::class,
    App\Providers\RetellAIMCPServiceProvider::class,
    App\Providers\RouteServiceProvider::class,
    App\Providers\ServiceTrackingProvider::class,
    App\Providers\ValidationServiceProvider::class,
];
