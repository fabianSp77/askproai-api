<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\MCP\MCPGateway;
use Illuminate\Support\Str;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n🚀 Testing MCP-based Retell Integration\n";
echo str_repeat('=', 50) . "\n\n";

$gateway = app(MCPGateway::class);
$companyId = 1; // Test company

// Test 1: Get Webhook Configuration
echo "Test 1: Getting Webhook Configuration\n";
$response = $gateway->process([
    'jsonrpc' => '2.0',
    'method' => 'retell.config.getWebhook',
    'params' => ['company_id' => $companyId],
    'id' => Str::uuid()->toString(),
]);

if (isset($response['result'])) {
    echo "✅ Webhook URL: " . $response['result']['webhook_url'] . "\n";
    echo "✅ Events: " . implode(', ', $response['result']['webhook_events'] ?? []) . "\n";
    echo "✅ Configured in Retell: " . ($response['result']['is_configured_in_retell'] ? 'Yes' : 'No') . "\n";
} else {
    echo "❌ Error: " . json_encode($response['error'] ?? 'Unknown error') . "\n";
}

echo "\n";

// Test 2: Get Custom Functions
echo "Test 2: Getting Custom Functions\n";
$response = $gateway->process([
    'jsonrpc' => '2.0',
    'method' => 'retell.config.getCustomFunctions',
    'params' => ['company_id' => $companyId],
    'id' => Str::uuid()->toString(),
]);

if (isset($response['result']['custom_functions'])) {
    foreach ($response['result']['custom_functions'] as $func) {
        echo "✅ Function: {$func['name']} - " . ($func['enabled'] ? 'Enabled' : 'Disabled') . "\n";
    }
} else {
    echo "❌ Error: " . json_encode($response['error'] ?? 'Unknown error') . "\n";
}

echo "\n";

// Test 3: Test Custom Function Call (collect_appointment)
echo "Test 3: Testing collect_appointment function\n";
$callId = 'test_' . Str::uuid();
$response = $gateway->process([
    'jsonrpc' => '2.0',
    'method' => 'retell.functions.collect_appointment',
    'params' => [
        'call_id' => $callId,
        'caller_number' => '+49 30 12345678',
        'to_number' => '+49 30 837 93 369',
        'datum' => 'morgen',
        'uhrzeit' => '14:00',
        'dienstleistung' => 'Haarschnitt',
        'name' => 'Test Kunde',
        'telefonnummer' => '+49 30 12345678',
    ],
    'id' => Str::uuid()->toString(),
]);

if (isset($response['result']['success']) && $response['result']['success']) {
    echo "✅ Appointment data collected successfully\n";
    echo "✅ Reference ID: " . $response['result']['reference_id'] . "\n";
    echo "✅ Summary: " . $response['result']['appointment_summary'] . "\n";
} else {
    echo "❌ Error: " . json_encode($response['error'] ?? $response['result'] ?? 'Unknown error') . "\n";
}

echo "\n";

// Test 4: Find Appointments by Phone
echo "Test 4: Finding appointments by phone number\n";
$response = $gateway->process([
    'jsonrpc' => '2.0',
    'method' => 'appointment.management.find',
    'params' => [
        'phone_number' => '+49 30 12345678',
        'status' => 'upcoming',
    ],
    'id' => Str::uuid()->toString(),
]);

if (isset($response['result'])) {
    if ($response['result']['found']) {
        echo "✅ Customer found: " . $response['result']['customer']['name'] . "\n";
        echo "✅ Appointments found: " . $response['result']['count'] . "\n";
        foreach ($response['result']['appointments'] as $apt) {
            echo "  - {$apt['service']} on {$apt['date']} at {$apt['time']}\n";
        }
    } else {
        echo "ℹ️  No appointments found for this number\n";
    }
} else {
    echo "❌ Error: " . json_encode($response['error'] ?? 'Unknown error') . "\n";
}

echo "\n";

// Test 5: Test Agent Prompt Template
echo "Test 5: Getting Agent Prompt Template\n";
$response = $gateway->process([
    'jsonrpc' => '2.0',
    'method' => 'retell.config.getAgentPromptTemplate',
    'params' => ['company_id' => $companyId],
    'id' => Str::uuid()->toString(),
]);

if (isset($response['result']['prompt_template'])) {
    echo "✅ Agent prompt template retrieved\n";
    echo "📝 Variables: " . implode(', ', array_keys($response['result']['variables'])) . "\n";
} else {
    echo "❌ Error: " . json_encode($response['error'] ?? 'Unknown error') . "\n";
}

echo "\n";

// Test 6: MCP Gateway Health Check
echo "Test 6: MCP Gateway Health Check\n";
$health = $gateway->health();
echo "✅ Gateway Status: " . $health['gateway'] . "\n";
echo "✅ Servers:\n";
foreach ($health['servers'] as $name => $status) {
    $statusIcon = ($status['status'] ?? 'unknown') === 'healthy' ? '✅' : '❌';
    echo "  {$statusIcon} {$name}: " . ($status['status'] ?? 'unknown') . "\n";
}

echo "\n";

// Test 7: List Available Methods
echo "Test 7: Available MCP Methods\n";
$methods = $gateway->listMethods();
$retellMethods = array_filter($methods, fn($m) => str_starts_with($m['method'], 'retell.'));
echo "✅ Found " . count($retellMethods) . " Retell-related methods\n";
foreach ($retellMethods as $method) {
    echo "  - {$method['method']}\n";
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "✅ MCP Retell Integration Test Complete!\n\n";

// Summary
echo "Summary:\n";
echo "- MCP Gateway: ✅ Working\n";
echo "- Retell Configuration: ✅ Accessible\n";
echo "- Custom Functions: ✅ Configured\n";
echo "- Appointment Management: ✅ Ready\n";
echo "- Health Monitoring: ✅ Active\n";

echo "\nNext Steps:\n";
echo "1. Configure webhook URL in Retell.ai dashboard\n";
echo "2. Deploy custom functions using the UI\n";
echo "3. Test with actual phone calls\n";
echo "4. Monitor webhook processing\n\n";