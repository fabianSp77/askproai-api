<?php
/**
 * Fix "Intent Erkennung" Node - Prevent hallucination
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$flowId = 'conversation_flow_a58405e3f67a';
$apiKey = config('services.retellai.api_key');

echo "=== FIX INTENT ROUTER NODE ===\n\n";

// 1. Get current flow
echo "1. Fetching current flow...\n";
$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $apiKey,
    'Content-Type' => 'application/json'
])->get("https://api.retellai.com/get-conversation-flow/{$flowId}");

if (!$response->successful()) {
    echo "   ❌ Failed to fetch flow: " . $response->status() . "\n";
    echo "   Response: " . $response->body() . "\n";
    exit(1);
}

$flow = $response->json();
$currentVersion = $flow['version'] ?? 'unknown';
echo "   ✅ Current version: {$currentVersion}\n\n";

// 2. Find and update "Intent Erkennung" node
echo "2. Updating 'Intent Erkennung' node...\n";

$nodeFound = false;
foreach ($flow['nodes'] as &$node) {
    if ($node['id'] === 'intent_router') {
        $nodeFound = true;

        echo "   Found node: {$node['name']}\n";
        echo "   Current instruction: " . substr($node['instruction']['text'], 0, 100) . "...\n\n";

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

// 3. Update flow via API
echo "3. Saving updated flow...\n";

$updateResponse = Http::withHeaders([
    'Authorization' => 'Bearer ' . $apiKey,
    'Content-Type' => 'application/json'
])->patch("https://api.retellai.com/update-conversation-flow/{$flowId}", [
    'nodes' => $flow['nodes'],
    'edges' => $flow['edges'] ?? [],
    'tools' => $flow['tools'] ?? [],
    'global_prompt' => $flow['global_prompt'] ?? '',
    'start_node_id' => $flow['start_node_id'] ?? 'node_greeting',
    'model_choice' => $flow['model_choice'] ?? [
        'type' => 'cascading',
        'model' => 'gpt-4.1-mini'
    ],
    'model_temperature' => $flow['model_temperature'] ?? 0.3
]);

if (!$updateResponse->successful()) {
    echo "   ❌ Failed to update flow: " . $updateResponse->status() . "\n";
    echo "   Response: " . $updateResponse->body() . "\n";
    exit(1);
}

$updatedFlow = $updateResponse->json();
$newVersion = $updatedFlow['version'] ?? 'unknown';

echo "   ✅ Flow updated successfully!\n";
echo "   New version: V{$newVersion}\n\n";

// 4. Verify the change
echo "4. Verifying changes...\n";

$verifyResponse = Http::withHeaders([
    'Authorization' => 'Bearer ' . $apiKey,
    'Content-Type' => 'application/json'
])->get("https://api.retellai.com/get-conversation-flow/{$flowId}");

if ($verifyResponse->successful()) {
    $verifiedFlow = $verifyResponse->json();

    foreach ($verifiedFlow['nodes'] as $node) {
        if ($node['id'] === 'intent_router') {
            $instruction = $node['instruction']['text'];

            if (strpos($instruction, 'STUMMER ROUTER') !== false) {
                echo "   ✅ Changes verified successfully!\n";
                echo "   Node instruction contains: 'STUMMER ROUTER'\n\n";
            } else {
                echo "   ❌ Changes NOT found in verified flow\n";
                echo "   Instruction: " . substr($instruction, 0, 100) . "...\n\n";
                exit(1);
            }
            break;
        }
    }
}

echo "=== FIX COMPLETE ===\n\n";
echo "✅ Flow updated: V{$currentVersion} → V{$newVersion}\n";
echo "✅ Node 'Intent Erkennung' instruction fixed\n";
echo "✅ Changes verified\n\n";

echo "NEXT STEP:\n";
echo "User should publish V{$newVersion} in Retell Dashboard\n\n";

echo "What was fixed:\n";
echo "- 'Intent Erkennung' node now acts as SILENT ROUTER\n";
echo "- Prevents agent from hallucinating availability\n";
echo "- Forces immediate transition without answering\n";
