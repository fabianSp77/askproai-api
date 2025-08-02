<?php

namespace App\Providers;

use App\Security\AskProAISecurityLayer;
use Carbon\Carbon;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
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
            // Create V1 service directly (uses backwards compatibility)
            $v1Service = new \App\Services\CalcomService();
            
            // Get V2 service from container
            $v2Service = $app->make(\App\Services\CalcomV2Service::class);
            
            return new \App\Services\CalcomMigrationService(
                $v1Service,
                $v2Service
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

        // Circuit Breaker Manager
        $this->app->singleton(\App\Services\CircuitBreaker\CircuitBreakerManager::class, function ($app) {
            return \App\Services\CircuitBreaker\CircuitBreakerManager::getInstance();
        });

        // Stripe Service with Circuit Breaker
        $this->app->singleton(\App\Services\StripeServiceWithCircuitBreaker::class);

        // Filament Login Response
        $this->app->bind(
            \Filament\Http\Responses\Auth\Contracts\LoginResponse::class,
            \App\Http\Responses\LoginResponse::class
        );

        // MCP Sentry Server
        $this->app->singleton(\App\Services\MCP\SentryMCPServer::class);

        // Monitoring Services
        $this->app->singleton(\App\Services\Monitoring\MetricsCollector::class);
        $this->app->singleton(\App\Services\Monitoring\MetricsCollectorService::class);

        // Register Prometheus CollectorRegistry
        $this->app->singleton(\Prometheus\CollectorRegistry::class, function ($app) {
            $options = [
                'host' => config('database.redis.default.host'),
                'port' => config('database.redis.default.port'),
                'database' => 2, // Separate Redis DB for metrics
            ];

            // Only add password if it's not null
            $password = config('database.redis.default.password');
            if (! empty($password)) {
                $options['password'] = $password;
            }

            $redis = new \Prometheus\Storage\Redis($options);

            return new \Prometheus\CollectorRegistry($redis);
        });

        // Register Unified Services
        $this->app->singleton(\App\Services\Unified\CalcomServiceUnified::class);
        $this->app->singleton(\App\Services\Unified\RetellServiceUnified::class);

        // Register Webhook Services
        $this->app->singleton(\App\Services\Webhook\WebhookDeduplicationService::class);

        // Register Monitoring & Alerting Services
        $this->app->singleton(\App\Services\Monitoring\UnifiedAlertingService::class);
        $this->app->singleton(\App\Services\Notifications\SlackNotificationService::class);

        // Register MCP Services
        $this->app->singleton(\App\Services\MCP\DatabaseMCPServer::class);
        $this->app->singleton(\App\Services\MCP\CalcomMCPServer::class);
        $this->app->singleton(\App\Services\MCP\RetellMCPServer::class);
        $this->app->singleton(\App\Services\MCP\QueueMCPServer::class);
        $this->app->singleton(\App\Services\MCP\WebhookMCPServer::class);
        $this->app->singleton(\App\Services\MCP\BranchMCPServer::class);
        $this->app->singleton(\App\Services\MCP\CompanyMCPServer::class);
        $this->app->singleton(\App\Services\MCP\AppointmentMCPServer::class);
        $this->app->singleton(\App\Services\MCP\CustomerMCPServer::class);
        $this->app->singleton(\App\Services\MCP\Context7MCPServer::class);

        // Context7 Service
        $this->app->singleton(\App\Services\Context7Service::class);

        // Register Branch Context Manager
        $this->app->singleton(\App\Services\BranchContextManager::class);
    }

    public function boot(Router $router): void
    {
        // CRITICAL: Set company context for authenticated users
        $this->app->booted(function () {
            if (request() && !app()->runningInConsole()) {
                if (auth()->check() && auth()->user() && auth()->user()->company_id) {
                    app()->instance('current_company_id', auth()->user()->company_id);
                    app()->instance('company_context_source', 'web_auth');
                }
            }
        });
        
        /* Alias **jedes Mal** beim Booten registrieren */
        $router->aliasMiddleware(
            'calcom.signature',
            \App\Http\Middleware\VerifyCalcomSignature::class
        );
        
        // Override Filament's LoginResponse to fix session persistence
        $this->app->bind(
            \Filament\Http\Responses\Auth\Contracts\LoginResponse::class,
            \App\Http\Responses\Auth\CustomLoginResponse::class
        );
        
        // Fix session cookie parameters
        // REMOVED - ini_set() cannot be called after session starts

        // Setze die Locale auf Deutsch
        App::setLocale('de');

        // Setze Carbon auf Deutsch
        Carbon::setLocale('de');

        // Setze die Fallback-Locale
        App::setFallbackLocale('en');

        // Zeitzone für Deutschland setzen
        date_default_timezone_set('Europe/Berlin');

        // Globale Filament-Konfiguration für deutsches Datumsformat
        \Filament\Forms\Components\DatePicker::configureUsing(function (\Filament\Forms\Components\DatePicker $datePicker) {
            return $datePicker
                ->format('d.m.Y')
                ->displayFormat('DD.MM.YYYY')
                ->timezone('Europe/Berlin');
        });

        \Filament\Forms\Components\DateTimePicker::configureUsing(function (\Filament\Forms\Components\DateTimePicker $dateTimePicker) {
            return $dateTimePicker
                ->format('d.m.Y H:i')
                ->displayFormat('DD.MM.YYYY HH:mm')
                ->timezone('Europe/Berlin');
        });

        // Globale Table-Konfiguration für deutsches Datumsformat
        \Filament\Tables\Columns\TextColumn::configureUsing(function (\Filament\Tables\Columns\TextColumn $column) {
            // Nur für Datums-Spalten das Format setzen
            return $column;
        });

        // Initialize Security Layer - DISABLED due to infinite loop
        // if (!app()->runningInConsole() || app()->runningUnitTests()) {
        //     $securityLayer = $this->app->make(AskProAISecurityLayer::class);
        //     $securityLayer->protect();
        // }

        // Configure URLs for production environment
        if (! app()->runningInConsole() && config('app.env') === 'production') {
            // Force HTTPS in production
            URL::forceScheme('https');
        }

        // Register Livewire components
        // Livewire::component('tutorial-overlay', \App\Livewire\TutorialOverlay::class);
        // Livewire::component('test-debug', \App\Livewire\TestDebug::class);

        // Register our test component
        if (class_exists(\App\Livewire\TestComponent::class)) {
            Livewire::component('test-component', \App\Livewire\TestComponent::class);
        }

        // Register Global Branch Selector
        Livewire::component('global-branch-selector', \App\Livewire\GlobalBranchSelector::class);

        // Let Livewire handle its own routes
    }
}
