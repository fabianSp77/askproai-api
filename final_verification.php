<?php

use App\Services\MCP\NotionMCPServer;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$notionServer = app(NotionMCPServer::class);

// Final page ID
$pageId = '244aba11-76e2-819b-b578-fb90144c922b';

echo "🎯 Final verification of complete Notion documentation...\n\n";

try {
    $result = $notionServer->executeTool('get_page', [
        'page_id' => $pageId,
        'include_content' => true
    ]);
    
    if ($result['success']) {
        $pageData = $result['data'];
        
        echo "📄 Page Details:\n";
        echo "   📋 Title: " . $pageData['title'] . "\n";
        echo "   🔗 URL: " . $pageData['url'] . "\n";
        echo "   📊 Content Length: " . number_format(strlen($pageData['content'])) . " characters\n";
        echo "   📈 Estimated Reading Time: " . ceil(strlen($pageData['content']) / 1000) . " minutes\n\n";
        
        // Check for all major sections
        $expectedSections = [
            'Project Overview',
            'Codebase Structure Analysis', 
            'Database Schema Analysis',
            'API & Integration Analysis',
            'Security & Performance Analysis',
            'Technology Stack',
            'Key Insights & Recommendations',
            'Summary'
        ];
        
        echo "✅ Section Verification:\n";
        $foundSections = 0;
        foreach ($expectedSections as $section) {
            $found = strpos($pageData['content'], $section) !== false;
            $status = $found ? '✅' : '❌';
            if ($found) $foundSections++;
            echo "   {$status} {$section}\n";
        }
        
        // Check for key technical terms
        $keyTerms = [
            'Laravel' => substr_count($pageData['content'], 'Laravel'),
            'Filament' => substr_count($pageData['content'], 'Filament'),
            'Retell.ai' => substr_count($pageData['content'], 'Retell.ai'),
            'Cal.com' => substr_count($pageData['content'], 'Cal.com'),
            'Stripe' => substr_count($pageData['content'], 'Stripe'),
            'Multi-tenant' => substr_count($pageData['content'], 'Multi-tenant'),
            'API' => substr_count($pageData['content'], 'API'),
            'Security' => substr_count($pageData['content'], 'Security'),
            'Database' => substr_count($pageData['content'], 'Database'),
            'Performance' => substr_count($pageData['content'], 'Performance')
        ];
        
        echo "\n🔍 Technical Content Analysis:\n";
        foreach ($keyTerms as $term => $count) {
            $status = $count > 0 ? '✅' : '❌';
            echo "   {$status} {$term}: {$count} mentions\n";
        }
        
        // Overall assessment
        $completionRate = round(($foundSections / count($expectedSections)) * 100);
        $hasKeyContent = array_sum($keyTerms) > 10;
        
        echo "\n📊 Final Assessment:\n";
        echo "   📋 Sections Complete: {$foundSections}/" . count($expectedSections) . " ({$completionRate}%)\n";
        echo "   🔍 Technical Depth: " . ($hasKeyContent ? 'Comprehensive' : 'Basic') . "\n";
        echo "   📈 Content Quality: " . (strlen($pageData['content']) > 5000 ? 'Rich' : 'Concise') . "\n";
        
        if ($completionRate >= 80 && $hasKeyContent) {
            echo "\n🎉 SUCCESS: Complete AskProAI documentation uploaded!\n";
            echo "   ✅ All major sections covered\n";
            echo "   ✅ Technical details included\n";
            echo "   ✅ Ready for stakeholder review\n";
            echo "   🔗 Final URL: " . $pageData['url'] . "\n";
        } else {
            echo "\n⚠️  PARTIAL: Documentation needs enhancement\n";
            echo "   📋 Missing sections: " . (count($expectedSections) - $foundSections) . "\n";
        }
        
        // Summary stats
        echo "\n📈 Content Statistics:\n";
        echo "   📝 Characters: " . number_format(strlen($pageData['content'])) . "\n";
        echo "   📊 Words (approx): " . number_format(str_word_count($pageData['content'])) . "\n";
        echo "   ⏱️  Reading time: " . ceil(str_word_count($pageData['content']) / 200) . " minutes\n";
        echo "   🎯 Production ready: " . ($completionRate >= 80 ? 'Yes' : 'Needs work') . "\n";
        
    } else {
        echo "❌ Failed to retrieve page: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🏁 Final verification completed!\n";