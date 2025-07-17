# Notion, Memory Bank & Figma MCP Servers Documentation

## Overview

This document covers the installation and integration of three powerful MCP servers that enhance productivity and development workflows:

1. **Notion MCP Server** - Document and task management integration
2. **Memory Bank MCP Server** - Persistent context retention across sessions
3. **Figma MCP Server** - Design-to-code workflow automation

## Installation Summary

All three MCP servers have been successfully installed and integrated into the AskProAI Laravel application.

### Installation Dates
- **Notion MCP Server**: 2025-07-09
- **Memory Bank MCP Server**: 2025-07-09
- **Figma MCP Server**: 2025-07-09

## Notion MCP Server

### Purpose
Connects Claude Code with Notion to enable document retrieval, task management, and project requirement integration directly within the coding workflow.

### Key Features
- **Document Management**: Search, retrieve, and create pages
- **Task Management**: Create and update tasks in Notion databases
- **Project Integration**: Access project requirements and specifications
- **Collaboration**: Sync team workflows with AI-driven updates

### Configuration
```bash
# .env configuration
MCP_NOTION_ENABLED=true
NOTION_API_KEY=your_notion_api_key_here
```

### Available Tools

#### 1. search_pages
Search for pages in your Notion workspace.
```php
$result = $notion->executeTool('search_pages', [
    'query' => 'API documentation',
    'filter' => ['property' => 'status', 'value' => 'published']
]);
```

#### 2. get_page
Retrieve complete page content.
```php
$result = $notion->executeTool('get_page', [
    'page_id' => 'page-uuid-here'
]);
```

#### 3. create_page
Create new documentation or notes.
```php
$result = $notion->executeTool('create_page', [
    'parent_id' => 'parent-page-id',
    'title' => 'Sprint Planning Notes',
    'content' => '## Objectives\n- Complete user authentication\n- Deploy to staging'
]);
```

#### 4. create_task
Add tasks to your project management database.
```php
$result = $notion->executeTool('create_task', [
    'database_id' => 'tasks-database-id',
    'title' => 'Review PR #123',
    'status' => 'In Progress',
    'priority' => 'High',
    'assignee' => 'developer@example.com',
    'due_date' => '2025-07-15'
]);
```

#### 5. update_task
Update task status or properties.
```php
$result = $notion->executeTool('update_task', [
    'task_id' => 'task-uuid',
    'updates' => [
        'status' => 'Completed',
        'priority' => 'Low'
    ]
]);
```

#### 6. query_database
Query Notion databases with filters.
```php
$result = $notion->executeTool('query_database', [
    'database_id' => 'projects-db-id',
    'filter' => [
        'property' => 'Status',
        'select' => ['equals' => 'Active']
    ],
    'sorts' => [
        ['property' => 'Created', 'direction' => 'descending']
    ]
]);
```

#### 7. get_project_requirements
Fetch project requirements by name.
```php
$result = $notion->executeTool('get_project_requirements', [
    'project_name' => 'AskProAI v2'
]);
```

### Use Cases

#### 1. Development Workflow Integration
```php
// During code review
$notion->executeTool('create_task', [
    'database_id' => config('notion.code_review_db'),
    'title' => "Review {$pullRequest->title}",
    'description' => "PR #{$pullRequest->number}: {$pullRequest->description}",
    'status' => 'Pending Review',
    'assignee' => $reviewer->email
]);
```

#### 2. Documentation Sync
```php
// Auto-update API documentation
$notion->executeTool('update_page', [
    'page_id' => config('notion.api_docs_page'),
    'updates' => [
        'content' => $generatedApiDocs
    ]
]);
```

#### 3. Sprint Planning
```php
// Create sprint planning page
$sprintPage = $notion->executeTool('create_page', [
    'parent_id' => config('notion.sprints_page'),
    'title' => "Sprint {$sprintNumber}",
    'content' => $this->generateSprintTemplate($tasks)
]);
```

## Memory Bank MCP Server

### Purpose
Provides persistent memory storage for Claude Code, ensuring context retention across sessions. Essential for managing large codebases and tracking decisions over time.

### Key Features
- **Context Persistence**: Remember previous interactions and decisions
- **Large Project Support**: Maintain coherence across multiple files
- **Decision Tracking**: Record and retrieve architectural decisions
- **Session Management**: Export/import memory states

### Configuration
```bash
# .env configuration
MCP_MEMORY_BANK_ENABLED=true
```

### Available Tools

#### 1. store_memory
Store information with context and tags.
```php
$result = $memoryBank->executeTool('store_memory', [
    'key' => 'current_feature',
    'value' => [
        'feature' => 'user_authentication',
        'branch' => 'feature/auth-system',
        'last_file' => 'app/Http/Controllers/AuthController.php',
        'todos' => ['Add 2FA', 'Write tests']
    ],
    'context' => 'project',
    'tags' => ['auth', 'in-progress'],
    'ttl' => 86400 // 24 hours
]);
```

#### 2. retrieve_memory
Get stored information.
```php
$result = $memoryBank->executeTool('retrieve_memory', [
    'key' => 'architecture_decisions',
    'context' => 'decisions'
]);
```

#### 3. search_memories
Search across all memories.
```php
$result = $memoryBank->executeTool('search_memories', [
    'query' => 'authentication',
    'context' => 'project',
    'tags' => ['security'],
    'limit' => 10
]);
```

#### 4. update_memory
Update existing memories.
```php
$result = $memoryBank->executeTool('update_memory', [
    'key' => 'current_feature',
    'value' => ['todos' => ['Deploy to staging']],
    'replace' => false // Merge with existing
]);
```

#### 5. list_contexts
View all memory contexts.
```php
$result = $memoryBank->executeTool('list_contexts', []);
// Returns: project, decisions, architecture, bugs, etc.
```

#### 6. get_session_summary
Get overview of current session.
```php
$result = $memoryBank->executeTool('get_session_summary', [
    'include_contexts' => ['project', 'decisions'],
    'format' => 'detailed'
]);
```

#### 7. export_memories
Export memories for backup.
```php
$result = $memoryBank->executeTool('export_memories', [
    'contexts' => ['project', 'architecture'],
    'format' => 'json' // or 'yaml', 'markdown'
]);
```

### Use Cases

#### 1. Project Context Management
```php
// Start of session - restore context
$lastSession = $memoryBank->executeTool('retrieve_memory', [
    'key' => 'last_session',
    'context' => 'project'
]);

// Continue where left off
$currentFile = $lastSession['data']['value']['current_file'];
$pendingTasks = $lastSession['data']['value']['pending_tasks'];
```

#### 2. Architecture Decision Records
```php
// Store architectural decision
$memoryBank->executeTool('store_memory', [
    'key' => 'adr_' . date('Y-m-d') . '_repository_pattern',
    'value' => [
        'title' => 'Use Repository Pattern',
        'status' => 'Accepted',
        'context' => 'Need better data access abstraction',
        'decision' => 'Implement repository pattern for all models',
        'consequences' => [
            'positive' => ['Better testability', 'Cleaner code'],
            'negative' => ['More boilerplate']
        ],
        'alternatives' => ['Active Record', 'Query Builder only']
    ],
    'context' => 'architecture',
    'tags' => ['pattern', 'data-access']
]);
```

#### 3. Bug Tracking Context
```php
// Store bug investigation context
$memoryBank->executeTool('store_memory', [
    'key' => 'bug_auth_redirect_loop',
    'value' => [
        'symptoms' => 'Infinite redirect on login',
        'investigated' => [
            'AuthController' => 'No issues found',
            'Middleware' => 'RedirectIfAuthenticated causing loop',
            'Routes' => 'Conflicting route definitions'
        ],
        'solution' => 'Fix route order in web.php',
        'fixed' => false
    ],
    'context' => 'bugs',
    'tags' => ['auth', 'routing', 'in-progress']
]);
```

## Figma MCP Server

### Purpose
Bridges the gap between design and development by enabling direct conversion of Figma designs into production-ready code.

### Key Features
- **Design-to-Code**: Convert Figma designs to HTML/React/Blade
- **Asset Extraction**: Export images and icons
- **Design Tokens**: Extract colors and typography
- **Component Generation**: Create reusable UI components

### Configuration
```bash
# .env configuration
MCP_FIGMA_ENABLED=true
FIGMA_API_TOKEN=your_figma_api_token_here
```

### Available Tools

#### 1. get_file
Get Figma file structure.
```php
$result = $figma->executeTool('get_file', [
    'file_key' => 'ABC123fromURL',
    'include_images' => true
]);
```

#### 2. generate_html
Generate HTML with CSS framework.
```php
$result = $figma->executeTool('generate_html', [
    'file_key' => 'ABC123',
    'node_id' => '1:234',
    'framework' => 'tailwind' // or 'bootstrap', 'html'
]);
```

#### 3. generate_react
Create React components.
```php
$result = $figma->executeTool('generate_react', [
    'file_key' => 'ABC123',
    'node_id' => '1:234',
    'typescript' => true,
    'style_type' => 'tailwind' // or 'styled-components', 'emotion'
]);
```

#### 4. generate_blade
Generate Laravel Blade components.
```php
$result = $figma->executeTool('generate_blade', [
    'file_key' => 'ABC123',
    'node_id' => '1:234',
    'component_name' => 'user-profile-card',
    'use_alpine' => true
]);

// Result includes both Blade template and PHP class
file_put_contents(
    resource_path('views/components/user-profile-card.blade.php'),
    $result['data']['blade']
);

file_put_contents(
    app_path('View/Components/UserProfileCard.php'),
    $result['data']['component_class']
);
```

#### 5. extract_colors
Get color palette.
```php
$result = $figma->executeTool('extract_colors', [
    'file_key' => 'ABC123',
    'format' => 'tailwind' // or 'css', 'scss', 'json'
]);

// Add to tailwind.config.js
file_put_contents(
    base_path('tailwind.colors.js'),
    $result['data']['formatted']
);
```

#### 6. extract_typography
Get text styles.
```php
$result = $figma->executeTool('extract_typography', [
    'file_key' => 'ABC123',
    'format' => 'css'
]);
```

#### 7. export_assets
Export images and icons.
```php
$result = $figma->executeTool('export_assets', [
    'file_key' => 'ABC123',
    'node_ids' => ['1:234', '1:235'],
    'format' => 'svg', // or 'png', 'jpg', 'pdf'
    'scale' => 2
]);
```

### Use Cases

#### 1. Automated Component Library
```php
class FigmaComponentSync extends Command
{
    protected $signature = 'figma:sync-components {file_key}';
    
    public function handle(FigmaMCPServer $figma)
    {
        // Get all components from Figma
        $file = $figma->executeTool('get_file', [
            'file_key' => $this->argument('file_key')
        ]);
        
        // Find component frames
        $components = $this->findComponents($file['data']);
        
        foreach ($components as $component) {
            // Generate Blade component
            $result = $figma->executeTool('generate_blade', [
                'file_key' => $this->argument('file_key'),
                'node_id' => $component['id'],
                'component_name' => Str::kebab($component['name'])
            ]);
            
            // Save files
            $this->saveComponent($result['data']);
            $this->info("Generated: {$component['name']}");
        }
    }
}
```

#### 2. Design System Sync
```php
// Sync design tokens
$colors = $figma->executeTool('extract_colors', [
    'file_key' => config('figma.design_system_key'),
    'format' => 'json'
]);

$typography = $figma->executeTool('extract_typography', [
    'file_key' => config('figma.design_system_key'),
    'format' => 'json'
]);

// Update design tokens file
file_put_contents(
    resource_path('design-tokens.json'),
    json_encode([
        'colors' => $colors['data']['colors'],
        'typography' => $typography['data']['typography'],
        'generated_at' => now()
    ], JSON_PRETTY_PRINT)
);
```

#### 3. Rapid Prototyping
```php
// Quick prototype from Figma
Route::get('/prototype/{node_id}', function ($nodeId) use ($figma) {
    $html = $figma->executeTool('generate_html', [
        'file_key' => config('figma.prototype_file'),
        'node_id' => $nodeId,
        'framework' => 'tailwind'
    ]);
    
    return view('prototype', [
        'content' => $html['data']['html']
    ]);
});
```

## Integration Patterns

### 1. Combined Workflow Example
```php
class FeatureDevelopmentService
{
    public function __construct(
        protected NotionMCPServer $notion,
        protected MemoryBankMCPServer $memory,
        protected FigmaMCPServer $figma
    ) {}
    
    public function startFeature(string $featureName, string $figmaNodeId)
    {
        // 1. Get requirements from Notion
        $requirements = $this->notion->executeTool('get_project_requirements', [
            'project_name' => $featureName
        ]);
        
        // 2. Store in memory bank
        $this->memory->executeTool('store_memory', [
            'key' => "feature_{$featureName}",
            'value' => [
                'requirements' => $requirements['data'],
                'started_at' => now(),
                'figma_node' => $figmaNodeId
            ],
            'context' => 'features',
            'tags' => ['active', $featureName]
        ]);
        
        // 3. Generate initial components from Figma
        $components = $this->figma->executeTool('generate_blade', [
            'file_key' => config('figma.project_file'),
            'node_id' => $figmaNodeId,
            'component_name' => Str::kebab($featureName)
        ]);
        
        // 4. Create development task in Notion
        $task = $this->notion->executeTool('create_task', [
            'database_id' => config('notion.tasks_db'),
            'title' => "Implement {$featureName}",
            'description' => "Generated from Figma design",
            'status' => 'In Progress'
        ]);
        
        return [
            'requirements' => $requirements,
            'components' => $components,
            'task' => $task
        ];
    }
}
```

### 2. Context-Aware Development
```php
// Restore context at session start
$lastContext = $memory->executeTool('retrieve_memory', [
    'key' => 'development_context',
    'context' => 'session'
]);

if ($lastContext['success']) {
    $context = $lastContext['data']['value'];
    
    // Restore Notion page
    $currentDocs = $notion->executeTool('get_page', [
        'page_id' => $context['notion_page_id']
    ]);
    
    // Check Figma for updates
    $designUpdates = $figma->executeTool('get_frame', [
        'file_key' => $context['figma_file'],
        'node_id' => $context['figma_node']
    ]);
}
```

### 3. Automated Documentation
```php
// After implementing feature
$implementation = [
    'files_modified' => $gitChanges,
    'tests_added' => $testFiles,
    'components_created' => $newComponents
];

// Store in memory
$memory->executeTool('store_memory', [
    'key' => 'implementation_' . date('Y-m-d'),
    'value' => $implementation,
    'context' => 'implementations',
    'tags' => ['documented']
]);

// Update Notion documentation
$notion->executeTool('create_page', [
    'parent_id' => config('notion.implementations_page'),
    'title' => "Implementation: {$featureName}",
    'content' => $this->formatImplementationDocs($implementation)
]);
```

## Best Practices

### 1. API Key Management
```php
// Store API keys securely in .env
NOTION_API_KEY=secret_xxx
FIGMA_API_TOKEN=fig_xxx

// Never commit API keys
// Use Laravel's config caching
php artisan config:cache
```

### 2. Error Handling
```php
$result = $notion->executeTool('search_pages', ['query' => 'test']);

if (!$result['success']) {
    Log::error('Notion search failed', [
        'error' => $result['error'],
        'query' => 'test'
    ]);
    
    // Fallback logic
    return $this->getDefaultPages();
}
```

### 3. Caching Strategy
```php
// Figma designs don't change often
$cacheKey = "figma_component_{$nodeId}";
$component = Cache::remember($cacheKey, 3600, function() use ($figma, $nodeId) {
    return $figma->executeTool('generate_blade', [
        'file_key' => config('figma.file_key'),
        'node_id' => $nodeId
    ]);
});
```

### 4. Memory Organization
```php
// Use consistent contexts
$contexts = [
    'project' => 'Current project state',
    'decisions' => 'Architecture decisions',
    'bugs' => 'Bug investigations',
    'features' => 'Feature implementations',
    'learning' => 'Code patterns learned'
];

// Tag consistently
$tags = ['priority:high', 'status:active', 'type:bug'];
```

## Troubleshooting

### Notion Issues
1. **401 Unauthorized**: Check API key
2. **Page not found**: Ensure integration has access
3. **Rate limits**: Implement exponential backoff

### Memory Bank Issues
1. **Storage full**: Export and clear old memories
2. **Search slow**: Limit search scope with contexts
3. **Memory not found**: Check context and key spelling

### Figma Issues
1. **403 Forbidden**: Regenerate API token
2. **Node not found**: Verify file key and node ID
3. **Export timeout**: Reduce number of assets per request

## Testing

Run test scripts to verify installations:
```bash
php test-notion-mcp.php
php test-memory-bank-mcp.php
php test-figma-mcp.php
```

## Summary

These three MCP servers provide a comprehensive productivity enhancement:

- **Notion**: External documentation and task management
- **Memory Bank**: Internal context and decision tracking
- **Figma**: Design-to-code automation

Together, they create a powerful development environment that maintains context, automates repetitive tasks, and bridges the gap between design and implementation.