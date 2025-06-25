<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Company;

// Find Fabian's user
$user = User::where('email', 'fabian@askproai.de')->first();

if (!$user) {
    echo "User not found!\n";
    exit;
}

echo "=== USER INFO ===\n";
echo "User ID: " . $user->id . "\n";
echo "Company ID: " . $user->company_id . "\n\n";

// Get company
$company = $user->company;

if (!$company) {
    echo "Company not found!\n";
    exit;
}

echo "=== COMPANY INFO ===\n";
echo "Company Name: " . $company->name . "\n";
echo "Retell API Key exists: " . (!empty($company->retell_api_key) ? 'YES' : 'NO') . "\n";
echo "Retell API Key length: " . strlen($company->retell_api_key) . "\n";
echo "CalCom API Key exists: " . (!empty($company->calcom_api_key) ? 'YES' : 'NO') . "\n\n";

// Test Retell API Key
if ($company->retell_api_key) {
    echo "=== TESTING RETELL API ===\n";
    
    try {
        $apiKey = $company->retell_api_key;
        // Decrypt if needed
        if (strlen($apiKey) > 50) {
            $apiKey = decrypt($apiKey);
        }
        
        echo "API Key (first 10 chars): " . substr($apiKey, 0, 10) . "...\n";
        
        // Initialize service
        $retellService = new App\Services\RetellV2Service($apiKey);
        
        // Try to list agents
        echo "Trying to list agents...\n";
        $result = $retellService->listAgents();
        
        if (isset($result['agents'])) {
            echo "SUCCESS! Found " . count($result['agents']) . " agents\n";
            
            foreach ($result['agents'] as $index => $agent) {
                echo "\nAgent " . ($index + 1) . ":\n";
                echo "  ID: " . ($agent['agent_id'] ?? 'N/A') . "\n";
                echo "  Name: " . ($agent['agent_name'] ?? 'N/A') . "\n";
                echo "  Status: " . ($agent['status'] ?? 'N/A') . "\n";
            }
        } else {
            echo "No agents found or API returned unexpected format\n";
            echo "Response: " . json_encode($result) . "\n";
        }
        
        // Try to list phone numbers
        echo "\n=== PHONE NUMBERS ===\n";
        $phones = $retellService->listPhoneNumbers();
        if (isset($phones['phone_numbers'])) {
            echo "Found " . count($phones['phone_numbers']) . " phone numbers\n";
            foreach ($phones['phone_numbers'] as $phone) {
                echo "  " . ($phone['phone_number'] ?? 'N/A') . " -> Agent: " . ($phone['agent_id'] ?? 'None') . "\n";
            }
        }
        
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
        echo "Trace: " . $e->getTraceAsString() . "\n";
    }
} else {
    echo "=== NO RETELL API KEY CONFIGURED ===\n";
    echo "This is why the Control Center shows no data!\n";
}

// Check for DEFAULT API key in env
echo "\n=== ENV CONFIGURATION ===\n";
echo "DEFAULT_RETELL_API_KEY exists: " . (config('services.retell.api_key') ? 'YES' : 'NO') . "\n";
echo "RETELL_TOKEN exists: " . (env('RETELL_TOKEN') ? 'YES' : 'NO') . "\n";