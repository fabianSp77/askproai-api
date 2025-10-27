<?php

/**
 * Deploy Friseur 1 Flow V18 to Retell Agent
 *
 * Updates Agent: agent_f1ce85d06a84afb989dfbb16a9
 * With: Composite Services + Staff Preference Support
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
echo "║     Deploying Friseur 1 Flow V18 to Retell                  ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

$agentId = 'agent_f1ce85d06a84afb989dfbb16a9'; // Friseur 1
$flowFile = __DIR__ . '/public/friseur1_flow_complete.json';

if (!file_exists($flowFile)) {
    echo "❌ Flow file not found: {$flowFile}\n";
    exit(1);
}

echo "📄 Loading V18 flow...\n";
$flow = json_decode(file_get_contents($flowFile), true);

if (!$flow) {
    echo "❌ Failed to parse V18 JSON\n";
    exit(1);
}

echo "✅ Flow loaded:\n";
echo "  - Nodes: " . count($flow['nodes']) . "\n";
echo "  - Tools: " . count($flow['tools']) . "\n";
echo "  - File: " . round(filesize($flowFile) / 1024, 2) . " KB\n";
echo PHP_EOL;

// Step 1: Update Agent with new conversation flow
echo "=== Step 1: Update Agent Conversation Flow ===\n";

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

    $responseData = json_decode($response, true);
    if ($responseData && isset($responseData['conversation_flow'])) {
        echo "  - Flow nodes: " . count($responseData['conversation_flow']['nodes']) . "\n";
        echo "  - Flow tools: " . count($responseData['conversation_flow']['tools']) . "\n";
    }
} else {
    echo "❌ Failed to update agent\n";
    echo "  - HTTP Code: {$httpCode}\n";
    echo "  - Response: {$response}\n";
    exit(1);
}
echo PHP_EOL;

// Step 2: PUBLISH Agent (CRITICAL - makes changes live!)
echo "=== Step 2: PUBLISH Agent (Making Changes Live) ===\n";

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
    echo "✅ Agent PUBLISHED successfully\n";
    echo "  - HTTP Code: {$httpCode}\n";
    echo "  - Changes are now LIVE in production!\n";
} else {
    echo "❌ Failed to publish agent\n";
    echo "  - HTTP Code: {$httpCode}\n";
    echo "  - Response: {$response}\n";
    echo "⚠️  Agent updated but NOT published - changes not visible!\n";
    exit(1);
}
echo PHP_EOL;

// Step 3: Verify the update
echo "=== Step 3: Verify Agent Configuration ===\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/get-agent/{$agentId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $retellApiKey,
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $agent = json_decode($response, true);

    echo "✅ Agent configuration retrieved:\n";
    echo "  - Agent ID: " . ($agent['id'] ?? 'N/A') . "\n";
    echo "  - Agent Name: " . ($agent['agent_name'] ?? 'N/A') . "\n";

    // Check if conversation_flow exists
    if (isset($agent['conversation_flow'])) {
        echo "  - Conversation Flow: ✅ Present\n";
        echo "  - Flow Nodes: " . count($agent['conversation_flow']['nodes']) . "\n";
        echo "  - Flow Tools: " . count($agent['conversation_flow']['tools']) . "\n";

        // Verify mitarbeiter parameter exists
        $hasMitarbeiter = false;
        foreach ($agent['conversation_flow']['tools'] as $tool) {
            if ($tool['name'] === 'book_appointment_v17') {
                if (isset($tool['parameters']['properties']['mitarbeiter'])) {
                    $hasMitarbeiter = true;
                    echo "  - 'mitarbeiter' parameter: ✅ Present\n";
                    break;
                }
            }
        }

        if (!$hasMitarbeiter) {
            echo "  - 'mitarbeiter' parameter: ❌ NOT FOUND\n";
        }
    } else {
        echo "  - Conversation Flow: ❌ Missing\n";
    }
} else {
    echo "⚠️ Could not verify agent configuration\n";
    echo "  - HTTP Code: {$httpCode}\n";
}
echo PHP_EOL;

// Summary
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                    DEPLOYMENT SUMMARY                        ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

echo "✅ V18 Flow Deployed to Friseur 1 Agent\n";
echo PHP_EOL;

echo "Agent Details:\n";
echo "  - Agent ID: {$agentId}\n";
echo "  - Dashboard: https://dashboard.retellai.com/agents/{$agentId}\n";
echo PHP_EOL;

echo "V18 Features:\n";
echo "  ✅ Composite Services Support\n";
echo "     - Ansatzfärbung with wait time explanations\n";
echo "     - Natural handling of ~2-3h services\n";
echo PHP_EOL;

echo "  ✅ Staff Preference Support\n";
echo "     - 'mitarbeiter' parameter in book_appointment_v17\n";
echo "     - Customers can request specific staff (\"bei Fabian\")\n";
echo "     - Backend maps names to staff IDs\n";
echo PHP_EOL;

echo "  ✅ Team Information\n";
echo "     - Agent knows all 5 Friseur 1 staff members\n";
echo "     - Emma, Fabian, David, Michael, Dr. Sarah\n";
echo PHP_EOL;

echo "📌 Next Step: Test with voice call\n";
echo "   Test scenarios:\n";
echo "   1. \"Ansatzfärbung morgen um 14 Uhr\"\n";
echo "   2. \"Ansatzfärbung bei Fabian morgen 14 Uhr\"\n";
echo "   3. \"Herrenhaarschnitt bei Emma übermorgen\"\n";
echo PHP_EOL;

echo "✅ Deployment: COMPLETE\n";
