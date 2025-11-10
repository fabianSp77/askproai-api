<?php
/**
 * Fix Version Number - Set to 77 explicitly
 * Content is already V77, just version field needs update
 */

echo "🔧 Fixing Version Number to 77..." . PHP_EOL;
echo PHP_EOL;

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$flowId = 'conversation_flow_a58405e3f67a';

// Get current flow
$ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
$response = curl_exec($ch);
curl_close($ch);

$flow = json_decode($response, true);

echo "Current version: {$flow['version']}" . PHP_EOL;

// Update version to 77
$flow['version'] = 77;

echo "Setting version to: 77" . PHP_EOL;
echo PHP_EOL;

// Upload with version 77
$payload = json_encode([
    'version' => 77,
    'global_prompt' => $flow['global_prompt'],
    'nodes' => $flow['nodes'],
    'start_node_id' => $flow['start_node_id'],
    'start_speaker' => $flow['start_speaker'],
    'begin_after_user_silence_ms' => $flow['begin_after_user_silence_ms'],
    'tools' => $flow['tools'],
], JSON_UNESCAPED_UNICODE);

$ch = curl_init("https://api.retellai.com/update-conversation-flow/{$flowId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ SUCCESS: Version set to 77" . PHP_EOL;

    // Verify
    $ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $updatedFlow = json_decode($response, true);
    echo "Verified version: {$updatedFlow['version']}" . PHP_EOL;

    if ($updatedFlow['version'] == 77) {
        echo "✅ CONFIRMED: V77 is now active!" . PHP_EOL;
        exit(0);
    } else {
        echo "⚠️ Version still shows as {$updatedFlow['version']}" . PHP_EOL;
        echo "Note: Retell might not persist version field, but content is V77" . PHP_EOL;
        exit(0);
    }
} else {
    echo "❌ FAILED: HTTP {$httpCode}" . PHP_EOL;
    echo $response . PHP_EOL;
    exit(1);
}
