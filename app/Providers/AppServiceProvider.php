<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;
use App\Security\AskProAISecurityLayer;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // RetellAgentService als Singleton registrieren
        $this->app->singleton(\App\Services\RetellAgentService::class);
        
        // Cal.com Sync Service
        $this->app->singleton(\App\Services\CalcomSyncService::class, function ($app) {
            return new \App\Services\CalcomSyncService(
                $app->make(\App\Services\CalcomV2Service::class)
            );
        });
        
        // EventTypeNameParser Service
        $this->app->singleton(\App\Services\EventTypeNameParser::class);
        
        // SmartEventTypeNameParser Service
        $this->app->singleton(\App\Services\SmartEventTypeNameParser::class);
        
        // Cal.com V2 Migration Service (old name)
        $this->app->singleton(\App\Services\CalcomV2MigrationService::class);
        
        // Cal.com Migration Service (handles V1 to V2 migration)
        $this->app->singleton(\App\Services\CalcomMigrationService::class, function ($app) {
            return new \App\Services\CalcomMigrationService(
                $app->make(\App\Services\CalcomV2Service::class),
                $app->make(\App\Services\CalcomV2Service::class)
            );
        });
        
        // Security Layer
        $this->app->singleton(AskProAISecurityLayer::class);
        
        // Smart Migration Service
        $this->app->singleton(\App\Services\SmartMigrationService::class);
        
        // Mobile Detector Service
        $this->app->singleton(\App\Services\MobileDetector::class, function ($app) {
            return new \App\Services\MobileDetector($app['request']);
        });
        
        // Dashboard Metrics Service
        $this->app->singleton(\App\Services\Dashboard\DashboardMetricsService::class);
        
        // ROI Calculation Service
        $this->app->singleton(\App\Services\Analytics\RoiCalculationService::class);
        
        // Circuit Breaker Service
        $this->app->singleton(\App\Services\CircuitBreaker\CircuitBreaker::class, function ($app) {
            return new \App\Services\CircuitBreaker\CircuitBreaker(
                failureThreshold: config('circuit-breaker.failure_threshold', 5),
                successThreshold: config('circuit-breaker.success_threshold', 2),
                timeout: config('circuit-breaker.timeout', 60),
                halfOpenRequests: config('circuit-breaker.half_open_requests', 3)
            );
        });
        
        // Filament Login Response
        $this->app->bind(
            \Filament\Http\Responses\Auth\Contracts\LoginResponse::class,
            \App\Http\Responses\LoginResponse::class
        );
        
        // MCP Sentry Server
        $this->app->singleton(\App\Services\MCP\SentryMCPServer::class);
    }

    public function boot(Router $router): void
    {
        /* Alias **jedes Mal** beim Booten registrieren  */
        $router->aliasMiddleware(
            'calcom.signature',
            \App\Http\Middleware\VerifyCalcomSignature::class
        );

        // Setze die Locale auf Deutsch
        App::setLocale('de');

        // Setze Carbon auf Deutsch
        Carbon::setLocale('de');

        // Setze die Fallback-Locale
        App::setFallbackLocale('en');

        // Zeitzone für Deutschland setzen
        date_default_timezone_set('Europe/Berlin');
        
        // Initialize Security Layer - DISABLED due to infinite loop
        // if (!app()->runningInConsole() || app()->runningUnitTests()) {
        //     $securityLayer = $this->app->make(AskProAISecurityLayer::class);
        //     $securityLayer->protect();
        // }
        
        // Configure URLs for production environment
        if (!app()->runningInConsole() && config('app.env') === 'production') {
            // Force HTTPS in production
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
        
        // Register Livewire components
        Livewire::component('tutorial-overlay', \App\Livewire\TutorialOverlay::class);
        Livewire::component('test-debug', \App\Livewire\TestDebug::class);
        
        // Let Livewire handle its own routes
    }
}
