<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\Company;
use App\Services\MCP\RetellMCPServer;
use Illuminate\Support\Facades\Cache;

echo "\n" . str_repeat('=', 60) . "\n";
echo "RETELL MCP REAL DATA TEST\n";
echo str_repeat('=', 60) . "\n\n";

// Clear cache to force fresh API calls
Cache::forget('mcp:retell:agents_with_phones:company_id:1');

$company = Company::find(1);
echo "✅ Testing with company: {$company->name}\n\n";

$mcpServer = new RetellMCPServer();

// Get real agents from API
echo "1. Fetching REAL agents from Retell API...\n";
$result = $mcpServer->getAgentsWithPhoneNumbers(['company_id' => $company->id]);

if (isset($result['error'])) {
    echo "   ❌ Error: {$result['error']}\n";
} else {
    echo "   ✅ Found {$result['total_agents']} agents\n";
    echo "   ✅ Total phone numbers: {$result['total_phone_numbers']}\n";
    
    if (isset($result['is_mock']) && $result['is_mock']) {
        echo "   ⚠️  Using mock data (API might still have issues)\n";
    } else {
        echo "   ✅ Using REAL data from Retell API!\n";
    }
    
    // Display first 5 agents
    $agents = array_slice($result['agents'], 0, 5);
    foreach ($agents as $index => $agent) {
        echo "\n   Agent " . ($index + 1) . ": {$agent['agent_name']}\n";
        echo "   - ID: {$agent['agent_id']}\n";
        echo "   - Voice: " . ($agent['voice_id'] ?? 'N/A') . "\n";
        echo "   - Language: " . ($agent['language'] ?? 'N/A') . "\n";
        echo "   - Phone Numbers: " . count($agent['phone_numbers']) . "\n";
        
        foreach ($agent['phone_numbers'] as $phone) {
            echo "     • " . ($phone['phone_number'] ?? 'N/A') . "\n";
        }
    }
    
    if (count($result['agents']) > 5) {
        echo "\n   ... and " . (count($result['agents']) - 5) . " more agents\n";
    }
}

// Test phone number sync
echo "\n2. Testing phone number synchronization...\n";
$syncResult = $mcpServer->syncPhoneNumbers(['company_id' => $company->id]);

if (isset($syncResult['error'])) {
    echo "   ❌ Sync error: {$syncResult['error']}\n";
} else {
    echo "   ✅ Successfully synced {$syncResult['synced_count']} phone numbers\n";
    
    // Show first 5 synced numbers
    $synced = array_slice($syncResult['phone_numbers'] ?? [], 0, 5);
    foreach ($synced as $sync) {
        echo "   - {$sync['phone_number']} → {$sync['branch']} ({$sync['agent_name']})\n";
    }
}

echo "\n✅ REAL DATA TEST COMPLETE!\n";