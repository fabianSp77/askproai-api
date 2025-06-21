<?php

namespace App\Services\MCP\Discovery;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class MCPDiscoveryService
{
    protected array $config;
    protected array $knownMCPs = [];
    protected string $catalogPath = 'mcp/catalog.json';
    
    public function __construct()
    {
        $this->config = config('mcp-discovery', [
            'sources' => [
                'anthropic' => [
                    'url' => 'https://api.anthropic.com/mcp/registry',
                    'check_interval' => 3600, // 1 hour
                ],
                'github' => [
                    'org' => 'anthropics',
                    'topic' => 'mcp',
                    'check_interval' => 7200, // 2 hours
                ],
                'npm' => [
                    'scope' => '@anthropic',
                    'keyword' => 'mcp',
                    'check_interval' => 7200,
                ]
            ],
            'evaluation' => [
                'auto_install' => false,
                'test_environment' => 'staging',
                'approval_required' => true
            ],
            'cache_ttl' => 86400 // 24 hours
        ]);
        
        $this->loadCatalog();
    }
    
    /**
     * Discover new MCPs from all sources
     */
    public function discoverNewMCPs(): array
    {
        $discoveries = [];
        
        // Check Anthropic registry
        $discoveries['anthropic'] = $this->checkAnthropicRegistry();
        
        // Check GitHub repositories
        $discoveries['github'] = $this->checkGitHubMCPs();
        
        // Check NPM packages
        $discoveries['npm'] = $this->checkNPMMCPs();
        
        // Check community sources
        $discoveries['community'] = $this->checkCommunityMCPs();
        
        // Merge and deduplicate discoveries
        $allMCPs = $this->mergeDiscoveries($discoveries);
        
        // Evaluate relevance for AskProAI
        $relevantMCPs = $this->evaluateRelevance($allMCPs);
        
        // Update catalog
        $this->updateCatalog($relevantMCPs);
        
        // Notify about new discoveries
        if (!empty($relevantMCPs)) {
            $this->notifyNewDiscoveries($relevantMCPs);
        }
        
        return $relevantMCPs;
    }
    
    /**
     * Check Anthropic's official MCP registry
     */
    protected function checkAnthropicRegistry(): array
    {
        $cacheKey = 'mcp:discovery:anthropic';
        
        return Cache::remember($cacheKey, $this->config['sources']['anthropic']['check_interval'], function () {
            try {
                // Simulate API call (replace with actual API when available)
                $response = Http::timeout(30)
                    ->get($this->config['sources']['anthropic']['url']);
                
                if ($response->successful()) {
                    return $this->parseAnthropicRegistry($response->json());
                }
            } catch (\Exception $e) {
                Log::error('Failed to check Anthropic MCP registry', [
                    'error' => $e->getMessage()
                ]);
            }
            
            // Fallback to known MCPs
            return $this->getKnownAnthropicMCPs();
        });
    }
    
    /**
     * Check GitHub for MCP repositories
     */
    protected function checkGitHubMCPs(): array
    {
        $cacheKey = 'mcp:discovery:github';
        
        return Cache::remember($cacheKey, $this->config['sources']['github']['check_interval'], function () {
            $mcps = [];
            
            try {
                // Search for repositories with MCP topic
                $response = Http::withHeaders([
                    'Accept' => 'application/vnd.github.v3+json',
                ])
                ->get('https://api.github.com/search/repositories', [
                    'q' => 'org:anthropics topic:mcp',
                    'sort' => 'updated',
                    'per_page' => 100
                ]);
                
                if ($response->successful()) {
                    $data = $response->json();
                    
                    foreach ($data['items'] ?? [] as $repo) {
                        $mcps[] = $this->parseGitHubRepo($repo);
                    }
                }
                
                // Also check for community MCPs
                $communityResponse = Http::withHeaders([
                    'Accept' => 'application/vnd.github.v3+json',
                ])
                ->get('https://api.github.com/search/repositories', [
                    'q' => 'mcp-server OR mcp-client in:name,description',
                    'sort' => 'stars',
                    'per_page' => 50
                ]);
                
                if ($communityResponse->successful()) {
                    $data = $communityResponse->json();
                    
                    foreach ($data['items'] ?? [] as $repo) {
                        $mcps[] = $this->parseGitHubRepo($repo);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Failed to check GitHub for MCPs', [
                    'error' => $e->getMessage()
                ]);
            }
            
            return $mcps;
        });
    }
    
    /**
     * Check NPM for MCP packages
     */
    protected function checkNPMMCPs(): array
    {
        $cacheKey = 'mcp:discovery:npm';
        
        return Cache::remember($cacheKey, $this->config['sources']['npm']['check_interval'], function () {
            $mcps = [];
            
            try {
                // Search NPM registry
                $response = Http::get('https://registry.npmjs.org/-/v1/search', [
                    'text' => '@anthropic mcp',
                    'size' => 100
                ]);
                
                if ($response->successful()) {
                    $data = $response->json();
                    
                    foreach ($data['objects'] ?? [] as $package) {
                        $mcps[] = $this->parseNPMPackage($package);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Failed to check NPM for MCPs', [
                    'error' => $e->getMessage()
                ]);
            }
            
            return $mcps;
        });
    }
    
    /**
     * Check community sources for MCPs
     */
    protected function checkCommunityMCPs(): array
    {
        $sources = [
            'https://awesome-mcp.com/api/list',
            'https://mcp-directory.dev/api/packages'
        ];
        
        $mcps = [];
        
        foreach ($sources as $source) {
            try {
                $response = Http::timeout(10)->get($source);
                
                if ($response->successful()) {
                    $mcps = array_merge($mcps, $response->json()['mcps'] ?? []);
                }
            } catch (\Exception $e) {
                // Continue with other sources
            }
        }
        
        return $mcps;
    }
    
    /**
     * Evaluate relevance of MCPs for AskProAI
     */
    protected function evaluateRelevance(array $mcps): array
    {
        $relevantMCPs = [];
        
        // Define relevance criteria for AskProAI
        $relevanceCriteria = [
            'categories' => [
                'calendar', 'scheduling', 'appointment', 'booking',
                'telephony', 'voice', 'ai', 'conversation',
                'crm', 'customer', 'business', 'automation',
                'monitoring', 'analytics', 'performance',
                'database', 'api', 'integration'
            ],
            'keywords' => [
                'laravel', 'php', 'filament', 'mysql',
                'retell', 'calcom', 'webhook', 'multi-tenant',
                'saas', 'german', 'gdpr', 'deployment'
            ],
            'minimum_score' => 0.3
        ];
        
        foreach ($mcps as $mcp) {
            $score = $this->calculateRelevanceScore($mcp, $relevanceCriteria);
            
            if ($score >= $relevanceCriteria['minimum_score']) {
                $mcp['relevance_score'] = $score;
                $mcp['relevance_reasons'] = $this->getRelevanceReasons($mcp, $relevanceCriteria);
                $relevantMCPs[] = $mcp;
            }
        }
        
        // Sort by relevance score
        usort($relevantMCPs, function ($a, $b) {
            return $b['relevance_score'] <=> $a['relevance_score'];
        });
        
        return $relevantMCPs;
    }
    
    /**
     * Calculate relevance score for an MCP
     */
    protected function calculateRelevanceScore(array $mcp, array $criteria): float
    {
        $score = 0;
        $factors = 0;
        
        // Check category match
        $categories = array_map('strtolower', $mcp['categories'] ?? []);
        $categoryMatches = array_intersect($categories, $criteria['categories']);
        if (!empty($categoryMatches)) {
            $score += 0.4 * (count($categoryMatches) / count($criteria['categories']));
            $factors++;
        }
        
        // Check keyword match in name and description
        $text = strtolower($mcp['name'] . ' ' . $mcp['description']);
        $keywordMatches = 0;
        foreach ($criteria['keywords'] as $keyword) {
            if (strpos($text, $keyword) !== false) {
                $keywordMatches++;
            }
        }
        if ($keywordMatches > 0) {
            $score += 0.3 * ($keywordMatches / count($criteria['keywords']));
            $factors++;
        }
        
        // Check popularity (stars, downloads)
        if (isset($mcp['stars']) && $mcp['stars'] > 100) {
            $score += 0.1;
            $factors++;
        }
        
        if (isset($mcp['downloads']) && $mcp['downloads'] > 1000) {
            $score += 0.1;
            $factors++;
        }
        
        // Check maintenance status
        if (isset($mcp['last_updated'])) {
            $lastUpdated = Carbon::parse($mcp['last_updated']);
            if ($lastUpdated->isAfter(now()->subMonths(3))) {
                $score += 0.1;
                $factors++;
            }
        }
        
        return $factors > 0 ? $score / $factors : 0;
    }
    
    /**
     * Get relevance reasons for an MCP
     */
    protected function getRelevanceReasons(array $mcp, array $criteria): array
    {
        $reasons = [];
        
        // Category matches
        $categories = array_map('strtolower', $mcp['categories'] ?? []);
        $categoryMatches = array_intersect($categories, $criteria['categories']);
        if (!empty($categoryMatches)) {
            $reasons[] = 'Matches categories: ' . implode(', ', $categoryMatches);
        }
        
        // Keyword matches
        $text = strtolower($mcp['name'] . ' ' . $mcp['description']);
        $keywordMatches = [];
        foreach ($criteria['keywords'] as $keyword) {
            if (strpos($text, $keyword) !== false) {
                $keywordMatches[] = $keyword;
            }
        }
        if (!empty($keywordMatches)) {
            $reasons[] = 'Contains keywords: ' . implode(', ', $keywordMatches);
        }
        
        // Specific use cases
        if (strpos($text, 'calendar') !== false || strpos($text, 'scheduling') !== false) {
            $reasons[] = 'Could enhance appointment booking functionality';
        }
        
        if (strpos($text, 'voice') !== false || strpos($text, 'telephony') !== false) {
            $reasons[] = 'Could improve phone AI integration';
        }
        
        if (strpos($text, 'monitoring') !== false || strpos($text, 'analytics') !== false) {
            $reasons[] = 'Could provide better system insights';
        }
        
        return $reasons;
    }
    
    /**
     * Update the MCP catalog
     */
    protected function updateCatalog(array $mcps): void
    {
        $catalog = [
            'last_updated' => now()->toIso8601String(),
            'total_discovered' => count($mcps),
            'mcps' => $mcps,
            'statistics' => $this->generateStatistics($mcps)
        ];
        
        Storage::put($this->catalogPath, json_encode($catalog, JSON_PRETTY_PRINT));
        
        // Clear cache
        Cache::forget('mcp:catalog');
    }
    
    /**
     * Generate statistics about discovered MCPs
     */
    protected function generateStatistics(array $mcps): array
    {
        $stats = [
            'by_category' => [],
            'by_source' => [],
            'by_relevance' => [
                'high' => 0,
                'medium' => 0,
                'low' => 0
            ],
            'new_this_week' => 0,
            'trending' => []
        ];
        
        foreach ($mcps as $mcp) {
            // Category stats
            foreach ($mcp['categories'] ?? [] as $category) {
                $stats['by_category'][$category] = ($stats['by_category'][$category] ?? 0) + 1;
            }
            
            // Source stats
            $source = $mcp['source'] ?? 'unknown';
            $stats['by_source'][$source] = ($stats['by_source'][$source] ?? 0) + 1;
            
            // Relevance stats
            $score = $mcp['relevance_score'] ?? 0;
            if ($score >= 0.7) {
                $stats['by_relevance']['high']++;
            } elseif ($score >= 0.4) {
                $stats['by_relevance']['medium']++;
            } else {
                $stats['by_relevance']['low']++;
            }
            
            // New MCPs
            if (isset($mcp['discovered_at'])) {
                $discovered = Carbon::parse($mcp['discovered_at']);
                if ($discovered->isAfter(now()->subWeek())) {
                    $stats['new_this_week']++;
                }
            }
        }
        
        // Sort categories by count
        arsort($stats['by_category']);
        
        return $stats;
    }
    
    /**
     * Notify about new MCP discoveries
     */
    protected function notifyNewDiscoveries(array $mcps): void
    {
        // Log discoveries
        Log::info('New MCPs discovered', [
            'count' => count($mcps),
            'high_relevance' => array_filter($mcps, fn($m) => ($m['relevance_score'] ?? 0) >= 0.7)
        ]);
        
        // Send notification (implement notification channel)
        // This could be email, Slack, or dashboard notification
    }
    
    /**
     * Load existing catalog
     */
    protected function loadCatalog(): void
    {
        if (Storage::exists($this->catalogPath)) {
            $catalog = json_decode(Storage::get($this->catalogPath), true);
            $this->knownMCPs = $catalog['mcps'] ?? [];
        }
    }
    
    /**
     * Get catalog of discovered MCPs
     */
    public function getCatalog(): array
    {
        $cacheTtl = $this->config['cache_ttl'] ?? 86400; // Default to 24 hours
        
        return Cache::remember('mcp:catalog', $cacheTtl, function () {
            if (Storage::exists($this->catalogPath)) {
                return json_decode(Storage::get($this->catalogPath), true);
            }
            
            return [
                'last_updated' => null,
                'total_discovered' => 0,
                'mcps' => [],
                'statistics' => [],
                'high_relevance' => 0,
                'last_check' => 'Never'
            ];
        });
    }
    
    /**
     * Parse Anthropic registry response
     */
    protected function parseAnthropicRegistry(array $data): array
    {
        $mcps = [];
        
        foreach ($data['mcps'] ?? [] as $mcp) {
            $mcps[] = [
                'id' => $mcp['id'],
                'name' => $mcp['name'],
                'description' => $mcp['description'],
                'version' => $mcp['version'],
                'categories' => $mcp['categories'] ?? [],
                'source' => 'anthropic',
                'repository' => $mcp['repository'] ?? null,
                'documentation' => $mcp['docs_url'] ?? null,
                'discovered_at' => now()->toIso8601String()
            ];
        }
        
        return $mcps;
    }
    
    /**
     * Parse GitHub repository
     */
    protected function parseGitHubRepo(array $repo): array
    {
        return [
            'id' => 'github:' . $repo['full_name'],
            'name' => $repo['name'],
            'description' => $repo['description'] ?? '',
            'categories' => $repo['topics'] ?? [],
            'source' => 'github',
            'repository' => $repo['html_url'],
            'stars' => $repo['stargazers_count'],
            'last_updated' => $repo['updated_at'],
            'language' => $repo['language'],
            'discovered_at' => now()->toIso8601String()
        ];
    }
    
    /**
     * Parse NPM package
     */
    protected function parseNPMPackage(array $package): array
    {
        $pkg = $package['package'];
        
        return [
            'id' => 'npm:' . $pkg['name'],
            'name' => $pkg['name'],
            'description' => $pkg['description'] ?? '',
            'version' => $pkg['version'],
            'categories' => $pkg['keywords'] ?? [],
            'source' => 'npm',
            'repository' => $pkg['links']['repository'] ?? null,
            'npm_url' => $pkg['links']['npm'],
            'downloads' => $package['downloads']['monthly'] ?? 0,
            'last_updated' => $pkg['date'],
            'discovered_at' => now()->toIso8601String()
        ];
    }
    
    /**
     * Get known Anthropic MCPs (fallback)
     */
    protected function getKnownAnthropicMCPs(): array
    {
        return [
            [
                'id' => 'anthropic:database-mcp',
                'name' => 'Database MCP',
                'description' => 'Secure database access via MCP protocol',
                'categories' => ['database', 'data-access'],
                'source' => 'anthropic'
            ],
            [
                'id' => 'anthropic:filesystem-mcp',
                'name' => 'Filesystem MCP',
                'description' => 'File system operations via MCP',
                'categories' => ['filesystem', 'storage'],
                'source' => 'anthropic'
            ],
            [
                'id' => 'anthropic:web-mcp',
                'name' => 'Web MCP',
                'description' => 'Web browsing and scraping capabilities',
                'categories' => ['web', 'scraping'],
                'source' => 'anthropic'
            ]
        ];
    }
    
    /**
     * Merge discoveries from multiple sources
     */
    protected function mergeDiscoveries(array $discoveries): array
    {
        $merged = [];
        $seen = [];
        
        foreach ($discoveries as $source => $mcps) {
            foreach ($mcps as $mcp) {
                // Create unique key based on name
                $key = strtolower($mcp['name']);
                
                if (!isset($seen[$key])) {
                    $merged[] = $mcp;
                    $seen[$key] = true;
                }
            }
        }
        
        return $merged;
    }
}