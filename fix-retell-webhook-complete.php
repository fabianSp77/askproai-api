<?php

// Complete fix for Retell webhook configuration
$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$baseUrl = 'https://api.retellai.com';
$agentId = 'agent_9a8202a740cd3120d96fcfda1e';

echo "Fixing Retell webhook configuration completely...\n\n";

// Step 1: Get current agent config
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
    die("Failed to get agent. HTTP Code: $httpCode\n");
}

$agent = json_decode($response, true);
echo "   Current webhook URL: " . ($agent['webhook_url'] ?? 'NOT SET') . "\n";

// Step 2: Update with ALL required fields
echo "\n2. Updating agent with complete webhook configuration...\n";

// IMPORTANT: Include ALL webhook-related fields
$updateData = [
    'webhook_url' => 'https://api.askproai.de/api/retell/debug-webhook',
    'end_call_webhook_url' => 'https://api.askproai.de/api/retell/debug-webhook', // Some versions use this
    'webhook_events' => ['call_started', 'call_ended', 'call_analyzed'],
    'send_webhook_for_test_calls' => true, // Enable webhooks for test calls
    'enable_webhook' => true, // Explicit enable flag
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
if ($httpCode != 200 && $httpCode != 201) {
    echo "   Error: " . substr($response, 0, 500) . "\n";
} else {
    echo "   ✅ Agent updated successfully!\n";
}

// Step 3: Verify the update
echo "\n3. Verifying the update...\n";
sleep(1);

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
    echo "   Webhook URL: " . ($updatedAgent['webhook_url'] ?? 'NOT SET') . "\n";
    echo "   Webhook Events: " . json_encode($updatedAgent['webhook_events'] ?? []) . "\n";
}

// Step 4: Test webhook connectivity from Retell's perspective
echo "\n4. Testing if our webhook endpoint is reachable...\n";
$testUrl = 'https://api.askproai.de/api/retell/debug-webhook';
$ch = curl_init($testUrl);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_exec($ch);
$responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($responseCode == 200 || $responseCode == 405) {
    echo "   ✅ Webhook endpoint is reachable (HTTP $responseCode)\n";
} else {
    echo "   ❌ Webhook endpoint returned HTTP $responseCode\n";
}

echo "\n✅ Configuration complete!\n";
echo "\nNEXT STEPS:\n";
echo "1. Make a new test call\n";
echo "2. Monitor nginx logs: tail -f /var/log/nginx/access.log | grep POST\n";
echo "3. Check Laravel logs: tail -f storage/logs/laravel-*.log | grep -i retell\n";
echo "\nDone!\n";