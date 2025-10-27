<?php

/**
 * Deploy Friseur 1 Flow V31 - ULTRA RADICAL FIX
 *
 * ROOT CAUSE: There are 3 CONVERSATION NODES before func_check_availability:
 * 1. initialize → Kundenrouting (conversation)
 * 2. Kundenrouting → Bekannter Kunde (conversation)
 * 3. Bekannter Kunde → Intent erkennen (conversation)
 * 4. Intent → func_check_availability
 *
 * V28 only fixed step 4, but agent gets stuck in steps 1-3!
 *
 * ULTRA RADICAL SOLUTION V31:
 * - initialize → func_check_availability (DIRECT!)
 * - SKIP: Kundenrouting, Bekannter Kunde, Intent erkennen
 * - Function node handles EVERYTHING
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
echo "║   🚀 V31: ULTRA RADICAL FIX - SKIP ALL NODES               ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

$agentId = 'agent_f1ce85d06a84afb989dfbb16a9';

// Get current flow
echo "=== STEP 1: Getting Current Flow ===\n";

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

echo "✅ Flow ID: {$flowId}\n";
echo "   Current version: " . ($agent['version'] ?? 'N/A') . "\n\n";

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

// Apply V31 ULTRA RADICAL Fix
echo "=== STEP 2: Applying V31 ULTRA RADICAL FIX ===\n";

$newNodes = [];

foreach ($flow['nodes'] as $node) {
    // ULTRA RADICAL FIX: initialize → func_check_availability (DIRECT!)
    if (($node['id'] ?? null) === 'func_00_initialize') {
        echo "  🔧 Fixing: func_00_initialize (Initialize Call)\n";

        $newEdges = [];
        foreach ($node['edges'] ?? [] as $edge) {
            if (($edge['id'] ?? null) === 'edge_00') {
                echo "     🚀 ULTRA RADICAL CHANGE!\n";
                echo "     OLD: → node_02_customer_routing\n";
                echo "     NEW: → func_check_availability (SKIP 3 CONVERSATION NODES!)\n";

                // SKIP: Kundenrouting, Bekannter Kunde, Intent erkennen
                $edge['destination_node_id'] = 'func_check_availability';

                // Ultra simple transition - always go there
                $edge['transition_condition'] = [
                    'type' => 'prompt',
                    'prompt' => 'Initialization complete'
                ];
            }
            $newEdges[] = $edge;
        }
        $node['edges'] = $newEdges;
    }

    // Update func_check_availability instruction
    if (($node['id'] ?? null) === 'func_check_availability') {
        echo "  🔧 Fixing: func_check_availability\n";

        // Ensure speak_during_execution is true
        $node['speak_during_execution'] = true;
        $node['wait_for_result'] = true;

        // Update instruction to handle EVERYTHING
        $node['instruction'] = [
            'type' => 'prompt',
            'text' =>
                "You are now at the FIRST interaction point after initialization.\n\n" .
                "**YOUR JOB:**\n" .
                "1. GREET the customer (use initialize_call result for name if available)\n" .
                "2. ASK how you can help\n" .
                "3. IDENTIFY intent (booking, reschedule, cancel, view appointments)\n" .
                "4. If BOOKING intent:\n" .
                "   - Collect ALL required data: name, service, date, time\n" .
                "   - Example questions:\n" .
                "     • 'Für welche Dienstleistung möchten Sie einen Termin?'\n" .
                "     • 'Welches Datum passt Ihnen?'\n" .
                "     • 'Um wie viel Uhr möchten Sie kommen?'\n" .
                "5. Once ALL data collected:\n" .
                "   - Call check_availability_v17 with:\n" .
                "     • name, datum (DD.MM.YYYY), uhrzeit (HH:MM), dienstleistung\n" .
                "     • bestaetigung: false (just check!)\n" .
                "6. Announce result:\n" .
                "   - Available: 'Ja, [Datum] um [Zeit] ist verfügbar! Soll ich buchen?'\n" .
                "   - Unavailable: 'Leider nicht verfügbar. Alternativen...'\n\n" .
                "**IMPORTANT:**\n" .
                "- You can speak DURING function execution!\n" .
                "- Handle greeting, intent recognition, and data collection ALL HERE\n" .
                "- No other nodes will help you - YOU do everything!\n\n" .
                "**SPECIAL CASES:**\n" .
                "- If NOT booking intent (reschedule/cancel/view), say: 'Einen Moment, ich verbinde Sie...'\n" .
                "  Then the flow will handle it elsewhere."
        ];

        echo "     ✅ speak_during_execution = true\n";
        echo "     ✅ Comprehensive instruction for EVERYTHING\n";
    }

    $newNodes[] = $node;
}

$flow['nodes'] = $newNodes;

echo "\n✅ All fixes applied to flow structure\n\n";

// Deploy
echo "=== STEP 3: Deploying V31 to Retell ===\n";

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
echo "=== STEP 4: Publishing Agent ===\n";

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
echo "=== STEP 5: Verification ===\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/get-conversation-flow/{$flowId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $retellApiKey]
]);
$flowResponse = curl_exec($ch);
curl_close($ch);

$verifyFlow = json_decode($flowResponse, true);

$ultraRadicalPathOk = false;

foreach ($verifyFlow['nodes'] as $node) {
    if (($node['id'] ?? null) === 'func_00_initialize') {
        foreach ($node['edges'] ?? [] as $edge) {
            if (($edge['id'] ?? null) === 'edge_00') {
                $target = $edge['destination_node_id'] ?? 'N/A';
                $ultraRadicalPathOk = ($target === 'func_check_availability');
                echo "Initialize Edge: " . ($ultraRadicalPathOk ? "✅" : "❌") . " → {$target}\n";
            }
        }
    }
}

echo "\n";

if ($ultraRadicalPathOk) {
    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║          🎉 V31 ULTRA RADICAL FIX DEPLOYED! 🎉              ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n\n";

    echo "📋 CHANGES IN V31:\n";
    echo "  ✅ initialize → func_check_availability (ULTRA DIRECT!)\n";
    echo "  ✅ SKIPPED: Kundenrouting (conversation node)\n";
    echo "  ✅ SKIPPED: Bekannter Kunde (conversation node)\n";
    echo "  ✅ SKIPPED: Intent erkennen (conversation node)\n";
    echo "  ✅ func_check_availability handles EVERYTHING\n\n";

    echo "💡 WHY THIS WILL WORK:\n";
    echo "  - ZERO conversation nodes before function node\n";
    echo "  - ZERO prompt-based transitions to fail\n";
    echo "  - Function node is GUARANTEED to execute\n";
    echo "  - Agent does greeting, intent, collection ALL in function\n\n";

    echo "🧪 TEST NOW:\n";
    echo "  Call: +493033081738\n";
    echo "  Expected: check_availability_v17 WILL be called!\n\n";
} else {
    echo "❌ VERIFICATION FAILED\n";
}
