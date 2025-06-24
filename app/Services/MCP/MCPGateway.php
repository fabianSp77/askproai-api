<?php

namespace App\Services\MCP;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Exceptions\MCPException;

/**
 * MCP Gateway - Central routing and orchestration for all MCP servers
 * 
 * This gateway provides a unified interface for all MCP communication,
 * handling service discovery, request routing, and response formatting.
 */
class MCPGateway
{
    protected array $servers = [];
    protected array $serverFactories = [];
    protected array $config;
    
    public function __construct()
    {
        $this->config = [
            'timeout' => config('mcp.gateway.timeout', 30),
            'retries' => config('mcp.gateway.retries', 3),
            'circuit_breaker' => config('mcp.gateway.circuit_breaker', true),
        ];
        
        $this->registerServerFactories();
    }
    
    /**
     * Register server factories for lazy loading
     */
    protected function registerServerFactories(): void
    {
        // Core MCP servers - use factories to avoid immediate instantiation
        $this->serverFactories = [
            'webhook' => fn() => class_exists(WebhookMCPServer::class) 
                ? app(WebhookMCPServer::class) 
                : null,
                
            'calcom' => fn() => class_exists(CalcomMCPServer::class) 
                ? app(CalcomMCPServer::class) 
                : null,
                
            'retell' => fn() => class_exists(RetellMCPServer::class) 
                ? app(RetellMCPServer::class) 
                : null,
                
            'database' => fn() => class_exists(DatabaseMCPServer::class) 
                ? app(DatabaseMCPServer::class) 
                : null,
                
            'queue' => fn() => class_exists(QueueMCPServer::class) 
                ? app(QueueMCPServer::class) 
                : null,
                
            'retell_config' => fn() => class_exists(RetellConfigurationMCPServer::class) 
                ? app(RetellConfigurationMCPServer::class) 
                : null,
                
            // These servers have dependencies that might cause circular references
            'retell_custom' => fn() => class_exists(RetellCustomFunctionMCPServer::class) 
                ? app()->makeWith(RetellCustomFunctionMCPServer::class, [
                    'phoneResolver' => app(PhoneNumberResolver::class),
                    'bookingService' => app()->makeWith(AppointmentBookingService::class, [
                        'mcpGateway' => null // Break circular dependency
                    ])
                ]) 
                : null,
                
            'appointment_mgmt' => fn() => class_exists(AppointmentManagementMCPServer::class) 
                ? app()->makeWith(AppointmentManagementMCPServer::class, [
                    'notificationService' => app(NotificationService::class)
                ]) 
                : null,
        ];
        
        Log::info('MCP Gateway factories registered', [
            'available_servers' => array_keys($this->serverFactories),
            'count' => count($this->serverFactories)
        ]);
    }
    
    /**
     * Get a server instance (lazy loading)
     */
    protected function getServer(string $serverName)
    {
        // Check if already instantiated
        if (isset($this->servers[$serverName])) {
            return $this->servers[$serverName];
        }
        
        // Check if factory exists
        if (!isset($this->serverFactories[$serverName])) {
            return null;
        }
        
        // Instantiate server using factory
        try {
            $server = ($this->serverFactories[$serverName])();
            if ($server !== null) {
                $this->servers[$serverName] = $server;
                Log::debug('MCP server instantiated', ['server' => $serverName]);
            }
            return $server;
        } catch (\Exception $e) {
            Log::error('Failed to instantiate MCP server', [
                'server' => $serverName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Process MCP request
     * 
     * @param array $request JSON-RPC 2.0 request
     * @return array JSON-RPC 2.0 response
     */
    public function process(array $request): array
    {
        $requestId = $request['id'] ?? Str::uuid()->toString();
        
        try {
            // Validate request format
            $this->validateRequest($request);
            
            // Extract method and server
            $method = $request['method'];
            [$serverName, $methodName] = $this->parseMethod($method);
            
            // Get the server instance (lazy loading)
            $server = $this->getServer($serverName);
            
            if (!$server) {
                throw new MCPException("Server '{$serverName}' not found or could not be loaded", -32601);
            }
            
            // Check if method exists
            if (!method_exists($server, $methodName)) {
                throw new MCPException("Method '{$method}' not found", -32601);
            }
            
            // Execute method with circuit breaker
            $result = $this->executeWithCircuitBreaker(
                $serverName,
                function () use ($server, $methodName, $request) {
                    return $server->$methodName($request['params'] ?? []);
                }
            );
            
            // Format success response
            return [
                'jsonrpc' => '2.0',
                'result' => $result,
                'id' => $requestId
            ];
            
        } catch (MCPException $e) {
            // Format error response
            return [
                'jsonrpc' => '2.0',
                'error' => [
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                    'data' => $e->getContext()
                ],
                'id' => $requestId
            ];
        } catch (\Exception $e) {
            Log::error('MCP Gateway error', [
                'request' => $request,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'jsonrpc' => '2.0',
                'error' => [
                    'code' => -32603,
                    'message' => 'Internal error',
                    'data' => app()->environment('local') ? $e->getMessage() : null
                ],
                'id' => $requestId
            ];
        }
    }
    
    /**
     * Validate JSON-RPC 2.0 request format
     */
    protected function validateRequest(array $request): void
    {
        if (!isset($request['jsonrpc']) || $request['jsonrpc'] !== '2.0') {
            throw new MCPException('Invalid Request', -32600);
        }
        
        if (!isset($request['method']) || !is_string($request['method'])) {
            throw new MCPException('Invalid Request', -32600);
        }
        
        if (isset($request['params']) && !is_array($request['params'])) {
            throw new MCPException('Invalid params', -32602);
        }
    }
    
    /**
     * Parse method into server and method name
     */
    protected function parseMethod(string $method): array
    {
        $parts = explode('.', $method);
        
        if (count($parts) < 2) {
            throw new MCPException("Invalid method format: {$method}", -32602);
        }
        
        $serverName = $parts[0];
        $methodName = $parts[1];
        
        return [$serverName, $methodName];
    }
    
    /**
     * Execute method with circuit breaker pattern
     */
    protected function executeWithCircuitBreaker(string $serverName, callable $callback)
    {
        if (!$this->config['circuit_breaker']) {
            return $callback();
        }
        
        $cacheKey = "mcp:circuit:{$serverName}";
        $failures = cache()->get($cacheKey, 0);
        
        // Check if circuit is open
        if ($failures >= 5) {
            $lastFailure = cache()->get("{$cacheKey}:timestamp");
            if ($lastFailure && now()->diffInMinutes($lastFailure) < 5) {
                throw new MCPException("Service temporarily unavailable", -32603);
            }
            
            // Reset circuit after cooldown
            cache()->forget($cacheKey);
            cache()->forget("{$cacheKey}:timestamp");
        }
        
        try {
            $result = $callback();
            
            // Reset failures on success
            if ($failures > 0) {
                cache()->forget($cacheKey);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            // Increment failure count
            cache()->increment($cacheKey);
            cache()->put("{$cacheKey}:timestamp", now(), 300);
            
            throw $e;
        }
    }
    
    /**
     * Get server health status
     */
    public function health(): array
    {
        $health = [
            'gateway' => 'healthy',
            'servers' => []
        ];
        
        // Check health of all registered servers
        foreach (array_keys($this->serverFactories) as $serverName) {
            try {
                $server = $this->getServer($serverName);
                if ($server && method_exists($server, 'health')) {
                    $health['servers'][$serverName] = $server->health();
                } else {
                    $health['servers'][$serverName] = ['status' => 'unknown'];
                }
            } catch (\Exception $e) {
                $health['servers'][$serverName] = [
                    'status' => 'unhealthy',
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $health;
    }
    
    /**
     * List available methods
     */
    public function listMethods(): array
    {
        $methods = [];
        
        foreach (array_keys($this->serverFactories) as $serverName) {
            try {
                $server = $this->getServer($serverName);
                if (!$server) {
                    continue;
                }
                
                $reflection = new \ReflectionClass($server);
                
                foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                    if ($method->name === '__construct' || 
                        str_starts_with($method->name, '__') ||
                        in_array($method->name, ['health'])) {
                        continue;
                    }
                    
                    $methods[] = [
                        'method' => "{$serverName}.{$method->name}",
                        'params' => $this->getMethodParams($method),
                        'description' => $this->getMethodDescription($method)
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('Failed to list methods for server', [
                    'server' => $serverName,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $methods;
    }
    
    /**
     * Get method parameters
     */
    protected function getMethodParams(\ReflectionMethod $method): array
    {
        $params = [];
        
        foreach ($method->getParameters() as $param) {
            $params[] = [
                'name' => $param->getName(),
                'type' => $param->getType() ? $param->getType()->getName() : 'mixed',
                'required' => !$param->isOptional()
            ];
        }
        
        return $params;
    }
    
    /**
     * Get method description from docblock
     */
    protected function getMethodDescription(\ReflectionMethod $method): ?string
    {
        $doc = $method->getDocComment();
        
        if (!$doc) {
            return null;
        }
        
        preg_match('/\*\s+(.+)/', $doc, $matches);
        
        return $matches[1] ?? null;
    }
    
    /**
     * Process batch of requests
     * 
     * @param array $requests Array of JSON-RPC 2.0 requests
     * @return array Array of responses
     */
    public function processBatch(array $requests): array
    {
        $responses = [];
        
        foreach ($requests as $request) {
            $responses[] = $this->process($request);
        }
        
        return $responses;
    }
}