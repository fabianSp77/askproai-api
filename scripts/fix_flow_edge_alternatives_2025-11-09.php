<?php
/**
 * Fix Conversation Flow Edge #3 - Add missing destination_node_id
 * Hole aktuellen Flow, füge fehlende Edge hinzu, upload via API
 */

$flowId = 'conversation_flow_a58405e3f67a';
$apiKey = 'key_6ff998ba48e842092e04a5455d19'; // RETELL_TOKEN from .env

echo "=== Conversation Flow Edge Fix ===\n\n";

// 1. Fetch current flow from API
echo "1. Fetching current flow from API...\n";
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
    die("❌ Failed to fetch flow. HTTP {$httpCode}\nResponse: {$response}\n");
}

$flow = json_decode($response, true);
echo "✅ Fetched Flow Version {$flow['version']}\n\n";

// 2. Find node_present_result
echo "2. Finding node_present_result...\n";
$nodeIndex = null;
foreach ($flow['nodes'] as $index => $node) {
    if ($node['id'] === 'node_present_result') {
        $nodeIndex = $index;
        break;
    }
}

if ($nodeIndex === null) {
    die("❌ node_present_result not found!\n");
}

echo "✅ Found node_present_result at index {$nodeIndex}\n";
echo "Current edges count: " . count($flow['nodes'][$nodeIndex]['edges']) . "\n\n";

// 3. Analyze existing edges
echo "3. Current edges:\n";
foreach ($flow['nodes'][$nodeIndex]['edges'] as $idx => $edge) {
    echo "  Edge #{$idx}: {$edge['id']}\n";
    echo "    Destination: " . ($edge['destination_node_id'] ?? 'MISSING') . "\n";
    echo "    Condition: " . substr($edge['transition_condition']['prompt'] ?? 'N/A', 0, 50) . "...\n\n";
}

// 4. Add/fix Edge #2 (alternatives in response)
echo "4. Adding/fixing Edge #2 (alternatives in response → node_present_alternatives)...\n";

// Check if edge already exists
$edge2Exists = false;
$edge2Index = null;
foreach ($flow['nodes'][$nodeIndex]['edges'] as $idx => $edge) {
    $prompt = $edge['transition_condition']['prompt'] ?? '';
    if (strpos($prompt, 'alternatives array is not empty') !== false) {
        $edge2Exists = true;
        $edge2Index = $idx;
        echo "Found existing Edge #2 at index {$idx}\n";
        break;
    }
}

if ($edge2Exists && $edge2Index !== null) {
    // Fix existing edge - add destination_node_id
    echo "Repairing existing edge...\n";
    $flow['nodes'][$nodeIndex]['edges'][$edge2Index]['destination_node_id'] = 'node_present_alternatives';
    echo "✅ Added destination_node_id to existing edge\n";
} else {
    // Add new edge
    echo "Creating new Edge #2...\n";
    $newEdge = [
        'id' => 'edge_result_to_alternatives_' . time(),
        'destination_node_id' => 'node_present_alternatives',
        'transition_condition' => [
            'type' => 'prompt',
            'prompt' => 'Alternativen im Tool-Response: Tool returned available:false BUT alternatives array is not empty (alternatives were provided in response)'
        ]
    ];

    // Insert at position 1 (between exact match and no alternatives)
    array_splice($flow['nodes'][$nodeIndex]['edges'], 1, 0, [$newEdge]);
    echo "✅ Added new Edge #2\n";
}

// 5. Update node_present_result instruction
echo "\n5. Updating node_present_result instruction...\n";
$newInstruction = <<<'INSTRUCTION'
Zeige das Ergebnis der Verfügbarkeitsprüfung basierend auf der Tool-Response:

**FALL 1: Exakter Wunschtermin VERFÜGBAR (Tool returned available:true mit exakter Zeit):**
"Ihr Wunschtermin am {{appointment_date}} um {{appointment_time}} ist verfügbar. Ich buche jetzt Ihren Termin"
→ Transition SOFORT zu func_start_booking (KEINE Rückfrage!)

**FALL 2: Wunschtermin NICHT verfügbar, aber Tool lieferte ALTERNATIVEN (alternatives array mit Einträgen):**
"Ihr Wunschtermin ist leider nicht verfügbar, aber folgende Termine sind noch verfügbar: [Nenne maximal 2-3 Alternativen aus dem Tool-Response, sortiert nach Nähe zum Kundenwunsch]. Welcher Termin würde Ihnen passen?"
→ Warte auf Kundenauswahl
→ Transition zu node_present_alternatives

**FALL 3: Wunschtermin NICHT verfügbar UND Tool lieferte KEINE Alternativen (alternatives leer oder nicht vorhanden):**
"Leider ist {{appointment_date}} um {{appointment_time}} nicht verfügbar. Einen Moment, ich suche nach weiteren Alternativen..."
→ Transition SOFORT zu func_get_alternatives (OHNE weitere Frage!)

WICHTIG:
- Im FALL 1: KEINE Bestätigung abwarten, sofort buchen!
- Im FALL 2: Alternativen aus Tool-Response nutzen, NICHT zu func_get_alternatives gehen
- Datum OHNE Jahr nennen (z.B. "Montag, den 11. November" statt "11.11.2025")
INSTRUCTION;

$flow['nodes'][$nodeIndex]['instruction']['text'] = $newInstruction;
echo "✅ Updated instruction with 3-case logic\n";

// 6. Display final edges
echo "\n6. Final edges configuration:\n";
foreach ($flow['nodes'][$nodeIndex]['edges'] as $idx => $edge) {
    echo "  Edge #{$idx}: {$edge['id']}\n";
    echo "    → " . ($edge['destination_node_id'] ?? 'MISSING') . "\n";
    $conditionPreview = substr($edge['transition_condition']['prompt'] ?? 'N/A', 0, 60);
    echo "    Condition: {$conditionPreview}...\n\n";
}

// 7. Upload to Retell API
echo "7. Uploading fixed flow to Retell API...\n";

$payload = json_encode($flow, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$ch = curl_init("https://api.retellai.com/update-conversation-flow/{$flowId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'PATCH',
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => $payload
]);

$uploadResponse = curl_exec($ch);
$uploadHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($uploadHttpCode !== 200) {
    echo "❌ Upload failed. HTTP {$uploadHttpCode}\n";
    echo "Response: {$uploadResponse}\n";
    die();
}

$uploadedFlow = json_decode($uploadResponse, true);
echo "✅ Upload successful! New Version: {$uploadedFlow['version']}\n\n";

// 8. Verify by fetching again
echo "8. Verification - Fetching flow again...\n";
$ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]
]);

$verifyResponse = curl_exec($ch);
$verifyHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($verifyHttpCode !== 200) {
    die("❌ Verification failed. HTTP {$verifyHttpCode}\n");
}

$verifiedFlow = json_decode($verifyResponse, true);

// Find node_present_result again
$verifiedNodeIndex = null;
foreach ($verifiedFlow['nodes'] as $index => $node) {
    if ($node['id'] === 'node_present_result') {
        $verifiedNodeIndex = $index;
        break;
    }
}

if ($verifiedNodeIndex === null) {
    die("❌ Verification: node_present_result not found!\n");
}

echo "✅ Verified Flow Version: {$verifiedFlow['version']}\n";
echo "✅ node_present_result edges count: " . count($verifiedFlow['nodes'][$verifiedNodeIndex]['edges']) . "\n\n";

// Verify Edge #2 exists with destination
echo "9. Checking Edge #2...\n";
$edge2Found = false;
foreach ($verifiedFlow['nodes'][$verifiedNodeIndex]['edges'] as $idx => $edge) {
    if (isset($edge['destination_node_id']) && $edge['destination_node_id'] === 'node_present_alternatives') {
        echo "✅ Edge #{$idx} correctly points to node_present_alternatives\n";
        echo "   Condition: " . substr($edge['transition_condition']['prompt'], 0, 60) . "...\n";
        $edge2Found = true;
    }
}

if (!$edge2Found) {
    echo "❌ WARNING: Edge #2 to node_present_alternatives not found in verification!\n";
} else {
    echo "\n✅✅✅ SUCCESS! Edge #2 is fixed and verified! ✅✅✅\n";
}

// Save locally
echo "\n10. Saving locally...\n";
file_put_contents(
    '/var/www/api-gateway/conversation_flow_v' . $verifiedFlow['version'] . '_fixed_2025-11-09.json',
    json_encode($verifiedFlow, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
);
echo "✅ Saved to conversation_flow_v{$verifiedFlow['version']}_fixed_2025-11-09.json\n";

echo "\n=== FIX COMPLETE ===\n";
echo "Next step: User should PUBLISH the flow in Retell Dashboard\n";
