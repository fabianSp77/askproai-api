<?php
/**
 * Publish Flow V100
 */

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$flowId = 'conversation_flow_a58405e3f67a';

echo "=== PUBLISH FLOW V100 ===\n\n";

// Try to publish
$ch = curl_init("https://api.retellai.com/publish-conversation-flow/{$flowId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => json_encode([])
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ Publish failed. HTTP {$httpCode}\n";
    echo "Response: {$response}\n\n";

    echo "⚠️  API Publishing funktioniert nicht!\n";
    echo "Du musst MANUELL im Retell Dashboard Flow V100 publishen:\n\n";
    echo "1. Gehe zu https://dashboard.retellai.com/agents/{$flowId}\n";
    echo "2. Finde Version 100\n";
    echo "3. Klicke 'Publish'\n\n";
} else {
    echo "✅ Flow V100 published!\n\n";
    echo json_encode(json_decode($response, true), JSON_PRETTY_PRINT) . "\n";
}

echo "\n=== END PUBLISH ===\n";
