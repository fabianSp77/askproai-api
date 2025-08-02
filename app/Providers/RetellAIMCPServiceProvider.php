<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\MCP\RetellAIBridgeMCPServer;
use App\Services\MCP\RetellAIBridgeMCPServerEnhanced;
use App\Services\MCP\RetellMCPServer;
use App\Services\PhoneNumberResolver;
use App\Services\AgentSelectionService;
use App\Services\AppointmentBookingService;
use App\Services\CalcomV2Service;
use App\Services\CircuitBreaker\CircuitBreakerManager;
use App\Livewire\CallInitiatorWidget;
use App\Livewire\VoiceTestConsole;
use Livewire\Livewire;

class RetellAIMCPServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register RetellAIBridgeMCPServer as singleton (enhanced version)
        $this->app->singleton(RetellAIBridgeMCPServer::class, function ($app) {
            // Use enhanced version if circuit breaker is available
            if ($app->bound(CircuitBreakerManager::class)) {
                return new RetellAIBridgeMCPServerEnhanced(
                    $app->make(RetellMCPServer::class),
                    $app->make(PhoneNumberResolver::class),
                    $app->make(AgentSelectionService::class),
                    $app->make(CircuitBreakerManager::class)
                );
            }
            
            // Fallback to basic version
            return new RetellAIBridgeMCPServer(
                $app->make(RetellMCPServer::class),
                $app->make(PhoneNumberResolver::class),
                $app->make(AgentSelectionService::class)
            );
        });

        // Register MCP configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/retell-mcp.php',
            'retell-mcp'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register Livewire components
        Livewire::component('call-initiator-widget', CallInitiatorWidget::class);
        Livewire::component('voice-test-console', VoiceTestConsole::class);

        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/retell-mcp.php' => config_path('retell-mcp.php'),
            ], 'retell-mcp-config');

            // Publish MCP server files
            $this->publishes([
                __DIR__ . '/../../mcp-external/retellai-mcp-server' => base_path('mcp-external/retellai-mcp-server'),
            ], 'retell-mcp-server');
        }

        // Register middleware for MCP webhooks
        $router = $this->app['router'];
        $router->aliasMiddleware('mcp.webhook', \App\Http\Middleware\VerifyMCPWebhookSignature::class);

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\RetellMCPHealthCheckCommand::class,
                \App\Console\Commands\RetellMCPTestCallCommand::class,
            ]);
        }

        // Register event listeners
        $this->app['events']->listen(
            \App\Events\OutboundCallInitiated::class,
            \App\Listeners\LogOutboundCall::class
        );

        $this->app['events']->listen(
            \App\Events\CallCampaignCompleted::class,
            \App\Listeners\SendCampaignReport::class
        );

        // Register health checks
        // Commented out - health service not available
        // $this->app->make('health')->checks([
        //     \App\Health\Checks\RetellMCPServerCheck::class,
        // ]);
    }
}