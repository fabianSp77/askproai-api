<?php

/**
 * Investigation: Why is {{call.call_id}} still empty in V15?
 */

$agentId = 'agent_45daa54928c5768b52ba3db736';
$flowId = 'conversation_flow_a58405e3f67a';
$apiKey = 'key_6ff998ba48e842092e04a5455d19';

echo "🔍 INVESTIGATING call_id EMPTY STRING ISSUE\n";
echo str_repeat('=', 80) . "\n\n";

// ============================================================================
// 1. VERIFY PUBLISHED AGENT IS USING FLOW V15
// ============================================================================

echo "1. CHECKING PUBLISHED AGENT CONFIGURATION\n";
echo str_repeat('-', 80) . "\n";

$ch = curl_init("https://api.retellai.com/get-agent/{$agentId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
$agentResponse = curl_exec($ch);
curl_close($ch);

$agent = json_decode($agentResponse, true);

echo "Agent ID: {$agent['agent_id']}\n";
echo "Agent Version: V{$agent['version']}\n";
echo "Is Published: " . ($agent['is_published'] ? 'YES' : 'NO') . "\n";
echo "Response Engine Type: {$agent['response_engine']['type']}\n";
echo "Flow Version: V{$agent['response_engine']['version']}\n";
echo "Flow ID: {$agent['response_engine']['conversation_flow_id']}\n\n";

if ($agent['version'] != 15) {
    echo "❌ PROBLEM: Agent is not V15!\n";
    exit(1);
}

if (!$agent['is_published']) {
    echo "❌ PROBLEM: Agent is not published!\n";
    exit(1);
}

if ($agent['response_engine']['version'] != 15) {
    echo "❌ PROBLEM: Agent is using Flow V{$agent['response_engine']['version']}, not V15!\n";
    exit(1);
}

echo "✅ Agent V15 is published and using Flow V15\n\n";

// ============================================================================
// 2. CHECK IF FLOW V15 IS ACTUALLY PUBLISHED
// ============================================================================

echo "2. CHECKING FLOW V15 PUBLISH STATUS\n";
echo str_repeat('-', 80) . "\n";

$ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
$flowResponse = curl_exec($ch);
curl_close($ch);

$flow = json_decode($flowResponse, true);

echo "Flow ID: {$flow['conversation_flow_id']}\n";
echo "Flow Version: V{$flow['version']}\n";
echo "Is Published: " . ($flow['is_published'] ? 'YES' : 'NO') . "\n\n";

if ($flow['version'] != 15) {
    echo "❌ PROBLEM: Flow is V{$flow['version']}, not V15!\n";
    exit(1);
}

if (!$flow['is_published']) {
    echo "⚠️  WARNING: Flow V15 is NOT published! This might be the issue.\n";
    echo "   Published agent might be using an older cached version.\n\n";
} else {
    echo "✅ Flow V15 is published\n\n";
}

// ============================================================================
// 3. EXAMINE ACTUAL PARAMETER MAPPINGS IN FLOW V15
// ============================================================================

echo "3. EXAMINING PARAMETER MAPPINGS IN FLOW V15\n";
echo str_repeat('-', 80) . "\n";

$functionNodes = array_filter($flow['nodes'], fn($n) => $n['type'] === 'function');

foreach ($functionNodes as $node) {
    echo "Function: {$node['name']}\n";
    echo "   Tool: " . ($node['tool']['name'] ?? 'N/A') . "\n";

    $mapping = $node['parameter_mapping'] ?? [];
    echo "   Parameter Mappings:\n";
    foreach ($mapping as $param => $value) {
        $icon = ($param === 'call_id') ? '🔍' : '  ';
        echo "   {$icon} {$param}: {$value}\n";
    }

    if (isset($mapping['call_id'])) {
        if ($mapping['call_id'] === '{{call.call_id}}') {
            echo "   ✅ call_id mapping looks correct\n";
        } else {
            echo "   ❌ call_id mapping is: {$mapping['call_id']}\n";
        }
    } else {
        echo "   ❌ call_id mapping is MISSING!\n";
    }
    echo "\n";
}

// ============================================================================
// 4. CHECK FOR DYNAMIC VARIABLES IN GLOBAL PROMPT
// ============================================================================

echo "4. CHECKING IF call.call_id IS DECLARED AS VARIABLE\n";
echo str_repeat('-', 80) . "\n";

$globalPrompt = $flow['global_prompt'];

// Check if call.call_id is mentioned anywhere
if (stripos($globalPrompt, 'call.call_id') !== false) {
    echo "✅ 'call.call_id' found in global_prompt\n";

    // Show context
    $lines = explode("\n", $globalPrompt);
    foreach ($lines as $line) {
        if (stripos($line, 'call.call_id') !== false) {
            echo "   Context: " . trim($line) . "\n";
        }
    }
} else {
    echo "⚠️  'call.call_id' NOT found in global_prompt\n";
    echo "   This might be the issue - system variables might need declaration\n";
}
echo "\n";

// Check what variables ARE declared
echo "Declared Dynamic Variables:\n";
preg_match_all('/-\s+\{\{([^}]+)\}\}\s+-/', $globalPrompt, $matches);
foreach ($matches[1] as $var) {
    $icon = ($var === 'call.call_id') ? '🔍' : '  ';
    echo "   {$icon} {{" . trim($var) . "}}\n";
}
echo "\n";

// ============================================================================
// 5. DIAGNOSIS
// ============================================================================

echo str_repeat('=', 80) . "\n";
echo "DIAGNOSIS:\n";
echo str_repeat('=', 80) . "\n\n";

$issues = [];

// Check if flow is published
if (!$flow['is_published']) {
    $issues[] = "Flow V15 is NOT published - agent might be using older cached version";
}

// Check if call.call_id is declared
$callIdDeclared = stripos($globalPrompt, 'call.call_id') !== false;
if (!$callIdDeclared) {
    $issues[] = "call.call_id is NOT declared in global_prompt - might need explicit declaration";
}

// Check parameter mappings
$allMappingsCorrect = true;
foreach ($functionNodes as $node) {
    $mapping = $node['parameter_mapping'] ?? [];
    if (isset($mapping['call_id']) && $mapping['call_id'] !== '{{call.call_id}}') {
        $allMappingsCorrect = false;
        $issues[] = "Function {$node['name']} has incorrect call_id mapping: {$mapping['call_id']}";
    }
}

if (count($issues) > 0) {
    echo "POTENTIAL ISSUES FOUND:\n\n";
    foreach ($issues as $i => $issue) {
        echo ($i + 1) . ". {$issue}\n";
    }
    echo "\n";
} else {
    echo "NO OBVIOUS CONFIGURATION ISSUES FOUND\n\n";
    echo "This suggests the problem might be:\n";
    echo "1. Retell conversation flows don't support system variables like {{call.call_id}}\n";
    echo "2. Different syntax is needed (e.g., {{retell.call_id}} or {{system.call_id}})\n";
    echo "3. call_id needs to be passed via a different mechanism\n\n";
}

// ============================================================================
// 6. RECOMMENDATIONS
// ============================================================================

echo str_repeat('=', 80) . "\n";
echo "RECOMMENDATIONS:\n";
echo str_repeat('=', 80) . "\n\n";

if (!$flow['is_published']) {
    echo "ACTION 1: Publish Flow V15\n";
    echo "   The flow needs to be published for the agent to use it.\n\n";
}

if (!$callIdDeclared) {
    echo "ACTION 2: Try declaring {{call.call_id}} in global_prompt\n";
    echo "   Add: - {{call.call_id}} - Eindeutige Call ID\n";
    echo "   System variables might need explicit declaration.\n\n";
}

echo "ACTION 3: Research Retell Documentation\n";
echo "   WebFetch Retell docs to find:\n";
echo "   - How to access call context in conversation flows\n";
echo "   - Correct syntax for system variables\n";
echo "   - Examples of parameter_mapping with system data\n\n";

echo "ACTION 4: Test Alternative Syntaxes\n";
echo "   Try these parameter_mapping values:\n";
echo "   - {{retell.call_id}}\n";
echo "   - {{system.call_id}}\n";
echo "   - {{call_id}} (without 'call.' prefix)\n";
echo "   - Look for Retell's list of available system variables\n\n";
