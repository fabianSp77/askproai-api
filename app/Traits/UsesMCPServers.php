<?php

namespace App\Traits;

use App\Services\MCP\MCPAutoDiscoveryService;
use App\Services\Analysis\SystemUnderstandingService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Exceptions\MCPException;

/**
 * MCP Service Trait
 * 
 * Provides automatic MCP server integration for any service.
 * Features:
 * - Automatic server discovery based on task
 * - Context7 integration for external API documentation
 * - Comprehensive logging of all MCP interactions
 * - Fallback mechanisms for reliability
 */
trait UsesMCPServers
{
    protected ?MCPAutoDiscoveryService $mcpDiscovery = null;
    protected ?SystemUnderstandingService $systemUnderstanding = null;
    protected array $mcpCallHistory = [];
    protected bool $mcpAutoDiscoveryEnabled = true;
    protected array $mcpPreferences = [];
    
    /**
     * Initialize MCP services
     */
    protected function initializeMCP(): void
    {
        $this->mcpDiscovery = app(MCPAutoDiscoveryService::class);
        $this->systemUnderstanding = app(SystemUnderstandingService::class);
        
        // Auto-analyze this service to understand its purpose
        $this->analyzeSelf();
    }
    
    /**
     * Analyze this service to better select MCP servers
     */
    protected function analyzeSelf(): void
    {
        try {
            $analysis = $this->systemUnderstanding->analyzeComponent(static::class);
            
            // Set preferences based on analysis
            if (!empty($analysis['integration_points'])) {
                foreach ($analysis['integration_points'] as $integration) {
                    $this->mcpPreferences[$integration] = true;
                }
            }
            
            // Log MCP opportunities
            if (!empty($analysis['mcp_opportunities'])) {
                Log::info('MCP opportunities detected for ' . static::class, [
                    'opportunities' => $analysis['mcp_opportunities']
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to analyze service for MCP', [
                'service' => static::class,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Execute a task using automatic MCP server discovery
     * 
     * @param string $task Natural language task description
     * @param array $params Parameters for the task
     * @param array $options Additional options
     * @return mixed Task result
     */
    protected function executeMCPTask(string $task, array $params = [], array $options = []): mixed
    {
        if (!$this->mcpDiscovery) {
            $this->initializeMCP();
        }
        
        $correlationId = $options['correlation_id'] ?? Str::uuid()->toString();
        $startTime = microtime(true);
        
        try {
            // Check if we need external API documentation
            if ($this->needsApiDocumentation($task)) {
                return $this->fetchApiDocumentation($task, $params);
            }
            
            // Discover the best server
            $context = array_merge($this->mcpPreferences, $options['context'] ?? []);
            $discovery = $this->mcpDiscovery->discoverForTask($task, $context);
            
            // Log the discovery
            $this->logMCPCall('discovery', $task, $discovery, $correlationId);
            
            // Execute the task
            $result = $this->mcpDiscovery->executeTask($task, $params, $context);
            
            // Log success
            $duration = (microtime(true) - $startTime) * 1000;
            $this->logMCPCall('execution', $task, [
                'server' => $discovery['server'],
                'duration_ms' => $duration,
                'success' => true
            ], $correlationId);
            
            return $result;
            
        } catch (MCPException $e) {
            // Try fallback servers
            if (!empty($discovery['alternatives'])) {
                return $this->tryFallbackServers($task, $params, $discovery['alternatives'], $correlationId);
            }
            
            throw $e;
            
        } catch (\Exception $e) {
            $this->logMCPCall('error', $task, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], $correlationId);
            
            throw new MCPException('MCP task execution failed: ' . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Check if task needs API documentation
     */
    protected function needsApiDocumentation(string $task): bool
    {
        $apiKeywords = [
            'documentation', 'api docs', 'how to use',
            'laravel', 'filament', 'library', 'package',
            'reference', 'guide', 'tutorial'
        ];
        
        $taskLower = strtolower($task);
        foreach ($apiKeywords as $keyword) {
            if (str_contains($taskLower, $keyword)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Fetch API documentation using Context7
     */
    protected function fetchApiDocumentation(string $query, array $params = []): array
    {
        $this->logMCPCall('context7', $query, ['type' => 'api_documentation'], Str::uuid());
        
        try {
            // Extract library name from query
            $libraryName = $this->extractLibraryName($query);
            
            if ($libraryName) {
                // First resolve library ID
                $resolveResult = $this->mcpDiscovery->executeTask(
                    "Resolve library ID for $libraryName",
                    ['libraryName' => $libraryName],
                    ['preferred_server' => 'context7']
                );
                
                if (isset($resolveResult['result'])) {
                    $libraryId = $resolveResult['result']['library_id'] ?? null;
                    
                    if ($libraryId) {
                        // Get documentation
                        $docsResult = $this->mcpDiscovery->executeTask(
                            "Get documentation for library $libraryId",
                            [
                                'context7CompatibleLibraryID' => $libraryId,
                                'topic' => $params['topic'] ?? null,
                                'tokens' => $params['tokens'] ?? 10000
                            ],
                            ['preferred_server' => 'context7']
                        );
                        
                        return [
                            'source' => 'context7',
                            'library' => $libraryName,
                            'library_id' => $libraryId,
                            'documentation' => $docsResult['result'] ?? null
                        ];
                    }
                }
            }
            
            // Fallback to knowledge base
            return $this->mcpDiscovery->executeTask(
                $query,
                $params,
                ['preferred_server' => 'knowledge']
            );
            
        } catch (\Exception $e) {
            Log::error('Context7 documentation fetch failed', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            
            // Return empty documentation
            return [
                'source' => 'none',
                'error' => $e->getMessage(),
                'documentation' => null
            ];
        }
    }
    
    /**
     * Extract library name from query
     */
    protected function extractLibraryName(string $query): ?string
    {
        // Common patterns
        $patterns = [
            '/(?:documentation|docs|api|reference) (?:for|of|about) (\w+)/i',
            '/(\w+) (?:documentation|docs|api|reference)/i',
            '/how to use (\w+)/i',
            '/(\w+) (?:library|package|framework)/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $query, $matches)) {
                return $matches[1];
            }
        }
        
        // Check for specific libraries
        $libraries = ['laravel', 'filament', 'livewire', 'vue', 'react', 'tailwind'];
        $queryLower = strtolower($query);
        
        foreach ($libraries as $library) {
            if (str_contains($queryLower, $library)) {
                return $library;
            }
        }
        
        return null;
    }
    
    /**
     * Try fallback servers when primary fails
     */
    protected function tryFallbackServers(string $task, array $params, array $alternatives, string $correlationId): mixed
    {
        $lastException = null;
        
        foreach ($alternatives as $server) {
            try {
                $this->logMCPCall('fallback_attempt', $task, [
                    'server' => $server,
                    'reason' => 'primary_failed'
                ], $correlationId);
                
                $result = $this->mcpDiscovery->executeTask(
                    $task,
                    $params,
                    ['preferred_server' => $server]
                );
                
                $this->logMCPCall('fallback_success', $task, [
                    'server' => $server
                ], $correlationId);
                
                return $result;
                
            } catch (\Exception $e) {
                $lastException = $e;
                Log::warning('Fallback server failed', [
                    'server' => $server,
                    'task' => $task,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        throw new MCPException(
            'All MCP servers failed for task: ' . $task,
            0,
            $lastException
        );
    }
    
    /**
     * Direct MCP server call (when you know which server to use)
     * 
     * @param string $server Server name
     * @param string $method Method name
     * @param array $params Parameters
     * @return mixed Result
     */
    protected function callMCPServer(string $server, string $method, array $params = []): mixed
    {
        if (!$this->mcpDiscovery) {
            $this->initializeMCP();
        }
        
        $correlationId = Str::uuid()->toString();
        $fullMethod = "$server.$method";
        
        $this->logMCPCall('direct_call', $fullMethod, [
            'server' => $server,
            'method' => $method
        ], $correlationId);
        
        $request = [
            'jsonrpc' => '2.0',
            'method' => $fullMethod,
            'params' => $params,
            'id' => $correlationId
        ];
        
        $gateway = app(\App\Services\MCP\MCPGateway::class);
        $response = $gateway->process($request);
        
        if (isset($response['error'])) {
            throw new MCPException(
                $response['error']['message'] ?? 'MCP call failed',
                $response['error']['code'] ?? -32603
            );
        }
        
        return $response['result'] ?? null;
    }
    
    /**
     * Log MCP calls for debugging and analysis
     */
    protected function logMCPCall(string $type, string $task, array $data, string $correlationId): void
    {
        $logEntry = [
            'type' => $type,
            'task' => $task,
            'service' => static::class,
            'correlation_id' => $correlationId,
            'timestamp' => now()->toIso8601String(),
            'data' => $data
        ];
        
        // Store in memory for this request
        $this->mcpCallHistory[] = $logEntry;
        
        // Log to file
        Log::channel('mcp')->info("MCP $type", $logEntry);
        
        // Log to database for analysis (if enabled)
        if (config('mcp.log_to_database', false)) {
            \DB::table('mcp_logs')->insert([
                'service' => static::class,
                'type' => $type,
                'task' => $task,
                'correlation_id' => $correlationId,
                'data' => json_encode($data),
                'created_at' => now()
            ]);
        }
    }
    
    /**
     * Get MCP call history for this service instance
     */
    public function getMCPCallHistory(): array
    {
        return $this->mcpCallHistory;
    }
    
    /**
     * Enable/disable automatic MCP discovery
     */
    protected function setMCPAutoDiscovery(bool $enabled): void
    {
        $this->mcpAutoDiscoveryEnabled = $enabled;
    }
    
    /**
     * Set MCP server preferences
     */
    protected function setMCPPreferences(array $preferences): void
    {
        $this->mcpPreferences = array_merge($this->mcpPreferences, $preferences);
    }
    
    /**
     * Helper: Execute database operation via MCP
     */
    protected function mcpDatabase(string $operation, array $params = []): mixed
    {
        return $this->callMCPServer('database', $operation, $params);
    }
    
    /**
     * Helper: Execute calendar operation via MCP
     */
    protected function mcpCalendar(string $operation, array $params = []): mixed
    {
        return $this->callMCPServer('calcom', $operation, $params);
    }
    
    /**
     * Helper: Execute phone/call operation via MCP
     */
    protected function mcpPhone(string $operation, array $params = []): mixed
    {
        return $this->callMCPServer('retell', $operation, $params);
    }
    
    /**
     * Helper: Execute webhook operation via MCP
     */
    protected function mcpWebhook(string $operation, array $params = []): mixed
    {
        return $this->callMCPServer('webhook', $operation, $params);
    }
    
    /**
     * Helper: Get API documentation via Context7
     */
    protected function mcpGetDocs(string $library, string $topic = null): array
    {
        return $this->fetchApiDocumentation(
            "Get documentation for $library" . ($topic ? " about $topic" : ''),
            ['topic' => $topic]
        );
    }
    
    /**
     * Analyze the impact of using MCP for a specific operation
     */
    protected function analyzeMCPImpact(string $operation): array
    {
        if (!$this->systemUnderstanding) {
            $this->initializeMCP();
        }
        
        // Analyze current implementation
        $currentAnalysis = $this->systemUnderstanding->analyzeComponent(static::class);
        
        // Find if operation exists in current methods
        $currentMethod = null;
        foreach ($currentAnalysis['implementation']['methods'] ?? [] as $method => $details) {
            if (stripos($method, $operation) !== false) {
                $currentMethod = $method;
                break;
            }
        }
        
        return [
            'operation' => $operation,
            'current_implementation' => $currentMethod,
            'mcp_available' => $this->mcpDiscovery !== null,
            'recommended_server' => $this->mcpDiscovery->discoverForTask($operation)['server'] ?? null,
            'benefits' => [
                'modularity' => 'Separates concerns into dedicated MCP servers',
                'testability' => 'Easier to mock and test MCP calls',
                'scalability' => 'MCP servers can be scaled independently',
                'maintainability' => 'Changes isolated to MCP server implementation'
            ]
        ];
    }
}