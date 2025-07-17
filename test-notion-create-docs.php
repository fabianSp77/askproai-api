<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Log;

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $notionMCP = new \App\Services\MCP\NotionMCPServer();
    
    // Test search first to find parent page
    echo "🔍 Searching for documentation pages...\n";
    $searchResult = $notionMCP->executeTool('search_pages', [
        'query' => 'Technical Docs'
    ]);
    
    if ($searchResult['success']) {
        echo "✅ Found " . count($searchResult['data']['pages']) . " pages\n";
        foreach ($searchResult['data']['pages'] as $page) {
            echo "  - {$page['title']} (ID: {$page['id']})\n";
        }
    } else {
        echo "❌ Search failed: " . $searchResult['error'] . "\n";
    }
    
    // Create Retell.ai documentation page
    echo "\n📝 Creating Retell.ai documentation...\n";
    
    $parentId = 'root'; // Will create at root level first
    
    $result = $notionMCP->executeTool('create_page', [
        'parent_id' => $parentId,
        'title' => 'Retell.ai Integration Documentation',
        'content' => "# Retell.ai Integration Documentation\n\nComplete documentation for the Retell.ai phone AI integration with AskProAI.\n\n## Status: ✅ Fully Functional (as of 2025-07-02)\n\n### Quick Links\n- [Setup Guide](#setup-guide)\n- [Operations Manual](#operations-manual)\n- [Troubleshooting](#troubleshooting)\n- [API Reference](#api-reference)",
        'properties' => [
            'tags' => [
                'multi_select' => [
                    ['name' => 'Integration'],
                    ['name' => 'Retell.ai'],
                    ['name' => 'Documentation']
                ]
            ]
        ]
    ]);
    
    if ($result['success']) {
        echo "✅ Successfully created documentation page!\n";
        echo "📄 Page ID: " . $result['data']['page_id'] . "\n";
        echo "🔗 URL: " . $result['data']['url'] . "\n";
    } else {
        echo "❌ Failed to create page: " . $result['error'] . "\n";
    }

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}