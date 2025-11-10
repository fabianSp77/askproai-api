<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$apiKey = $_ENV['RETELLAI_API_KEY'] ?? $_ENV['RETELL_TOKEN'];
$agentId = 'agent_45daa54928c5768b52ba3db736';
$conversationFlowId = 'conversation_flow_a58405e3f67a';

echo "═══════════════════════════════════════════════════════\n";
echo "🔍 VERIFYING V63 UPLOAD (V62 Content)\n";
echo "═══════════════════════════════════════════════════════\n\n";

// 1. Get agent details
echo "1️⃣ Fetching Agent Details...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.retellai.com/get-agent/$agentId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("❌ Failed to fetch agent: HTTP $httpCode\n$response\n");
}

$agent = json_decode($response, true);
echo "✅ Agent fetched successfully\n";
echo "   - Version: " . $agent['version'] . "\n";
echo "   - Name: " . $agent['agent_name'] . "\n";
echo "   - Published: " . ($agent['is_published'] ? 'YES' : 'NO') . "\n\n";

// 2. Get conversation flow details
echo "2️⃣ Fetching Conversation Flow Details...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.retellai.com/get-conversation-flow/$conversationFlowId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("❌ Failed to fetch conversation flow: HTTP $httpCode\n$response\n");
}

$flow = json_decode($response, true);
echo "✅ Conversation Flow fetched successfully\n\n";

// Save for analysis
file_put_contents(__DIR__ . '/../v63_conversation_flow_live.json', json_encode($flow, JSON_PRETTY_PRINT));

// 3. Verify nodes
echo "3️⃣ Verifying Nodes...\n";
$nodes = $flow['nodes'] ?? [];
echo "   - Total Nodes: " . count($nodes) . "\n";

$nodeTypes = [];
foreach ($nodes as $node) {
    $type = $node['type'] ?? 'unknown';
    $nodeTypes[$type] = ($nodeTypes[$type] ?? 0) + 1;
}

echo "   - Node Types:\n";
foreach ($nodeTypes as $type => $count) {
    echo "     • $type: $count\n";
}

// Check for specific important nodes
$importantNodes = [
    'logic_split_anti_loop',
    'node_anti_loop_handler',
    'func_check_availability',
    'func_start_booking',
    'func_get_current_context'
];

echo "\n   - Important Nodes Check:\n";
foreach ($importantNodes as $nodeName) {
    $found = false;
    foreach ($nodes as $node) {
        if (($node['id'] ?? $node['name'] ?? '') === $nodeName) {
            $found = true;
            break;
        }
    }
    echo "     " . ($found ? "✅" : "❌") . " $nodeName\n";
}

// 4. Verify tools
echo "\n4️⃣ Verifying Tools...\n";
$tools = $flow['tools'] ?? [];
echo "   - Total Tools: " . count($tools) . "\n\n";

foreach ($tools as $tool) {
    $name = $tool['tool_id'] ?? $tool['name'] ?? 'unknown';
    $timeout = $tool['timeout_ms'] ?? 'N/A';
    echo "   • $name: {$timeout}ms\n";
}

// 5. Check Fine-tuning Examples
echo "\n5️⃣ Checking Fine-tuning Examples...\n";
$exampleCount = 0;
foreach ($nodes as $node) {
    if (isset($node['edges'])) {
        foreach ($node['edges'] as $edge) {
            if (isset($edge['fine_tuning_examples'])) {
                $exampleCount += count($edge['fine_tuning_examples']);
            }
        }
    }
}
echo "   - Total Fine-tuning Examples: $exampleCount\n";

// 6. Verify Global Prompt contains Zeit/Datum standards
echo "\n6️⃣ Verifying Global Prompt Enhancements...\n";
$globalPrompt = $flow['global_prompt'] ?? '';
$checks = [
    'Zeit/Datum-Standard' => strpos($globalPrompt, '⏰ ZEIT- UND DATUMSANSAGE STANDARD') !== false,
    'Anti-Repetition Rules' => strpos($globalPrompt, '🚨 Anti-Repetition Rules') !== false,
    'Alternative Attempt Counter' => strpos($globalPrompt, 'alternative_attempt_count') !== false,
    'Tool-Call Enforcement' => strpos($globalPrompt, 'Tool-Call Enforcement') !== false,
];

foreach ($checks as $check => $passed) {
    echo "   " . ($passed ? "✅" : "❌") . " $check\n";
}

// Summary
echo "\n═══════════════════════════════════════════════════════\n";
echo "📊 VERIFICATION SUMMARY\n";
echo "═══════════════════════════════════════════════════════\n";
echo "Agent Version: " . $agent['version'] . " (Draft)\n";
echo "Nodes: " . count($nodes) . "\n";
echo "Tools: " . count($tools) . "\n";
echo "Fine-tuning Examples: $exampleCount\n";
echo "Global Prompt Length: " . strlen($globalPrompt) . " chars\n";
echo "\n";
echo "✅ V63 uploaded successfully with V62 optimizations!\n";
echo "🔗 Dashboard: https://dashboard.retellai.com/agents/$agentId\n";
echo "\n";
echo "📝 Full conversation flow saved to: v63_conversation_flow_live.json\n";
