<?php

namespace App\Services\MCP;

use App\Services\MCP\Contracts\ExternalMCPProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MemoryBankMCPServer implements ExternalMCPProvider
{
    protected string $name = 'memory_bank';
    protected string $version = '1.0.0';
    protected array $capabilities = [
        'context_retention',
        'memory_storage',
        'session_management',
        'large_project_support',
        'decision_tracking',
        'knowledge_persistence'
    ];

    /**
     * Memory storage path
     */
    protected string $storagePath = 'mcp/memory-bank';

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getCapabilities(): array
    {
        return $this->capabilities;
    }

    /**
     * Get available Memory Bank tools
     */
    public function getTools(): array
    {
        return [
            [
                'name' => 'store_memory',
                'description' => 'Store information in persistent memory',
                'category' => 'memory',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'key' => [
                            'type' => 'string',
                            'description' => 'Unique key for the memory'
                        ],
                        'value' => [
                            'type' => 'object',
                            'description' => 'Information to store (any data structure)'
                        ],
                        'context' => [
                            'type' => 'string',
                            'description' => 'Context or category (e.g., "project", "decisions", "architecture")'
                        ],
                        'tags' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Tags for easier retrieval'
                        ],
                        'ttl' => [
                            'type' => 'integer',
                            'description' => 'Time to live in seconds (optional)'
                        ]
                    ],
                    'required' => ['key', 'value']
                ]
            ],
            [
                'name' => 'retrieve_memory',
                'description' => 'Retrieve information from memory',
                'category' => 'memory',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'key' => [
                            'type' => 'string',
                            'description' => 'Key of the memory to retrieve'
                        ],
                        'context' => [
                            'type' => 'string',
                            'description' => 'Optional context filter'
                        ]
                    ],
                    'required' => ['key']
                ]
            ],
            [
                'name' => 'search_memories',
                'description' => 'Search memories by pattern or context',
                'category' => 'search',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => [
                            'type' => 'string',
                            'description' => 'Search query (supports wildcards)'
                        ],
                        'context' => [
                            'type' => 'string',
                            'description' => 'Optional context filter'
                        ],
                        'tags' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Filter by tags'
                        ],
                        'limit' => [
                            'type' => 'integer',
                            'default' => 10
                        ]
                    ],
                    'required' => ['query']
                ]
            ],
            [
                'name' => 'update_memory',
                'description' => 'Update existing memory',
                'category' => 'memory',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'key' => [
                            'type' => 'string',
                            'description' => 'Key of memory to update'
                        ],
                        'value' => [
                            'type' => 'object',
                            'description' => 'New value (will be merged with existing)'
                        ],
                        'replace' => [
                            'type' => 'boolean',
                            'default' => false,
                            'description' => 'Replace entirely instead of merging'
                        ]
                    ],
                    'required' => ['key', 'value']
                ]
            ],
            [
                'name' => 'list_contexts',
                'description' => 'List all available memory contexts',
                'category' => 'management',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => []
                ]
            ],
            [
                'name' => 'clear_context',
                'description' => 'Clear all memories in a specific context',
                'category' => 'management',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'context' => [
                            'type' => 'string',
                            'description' => 'Context to clear'
                        ],
                        'confirm' => [
                            'type' => 'boolean',
                            'default' => false,
                            'description' => 'Confirm deletion'
                        ]
                    ],
                    'required' => ['context', 'confirm']
                ]
            ],
            [
                'name' => 'get_session_summary',
                'description' => 'Get a summary of the current session memories',
                'category' => 'reporting',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'include_contexts' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Contexts to include in summary'
                        ],
                        'format' => [
                            'type' => 'string',
                            'enum' => ['brief', 'detailed', 'json'],
                            'default' => 'brief'
                        ]
                    ]
                ]
            ],
            [
                'name' => 'export_memories',
                'description' => 'Export memories to file',
                'category' => 'management',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'contexts' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Contexts to export (empty for all)'
                        ],
                        'format' => [
                            'type' => 'string',
                            'enum' => ['json', 'yaml', 'markdown'],
                            'default' => 'json'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Execute a Memory Bank tool
     */
    public function executeTool(string $tool, array $arguments): array
    {
        Log::debug("Executing Memory Bank tool: {$tool}", $arguments);

        try {
            switch ($tool) {
                case 'store_memory':
                    return $this->storeMemory($arguments);
                
                case 'retrieve_memory':
                    return $this->retrieveMemory($arguments);
                
                case 'search_memories':
                    return $this->searchMemories($arguments);
                
                case 'update_memory':
                    return $this->updateMemory($arguments);
                
                case 'list_contexts':
                    return $this->listContexts();
                
                case 'clear_context':
                    return $this->clearContext($arguments);
                
                case 'get_session_summary':
                    return $this->getSessionSummary($arguments);
                
                case 'export_memories':
                    return $this->exportMemories($arguments);
                
                default:
                    return [
                        'success' => false,
                        'error' => "Unknown Memory Bank tool: {$tool}",
                        'data' => null
                    ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Memory Bank operation failed: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Store memory
     */
    protected function storeMemory(array $arguments): array
    {
        $key = $arguments['key'];
        $value = $arguments['value'];
        $context = $arguments['context'] ?? 'default';
        $tags = $arguments['tags'] ?? [];
        $ttl = $arguments['ttl'] ?? null;

        // Create memory structure
        $memory = [
            'key' => $key,
            'value' => $value,
            'context' => $context,
            'tags' => $tags,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
            'accessed_count' => 0,
            'last_accessed' => null
        ];

        // Store in cache with optional TTL
        $cacheKey = $this->getCacheKey($context, $key);
        if ($ttl) {
            Cache::put($cacheKey, $memory, $ttl);
        } else {
            Cache::forever($cacheKey, $memory);
        }

        // Also persist to disk for long-term storage
        $this->persistToDisk($context, $key, $memory);

        // Update context index
        $this->updateContextIndex($context, $key);

        return [
            'success' => true,
            'error' => null,
            'data' => [
                'key' => $key,
                'context' => $context,
                'stored' => true,
                'ttl' => $ttl
            ]
        ];
    }

    /**
     * Retrieve memory
     */
    protected function retrieveMemory(array $arguments): array
    {
        $key = $arguments['key'];
        $context = $arguments['context'] ?? 'default';

        $cacheKey = $this->getCacheKey($context, $key);
        
        // Try cache first
        $memory = Cache::get($cacheKey);
        
        // If not in cache, try disk
        if (!$memory) {
            $memory = $this->loadFromDisk($context, $key);
            if ($memory) {
                // Restore to cache
                Cache::forever($cacheKey, $memory);
            }
        }

        if (!$memory) {
            return [
                'success' => false,
                'error' => "Memory not found: {$key} in context {$context}",
                'data' => null
            ];
        }

        // Update access metrics
        $memory['accessed_count']++;
        $memory['last_accessed'] = now()->toDateTimeString();
        Cache::put($cacheKey, $memory);
        $this->persistToDisk($context, $key, $memory);

        return [
            'success' => true,
            'error' => null,
            'data' => [
                'key' => $key,
                'context' => $context,
                'value' => $memory['value'],
                'metadata' => [
                    'created_at' => $memory['created_at'],
                    'updated_at' => $memory['updated_at'],
                    'accessed_count' => $memory['accessed_count'],
                    'tags' => $memory['tags'] ?? []
                ]
            ]
        ];
    }

    /**
     * Search memories
     */
    protected function searchMemories(array $arguments): array
    {
        $query = $arguments['query'];
        $context = $arguments['context'] ?? null;
        $tags = $arguments['tags'] ?? [];
        $limit = $arguments['limit'] ?? 10;

        $results = [];
        
        // Get contexts to search
        $contextsToSearch = $context ? [$context] : $this->getAllContexts();

        foreach ($contextsToSearch as $ctx) {
            $contextMemories = $this->getContextMemories($ctx);
            
            foreach ($contextMemories as $memory) {
                // Check if matches query
                if ($this->matchesQuery($memory, $query)) {
                    // Check tags if specified
                    if (empty($tags) || !empty(array_intersect($tags, $memory['tags'] ?? []))) {
                        $results[] = [
                            'key' => $memory['key'],
                            'context' => $ctx,
                            'value' => $memory['value'],
                            'tags' => $memory['tags'] ?? [],
                            'relevance' => $this->calculateRelevance($memory, $query)
                        ];
                    }
                }
                
                if (count($results) >= $limit) {
                    break 2;
                }
            }
        }

        // Sort by relevance
        usort($results, function($a, $b) {
            return $b['relevance'] <=> $a['relevance'];
        });

        return [
            'success' => true,
            'error' => null,
            'data' => [
                'query' => $query,
                'results' => array_slice($results, 0, $limit),
                'count' => count($results),
                'contexts_searched' => $contextsToSearch
            ]
        ];
    }

    /**
     * Update memory
     */
    protected function updateMemory(array $arguments): array
    {
        $key = $arguments['key'];
        $value = $arguments['value'];
        $replace = $arguments['replace'] ?? false;
        $context = $arguments['context'] ?? 'default';

        // Retrieve existing memory
        $result = $this->retrieveMemory(['key' => $key, 'context' => $context]);
        
        if (!$result['success']) {
            return [
                'success' => false,
                'error' => "Memory not found: {$key}",
                'data' => null
            ];
        }

        $memory = Cache::get($this->getCacheKey($context, $key));
        
        // Update value
        if ($replace) {
            $memory['value'] = $value;
        } else {
            // Merge if both are arrays
            if (is_array($memory['value']) && is_array($value)) {
                $memory['value'] = array_merge($memory['value'], $value);
            } else {
                $memory['value'] = $value;
            }
        }
        
        $memory['updated_at'] = now()->toDateTimeString();

        // Save updated memory
        $cacheKey = $this->getCacheKey($context, $key);
        Cache::forever($cacheKey, $memory);
        $this->persistToDisk($context, $key, $memory);

        return [
            'success' => true,
            'error' => null,
            'data' => [
                'key' => $key,
                'context' => $context,
                'updated' => true,
                'replace' => $replace
            ]
        ];
    }

    /**
     * List all contexts
     */
    protected function listContexts(): array
    {
        $contexts = $this->getAllContexts();
        $contextStats = [];

        foreach ($contexts as $context) {
            $memories = $this->getContextMemories($context);
            $contextStats[] = [
                'name' => $context,
                'memory_count' => count($memories),
                'total_size' => $this->calculateContextSize($memories),
                'last_updated' => $this->getLastUpdated($memories)
            ];
        }

        return [
            'success' => true,
            'error' => null,
            'data' => [
                'contexts' => $contextStats,
                'total_contexts' => count($contexts)
            ]
        ];
    }

    /**
     * Clear context
     */
    protected function clearContext(array $arguments): array
    {
        $context = $arguments['context'];
        $confirm = $arguments['confirm'] ?? false;

        if (!$confirm) {
            return [
                'success' => false,
                'error' => 'Confirmation required to clear context',
                'data' => null
            ];
        }

        // Get all memories in context
        $memories = $this->getContextMemories($context);
        $count = count($memories);

        // Clear from cache
        foreach ($memories as $memory) {
            Cache::forget($this->getCacheKey($context, $memory['key']));
        }

        // Clear from disk
        $contextPath = storage_path("app/{$this->storagePath}/{$context}");
        if (file_exists($contextPath)) {
            array_map('unlink', glob("$contextPath/*.json"));
            rmdir($contextPath);
        }

        // Clear context index
        Cache::forget("memory_bank:contexts:{$context}");

        return [
            'success' => true,
            'error' => null,
            'data' => [
                'context' => $context,
                'cleared' => true,
                'memories_removed' => $count
            ]
        ];
    }

    /**
     * Get session summary
     */
    protected function getSessionSummary(array $arguments): array
    {
        $includeContexts = $arguments['include_contexts'] ?? [];
        $format = $arguments['format'] ?? 'brief';

        $contexts = empty($includeContexts) ? $this->getAllContexts() : $includeContexts;
        $summary = [];

        foreach ($contexts as $context) {
            $memories = $this->getContextMemories($context);
            
            $contextSummary = [
                'context' => $context,
                'memory_count' => count($memories),
                'recent_memories' => []
            ];

            // Sort by updated_at
            usort($memories, function($a, $b) {
                return strtotime($b['updated_at']) - strtotime($a['updated_at']);
            });

            // Get recent memories based on format
            $recentCount = $format === 'detailed' ? 10 : 3;
            foreach (array_slice($memories, 0, $recentCount) as $memory) {
                $memorySummary = [
                    'key' => $memory['key'],
                    'updated' => $memory['updated_at'],
                    'accessed' => $memory['accessed_count'] ?? 0
                ];

                if ($format === 'detailed' || $format === 'json') {
                    $memorySummary['value'] = $memory['value'];
                    $memorySummary['tags'] = $memory['tags'] ?? [];
                }

                $contextSummary['recent_memories'][] = $memorySummary;
            }

            $summary[] = $contextSummary;
        }

        // Format output based on requested format
        if ($format === 'brief') {
            $output = "Session Summary:\n";
            foreach ($summary as $ctx) {
                $output .= "\n{$ctx['context']}: {$ctx['memory_count']} memories\n";
                foreach ($ctx['recent_memories'] as $mem) {
                    $output .= "  - {$mem['key']} (accessed {$mem['accessed']} times)\n";
                }
            }
            $data = ['summary' => $output];
        } else {
            $data = ['summary' => $summary];
        }

        return [
            'success' => true,
            'error' => null,
            'data' => $data
        ];
    }

    /**
     * Export memories
     */
    protected function exportMemories(array $arguments): array
    {
        $contexts = $arguments['contexts'] ?? [];
        $format = $arguments['format'] ?? 'json';

        $contextsToExport = empty($contexts) ? $this->getAllContexts() : $contexts;
        $exportData = [];

        foreach ($contextsToExport as $context) {
            $memories = $this->getContextMemories($context);
            $exportData[$context] = $memories;
        }

        // Format export based on requested format
        switch ($format) {
            case 'json':
                $output = json_encode($exportData, JSON_PRETTY_PRINT);
                $extension = 'json';
                break;
            
            case 'yaml':
                $output = $this->toYaml($exportData);
                $extension = 'yaml';
                break;
            
            case 'markdown':
                $output = $this->toMarkdown($exportData);
                $extension = 'md';
                break;
            
            default:
                $output = json_encode($exportData);
                $extension = 'json';
        }

        // Save export file
        $filename = 'memory_export_' . now()->format('Y-m-d_His') . '.' . $extension;
        $path = storage_path("app/exports/{$filename}");
        
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        
        file_put_contents($path, $output);

        return [
            'success' => true,
            'error' => null,
            'data' => [
                'filename' => $filename,
                'path' => $path,
                'format' => $format,
                'contexts_exported' => count($contextsToExport),
                'memories_exported' => array_sum(array_map('count', $exportData))
            ]
        ];
    }

    /**
     * Helper: Get cache key
     */
    protected function getCacheKey(string $context, string $key): string
    {
        return "memory_bank:{$context}:{$key}";
    }

    /**
     * Helper: Persist memory to disk
     */
    protected function persistToDisk(string $context, string $key, array $memory): void
    {
        $path = storage_path("app/{$this->storagePath}/{$context}");
        
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        
        $filename = Str::slug($key) . '.json';
        file_put_contents("{$path}/{$filename}", json_encode($memory, JSON_PRETTY_PRINT));
    }

    /**
     * Helper: Load memory from disk
     */
    protected function loadFromDisk(string $context, string $key): ?array
    {
        $filename = Str::slug($key) . '.json';
        $path = storage_path("app/{$this->storagePath}/{$context}/{$filename}");
        
        if (file_exists($path)) {
            return json_decode(file_get_contents($path), true);
        }
        
        return null;
    }

    /**
     * Helper: Update context index
     */
    protected function updateContextIndex(string $context, string $key): void
    {
        $indexKey = "memory_bank:contexts:{$context}";
        $index = Cache::get($indexKey, []);
        
        if (!in_array($key, $index)) {
            $index[] = $key;
            Cache::forever($indexKey, $index);
        }
    }

    /**
     * Helper: Get all contexts
     */
    protected function getAllContexts(): array
    {
        $contexts = [];
        $basePath = storage_path("app/{$this->storagePath}");
        
        if (is_dir($basePath)) {
            $dirs = array_diff(scandir($basePath), ['.', '..']);
            foreach ($dirs as $dir) {
                if (is_dir("{$basePath}/{$dir}")) {
                    $contexts[] = $dir;
                }
            }
        }
        
        return $contexts;
    }

    /**
     * Helper: Get context memories
     */
    protected function getContextMemories(string $context): array
    {
        $memories = [];
        $path = storage_path("app/{$this->storagePath}/{$context}");
        
        if (is_dir($path)) {
            $files = glob("{$path}/*.json");
            foreach ($files as $file) {
                $memory = json_decode(file_get_contents($file), true);
                if ($memory) {
                    $memories[] = $memory;
                }
            }
        }
        
        return $memories;
    }

    /**
     * Helper: Check if memory matches query
     */
    protected function matchesQuery(array $memory, string $query): bool
    {
        $searchIn = json_encode($memory, JSON_UNESCAPED_UNICODE);
        $query = strtolower($query);
        
        // Support wildcards
        $query = str_replace('*', '.*', $query);
        
        return preg_match("/{$query}/i", $searchIn);
    }

    /**
     * Helper: Calculate relevance score
     */
    protected function calculateRelevance(array $memory, string $query): float
    {
        $relevance = 0;
        $query = strtolower($query);
        
        // Check key match
        if (stripos($memory['key'], $query) !== false) {
            $relevance += 10;
        }
        
        // Check value match
        $valueStr = json_encode($memory['value']);
        $matches = substr_count(strtolower($valueStr), $query);
        $relevance += $matches * 2;
        
        // Check tags
        foreach ($memory['tags'] ?? [] as $tag) {
            if (stripos($tag, $query) !== false) {
                $relevance += 5;
            }
        }
        
        // Factor in access count
        $relevance += log(($memory['accessed_count'] ?? 0) + 1);
        
        return $relevance;
    }

    /**
     * Helper: Calculate context size
     */
    protected function calculateContextSize(array $memories): string
    {
        $totalSize = 0;
        
        foreach ($memories as $memory) {
            $totalSize += strlen(json_encode($memory));
        }
        
        // Convert to human readable
        if ($totalSize < 1024) {
            return $totalSize . ' B';
        } elseif ($totalSize < 1048576) {
            return round($totalSize / 1024, 2) . ' KB';
        } else {
            return round($totalSize / 1048576, 2) . ' MB';
        }
    }

    /**
     * Helper: Get last updated time
     */
    protected function getLastUpdated(array $memories): ?string
    {
        if (empty($memories)) {
            return null;
        }
        
        $latest = null;
        foreach ($memories as $memory) {
            if (!$latest || strtotime($memory['updated_at']) > strtotime($latest)) {
                $latest = $memory['updated_at'];
            }
        }
        
        return $latest;
    }

    /**
     * Helper: Convert to YAML format
     */
    protected function toYaml(array $data): string
    {
        // Simple YAML conversion (you could use a library for more complex cases)
        $yaml = '';
        
        foreach ($data as $context => $memories) {
            $yaml .= "{$context}:\n";
            foreach ($memories as $memory) {
                $yaml .= "  - key: {$memory['key']}\n";
                $yaml .= "    value: " . json_encode($memory['value']) . "\n";
                $yaml .= "    tags: [" . implode(', ', $memory['tags'] ?? []) . "]\n";
                $yaml .= "    updated: {$memory['updated_at']}\n\n";
            }
        }
        
        return $yaml;
    }

    /**
     * Helper: Convert to Markdown format
     */
    protected function toMarkdown(array $data): string
    {
        $markdown = "# Memory Bank Export\n\n";
        $markdown .= "Generated: " . now()->toDateTimeString() . "\n\n";
        
        foreach ($data as $context => $memories) {
            $markdown .= "## Context: {$context}\n\n";
            
            foreach ($memories as $memory) {
                $markdown .= "### {$memory['key']}\n\n";
                $markdown .= "- **Updated**: {$memory['updated_at']}\n";
                $markdown .= "- **Accessed**: {$memory['accessed_count']} times\n";
                
                if (!empty($memory['tags'])) {
                    $markdown .= "- **Tags**: " . implode(', ', $memory['tags']) . "\n";
                }
                
                $markdown .= "\n**Value**:\n```json\n";
                $markdown .= json_encode($memory['value'], JSON_PRETTY_PRINT);
                $markdown .= "\n```\n\n---\n\n";
            }
        }
        
        return $markdown;
    }

    /**
     * Check if external server is running
     */
    public function isExternalServerRunning(): bool
    {
        // Memory Bank runs on-demand
        return true;
    }

    /**
     * Start the external server
     */
    public function startExternalServer(): bool
    {
        // Memory Bank runs on-demand
        return true;
    }

    /**
     * Get server configuration
     */
    public function getConfiguration(): array
    {
        return [
            'storage_path' => $this->storagePath,
            'persistent_storage' => true,
            'cache_enabled' => true,
            'export_formats' => ['json', 'yaml', 'markdown']
        ];
    }
}