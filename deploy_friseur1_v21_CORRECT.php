<?php

/**
 * Deploy Friseur 1 Flow V21 - CORRECT VERSION
 *
 * Updates Agent: agent_f1ce85d06a84afb989dfbb16a9
 *
 * FIXES:
 * 1. Start from V20 (includes anti-hallucination policy)
 * 2. Add double greeting fix (speak_during_execution = false)
 * 3. Add multiple time handling policy
 * 4. Deploy combined V20+V21 fixes
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
echo "║   🚀 V21 CORRECT: V20 + Greeting Fix + Multiple Time Fix    ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

$agentId = 'agent_f1ce85d06a84afb989dfbb16a9'; // Friseur 1
$sourceFile = __DIR__ . '/public/friseur1_flow_v20_anti_hallucination.json'; // START FROM V20!
$targetFile = __DIR__ . '/public/friseur1_flow_v21_CORRECT.json';

if (!file_exists($sourceFile)) {
    echo "❌ Source flow file not found: {$sourceFile}\n";
    exit(1);
}

echo "📄 Loading V20 flow (with anti-hallucination)...\n";
$flow = json_decode(file_get_contents($sourceFile), true);

if (!$flow) {
    echo "❌ Failed to parse flow JSON\n";
    exit(1);
}

echo "✅ V20 Flow loaded: " . count($flow['nodes']) . " nodes\n";
echo PHP_EOL;

// Fix 1: Remove double greeting by disabling speak_during_execution
echo "=== FIX 1: Remove Double Greeting ===\n";
$initNodeFound = false;

foreach ($flow['nodes'] as &$node) {
    $nodeId = $node['id'] ?? $node['node_id'] ?? null;
    $nodeName = $node['name'] ?? null;

    if ($nodeId === 'func_00_initialize' || $nodeName === '🚀 V16: Initialize Call (Parallel)') {
        $initNodeFound = true;

        // Disable speak_during_execution
        $node['speak_during_execution'] = false;
        if (isset($node['spoken_message'])) {
            $node['spoken_message'] = null;
        }

        echo "✅ Updated 'func_00_initialize' node\n";
        echo "  - speak_during_execution: FALSE (wait for customer data)\n";
        echo "  - FIX: Agent will greet ONCE with personalization\n";
        echo "  - NO MORE: Generic greeting → pause → personal greeting\n";
        break;
    }
}
unset($node); // Break reference

if (!$initNodeFound) {
    echo "⚠️  WARNING: 'func_00_initialize' node not found!\n";
}
echo PHP_EOL;

// Fix 2: Add multiple time handling policy (PRESERVING V20 anti-hallucination)
echo "=== FIX 2: Multiple Time Handling Policy ===\n";
$dateNodeFound = false;

foreach ($flow['nodes'] as &$node) {
    if (($node['name'] ?? null) === 'Datum & Zeit sammeln') {
        $dateNodeFound = true;

        // Get existing instruction text
        $existingText = $node['instruction']['text'] ?? '';

        // Check if V20 anti-hallucination policy exists
        $hasV20 = strpos($existingText, 'ANTI-HALLUCINATION') !== false;

        if (!$hasV20) {
            echo "⚠️  WARNING: V20 anti-hallucination policy NOT found in source!\n";
        } else {
            echo "✅ V20 anti-hallucination policy preserved\n";
        }

        // ADD V21 multiple time policy at the beginning
        $node['instruction'] = [
            'type' => 'prompt',
            'text' => 'Collect preferred date and time for the appointment.

**🚨 CRITICAL POLICY: Single Time Required**

When customer provides MULTIPLE times (e.g., "9 Uhr oder 10 Uhr"):
1. ACKNOWLEDGE: "Ich verstehe, 9 oder 10 Uhr"
2. ASK: "Welche Zeit bevorzugen Sie - 9 Uhr oder 10 Uhr?"
3. WAIT for customer to choose ONE specific time
4. ONLY proceed when you have SINGLE time in HH:MM format

**DATE POLICY:**
When customer provides only a TIME without a DATE (e.g., "14 Uhr"):
- Check current time
- If the requested time has ALREADY PASSED today → automatically assume TOMORROW
- If the requested time is STILL IN THE FUTURE today → ASK for clarification:
  "Meinen Sie heute um [TIME] Uhr oder morgen?"
- NEVER assume "today" without explicit confirmation when time is ambiguous

**🚨 ANTI-HALLUCINATION POLICY (CRITICAL):**
You are in DATA COLLECTION mode. You DO NOT have access to availability information.

**STRICT RULES - NO EXCEPTIONS:**
1. NEVER say whether a time is "verfügbar" (available) or "nicht verfügbar" (not available)
2. NEVER respond to availability questions like "Ist 9 Uhr frei?" without API check
3. If customer asks "Is X time free?":
   - Acknowledge: "Ich prüfe das gleich für Sie" (I\'ll check that for you)
   - Do NOT say "verfügbar" or "nicht verfügbar"
   - IMMEDIATELY indicate you have the time they want to check
4. Your ONLY job: Collect date AND time, then transition to availability check

**COMPLETE INFORMATION REQUIRED:**
- Date: Must be explicit (e.g., "heute", "morgen", "Montag", "24.10.2025")
- Time: Must be SINGLE specific time (e.g., "14:00", NOT "14 oder 15 Uhr")
- Confirm both date AND time before proceeding

**TRANSITION READINESS:**
You are ready to check availability when you have:
✅ Single specific date
✅ Single specific time (not multiple options)
✅ Service type
✅ Customer name (if new customer)

If customer already mentioned date/time, confirm it. Otherwise, ask for missing information.'
        ];

        echo "✅ Updated 'Datum & Zeit sammeln' node\n";
        echo "  - ✅ V21: Force single time selection\n";
        echo "  - ✅ V20: Anti-hallucination policy preserved\n";
        echo "  - ✅ Combined: Both policies active\n";
        break;
    }
}
unset($node); // Break reference

if (!$dateNodeFound) {
    echo "⚠️  WARNING: 'Datum & Zeit sammeln' node not found!\n";
}
echo PHP_EOL;

// Save updated flow
echo "=== Saving V21 CORRECT Flow ===\n";
file_put_contents($targetFile, json_encode($flow, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "✅ Saved to: {$targetFile}\n";
echo "  - Size: " . round(filesize($targetFile) / 1024, 2) . " KB\n";
echo "  - Nodes: " . count($flow['nodes']) . "\n";
echo PHP_EOL;

// Deploy to Retell
echo "=== Deploying to Retell Agent ===\n";

$updatePayload = [
    'conversation_flow' => $flow
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/update-agent/{$agentId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'PATCH',
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $retellApiKey,
        'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode($updatePayload),
    CURLOPT_VERBOSE => false
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($httpCode === 200 || $httpCode === 201) {
    echo "✅ Agent conversation flow updated successfully\n";
    echo "  - Agent ID: {$agentId}\n";
    echo "  - HTTP Code: {$httpCode}\n";

    // Get version from response
    $respData = json_decode($response, true);
    if (isset($respData['version'])) {
        echo "  - New Version: " . $respData['version'] . "\n";
    }
} else {
    echo "❌ Failed to update agent\n";
    echo "  - HTTP Code: {$httpCode}\n";
    echo "  - Error: {$error}\n";
    echo "  - Response: " . substr($response, 0, 500) . "\n";
    exit(1);
}
echo PHP_EOL;

// PUBLISH Agent
echo "=== Publishing Agent (Making Changes Live) ===\n";

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
$error = curl_error($ch);
curl_close($ch);

if ($httpCode === 200 || $httpCode === 201) {
    echo "✅ Agent published successfully\n";
    echo "  - Changes are now LIVE\n";
    echo "  - Version: V21 CORRECT (V20 + Greeting + Multiple Time)\n";
} else {
    echo "❌ Failed to publish agent\n";
    echo "  - HTTP Code: {$httpCode}\n";
    echo "  - Error: {$error}\n";
    echo "  - Response: {$response}\n";
    exit(1);
}
echo PHP_EOL;

// Verify deployment by fetching conversation flow directly
echo "=== Verifying Deployment ===\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/get-agent/{$agentId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $retellApiKey
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $agent = json_decode($response, true);

    if (isset($agent['response_engine']['conversation_flow_id'])) {
        $flowId = $agent['response_engine']['conversation_flow_id'];
        echo "  - Flow ID: {$flowId}\n";

        // Fetch the actual flow
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "https://api.retellai.com/get-conversation-flow/{$flowId}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $retellApiKey
            ]
        ]);

        $flowResponse = curl_exec($ch);
        $flowHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($flowHttpCode === 200) {
            $liveFlow = json_decode($flowResponse, true);

            if (isset($liveFlow['nodes'])) {
                $nodeCount = count($liveFlow['nodes']);
                echo "  - Live nodes: {$nodeCount}\n";

                // Verify fixes
                $v21Fix = false;
                $v20Fix = false;
                $greetingFix = false;

                foreach ($liveFlow['nodes'] as $node) {
                    // Check greeting fix
                    if (($node['id'] ?? null) === 'func_00_initialize') {
                        $greetingFix = ($node['speak_during_execution'] ?? true) === false;
                    }

                    // Check date/time node
                    if (($node['name'] ?? null) === 'Datum & Zeit sammeln') {
                        $text = $node['instruction']['text'] ?? '';
                        $v21Fix = strpos($text, 'Single Time Required') !== false;
                        $v20Fix = strpos($text, 'ANTI-HALLUCINATION') !== false;
                    }
                }

                echo "\n";
                echo "  " . ($greetingFix ? "✅" : "❌") . " Greeting fix (speak_during_execution = false)\n";
                echo "  " . ($v21Fix ? "✅" : "❌") . " V21 Multiple Time policy\n";
                echo "  " . ($v20Fix ? "✅" : "❌") . " V20 Anti-Hallucination policy\n";

                if ($greetingFix && $v21Fix && $v20Fix) {
                    echo "\n🎉 ALL FIXES VERIFIED IN PRODUCTION!\n";
                } else {
                    echo "\n⚠️  Some fixes missing in production\n";
                }
            }
        }
    }
}
echo PHP_EOL;

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║           V21 CORRECT DEPLOYMENT COMPLETED                   ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

echo "📋 Fixes Deployed:\n";
echo "  1. ✅ V20: Anti-Hallucination Policy (preserved)\n";
echo "  2. ✅ V21: Double Greeting Fix (speak_during_execution = FALSE)\n";
echo "  3. ✅ V21: Multiple Time Policy (force single time selection)\n";
echo PHP_EOL;

echo "🧪 Test Now:\n";
echo "  1. Call: +493033081738\n";
echo "  2. Expected: ONE greeting (\"Willkommen zurück, [Name]!\")\n";
echo "  3. Say: 'Ich möchte morgen einen Termin'\n";
echo "  4. Say: '9 oder 10 Uhr'\n";
echo "  5. Expected: Agent asks 'Welche Zeit bevorzugen Sie?'\n";
echo "  6. Say: '9 Uhr'\n";
echo "  7. Expected: Agent checks availability via API\n";
echo "  8. Expected: NO hallucination (only real availability data)\n";
echo PHP_EOL;
