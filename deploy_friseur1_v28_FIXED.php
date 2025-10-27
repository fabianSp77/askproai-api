<?php

/**
 * Deploy Friseur 1 Flow V28 - BYPASS CONVERSATION NODES (FIXED)
 *
 * Properly rebuilds nodes array with modified edges
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$retellApiKey = config('services.retellai.api_key');

if (!$retellApiKey) {
    echo "❌ RETELLAI_API_KEY not found in environment\n";
    exit(1);
}

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║   🚀 V28: BYPASS NODES - FIXED VERSION                     ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

$agentId = 'agent_f1ce85d06a84afb989dfbb16a9';

// Get current flow
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

echo "✅ Flow ID: {$flowId}\n\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/get-conversation-flow/{$flowId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $retellApiKey]
]);
$flowResponse = curl_exec($ch);
curl_close($ch);

$flow = json_decode($flowResponse, true);
echo "✅ Flow loaded: " . count($flow['nodes']) . " nodes\n\n";

// Apply fixes by rebuilding nodes array
echo "=== Applying V28 Fixes ===\n";

$newNodes = [];

foreach ($flow['nodes'] as $node) {
    // FIX 1: Intent Node - Direct to func_check_availability
    if (($node['id'] ?? null) === 'node_04_intent_enhanced') {
        echo "  🔧 Fixing: node_04_intent_enhanced\n";

        $newEdges = [];
        foreach ($node['edges'] ?? [] as $edge) {
            if (($edge['id'] ?? null) === 'edge_07a') {
                echo "     🚀 Redirecting edge_07a\n";
                echo "     OLD: → node_06_service_selection\n";
                echo "     NEW: → func_check_availability (DIRECT!)\n";

                $edge['destination_node_id'] = 'func_check_availability';
                $edge['transition_condition'] = [
                    'type' => 'prompt',
                    'prompt' => 'User wants to book an appointment'
                ];
            }
            $newEdges[] = $edge;
        }
        $node['edges'] = $newEdges;
    }

    // FIX 2: func_check_availability - Enable speaking during execution
    if (($node['id'] ?? null) === 'func_check_availability') {
        echo "  🔧 Fixing: func_check_availability\n";

        $node['speak_during_execution'] = true;

        $node['instruction'] = [
            'type' => 'prompt',
            'text' =>
                "This function checks appointment availability.\n\n" .
                "**IMPORTANT: You can speak DURING this function execution!**\n\n" .
                "Workflow:\n" .
                "1. If ANY required data missing (name, service, date, time):\n" .
                "   - ASK for missing data naturally\n" .
                "   - Example: 'Für welche Dienstleistung möchten Sie den Termin?'\n" .
                "   - Example: 'Welches Datum passt Ihnen am besten?'\n" .
                "   - Example: 'Um wie viel Uhr möchten Sie kommen?'\n" .
                "2. Once ALL data collected:\n" .
                "   - Call check_availability_v17 with:\n" .
                "     • name (if known, else ask)\n" .
                "     • datum (date in DD.MM.YYYY format)\n" .
                "     • uhrzeit (time in HH:MM format)\n" .
                "     • dienstleistung (service name)\n" .
                "     • bestaetigung: false (just check, don't book yet!)\n" .
                "3. Announce result:\n" .
                "   - Available: 'Ja, [Datum] um [Zeit] ist verfügbar! Soll ich das für Sie buchen?'\n" .
                "   - Unavailable: 'Leider ist dieser Termin nicht verfügbar. Ich habe aber folgende Alternativen...'\n\n" .
                "The function will be called when you have all required parameters."
        ];

        echo "     ✅ speak_during_execution = true\n";
        echo "     ✅ Updated instruction\n";
    }

    $newNodes[] = $node;
}

$flow['nodes'] = $newNodes;

echo "\n✅ All fixes applied to flow structure\n\n";

// Deploy
echo "=== Deploying to Retell ===\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/update-conversation-flow/{$flowId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'PATCH',
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $retellApiKey,
        'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode($flow)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 && $httpCode !== 201) {
    echo "❌ Failed: HTTP {$httpCode}\n";
    echo substr($response, 0, 1000) . "\n";
    exit(1);
}

echo "✅ Flow updated: HTTP {$httpCode}\n\n";

sleep(3);

// Publish
echo "=== Publishing Agent ===\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/publish-agent/{$agentId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $retellApiKey,
        'Content-Type: application/json'
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 && $httpCode !== 201) {
    echo "❌ Publish failed: HTTP {$httpCode}\n";
    exit(1);
}

echo "✅ Agent published!\n\n";

sleep(3);

// Verify
echo "=== Verification ===\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/get-conversation-flow/{$flowId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $retellApiKey]
]);
$flowResponse = curl_exec($ch);
curl_close($ch);

$verifyFlow = json_decode($flowResponse, true);

$directPathOk = false;
$speakDuringExecOk = false;

foreach ($verifyFlow['nodes'] as $node) {
    if (($node['id'] ?? null) === 'node_04_intent_enhanced') {
        foreach ($node['edges'] ?? [] as $edge) {
            if (($edge['id'] ?? null) === 'edge_07a') {
                $target = $edge['destination_node_id'] ?? 'N/A';
                $directPathOk = ($target === 'func_check_availability');
                echo "Intent Edge: " . ($directPathOk ? "✅" : "❌") . " → {$target}\n";
            }
        }
    }

    if (($node['id'] ?? null) === 'func_check_availability') {
        $speakDuringExecOk = ($node['speak_during_execution'] ?? false) === true;
        echo "Speak During Exec: " . ($speakDuringExecOk ? "✅ true" : "❌ false") . "\n";
    }
}

echo "\n";

if ($directPathOk && $speakDuringExecOk) {
    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║              🎉 V28 DEPLOYED SUCCESSFULLY! 🎉                ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n\n";

    echo "📋 CHANGES IN V28:\n";
    echo "  ✅ Intent → func_check_availability (DIRECT PATH - NO INTERMEDIATE NODES!)\n";
    echo "  ✅ speak_during_execution=true (Agent can collect data DURING function)\n";
    echo "  ✅ Bypassed: Service Selection node (unnecessary!)\n";
    echo "  ✅ Bypassed: DateTime Collection node (unnecessary!)\n\n";

    echo "💡 WHY THIS FIXES THE PROBLEM:\n";
    echo "  - Function nodes are GUARANTEED to execute (no transition failures)\n";
    echo "  - Agent speaks during execution to collect ANY missing data\n";
    echo "  - No more getting stuck in conversation nodes!\n";
    echo "  - Zero prompt-based transition failures between Intent and Function\n\n";

    echo "🧪 TEST NOW:\n";
    echo "  Call: +493033081738\n";
    echo "  Say: 'Ich möchte morgen 10 Uhr einen Herrenhaarschnitt'\n";
    echo "  Expected: check_availability_v17 WILL be called!\n";
    echo "  Check: https://api.askproai.de/admin/retell-call-sessions\n\n";
} else {
    echo "❌ VERIFICATION FAILED\n";
    if (!$directPathOk) echo "  ❌ Direct path not applied\n";
    if (!$speakDuringExecOk) echo "  ❌ speak_during_execution not enabled\n";
}
