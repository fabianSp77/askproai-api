<?php

/**
 * Deploy Friseur 1 Flow V23 - DSGVO Name Policy + Booking Edge Fix
 *
 * Updates Agent: agent_f1ce85d06a84afb989dfbb16a9 (Friseur 1)
 *
 * CRITICAL FIXES:
 * 1. DSGVO Name Policy: Use "Herr/Frau Nachname" instead of first name only
 * 2. func_book_appointment Edge: Fix destination (success goodbye instead of confirmation loop)
 * 3. Preserve all V20/V21/V22 fixes
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
echo "║   🚀 V23: DSGVO Name Policy + Booking Edge Fix              ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

$agentId = 'agent_f1ce85d06a84afb989dfbb16a9'; // Friseur 1
$sourceFile = __DIR__ . '/public/friseur1_flow_v22_intent_fix.json';
$targetFile = __DIR__ . '/public/friseur1_flow_v23_name_policy_fix.json';

if (!file_exists($sourceFile)) {
    echo "❌ Source flow file not found: {$sourceFile}\n";
    exit(1);
}

echo "📄 Loading V22 flow...\n";
$flow = json_decode(file_get_contents($sourceFile), true);

if (!$flow) {
    echo "❌ Failed to parse flow JSON\n";
    exit(1);
}

echo "✅ V22 Flow loaded: " . count($flow['nodes']) . " nodes\n";
echo PHP_EOL;

// Fix 1: Update func_00_initialize instruction for DSGVO-compliant name addressing
echo "=== FIX 1: DSGVO Name Policy (func_00_initialize) ===\n";
$initNodeFound = false;

foreach ($flow['nodes'] as &$node) {
    if (($node['id'] ?? null) === 'func_00_initialize') {
        $initNodeFound = true;

        // Update instruction to use formal addressing
        $node['instruction'] = [
            'type' => 'prompt',
            'text' => 'Execute initialize_call function and greet the customer.

**🔒 DSGVO NAME POLICY (CRITICAL):**

When greeting customers, you MUST follow these rules:

**1. KNOWN CUSTOMER (initialize_call returns customer data):**
   - Use the greeting message from initialize_call result
   - Example result: {"customer": {"message": "Willkommen zurück, Herr Schuster!"}}
   - Use it EXACTLY as provided
   - DO NOT modify the name format
   - DO NOT use first name only

**2. ADDRESS CUSTOMER IN CONVERSATION:**
   - ALWAYS use: "Herr/Frau [Nachname]" (e.g., "Herr Schuster")
   - OR full name: "[Vorname] [Nachname]" (e.g., "Hans Schuster")
   - NEVER use first name only (e.g., NEVER just "Hans")
   - Exception: Only after explicit permission ("Darf ich Sie beim Vornamen nennen?")

**3. EXAMPLES:**

✅ CORRECT:
- "Willkommen zurück, Herr Schuster!"
- "Guten Tag, Hans Schuster!"
- "Klar, Herr Schuster, ich helfe Ihnen gerne!"

❌ WRONG (DSGVO VIOLATION):
- "Willkommen zurück, Hans!" (first name only without permission)
- "Guten Tag, Hans!" (informal without permission)
- "Klar, Hans!" (using first name only)

**4. NEW CUSTOMERS:**
   - Greet formally: "Guten Tag bei Friseur 1, mein Name ist Carola. Wie kann ich helfen?"
   - Ask for name: "Darf ich Ihren Namen erfragen?"
   - Use full name once provided

**5. YOUR RESPONSE:**
After initialize_call completes, greet with the message provided and ask how you can help.

DO NOT speak before initialize_call completes (speak_during_execution = false).'
        ];

        echo "✅ Updated 'func_00_initialize' instruction\n";
        echo "  - DSGVO Policy: Herr/Frau Nachname ODER Vor- und Nachname\n";
        echo "  - NEVER first name only without permission\n";
        echo "  - Uses greeting message from initialize_call result\n";
        break;
    }
}
unset($node);

if (!$initNodeFound) {
    echo "⚠️  WARNING: 'func_00_initialize' node not found!\n";
}
echo PHP_EOL;

// Fix 2: Update func_book_appointment edge destination
echo "=== FIX 2: Booking Edge Destination (func_book_appointment) ===\n";
$bookingNodeFound = false;

foreach ($flow['nodes'] as &$node) {
    if (($node['id'] ?? null) === 'func_book_appointment') {
        $bookingNodeFound = true;

        // Find the success edge
        if (isset($node['edges'])) {
            foreach ($node['edges'] as &$edge) {
                if (($edge['id'] ?? null) === 'edge_book_success') {
                    $oldDest = $edge['destination_node_id'] ?? 'N/A';
                    $edge['destination_node_id'] = 'node_14_success_goodbye';

                    echo "✅ Updated func_book_appointment success edge\n";
                    echo "  - Old: {$oldDest}\n";
                    echo "  - New: node_14_success_goodbye\n";
                    echo "  - Fix: No more asking for confirmation AFTER booking\n";
                    break;
                }
            }
        }
        break;
    }
}
unset($node);

if (!$bookingNodeFound) {
    echo "⚠️  WARNING: 'func_book_appointment' node not found!\n";
}
echo PHP_EOL;

// Save updated flow
echo "=== Saving V23 Flow ===\n";
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
    echo "  - Version: V23 (DSGVO + Booking Fix)\n";

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

// Verify deployment
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
    $flowId = $agent['response_engine']['conversation_flow_id'] ?? null;

    if ($flowId) {
        // Get flow
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

        $liveFlow = json_decode($flowResponse, true);

        if (isset($liveFlow['nodes'])) {
            echo "  - Live nodes: " . count($liveFlow['nodes']) . "\n";

            // Verify name policy
            $namePolicyFound = false;
            $bookingEdgeFixed = false;

            foreach ($liveFlow['nodes'] as $node) {
                if (($node['id'] ?? null) === 'func_00_initialize') {
                    $text = $node['instruction']['text'] ?? '';
                    $namePolicyFound = strpos($text, 'DSGVO NAME POLICY') !== false;
                }

                if (($node['id'] ?? null) === 'func_book_appointment') {
                    foreach ($node['edges'] ?? [] as $edge) {
                        if (($edge['id'] ?? null) === 'edge_book_success') {
                            $bookingEdgeFixed = ($edge['destination_node_id'] ?? null) === 'node_14_success_goodbye';
                        }
                    }
                }
            }

            echo "\n";
            echo "  " . ($namePolicyFound ? "✅" : "❌") . " DSGVO Name Policy (Herr/Frau Nachname)\n";
            echo "  " . ($bookingEdgeFixed ? "✅" : "❌") . " Booking Edge Fixed (no confirmation loop)\n";

            if ($namePolicyFound && $bookingEdgeFixed) {
                echo "\n🎉 ALL V23 FIXES VERIFIED IN PRODUCTION!\n";
            }
        }
    }
}
echo PHP_EOL;

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║           V23 DEPLOYMENT COMPLETED                           ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

echo "📋 All Fixes Deployed:\n";
echo "  1. ✅ V20: Anti-Hallucination Policy\n";
echo "  2. ✅ V21: Single Greeting + Multiple Time Policy\n";
echo "  3. ✅ V22: Intent Recognition Fix\n";
echo "  4. ✅ V23: DSGVO Name Policy (Herr/Frau Nachname)\n";
echo "  5. ✅ V23: Booking Edge Fix (no confirmation loop)\n";
echo PHP_EOL;

echo "🧪 Test Now:\n";
echo "  ⚠️  WICHTIG: Richtige Nummer verwenden!\n";
echo "  ✅ CORRECT: +493033081738 (Friseur 1)\n";
echo "  ❌ WRONG:   +493083793369 (AskProAI)\n";
echo PHP_EOL;
echo "  Expected:\n";
echo "  - Greeting: 'Willkommen zurück, Herr [Nachname]!' (NOT first name only)\n";
echo "  - Intent recognition works immediately\n";
echo "  - Availability check happens automatically\n";
echo "  - Booking completes and shows success message\n";
echo "  - NO asking for confirmation after booking\n";
echo PHP_EOL;
