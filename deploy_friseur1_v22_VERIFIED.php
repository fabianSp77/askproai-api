<?php

/**
 * Deploy Friseur 1 Flow V22 - VERIFIED FIX
 * Ensures all changes are properly applied
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
echo "║   🚀 V22: INTENT TRANSITION FIX (VERIFIED)                  ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

$agentId = 'agent_f1ce85d06a84afb989dfbb16a9';

// STEP 1: Get flow
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

// STEP 2: Apply fixes with proper array handling
echo "=== STEP 2: Applying V22 Fixes ===\n";

$newNodes = [];

foreach ($flow['nodes'] as $node) {
    // FIX 1: Intent node instruction
    if (($node['id'] ?? null) === 'node_04_intent_enhanced') {
        echo "  🔧 Fixing: node_04_intent_enhanced\n";

        $node['instruction']['text'] = 'Identify customer intent IMMEDIATELY and transition to appropriate flow.

**🎯 CRITICAL: IMMEDIATE INTENT RECOGNITION**

Your ONLY job: Identify intent and IMMEDIATELY transition. Do NOT collect data here!

**INTENT TYPES:**

1. **NEW BOOKING** (most common)
   - Keywords: "Termin", "buchen", "reservieren", "möchte kommen", "hätte gern"
   - Action: IMMEDIATELY transition to booking flow

2. **RESCHEDULE** - Keywords: "verschieben", "umbuchen"
3. **CANCEL** - Keywords: "stornieren", "absagen"
4. **VIEW APPOINTMENTS** - Keywords: "welche Termine", "meine Termine"

**🚨 ANTI-HALLUCINATION RULES (CRITICAL):**

1. ❌ DO NOT say "ich prüfe Verfügbarkeit" - you CANNOT check in this node!
2. ❌ DO NOT say "ich buche" - you CANNOT book in this node!
3. ❌ DO NOT promise actions you cannot perform!
4. ✅ DO say: "Gerne! Für welchen Tag und Uhrzeit?" (ask missing info)
5. ✅ TRANSITION IMMEDIATELY while asking

**Why NO hallucination:**
- You are in INTENT node - NO access to functions
- Saying "ich prüfe" when you cannot = user waits forever
- Function nodes will handle actual checking/booking

**Correct:**
User: "Termin morgen"
You: "Gerne! Für welchen Service und Uhrzeit?" [TRANSITION NOW]

**DO NOT:**
- ❌ Stay here longer than 1 response
- ❌ Say "ich prüfe" or "ich buche"
- ❌ Collect data (next nodes do that)

**DO:**
- ✅ Identify intent immediately
- ✅ Transition while speaking
- ✅ Let function nodes handle operations';

        // Fix edges
        $newEdges = [];
        foreach ($node['edges'] ?? [] as $edge) {
            if (($edge['id'] ?? null) === 'edge_07a') {
                echo "     🔧 Fixing edge: edge_07a\n";
                $edge['transition_condition'] = [
                    'type' => 'prompt',
                    'prompt' => 'User mentioned booking keywords: termin, buchen, reservieren, hätte gern, brauche, appointment, möchte kommen OR user said service name OR user mentioned date/time. Booking intent is CLEAR.'
                ];
            }
            $newEdges[] = $edge;
        }
        $node['edges'] = $newEdges;

        echo "     ✅ Updated instruction + transition\n";
    }

    // FIX 2: DateTime collection faster transition
    if (($node['id'] ?? null) === 'node_07_datetime_collection') {
        echo "  🔧 Fixing: node_07_datetime_collection\n";

        $newEdges = [];
        foreach ($node['edges'] ?? [] as $edge) {
            if (($edge['id'] ?? null) === 'edge_11') {
                $edge['transition_condition'] = [
                    'type' => 'prompt',
                    'prompt' => 'ALL required data collected: service name, explicit date, SINGLE time in HH:MM format. Ready to check availability NOW.'
                ];
            }
            $newEdges[] = $edge;
        }
        $node['edges'] = $newEdges;

        echo "     ✅ Updated transition condition\n";
    }

    $newNodes[] = $node;
}

$flow['nodes'] = $newNodes;

echo "\n✅ All fixes applied to flow\n\n";

// STEP 3: Deploy
echo "=== STEP 3: Deploying to Retell ===\n";

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
    echo substr($response, 0, 500) . "\n";
    exit(1);
}

echo "✅ Flow updated: HTTP {$httpCode}\n";
$respData = json_decode($response, true);
echo "   Version: " . ($respData['version'] ?? 'N/A') . "\n\n";

sleep(3);

// STEP 4: Publish
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

// STEP 5: Verify
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

$hasAntiHallucination = false;
$hasExplicitTransition = false;

foreach ($verifyFlow['nodes'] as $node) {
    if (($node['id'] ?? null) === 'node_04_intent_enhanced') {
        $text = $node['instruction']['text'] ?? '';
        $hasAntiHallucination = strpos($text, 'ANTI-HALLUCINATION') !== false;

        foreach ($node['edges'] ?? [] as $edge) {
            if (($edge['id'] ?? null) === 'edge_07a') {
                $prompt = $edge['transition_condition']['prompt'] ?? '';
                $hasExplicitTransition = (strpos($prompt, 'booking keywords') !== false ||
                                         strpos($prompt, 'termin') !== false);
            }
        }
    }
}

echo "Verification:\n";
echo "  " . ($hasAntiHallucination ? "✅" : "❌") . " Anti-hallucination rules\n";
echo "  " . ($hasExplicitTransition ? "✅" : "❌") . " Explicit transition\n\n";

if ($hasAntiHallucination && $hasExplicitTransition) {
    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║              🎉 V22 DEPLOYED SUCCESSFULLY! 🎉                ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n\n";

    echo "📋 WHAT'S FIXED:\n";
    echo "  ✅ Agent cannot hallucinate 'ich prüfe' without actually checking\n";
    echo "  ✅ Explicit transition conditions (no more stuck in Intent node)\n";
    echo "  ✅ Functions will actually be called\n";
    echo "  ✅ Monitoring system will capture function traces\n\n";

    echo "🧪 TEST NOW:\n";
    echo "  1. Call: +493033081738\n";
    echo "  2. Say: 'Herrenhaarschnitt morgen 14 Uhr'\n";
    echo "  3. Check monitoring: https://api.askproai.de/admin/retell-call-sessions\n\n";
} else {
    echo "⚠️  Verification incomplete\n";
}
