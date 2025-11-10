<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$apiKey = $_ENV['RETELLAI_API_KEY'] ?? $_ENV['RETELL_TOKEN'];
$baseUrl = 'https://api.retellai.com';
$conversationFlowId = 'conversation_flow_a58405e3f67a'; // Friseur 1 Conversation Flow

echo "═══════════════════════════════════════════════════════\n";
echo "🚀 UPLOADING V62 CONVERSATION FLOW (CORRECT ENDPOINT)\n";
echo "═══════════════════════════════════════════════════════\n\n";

echo "Conversation Flow ID: $conversationFlowId\n";
echo "Endpoint: /update-conversation-flow/{id}\n\n";

// Load the V62 conversation flow
$v62Full = json_decode(file_get_contents(__DIR__ . '/../retell_agent_v62_fixed.json'), true);

if (!$v62Full || !isset($v62Full['conversation_flow'])) {
    die("❌ Failed to load V62 JSON or conversation_flow missing!\n");
}

// Extract the conversation flow (without conversation_flow_id and version wrapper)
$conversationFlow = $v62Full['conversation_flow'];

echo "✅ Conversation Flow loaded:\n";
echo "   - Nodes: " . count($conversationFlow['nodes']) . "\n";
echo "   - Tools: " . count($conversationFlow['tools']) . "\n";
echo "   - Global Prompt: " . strlen($conversationFlow['global_prompt']) . " chars\n";

// Verify V62 content
if (strpos($conversationFlow['global_prompt'], 'V62 (2025-11-07 OPTIMIZED)') === false) {
    die("❌ Global Prompt does NOT contain V62 marker!\n");
}
echo "   ✅ Global Prompt contains V62 marker\n\n";

// Check for new features
$hasAntiLoopNode = false;
foreach ($conversationFlow['nodes'] as $node) {
    if (($node['id'] ?? '') === 'logic_split_anti_loop' || ($node['name'] ?? '') === 'Anti-Loop Check') {
        $hasAntiLoopNode = true;
        break;
    }
}
echo "   " . ($hasAntiLoopNode ? "✅" : "❌") . " Anti-Loop Node found\n";

// Check tool timeouts
$checkAvailTool = null;
foreach ($conversationFlow['tools'] as $tool) {
    if ($tool['tool_id'] === 'tool-check-availability') {
        $checkAvailTool = $tool;
        break;
    }
}
echo "   Tool timeout check_availability: " . ($checkAvailTool['timeout_ms'] ?? 'N/A') . "ms\n";
if ($checkAvailTool && $checkAvailTool['timeout_ms'] === 3000) {
    echo "   ✅ Timeout optimized (3000ms)\n";
} else {
    echo "   ❌ Timeout NOT optimized (expected 3000ms)\n";
}

echo "\n📤 Uploading to Retell AI...\n";
echo "Method: PATCH /update-conversation-flow/$conversationFlowId\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/update-conversation-flow/$conversationFlowId");
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($conversationFlow));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";

if ($httpCode === 200 || $httpCode === 201) {
    $result = json_decode($response, true);

    echo "\n✅ UPLOAD SUCCESSFUL!\n";
    echo "══════════════════════════════════════════════════\n";
    echo "Conversation Flow ID: " . ($result['conversation_flow_id'] ?? 'N/A') . "\n";
    echo "Version: " . ($result['version'] ?? 'N/A') . "\n";
    echo "Nodes: " . (isset($result['nodes']) ? count($result['nodes']) : 'N/A') . "\n";
    echo "Tools: " . (isset($result['tools']) ? count($result['tools']) : 'N/A') . "\n";
    echo "══════════════════════════════════════════════════\n\n";

    // Save response for verification
    file_put_contents(__DIR__ . '/../v62_flow_upload_response.json', json_encode($result, JSON_PRETTY_PRINT));
    echo "✅ Response saved to: v62_flow_upload_response.json\n\n";

    echo "📋 Next Steps:\n";
    echo "1. Verify the conversation flow in Retell Dashboard\n";
    echo "2. Link to agent (should be automatic)\n";
    echo "3. Test the new version\n";
    echo "4. Publish agent if tests pass\n";

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
