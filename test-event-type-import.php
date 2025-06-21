<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Services\CalcomV2Service;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Support\Facades\Log;

// Test Cal.com API connection
$company = Company::first();
if (!$company) {
    echo "No company found\n";
    exit(1);
}

echo "Testing with company: {$company->name}\n";

// Decrypt API key
if ($company->calcom_api_key) {
    try {
        $apiKey = decrypt($company->calcom_api_key);
        echo "✓ API key decrypted successfully\n";
        
        // Test API call
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->get('https://api.cal.com/v2/event-types');
        
        if ($response->successful()) {
            echo "✓ Cal.com API call successful\n";
            $data = $response->json();
            
            // Check response structure
            if (isset($data['data']['eventTypeGroups'])) {
                $totalEventTypes = 0;
                foreach ($data['data']['eventTypeGroups'] as $group) {
                    if (isset($group['eventTypes'])) {
                        $totalEventTypes += count($group['eventTypes']);
                        
                        echo "\nGroup: " . ($group['profile']['name'] ?? 'Unknown') . "\n";
                        foreach ($group['eventTypes'] as $et) {
                            echo "  - " . $et['title'] . " (ID: " . $et['id'] . ")\n";
                            
                            // Check for users
                            if (isset($et['users']) && count($et['users']) > 0) {
                                echo "    Users:\n";
                                foreach ($et['users'] as $user) {
                                    echo "      - " . $user['name'] . " (ID: " . $user['id'] . ")\n";
                                }
                            }
                        }
                    }
                }
                echo "\nTotal Event Types: $totalEventTypes\n";
            } else {
                echo "Unexpected response structure\n";
                print_r($data);
            }
        } else {
            echo "✗ Cal.com API call failed: " . $response->status() . "\n";
            echo $response->body() . "\n";
        }
        
    } catch (\Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ No API key found for company\n";
}

// Check branches
$branches = \DB::table('branches')->where('company_id', $company->id)->get();
echo "\nBranches for company:\n";
foreach ($branches as $branch) {
    echo "  - {$branch->name} (ID: {$branch->id})\n";
}