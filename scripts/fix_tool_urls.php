<?php

/**
 * Fix Tool URLs in Conversation Flow
 *
 * FIX 2025-11-03: All tools must point to /api/webhooks/retell/function
 * The 'name' parameter determines which function is executed!
 */

$flowId = 'conversation_flow_a58405e3f67a';
$apiKey = 'key_6ff998ba48e842092e04a5455d19';

echo "🔍 Fetching conversation flow...\n\n";

// GET current conversation flow
$ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("❌ Failed to fetch flow (HTTP {$httpCode})\n{$response}\n");
}

$flow = json_decode($response, true);

echo "📋 Flow ID: {$flow['conversation_flow_id']}\n";
echo "📋 Version: {$flow['version']}\n";
echo "📋 Tools: " . count($flow['tools']) . "\n\n";

$correctUrl = 'https://api.askproai.de/api/webhooks/retell/function';
$updated = 0;

foreach ($flow['tools'] as &$tool) {
    // Fix empty arrays → objects
    if (isset($tool['headers']) && is_array($tool['headers']) && empty($tool['headers'])) {
        $tool['headers'] = new stdClass();
    }
    if (isset($tool['query_params']) && is_array($tool['query_params']) && empty($tool['query_params'])) {
        $tool['query_params'] = new stdClass();
    }
    if (isset($tool['response_variables']) && is_array($tool['response_variables']) && empty($tool['response_variables'])) {
        $tool['response_variables'] = new stdClass();
    }
    if (isset($tool['user_dtmf_options']) && is_array($tool['user_dtmf_options']) && empty($tool['user_dtmf_options'])) {
        $tool['user_dtmf_options'] = new stdClass();
    }

    // Check and fix URL
    if (isset($tool['url']) && $tool['url'] !== $correctUrl) {
        echo "🔧 {$tool['name']}\n";
        echo "   Old URL: {$tool['url']}\n";
        echo "   New URL: {$correctUrl}\n\n";

        $tool['url'] = $correctUrl;
        $updated++;
    }
}

if ($updated === 0) {
    echo "✅ All tool URLs are correct!\n";
    exit(0);
}

echo "\n📊 Summary: {$updated} tools updated\n\n";

// Update conversation flow
$updatePayload = [
    'tools' => $flow['tools']
];

echo "🚀 Updating conversation flow...\n\n";

$ch = curl_init("https://api.retellai.com/update-conversation-flow/{$flowId}");
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($updatePayload));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 || $httpCode === 201) {
    $result = json_decode($response, true);
    echo "✅ SUCCESS!\n";
    echo "📋 New Version: {$result['version']}\n\n";

    file_put_contents('/tmp/flow_urls_fixed.json', json_encode($result, JSON_PRETTY_PRINT));
    echo "💾 Saved to: /tmp/flow_urls_fixed.json\n\n";

    echo "🎯 All tools now point to: {$correctUrl}\n";
    echo "   The 'name' parameter determines which function is executed.\n\n";

    echo "📋 Updated Tools:\n";
    foreach ($result['tools'] as $tool) {
        echo "  ✅ {$tool['name']} → {$tool['url']}\n";
    }

    exit(0);
} else {
    echo "❌ ERROR: HTTP {$httpCode}\n";
    echo $response . "\n";
    exit(1);
}
