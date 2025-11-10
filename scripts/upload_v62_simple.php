<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$apiKey = $_ENV['RETELLAI_API_KEY'] ?? $_ENV['RETELL_TOKEN'];
$baseUrl = 'https://api.retellai.com';
$agentId = 'agent_45daa54928c5768b52ba3db736'; // Friseur 1 Active Agent

echo "═══════════════════════════════════════════════════════\n";
echo "🚀 UPLOADING V62 CONVERSATION FLOW ONLY\n";
echo "═══════════════════════════════════════════════════════\n\n";

echo "Agent ID: $agentId\n";
echo "Strategy: Update conversation_flow ONLY (not full agent)\n\n";

// Load the full V62 JSON
$v62Full = json_decode(file_get_contents(__DIR__ . '/../retell_agent_v62_fixed.json'), true);

if (!$v62Full || !isset($v62Full['conversation_flow'])) {
    die("❌ Failed to load V62 JSON or conversation_flow missing!\n");
}

// Extract ONLY the conversation_flow
$payload = [
    'conversation_flow' => $v62Full['conversation_flow']
];

echo "✅ Conversation Flow loaded:\n";
echo "   - Nodes: " . count($v62Full['conversation_flow']['nodes']) . "\n";
echo "   - Global Prompt: " . strlen($v62Full['conversation_flow']['global_prompt']) . " chars\n\n";

echo "📤 Uploading to Retell AI...\n";
echo "Method: PATCH /update-agent/$agentId\n";
echo "Payload: conversation_flow only\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/update-agent/$agentId");
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";

if ($httpCode === 200 || $httpCode === 201) {
    $result = json_decode($response, true);

    echo "\n✅ UPLOAD SUCCESSFUL!\n";
    echo "══════════════════════════════════════════════════\n";
    echo "Agent ID: " . ($result['agent_id'] ?? 'N/A') . "\n";
    echo "Version: " . ($result['version'] ?? 'N/A') . "\n";
    echo "Agent Name: " . ($result['agent_name'] ?? 'N/A') . "\n";
    echo "Published: " . (($result['is_published'] ?? false) ? 'YES' : 'NO (Draft)') . "\n";
    echo "══════════════════════════════════════════════════\n\n";

    // Save response for verification
    file_put_contents(__DIR__ . '/../v62_upload_response.json', json_encode($result, JSON_PRETTY_PRINT));
    echo "✅ Response saved to: v62_upload_response.json\n\n";

    echo "📋 Next Steps:\n";
    echo "1. Verify the agent in Retell Dashboard\n";
    echo "2. Test the new version\n";
    echo "3. Publish if tests pass\n";

} else {
    echo "\n❌ UPLOAD FAILED!\n";
    echo "══════════════════════════════════════════════════\n";
    echo "Response:\n";
    echo $response . "\n";
    echo "══════════════════════════════════════════════════\n";

    // Try to decode error
    $error = json_decode($response, true);
    if ($error) {
        echo "\nError Details:\n";
        print_r($error);
    }
}
