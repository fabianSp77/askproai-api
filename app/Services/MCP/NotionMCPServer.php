<?php

namespace App\Services\MCP;

use App\Services\MCP\Contracts\ExternalMCPProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class NotionMCPServer implements ExternalMCPProvider
{
    protected string $name = 'notion';
    protected string $version = '1.0.0';
    protected array $capabilities = [
        'document_management',
        'task_management',
        'database_queries',
        'page_creation',
        'content_search',
        'collaboration_sync',
        'project_tracking'
    ];

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
     * Get available Notion tools
     */
    public function getTools(): array
    {
        return [
            [
                'name' => 'search_pages',
                'description' => 'Search for pages in Notion workspace',
                'category' => 'content',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => [
                            'type' => 'string',
                            'description' => 'Search query'
                        ],
                        'filter' => [
                            'type' => 'object',
                            'properties' => [
                                'property' => ['type' => 'string'],
                                'value' => ['type' => 'string']
                            ],
                            'description' => 'Optional filter'
                        ]
                    ],
                    'required' => ['query']
                ]
            ],
            [
                'name' => 'get_page',
                'description' => 'Get content of a specific Notion page',
                'category' => 'content',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'page_id' => [
                            'type' => 'string',
                            'description' => 'Notion page ID'
                        ]
                    ],
                    'required' => ['page_id']
                ]
            ],
            [
                'name' => 'create_page',
                'description' => 'Create a new page in Notion',
                'category' => 'content',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'parent_id' => [
                            'type' => 'string',
                            'description' => 'Parent page or database ID'
                        ],
                        'title' => [
                            'type' => 'string',
                            'description' => 'Page title'
                        ],
                        'content' => [
                            'type' => 'string',
                            'description' => 'Page content (markdown supported)'
                        ],
                        'properties' => [
                            'type' => 'object',
                            'description' => 'Page properties'
                        ]
                    ],
                    'required' => ['parent_id', 'title']
                ]
            ],
            [
                'name' => 'update_page',
                'description' => 'Update an existing Notion page',
                'category' => 'content',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'page_id' => [
                            'type' => 'string',
                            'description' => 'Page ID to update'
                        ],
                        'updates' => [
                            'type' => 'object',
                            'properties' => [
                                'title' => ['type' => 'string'],
                                'content' => ['type' => 'string'],
                                'properties' => ['type' => 'object']
                            ]
                        ]
                    ],
                    'required' => ['page_id', 'updates']
                ]
            ],
            [
                'name' => 'create_task',
                'description' => 'Create a task in Notion task database',
                'category' => 'tasks',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'database_id' => [
                            'type' => 'string',
                            'description' => 'Task database ID'
                        ],
                        'title' => [
                            'type' => 'string',
                            'description' => 'Task title'
                        ],
                        'description' => [
                            'type' => 'string',
                            'description' => 'Task description'
                        ],
                        'status' => [
                            'type' => 'string',
                            'enum' => ['Not Started', 'In Progress', 'Completed'],
                            'default' => 'Not Started'
                        ],
                        'priority' => [
                            'type' => 'string',
                            'enum' => ['Low', 'Medium', 'High'],
                            'default' => 'Medium'
                        ],
                        'assignee' => [
                            'type' => 'string',
                            'description' => 'Assignee email or name'
                        ],
                        'due_date' => [
                            'type' => 'string',
                            'description' => 'Due date (ISO format)'
                        ]
                    ],
                    'required' => ['database_id', 'title']
                ]
            ],
            [
                'name' => 'update_task',
                'description' => 'Update task status or properties',
                'category' => 'tasks',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'task_id' => [
                            'type' => 'string',
                            'description' => 'Task ID to update'
                        ],
                        'updates' => [
                            'type' => 'object',
                            'properties' => [
                                'status' => ['type' => 'string'],
                                'priority' => ['type' => 'string'],
                                'assignee' => ['type' => 'string'],
                                'due_date' => ['type' => 'string']
                            ]
                        ]
                    ],
                    'required' => ['task_id', 'updates']
                ]
            ],
            [
                'name' => 'query_database',
                'description' => 'Query a Notion database with filters',
                'category' => 'database',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'database_id' => [
                            'type' => 'string',
                            'description' => 'Database ID'
                        ],
                        'filter' => [
                            'type' => 'object',
                            'description' => 'Database filter object'
                        ],
                        'sorts' => [
                            'type' => 'array',
                            'description' => 'Sort criteria'
                        ],
                        'limit' => [
                            'type' => 'integer',
                            'default' => 10
                        ]
                    ],
                    'required' => ['database_id']
                ]
            ],
            [
                'name' => 'get_project_requirements',
                'description' => 'Fetch project requirements from Notion',
                'category' => 'project',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'project_name' => [
                            'type' => 'string',
                            'description' => 'Project name or ID'
                        ]
                    ],
                    'required' => ['project_name']
                ]
            ]
        ];
    }

    /**
     * Execute a Notion tool
     */
    public function executeTool(string $tool, array $arguments): array
    {
        Log::debug("Executing Notion tool: {$tool}", $arguments);

        try {
            // Check if we have Notion credentials
            $notionKey = config('services.notion.api_key');
            if (!$notionKey) {
                return [
                    'success' => false,
                    'error' => 'Notion API key not configured. Please set NOTION_API_KEY in .env',
                    'data' => null
                ];
            }

            switch ($tool) {
                case 'search_pages':
                    return $this->searchPages($arguments);
                
                case 'get_page':
                    return $this->getPage($arguments);
                
                case 'create_page':
                    return $this->createPage($arguments);
                
                case 'update_page':
                    return $this->updatePage($arguments);
                
                case 'create_task':
                    return $this->createTask($arguments);
                
                case 'update_task':
                    return $this->updateTask($arguments);
                
                case 'query_database':
                    return $this->queryDatabase($arguments);
                
                case 'get_project_requirements':
                    return $this->getProjectRequirements($arguments);
                
                default:
                    return [
                        'success' => false,
                        'error' => "Unknown Notion tool: {$tool}",
                        'data' => null
                    ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Notion operation failed: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Search for pages in Notion
     */
    protected function searchPages(array $arguments): array
    {
        $query = $arguments['query'];
        $filter = $arguments['filter'] ?? null;

        // Make API call to Notion
        $body = [
            'query' => $query,
            'page_size' => 10
        ];
        
        if ($filter) {
            $body['filter'] = ['property' => $filter['property'], 'value' => $filter['value']];
        }
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.notion.api_key'),
            'Notion-Version' => '2022-06-28',
            'Content-Type' => 'application/json'
        ])->post('https://api.notion.com/v1/search', $body);

        if ($response->successful()) {
            $results = $response->json()['results'] ?? [];
            
            return [
                'success' => true,
                'error' => null,
                'data' => [
                    'pages' => array_map(function($page) {
                        return [
                            'id' => $page['id'],
                            'title' => $this->extractTitle($page),
                            'url' => $page['url'] ?? null,
                            'last_edited' => $page['last_edited_time'] ?? null
                        ];
                    }, $results),
                    'count' => count($results)
                ]
            ];
        }

        return [
            'success' => false,
            'error' => 'Failed to search Notion pages: ' . $response->body(),
            'data' => null
        ];
    }

    /**
     * Get a specific page
     */
    protected function getPage(array $arguments): array
    {
        $pageId = $arguments['page_id'];

        // Cache key
        $cacheKey = "notion:page:{$pageId}";
        
        // Check cache first
        if ($cached = Cache::get($cacheKey)) {
            return [
                'success' => true,
                'error' => null,
                'data' => $cached
            ];
        }

        // Get page metadata
        $pageResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.notion.api_key'),
            'Notion-Version' => '2022-06-28'
        ])->get("https://api.notion.com/v1/pages/{$pageId}");

        if (!$pageResponse->successful()) {
            return [
                'success' => false,
                'error' => 'Failed to get page: ' . $pageResponse->body(),
                'data' => null
            ];
        }

        $page = $pageResponse->json();

        // Get page content blocks
        $blocksResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.notion.api_key'),
            'Notion-Version' => '2022-06-28'
        ])->get("https://api.notion.com/v1/blocks/{$pageId}/children");

        $blocks = $blocksResponse->successful() ? $blocksResponse->json()['results'] ?? [] : [];

        $pageData = [
            'id' => $page['id'],
            'title' => $this->extractTitle($page),
            'properties' => $page['properties'] ?? [],
            'content' => $this->blocksToMarkdown($blocks),
            'url' => $page['url'] ?? null,
            'last_edited' => $page['last_edited_time'] ?? null
        ];

        // Cache for 5 minutes
        Cache::put($cacheKey, $pageData, 300);

        return [
            'success' => true,
            'error' => null,
            'data' => $pageData
        ];
    }

    /**
     * Create a new page
     */
    protected function createPage(array $arguments): array
    {
        $parentId = $arguments['parent_id'];
        $title = $arguments['title'];
        $content = $arguments['content'] ?? '';
        $properties = $arguments['properties'] ?? [];

        // Prepare page data
        $pageData = [
            'parent' => ['page_id' => $parentId],
            'properties' => array_merge([
                'title' => [
                    'title' => [
                        ['text' => ['content' => $title]]
                    ]
                ]
            ], $properties)
        ];

        // Create page
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.notion.api_key'),
            'Notion-Version' => '2022-06-28',
            'Content-Type' => 'application/json'
        ])->post('https://api.notion.com/v1/pages', $pageData);

        if ($response->successful()) {
            $page = $response->json();
            
            // Add content blocks if provided
            if ($content) {
                $this->addContentToPage($page['id'], $content);
            }

            return [
                'success' => true,
                'error' => null,
                'data' => [
                    'page_id' => $page['id'],
                    'url' => $page['url'] ?? null,
                    'title' => $title
                ]
            ];
        }

        return [
            'success' => false,
            'error' => 'Failed to create page: ' . $response->body(),
            'data' => null
        ];
    }

    /**
     * Update an existing page
     */
    protected function updatePage(array $arguments): array
    {
        $pageId = $arguments['page_id'];
        $updates = $arguments['updates'];

        $updateData = ['properties' => []];

        // Update title if provided
        if (isset($updates['title'])) {
            $updateData['properties']['title'] = [
                'title' => [
                    ['text' => ['content' => $updates['title']]]
                ]
            ];
        }

        // Update other properties
        if (isset($updates['properties'])) {
            $updateData['properties'] = array_merge($updateData['properties'], $updates['properties']);
        }

        // Update page properties
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.notion.api_key'),
            'Notion-Version' => '2022-06-28',
            'Content-Type' => 'application/json'
        ])->patch("https://api.notion.com/v1/pages/{$pageId}", $updateData);

        if ($response->successful()) {
            // Clear cache
            Cache::forget("notion:page:{$pageId}");

            return [
                'success' => true,
                'error' => null,
                'data' => [
                    'page_id' => $pageId,
                    'updated' => true
                ]
            ];
        }

        return [
            'success' => false,
            'error' => 'Failed to update page: ' . $response->body(),
            'data' => null
        ];
    }

    /**
     * Create a task in Notion
     */
    protected function createTask(array $arguments): array
    {
        $databaseId = $arguments['database_id'];
        $title = $arguments['title'];
        $description = $arguments['description'] ?? '';
        $status = $arguments['status'] ?? 'Not Started';
        $priority = $arguments['priority'] ?? 'Medium';
        $assignee = $arguments['assignee'] ?? null;
        $dueDate = $arguments['due_date'] ?? null;

        // Build task properties
        $properties = [
            'Name' => [
                'title' => [
                    ['text' => ['content' => $title]]
                ]
            ],
            'Status' => [
                'select' => ['name' => $status]
            ],
            'Priority' => [
                'select' => ['name' => $priority]
            ]
        ];

        if ($description) {
            $properties['Description'] = [
                'rich_text' => [
                    ['text' => ['content' => $description]]
                ]
            ];
        }

        if ($assignee) {
            $properties['Assignee'] = [
                'people' => [
                    ['object' => 'user', 'id' => $assignee]
                ]
            ];
        }

        if ($dueDate) {
            $properties['Due'] = [
                'date' => ['start' => $dueDate]
            ];
        }

        // Create task
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.notion.api_key'),
            'Notion-Version' => '2022-06-28',
            'Content-Type' => 'application/json'
        ])->post('https://api.notion.com/v1/pages', [
            'parent' => ['database_id' => $databaseId],
            'properties' => $properties
        ]);

        if ($response->successful()) {
            $task = $response->json();
            
            return [
                'success' => true,
                'error' => null,
                'data' => [
                    'task_id' => $task['id'],
                    'url' => $task['url'] ?? null,
                    'title' => $title,
                    'status' => $status
                ]
            ];
        }

        return [
            'success' => false,
            'error' => 'Failed to create task: ' . $response->body(),
            'data' => null
        ];
    }

    /**
     * Update task status or properties
     */
    protected function updateTask(array $arguments): array
    {
        $taskId = $arguments['task_id'];
        $updates = $arguments['updates'];

        $properties = [];

        if (isset($updates['status'])) {
            $properties['Status'] = [
                'select' => ['name' => $updates['status']]
            ];
        }

        if (isset($updates['priority'])) {
            $properties['Priority'] = [
                'select' => ['name' => $updates['priority']]
            ];
        }

        if (isset($updates['assignee'])) {
            $properties['Assignee'] = [
                'people' => [
                    ['object' => 'user', 'id' => $updates['assignee']]
                ]
            ];
        }

        if (isset($updates['due_date'])) {
            $properties['Due'] = [
                'date' => ['start' => $updates['due_date']]
            ];
        }

        // Update task
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.notion.api_key'),
            'Notion-Version' => '2022-06-28',
            'Content-Type' => 'application/json'
        ])->patch("https://api.notion.com/v1/pages/{$taskId}", [
            'properties' => $properties
        ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'error' => null,
                'data' => [
                    'task_id' => $taskId,
                    'updated' => true,
                    'updates' => array_keys($updates)
                ]
            ];
        }

        return [
            'success' => false,
            'error' => 'Failed to update task: ' . $response->body(),
            'data' => null
        ];
    }

    /**
     * Query a Notion database
     */
    protected function queryDatabase(array $arguments): array
    {
        $databaseId = $arguments['database_id'];
        $filter = $arguments['filter'] ?? null;
        $sorts = $arguments['sorts'] ?? [];
        $limit = $arguments['limit'] ?? 10;

        $queryData = [
            'page_size' => min($limit, 100)
        ];

        if ($filter) {
            $queryData['filter'] = $filter;
        }

        if (!empty($sorts)) {
            $queryData['sorts'] = $sorts;
        }

        // Query database
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.notion.api_key'),
            'Notion-Version' => '2022-06-28',
            'Content-Type' => 'application/json'
        ])->post("https://api.notion.com/v1/databases/{$databaseId}/query", $queryData);

        if ($response->successful()) {
            $results = $response->json()['results'] ?? [];
            
            return [
                'success' => true,
                'error' => null,
                'data' => [
                    'items' => array_map(function($item) {
                        return [
                            'id' => $item['id'],
                            'properties' => $item['properties'] ?? [],
                            'url' => $item['url'] ?? null,
                            'created_time' => $item['created_time'] ?? null,
                            'last_edited_time' => $item['last_edited_time'] ?? null
                        ];
                    }, $results),
                    'count' => count($results),
                    'has_more' => $response->json()['has_more'] ?? false
                ]
            ];
        }

        return [
            'success' => false,
            'error' => 'Failed to query database: ' . $response->body(),
            'data' => null
        ];
    }

    /**
     * Get project requirements
     */
    protected function getProjectRequirements(array $arguments): array
    {
        $projectName = $arguments['project_name'];

        // Search for project requirements page
        $searchResult = $this->searchPages([
            'query' => "{$projectName} requirements"
        ]);

        if (!$searchResult['success'] || empty($searchResult['data']['pages'])) {
            // Try searching for just the project
            $searchResult = $this->searchPages([
                'query' => $projectName
            ]);
        }

        if ($searchResult['success'] && !empty($searchResult['data']['pages'])) {
            // Get the first matching page
            $pageId = $searchResult['data']['pages'][0]['id'];
            
            // Get full page content
            $pageResult = $this->getPage(['page_id' => $pageId]);
            
            if ($pageResult['success']) {
                return [
                    'success' => true,
                    'error' => null,
                    'data' => [
                        'project' => $projectName,
                        'requirements' => $pageResult['data']
                    ]
                ];
            }
        }

        return [
            'success' => false,
            'error' => "No requirements found for project: {$projectName}",
            'data' => null
        ];
    }

    /**
     * Extract title from Notion page object
     */
    protected function extractTitle($page): string
    {
        if (isset($page['properties']['title']['title'][0]['text']['content'])) {
            return $page['properties']['title']['title'][0]['text']['content'];
        }
        
        if (isset($page['properties']['Name']['title'][0]['text']['content'])) {
            return $page['properties']['Name']['title'][0]['text']['content'];
        }

        // Try to find any title property
        foreach ($page['properties'] as $prop) {
            if (isset($prop['title'][0]['text']['content'])) {
                return $prop['title'][0]['text']['content'];
            }
        }

        return 'Untitled';
    }

    /**
     * Convert Notion blocks to markdown
     */
    protected function blocksToMarkdown(array $blocks): string
    {
        $markdown = '';

        foreach ($blocks as $block) {
            $type = $block['type'];
            
            switch ($type) {
                case 'paragraph':
                    $text = $this->richTextToPlain($block['paragraph']['rich_text'] ?? []);
                    $markdown .= $text . "\n\n";
                    break;
                
                case 'heading_1':
                    $text = $this->richTextToPlain($block['heading_1']['rich_text'] ?? []);
                    $markdown .= "# {$text}\n\n";
                    break;
                
                case 'heading_2':
                    $text = $this->richTextToPlain($block['heading_2']['rich_text'] ?? []);
                    $markdown .= "## {$text}\n\n";
                    break;
                
                case 'heading_3':
                    $text = $this->richTextToPlain($block['heading_3']['rich_text'] ?? []);
                    $markdown .= "### {$text}\n\n";
                    break;
                
                case 'bulleted_list_item':
                    $text = $this->richTextToPlain($block['bulleted_list_item']['rich_text'] ?? []);
                    $markdown .= "- {$text}\n";
                    break;
                
                case 'numbered_list_item':
                    $text = $this->richTextToPlain($block['numbered_list_item']['rich_text'] ?? []);
                    $markdown .= "1. {$text}\n";
                    break;
                
                case 'code':
                    $text = $this->richTextToPlain($block['code']['rich_text'] ?? []);
                    $language = $block['code']['language'] ?? '';
                    $markdown .= "```{$language}\n{$text}\n```\n\n";
                    break;
                
                case 'quote':
                    $text = $this->richTextToPlain($block['quote']['rich_text'] ?? []);
                    $markdown .= "> {$text}\n\n";
                    break;
            }
        }

        return trim($markdown);
    }

    /**
     * Convert rich text to plain text
     */
    protected function richTextToPlain(array $richText): string
    {
        $plain = '';
        
        foreach ($richText as $text) {
            $plain .= $text['plain_text'] ?? '';
        }
        
        return $plain;
    }

    /**
     * Add content blocks to a page
     */
    protected function addContentToPage(string $pageId, string $content): void
    {
        // Convert markdown to Notion blocks
        $blocks = $this->markdownToBlocks($content);

        if (empty($blocks)) {
            return;
        }

        // Add blocks to page
        Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.notion.api_key'),
            'Notion-Version' => '2022-06-28',
            'Content-Type' => 'application/json'
        ])->patch("https://api.notion.com/v1/blocks/{$pageId}/children", [
            'children' => $blocks
        ]);
    }

    /**
     * Convert markdown to Notion blocks
     */
    protected function markdownToBlocks(string $markdown): array
    {
        $blocks = [];
        $lines = explode("\n", $markdown);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (empty($line)) {
                continue;
            }
            
            // Headings
            if (strpos($line, '# ') === 0) {
                $blocks[] = [
                    'object' => 'block',
                    'type' => 'heading_1',
                    'heading_1' => [
                        'rich_text' => [
                            ['type' => 'text', 'text' => ['content' => substr($line, 2)]]
                        ]
                    ]
                ];
            } elseif (strpos($line, '## ') === 0) {
                $blocks[] = [
                    'object' => 'block',
                    'type' => 'heading_2',
                    'heading_2' => [
                        'rich_text' => [
                            ['type' => 'text', 'text' => ['content' => substr($line, 3)]]
                        ]
                    ]
                ];
            } elseif (strpos($line, '- ') === 0) {
                $blocks[] = [
                    'object' => 'block',
                    'type' => 'bulleted_list_item',
                    'bulleted_list_item' => [
                        'rich_text' => [
                            ['type' => 'text', 'text' => ['content' => substr($line, 2)]]
                        ]
                    ]
                ];
            } else {
                $blocks[] = [
                    'object' => 'block',
                    'type' => 'paragraph',
                    'paragraph' => [
                        'rich_text' => [
                            ['type' => 'text', 'text' => ['content' => $line]]
                        ]
                    ]
                ];
            }
        }
        
        return $blocks;
    }

    /**
     * Check if external server is running
     */
    public function isExternalServerRunning(): bool
    {
        // Notion MCP runs via npx on-demand
        return true;
    }

    /**
     * Start the external server
     */
    public function startExternalServer(): bool
    {
        // Notion MCP runs on-demand via npx
        return true;
    }

    /**
     * Get server configuration
     */
    public function getConfiguration(): array
    {
        return [
            'external_server' => '@composio/mcp',
            'uses_npx' => true,
            'oauth_required' => true,
            'api_key_required' => true,
            'workspace_integration' => true
        ];
    }
}