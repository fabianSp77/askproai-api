<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Company;
use App\Models\Branch;
use App\Services\RetellV2Service;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== RETELL SYNC DEBUG ===\n\n";

// Get company
$company = Company::first();
echo "Company: {$company->name}\n";
echo "Retell API Key: " . (substr($company->retell_api_key ?? '', 0, 20) . '...') . "\n\n";

// Set company context for tenant scope
app()->instance('company_id', $company->id);

// Get branches with phone numbers
$branches = Branch::whereNotNull('phone_number')->where('phone_number', '!=', '')->get();
echo "Branches with phone numbers:\n";
foreach ($branches as $branch) {
    echo "- {$branch->name}: {$branch->phone_number} -> Agent: " . ($branch->retell_agent_id ?? 'NONE') . "\n";
}
echo "\n";

// Initialize Retell service
try {
    $retellService = new RetellV2Service();
    $retellService->setCompany($company);
    
    // Get phone numbers from Retell
    echo "Fetching phone numbers from Retell API...\n";
    $phoneResponse = $retellService->listPhoneNumbers();
    
    if (!empty($phoneResponse['phone_numbers'])) {
        echo "\nPhone numbers in Retell:\n";
        foreach ($phoneResponse['phone_numbers'] as $phone) {
            echo "- Number: {$phone['phone_number']}\n";
            echo "  Agent ID: " . ($phone['agent_id'] ?? 'NONE') . "\n";
            echo "  Status: " . ($phone['status'] ?? 'unknown') . "\n";
            echo "  Inbound enabled: " . ($phone['inbound_agent_id'] ? 'YES' : 'NO') . "\n";
            echo "  Inbound Agent: " . ($phone['inbound_agent_id'] ?? 'NONE') . "\n\n";
        }
    }
    
    // Get recent calls
    echo "\nFetching recent calls from Retell API...\n";
    $callsResponse = $retellService->listCalls(5);
    
    if (!empty($callsResponse['calls'])) {
        echo "\nRecent calls:\n";
        foreach ($callsResponse['calls'] as $call) {
            echo "- Call ID: {$call['call_id']}\n";
            echo "  From: {$call['from_number']} -> To: {$call['to_number']}\n";
            echo "  Agent: {$call['agent_id']}\n";
            echo "  Start: " . date('Y-m-d H:i:s', $call['start_timestamp'] / 1000) . "\n";
            echo "  Duration: {$call['call_length']} seconds\n";
            echo "  Status: {$call['call_status']}\n\n";
        }
    } else {
        echo "No recent calls found.\n";
    }
    
    // Check webhook endpoint
    $webhookUrl = config('app.url') . '/api/retell/webhook';
    echo "\nWebhook URL configured: {$webhookUrl}\n";
    echo "Make sure this URL is configured in your Retell dashboard!\n";
    
} catch (\Exception $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}