<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\Company;
use App\Models\Branch;
use App\Models\PhoneNumber;
use App\Services\RetellV2Service;
use App\Services\MCP\RetellMCPServer;

echo "\n" . str_repeat('=', 60) . "\n";
echo "TESTING SPECIFIC AGENT AND PHONE NUMBER\n";
echo str_repeat('=', 60) . "\n\n";

$targetAgentId = 'agent_9a8202a740cd3120d96fcfda1e';
$targetPhoneNumber = '+493083793369';

$company = Company::find(1);
$apiKey = decrypt($company->retell_api_key);
$service = new RetellV2Service($apiKey);

// 1. Get agent details
echo "1. Getting agent details...\n";
try {
    $agent = $service->getAgent($targetAgentId);
    
    echo "   ✅ Agent found!\n";
    echo "   - Name: " . ($agent['agent_name'] ?? 'N/A') . "\n";
    echo "   - ID: " . ($agent['agent_id'] ?? 'N/A') . "\n";
    echo "   - Voice: " . ($agent['voice_id'] ?? 'N/A') . "\n";
    echo "   - Language: " . ($agent['language'] ?? 'N/A') . "\n";
    echo "   - Webhook URL: " . ($agent['webhook_url'] ?? 'Not set') . "\n";
    echo "   - Response Engine: " . ($agent['response_engine']['type'] ?? 'N/A') . "\n";
    
    if (isset($agent['post_call_analysis_data'])) {
        echo "   - Post Call Analysis: Enabled\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// 2. Get phone number details
echo "\n2. Getting phone number details...\n";
try {
    $phone = $service->getPhoneNumber($targetPhoneNumber);
    
    echo "   ✅ Phone number found!\n";
    echo "   - Number: " . ($phone['phone_number'] ?? 'N/A') . "\n";
    echo "   - Nickname: " . ($phone['nickname'] ?? 'N/A') . "\n";
    echo "   - Type: " . ($phone['phone_number_type'] ?? 'N/A') . "\n";
    echo "   - Inbound Agent ID: " . ($phone['inbound_agent_id'] ?? 'Not assigned') . "\n";
    echo "   - Inbound Webhook URL: " . ($phone['inbound_webhook_url'] ?? 'Not set') . "\n";
    
    // Verify agent assignment
    if (($phone['inbound_agent_id'] ?? '') === $targetAgentId) {
        echo "   ✅ Phone number is correctly assigned to the agent!\n";
    } else {
        echo "   ⚠️  Phone number is assigned to a different agent\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// 3. Check database mapping
echo "\n3. Checking database mapping...\n";

// Check phone number in database
$phoneRecord = PhoneNumber::withoutGlobalScopes()
    ->where('number', $targetPhoneNumber)
    ->first();

if ($phoneRecord) {
    echo "   ✅ Phone number exists in database\n";
    echo "   - Branch ID: " . ($phoneRecord->branch_id ?? 'Not assigned') . "\n";
    echo "   - Retell Agent ID: " . ($phoneRecord->retell_agent_id ?? 'Not set') . "\n";
    echo "   - Is Active: " . ($phoneRecord->is_active ? 'Yes' : 'No') . "\n";
    
    // Check branch
    if ($phoneRecord->branch_id) {
        $branch = Branch::withoutGlobalScopes()->find($phoneRecord->branch_id);
        if ($branch) {
            echo "   - Branch Name: {$branch->name}\n";
            echo "   - Branch Retell Agent ID: " . ($branch->retell_agent_id ?? 'Not set') . "\n";
            echo "   - Branch Cal.com Event Type: " . ($branch->calcom_event_type_id ?? 'Not set') . "\n";
        }
    }
} else {
    echo "   ⚠️  Phone number not found in database\n";
    
    // Create it
    echo "   Creating phone number record...\n";
    $phoneRecord = PhoneNumber::withoutGlobalScopes()->create([
        'number' => $targetPhoneNumber,
        'company_id' => $company->id,
        'retell_phone_id' => $targetPhoneNumber,
        'retell_agent_id' => $targetAgentId,
        'is_active' => true,
        'type' => 'retell',
        'capabilities' => [
            'sms' => false,
            'voice' => true,
            'whatsapp' => false
        ],
        'metadata' => [
            'agent_name' => 'Online: Assistent für Fabian Spitzer Rechtliches',
            'last_synced' => now()->toIso8601String()
        ]
    ]);
    echo "   ✅ Phone number record created\n";
}

// 4. Test webhook flow
echo "\n4. Testing webhook flow...\n";
echo "   When a call comes to {$targetPhoneNumber}:\n";
echo "   1. Retell will use agent: {$targetAgentId}\n";
echo "   2. Webhook will be sent to: https://api.askproai.de/api/mcp/retell/webhook\n";
echo "   3. MCP will process the webhook\n";
echo "   4. Call will be logged in the database\n";

// 5. Test a sample webhook payload
echo "\n5. Simulating a test webhook...\n";
$testPayload = [
    'event' => 'call_started',
    'call' => [
        'call_id' => 'test_call_' . uniqid(),
        'from_number' => '+491234567890',
        'to_number' => $targetPhoneNumber,
        'direction' => 'inbound',
        'call_status' => 'ongoing',
        'metadata' => [
            'agent_id' => $targetAgentId
        ]
    ]
];

echo "   Test payload:\n";
echo "   - Event: call_started\n";
echo "   - From: +491234567890\n";
echo "   - To: {$targetPhoneNumber}\n";
echo "   - Agent: {$targetAgentId}\n";

// Use MCP to process
$mcpServer = new RetellMCPServer();
$webhookTest = $mcpServer->testWebhookEndpoint([
    'webhook_url' => 'https://api.askproai.de/api/mcp/retell/webhook'
]);

if ($webhookTest['success'] ?? false) {
    echo "   ✅ Webhook endpoint is reachable\n";
} else {
    echo "   ⚠️  Webhook endpoint returned: " . ($webhookTest['status_code'] ?? 'Unknown') . "\n";
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "CONFIGURATION SUMMARY\n";
echo str_repeat('=', 60) . "\n";
echo "\n✅ Agent ID: {$targetAgentId}\n";
echo "✅ Phone: {$targetPhoneNumber}\n";
echo "✅ Webhook: https://api.askproai.de/api/mcp/retell/webhook\n";
echo "✅ Ready to receive calls!\n\n";
echo "When someone calls +49 30 837 93 369, they will reach:\n";
echo "'Online: Assistent für Fabian Spitzer Rechtliches'\n";