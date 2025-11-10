<?php
/**
 * Check which flow version is published and verify its parameter_mappings
 */

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$agentId = 'agent_45daa54928c5768b52ba3db736';

echo "=== CHECK PUBLISHED FLOW PARAMETER MAPPINGS ===\n\n";

// Get agent details to find which flow version is being used
$ch = curl_init("https://api.retellai.com/get-agent/{$agentId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]
]);

$response = curl_exec($ch);
$agent = json_decode($response, true);
curl_close($ch);

echo "Agent: {$agent['agent_name']}\n";
echo "Agent Version: {$agent['version']}\n";
echo "Is Published: " . ($agent['is_published'] ? 'YES' : 'NO') . "\n\n";

$flowId = $agent['response_engine']['conversation_flow_id'];
$flowVersionInAgent = $agent['response_engine']['conversation_flow_version'] ?? 'NOT SET';

echo "Flow ID: {$flowId}\n";
echo "Flow Version in Agent Config: {$flowVersionInAgent}\n\n";

// Get current flow (latest version)
echo "Fetching flow details...\n";
$ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]
]);

$response = curl_exec($ch);
$flow = json_decode($response, true);
curl_close($ch);

echo "Current Flow Version: V{$flow['version']}\n";
echo "Is Published: " . ($flow['is_published'] ? 'YES ✅' : 'NO ❌') . "\n\n";

// Now list all agent versions to find which one is published and which flow it uses
echo "=== CHECKING ALL AGENT VERSIONS ===\n\n";

$ch = curl_init("https://api.retellai.com/list-agents");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]
]);

$response = curl_exec($ch);
$agents = json_decode($response, true);
curl_close($ch);

$publishedAgents = [];
foreach ($agents as $a) {
    if ($a['agent_id'] === $agentId && $a['is_published']) {
        $publishedAgents[] = $a;
    }
}

echo "Published Agent Versions:\n";
foreach ($publishedAgents as $a) {
    $flowVer = $a['response_engine']['conversation_flow_version'] ?? 'NOT SET';
    echo "  - Agent V{$a['version']}: Flow V{$flowVer}\n";
}

echo "\n";

// The published agent will use the latest PUBLISHED flow version
// Since flow version is "NOT SET", it uses whatever flow is published
echo "⚠️  Since Agent has 'Flow Version: NOT SET',\n";
echo "   it will use the LATEST PUBLISHED FLOW VERSION\n\n";

// Check which flow versions are published by checking agent history
echo "=== CHECKING PARAMETER MAPPINGS ===\n\n";

$toolsToCheck = [
    'get_current_context',
    'start_booking',
    'confirm_booking',
    'get_alternatives',
    'request_callback',
    'get_customer_appointments',
    'cancel_appointment',
    'reschedule_appointment',
    'get_available_services'
];

$correctCount = 0;
$missingCount = 0;
$wrongCount = 0;

foreach ($flow['tools'] as $tool) {
    if (!in_array($tool['name'], $toolsToCheck)) {
        continue;
    }

    echo "Tool: {$tool['name']}\n";

    // Check if tool has call_id parameter
    $hasCallIdParam = isset($tool['parameters']['properties']['call_id']);

    if (!$hasCallIdParam) {
        echo "  ℹ️  No call_id parameter (OK for some tools)\n\n";
        continue;
    }

    // Check parameter_mapping
    if (!isset($tool['parameter_mapping'])) {
        echo "  ❌ FEHLT: Kein parameter_mapping vorhanden!\n";
        $missingCount++;
    } elseif (!isset($tool['parameter_mapping']['call_id'])) {
        echo "  ❌ FEHLT: parameter_mapping hat kein call_id!\n";
        $missingCount++;
    } else {
        $mapping = $tool['parameter_mapping']['call_id'];
        echo "  parameter_mapping['call_id']: {$mapping}\n";

        if ($mapping === '{{call_id}}') {
            echo "  ✅ KORREKT\n";
            $correctCount++;
        } else {
            echo "  ❌ FALSCH (sollte {{call_id}} sein)\n";
            $wrongCount++;
        }
    }

    echo "\n";
}

echo "=== SUMMARY ===\n\n";
echo "Current Flow Version: V{$flow['version']}\n";
echo "Published: " . ($flow['is_published'] ? 'YES ✅' : 'NO ❌') . "\n\n";

echo "Parameter Mappings:\n";
echo "  ✅ Correct: {$correctCount}\n";
echo "  ❌ Missing/Wrong: " . ($missingCount + $wrongCount) . "\n\n";

if ($flow['is_published'] && $correctCount > 0 && ($missingCount + $wrongCount) === 0) {
    echo "✅ PUBLISHED FLOW HAT ALLE KORREKTEN MAPPINGS!\n";
    echo "   Testanrufe sollten funktionieren!\n";
} elseif ($flow['is_published']) {
    echo "⚠️  PUBLISHED FLOW HAT FEHLERHAFTE MAPPINGS!\n";
    echo "   Testanrufe werden fehlschlagen mit call_id=\"1\"\n";
} else {
    echo "❌ FLOW IST NICHT PUBLISHED!\n";
    echo "   Du musst Flow V{$flow['version']} im Dashboard publishen!\n";
}

echo "\n=== END CHECK ===\n";
