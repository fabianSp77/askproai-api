<?php
namespace App\Providers;

use App\Models\Service;
use App\Models\Appointment;
use App\Models\PolicyConfiguration;
use App\Models\CallbackRequest;
use App\Models\NotificationConfiguration;
use App\Observers\ServiceObserver;
use App\Observers\AppointmentObserver;
use App\Observers\AppointmentPhaseObserver;
use App\Observers\PolicyConfigurationObserver;
use App\Observers\CallbackRequestObserver;
use App\Observers\NotificationConfigurationObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Appointments\Contracts\AvailabilityServiceInterface;
use App\Services\Appointments\WeeklyAvailabilityService;
use App\Services\Appointments\Contracts\BookingServiceInterface;
use App\Services\Appointments\BookingService;
use App\Services\Validation\PostBookingValidationService;
use App\Services\Monitoring\DataConsistencyMonitor;
use App\Services\Resilience\AppointmentBookingCircuitBreaker;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Phase 5: Register EventBus singleton for event-driven architecture
        $this->app->singleton(
            \App\Shared\Events\EventBus::class,
            fn () => new \App\Shared\Events\EventBus()
        );

        // Bind Availability Service Interface
        $this->app->bind(
            AvailabilityServiceInterface::class,
            WeeklyAvailabilityService::class
        );

        // Bind Booking Service Interface
        $this->app->bind(
            BookingServiceInterface::class,
            BookingService::class
        );

        // Register Data Consistency Prevention Services (2025-10-20)
        $this->app->singleton(PostBookingValidationService::class);
        $this->app->singleton(DataConsistencyMonitor::class);
        $this->app->singleton(AppointmentBookingCircuitBreaker::class);
    }

    public function boot(): void
    {
        // CRITICAL DEBUG: Log boot start
        \Log::info('🚀 AppServiceProvider::boot() START - Memory: ' . round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB');

        // STAGING GUARD: Prevent production database access
        if (app()->environment('staging')) {
            if (config('database.connections.mysql.database') === 'askproai_db') {
                throw new \RuntimeException(
                    '🚨 STAGING DARF NICHT GEGEN PRODUCTION DB LAUFEN! ' .
                    'Check .env: DB_DATABASE muss askproai_staging sein.'
                );
            }
        }

        // Phase 4: Enable Database Performance Monitor for N+1 detection
        \App\Services\Monitoring\DatabasePerformanceMonitor::enable();

        // Phase 5: Register event listeners for event-driven architecture
        $this->registerEventListeners();

        // Set German locale for Carbon dates
        Carbon::setLocale('de');

        // Set German locale for Number formatting
        Number::useLocale('de');

        /* Alias **jedes Mal** beim Booten registrieren  */
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware(
            'calcom.signature',
            \App\Http\Middleware\VerifyCalcomSignature::class
        );

        // Register Service Observer
        Service::observe(ServiceObserver::class);

        // Register Appointment Observer (auto-syncs call flags)
        Appointment::observe(AppointmentObserver::class);

        // Register AppointmentPhase Observer (auto-creates phases for processing time services)
        Appointment::observe(AppointmentPhaseObserver::class);

        // Register Multi-Tenant Input Validation Observers
        PolicyConfiguration::observe(PolicyConfigurationObserver::class);
        CallbackRequest::observe(CallbackRequestObserver::class);
        NotificationConfiguration::observe(NotificationConfigurationObserver::class);

        // CRITICAL DEBUG: Query and memory logging to identify 2GB exhaustion
        DB::listen(function ($query) {
            $memoryMB = round(memory_get_usage(true) / 1024 / 1024, 2);

            // Log every query with memory usage
            Log::channel('single')->info("[{$memoryMB} MB] QUERY", [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time_ms' => $query->time,
            ]);

            // Alert at memory thresholds
            if ($memoryMB > 1500) {
                Log::channel('single')->critical("⚠️ MEMORY THRESHOLD 1.5GB EXCEEDED", [
                    'memory_mb' => $memoryMB,
                    'query' => $query->sql,
                ]);
            }

            if ($memoryMB > 1800) {
                Log::channel('single')->emergency("🚨 MEMORY CRITICAL 1.8GB", [
                    'memory_mb' => $memoryMB,
                    'query' => $query->sql,
                    'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5),
                ]);
            }
        });

        // Disable debug mode for production
        // TEMPORARILY COMMENTED OUT FOR DEBUGGING 405 ERROR (2025-10-17)
        // config(['app.debug' => false]);

        // CRITICAL DEBUG: Log boot end
        \Log::info('✅ AppServiceProvider::boot() END - Memory: ' . round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB');
    }

    /**
     * Phase 5: Register event listeners for domain events
     */
    private function registerEventListeners(): void
    {
        $eventBus = $this->app->make(\App\Shared\Events\EventBus::class);

        // Appointment domain listeners
        $eventBus->subscribe(
            \App\Domains\Appointments\Events\AppointmentCreatedEvent::class,
            new \App\Domains\Appointments\Listeners\CalcomSyncListener()
        );

        $eventBus->subscribe(
            \App\Domains\Appointments\Events\AppointmentCreatedEvent::class,
            new \App\Domains\Appointments\Listeners\SendConfirmationListener()
        );

        \Log::info('✅ Event listeners registered for Phase 5 event-driven architecture');
    }
}
