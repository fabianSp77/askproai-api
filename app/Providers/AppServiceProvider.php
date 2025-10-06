<?php
namespace App\Providers;

use App\Models\Service;
use App\Models\Appointment;
use App\Models\PolicyConfiguration;
use App\Models\CallbackRequest;
use App\Models\NotificationConfiguration;
use App\Observers\ServiceObserver;
use App\Observers\AppointmentObserver;
use App\Observers\PolicyConfigurationObserver;
use App\Observers\CallbackRequestObserver;
use App\Observers\NotificationConfigurationObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // CRITICAL DEBUG: Log boot start
        \Log::info('🚀 AppServiceProvider::boot() START - Memory: ' . round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB');

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
        config(['app.debug' => false]);

        // CRITICAL DEBUG: Log boot end
        \Log::info('✅ AppServiceProvider::boot() END - Memory: ' . round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB');
    }
}
