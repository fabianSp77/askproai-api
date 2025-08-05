<?php

use App\Services\MCP\NotionMCPServer;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$notionServer = app(NotionMCPServer::class);

// Parent page ID for documentation
$parentPageId = '205aba11-76e2-8052-a79d-c0feb2093cad';

echo "🚀 Creating complete AskProAI Codebase Analysis page...\n";

// Read the complete codebase_analysis.md file
$content = file_get_contents('codebase_analysis.md');

try {
    $result = $notionServer->executeTool('create_page', [
        'parent_id' => $parentPageId,
        'title' => '📋 AskProAI - Complete Codebase Analysis & Architecture Documentation',
        'content' => $content
    ]);
    
    if ($result['success']) {
        $pageData = $result['data'];
        
        echo "✅ Successfully created complete codebase analysis page!\n";
        echo "📄 Page Title: " . $pageData['title'] . "\n";
        echo "🔗 Page URL: " . $pageData['url'] . "\n";
        echo "📋 Page ID: " . $pageData['page_id'] . "\n";
        
        // Wait a moment for content to be processed
        sleep(2);
        
        // Verify the content was added
        echo "\n🔍 Verifying content upload...\n";
        $verifyResult = $notionServer->executeTool('get_page', [
            'page_id' => $pageData['page_id'],
            'include_content' => true
        ]);
        
        if ($verifyResult['success']) {
            $verifyData = $verifyResult['data'];
            echo "📊 Content Length: " . strlen($verifyData['content']) . " characters\n";
            
            // Check for all expected sections
            $expectedSections = [
                '# 1. Project Overview',
                '## 2. Codebase Structure Analysis', 
                '## 3. Database Schema Analysis',
                '## 4. API & Integration Analysis',
                '## 5. Security & Performance Analysis',
                '## 6. Environment & Setup Analysis',
                '## 7. Technology Stack Breakdown',
                '## 8. Visual Architecture Diagram',
                '## 9. Key Insights & Recommendations'
            ];
            
            echo "\n✅ Section Verification:\n";
            $foundSections = 0;
            foreach ($expectedSections as $section) {
                $found = strpos($verifyData['content'], $section) !== false;
                $status = $found ? '✅' : '❌';
                if ($found) $foundSections++;
                echo "   {$status} {$section}\n";
            }
            
            echo "\n📊 Upload Summary:\n";
            echo "   📝 Sections Found: {$foundSections}/" . count($expectedSections) . "\n";
            echo "   🎯 Completion: " . round(($foundSections / count($expectedSections)) * 100) . "%\n";
            
            if ($foundSections === count($expectedSections)) {
                echo "   🎉 Complete documentation successfully uploaded!\n";
            } else {
                echo "   ⚠️  Some sections may need manual verification\n";
            }
        }
        
    } else {
        echo "❌ Failed to create page: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🏁 Process completed!\n";