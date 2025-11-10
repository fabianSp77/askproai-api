<?php

/**
 * Check V16 Status and Compare with V15
 */

$agentId = 'agent_45daa54928c5768b52ba3db736';
$flowId = 'conversation_flow_a58405e3f67a';
$apiKey = 'key_6ff998ba48e842092e04a5455d19';

echo "🔍 CHECKING V16 STATUS\n";
echo str_repeat('=', 80) . "\n\n";

// Get Agent
$ch = curl_init("https://api.retellai.com/get-agent/{$agentId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
$agentResponse = curl_exec($ch);
curl_close($ch);

$agent = json_decode($agentResponse, true);

echo "AGENT STATUS:\n";
echo "   Version: V{$agent['version']}\n";
echo "   Is Published: " . ($agent['is_published'] ? '🟢 YES' : '🔴 NO') . "\n";
echo "   Using Flow: V{$agent['response_engine']['version']}\n\n";

// Get Flow
$ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
$flowResponse = curl_exec($ch);
curl_close($ch);

$flow = json_decode($flowResponse, true);

echo "FLOW STATUS:\n";
echo "   Version: V{$flow['version']}\n";
echo "   Is Published: " . ($flow['is_published'] ? '🟢 YES' : '🔴 NO') . "\n\n";

// Check parameter mappings in V16
echo "PARAMETER MAPPINGS IN V16:\n";
echo str_repeat('-', 80) . "\n";

$functionNodes = array_filter($flow['nodes'], fn($n) => $n['type'] === 'function');

foreach ($functionNodes as $node) {
    $mapping = $node['parameter_mapping'] ?? [];
    $callId = $mapping['call_id'] ?? 'NOT SET';
    echo "   {$node['name']}: call_id = {$callId}\n";
}
echo "\n";

// Check global prompt
echo "GLOBAL PROMPT - DYNAMIC VARIABLES:\n";
echo str_repeat('-', 80) . "\n";

preg_match_all('/-\s+\{\{([^}]+)\}\}\s+-/', $flow['global_prompt'], $matches);
foreach ($matches[1] as $var) {
    echo "   - {{" . trim($var) . "}}\n";
}
echo "\n";

// Diagnosis
echo str_repeat('=', 80) . "\n";
echo "DIAGNOSIS:\n";
echo str_repeat('=', 80) . "\n\n";

if (!$agent['is_published']) {
    echo "❌ PROBLEM: Agent V16 is NOT PUBLISHED\n";
    echo "   Your test call used Agent V15 (published).\n";
    echo "   But we've been editing V16 (draft).\n\n";
}

if ($flow['version'] == 16 && !$flow['is_published']) {
    echo "❌ PROBLEM: Flow V16 is NOT PUBLISHED\n";
    echo "   Even if you publish Agent V16, it will use unpublished Flow V16.\n\n";
}

echo "NEXT STEPS:\n";
echo "1. Determine if {{call.call_id}} is even available in Retell conversation flows\n";
echo "2. If not available, find alternative solution\n";
echo "3. Publish correct version with working solution\n\n";
