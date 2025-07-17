#!/usr/bin/env php
<?php
/**
 * Test Notion API Connection
 * 
 * This script tests if the Notion API key works
 * and helps find workspaces and pages
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

echo "🔍 Testing Notion API Connection...\n";
echo "==================================\n\n";

$apiKey = env('NOTION_API_KEY');

if (!$apiKey) {
    echo "❌ NOTION_API_KEY not found in .env\n";
    exit(1);
}

echo "✅ API Key found\n\n";

// Test API connection
echo "Testing API connection...\n";

try {
    // Search for pages
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $apiKey,
        'Content-Type' => 'application/json',
        'Notion-Version' => '2022-06-28'
    ])->post('https://api.notion.com/v1/search', [
        'filter' => [
            'property' => 'object',
            'value' => 'page'
        ],
        'sort' => [
            'direction' => 'descending',
            'timestamp' => 'last_edited_time'
        ],
        'page_size' => 10
    ]);

    if ($response->successful()) {
        echo "✅ API Connection successful!\n\n";
        
        $data = $response->json();
        $results = $data['results'] ?? [];
        
        echo "Found " . count($results) . " pages in your workspace:\n\n";
        
        foreach ($results as $index => $page) {
            $title = 'Untitled';
            if (isset($page['properties']['title']['title'][0]['text']['content'])) {
                $title = $page['properties']['title']['title'][0]['text']['content'];
            } elseif (isset($page['properties']['Name']['title'][0]['text']['content'])) {
                $title = $page['properties']['Name']['title'][0]['text']['content'];
            }
            
            echo ($index + 1) . ". " . $title . "\n";
            echo "   ID: " . $page['id'] . "\n";
            echo "   URL: " . ($page['url'] ?? 'N/A') . "\n\n";
        }
        
        echo "\n📝 To use one of these as parent page, add to .env:\n";
        echo "NOTION_PARENT_PAGE_ID=<page-id-from-above>\n\n";
        
        // Try to create a test page
        echo "Would you like to create a test documentation root page? (yes/no): ";
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);
        
        if ($line === 'yes' && count($results) > 0) {
            // Use the first page as parent
            $parentId = $results[0]['id'];
            
            $testResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
                'Notion-Version' => '2022-06-28'
            ])->post('https://api.notion.com/v1/pages', [
                'parent' => ['page_id' => $parentId],
                'properties' => [
                    'title' => [
                        'title' => [
                            ['text' => ['content' => '📚 AskProAI Documentation Hub']]
                        ]
                    ]
                ],
                'children' => [
                    [
                        'type' => 'paragraph',
                        'paragraph' => [
                            'rich_text' => [[
                                'type' => 'text',
                                'text' => ['content' => 'This is the main documentation hub for AskProAI. Documentation will be imported here.']
                            ]]
                        ]
                    ]
                ]
            ]);
            
            if ($testResponse->successful()) {
                $newPage = $testResponse->json();
                echo "\n✅ Created test documentation page!\n";
                echo "Page ID: " . $newPage['id'] . "\n";
                echo "URL: " . $newPage['url'] . "\n\n";
                echo "Add this to your .env:\n";
                echo "NOTION_PARENT_PAGE_ID=" . $newPage['id'] . "\n";
            } else {
                echo "\n❌ Failed to create test page: " . $testResponse->json()['message'] . "\n";
            }
        }
        
    } else {
        echo "❌ API Connection failed!\n";
        echo "Response: " . $response->body() . "\n";
        
        $error = $response->json();
        if (isset($error['message'])) {
            echo "\nError: " . $error['message'] . "\n";
        }
        
        echo "\nPlease check:\n";
        echo "1. API Key is correct\n";
        echo "2. Integration has access to your workspace\n";
        echo "3. Go to Notion → Settings → Integrations → Your Integration → Check workspace access\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}