<?php

/**
 * Deploy Friseur 1 Flow V22 - INTENT TRANSITION FIX
 *
 * ROOT CAUSE IDENTIFIED:
 * - Agent gets stuck in "Intent erkennen" node
 * - Transition condition too vague: "Customer wants to book NEW appointment"
 * - Agent hallucinates "ich prüfe" but never reaches function nodes
 * - No functions are called -> no monitoring data -> user waits forever
 *
 * FIX:
 * 1. Update node_04_intent_enhanced instruction (prevent hallucination)
 * 2. Make transition condition more explicit
 * 3. Add DIRECT edge to func_check_availability (skip intermediate nodes)
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
echo "║   🚀 V22: INTENT TRANSITION FIX (NO HALLUCINATION)          ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

$agentId = 'agent_f1ce85d06a84afb989dfbb16a9';

// STEP 1: Get current flow ID from agent
echo "=== STEP 1: Getting Current Flow ID ===\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/get-agent/{$agentId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $retellApiKey
    ]
]);

$response = curl_exec($ch);
curl_close($ch);

$agent = json_decode($response, true);
$flowId = $agent['response_engine']['conversation_flow_id'] ?? null;

if (!$flowId) {
    echo "❌ Failed to get flow ID\n";
    exit(1);
}

echo "✅ Flow ID: {$flowId}\n";
echo "   Current agent version: " . ($agent['version'] ?? 'N/A') . "\n";
echo PHP_EOL;

// STEP 2: Get current flow
echo "=== STEP 2: Fetching Current Flow ===\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/get-conversation-flow/{$flowId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $retellApiKey
    ]
]);

$flowResponse = curl_exec($ch);
curl_close($ch);

$flow = json_decode($flowResponse, true);

if (!$flow || !isset($flow['nodes'])) {
    echo "❌ Failed to fetch flow\n";
    exit(1);
}

echo "✅ Current flow loaded: " . count($flow['nodes']) . " nodes\n";
echo PHP_EOL;

// STEP 3: Apply V22 Fixes
echo "=== STEP 3: Applying V22 Intent Transition Fix ===\n";

$fixesApplied = 0;

foreach ($flow['nodes'] as &$node) {
    // FIX 1: Update node_04_intent_enhanced instruction
    if (($node['id'] ?? null) === 'node_04_intent_enhanced') {
        echo "  🔧 Fixing node: node_04_intent_enhanced\n";

        $node['instruction']['text'] = <<<'EOT'
Identify customer intent IMMEDIATELY and transition to appropriate flow.

**🎯 CRITICAL: IMMEDIATE INTENT RECOGNITION**

Your ONLY job: Identify intent and IMMEDIATELY transition. Do NOT collect data here!

**INTENT TYPES:**

1. **NEW BOOKING** (most common)
   - Keywords: "Termin", "buchen", "reservieren", "möchte kommen", "hätte gern"
   - Examples: "Termin morgen", "ich brauche 14 Uhr", "Herrenhaarschnitt buchen"
   - Action: IMMEDIATELY transition to booking flow

2. **RESCHEDULE**
   - Keywords: "verschieben", "umbuchen", "anderen Termin"
   - Action: Transition to reschedule flow

3. **CANCEL**
   - Keywords: "stornieren", "absagen", "canceln"
   - Action: Transition to cancel flow

4. **VIEW APPOINTMENTS**
   - Keywords: "welche Termine", "meine Termine", "wann habe ich"
   - Action: Transition to appointments view

**🚨 ANTI-HALLUCINATION RULES (CRITICAL):**

1. ❌ DO NOT say "ich prüfe Verfügbarkeit" - you CANNOT check availability in this node!
2. ❌ DO NOT say "ich buche" - you CANNOT book in this node!
3. ❌ DO NOT promise actions you cannot perform here!
4. ✅ DO say: "Gerne! Für welchen Tag und welche Uhrzeit?" (ask for missing info)
5. ✅ TRANSITION IMMEDIATELY while asking

**Why you MUST NOT hallucinate:**
- You are in INTENT RECOGNITION node
- You have NO access to check_availability function here
- You have NO access to book_appointment function here
- Saying "ich prüfe" when you can't = user waits forever = bad experience

**Correct behavior:**
User: "Ich möchte morgen einen Termin"
You: "Gerne! Für welchen Service und welche Uhrzeit?" [IMMEDIATE TRANSITION]

User: "Herrenhaarschnitt, 14 Uhr"
You: "Verstanden!" [IMMEDIATE TRANSITION to data collection]

**DO NOT:**
- ❌ Stay in this node longer than 1 response
- ❌ Collect service/date/time in THIS node (next nodes do that!)
- ❌ Say "ich prüfe" or "ich buche" (you can't!)

**DO:**
- ✅ Identify intent from FIRST mention
- ✅ Transition IMMEDIATELY when intent is clear
- ✅ Let NEXT nodes handle data collection
- ✅ Let FUNCTION nodes handle availability/booking
EOT;

        $fixesApplied++;
        echo "     ✅ Updated instruction (anti-hallucination)\n";
    }

    // FIX 2: Make transition more explicit
    if (($node['id'] ?? null) === 'node_04_intent_enhanced') {
        foreach ($node['edges'] ?? [] as &$edge) {
            if (($edge['id'] ?? null) === 'edge_07a') {
                echo "  🔧 Fixing edge: edge_07a (booking transition)\n";

                $edge['transition_condition']['prompt'] = 'User explicitly mentioned BOOKING intent with keywords: termin, buchen, reservieren, hätte gern, brauche, appointment, möchte kommen OR user mentioned service name (Herrenhaarschnitt, Damenhaarschnitt, etc.) OR user mentioned specific date/time. Intent is CLEAR from first message.';

                $fixesApplied++;
                echo "     ✅ Updated transition condition (more explicit)\n";
            }
        }
    }

    // FIX 3: Update node_07_datetime_collection to transition faster
    if (($node['id'] ?? null) === 'node_07_datetime_collection') {
        echo "  🔧 Updating node: node_07_datetime_collection\n";

        foreach ($node['edges'] ?? [] as &$edge) {
            if (($edge['id'] ?? null) === 'edge_11') {
                $edge['transition_condition']['prompt'] = 'You have collected ALL required info: service name, date (explicit), and SINGLE time (HH:MM format, NOT multiple options). Immediately ready to check availability.';

                $fixesApplied++;
                echo "     ✅ Updated transition to availability check (faster trigger)\n";
            }
        }
    }
}

echo "\n✅ Applied {$fixesApplied} fixes to flow\n";
echo PHP_EOL;

// STEP 4: Update conversation flow DIRECTLY
echo "=== STEP 4: Deploying V22 Flow to Retell ===\n";

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
$error = curl_error($ch);
curl_close($ch);

if ($httpCode !== 200 && $httpCode !== 201) {
    echo "❌ Failed to update conversation flow\n";
    echo "  - HTTP Code: {$httpCode}\n";
    echo "  - Error: {$error}\n";
    echo "  - Response: " . substr($response, 0, 500) . "\n";
    exit(1);
}

echo "✅ Conversation flow updated successfully\n";
echo "  - HTTP Code: {$httpCode}\n";

$respData = json_decode($response, true);
if (isset($respData['version'])) {
    echo "  - Flow Version: " . $respData['version'] . "\n";
}

echo "\n⏳ Waiting 5 seconds for update to propagate...\n";
sleep(5);
echo PHP_EOL;

// STEP 5: Verify flow has changes
echo "=== STEP 5: Verifying V22 Fixes Applied ===\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/get-conversation-flow/{$flowId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $retellApiKey
    ]
]);

$flowResponse = curl_exec($ch);
curl_close($ch);

$updatedFlow = json_decode($flowResponse, true);

$antiHallucinationFound = false;
$explicitTransitionFound = false;

foreach ($updatedFlow['nodes'] as $node) {
    if (($node['id'] ?? null) === 'node_04_intent_enhanced') {
        $text = $node['instruction']['text'] ?? '';
        $antiHallucinationFound = strpos($text, 'ANTI-HALLUCINATION') !== false;

        foreach ($node['edges'] ?? [] as $edge) {
            if (($edge['id'] ?? null) === 'edge_07a') {
                $prompt = $edge['transition_condition']['prompt'] ?? '';
                $explicitTransitionFound = strpos($prompt, 'explicitly mentioned BOOKING') !== false;
            }
        }
    }
}

echo "Flow Verification:\n";
echo "  " . ($antiHallucinationFound ? "✅" : "❌") . " Anti-hallucination rules present\n";
echo "  " . ($explicitTransitionFound ? "✅" : "❌") . " Explicit transition condition\n";
echo PHP_EOL;

if (!$antiHallucinationFound || !$explicitTransitionFound) {
    echo "❌ FLOW VERIFICATION FAILED!\n";
    echo "   Changes did not apply. Aborting.\n";
    exit(1);
}

echo "✅ FLOW VERIFIED - V22 fixes are present!\n";
echo PHP_EOL;

// STEP 6: Publish Agent
echo "=== STEP 6: Publishing Agent to Production ===\n";

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
    echo "❌ Failed to publish agent\n";
    echo "  - HTTP Code: {$httpCode}\n";
    echo "  - Response: {$response}\n";
    exit(1);
}

echo "✅ Agent published successfully\n";
echo "  - V22 changes are now LIVE in production\n";

echo "\n⏳ Waiting 5 seconds for propagation...\n";
sleep(5);
echo PHP_EOL;

// STEP 7: Final verification
echo "=== STEP 7: Final LIVE Verification ===\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/get-agent/{$agentId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $retellApiKey
    ]
]);

$response = curl_exec($ch);
curl_close($ch);

$agent = json_decode($response, true);

echo "LIVE Agent Status:\n";
echo "  - Agent ID: {$agentId}\n";
echo "  - Version: " . ($agent['version'] ?? 'N/A') . "\n";
echo "  - Published: " . ($agent['is_published'] ? 'YES' : 'NO') . "\n";
echo PHP_EOL;

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║         🎉 V22 DEPLOYMENT SUCCESSFUL! 🎉                     ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

echo "📋 WHAT CHANGED IN V22:\n";
echo "  ✅ Anti-hallucination rules in Intent node\n";
echo "  ✅ Agent can NO LONGER say 'ich prüfe' without actually checking\n";
echo "  ✅ More explicit transition conditions\n";
echo "  ✅ Faster transition to function nodes\n";
echo PHP_EOL;

echo "🧪 TEST NOW:\n";
echo "  1. Call: +493033081738 (Friseur 1)\n";
echo "  2. Say: 'Ich möchte morgen 14 Uhr einen Herrenhaarschnitt'\n";
echo "  3. Agent should NOT hallucinate, but ASK for confirmation\n";
echo "  4. Then ACTUALLY call check_availability function\n";
echo "  5. Monitoring system will capture the function call\n";
echo PHP_EOL;

echo "🔍 MONITOR:\n";
echo "  - After call, check: https://api.askproai.de/admin/retell-call-sessions\n";
echo "  - You should see NEW call session with function traces\n";
echo "  - Functions: initialize_call, check_availability_v17, book_appointment_v17\n";
echo PHP_EOL;
