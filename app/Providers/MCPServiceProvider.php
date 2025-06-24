<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\MCP\MCPOrchestrator;
use App\Services\MCP\WebhookMCPServer;
use App\Services\MCP\CalcomMCPServer;
use App\Services\MCP\DatabaseMCPServer;
use App\Services\MCP\QueueMCPServer;
use App\Services\MCP\RetellMCPServer;
use App\Services\MCP\StripeMCPServer;
use App\Services\MCP\KnowledgeMCPServer;
use App\Services\MCP\DistributedTransactionManager;
use App\Services\MCP\MCPServiceRegistry;
use App\Services\MCP\MCPCacheManager;
use App\Services\MCP\MCPMetricsCollector;
use App\Services\MCP\MCPHealthCheckService;
use App\Services\RateLimiter\ApiRateLimiter;
use App\Services\Database\ConnectionPoolManager;
use App\Services\MCP\MCPCacheWarmer;
use App\Services\MCP\MCPQueryOptimizer;
use App\Services\MCP\MCPConnectionPoolManager;
use App\Services\MCP\MCPContextResolver;
use App\Services\MCP\MCPBookingOrchestrator;
use App\Services\MCP\ExternalMCPManager;
use App\Services\MCP\MCPGateway;
use App\Services\MCP\RetellConfigurationMCPServer;
use App\Services\MCP\RetellCustomFunctionMCPServer;
use App\Services\MCP\AppointmentManagementMCPServer;
use App\Services\MCP\AppointmentMCPServer;
use App\Services\MCP\CustomerMCPServer;
use Illuminate\Support\Facades\Log;

class MCPServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register Connection Pool Manager as singleton
        $this->app->singleton(ConnectionPoolManager::class, function ($app) {
            return new ConnectionPoolManager();
        });
        
        // Register API Rate Limiter
        $this->app->singleton(ApiRateLimiter::class);
        
        // Register MCP Core Components
        $this->app->singleton(MCPServiceRegistry::class);
        $this->app->singleton(MCPCacheManager::class);
        $this->app->singleton(MCPMetricsCollector::class);
        $this->app->singleton(MCPHealthCheckService::class);
        
        // Register Performance Optimization Components
        $this->app->singleton(MCPCacheWarmer::class);
        $this->app->singleton(MCPQueryOptimizer::class);
        $this->app->singleton(MCPConnectionPoolManager::class);
        
        // Register MCP Orchestrator
        $this->app->singleton(MCPOrchestrator::class, function ($app) {
            return new MCPOrchestrator(
                $app->make(ApiRateLimiter::class),
                $app->make(ConnectionPoolManager::class)
            );
        });
        
        // Register individual MCP servers
        $this->app->singleton(WebhookMCPServer::class);
        $this->app->singleton(CalcomMCPServer::class);
        $this->app->singleton(DatabaseMCPServer::class);
        $this->app->singleton(QueueMCPServer::class);
        $this->app->singleton(RetellMCPServer::class);
        $this->app->singleton(StripeMCPServer::class);
        $this->app->singleton(KnowledgeMCPServer::class);
        
        // Register MCP Context and Booking services
        $this->app->singleton(MCPContextResolver::class);
        $this->app->singleton(MCPBookingOrchestrator::class);
        
        // Register Distributed Transaction Manager
        $this->app->bind(DistributedTransactionManager::class, function ($app) {
            return new DistributedTransactionManager();
        });
        
        // Register External MCP Manager
        $this->app->singleton(ExternalMCPManager::class);
        
        // Register new MCP servers
        $this->app->singleton(MCPGateway::class);
        $this->app->singleton(RetellConfigurationMCPServer::class);
        $this->app->singleton(RetellCustomFunctionMCPServer::class);
        $this->app->singleton(AppointmentManagementMCPServer::class);
        $this->app->singleton(AppointmentMCPServer::class);
        $this->app->singleton(CustomerMCPServer::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Fixed MCP warmup with proper error handling
        if (!app()->runningInConsole()) {
            app()->booted(function () {
                try {
                    // Add timeout to prevent infinite loops
                    $timeout = 5; // 5 seconds max
                    $startTime = time();
                    
                    // Warm up orchestrator with timeout check
                    $orchestrator = app(MCPOrchestrator::class);
                    if ((time() - $startTime) < $timeout) {
                        $orchestrator->warmup();
                    }
                    
                    // Warm cache only if we have time left
                    if ((time() - $startTime) < $timeout && class_exists(MCPCacheWarmer::class)) {
                        try {
                            $cacheWarmer = app(MCPCacheWarmer::class);
                            if (method_exists($cacheWarmer, 'warmAll')) {
                                $cacheWarmer->warmAll();
                            }
                        } catch (\Exception $e) {
                            Log::debug('MCPCacheWarmer not available', ['error' => $e->getMessage()]);
                        }
                    }
                    
                    // Skip connection pool configuration - method doesn't exist
                    // The MCPConnectionPoolManager optimizes on demand
                    
                } catch (\Exception $e) {
                    Log::warning('Failed to warmup MCP services', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            });
        }
    }
}