<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ServiceLayerProvider extends ServiceProvider
{
    /**
     * Service bindings
     */
    public array $bindings = [
        \App\Contracts\Services\AvailabilityServiceInterface::class => \App\Services\AvailabilityService::class,
        \App\Contracts\Services\CalcomMigrationServiceInterface::class => \App\Services\CalcomMigrationService::class,
        \App\Contracts\Services\CompanyServiceInterface::class => \App\Services\CompanyService::class,
        \App\Contracts\Services\FileWatcherServiceInterface::class => \App\Services\FileWatcherService::class,
        \App\Contracts\Services\SecureAuthenticationServiceInterface::class => \App\Services\SecureAuthenticationService::class,
        \App\Contracts\Services\ServiceServiceInterface::class => \App\Services\ServiceService::class,
        \App\Contracts\Services\StaffServiceInterface::class => \App\Services\StaffService::class,
        \App\Contracts\Services\StripeTopupServiceInterface::class => \App\Services\StripeTopupService::class,
    ];
    
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register singleton services to prevent multiple instantiation
        $this->app->singleton(\App\Services\CompanyService::class);
        $this->app->singleton(\App\Services\StaffService::class);
        $this->app->singleton(\App\Services\ServiceService::class);
        $this->app->singleton(\App\Services\AvailabilityService::class);
    }
    
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
