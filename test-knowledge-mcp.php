<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\MCP\KnowledgeMCPServer;
use App\Models\Company;
use App\Models\KnowledgeCategory;
use Illuminate\Support\Facades\DB;

// Initialize the MCP Server
$mcp = new KnowledgeMCPServer();

// Test company ID - you might need to adjust this
$testCompanyId = 1;

// Make sure we have a test company
$company = Company::find($testCompanyId);
if (!$company) {
    echo "No company found with ID $testCompanyId. Creating test company...\n";
    $company = Company::create([
        'name' => 'Test Medical Practice',
        'industry' => 'medical',
        'email' => 'test@medical.de',
        'phone' => '+49 30 12345678',
        'is_active' => true,
    ]);
    $testCompanyId = $company->id;
}

echo "Using company: {$company->name} (ID: {$company->id})\n\n";

// Test 1: Create from template
echo "Test 1: Creating documents from medical industry template\n";
echo str_repeat('-', 50) . "\n";

$result = $mcp->createFromTemplate([
    'company_id' => $testCompanyId,
    'industry' => 'medical',
    'custom_data' => [
        'title' => 'Terminvereinbarung - ' . $company->name,
        'category_id' => null // We'll create a category later
    ]
]);

if (isset($result['success'])) {
    echo "✓ Document created: {$result['document']['title']}\n";
    echo "  ID: {$result['document']['id']}\n";
    echo "  Status: {$result['document']['status']}\n";
} else {
    echo "✗ Error: {$result['error']}\n";
}

// Test 2: Get company knowledge
echo "\nTest 2: Getting company knowledge\n";
echo str_repeat('-', 50) . "\n";

$result = $mcp->getCompanyKnowledge([
    'company_id' => $testCompanyId,
    'limit' => 10
]);

if (!isset($result['error'])) {
    echo "✓ Found {$result['pagination']['total']} documents\n";
    foreach ($result['documents'] as $doc) {
        echo "  - {$doc['title']} (Status: {$doc['status']})\n";
    }
} else {
    echo "✗ Error: {$result['error']}\n";
}

// Test 3: Search knowledge
echo "\nTest 3: Searching knowledge\n";
echo str_repeat('-', 50) . "\n";

$result = $mcp->searchKnowledge([
    'query' => 'Termin',
    'company_id' => $testCompanyId,
    'limit' => 5
]);

if (!isset($result['error'])) {
    echo "✓ Found {$result['count']} results for 'Termin'\n";
    foreach ($result['results'] as $doc) {
        echo "  - {$doc['title']} (Relevance: {$doc['relevance']})\n";
    }
} else {
    echo "✗ Error: {$result['error']}\n";
}

// Test 4: Get AI context
echo "\nTest 4: Getting AI context\n";
echo str_repeat('-', 50) . "\n";

$result = $mcp->getContextForAI([
    'company_id' => $testCompanyId,
    'context' => 'Kunde möchte einen Termin vereinbaren',
    'industry' => 'medical',
    'max_documents' => 3
]);

if (!isset($result['error'])) {
    echo "✓ Generated AI context with {$result['metadata']['total_documents']} documents\n";
    echo "  Total context length: {$result['metadata']['context_length']} characters\n";
    
    foreach ($result['documents'] as $doc) {
        echo "  - {$doc['title']} ({$doc['category']})\n";
        if (!empty($doc['tags'])) {
            echo "    Tags: " . implode(', ', $doc['tags']) . "\n";
        }
    }
} else {
    echo "✗ Error: {$result['error']}\n";
}

// Test 5: Update knowledge
echo "\nTest 5: Updating knowledge document\n";
echo str_repeat('-', 50) . "\n";

// First, get a document to update
$docs = DB::table('knowledge_documents')
    ->where('company_id', $testCompanyId)
    ->first();

if ($docs) {
    $result = $mcp->updateKnowledge([
        'document_id' => $docs->id,
        'company_id' => $testCompanyId,
        'user_id' => 1,
        'content' => $docs->content . "\n\n### Zusätzliche Information\nDiese Information wurde automatisch hinzugefügt.",
        'tags' => ['updated', 'test', 'ai-context']
    ]);
    
    if (isset($result['success'])) {
        echo "✓ Document updated successfully\n";
        echo "  Tags: " . implode(', ', array_column($result['document']['tags'], 'name')) . "\n";
    } else {
        echo "✗ Error: {$result['error']}\n";
    }
} else {
    echo "✗ No documents found to update\n";
}

// Test 6: Get statistics
echo "\nTest 6: Getting knowledge statistics\n";
echo str_repeat('-', 50) . "\n";

$result = $mcp->getStatistics([
    'company_id' => $testCompanyId,
    'period' => '30days'
]);

if (!isset($result['error'])) {
    echo "✓ Statistics for last 30 days:\n";
    echo "  Total documents: {$result['overview']['total_documents']}\n";
    echo "  Total views: {$result['overview']['total_views']}\n";
    echo "  Helpfulness rate: {$result['overview']['helpfulness_rate']}%\n";
    echo "  Avg views per document: {$result['overview']['avg_views_per_document']}\n";
    
    if (!empty($result['popular_documents'])) {
        echo "\n  Popular documents:\n";
        foreach ($result['popular_documents'] as $doc) {
            echo "    - {$doc['title']} ({$doc['view_count']} views)\n";
        }
    }
} else {
    echo "✗ Error: {$result['error']}\n";
}

// Test 7: Industry templates demo
echo "\nTest 7: Available industry templates\n";
echo str_repeat('-', 50) . "\n";

$industries = ['medical', 'beauty', 'veterinary', 'legal'];
foreach ($industries as $industry) {
    echo "\n{$industry} industry:\n";
    
    // Try to create a sample document for each industry
    $result = $mcp->createFromTemplate([
        'company_id' => $testCompanyId,
        'industry' => $industry,
        'custom_data' => [
            'title' => ucfirst($industry) . ' - Sample Document',
            'status' => 'draft'
        ]
    ]);
    
    if (isset($result['success'])) {
        echo "  ✓ Template available and document created\n";
    } else {
        echo "  ✗ No template available\n";
    }
}

echo "\n\nAll tests completed!\n";