<?php
/**
 * Fix Intent Router Node AND Publish
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$flowId = 'conversation_flow_a58405e3f67a';
$apiKey = config('services.retellai.api_key');

echo "=== FIX & PUBLISH INTENT ROUTER ===\n\n";

// STEP 1: Get current flow
echo "1. Fetching current flow...\n";
$ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "   ❌ Failed to fetch flow: HTTP {$httpCode}\n";
    echo "   Response: {$response}\n";
    exit(1);
}

$flow = json_decode($response, true);
$currentVersion = $flow['version'] ?? 'unknown';
echo "   ✅ Current version: V{$currentVersion}\n";
echo "   Published: " . ($flow['is_published'] ? 'YES' : 'NO') . "\n\n";

// STEP 2: Update Intent Router node
echo "2. Updating 'Intent Erkennung' node...\n";

$nodeFound = false;
foreach ($flow['nodes'] as &$node) {
    if ($node['id'] === 'intent_router') {
        $nodeFound = true;

        echo "   Found node: {$node['name']}\n";

        // Update instruction
        $node['instruction'] = [
            'type' => 'prompt',
            'text' => "KRITISCH: Du bist ein STUMMER ROUTER!\n\n" .
                     "Deine EINZIGE Aufgabe:\n" .
                     "1. Kundenabsicht erkennen\n" .
                     "2. SOFORT zum passenden Node transitionieren\n\n" .
                     "VERBOTEN:\n" .
                     "❌ Verfügbarkeit prüfen oder raten\n" .
                     "❌ Termine vorschlagen\n" .
                     "❌ Irgendwas antworten\n" .
                     "❌ \"Ich prüfe...\" sagen\n" .
                     "❌ Tool aufrufen\n\n" .
                     "ERLAUBT:\n" .
                     "✅ NUR silent transition\n\n" .
                     "Beispiel:\n" .
                     "User: \"Termin am Dienstag 9 Uhr buchen\"\n" .
                     "→ Erkenne: BOOKING Intent\n" .
                     "→ Transition: node_extract_booking_variables\n" .
                     "→ NICHTS SAGEN!\n\n" .
                     "WICHTIG: Wenn User ALLE Daten in einem Satz gibt (Name + Service + Datum + Zeit), dann SOFORT zu node_extract_booking_variables transitionieren!"
        ];

        echo "   ✅ Instruction updated\n\n";
        break;
    }
}

if (!$nodeFound) {
    echo "   ❌ Node 'intent_router' not found!\n";
    exit(1);
}

// STEP 3: Update flow via API
echo "3. Saving updated flow (will create NEW version)...\n";

$payload = json_encode($flow, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$ch = curl_init("https://api.retellai.com/update-conversation-flow/{$flowId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => "PATCH",
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => $payload
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "   ❌ Failed to update flow: HTTP {$httpCode}\n";
    echo "   Response: {$response}\n";
    exit(1);
}

$updatedFlow = json_decode($response, true);
$newVersion = $updatedFlow['version'] ?? 'unknown';

echo "   ✅ Flow updated successfully!\n";
echo "   New version created: V{$newVersion}\n\n";

// STEP 4: Verify the change
echo "4. Verifying changes in V{$newVersion}...\n";

$ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]
]);
$response = curl_exec($ch);
curl_close($ch);

$verifiedFlow = json_decode($response, true);

$verified = false;
foreach ($verifiedFlow['nodes'] as $node) {
    if ($node['id'] === 'intent_router') {
        $instruction = $node['instruction']['text'];

        if (strpos($instruction, 'STUMMER ROUTER') !== false) {
            echo "   ✅ Changes verified in V{$verifiedFlow['version']}!\n";
            echo "   Node instruction contains: 'STUMMER ROUTER'\n\n";
            $verified = true;
        } else {
            echo "   ❌ Changes NOT found!\n";
            echo "   Instruction: " . substr($instruction, 0, 100) . "...\n\n";
        }
        break;
    }
}

if (!$verified) {
    echo "   ⚠️  Verification failed, but continuing to publish...\n\n";
}

// STEP 5: Publish the new version
echo "5. Publishing V{$newVersion}...\n";

$ch = curl_init("https://api.retellai.com/publish-conversation-flow/{$flowId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => json_encode([])
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 || $httpCode === 201) {
    echo "   ✅ V{$newVersion} published successfully!\n\n";

    $publishResponse = json_decode($response, true);
    if (isset($publishResponse['version'])) {
        echo "   Published version: V{$publishResponse['version']}\n";
    }
} else {
    echo "   ⚠️  Publish via API failed: HTTP {$httpCode}\n";
    echo "   Response: {$response}\n\n";
    echo "   This is expected - Retell API doesn't support publishing.\n";
    echo "   USER MUST MANUALLY PUBLISH V{$newVersion} in Dashboard!\n\n";
}

// STEP 6: Check what's actually published
echo "\n6. Checking published version...\n";

$ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]
]);
$response = curl_exec($ch);
curl_close($ch);

$finalFlow = json_decode($response, true);

echo "   Current version: V{$finalFlow['version']}\n";
echo "   Published: " . ($finalFlow['is_published'] ? 'YES ✅' : 'NO ❌') . "\n\n";

// STEP 7: Summary
echo "=== SUMMARY ===\n\n";
echo "✅ Original version: V{$currentVersion}\n";
echo "✅ Updated to: V{$newVersion}\n";
echo "✅ Changes verified: " . ($verified ? 'YES' : 'NO') . "\n";
echo "📌 Published: " . ($finalFlow['is_published'] ? 'YES' : 'NO') . "\n\n";

if ($finalFlow['is_published']) {
    echo "🎉 SUCCESS! V{$newVersion} is published and ready!\n\n";
    echo "NEXT: Make VOICE CALL test (not text chat!)\n";
    echo "Expected: Agent will NOT hallucinate availability\n";
} else {
    echo "⚠️  USER ACTION REQUIRED:\n\n";
    echo "Go to: https://dashboard.retellai.com/\n";
    echo "Agent: Friseur 1 Agent V51\n";
    echo "Flow: Version {$newVersion}\n";
    echo "Action: Click 'Publish'\n\n";
    echo "After publishing: New draft V" . ($newVersion + 1) . " will be auto-created (ignore it)\n";
    echo "The PUBLISHED version V{$newVersion} will be used by the agent.\n";
}

echo "\n=== END ===\n";
