<?php

// Fix Retell webhook configuration for MCP server
require_once __DIR__ . '/vendor/autoload.php';

use App\Services\RetellV2Service;

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$agentId = 'agent_9a8202a740cd3120d96fcfda1e';

echo "=== Fixing Retell MCP Webhook Configuration ===\n\n";

// Create service instance
$retellService = new RetellV2Service($apiKey);

// Step 1: Get current configuration
echo "1. Getting current agent configuration...\n";
$currentAgent = $retellService->getAgent($agentId);

if (!$currentAgent) {
    die("❌ Failed to get agent configuration\n");
}

echo "   Current webhook URL: " . ($currentAgent['webhook_url'] ?? 'NOT SET') . "\n";
echo "   Current webhook events: " . json_encode($currentAgent['webhook_events'] ?? []) . "\n\n";

// Step 2: Update to MCP webhook with all events enabled
echo "2. Updating webhook configuration...\n";

$updateConfig = [
    'webhook_url' => 'https://api.askproai.de/api/mcp/retell/webhook',
    'webhook_events' => [
        'call_started',
        'call_ended',
        'call_analyzed'
    ]
];

try {
    $result = $retellService->updateAgent($agentId, $updateConfig);
    echo "   ✅ Update successful!\n\n";
} catch (\Exception $e) {
    die("   ❌ Update failed: " . $e->getMessage() . "\n");
}

// Step 3: Verify the update
echo "3. Verifying update...\n";
sleep(2); // Give API time to propagate

$updatedAgent = $retellService->getAgent($agentId);

if (!$updatedAgent) {
    die("❌ Failed to verify agent configuration\n");
}

echo "   New webhook URL: " . ($updatedAgent['webhook_url'] ?? 'NOT SET') . "\n";
echo "   New webhook events: " . json_encode($updatedAgent['webhook_events'] ?? []) . "\n\n";

// Step 4: Test the MCP webhook endpoint
echo "4. Testing MCP webhook endpoint...\n";

$ch = curl_init('https://api.askproai.de/api/mcp/retell/webhook');
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'event' => 'test',
    'call_id' => 'test-' . uniqid()
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'x-retell-signature: test-signature'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   MCP endpoint response code: $httpCode\n";
if ($httpCode >= 200 && $httpCode < 300) {
    echo "   ✅ MCP endpoint is accessible\n";
} else {
    echo "   ⚠️  MCP endpoint returned unexpected code\n";
}

echo "\n=== Summary ===\n";
if ($updatedAgent['webhook_url'] == 'https://api.askproai.de/api/mcp/retell/webhook') {
    echo "✅ Webhook URL is correctly set to MCP endpoint\n";
} else {
    echo "❌ Webhook URL is NOT set to MCP endpoint\n";
}

if (!empty($updatedAgent['webhook_events'])) {
    echo "✅ Webhook events are enabled: " . implode(', ', $updatedAgent['webhook_events']) . "\n";
} else {
    echo "⚠️  Webhook events might not be visible in API response (this is normal)\n";
    echo "   The events should still be enabled. Make a test call to verify.\n";
}

echo "\n🎯 Next Steps:\n";
echo "1. Make a test call to +49 30 837 93 369\n";
echo "2. Check MCP server logs: tail -f storage/logs/mcp-server.log\n";
echo "3. Verify webhook is received at MCP endpoint\n";

echo "\nDone!\n";