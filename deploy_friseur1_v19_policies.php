<?php

/**
 * Deploy Friseur 1 Flow V19 - Add Name & Date Policies
 *
 * Updates Agent: agent_f1ce85d06a84afb989dfbb16a9
 * Changes:
 * 1. Name Policy: Require full name (first AND last)
 * 2. Date Policy: Smart handling of time-only input
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
echo "║   Deploying Friseur 1 Flow V19 - Name & Date Policies       ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

$agentId = 'agent_f1ce85d06a84afb989dfbb16a9'; // Friseur 1
$sourceFile = __DIR__ . '/public/friseur1_flow_complete.json';
$targetFile = __DIR__ . '/public/friseur1_flow_v19_policies.json';

if (!file_exists($sourceFile)) {
    echo "❌ Source flow file not found: {$sourceFile}\n";
    exit(1);
}

echo "📄 Loading source flow...\n";
$flow = json_decode(file_get_contents($sourceFile), true);

if (!$flow) {
    echo "❌ Failed to parse flow JSON\n";
    exit(1);
}

echo "✅ Flow loaded: " . count($flow['nodes']) . " nodes\n";
echo PHP_EOL;

// Update 1: Name Policy - "Name sammeln" node
echo "=== Update 1: Adding Name Policy ===\n";
$nameNodeFound = false;

foreach ($flow['nodes'] as &$node) {
    if ($node['name'] === 'Name sammeln') {
        $nameNodeFound = true;

        $node['instruction'] = [
            'type' => 'prompt',
            'text' => 'Collect full customer name (first AND last name).

**NAME POLICY (CRITICAL):**
- ALWAYS ask for COMPLETE NAME: "Wie ist Ihr vollständiger Name?" or "Vorname und Nachname bitte?"
- If customer gives only first name → IMMEDIATELY ask: "Und Ihr Nachname bitte?"
- NEVER proceed without both first AND last name
- Format: "Vorname Nachname" (e.g., "Max Mustermann")
- Confirm complete name before proceeding

After collecting full name, thank them and proceed to understand their request.'
        ];

        echo "✅ Updated 'Name sammeln' node with Name Policy\n";
        echo "  - Requires: First AND last name\n";
        echo "  - Enforces: Explicit confirmation\n";
        break;
    }
}

if (!$nameNodeFound) {
    echo "⚠️  WARNING: 'Name sammeln' node not found!\n";
}
echo PHP_EOL;

// Update 2: Date Policy - "Datum & Zeit sammeln" node
echo "=== Update 2: Adding Date Policy ===\n";
$dateNodeFound = false;

foreach ($flow['nodes'] as &$node) {
    if ($node['name'] === 'Datum & Zeit sammeln') {
        $dateNodeFound = true;

        $node['instruction'] = [
            'type' => 'prompt',
            'text' => 'Collect preferred date and time for the appointment.

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

If customer already mentioned date/time, confirm it. Otherwise, ask for missing information.'
        ];

        echo "✅ Updated 'Datum & Zeit sammeln' node with Date Policy\n";
        echo "  - Smart inference: Past time → tomorrow\n";
        echo "  - Explicit ask: Future time → heute oder morgen?\n";
        break;
    }
}

if (!$dateNodeFound) {
    echo "⚠️  WARNING: 'Datum & Zeit sammeln' node not found!\n";
}
echo PHP_EOL;

// Save updated flow
echo "=== Saving Updated Flow ===\n";
file_put_contents($targetFile, json_encode($flow, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "✅ Saved to: {$targetFile}\n";
echo "  - Size: " . round(filesize($targetFile) / 1024, 2) . " KB\n";
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
    echo "  - Version: V19 (Name & Date Policies)\n";
} else {
    echo "❌ Failed to publish agent\n";
    echo "  - HTTP Code: {$httpCode}\n";
    echo "  - Response: {$response}\n";
    exit(1);
}
echo PHP_EOL;

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                 DEPLOYMENT SUCCESSFUL                        ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

echo "📋 Summary:\n";
echo "  - Name Policy: ✅ Enforces full name (first + last)\n";
echo "  - Date Policy: ✅ Smart time-only inference\n";
echo "  - Flow Version: V19\n";
echo "  - Status: LIVE\n";
echo PHP_EOL;

echo "🧪 Test the changes:\n";
echo "  1. Call Friseur 1 number\n";
echo "  2. Say only first name → should ask for last name\n";
echo "  3. Say only time (e.g., '14 Uhr') → should ask 'heute oder morgen?'\n";
echo PHP_EOL;
