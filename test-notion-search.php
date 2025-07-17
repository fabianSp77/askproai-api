<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Log;

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $notionMCP = new \App\Services\MCP\NotionMCPServer();
    
    // Search for various pages
    $searchTerms = ['AskProAI', 'Technical', 'Documentation', 'Integrations', 'API', 'Retell'];
    
    foreach ($searchTerms as $term) {
        echo "\n🔍 Searching for: $term\n";
        $searchResult = $notionMCP->executeTool('search_pages', [
            'query' => $term
        ]);
        
        if ($searchResult['success'] && count($searchResult['data']['pages']) > 0) {
            echo "✅ Found " . count($searchResult['data']['pages']) . " pages:\n";
            foreach ($searchResult['data']['pages'] as $page) {
                echo "  - {$page['title']} (ID: {$page['id']})\n";
                if (isset($page['url'])) {
                    echo "    URL: {$page['url']}\n";
                }
            }
        } else {
            echo "❌ No pages found\n";
        }
    }
    
    // Try to get workspace info
    echo "\n📊 Checking workspace...\n";
    $workspaceResult = $notionMCP->executeTool('search_pages', [
        'query' => ''  // Empty query might return all accessible pages
    ]);
    
    if ($workspaceResult['success']) {
        echo "Found " . count($workspaceResult['data']['pages']) . " total accessible pages\n";
    }

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}