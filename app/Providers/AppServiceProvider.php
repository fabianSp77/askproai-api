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
                $app->make(\App\Services\CalcomService::class)
            );
        });
        
        // EventTypeNameParser Service
        $this->app->singleton(\App\Services\EventTypeNameParser::class);
        
        // Cal.com V2 Migration Service
        $this->app->singleton(\App\Services\CalcomV2MigrationService::class);
        
        // Security Layer
        $this->app->singleton(AskProAISecurityLayer::class);
        
        // Smart Migration Service
        $this->app->singleton(\App\Services\SmartMigrationService::class);
        
        // Mobile Detector Service
        $this->app->singleton(\App\Services\MobileDetector::class, function ($app) {
            return new \App\Services\MobileDetector($app['request']);
        });
        
        // Filament Login Response
        $this->app->bind(
            \Filament\Http\Responses\Auth\Contracts\LoginResponse::class,
            \App\Http\Responses\LoginResponse::class
        );
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
