#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\MCP\NotionMCPServer;

try {
    echo "Testing Notion MCP Server...\n";
    echo str_repeat("=", 60) . "\n\n";

    // Initialize Notion MCP Server
    $notion = app(NotionMCPServer::class);
    
    // Get server info
    echo "Server Information:\n";
    echo "- Name: " . $notion->getName() . "\n";
    echo "- Version: " . $notion->getVersion() . "\n";
    echo "- Capabilities: " . implode(', ', $notion->getCapabilities()) . "\n\n";

    // List available tools
    echo "Available Notion Tools:\n";
    $tools = $notion->getTools();
    foreach ($tools as $tool) {
        echo "  - {$tool['name']}: {$tool['description']}\n";
    }
    echo "\n";

    // Check API key configuration
    $apiKey = config('services.notion.api_key');
    if (!$apiKey || $apiKey === 'your_notion_api_key_here') {
        echo "⚠️  WARNING: Notion API key not configured!\n";
        echo "Please set NOTION_API_KEY in your .env file\n";
        echo "Get your API key from: https://www.notion.so/my-integrations\n\n";
        echo "Once you have your API key, you can:\n";
        echo "1. Create pages and tasks\n";
        echo "2. Search and retrieve documents\n";
        echo "3. Update task statuses\n";
        echo "4. Query databases\n\n";
    } else {
        echo "✅ Notion API key is configured\n\n";
        
        // Test search functionality
        echo "Test 1: Searching for pages...\n";
        $result = $notion->executeTool('search_pages', [
            'query' => 'project requirements'
        ]);

        if ($result['success']) {
            echo "✅ Search successful!\n";
            echo "Found {$result['data']['count']} pages\n";
            foreach ($result['data']['pages'] as $page) {
                echo "  - {$page['title']} (ID: {$page['id']})\n";
            }
            echo "\n";
        } else {
            echo "❌ Search failed: {$result['error']}\n\n";
        }
    }

    echo "💡 Usage Examples:\n\n";
    
    echo "1. Search for documents:\n";
    echo "```php\n";
    echo "\$result = \$notion->executeTool('search_pages', [\n";
    echo "    'query' => 'API documentation'\n";
    echo "]);\n```\n\n";
    
    echo "2. Create a task:\n";
    echo "```php\n";
    echo "\$result = \$notion->executeTool('create_task', [\n";
    echo "    'database_id' => 'your-task-database-id',\n";
    echo "    'title' => 'Review code changes',\n";
    echo "    'description' => 'Review PR #123',\n";
    echo "    'status' => 'In Progress',\n";
    echo "    'priority' => 'High'\n";
    echo "]);\n```\n\n";
    
    echo "3. Get project requirements:\n";
    echo "```php\n";
    echo "\$result = \$notion->executeTool('get_project_requirements', [\n";
    echo "    'project_name' => 'AskProAI v2'\n";
    echo "]);\n```\n\n";
    
    echo "4. Update task status:\n";
    echo "```php\n";
    echo "\$result = \$notion->executeTool('update_task', [\n";
    echo "    'task_id' => 'task-id-here',\n";
    echo "    'updates' => ['status' => 'Completed']\n";
    echo "]);\n```\n\n";

    echo "📚 Integration with Laravel:\n";
    echo "The Notion MCP Server integrates seamlessly with your Laravel application.\n";
    echo "You can use it in controllers, services, or commands:\n\n";
    echo "```php\n";
    echo "class ProjectController extends Controller\n";
    echo "{\n";
    echo "    protected \$notion;\n";
    echo "    \n";
    echo "    public function __construct(NotionMCPServer \$notion)\n";
    echo "    {\n";
    echo "        \$this->notion = \$notion;\n";
    echo "    }\n";
    echo "    \n";
    echo "    public function getRequirements(\$projectName)\n";
    echo "    {\n";
    echo "        \$result = \$this->notion->executeTool('get_project_requirements', [\n";
    echo "            'project_name' => \$projectName\n";
    echo "        ]);\n";
    echo "        \n";
    echo "        return response()->json(\$result);\n";
    echo "    }\n";
    echo "}\n";
    echo "```\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}