<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\MCP\MCPOrchestrator;
use App\Services\MCPAutoDiscoveryService;
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
use App\Services\MCP\GitHubMCPServer;
use App\Services\MCP\ApidogMCPServer;
use App\Services\MCP\SequentialThinkingMCPServer;
use App\Services\MCP\DatabaseQueryMCPServer;
use App\Services\MCP\NotionMCPServer;
use App\Services\MCP\MemoryBankMCPServer;
use App\Services\MCP\FigmaMCPServer;
use App\Services\MCP\AuthenticationMCPServer;
use App\Services\MCP\DebugMCPServer;
use App\Services\MCP\CallMCPServer;
use App\Services\MCP\BillingMCPServer;
use App\Services\MCP\WebSocketMCPServer;
use App\Services\MCP\EventMCPServer;
use App\Services\MCP\DashboardMCPServer;
use App\Services\MCP\SettingsMCPServer;
use App\Services\MCP\BranchMCPServer;
use App\Services\MCP\CompanyMCPServer;
use App\Services\MCP\SentryMCPServer;
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
        
        // Register MCP Auto Discovery Service
        $this->app->singleton(MCPAutoDiscoveryService::class, function ($app) {
            return new MCPAutoDiscoveryService(
                $app->make(MCPOrchestrator::class)
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
        $this->app->singleton(GitHubMCPServer::class);
        $this->app->singleton(ApidogMCPServer::class);
        $this->app->singleton(SequentialThinkingMCPServer::class);
        $this->app->singleton(DatabaseQueryMCPServer::class);
        $this->app->singleton(NotionMCPServer::class);
        $this->app->singleton(MemoryBankMCPServer::class);
        $this->app->singleton(FigmaMCPServer::class);
        $this->app->singleton(AuthenticationMCPServer::class);
        $this->app->singleton(DebugMCPServer::class);
        $this->app->singleton(CallMCPServer::class);
        $this->app->singleton(BillingMCPServer::class);
        $this->app->singleton(WebSocketMCPServer::class);
        $this->app->singleton(EventMCPServer::class);
        $this->app->singleton(DashboardMCPServer::class);
        $this->app->singleton(SettingsMCPServer::class);
        $this->app->singleton(BranchMCPServer::class);
        $this->app->singleton(CompanyMCPServer::class);
        $this->app->singleton(SentryMCPServer::class);
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
                    
                    // TEMPORARILY DISABLED - Causes TenantScope issues
                    /*
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
                    */
                    
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