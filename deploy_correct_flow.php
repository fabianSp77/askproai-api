<?php

/**
 * Deploy CORRECT Conversation Flow to Retell.ai
 *
 * Nach Retell.ai Best Practices:
 * - KURZE node instructions
 * - Logik im global_prompt
 * - Keine technischen Begriffe
 */

$flowId = 'conversation_flow_da76e7c6f3ba';

// Read API key
$envFile = __DIR__ . '/.env';
$envContent = file_get_contents($envFile);

$apiKey = null;
if (preg_match('/RETELL_TOKEN=(.+)/', $envContent, $matches)) {
    $apiKey = trim($matches[1]);
}

if (!$apiKey) {
    die("ERROR: API key not found\n");
}

echo "=== DEPLOYING CORRECT CONVERSATION FLOW ===\n\n";

// Load flow
$flowFile = '/var/www/api-gateway/public/askproai_conversation_flow_correct.json';
if (!file_exists($flowFile)) {
    die("ERROR: Flow file not found\n");
}

$flowJson = file_get_contents($flowFile);
$flowData = json_decode($flowJson, true);

if (!$flowData) {
    die("ERROR: Invalid JSON\n");
}

echo "Flow: askproai_conversation_flow_correct.json\n";
echo "- Nodes: " . count($flowData['nodes']) . "\n";
echo "- Tools: " . count($flowData['tools']) . "\n";
echo "- Size: " . round(strlen($flowJson) / 1024, 2) . " KB\n\n";

// Deploy
echo "Deploying to Retell.ai...\n";

$url = "https://api.retellai.com/update-conversation-flow/$flowId";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'PATCH',
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS => $flowJson
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ DEPLOYMENT FAILED!\n";
    echo "HTTP: $httpCode\n";
    echo "Response: $response\n";
    exit(1);
}

echo "\n✅ DEPLOYMENT SUCCESSFUL!\n\n";
echo "Flow ID: $flowId\n";
echo "Version: 10 (Correct nach Best Practices)\n";
echo "Status: LIVE\n\n";

echo "=== WAS GEFIXT WURDE ===\n";
echo "1. ✅ Keine langen technischen Instructions mehr\n";
echo "2. ✅ Logik im Global Prompt statt in Nodes\n";
echo "3. ✅ Static Text für feste Sätze ('Einen Moment bitte...')\n";
echo "4. ✅ Prompt nur für kurze Anweisungen\n";
echo "5. ✅ Keine IF/THEN Logik in Instructions\n";
echo "6. ✅ Keine WICHTIG/STRATEGIE/BEISPIELE mehr\n\n";

echo "JETZT TESTEN! 📞\n";
echo "Der Agent sollte jetzt:\n";
echo "- Natürlich sprechen\n";
echo "- KEINE technischen Kommandos vorlesen\n";
echo "- Intent erkennen (Name/Datum/Zeit aus erstem Input)\n";
echo "- API-Calls machen\n\n";

echo "Bereit für Test-Anruf! 🎉\n";
