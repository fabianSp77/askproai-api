<?php

// Fix Retell webhook configuration for MCP server
$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$baseUrl = 'https://api.retellai.com';
$agentId = 'agent_9a8202a740cd3120d96fcfda1e';

echo "=== Fixing Retell MCP Webhook Configuration ===\n\n";

// Step 1: Get current configuration
echo "1. Getting current agent configuration...\n";
$ch = curl_init($baseUrl . '/get-agent/' . $agentId);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode != 200) {
    die("❌ Failed to get agent configuration. HTTP Code: $httpCode\n");
}

$currentAgent = json_decode($response, true);
echo "   Current webhook URL: " . ($currentAgent['webhook_url'] ?? 'NOT SET') . "\n";
echo "   Current webhook events: " . json_encode($currentAgent['webhook_events'] ?? []) . "\n\n";

// Step 2: Update to MCP webhook with all events enabled
echo "2. Updating webhook configuration to MCP endpoint...\n";

$updateData = [
    'webhook_url' => 'https://api.askproai.de/api/mcp/retell/webhook',
    'webhook_events' => [
        'call_started',
        'call_ended',
        'call_analyzed'
    ]
];

$ch = curl_init($baseUrl . '/update-agent/' . $agentId);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($updateData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$errorInfo = curl_error($ch);
curl_close($ch);

echo "   Response Code: $httpCode\n";

if ($httpCode == 200 || $httpCode == 201) {
    echo "   ✅ Update successful!\n\n";
} else {
    echo "   ❌ Update failed!\n";
    echo "   Response: " . substr($response, 0, 500) . "\n";
    if ($errorInfo) {
        echo "   CURL Error: " . $errorInfo . "\n";
    }
    die("\nExiting due to error.\n");
}

// Step 3: Verify the update
echo "3. Verifying update...\n";
sleep(2); // Give API time to propagate

$ch = curl_init($baseUrl . '/get-agent/' . $agentId);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    $updatedAgent = json_decode($response, true);
    echo "   New webhook URL: " . ($updatedAgent['webhook_url'] ?? 'NOT SET') . "\n";
    echo "   New webhook events: " . json_encode($updatedAgent['webhook_events'] ?? []) . "\n\n";
} else {
    echo "   ⚠️  Failed to verify configuration\n\n";
}

// Step 4: Test the MCP webhook endpoint
echo "4. Testing MCP webhook endpoint...\n";

$testPayload = [
    'event' => 'test',
    'call_id' => 'test-' . uniqid(),
    'timestamp' => time()
];

$ch = curl_init('https://api.askproai.de/api/mcp/retell/webhook');
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testPayload));
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
    echo "   Response: " . substr($response, 0, 200) . "\n";
} else {
    echo "   ⚠️  MCP endpoint returned unexpected code\n";
    echo "   Response: " . substr($response, 0, 200) . "\n";
}

// Step 5: Check old webhook endpoint
echo "\n5. Checking old webhook endpoint status...\n";

$ch = curl_init('https://api.askproai.de/api/retell/webhook');
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testPayload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'x-retell-signature: test-signature'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   Old endpoint response code: $httpCode\n";

echo "\n=== Summary ===\n";
if (isset($updatedAgent)) {
    if ($updatedAgent['webhook_url'] == 'https://api.askproai.de/api/mcp/retell/webhook') {
        echo "✅ Webhook URL is correctly set to MCP endpoint\n";
    } else {
        echo "❌ Webhook URL is NOT set to MCP endpoint\n";
        echo "   Current URL: " . $updatedAgent['webhook_url'] . "\n";
    }
    
    if (!empty($updatedAgent['webhook_events'])) {
        echo "✅ Webhook events are enabled: " . implode(', ', $updatedAgent['webhook_events']) . "\n";
    } else {
        echo "⚠️  Webhook events might not be visible in API response (this is normal)\n";
        echo "   The events should still be enabled. Make a test call to verify.\n";
    }
}

echo "\n🎯 Next Steps:\n";
echo "1. Make a test call to +49 30 837 93 369\n";
echo "2. Check MCP server logs: tail -f storage/logs/mcp-server.log\n";
echo "3. Monitor webhook table: SELECT * FROM retell_webhooks ORDER BY created_at DESC LIMIT 5;\n";
echo "4. If webhooks still go to old endpoint, restart MCP server:\n";
echo "   php artisan queue:restart\n";
echo "   supervisorctl restart mcp-server\n";

echo "\nDone!\n";