<?php

namespace App\Services;

use App\Services\MCP\MCPOrchestrator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MCPAutoDiscoveryService
{
    protected MCPOrchestrator $orchestrator;
    protected array $serverCapabilities = [];
    
    public function __construct(MCPOrchestrator $orchestrator)
    {
        $this->orchestrator = $orchestrator;
        $this->loadServerCapabilities();
    }
    
    /**
     * Discover the best MCP server for a given task
     */
    public function discoverBestServer(string $taskDescription): array
    {
        $keywords = $this->extractKeywords($taskDescription);
        $scores = [];
        
        foreach ($this->serverCapabilities as $server => $capabilities) {
            $score = $this->calculateMatchScore($keywords, $capabilities);
            if ($score > 0) {
                $scores[$server] = $score;
            }
        }
        
        arsort($scores);
        
        if (empty($scores)) {
            return [
                'success' => false,
                'error' => 'No suitable MCP server found for this task',
                'task' => $taskDescription
            ];
        }
        
        $bestServer = array_key_first($scores);
        
        return [
            'success' => true,
            'server' => $bestServer,
            'confidence' => round($scores[$bestServer] / 100, 2),
            'alternatives' => array_slice(array_keys($scores), 1, 2),
            'capabilities' => $this->serverCapabilities[$bestServer]['tools'] ?? []
        ];
    }
    
    /**
     * Execute a task using auto-discovered server
     */
    public function executeTask(string $taskDescription, array $parameters = []): array
    {
        $discovery = $this->discoverBestServer($taskDescription);
        
        if (!$discovery['success']) {
            return $discovery;
        }
        
        $server = $discovery['server'];
        $tool = $this->findBestTool($server, $taskDescription);
        
        if (!$tool) {
            return [
                'success' => false,
                'error' => "No suitable tool found in {$server} for this task"
            ];
        }
        
        try {
            $result = $this->orchestrator->execute($server, $tool, $parameters);
            
            // Cache successful discovery
            $this->cacheDiscovery($taskDescription, $server, $tool);
            
            return [
                'success' => true,
                'server' => $server,
                'tool' => $tool,
                'result' => $result,
                'cached' => true
            ];
            
        } catch (\Exception $e) {
            Log::error('MCP auto-discovery execution failed', [
                'task' => $taskDescription,
                'server' => $server,
                'tool' => $tool,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'server' => $server,
                'tool' => $tool
            ];
        }
    }
    
    /**
     * Get recommendations for a task
     */
    public function getRecommendations(string $taskDescription): array
    {
        $keywords = $this->extractKeywords($taskDescription);
        $recommendations = [];
        
        foreach ($this->serverCapabilities as $server => $capabilities) {
            $relevantTools = $this->findRelevantTools($server, $keywords);
            
            if (!empty($relevantTools)) {
                $recommendations[] = [
                    'server' => $server,
                    'confidence' => $this->calculateMatchScore($keywords, $capabilities) / 100,
                    'tools' => $relevantTools,
                    'description' => $capabilities['description'] ?? ''
                ];
            }
        }
        
        usort($recommendations, fn($a, $b) => $b['confidence'] <=> $a['confidence']);
        
        return array_slice($recommendations, 0, 3);
    }
    
    /**
     * Load all server capabilities
     */
    protected function loadServerCapabilities(): void
    {
        $servers = config('mcp-servers.servers', []);
        
        foreach ($servers as $name => $config) {
            if (!$config['enabled']) continue;
            
            try {
                $serverClass = $config['class'];
                if (!class_exists($serverClass)) continue;
                
                $instance = app($serverClass);
                
                $capabilities = [];
                
                if (method_exists($instance, 'getCapabilities')) {
                    $capabilities['capabilities'] = $instance->getCapabilities();
                }
                
                if (method_exists($instance, 'getTools')) {
                    $tools = $instance->getTools();
                    $capabilities['tools'] = $tools;
                    
                    // Extract keywords from tools
                    $capabilities['keywords'] = $this->extractToolKeywords($tools);
                }
                
                $capabilities['description'] = $config['description'] ?? '';
                
                $this->serverCapabilities[$name] = $capabilities;
                
            } catch (\Exception $e) {
                Log::warning("Failed to load capabilities for {$name}", [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    
    /**
     * Extract keywords from task description
     */
    protected function extractKeywords(string $text): array
    {
        $text = strtolower($text);
        
        // Common task keywords
        $patterns = [
            'appointment' => ['appointment', 'booking', 'schedule', 'termin', 'buchen'],
            'customer' => ['customer', 'kunde', 'client', 'patient'],
            'call' => ['call', 'anruf', 'phone', 'telefon'],
            'payment' => ['payment', 'zahlung', 'billing', 'invoice', 'rechnung'],
            'sync' => ['sync', 'synchronize', 'import', 'export'],
            'database' => ['database', 'data', 'query', 'datenbank'],
            'webhook' => ['webhook', 'event', 'trigger'],
            'queue' => ['queue', 'job', 'process', 'background'],
            'github' => ['github', 'issue', 'pull request', 'pr', 'repository'],
            'notion' => ['notion', 'page', 'database', 'workspace'],
            'memory' => ['remember', 'memory', 'context', 'session'],
        ];
        
        $keywords = [];
        
        foreach ($patterns as $key => $words) {
            foreach ($words as $word) {
                if (str_contains($text, $word)) {
                    $keywords[] = $key;
                    break;
                }
            }
        }
        
        // Also extract individual words
        $words = preg_split('/\s+/', $text);
        foreach ($words as $word) {
            if (strlen($word) > 3) {
                $keywords[] = $word;
            }
        }
        
        return array_unique($keywords);
    }
    
    /**
     * Extract keywords from tool definitions
     */
    protected function extractToolKeywords(array $tools): array
    {
        $keywords = [];
        
        foreach ($tools as $tool) {
            // From tool name
            $keywords[] = strtolower($tool['name']);
            
            // From description
            if (isset($tool['description'])) {
                $words = preg_split('/\s+/', strtolower($tool['description']));
                foreach ($words as $word) {
                    if (strlen($word) > 4) {
                        $keywords[] = $word;
                    }
                }
            }
            
            // From category
            if (isset($tool['category'])) {
                $keywords[] = strtolower($tool['category']);
            }
        }
        
        return array_unique($keywords);
    }
    
    /**
     * Calculate match score between keywords and capabilities
     */
    protected function calculateMatchScore(array $keywords, array $capabilities): int
    {
        $score = 0;
        $serverKeywords = $capabilities['keywords'] ?? [];
        
        foreach ($keywords as $keyword) {
            foreach ($serverKeywords as $serverKeyword) {
                if (str_contains($serverKeyword, $keyword) || str_contains($keyword, $serverKeyword)) {
                    $score += 10;
                }
            }
        }
        
        // Bonus for exact capability match
        if (isset($capabilities['capabilities'])) {
            foreach ($keywords as $keyword) {
                foreach ($capabilities['capabilities'] as $capability) {
                    if (str_contains(strtolower($capability), $keyword)) {
                        $score += 20;
                    }
                }
            }
        }
        
        return $score;
    }
    
    /**
     * Find the best tool for a task within a server
     */
    protected function findBestTool(string $server, string $taskDescription): ?string
    {
        $tools = $this->serverCapabilities[$server]['tools'] ?? [];
        $keywords = $this->extractKeywords($taskDescription);
        
        $bestTool = null;
        $bestScore = 0;
        
        foreach ($tools as $tool) {
            $score = 0;
            
            // Check tool name
            foreach ($keywords as $keyword) {
                if (str_contains(strtolower($tool['name']), $keyword)) {
                    $score += 20;
                }
            }
            
            // Check tool description
            if (isset($tool['description'])) {
                foreach ($keywords as $keyword) {
                    if (str_contains(strtolower($tool['description']), $keyword)) {
                        $score += 10;
                    }
                }
            }
            
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestTool = $tool['name'];
            }
        }
        
        return $bestTool;
    }
    
    /**
     * Find relevant tools for keywords
     */
    protected function findRelevantTools(string $server, array $keywords): array
    {
        $tools = $this->serverCapabilities[$server]['tools'] ?? [];
        $relevant = [];
        
        foreach ($tools as $tool) {
            $isRelevant = false;
            
            foreach ($keywords as $keyword) {
                if (str_contains(strtolower($tool['name']), $keyword) ||
                    (isset($tool['description']) && str_contains(strtolower($tool['description']), $keyword))) {
                    $isRelevant = true;
                    break;
                }
            }
            
            if ($isRelevant) {
                $relevant[] = [
                    'name' => $tool['name'],
                    'description' => $tool['description'] ?? ''
                ];
            }
        }
        
        return $relevant;
    }
    
    /**
     * Cache successful discovery
     */
    protected function cacheDiscovery(string $task, string $server, string $tool): void
    {
        $cacheKey = 'mcp_discovery:' . md5(strtolower($task));
        
        Cache::put($cacheKey, [
            'server' => $server,
            'tool' => $tool,
            'discovered_at' => now()
        ], 86400); // 24 hours
    }
    
    /**
     * Get cached discovery if available
     */
    public function getCachedDiscovery(string $task): ?array
    {
        $cacheKey = 'mcp_discovery:' . md5(strtolower($task));
        return Cache::get($cacheKey);
    }
}