<?php

// Fix Retell webhook to use the CORRECT MCP endpoint
$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$baseUrl = 'https://api.retellai.com';
$agentId = 'agent_9a8202a740cd3120d96fcfda1e';

echo "=== Fixing Retell Webhook to Correct MCP Endpoint ===\n\n";

// The CORRECT MCP webhook endpoint based on route:list
$correctMcpWebhookUrl = 'https://api.askproai.de/api/mcp/webhook/retell';

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

// Update to the CORRECT MCP webhook endpoint
echo "2. Updating webhook configuration to CORRECT MCP endpoint...\n";
echo "   New webhook URL: $correctMcpWebhookUrl\n";

$updateData = [
    'webhook_url' => $correctMcpWebhookUrl,
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
curl_close($ch);

echo "   Response Code: $httpCode\n";

if ($httpCode == 200 || $httpCode == 201) {
    echo "   ✅ Update successful!\n\n";
} else {
    echo "   ❌ Update failed!\n";
    echo "   Response: " . substr($response, 0, 500) . "\n";
    die("\nExiting due to error.\n");
}

// Verify the update
echo "3. Verifying update...\n";
sleep(2);

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
}

// Test the CORRECT MCP webhook endpoint
echo "4. Testing CORRECT MCP webhook endpoint...\n";

$testPayload = [
    'event' => 'test',
    'call_id' => 'test-' . uniqid(),
    'timestamp' => time()
];

$ch = curl_init($correctMcpWebhookUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testPayload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   MCP endpoint response code: $httpCode\n";
if ($httpCode == 401) {
    echo "   ✅ MCP endpoint requires authentication (expected)\n";
} elseif ($httpCode >= 200 && $httpCode < 300) {
    echo "   ✅ MCP endpoint is accessible\n";
} else {
    echo "   Response: " . substr($response, 0, 200) . "\n";
}

echo "\n=== Summary ===\n";
echo "✅ Webhook URL updated to: $correctMcpWebhookUrl\n";
echo "✅ This is the CORRECT MCP webhook handler\n";
echo "\n🎯 Next Steps:\n";
echo "1. Make a test call to +49 30 837 93 369\n";
echo "2. Monitor the database:\n";
echo "   mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db -e \"SELECT * FROM retell_webhooks ORDER BY created_at DESC LIMIT 5;\"\n";
echo "3. Check MCP logs:\n";
echo "   tail -f storage/logs/mcp-server.log\n";
echo "4. Check Laravel logs:\n";
echo "   tail -f storage/logs/laravel.log | grep -i mcp\n";

echo "\nDone!\n";