<?php

use App\Services\MCP\NotionMCPServer;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$notionServer = app(NotionMCPServer::class);

// Use the newly created page
$pageId = '244aba11-76e2-819b-b578-fb90144c922b';

echo "📝 Manually uploading content to Notion page...\n";

// Read the complete codebase_analysis.md file
$content = file_get_contents('codebase_analysis.md');

try {
    // Use reflection to access the protected method directly
    $reflection = new ReflectionClass($notionServer);
    $method = $reflection->getMethod('addContentToPage');
    $method->setAccessible(true);
    
    echo "🔄 Adding content to page...\n";
    $method->invoke($notionServer, $pageId, $content);
    
    echo "✅ Content upload completed!\n";
    
    // Wait a moment for processing
    sleep(3);
    
    // Verify the upload
    echo "🔍 Verifying upload...\n";
    $verifyResult = $notionServer->executeTool('get_page', [
        'page_id' => $pageId,
        'include_content' => true
    ]);
    
    if ($verifyResult['success']) {
        $verifyData = $verifyResult['data'];
        echo "📊 Content Length: " . strlen($verifyData['content']) . " characters\n";
        
        // Check for sections
        $sections = [
            'Project Overview',
            'Codebase Structure Analysis', 
            'Database Schema Analysis',
            'API & Integration Analysis',
            'Security & Performance Analysis',
            'Environment & Setup Analysis',
            'Technology Stack Breakdown',
            'Visual Architecture Diagram',
            'Key Insights & Recommendations',
            'Summary'
        ];
        
        echo "\n✅ Section Check:\n";
        $foundSections = 0;
        foreach ($sections as $section) {
            $found = strpos($verifyData['content'], $section) !== false;
            if ($found) $foundSections++;
            $status = $found ? '✅' : '❌';
            echo "   {$status} {$section}\n";
        }
        
        echo "\n📈 Final Results:\n";
        echo "   📝 Total Content: " . number_format(strlen($verifyData['content'])) . " characters\n";
        echo "   📊 Sections Found: {$foundSections}/" . count($sections) . "\n";
        echo "   🎯 Completion Rate: " . round(($foundSections / count($sections)) * 100) . "%\n";
        
        if ($foundSections >= 8) {
            echo "   🎉 SUCCESS: Complete documentation uploaded!\n";
            echo "   🔗 Page URL: https://www.notion.so/AskProAI-Complete-Codebase-Analysis-Architecture-Documentation-{$pageId}\n";
        } else {
            echo "   ⚠️  Partial upload - may need retry\n";
        }
        
    } else {
        echo "❌ Failed to verify upload\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n🏁 Manual upload process completed!\n";