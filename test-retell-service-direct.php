<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\Company;
use App\Services\RetellV2Service;

echo "TESTING RETELL V2 SERVICE DIRECTLY\n";
echo str_repeat('=', 50) . "\n\n";

$company = Company::find(1);
$apiKey = decrypt($company->retell_api_key);

echo "API Key: " . substr($apiKey, 0, 20) . "...\n\n";

try {
    $service = new RetellV2Service($apiKey);
    
    echo "1. Testing listAgents()...\n";
    $agentsResult = $service->listAgents();
    
    if (isset($agentsResult['agents'])) {
        echo "   ✅ Success! Found " . count($agentsResult['agents']) . " agents\n";
        
        // Show first 3 agents
        foreach (array_slice($agentsResult['agents'], 0, 3) as $i => $agent) {
            echo "   Agent " . ($i + 1) . ": {$agent['agent_name']} (ID: {$agent['agent_id']})\n";
        }
    } else {
        echo "   ❌ Unexpected result: " . json_encode($agentsResult) . "\n";
    }
    
    echo "\n2. Testing listPhoneNumbers()...\n";
    $phonesResult = $service->listPhoneNumbers();
    
    if (isset($phonesResult['phone_numbers'])) {
        echo "   ✅ Success! Found " . count($phonesResult['phone_numbers']) . " phone numbers\n";
        
        // Show first 3 phone numbers
        foreach (array_slice($phonesResult['phone_numbers'], 0, 3) as $i => $phone) {
            echo "   Phone " . ($i + 1) . ": {$phone['phone_number']}\n";
        }
    } else {
        echo "   ❌ Unexpected result: " . json_encode($phonesResult) . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n✅ DIRECT SERVICE TEST COMPLETE\n";