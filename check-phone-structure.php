<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\Company;
use App\Services\RetellV2Service;

$company = Company::find(1);
$service = new RetellV2Service(decrypt($company->retell_api_key));

echo "CHECKING PHONE NUMBER STRUCTURE\n";
echo str_repeat('=', 50) . "\n\n";

// Get agents
$agentsResult = $service->listAgents();
$agents = $agentsResult['agents'] ?? [];

echo "Sample Agent Structure:\n";
if (!empty($agents)) {
    $sampleAgent = $agents[0];
    echo "Agent Name: " . $sampleAgent['agent_name'] . "\n";
    echo "Agent ID: " . $sampleAgent['agent_id'] . "\n";
    echo "Phone Number IDs field: ";
    
    if (isset($sampleAgent['phone_number_ids'])) {
        echo "EXISTS\n";
        echo "  Type: " . gettype($sampleAgent['phone_number_ids']) . "\n";
        echo "  Count: " . count($sampleAgent['phone_number_ids']) . "\n";
        echo "  Sample: " . json_encode(array_slice($sampleAgent['phone_number_ids'], 0, 3)) . "\n";
    } else {
        echo "NOT FOUND\n";
        echo "  Available keys: " . implode(', ', array_keys($sampleAgent)) . "\n";
    }
}

// Get phone numbers
echo "\n\nSample Phone Number Structure:\n";
$phonesResult = $service->listPhoneNumbers();
$phones = $phonesResult['phone_numbers'] ?? [];

if (!empty($phones)) {
    $samplePhone = $phones[0];
    echo "Phone Number: " . $samplePhone['phone_number'] . "\n";
    echo "Available fields:\n";
    foreach ($samplePhone as $key => $value) {
        echo "  - $key: " . (is_array($value) ? json_encode($value) : $value) . "\n";
    }
    
    // Check for ID field
    echo "\nID field check:\n";
    $possibleIdFields = ['phone_number_id', 'id', 'phone_id', 'number_id'];
    foreach ($possibleIdFields as $field) {
        if (isset($samplePhone[$field])) {
            echo "  ✅ Found: $field = " . $samplePhone[$field] . "\n";
        }
    }
}

echo "\n✅ STRUCTURE CHECK COMPLETE\n";