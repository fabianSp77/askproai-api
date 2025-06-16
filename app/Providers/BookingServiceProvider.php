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