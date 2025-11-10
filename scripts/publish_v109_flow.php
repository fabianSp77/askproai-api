<?php
/**
 * Publish V109 Flow in Retell
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$flowId = 'conversation_flow_a58405e3f67a';
$apiKey = config('services.retellai.api_key');

echo "=== PUBLISH V109 FLOW ===\n\n";

// Get current flow to check version
$ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]
]);
$response = curl_exec($ch);
curl_close($ch);

$flow = json_decode($response, true);
$currentVersion = $flow['version'] ?? 'unknown';

echo "Current flow version: V{$currentVersion}\n";
echo "Flow ID: {$flowId}\n\n";

// Publish the flow
echo "Publishing flow...\n";

$ch = curl_init("https://api.retellai.com/publish-conversation-flow/{$flowId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ Failed to publish: HTTP {$httpCode}\n";
    echo "Response: {$response}\n";
    exit(1);
}

$result = json_decode($response, true);

echo "✅ Flow published successfully!\n";
echo "Published version: V{$currentVersion}\n\n";

echo "=== NEXT: UPDATE AGENT ===\n\n";
echo "Agent ID: agent_c1d8dea0445f375857a55ffd61\n";
echo "Phone: +493033081738\n\n";

echo "The agent should now automatically use V{$currentVersion}\n";
echo "Test via: https://api.askpro.ai/docs/api-testing\n\n";

echo "=== END ===\n";
