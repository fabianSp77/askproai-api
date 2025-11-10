<?php
/**
 * Detailed Agent Verification
 * Check if agent is correctly configured and active
 */

echo "╔═══════════════════════════════════════════════════════════════╗" . PHP_EOL;
echo "║  🔍 DETAILED AGENT VERIFICATION                              ║" . PHP_EOL;
echo "╚═══════════════════════════════════════════════════════════════╝" . PHP_EOL;
echo PHP_EOL;

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$agentId = 'agent_45daa54928c5768b52ba3db736';
$expectedFlowId = 'conversation_flow_a58405e3f67a';

// ============================================================================
// GET AGENT DETAILS
// ============================================================================
echo "═══ AGENT CONFIGURATION ═══" . PHP_EOL;

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
    echo $response . PHP_EOL;
    exit(1);
}

$agent = json_decode($response, true);

echo "Agent ID: {$agent['agent_id']}" . PHP_EOL;
echo "Agent Name: {$agent['agent_name']}" . PHP_EOL;
echo PHP_EOL;

// Check basic configuration
echo "Basic Configuration:" . PHP_EOL;
echo "  Voice ID: {$agent['voice_id']}" . PHP_EOL;
echo "  Language: {$agent['language']}" . PHP_EOL;
echo "  Responsiveness: " . ($agent['responsiveness'] ?? 'default') . PHP_EOL;
echo "  Interruption Sensitivity: " . ($agent['interruption_sensitivity'] ?? 'default') . PHP_EOL;
echo PHP_EOL;

// ============================================================================
// CHECK RESPONSE ENGINE
// ============================================================================
echo "═══ RESPONSE ENGINE ═══" . PHP_EOL;

$responseEngine = $agent['response_engine'];
echo "Type: {$responseEngine['type']}" . PHP_EOL;

if ($responseEngine['type'] === 'conversation-flow') {
    $flowId = $responseEngine['conversation_flow_id'] ?? null;

    if ($flowId === $expectedFlowId) {
        echo "✅ Uses correct conversation flow: {$flowId}" . PHP_EOL;
    } else {
        echo "❌ Uses WRONG conversation flow!" . PHP_EOL;
        echo "   Expected: {$expectedFlowId}" . PHP_EOL;
        echo "   Actual: {$flowId}" . PHP_EOL;
    }
} else {
    echo "⚠️ Not using conversation flow! Type: {$responseEngine['type']}" . PHP_EOL;
}

echo PHP_EOL;

// Check LLM configuration
if (isset($responseEngine['llm_id'])) {
    echo "LLM ID: {$responseEngine['llm_id']}" . PHP_EOL;
}
if (isset($responseEngine['llm_websocket_url'])) {
    echo "✅ Websocket URL configured (ready for live calls)" . PHP_EOL;
} else {
    echo "⚠️ No websocket URL (might not work for live calls)" . PHP_EOL;
}

echo PHP_EOL;

// ============================================================================
// CHECK AGENT PHONE NUMBER(S)
// ============================================================================
echo "═══ AGENT PHONE NUMBERS ═══" . PHP_EOL;

$ch = curl_init("https://api.retellai.com/list-phone-numbers");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
$response = curl_exec($ch);
curl_close($ch);

$phoneNumbers = json_decode($response, true);

$agentPhones = [];
foreach ($phoneNumbers as $phone) {
    if (isset($phone['agent_id']) && $phone['agent_id'] === $agentId) {
        $agentPhones[] = $phone;
    }
}

if (empty($agentPhones)) {
    echo "⚠️ No phone numbers assigned to this agent" . PHP_EOL;
    echo "   Agent might not receive calls" . PHP_EOL;
} else {
    echo "✅ Agent has " . count($agentPhones) . " phone number(s):" . PHP_EOL;
    foreach ($agentPhones as $phone) {
        echo "   - {$phone['phone_number']} ({$phone['nickname']})" . PHP_EOL;
    }
}

echo PHP_EOL;

// ============================================================================
// GET LIVE CONVERSATION FLOW CONTENT
// ============================================================================
echo "═══ LIVE CONVERSATION FLOW CONTENT ═══" . PHP_EOL;

$flowId = $responseEngine['conversation_flow_id'] ?? null;
if ($flowId) {
    $ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $flow = json_decode($response, true);

    echo "Flow ID: {$flow['conversation_flow_id']}" . PHP_EOL;
    echo "Version: {$flow['version']}" . PHP_EOL;
    echo "Prompt Size: " . strlen($flow['global_prompt']) . " chars" . PHP_EOL;
    echo PHP_EOL;

    // Check V77 content
    $prompt = $flow['global_prompt'];

    echo "V77 Content Checks:" . PHP_EOL;

    $checks = [
        'V74.1 Prompt' => strpos($prompt, 'V74.1') !== false,
        'PFLICHT: Nur Name' => strpos($prompt, 'PFLICHT: Nur') !== false,
        'OPTIONAL: Telefon' => strpos($prompt, 'OPTIONAL') !== false && strpos($prompt, 'Telefonnummer') !== false,
        'NICHT nach Telefon' => strpos($prompt, 'NICHT nach Telefon') !== false,
    ];

    foreach ($checks as $label => $result) {
        $status = $result ? '✅' : '❌';
        echo "  {$status} {$label}" . PHP_EOL;
    }

    // Check error handler
    $errorNode = null;
    foreach ($flow['nodes'] as $node) {
        if ($node['id'] === 'node_collect_missing_data') {
            $errorNode = $node;
            break;
        }
    }

    if ($errorNode) {
        $instruction = $errorNode['instruction']['text'];
        $errorChecks = [
            'Error: Kundenname fehlt' => strpos($instruction, 'Kundenname fehlt') !== false,
            'Error: NICHT nach Telefon' => strpos($instruction, 'NICHT nach Telefon') !== false,
        ];

        foreach ($errorChecks as $label => $result) {
            $status = $result ? '✅' : '❌';
            echo "  {$status} {$label}" . PHP_EOL;
        }
    }
}

echo PHP_EOL;

// ============================================================================
// CHECK IF THERE ARE NEWER AGENT VERSIONS
// ============================================================================
echo "═══ CHECK FOR MULTIPLE AGENT VERSIONS ═══" . PHP_EOL;

$ch = curl_init("https://api.retellai.com/list-agents");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
$response = curl_exec($ch);
curl_close($ch);

$allAgents = json_decode($response, true);

// Find all agents with "Friseur 1" in name
$friseurAgents = [];
foreach ($allAgents as $a) {
    if (stripos($a['agent_name'], 'Friseur 1') !== false &&
        !stripos($a['agent_name'], 'copy') &&
        !stripos($a['agent_name'], 'Test') &&
        $a['agent_id'] !== 'agent_f1ce85d06a84afb989dfbb16a9' && // old agent
        $a['agent_id'] !== 'agent_9a8202a740cd3120d96fcfda1e') { // old agent

        $friseurAgents[] = [
            'id' => $a['agent_id'],
            'name' => $a['agent_name'],
            'flow' => $a['response_engine']['conversation_flow_id'] ?? 'none',
        ];
    }
}

// Remove duplicates
$uniqueAgents = [];
$seen = [];
foreach ($friseurAgents as $a) {
    $key = $a['id'] . $a['name'];
    if (!isset($seen[$key])) {
        $uniqueAgents[] = $a;
        $seen[$key] = true;
    }
}

if (count($uniqueAgents) > 1) {
    echo "⚠️ WARNING: Multiple Friseur 1 agents found!" . PHP_EOL;
    echo PHP_EOL;
    foreach ($uniqueAgents as $a) {
        $isCurrent = $a['id'] === $agentId ? '👉 CURRENT' : '   ';
        echo "{$isCurrent} {$a['name']}" . PHP_EOL;
        echo "   ID: {$a['id']}" . PHP_EOL;
        echo "   Flow: {$a['flow']}" . PHP_EOL;
        echo PHP_EOL;
    }
    echo "⚠️ Make sure the correct agent is used for calls!" . PHP_EOL;
} else {
    echo "✅ Only one active Friseur 1 agent found (correct)" . PHP_EOL;
    echo "   {$uniqueAgents[0]['name']}" . PHP_EOL;
    echo "   ID: {$uniqueAgents[0]['id']}" . PHP_EOL;
}

echo PHP_EOL;

// ============================================================================
// FINAL SUMMARY
// ============================================================================
echo "╔═══════════════════════════════════════════════════════════════╗" . PHP_EOL;
echo "║  📊 AGENT VERIFICATION SUMMARY                               ║" . PHP_EOL;
echo "╚═══════════════════════════════════════════════════════════════╝" . PHP_EOL;
echo PHP_EOL;

$allGood = true;
$summary = [
    'Agent exists' => $agent !== null,
    'Uses conversation flow' => $responseEngine['type'] === 'conversation-flow',
    'Correct flow ID' => ($responseEngine['conversation_flow_id'] ?? null) === $expectedFlowId,
    'Flow has V77 content' => isset($checks) && !in_array(false, $checks),
    'Error handler correct' => isset($errorChecks) && !in_array(false, $errorChecks),
    'Has phone numbers' => !empty($agentPhones),
];

foreach ($summary as $label => $result) {
    $status = $result ? '✅' : '❌';
    echo "{$status} {$label}" . PHP_EOL;
    if (!$result) $allGood = false;
}

echo PHP_EOL;

if ($allGood) {
    echo "✅ ✅ ✅ AGENT IS CORRECTLY CONFIGURED! ✅ ✅ ✅" . PHP_EOL;
    echo PHP_EOL;
    echo "🎯 The agent will use V77 conversation flow for all calls" . PHP_EOL;
    echo "📞 Ready to receive calls and book without phone/email requirement" . PHP_EOL;
    exit(0);
} else {
    echo "⚠️ AGENT CONFIGURATION HAS ISSUES" . PHP_EOL;
    echo "Review failed checks above" . PHP_EOL;
    exit(1);
}
