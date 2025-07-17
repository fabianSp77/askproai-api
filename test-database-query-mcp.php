#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\MCP\DatabaseQueryMCPServer;

try {
    echo "Testing Database Query MCP Server (PostgreSQL MCP adapted for MySQL)...\n";
    echo str_repeat("=", 60) . "\n\n";

    // Initialize Database Query MCP Server
    $db = app(DatabaseQueryMCPServer::class);
    
    // Get server info
    echo "Server Information:\n";
    echo "- Name: " . $db->getName() . "\n";
    echo "- Version: " . $db->getVersion() . "\n";
    echo "- Capabilities: " . implode(', ', $db->getCapabilities()) . "\n";
    echo "- Database Type: MySQL/MariaDB\n\n";

    // List available tools
    echo "Available Database Tools:\n";
    $tools = $db->getTools();
    foreach ($tools as $tool) {
        echo "  - {$tool['name']}: {$tool['description']}\n";
    }
    echo "\n";

    // Test 1: List all tables
    echo "Test 1: Listing all tables in the database...\n";
    $result = $db->executeTool('list_tables', []);

    if ($result['success']) {
        echo "✅ Tables retrieved successfully!\n";
        echo "Total tables: {$result['data']['count']}\n";
        echo "First 10 tables:\n";
        foreach (array_slice($result['data']['tables'], 0, 10) as $table) {
            echo "  - {$table['name']} ({$table['row_count']} rows)\n";
        }
        echo "\n";
    } else {
        echo "❌ Failed: {$result['error']}\n\n";
    }

    // Test 2: Natural language query - simple
    echo "Test 2: Natural language query - 'get all users'...\n";
    $result = $db->executeTool('query_natural', [
        'query' => 'get all users',
        'limit' => 5
    ]);

    if ($result['success']) {
        echo "✅ Query executed successfully!\n";
        echo "Generated SQL: {$result['data']['query']}\n";
        echo "Results: {$result['data']['count']} records\n";
        if ($result['data']['count'] > 0) {
            echo "First user: " . json_encode($result['data']['results'][0]) . "\n";
        }
        echo "\n";
    } else {
        echo "❌ Failed: {$result['error']}\n\n";
    }

    // Test 3: Natural language query - count
    echo "Test 3: Natural language query - 'count companies'...\n";
    $result = $db->executeTool('query_natural', [
        'query' => 'count companies'
    ]);

    if ($result['success']) {
        echo "✅ Query executed successfully!\n";
        echo "Generated SQL: {$result['data']['query']}\n";
        if (!empty($result['data']['results'])) {
            echo "Count: " . $result['data']['results'][0]->count . "\n";
        }
        echo "\n";
    } else {
        echo "❌ Failed: {$result['error']}\n\n";
    }

    // Test 4: Natural language query - with date
    echo "Test 4: Natural language query - 'get appointments created today'...\n";
    $result = $db->executeTool('query_natural', [
        'query' => 'get appointments created today'
    ]);

    if ($result['success']) {
        echo "✅ Query executed successfully!\n";
        echo "Generated SQL: {$result['data']['query']}\n";
        echo "Results: {$result['data']['count']} appointments today\n\n";
    } else {
        echo "❌ Failed: {$result['error']}\n\n";
    }

    // Test 5: Describe a table
    echo "Test 5: Describing 'users' table structure...\n";
    $result = $db->executeTool('describe_table', [
        'table' => 'users'
    ]);

    if ($result['success']) {
        echo "✅ Table structure retrieved!\n";
        echo "Table: {$result['data']['table']}\n";
        echo "Columns: {$result['data']['column_count']}\n";
        echo "First 5 columns:\n";
        foreach (array_slice($result['data']['columns'], 0, 5) as $column) {
            echo "  - {$column['name']} ({$column['type']})\n";
        }
        echo "\n";
    } else {
        echo "❌ Failed: {$result['error']}\n\n";
    }

    // Test 6: Execute raw SQL
    echo "Test 6: Execute raw SQL query...\n";
    $result = $db->executeTool('execute_sql', [
        'sql' => 'SELECT COUNT(*) as total, MAX(created_at) as latest FROM calls WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)'
    ]);

    if ($result['success']) {
        echo "✅ SQL query executed!\n";
        if (!empty($result['data']['results'])) {
            $data = $result['data']['results'][0];
            echo "Calls in last 7 days: {$data->total}\n";
            echo "Latest call: {$data->latest}\n";
        }
        echo "\n";
    } else {
        echo "❌ Failed: {$result['error']}\n\n";
    }

    // Test 7: Get database statistics
    echo "Test 7: Getting database statistics...\n";
    $result = $db->executeTool('get_statistics', []);

    if ($result['success']) {
        echo "✅ Statistics retrieved!\n";
        $stats = $result['data'];
        echo "Database: {$stats['database']}\n";
        echo "Total tables: {$stats['table_count']}\n";
        echo "Total size: {$stats['total_size']->total_size_mb} MB\n";
        echo "Largest tables:\n";
        foreach ($stats['largest_tables'] as $table) {
            echo "  - {$table->table_name}: {$table->size_mb} MB ({$table->table_rows} rows)\n";
        }
        echo "\n";
    } else {
        echo "❌ Failed: {$result['error']}\n\n";
    }

    // Test 8: Natural language - complex query
    echo "Test 8: Natural language query - 'get last 5 calls'...\n";
    $result = $db->executeTool('query_natural', [
        'query' => 'get last 5 calls'
    ]);

    if ($result['success']) {
        echo "✅ Query executed successfully!\n";
        echo "Generated SQL: {$result['data']['query']}\n";
        echo "Results: {$result['data']['count']} calls\n\n";
    } else {
        echo "❌ Failed: {$result['error']}\n\n";
    }

    // Test 9: Analyze query
    echo "Test 9: Analyzing query execution plan...\n";
    $result = $db->executeTool('analyze_query', [
        'sql' => 'SELECT * FROM appointments WHERE customer_id = 123 ORDER BY created_at DESC'
    ]);

    if ($result['success']) {
        echo "✅ Query analyzed!\n";
        if (!empty($result['data']['analysis']['warnings'])) {
            echo "Warnings:\n";
            foreach ($result['data']['analysis']['warnings'] as $warning) {
                echo "  ⚠️  {$warning}\n";
            }
        }
        if (!empty($result['data']['analysis']['suggestions'])) {
            echo "Suggestions:\n";
            foreach ($result['data']['analysis']['suggestions'] as $suggestion) {
                echo "  💡 {$suggestion}\n";
            }
        }
        echo "\n";
    } else {
        echo "❌ Failed: {$result['error']}\n\n";
    }

    echo "✅ Database Query MCP Server is working correctly!\n\n";
    
    echo "💡 Natural Language Query Examples:\n";
    echo "- 'get all customers'\n";
    echo "- 'count appointments'\n";
    echo "- 'show last 10 calls'\n";
    echo "- 'get users created last month'\n";
    echo "- 'sum amount from payments'\n";
    echo "- 'average duration_sec from calls'\n\n";
    
    echo "💡 Usage in your application:\n";
    echo "```php\n";
    echo "\$db = app(DatabaseQueryMCPServer::class);\n";
    echo "\$result = \$db->executeTool('query_natural', [\n";
    echo "    'query' => 'get all active users from last week'\n";
    echo "]);\n";
    echo "```\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}