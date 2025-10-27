<?php

/**
 * Deploy Friseur 1 Flow V20 - Anti-Hallucination Policy
 *
 * Updates Agent: agent_f1ce85d06a84afb989dfbb16a9
 * Critical Fix: Prevent LLM from inventing availability without API check
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
echo "║   🚨 CRITICAL FIX: V20 Anti-Hallucination Policy             ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

$agentId = 'agent_f1ce85d06a84afb989dfbb16a9'; // Friseur 1
$sourceFile = __DIR__ . '/public/friseur1_flow_v19_policies.json';
$targetFile = __DIR__ . '/public/friseur1_flow_v20_anti_hallucination.json';

if (!file_exists($sourceFile)) {
    echo "❌ Source flow file not found: {$sourceFile}\n";
    exit(1);
}

echo "📄 Loading V19 flow...\n";
$flow = json_decode(file_get_contents($sourceFile), true);

if (!$flow) {
    echo "❌ Failed to parse flow JSON\n";
    exit(1);
}

echo "✅ Flow loaded: " . count($flow['nodes']) . " nodes\n";
echo PHP_EOL;

// Update: "Datum & Zeit sammeln" node with ANTI-HALLUCINATION policy
echo "=== CRITICAL FIX: Anti-Hallucination Policy ===\n";
$dateNodeFound = false;

foreach ($flow['nodes'] as &$node) {
    if ($node['name'] === 'Datum & Zeit sammeln') {
        $dateNodeFound = true;

        $node['instruction'] = [
            'type' => 'prompt',
            'text' => 'Collect preferred date and time for the appointment.

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

**WHY THIS MATTERS:**
Without this rule, you will INVENT/HALLUCINATE availability answers.
This destroys user trust and causes wrong bookings.

**DATE POLICY (CRITICAL):**
When customer provides only a TIME without a DATE (e.g., "14 Uhr"):
- Check current time
- If the requested time has ALREADY PASSED today → automatically assume TOMORROW
- If the requested time is STILL IN THE FUTURE today → ASK for clarification:
  "Meinen Sie heute um [TIME] Uhr oder morgen?"
- NEVER assume "today" without explicit confirmation when time is ambiguous

**COMPLETE INFORMATION:**
- Date: Must be explicit (e.g., "heute", "morgen", "Montag", "20.10.2025")
- Time: Must be specific (e.g., "14:00", "14 Uhr")
- Confirm both date AND time before proceeding

**TRANSITION READINESS:**
Once you have BOTH date AND time → indicate readiness to check availability.
The system will automatically transition to func_check_availability node.

If customer already mentioned date/time, confirm it. Otherwise, ask for missing information.'
        ];

        echo "✅ Updated 'Datum & Zeit sammeln' node with Anti-Hallucination Policy\n";
        echo "  - Strict rule: NEVER say 'verfügbar' or 'nicht verfügbar' without API\n";
        echo "  - Enforces: Acknowledge availability questions, don't answer them\n";
        echo "  - Prevents: LLM inventing/hallucinating availability data\n";
        break;
    }
}

if (!$dateNodeFound) {
    echo "⚠️  WARNING: 'Datum & Zeit sammeln' node not found!\n";
    exit(1);
}
echo PHP_EOL;

// Save updated flow
echo "=== Saving V20 Flow ===\n";
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
    CURLOPT_POSTFIELDS => json_encode($updatePayload)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 || $httpCode === 201) {
    echo "✅ Agent conversation flow updated successfully\n";
    echo "  - Agent ID: {$agentId}\n";
    echo "  - HTTP Code: {$httpCode}\n";
} else {
    echo "❌ Failed to update agent\n";
    echo "  - HTTP Code: {$httpCode}\n";
    echo "  - Response: {$response}\n";
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
curl_close($ch);

if ($httpCode === 200 || $httpCode === 201) {
    echo "✅ Agent published successfully\n";
    echo "  - Changes are now LIVE\n";
    echo "  - Version: V20 (Anti-Hallucination)\n";
} else {
    echo "❌ Failed to publish agent\n";
    echo "  - HTTP Code: {$httpCode}\n";
    echo "  - Response: {$response}\n";
    exit(1);
}
echo PHP_EOL;

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║           🚨 CRITICAL FIX DEPLOYED SUCCESSFULLY              ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

echo "📋 What Changed:\n";
echo "  - Node: 'Datum & Zeit sammeln'\n";
echo "  - Fix: Agent can NO LONGER invent availability without API check\n";
echo "  - Policy: STRICT rules against saying 'verfügbar'/'nicht verfügbar'\n";
echo "  - Behavior: Agent acknowledges questions but doesn't answer without data\n";
echo PHP_EOL;

echo "🧪 Test Now:\n";
echo "  1. Call Friseur 1 number\n";
echo "  2. Say: 'Ich möchte morgen einen Termin'\n";
echo "  3. Say: 'Haben Sie 9 Uhr oder 10 Uhr frei?'\n";
echo "  4. Expected: Agent says 'Ich prüfe das für Sie' (NOT 'nicht verfügbar')\n";
echo "  5. Expected: System makes REAL API call before answering\n";
echo PHP_EOL;

echo "📄 Documentation:\n";
echo "  - Analysis: CRITICAL_BUG_AVAILABILITY_HALLUCINATION_2025-10-23.md\n";
echo "  - Phase 1: PHASE_1_COMPLETE_SERVICE_DATE_FIXES_2025-10-23.md\n";
echo PHP_EOL;
