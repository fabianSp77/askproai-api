<?php
/**
 * Verify V77 Deployment Status
 *
 * Checks:
 * 1. Current conversation flow version
 * 2. Global prompt content (phone/email optional)
 * 3. Error handler node content
 * 4. Published agent status
 */

echo "╔═══════════════════════════════════════════════════════════════╗" . PHP_EOL;
echo "║  🔍 VERIFY V77 DEPLOYMENT STATUS                            ║" . PHP_EOL;
echo "╚═══════════════════════════════════════════════════════════════╝" . PHP_EOL;
echo PHP_EOL;

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$flowId = 'conversation_flow_a58405e3f67a';
$agentId = 'agent_45daa54928c5768b52ba3db736';

// ============================================================================
// CHECK 1: Get Current Conversation Flow
// ============================================================================
echo "═══ CHECK 1: Conversation Flow Version ═══" . PHP_EOL;

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
    echo "❌ FAILED: HTTP {$httpCode}" . PHP_EOL;
    echo $response . PHP_EOL;
    exit(1);
}

$flow = json_decode($response, true);

echo "Flow ID: {$flow['conversation_flow_id']}" . PHP_EOL;
echo "Version: {$flow['version']}" . PHP_EOL;
echo PHP_EOL;

if ($flow['version'] >= 77) {
    echo "✅ PASS: Version is 77 or higher" . PHP_EOL;
} else {
    echo "❌ FAIL: Version is {$flow['version']}, expected 77+" . PHP_EOL;
}

echo PHP_EOL;

// ============================================================================
// CHECK 2: Global Prompt - Phone/Email Optional
// ============================================================================
echo "═══ CHECK 2: Global Prompt Content ═══" . PHP_EOL;

$prompt = $flow['global_prompt'];
$promptSize = strlen($prompt);

echo "Prompt size: {$promptSize} chars" . PHP_EOL;
echo PHP_EOL;

// Check for key phrases
$checks = [
    'V74.1' => strpos($prompt, 'V74.1') !== false,
    'Phone/Email Optional' => strpos($prompt, 'Phone/Email Optional') !== false || strpos($prompt, 'OPTIONAL') !== false,
    'PFLICHT: Nur' => strpos($prompt, 'PFLICHT: Nur') !== false,
    'Vor- UND Nachname' => strpos($prompt, 'Vor- UND Nachname') !== false,
    'NICHT nach Telefon' => strpos($prompt, 'NICHT nach Telefon') !== false || strpos($prompt, 'nicht mehr Pflicht') !== false,
];

foreach ($checks as $label => $result) {
    if ($result) {
        echo "✅ Found: '{$label}'" . PHP_EOL;
    } else {
        echo "⚠️ Missing: '{$label}'" . PHP_EOL;
    }
}

echo PHP_EOL;

// Check if old phone requirement text is removed
$oldPhoneText = strpos($prompt, 'Brauche IMMER') !== false && strpos($prompt, 'Telefonnummer') !== false;
if ($oldPhoneText) {
    echo "❌ FAIL: Old phone requirement text still present" . PHP_EOL;
} else {
    echo "✅ PASS: Old phone requirement text removed" . PHP_EOL;
}

echo PHP_EOL;

// ============================================================================
// CHECK 3: Error Handler Node
// ============================================================================
echo "═══ CHECK 3: Error Handler Node ═══" . PHP_EOL;

$errorNode = null;
foreach ($flow['nodes'] as $node) {
    if ($node['id'] === 'node_collect_missing_data') {
        $errorNode = $node;
        break;
    }
}

if (!$errorNode) {
    echo "❌ FAIL: Error handler node not found" . PHP_EOL;
} else {
    echo "✅ Found: node_collect_missing_data" . PHP_EOL;
    echo "Name: {$errorNode['name']}" . PHP_EOL;
    echo PHP_EOL;

    $instruction = $errorNode['instruction']['text'];
    echo "Instruction preview:" . PHP_EOL;
    echo substr($instruction, 0, 200) . "..." . PHP_EOL;
    echo PHP_EOL;

    // Check instruction content
    $errorChecks = [
        'Kundenname fehlt' => strpos($instruction, 'Kundenname fehlt') !== false || strpos($instruction, 'Name fehlt') !== false,
        'vollständigen Namen' => strpos($instruction, 'vollständigen Namen') !== false,
        'NICHT nach Telefon' => strpos($instruction, 'NICHT nach Telefon') !== false,
        'optional' => strpos($instruction, 'optional') !== false,
    ];

    foreach ($errorChecks as $label => $result) {
        if ($result) {
            echo "✅ Check: '{$label}'" . PHP_EOL;
        } else {
            echo "⚠️ Missing: '{$label}'" . PHP_EOL;
        }
    }

    // Check if old phone/email text is present
    $hasOldPhoneText = strpos($instruction, 'Telefonnummer für Rückfragen') !== false;
    $hasOldEmailText = strpos($instruction, 'E-Mail für die Bestätigung') !== false;

    echo PHP_EOL;
    if ($hasOldPhoneText || $hasOldEmailText) {
        echo "❌ FAIL: Old phone/email prompt still in instruction" . PHP_EOL;
    } else {
        echo "✅ PASS: Old phone/email prompts removed" . PHP_EOL;
    }
}

echo PHP_EOL;

// ============================================================================
// CHECK 4: Published Agent Status
// ============================================================================
echo "═══ CHECK 4: Published Agent Status ═══" . PHP_EOL;

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
    echo "❌ FAILED: HTTP {$httpCode}" . PHP_EOL;
    echo $response . PHP_EOL;
    exit(1);
}

$agent = json_decode($response, true);

echo "Agent ID: {$agent['agent_id']}" . PHP_EOL;
echo "Agent Name: {$agent['agent_name']}" . PHP_EOL;
echo PHP_EOL;

// Check response engine
$responseEngine = $agent['response_engine'];
echo "Response Engine Type: {$responseEngine['type']}" . PHP_EOL;

if (isset($responseEngine['conversation_flow_id'])) {
    $agentFlowId = $responseEngine['conversation_flow_id'];
    echo "Conversation Flow ID: {$agentFlowId}" . PHP_EOL;

    if ($agentFlowId === $flowId) {
        echo "✅ PASS: Agent uses correct conversation flow" . PHP_EOL;
    } else {
        echo "❌ FAIL: Agent uses different flow: {$agentFlowId}" . PHP_EOL;
    }
} else {
    echo "⚠️ WARNING: No conversation flow ID in agent config" . PHP_EOL;
}

echo PHP_EOL;

// Check if agent has last_modification_timestamp
if (isset($agent['last_modification_timestamp'])) {
    $lastModified = date('Y-m-d H:i:s', $agent['last_modification_timestamp']);
    echo "Last Modified: {$lastModified}" . PHP_EOL;
}

echo PHP_EOL;

// ============================================================================
// SUMMARY
// ============================================================================
echo "╔═══════════════════════════════════════════════════════════════╗" . PHP_EOL;
echo "║  📊 VERIFICATION SUMMARY                                     ║" . PHP_EOL;
echo "╚═══════════════════════════════════════════════════════════════╝" . PHP_EOL;
echo PHP_EOL;

$allChecks = [
    'Flow Version 77+' => $flow['version'] >= 77,
    'Global Prompt Updated' => $checks['PFLICHT: Nur'] ?? false,
    'Error Handler Updated' => $errorNode !== null,
    'Agent Uses Flow' => isset($agentFlowId) && $agentFlowId === $flowId,
];

$passed = array_sum($allChecks);
$total = count($allChecks);

foreach ($allChecks as $label => $result) {
    $status = $result ? '✅' : '❌';
    echo "{$status} {$label}" . PHP_EOL;
}

echo PHP_EOL;
echo "Result: {$passed}/{$total} checks passed" . PHP_EOL;
echo PHP_EOL;

if ($passed === $total) {
    echo "✅ V77 IS DEPLOYED AND ACTIVE!" . PHP_EOL;
    echo PHP_EOL;
    echo "🧪 Ready for testing:" . PHP_EOL;
    echo "   - Call agent and book without phone" . PHP_EOL;
    echo "   - Error should only ask for name" . PHP_EOL;
    echo "   - Fallback phone +49000000000 should be used" . PHP_EOL;
    exit(0);
} else {
    echo "⚠️ V77 DEPLOYMENT INCOMPLETE" . PHP_EOL;
    echo "Review failed checks above" . PHP_EOL;
    exit(1);
}
