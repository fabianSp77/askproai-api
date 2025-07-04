<?php

namespace App\Services\MCP;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Exceptions\MCPException;

/**
 * MCP Auto-Discovery Service
 * 
 * Automatically discovers and selects the optimal MCP server for any given task.
 * Includes intelligent routing, fallback mechanisms, and Context7 integration.
 */
class MCPAutoDiscoveryService
{
    protected MCPGateway $gateway;
    protected array $serverCapabilities = [];
    protected array $taskPatterns = [];
    protected array $serverPriority = [];
    
    /**
     * Cache duration for server capabilities (5 minutes)
     */
    const CAPABILITY_CACHE_TTL = 300;
    
    /**
     * Cache duration for server health status (1 minute)
     */
    const HEALTH_CACHE_TTL = 60;
    
    public function __construct(MCPGateway $gateway)
    {
        $this->gateway = $gateway;
        $this->initializeTaskPatterns();
        $this->initializeServerPriority();
    }
    
    /**
     * Initialize task patterns for automatic server selection
     */
    protected function initializeTaskPatterns(): void
    {
        $this->taskPatterns = [
            // Calendar and appointment related tasks
            'appointment' => ['calcom', 'appointment_mgmt', 'appointment'],
            'calendar' => ['calcom', 'appointment_mgmt'],
            'booking' => ['calcom', 'appointment_mgmt', 'retell_custom'],
            'availability' => ['calcom', 'appointment_mgmt'],
            
            // Phone and call related tasks
            'call' => ['retell', 'retell_custom', 'webhook'],
            'phone' => ['retell', 'retell_config', 'retell_custom'],
            'agent' => ['retell', 'retell_config'],
            
            // Customer and company management
            'customer' => ['customer', 'database'],
            'company' => ['company', 'database'],
            'branch' => ['branch', 'database'],
            
            // External API documentation (Context7)
            'api_documentation' => ['context7'],
            'library_docs' => ['context7'],
            'external_api' => ['context7'],
            
            // Webhook and integration tasks
            'webhook' => ['webhook', 'queue'],
            'integration' => ['webhook', 'calcom', 'retell', 'stripe'],
            
            // Database operations
            'query' => ['database'],
            'report' => ['database', 'analytics'],
            
            // Communication tasks
            'sms' => ['whatsapp', 'notification'],
            'whatsapp' => ['whatsapp'],
            'notification' => ['notification', 'email'],
            
            // Queue and background jobs
            'job' => ['queue'],
            'async' => ['queue'],
            
            // Payment processing
            'payment' => ['stripe'],
            'invoice' => ['stripe', 'database'],
            
            // Knowledge base
            'knowledge' => ['knowledge'],
            'documentation' => ['knowledge', 'context7'],
            
            // Error tracking
            'error' => ['sentry'],
            'monitoring' => ['sentry', 'metrics']
        ];
    }
    
    /**
     * Initialize server priority (higher number = higher priority)
     */
    protected function initializeServerPriority(): void
    {
        $this->serverPriority = [
            // Core business logic servers (highest priority)
            'appointment_mgmt' => 100,
            'retell_custom' => 95,
            'calcom' => 90,
            'retell' => 85,
            
            // Data management
            'customer' => 80,
            'company' => 75,
            'branch' => 70,
            
            // Integration servers
            'webhook' => 65,
            'retell_config' => 60,
            'stripe' => 55,
            'whatsapp' => 50,
            
            // Support servers
            'database' => 45,
            'queue' => 40,
            'knowledge' => 35,
            'notification' => 30,
            'email' => 25,
            'sentry' => 20,
            
            // External documentation (Context7)
            'context7' => 15,
            
            // Analytics and metrics
            'analytics' => 10,
            'metrics' => 5
        ];
    }
    
    /**
     * Discover the best MCP server for a given task
     * 
     * @param string $taskDescription Natural language description of the task
     * @param array $context Additional context for server selection
     * @return array Server information with methods and capabilities
     */
    public function discoverForTask(string $taskDescription, array $context = []): array
    {
        $startTime = microtime(true);
        $correlationId = Str::uuid()->toString();
        
        Log::info('MCP Auto-Discovery started', [
            'correlation_id' => $correlationId,
            'task' => $taskDescription,
            'context' => $context
        ]);
        
        try {
            // Extract keywords from task description
            $keywords = $this->extractKeywords($taskDescription);
            
            // Check if we need external API documentation
            if ($this->needsExternalDocumentation($taskDescription, $keywords)) {
                return $this->getContext7Server($taskDescription);
            }
            
            // Find matching servers based on keywords
            $candidates = $this->findCandidateServers($keywords);
            
            // Get server health status
            $healthStatus = $this->getServerHealthStatus();
            
            // Filter out unhealthy servers
            $healthyServers = $this->filterHealthyServers($candidates, $healthStatus);
            
            // If no healthy servers found, try fallback options
            if (empty($healthyServers)) {
                $healthyServers = $this->getFallbackServers($candidates);
            }
            
            // Rank servers by priority and capabilities
            $rankedServers = $this->rankServers($healthyServers, $taskDescription, $context);
            
            // Get the best server
            $bestServer = $rankedServers[0] ?? null;
            
            if (!$bestServer) {
                throw new MCPException('No suitable MCP server found for task: ' . $taskDescription);
            }
            
            // Get server capabilities
            $capabilities = $this->getServerCapabilities($bestServer);
            
            // Log the selection
            $duration = (microtime(true) - $startTime) * 1000;
            Log::info('MCP Auto-Discovery completed', [
                'correlation_id' => $correlationId,
                'selected_server' => $bestServer,
                'duration_ms' => $duration,
                'candidates_count' => count($candidates),
                'keywords' => $keywords
            ]);
            
            return [
                'server' => $bestServer,
                'capabilities' => $capabilities,
                'alternatives' => array_slice($rankedServers, 1, 2), // Top 2 alternatives
                'correlation_id' => $correlationId,
                'confidence' => $this->calculateConfidence($bestServer, $keywords)
            ];
            
        } catch (\Exception $e) {
            Log::error('MCP Auto-Discovery failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'task' => $taskDescription
            ]);
            
            // Return database server as ultimate fallback
            return [
                'server' => 'database',
                'capabilities' => $this->getServerCapabilities('database'),
                'alternatives' => [],
                'correlation_id' => $correlationId,
                'confidence' => 0.1,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Check if task needs external API documentation
     */
    protected function needsExternalDocumentation(string $task, array $keywords): bool
    {
        $apiKeywords = [
            'api', 'documentation', 'docs', 'library', 'package',
            'laravel', 'filament', 'php', 'composer', 'npm',
            'integration guide', 'how to use', 'reference'
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
     * Get Context7 server configuration for external documentation
     */
    protected function getContext7Server(string $task): array
    {
        return [
            'server' => 'context7',
            'capabilities' => [
                'methods' => [
                    'resolve_library_id' => 'Find library documentation',
                    'get_library_docs' => 'Retrieve library documentation'
                ],
                'description' => 'External API and library documentation via Context7'
            ],
            'alternatives' => ['knowledge'],
            'correlation_id' => Str::uuid()->toString(),
            'confidence' => 0.95,
            'auto_selected' => true,
            'reason' => 'External API documentation requested'
        ];
    }
    
    /**
     * Extract keywords from task description
     */
    protected function extractKeywords(string $text): array
    {
        $text = strtolower($text);
        $keywords = [];
        
        // Check each task pattern
        foreach ($this->taskPatterns as $keyword => $servers) {
            if (str_contains($text, $keyword)) {
                $keywords[] = $keyword;
            }
        }
        
        // Additional keyword extraction
        $commonKeywords = [
            'create', 'update', 'delete', 'fetch', 'get', 'list',
            'book', 'schedule', 'cancel', 'reschedule',
            'send', 'notify', 'process', 'analyze', 'report',
            'sync', 'import', 'export', 'validate', 'check'
        ];
        
        foreach ($commonKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                $keywords[] = $keyword;
            }
        }
        
        return array_unique($keywords);
    }
    
    /**
     * Find candidate servers based on keywords
     */
    protected function findCandidateServers(array $keywords): array
    {
        $candidates = [];
        
        foreach ($keywords as $keyword) {
            if (isset($this->taskPatterns[$keyword])) {
                $candidates = array_merge($candidates, $this->taskPatterns[$keyword]);
            }
        }
        
        // If no specific candidates found, return general-purpose servers
        if (empty($candidates)) {
            $candidates = ['database', 'queue', 'webhook'];
        }
        
        return array_unique($candidates);
    }
    
    /**
     * Get server health status from cache or fresh check
     */
    protected function getServerHealthStatus(): array
    {
        return Cache::remember('mcp:health:status', self::HEALTH_CACHE_TTL, function () {
            try {
                $health = $this->gateway->health();
                return $health['servers'] ?? [];
            } catch (\Exception $e) {
                Log::error('Failed to get MCP health status', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }
    
    /**
     * Filter out unhealthy servers
     */
    protected function filterHealthyServers(array $candidates, array $healthStatus): array
    {
        return array_filter($candidates, function ($server) use ($healthStatus) {
            if (!isset($healthStatus[$server])) {
                return true; // Assume healthy if no status
            }
            
            $status = $healthStatus[$server]['status'] ?? 'unknown';
            return in_array($status, ['healthy', 'unknown']);
        });
    }
    
    /**
     * Get fallback servers for critical operations
     */
    protected function getFallbackServers(array $originalCandidates): array
    {
        $fallbacks = [
            'appointment' => ['database'],
            'call' => ['webhook', 'database'],
            'customer' => ['database'],
            'webhook' => ['queue', 'database']
        ];
        
        foreach ($originalCandidates as $candidate) {
            if (isset($fallbacks[$candidate])) {
                return $fallbacks[$candidate];
            }
        }
        
        return ['database']; // Ultimate fallback
    }
    
    /**
     * Rank servers by priority and capabilities
     */
    protected function rankServers(array $servers, string $task, array $context): array
    {
        $scored = [];
        
        foreach ($servers as $server) {
            $score = 0;
            
            // Base priority score
            $score += $this->serverPriority[$server] ?? 0;
            
            // Context bonus
            if (isset($context['preferred_server']) && $context['preferred_server'] === $server) {
                $score += 50;
            }
            
            // Recent success bonus
            $recentSuccess = Cache::get("mcp:success:$server", 0);
            $score += min($recentSuccess * 5, 25); // Max 25 points for recent success
            
            // Task-specific bonus
            $taskLower = strtolower($task);
            if (str_contains($taskLower, $server)) {
                $score += 30;
            }
            
            $scored[$server] = $score;
        }
        
        // Sort by score descending
        arsort($scored);
        
        return array_keys($scored);
    }
    
    /**
     * Get server capabilities from cache or fresh check
     */
    protected function getServerCapabilities(string $server): array
    {
        return Cache::remember("mcp:capabilities:$server", self::CAPABILITY_CACHE_TTL, function () use ($server) {
            try {
                $methods = $this->gateway->listMethods();
                $serverMethods = array_filter($methods, function ($method) use ($server) {
                    return str_starts_with($method['method'], "$server.");
                });
                
                return [
                    'methods' => array_column($serverMethods, 'description', 'method'),
                    'total_methods' => count($serverMethods),
                    'available' => !empty($serverMethods)
                ];
            } catch (\Exception $e) {
                Log::warning('Failed to get server capabilities', [
                    'server' => $server,
                    'error' => $e->getMessage()
                ]);
                
                return [
                    'methods' => [],
                    'total_methods' => 0,
                    'available' => false
                ];
            }
        });
    }
    
    /**
     * Calculate confidence score for server selection
     */
    protected function calculateConfidence(string $server, array $keywords): float
    {
        $confidence = 0.5; // Base confidence
        
        // Check if server directly matches any keyword
        foreach ($keywords as $keyword) {
            if (isset($this->taskPatterns[$keyword]) && 
                in_array($server, $this->taskPatterns[$keyword])) {
                $confidence += 0.1;
            }
        }
        
        // Priority-based confidence
        $priority = $this->serverPriority[$server] ?? 0;
        $confidence += ($priority / 100) * 0.3;
        
        // Cap at 0.99
        return min($confidence, 0.99);
    }
    
    /**
     * Execute task with automatic server discovery
     * 
     * @param string $task Task description
     * @param array $params Parameters for the task
     * @param array $context Additional context
     * @return mixed Task execution result
     */
    public function executeTask(string $task, array $params = [], array $context = []): mixed
    {
        $discovery = $this->discoverForTask($task, $context);
        $server = $discovery['server'];
        
        // Try to find the best matching method
        $capabilities = $discovery['capabilities'];
        $method = $this->findBestMethod($task, $capabilities['methods'] ?? []);
        
        if (!$method) {
            throw new MCPException("No suitable method found for task: $task on server: $server");
        }
        
        // Execute via gateway
        $request = [
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
            'id' => $discovery['correlation_id']
        ];
        
        $response = $this->gateway->process($request);
        
        // Track success
        if (!isset($response['error'])) {
            Cache::increment("mcp:success:$server");
        }
        
        return $response;
    }
    
    /**
     * Find the best matching method for a task
     */
    protected function findBestMethod(string $task, array $methods): ?string
    {
        $taskLower = strtolower($task);
        $bestMatch = null;
        $bestScore = 0;
        
        foreach ($methods as $method => $description) {
            $methodLower = strtolower($method);
            $descLower = strtolower($description ?? '');
            
            $score = 0;
            
            // Direct method name match
            if (str_contains($taskLower, $methodLower)) {
                $score += 10;
            }
            
            // Description match
            if ($description && str_contains($taskLower, $descLower)) {
                $score += 5;
            }
            
            // Partial matches
            $taskWords = explode(' ', $taskLower);
            foreach ($taskWords as $word) {
                if (str_contains($methodLower, $word)) {
                    $score += 2;
                }
                if ($description && str_contains($descLower, $word)) {
                    $score += 1;
                }
            }
            
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $method;
            }
        }
        
        return $bestMatch;
    }
    
    /**
     * Get server recommendations for common tasks
     */
    public function getRecommendations(): array
    {
        $recommendations = [];
        
        foreach ($this->taskPatterns as $taskType => $servers) {
            $recommendations[$taskType] = [
                'primary' => $servers[0] ?? 'database',
                'alternatives' => array_slice($servers, 1),
                'description' => $this->getTaskDescription($taskType)
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Get human-readable task description
     */
    protected function getTaskDescription(string $taskType): string
    {
        $descriptions = [
            'appointment' => 'Appointment booking and management',
            'calendar' => 'Calendar operations and availability',
            'booking' => 'Booking creation and updates',
            'call' => 'Phone call handling and processing',
            'phone' => 'Phone number and agent management',
            'customer' => 'Customer data management',
            'webhook' => 'Webhook processing and handling',
            'api_documentation' => 'External API documentation lookup',
            'payment' => 'Payment processing and invoicing',
            'notification' => 'Sending notifications (SMS, WhatsApp, Email)',
            'knowledge' => 'Knowledge base operations'
        ];
        
        return $descriptions[$taskType] ?? ucfirst(str_replace('_', ' ', $taskType));
    }
}