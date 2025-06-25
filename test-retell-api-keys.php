<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Company;
use App\Services\RetellV2Service;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TESTING RETELL API KEYS ===\n\n";

// Get company
$company = Company::find(1);
echo "Company: " . $company->name . "\n\n";

// Test different API keys
$apiKeys = [
    'ENV DEFAULT_RETELL_API_KEY' => env('DEFAULT_RETELL_API_KEY'),
    'ENV RETELL_TOKEN' => env('RETELL_TOKEN'),
    'Company DB Key' => $company->retell_api_key
];

foreach ($apiKeys as $source => $apiKey) {
    if (!$apiKey) {
        echo "$source: NOT SET\n";
        continue;
    }
    
    echo "$source:\n";
    echo "  Key: " . substr($apiKey, 0, 20) . "...\n";
    
    try {
        // Try to use the key
        $service = new RetellV2Service($apiKey);
        
        // Test list agents
        $agents = $service->listAgents();
        echo "  ✓ List Agents: " . count($agents['agents'] ?? []) . " agents found\n";
        
        // Test list calls
        $calls = $service->listCalls(10);
        echo "  ✓ List Calls: " . count($calls['calls'] ?? []) . " calls found\n";
        
        // Test list phone numbers
        $phones = $service->listPhoneNumbers();
        echo "  ✓ List Phones: " . count($phones['phone_numbers'] ?? []) . " phones found\n";
        
    } catch (\Exception $e) {
        echo "  ✗ ERROR: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

// Check which key the Control Center would use
echo "\nControl Center Configuration:\n";
$controlCenterKey = $company->retell_api_key ?? config('services.retell.api_key');
echo "Would use: " . (strpos($controlCenterKey, 'key_') === 0 ? 'Plain API Key' : 'Encrypted Key') . "\n";

// Test with all phone numbers
echo "\n=== CHECKING ALL PHONE NUMBERS ===\n";
$service = new RetellV2Service(env('RETELL_TOKEN'));
$phones = $service->listPhoneNumbers();

foreach ($phones['phone_numbers'] ?? [] as $phone) {
    echo "\nPhone: " . $phone['phone_number'] . "\n";
    echo "  Inbound Agent: " . ($phone['inbound_agent_id'] ?? 'NONE') . "\n";
    
    // Try to get recent calls for this phone
    $calls = $service->listCalls(1000);
    $phoneCalls = array_filter($calls['calls'] ?? [], function($call) use ($phone) {
        return ($call['to_number'] ?? '') === $phone['phone_number'];
    });
    
    echo "  Calls to this number: " . count($phoneCalls) . "\n";
    
    if (count($phoneCalls) > 0) {
        $latest = reset($phoneCalls);
        echo "  Latest call: " . date('Y-m-d H:i:s', $latest['start_timestamp'] / 1000) . "\n";
    }
}