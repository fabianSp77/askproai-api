<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AvailabilityService;
use App\Services\ConflictDetectionService;
use App\Services\NotificationService;
use App\Contracts\AvailabilityServiceInterface;
use App\Contracts\CalendarProviderInterface;
use App\Services\CalendarProviders\CalcomProvider;
use App\Services\CalendarProviders\GoogleCalendarProvider;
use App\Repositories\AppointmentRepository;
use App\Services\Booking\UniversalBookingOrchestrator;
use App\Services\Booking\StaffServiceMatcher;
use App\Services\Booking\UnifiedAvailabilityService;
use App\Services\Booking\Strategies\BranchSelectionStrategyInterface;
use App\Services\PhoneNumberResolver;
use App\Services\CalcomV2Service;
use App\Services\CacheService;

class BookingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind interfaces to implementations
        $this->app->bind(AvailabilityServiceInterface::class, AvailabilityService::class);
        
        // Register calendar providers
        $this->app->bind('calendar.calcom', CalcomProvider::class);
        $this->app->bind('calendar.google', GoogleCalendarProvider::class);
        
        // Default calendar provider
        $this->app->bind(CalendarProviderInterface::class, function ($app) {
            $default = config('services.calendar.default', 'calcom');
            return $app->make("calendar.{$default}");
        });
        
        // Singleton services
        $this->app->singleton(ConflictDetectionService::class);
        $this->app->singleton(NotificationService::class);
        $this->app->singleton(AppointmentRepository::class);
        
        // Register new booking services
        $this->registerBookingServices();
    }
    
    /**
     * Register new universal booking services
     */
    protected function registerBookingServices(): void
    {
        // Register default branch selection strategy
        $this->app->bind(BranchSelectionStrategyInterface::class, function ($app) {
            // Default strategy can be configured in config
            $strategy = config('booking.default_branch_strategy', 'nearest');
            
            return match ($strategy) {
                'nearest' => new \App\Services\Booking\Strategies\NearestLocationStrategy(),
                'first_available' => new \App\Services\Booking\Strategies\FirstAvailableStrategy(),
                'load_balanced' => new \App\Services\Booking\Strategies\LoadBalancedStrategy(),
                default => new \App\Services\Booking\Strategies\NearestLocationStrategy(),
            };
        });
        
        // Register UniversalBookingOrchestrator as singleton
        $this->app->singleton(UniversalBookingOrchestrator::class, function ($app) {
            return new UniversalBookingOrchestrator(
                $app->make(PhoneNumberResolver::class),
                $app->make(StaffServiceMatcher::class),
                $app->make(UnifiedAvailabilityService::class),
                $app->make(CalcomV2Service::class),
                $app->make(NotificationService::class),
                $app->make(BranchSelectionStrategyInterface::class)
            );
        });
        
        // Register StaffServiceMatcher as singleton
        $this->app->singleton(StaffServiceMatcher::class);
        
        // Register UnifiedAvailabilityService
        $this->app->singleton(UnifiedAvailabilityService::class, function ($app) {
            return new UnifiedAvailabilityService(
                $app->make(CalcomV2Service::class),
                $app->make(CacheService::class)
            );
        });
        
        // Register PhoneNumberResolver as singleton
        $this->app->singleton(PhoneNumberResolver::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register policies
        $this->registerPolicies();
        
        // Register queue jobs
        $this->registerQueueJobs();
        
        // Register console commands
        $this->registerCommands();
    }
    
    /**
     * Register policies
     */
    protected function registerPolicies(): void
    {
        \Illuminate\Support\Facades\Gate::policy(
            \App\Models\CalcomEventType::class,
            \App\Policies\EventTypePolicy::class
        );
    }
    
    /**
     * Register queue jobs
     */
    protected function registerQueueJobs(): void
    {
        // Configure queue connections for notifications
        config([
            'queue.connections.notifications' => [
                'driver' => 'database',
                'table' => 'jobs',
                'queue' => 'notifications',
                'retry_after' => 90,
                'after_commit' => false,
            ]
        ]);
    }
    
    /**
     * Register console commands
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\SendAppointmentReminders::class,
                \App\Console\Commands\CleanupOldNotifications::class,
                \App\Console\Commands\GenerateAvailabilityReport::class,
            ]);
        }
    }
}