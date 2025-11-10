<?php
/**
 * Fix node_collect_booking_info instruction type
 * Change from "static_text" to "prompt" to prevent reading aloud
 */

$flowId = 'conversation_flow_a58405e3f67a';
$apiKey = 'key_6ff998ba48e842092e04a5455d19';

echo "=== Fix Instruction Type ===\n\n";

// 1. Fetch current flow
echo "1. Fetching flow V96...\n";
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
    die("❌ Failed to fetch flow. HTTP {$httpCode}\n");
}

$flow = json_decode($response, true);
echo "✅ Fetched Flow Version {$flow['version']}\n\n";

// 2. Find node_collect_booking_info
echo "2. Finding node_collect_booking_info...\n";
$nodeIndex = null;
foreach ($flow['nodes'] as $index => $node) {
    if ($node['id'] === 'node_collect_booking_info') {
        $nodeIndex = $index;
        break;
    }
}

if ($nodeIndex === null) {
    die("❌ node_collect_booking_info not found!\n");
}

echo "✅ Found node at index {$nodeIndex}\n";
echo "Current instruction type: " . $flow['nodes'][$nodeIndex]['instruction']['type'] . "\n\n";

// 3. Update instruction
echo "3. Updating instruction...\n";

$newInstruction = <<<'INSTRUCTION'
Sammle alle notwendigen Informationen für die Terminbuchung:
- Service (Welche Dienstleistung?)
- Datum (Welcher Tag?)
- Uhrzeit (Welche Zeit?)
- Kundenname (optional, kann später erfragt werden)

WICHTIG - Wenn User nach Vorschlägen fragt:
Wenn der User sagt:
- "Nächster freier Termin"
- "Haben Sie noch frei?"
- "Was haben Sie noch frei?"
- "Welche Zeiten haben Sie?"
- "Können Sie Vorschläge machen?"
- "Wann passt es denn?"

→ DANN sage: "Einen Moment, ich prüfe die Verfügbarkeit für Sie."
→ Der Flow wird automatisch zur Verfügbarkeitsprüfung weitergehen.

Wenn User KONKRETE Zeit nennt ("16 Uhr", "morgen 14 Uhr"):
→ Notiere die Zeit und fahre fort.

Frage nur nach fehlenden Informationen. Wenn User alle Infos gegeben hat oder nach Vorschlägen fragt, fahre fort.
INSTRUCTION;

// Change type from "static_text" to "prompt"
$flow['nodes'][$nodeIndex]['instruction'] = [
    'type' => 'prompt',
    'text' => $newInstruction
];

echo "✅ Changed instruction type: static_text → prompt\n";
echo "✅ Shortened instruction length\n\n";

// 4. Upload
echo "4. Uploading to Retell API...\n";
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

// 5. Verify
echo "5. Verifying...\n";
$ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]
]);

$verifyResponse = curl_exec($ch);
curl_close($ch);

$verifiedFlow = json_decode($verifyResponse, true);

// Find node again
foreach ($verifiedFlow['nodes'] as $node) {
    if ($node['id'] === 'node_collect_booking_info') {
        echo "✅ Verified instruction type: " . $node['instruction']['type'] . "\n";
        if ($node['instruction']['type'] === 'prompt') {
            echo "✅✅✅ SUCCESS! Agent wird Instruction NICHT mehr vorlesen! ✅✅✅\n";
        } else {
            echo "❌ WARNING: Type is still " . $node['instruction']['type'] . "\n";
        }
        break;
    }
}

echo "\n=== FIX COMPLETE ===\n";
echo "Flow Version: {$verifiedFlow['version']}\n";
echo "Next: User muss Version {$verifiedFlow['version']} im Dashboard publishen\n";
