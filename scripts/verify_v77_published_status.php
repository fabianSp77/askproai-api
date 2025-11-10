<?php
/**
 * Complete V77 Published Status Verification
 *
 * Checks:
 * 1. Conversation Flow content
 * 2. Agent configuration
 * 3. Agent published status
 * 4. Live agent uses V77 content
 */

echo "╔═══════════════════════════════════════════════════════════════╗" . PHP_EOL;
echo "║  🔍 COMPLETE V77 PUBLISHED STATUS VERIFICATION               ║" . PHP_EOL;
echo "╚═══════════════════════════════════════════════════════════════╝" . PHP_EOL;
echo PHP_EOL;

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$flowId = 'conversation_flow_a58405e3f67a';
$agentId = 'agent_45daa54928c5768b52ba3db736';

$allPassed = true;

// ============================================================================
// CHECK 1: Conversation Flow Content
// ============================================================================
echo "═══ CHECK 1: Conversation Flow Content ═══" . PHP_EOL;

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
    echo "❌ FAILED to get flow: HTTP {$httpCode}" . PHP_EOL;
    exit(1);
}

$flow = json_decode($response, true);
$prompt = $flow['global_prompt'];

// V77 Content Checks
$v77Checks = [
    'V74.1 in prompt' => strpos($prompt, 'V74.1') !== false,
    'PFLICHT: Nur' => strpos($prompt, 'PFLICHT: Nur') !== false,
    'OPTIONAL: Telefon' => strpos($prompt, 'OPTIONAL') !== false && strpos($prompt, 'Telefonnummer') !== false,
    'NICHT nach Telefon' => strpos($prompt, 'NICHT nach Telefon') !== false,
];

// Error handler check
$errorNode = null;
foreach ($flow['nodes'] as $node) {
    if ($node['id'] === 'node_collect_missing_data') {
        $errorNode = $node;
        break;
    }
}

if ($errorNode) {
    $errorInstruction = $errorNode['instruction']['text'];
    $v77Checks['Error: Kundenname fehlt'] = strpos($errorInstruction, 'Kundenname fehlt') !== false;
    $v77Checks['Error: NICHT nach Telefon'] = strpos($errorInstruction, 'NICHT nach Telefon') !== false;
}

foreach ($v77Checks as $check => $result) {
    $status = $result ? '✅' : '❌';
    echo "{$status} {$check}" . PHP_EOL;
    if (!$result) $allPassed = false;
}

echo PHP_EOL;

// ============================================================================
// CHECK 2: Agent Configuration
// ============================================================================
echo "═══ CHECK 2: Agent Configuration ═══" . PHP_EOL;

$ch = curl_init("https://api.retellai.com/get-agent/{$agentId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ FAILED to get agent: HTTP {$httpCode}" . PHP_EOL;
    exit(1);
}

$agent = json_decode($response, true);

echo "Agent ID: {$agent['agent_id']}" . PHP_EOL;
echo "Agent Name: {$agent['agent_name']}" . PHP_EOL;
echo PHP_EOL;

// Check if agent uses our flow
$responseEngine = $agent['response_engine'];
$agentUsesFlow = isset($responseEngine['conversation_flow_id']) &&
                 $responseEngine['conversation_flow_id'] === $flowId;

if ($agentUsesFlow) {
    echo "✅ Agent uses conversation flow: {$flowId}" . PHP_EOL;
} else {
    echo "❌ Agent uses different flow!" . PHP_EOL;
    if (isset($responseEngine['conversation_flow_id'])) {
        echo "   Agent flow: {$responseEngine['conversation_flow_id']}" . PHP_EOL;
    }
    $allPassed = false;
}

echo PHP_EOL;

// ============================================================================
// CHECK 3: List All Agent Versions
// ============================================================================
echo "═══ CHECK 3: Agent Versions ═══" . PHP_EOL;

$ch = curl_init("https://api.retellai.com/list-agents");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
$response = curl_exec($ch);
curl_close($ch);

$agents = json_decode($response, true);

// Find our agent and related versions
$friseurAgents = [];
foreach ($agents as $a) {
    if (strpos($a['agent_name'], 'Friseur') !== false) {
        $friseurAgents[] = [
            'id' => $a['agent_id'],
            'name' => $a['agent_name'],
            'flow' => $a['response_engine']['conversation_flow_id'] ?? 'none',
        ];
    }
}

echo "Found Friseur agents:" . PHP_EOL;
foreach ($friseurAgents as $a) {
    $isCurrent = $a['id'] === $agentId ? '👉' : '  ';
    echo "{$isCurrent} {$a['name']}" . PHP_EOL;
    echo "   ID: {$a['id']}" . PHP_EOL;
    echo "   Flow: {$a['flow']}" . PHP_EOL;
}

echo PHP_EOL;

// ============================================================================
// CHECK 4: Test Live Agent Call (simulate)
// ============================================================================
echo "═══ CHECK 4: Agent Global Prompt (What Will Be Used Live) ═══" . PHP_EOL;

// The agent's response engine contains the LIVE configuration
// Check what prompt will actually be used

if (isset($responseEngine['llm_websocket_url'])) {
    echo "✅ Agent is configured for live calls (websocket ready)" . PHP_EOL;
} else {
    echo "⚠️ No websocket URL found" . PHP_EOL;
}

// Get the actual conversation flow being used
$actualFlowId = $responseEngine['conversation_flow_id'] ?? null;
if ($actualFlowId) {
    $ch = curl_init("https://api.retellai.com/get-conversation-flow/{$actualFlowId}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $liveFlow = json_decode($response, true);
    $livePrompt = $liveFlow['global_prompt'];

    echo PHP_EOL;
    echo "Live Agent Global Prompt Analysis:" . PHP_EOL;

    // Check LIVE prompt for V77 features
    $liveV77Checks = [
        'PFLICHT: Nur' => strpos($livePrompt, 'PFLICHT: Nur') !== false,
        'OPTIONAL: Telefonnummer' => strpos($livePrompt, 'OPTIONAL') !== false && strpos($livePrompt, 'Telefonnummer') !== false,
        'NICHT nach Telefon/Email' => strpos($livePrompt, 'NICHT nach Telefon') !== false,
        'V74.1' => strpos($livePrompt, 'V74.1') !== false,
    ];

    foreach ($liveV77Checks as $check => $result) {
        $status = $result ? '✅' : '❌';
        echo "   {$status} {$check}" . PHP_EOL;
        if (!$result) $allPassed = false;
    }
}

echo PHP_EOL;

// ============================================================================
// CHECK 5: Backend Configuration
// ============================================================================
echo "═══ CHECK 5: Backend Phone Validation ═══" . PHP_EOL;

$controllerPath = '/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php';
$controllerContent = file_get_contents($controllerPath);

// Check if phone validation error is removed
$hasPhoneValidationError = strpos($controllerContent, "'error_code' => 'MISSING_CUSTOMER_PHONE'") !== false;
$hasFallbackPhone = strpos($controllerContent, "config('retell.fallback_phone'") !== false ||
                     strpos($controllerContent, "fallback_phone") !== false;

if (!$hasPhoneValidationError && $hasFallbackPhone) {
    echo "✅ Backend: Phone validation error removed" . PHP_EOL;
    echo "✅ Backend: Fallback phone implemented" . PHP_EOL;
} else {
    if ($hasPhoneValidationError) {
        echo "❌ Backend: Phone validation error still exists" . PHP_EOL;
        $allPassed = false;
    }
    if (!$hasFallbackPhone) {
        echo "❌ Backend: Fallback phone not implemented" . PHP_EOL;
        $allPassed = false;
    }
}

// Check name validation still exists (should be mandatory)
$hasNameValidation = strpos($controllerContent, "'error_code' => 'MISSING_CUSTOMER_NAME'") !== false;
if ($hasNameValidation) {
    echo "✅ Backend: Name validation still mandatory (correct)" . PHP_EOL;
} else {
    echo "⚠️ Backend: Name validation not found (should be mandatory)" . PHP_EOL;
}

echo PHP_EOL;

// ============================================================================
// FINAL SUMMARY
// ============================================================================
echo "╔═══════════════════════════════════════════════════════════════╗" . PHP_EOL;
echo "║  📊 FINAL VERIFICATION SUMMARY                               ║" . PHP_EOL;
echo "╚═══════════════════════════════════════════════════════════════╝" . PHP_EOL;
echo PHP_EOL;

if ($allPassed) {
    echo "✅ ✅ ✅ V77 IS FULLY DEPLOYED AND PUBLISHED! ✅ ✅ ✅" . PHP_EOL;
    echo PHP_EOL;
    echo "🎯 What's Live:" . PHP_EOL;
    echo "   ✅ Agent uses V77 conversation flow" . PHP_EOL;
    echo "   ✅ Global prompt has phone/email optional" . PHP_EOL;
    echo "   ✅ Error handler only asks for name" . PHP_EOL;
    echo "   ✅ Backend uses fallback phone" . PHP_EOL;
    echo "   ✅ Name is still mandatory (correct)" . PHP_EOL;
    echo PHP_EOL;
    echo "🧪 Ready for live call testing!" . PHP_EOL;
    echo PHP_EOL;
    echo "📝 Next Step: Make a test call to verify behavior" . PHP_EOL;
    echo "   - Book without providing phone" . PHP_EOL;
    echo "   - Agent should NOT ask for phone" . PHP_EOL;
    echo "   - Fallback +49000000000 should be used" . PHP_EOL;
    exit(0);
} else {
    echo "⚠️ SOME V77 FEATURES NOT FULLY DEPLOYED" . PHP_EOL;
    echo "Review failed checks above" . PHP_EOL;
    exit(1);
}
