<?php

/**
 * Deploy Friseur 1 Flow V22 - INTENT RECOGNITION FIX
 *
 * Updates Agent: agent_f1ce85d06a84afb989dfbb16a9
 *
 * ROOT CAUSE:
 * - node_04_intent_enhanced has vague instruction
 * - Edge condition "Customer wants to book NEW appointment" not recognized
 * - Agent stays stuck in intent node even when intent is clear
 *
 * FIX:
 * - Make intent recognition EXPLICIT
 * - Add immediate transition trigger
 * - Clarify when to move to next node
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
echo "║   🚀 V22: Intent Recognition Fix                            ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

$agentId = 'agent_f1ce85d06a84afb989dfbb16a9';
$sourceFile = __DIR__ . '/public/friseur1_flow_v21_CORRECT.json';
$targetFile = __DIR__ . '/public/friseur1_flow_v22_intent_fix.json';

if (!file_exists($sourceFile)) {
    echo "❌ Source flow file not found: {$sourceFile}\n";
    exit(1);
}

echo "📄 Loading V21 CORRECT flow...\n";
$flow = json_decode(file_get_contents($sourceFile), true);

if (!$flow) {
    echo "❌ Failed to parse flow JSON\n";
    exit(1);
}

echo "✅ V21 Flow loaded: " . count($flow['nodes']) . " nodes\n";
echo PHP_EOL;

// Fix: Update node_04_intent_enhanced instruction
echo "=== FIX: Intent Recognition (node_04_intent_enhanced) ===\n";
$intentNodeFound = false;

foreach ($flow['nodes'] as &$node) {
    if (($node['id'] ?? null) === 'node_04_intent_enhanced') {
        $intentNodeFound = true;

        $node['instruction'] = [
            'type' => 'prompt',
            'text' => 'Identify customer intent IMMEDIATELY and transition to appropriate flow.

**🎯 CRITICAL: IMMEDIATE INTENT RECOGNITION**

Your ONLY job: Identify intent and IMMEDIATELY transition. Do NOT collect data here!

**INTENT TYPES:**

1. **NEW BOOKING** (most common)
   - Keywords: "Termin", "buchen", "reservieren", "möchte kommen", "hätte gern"
   - Examples: "Termin morgen", "ich brauche 14 Uhr", "Herrenhaarschnitt buchen"
   - Action: IMMEDIATELY transition to booking flow (Edge → node_06_service_selection)

2. **RESCHEDULE**
   - Keywords: "verschieben", "umbuchen", "anderen Termin"
   - Examples: "meinen Termin verschieben", "kann ich umbuchen"
   - Action: Transition to reschedule flow

3. **CANCEL**
   - Keywords: "stornieren", "absagen", "canceln"
   - Examples: "Termin absagen", "stornieren bitte"
   - Action: Transition to cancel flow

4. **VIEW APPOINTMENTS**
   - Keywords: "welche Termine", "meine Termine", "wann habe ich"
   - Examples: "welche Termine habe ich", "wann war mein letzter Termin"
   - Action: Transition to appointments view

**🚨 CRITICAL RULES:**

1. If customer mentions service + time/date → INTENT = NEW BOOKING → Transition IMMEDIATELY
2. Do NOT start collecting data in this node
3. Do NOT say "Einen Moment" or "ich prüfe"
4. Your response: Acknowledge intent and ask what is MISSING:
   - If they said service but not time → ask for time
   - If they said time but not service → ask for service
5. As soon as you acknowledge → TRANSITION (even while speaking!)

**TRANSITION TRIGGERS:**

- Intent = NEW BOOKING → Ready when you understand they want new appointment
- Intent = RESCHEDULE → Ready when they mention changing appointment
- Intent = CANCEL → Ready when they want to cancel
- Intent = VIEW → Ready when they ask about appointments

**Example Flow:**
User: "Ich möchte morgen einen Termin"
You: "Gerne! Für welchen Service?" [WHILE speaking, transition to node_06_service_selection]

User: "Herrenhaarschnitt, 14 Uhr"
You: "Super!" [IMMEDIATE transition to booking flow]

**DO NOT:**
- ❌ Collect service/date/time in THIS node
- ❌ Say "ich prüfe Verfügbarkeit" (that happens LATER!)
- ❌ Stay in this node longer than 1 response
- ❌ Wait for complete information before transitioning

**DO:**
- ✅ Identify intent from FIRST mention
- ✅ Transition IMMEDIATELY when intent is clear
- ✅ Let NEXT node handle data collection'
        ];

        echo "✅ Updated 'node_04_intent_enhanced' instruction\n";
        echo "  - Old: Vague 'understand what customer wants'\n";
        echo "  - New: EXPLICIT intent recognition with immediate transition\n";
        echo "  - Fix: Agent will transition IMMEDIATELY when intent = new booking\n";
        break;
    }
}
unset($node);

if (!$intentNodeFound) {
    echo "⚠️  WARNING: 'node_04_intent_enhanced' node not found!\n";
}
echo PHP_EOL;

// Save updated flow
echo "=== Saving V22 Flow ===\n";
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
    echo "  - Version: V22 (Intent Recognition Fix)\n";

    // Wait for propagation
    echo "\nWaiting 3 seconds for propagation...\n";
    sleep(3);
} else {
    echo "❌ Failed to publish agent\n";
    echo "  - HTTP Code: {$httpCode}\n";
    echo "  - Error: {$error}\n";
    echo "  - Response: {$response}\n";
    exit(1);
}
echo PHP_EOL;

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║           V22 DEPLOYMENT COMPLETED                           ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

echo "📋 Fixes Deployed:\n";
echo "  1. ✅ V20: Anti-Hallucination Policy (preserved)\n";
echo "  2. ✅ V21: Greeting Fix (preserved)\n";
echo "  3. ✅ V21: Multiple Time Policy (preserved)\n";
echo "  4. ✅ V22: Intent Recognition Fix (NEW)\n";
echo PHP_EOL;

echo "🧪 Test Now:\n";
echo "  Call: +493033081738\n";
echo "  Say: 'Ich möchte morgen einen Herrenhaarschnitt um 14 Uhr'\n";
echo "  Expected: Agent IMMEDIATELY transitions to booking flow\n";
echo "  Expected: Availability check happens AUTOMATICALLY\n";
echo "  Expected: NO MORE stuck in Intent node!\n";
echo PHP_EOL;
