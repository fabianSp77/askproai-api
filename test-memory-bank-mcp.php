#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\MCP\MemoryBankMCPServer;

try {
    echo "Testing Memory Bank MCP Server...\n";
    echo str_repeat("=", 60) . "\n\n";

    // Initialize Memory Bank MCP Server
    $memoryBank = app(MemoryBankMCPServer::class);
    
    // Get server info
    echo "Server Information:\n";
    echo "- Name: " . $memoryBank->getName() . "\n";
    echo "- Version: " . $memoryBank->getVersion() . "\n";
    echo "- Capabilities: " . implode(', ', $memoryBank->getCapabilities()) . "\n\n";

    // List available tools
    echo "Available Memory Bank Tools:\n";
    $tools = $memoryBank->getTools();
    foreach ($tools as $tool) {
        echo "  - {$tool['name']}: {$tool['description']}\n";
    }
    echo "\n";

    // Test 1: Store memory
    echo "Test 1: Storing a memory...\n";
    $result = $memoryBank->executeTool('store_memory', [
        'key' => 'test_project_info',
        'value' => [
            'project_name' => 'AskProAI',
            'version' => '2.0',
            'last_worked_on' => 'AppointmentResource',
            'important_notes' => [
                'Remember to run migrations',
                'Cal.com API v2 is preferred',
                'Retell webhook structure changed'
            ]
        ],
        'context' => 'project',
        'tags' => ['askproai', 'development', 'notes']
    ]);

    if ($result['success']) {
        echo "✅ Memory stored successfully!\n";
        echo "Key: {$result['data']['key']}\n";
        echo "Context: {$result['data']['context']}\n\n";
    } else {
        echo "❌ Failed to store memory: {$result['error']}\n\n";
    }

    // Test 2: Retrieve memory
    echo "Test 2: Retrieving the stored memory...\n";
    $result = $memoryBank->executeTool('retrieve_memory', [
        'key' => 'test_project_info',
        'context' => 'project'
    ]);

    if ($result['success']) {
        echo "✅ Memory retrieved successfully!\n";
        echo "Value: " . json_encode($result['data']['value'], JSON_PRETTY_PRINT) . "\n";
        echo "Created: {$result['data']['metadata']['created_at']}\n\n";
    } else {
        echo "❌ Failed to retrieve memory: {$result['error']}\n\n";
    }

    // Test 3: Store another memory
    echo "Test 3: Storing decision memory...\n";
    $result = $memoryBank->executeTool('store_memory', [
        'key' => 'architecture_decision_001',
        'value' => [
            'decision' => 'Use Repository Pattern',
            'reason' => 'Better separation of concerns',
            'date' => '2025-07-09',
            'alternatives_considered' => ['Active Record', 'Data Mapper']
        ],
        'context' => 'decisions',
        'tags' => ['architecture', 'patterns']
    ]);

    if ($result['success']) {
        echo "✅ Decision memory stored!\n\n";
    }

    // Test 4: Search memories
    echo "Test 4: Searching memories...\n";
    $result = $memoryBank->executeTool('search_memories', [
        'query' => 'project',
        'limit' => 5
    ]);

    if ($result['success']) {
        echo "✅ Search completed!\n";
        echo "Found {$result['data']['count']} memories\n";
        foreach ($result['data']['results'] as $memory) {
            echo "  - {$memory['key']} (context: {$memory['context']})\n";
        }
        echo "\n";
    } else {
        echo "❌ Search failed: {$result['error']}\n\n";
    }

    // Test 5: List contexts
    echo "Test 5: Listing all contexts...\n";
    $result = $memoryBank->executeTool('list_contexts', []);

    if ($result['success']) {
        echo "✅ Contexts retrieved!\n";
        echo "Total contexts: {$result['data']['total_contexts']}\n";
        foreach ($result['data']['contexts'] as $context) {
            echo "  - {$context['name']}: {$context['memory_count']} memories\n";
        }
        echo "\n";
    } else {
        echo "❌ Failed to list contexts: {$result['error']}\n\n";
    }

    // Test 6: Get session summary
    echo "Test 6: Getting session summary...\n";
    $result = $memoryBank->executeTool('get_session_summary', [
        'format' => 'brief'
    ]);

    if ($result['success']) {
        echo "✅ Session summary:\n";
        echo $result['data']['summary'] . "\n";
    } else {
        echo "❌ Failed to get summary: {$result['error']}\n\n";
    }

    echo "✅ Memory Bank MCP Server is working correctly!\n\n";

    echo "💡 Usage Examples:\n\n";
    
    echo "1. Remember project context:\n";
    echo "```php\n";
    echo "\$memoryBank->executeTool('store_memory', [\n";
    echo "    'key' => 'current_task',\n";
    echo "    'value' => ['working_on' => 'UserController', 'branch' => 'feature/auth'],\n";
    echo "    'context' => 'project'\n";
    echo "]);\n```\n\n";
    
    echo "2. Track decisions:\n";
    echo "```php\n";
    echo "\$memoryBank->executeTool('store_memory', [\n";
    echo "    'key' => 'api_version_decision',\n";
    echo "    'value' => ['chosen' => 'v2', 'reason' => 'Better performance'],\n";
    echo "    'context' => 'decisions',\n";
    echo "    'tags' => ['api', 'architecture']\n";
    echo "]);\n```\n\n";
    
    echo "3. Continue where you left off:\n";
    echo "```php\n";
    echo "\$lastTask = \$memoryBank->executeTool('retrieve_memory', [\n";
    echo "    'key' => 'last_task',\n";
    echo "    'context' => 'project'\n";
    echo "]);\n```\n\n";

    echo "📚 Integration Tips:\n";
    echo "- Use contexts to organize memories (project, decisions, architecture, etc.)\n";
    echo "- Tag memories for easier searching\n";
    echo "- Export memories before major changes\n";
    echo "- Set TTL for temporary memories\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}