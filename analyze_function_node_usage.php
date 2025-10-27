<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$retellApiKey = config('services.retellai.api_key');
$agentId = 'agent_f1ce85d06a84afb989dfbb16a9';

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/get-agent/{$agentId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $retellApiKey]
]);
$response = curl_exec($ch);
curl_close($ch);

$agent = json_decode($response, true);
$flowId = $agent['response_engine']['conversation_flow_id'] ?? null;

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/get-conversation-flow/{$flowId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $retellApiKey]
]);
$flowResponse = curl_exec($ch);
curl_close($ch);

$flow = json_decode($flowResponse, true);

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║   FUNCTION NODE USAGE ANALYSIS (RETELL BEST PRACTICES)      ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

echo "Flow Version: " . ($flow['version'] ?? 'N/A') . "\n\n";

// Analyze func_check_availability
foreach ($flow['nodes'] as $node) {
    if (($node['id'] ?? null) === 'func_check_availability') {
        echo "=== func_check_availability NODE ===\n\n";

        $toolId = $node['tool_id'] ?? 'N/A';
        $speakDuring = $node['speak_during_execution'] ?? false;
        $waitForResult = $node['wait_for_result'] ?? false;
        $instruction = $node['instruction']['text'] ?? '';

        echo "Tool ID: $toolId\n";
        echo "speak_during_execution: " . ($speakDuring ? 'TRUE' : 'FALSE') . "\n";
        echo "wait_for_result: " . ($waitForResult ? 'TRUE' : 'FALSE') . "\n";
        echo "Instruction Length: " . strlen($instruction) . " characters\n\n";

        echo "Instruction:\n";
        echo "─────────────────────────────────────────────────────\n";
        echo $instruction . "\n";
        echo "─────────────────────────────────────────────────────\n\n";

        echo "=== RETELL DOCUMENTATION COMPLIANCE ===\n\n";

        $violations = [];

        // Check 1: Conversation logic in function node
        if (stripos($instruction, 'GREET') !== false ||
            stripos($instruction, 'greet the customer') !== false ||
            stripos($instruction, 'ASK how you can help') !== false) {
            $violations[] = "❌ CRITICAL: Contains GREETING/CONVERSATION logic";
            echo "❌ CRITICAL VIOLATION: Greeting/Conversation Logic\n";
            echo "   Found: GREET, ASK how you can help\n";
            echo "   Retell Docs: 'function node is not intended for having a conversation'\n";
            echo "   Fix: Move greeting to separate conversation node\n\n";
        }

        // Check 2: Intent recognition
        if (stripos($instruction, 'IDENTIFY intent') !== false) {
            $violations[] = "❌ CRITICAL: Contains INTENT RECOGNITION";
            echo "❌ CRITICAL VIOLATION: Intent Recognition Logic\n";
            echo "   Found: IDENTIFY intent\n";
            echo "   Retell Docs: Use conversation nodes for intent recognition\n";
            echo "   Fix: Use separate conversation node for intent\n\n";
        }

        // Check 3: Data collection
        if (stripos($instruction, 'Collect ALL required data') !== false ||
            stripos($instruction, 'If ANY required data missing') !== false ||
            stripos($instruction, 'ASK for missing data') !== false) {
            $violations[] = "❌ CRITICAL: Contains DATA COLLECTION logic";
            echo "❌ CRITICAL VIOLATION: Data Collection Logic\n";
            echo "   Found: Collect data, ASK for missing data\n";
            echo "   Retell Docs: speak_during_execution is for simple status updates\n";
            echo "   Example: 'Let me check that for you' - NOT complex data collection!\n";
            echo "   Fix: Use conversation node BEFORE function node for data collection\n\n";
        }

        // Check 4: Instruction complexity
        if (strlen($instruction) > 500) {
            $violations[] = "⚠️  WARNING: Very long instruction";
            echo "⚠️  WARNING: Instruction Very Long\n";
            echo "   Length: " . strlen($instruction) . " characters\n";
            echo "   Best Practice: Simple, focused instructions\n";
            echo "   Fix: Simplify to just function execution logic\n\n";
        }

        echo "=== CORRECT USAGE PATTERN ===\n\n";
        echo "According to Retell documentation:\n\n";
        echo "1. Conversation Node (BEFORE function):\n";
        echo "   - Greet customer\n";
        echo "   - Identify intent\n";
        echo "   - Collect all required data\n";
        echo "   - Validate data is complete\n\n";

        echo "2. Function Node:\n";
        echo "   - Simple instruction: 'Check availability with provided parameters'\n";
        echo "   - speak_during_execution: 'Einen Moment, ich prüfe das...'\n";
        echo "   - Tool automatically called with parameters\n";
        echo "   - wait_for_result: true\n\n";

        echo "3. Conversation Node (AFTER function):\n";
        echo "   - Announce result to user\n";
        echo "   - Handle different outcomes (available vs unavailable)\n\n";

        echo "=== SUMMARY ===\n\n";

        if (count($violations) > 0) {
            echo "🚨 FOUND " . count($violations) . " VIOLATIONS:\n";
            foreach ($violations as $violation) {
                echo "   $violation\n";
            }
            echo "\n";
            echo "❌ CONCLUSION: Function node is INCORRECTLY configured!\n";
            echo "   This explains why functions are not being called.\n";
            echo "   Retell may be blocking transitions due to incorrect usage.\n\n";
        } else {
            echo "✅ No violations found - configuration looks correct!\n\n";
        }
    }
}
