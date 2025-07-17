<?php

namespace App\Services\MCP;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\CircuitBreaker\CircuitBreaker;
use App\Services\RateLimiter\ApiRateLimiter;
use App\Services\Database\ConnectionPoolManager;
use App\Exceptions\MCPException;
use App\Contracts\MCPServiceInterface;
use App\Services\MCP\GitHubMCPServer;
use App\Services\MCP\ApidogMCPServer;
use App\Services\MCP\SequentialThinkingMCPServer;
use App\Services\MCP\DatabaseQueryMCPServer;
use App\Services\MCP\NotionMCPServer;
use App\Services\MCP\MemoryBankMCPServer;
use App\Services\MCP\FigmaMCPServer;

/**
 * MCP Orchestrator - Central routing and coordination for all MCP services
 * Handles service discovery, load balancing, and tenant isolation
 */
class MCPOrchestrator
{
    protected array $services = [];
    protected array $config;
    protected ApiRateLimiter $rateLimiter;
    protected ConnectionPoolManager $connectionPool;
    
    /**
     * Service health status cache
     */
    protected array $healthCache = [];
    
    /**
     * Metrics collection
     */
    protected array $metrics = [
        'requests' => 0,
        'errors' => 0,
        'latency' => [],
    ];
    
    public function __construct(
        ApiRateLimiter $rateLimiter,
        ConnectionPoolManager $connectionPool
    ) {
        $this->rateLimiter = $rateLimiter;
        $this->connectionPool = $connectionPool;
        
        $this->config = [
            'cache_ttl' => 60, // 1 minute health cache
            'timeout' => 30, // 30 second service timeout
            'max_retries' => 3,
            'tenant_quotas' => [
                'requests_per_minute' => 1000,
                'concurrent_operations' => 50,
            ],
        ];
        
        $this->registerServices();
    }
    
    /**
     * Register all available MCP services
     */
    protected function registerServices(): void
    {
        $this->services = [
            'webhook' => app(WebhookMCPServer::class),
            'calcom' => app(CalcomMCPServer::class),
            'database' => app(DatabaseMCPServer::class),
            'queue' => app(QueueMCPServer::class),
            'retell' => app(RetellMCPServer::class),
            'stripe' => app(StripeMCPServer::class),
            'github' => app(GitHubMCPServer::class),
            'apidog' => app(ApidogMCPServer::class),
            'sequential_thinking' => app(SequentialThinkingMCPServer::class),
            'database_query' => app(DatabaseQueryMCPServer::class),
            'notion' => app(NotionMCPServer::class),
            'memory_bank' => app(MemoryBankMCPServer::class),
            'figma' => app(FigmaMCPServer::class),
        ];
        
        Log::info('MCP Orchestrator initialized', [
            'services' => array_keys($this->services),
        ]);
    }
    
    /**
     * Route request to appropriate MCP service
     */
    public function route(MCPRequest $request): MCPResponse
    {
        $startTime = microtime(true);
        $this->metrics['requests']++;
        
        try {
            // Extract service and tenant information
            $service = $request->getService();
            $tenantId = $request->getTenantId();
            $operation = $request->getOperation();
            
            // Validate service exists
            if (!isset($this->services[$service])) {
                throw new MCPException("Service '{$service}' not found");
            }
            
            // Check tenant quotas
            $this->enforceQuotas($tenantId);
            
            // Check service health
            if (!$this->isServiceHealthy($service)) {
                throw new MCPException("Service '{$service}' is unhealthy");
            }
            
            // Apply rate limiting
            $this->rateLimiter->attempt("mcp:{$service}", "tenant:{$tenantId}");
            
            // Get circuit breaker for service
            $circuitBreaker = app("circuit.breaker.{$service}");
            
            // Execute operation with circuit breaker protection
            $response = $circuitBreaker->call($service, function () use ($service, $operation, $request) {
                return $this->executeOperation($service, $operation, $request);
            });
            
            // Record success metrics
            $duration = microtime(true) - $startTime;
            $this->recordMetrics($service, true, $duration);
            
            return new MCPResponse(
                success: true,
                data: $response,
                metadata: [
                    'service' => $service,
                    'duration_ms' => round($duration * 1000, 2),
                    'tenant_id' => $tenantId,
                ]
            );
            
        } catch (\Exception $e) {
            $this->metrics['errors']++;
            
            // Record error metrics
            $duration = microtime(true) - $startTime;
            $this->recordMetrics($service ?? 'unknown', false, $duration);
            
            Log::error('MCP Orchestrator error', [
                'service' => $service ?? 'unknown',
                'operation' => $operation ?? 'unknown',
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId ?? null,
            ]);
            
            return new MCPResponse(
                success: false,
                error: $e->getMessage(),
                metadata: [
                    'service' => $service ?? 'unknown',
                    'duration_ms' => round($duration * 1000, 2),
                ]
            );
        }
    }
    
    /**
     * Execute operation on MCP service
     */
    protected function executeOperation(string $service, string $operation, MCPRequest $request)
    {
        $serviceInstance = $this->services[$service];
        
        // Check if operation exists
        if (!method_exists($serviceInstance, $operation)) {
            throw new MCPException("Operation '{$operation}' not found on service '{$service}'");
        }
        
        // Set tenant context for the operation
        $this->setTenantContext($request->getTenantId());
        
        try {
            // Execute with timeout
            $result = $this->executeWithTimeout(
                fn() => $serviceInstance->$operation($request->getParams()),
                $this->config['timeout']
            );
            
            return $result;
        } finally {
            // Clear tenant context
            $this->clearTenantContext();
        }
    }
    
    /**
     * Execute operation with timeout
     */
    protected function executeWithTimeout(callable $operation, int $timeout)
    {
        // For now, just execute directly
        // TODO: Implement actual timeout mechanism
        return $operation();
    }
    
    /**
     * Check if service is healthy
     */
    protected function isServiceHealthy(string $service): bool
    {
        // Check cache first
        $cacheKey = "mcp:health:{$service}";
        $cached = Cache::get($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }
        
        // Perform health check
        try {
            $serviceInstance = $this->services[$service];
            
            // Basic health check - service exists and responds
            if (method_exists($serviceInstance, 'healthCheck')) {
                $health = $serviceInstance->healthCheck();
                $isHealthy = $health['status'] ?? false;
            } else {
                // Service doesn't have health check, assume healthy
                $isHealthy = true;
            }
            
            // Cache result
            Cache::put($cacheKey, $isHealthy, $this->config['cache_ttl']);
            
            return $isHealthy;
        } catch (\Exception $e) {
            Log::warning('Service health check failed', [
                'service' => $service,
                'error' => $e->getMessage(),
            ]);
            
            // Cache failure
            Cache::put($cacheKey, false, 30); // Shorter TTL for failures
            
            return false;
        }
    }
    
    /**
     * Enforce tenant quotas
     */
    public function enforceQuotas(int $tenantId): void
    {
        $quotas = $this->config['tenant_quotas'];
        
        // Check requests per minute
        $requestsKey = "mcp:quota:requests:{$tenantId}";
        $requests = Cache::increment($requestsKey);
        
        if ($requests === 1) {
            Cache::put($requestsKey, 1, 60); // Reset after 1 minute
        }
        
        if ($requests > $quotas['requests_per_minute']) {
            throw new MCPException('Tenant quota exceeded: Too many requests');
        }
        
        // Check concurrent operations
        $concurrentKey = "mcp:quota:concurrent:{$tenantId}";
        $concurrent = Cache::get($concurrentKey, 0);
        
        if ($concurrent >= $quotas['concurrent_operations']) {
            throw new MCPException('Tenant quota exceeded: Too many concurrent operations');
        }
        
        // Increment concurrent operations
        Cache::increment($concurrentKey);
        
        // Register cleanup callback
        register_shutdown_function(function () use ($concurrentKey) {
            Cache::decrement($concurrentKey);
        });
    }
    
    /**
     * Get overall health status
     */
    public function healthCheck(): array
    {
        $health = [
            'status' => 'healthy',
            'services' => [],
            'metrics' => $this->getMetrics(),
            'connection_pool' => $this->connectionPool->getStats(),
        ];
        
        foreach ($this->services as $name => $service) {
            $health['services'][$name] = $this->isServiceHealthy($name) ? 'healthy' : 'unhealthy';
            
            if ($health['services'][$name] === 'unhealthy') {
                $health['status'] = 'degraded';
            }
        }
        
        return $health;
    }
    
    /**
     * Get performance metrics
     */
    public function getMetrics(): array
    {
        return [
            'total_requests' => $this->metrics['requests'],
            'total_errors' => $this->metrics['errors'],
            'error_rate' => $this->metrics['requests'] > 0 
                ? round(($this->metrics['errors'] / $this->metrics['requests']) * 100, 2) 
                : 0,
            'avg_latency_ms' => !empty($this->metrics['latency']) 
                ? round(array_sum($this->metrics['latency']) / count($this->metrics['latency']), 2)
                : 0,
            'p99_latency_ms' => $this->calculateP99Latency(),
        ];
    }
    
    /**
     * Record metrics for monitoring
     */
    protected function recordMetrics(string $service, bool $success, float $duration): void
    {
        // Add to latency array (keep last 1000 entries)
        $this->metrics['latency'][] = $duration * 1000;
        if (count($this->metrics['latency']) > 1000) {
            array_shift($this->metrics['latency']);
        }
        
        // Store in database for long-term analysis
        try {
            \DB::table('mcp_metrics')->insert([
                'service' => $service,
                'success' => $success,
                'duration_ms' => round($duration * 1000, 2),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to record MCP metrics', [
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Calculate P99 latency
     */
    protected function calculateP99Latency(): float
    {
        if (empty($this->metrics['latency'])) {
            return 0;
        }
        
        $sorted = $this->metrics['latency'];
        sort($sorted);
        
        $index = (int) ceil(count($sorted) * 0.99) - 1;
        return round($sorted[$index] ?? 0, 2);
    }
    
    /**
     * Set tenant context for current operation
     */
    protected function setTenantContext(int $tenantId): void
    {
        app()->instance('current_company_id', $tenantId);
    }
    
    /**
     * Clear tenant context
     */
    protected function clearTenantContext(): void
    {
        app()->forgetInstance('current_company_id');
    }
    
    /**
     * Get service instance
     */
    public function getService(string $name): ?object
    {
        return $this->services[$name] ?? null;
    }
    
    /**
     * Warmup all services
     */
    public function warmup(): void
    {
        foreach ($this->services as $name => $service) {
            try {
                $this->isServiceHealthy($name);
                Log::info('MCP service warmed up', ['service' => $name]);
            } catch (\Exception $e) {
                Log::warning('MCP service warmup failed', [
                    'service' => $name,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}