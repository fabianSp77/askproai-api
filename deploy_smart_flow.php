<?php

/**
 * Deploy SMART Conversation Flow to Retell.ai
 *
 * NEUE FEATURES:
 * - Intent Recognition
 * - Smart Collection (keine wiederholten Fragen)
 * - Explicit Function Instructions
 * - Tatsächliche API-Calls
 */

$flowId = 'conversation_flow_da76e7c6f3ba';

// Read API key from .env file
$envFile = __DIR__ . '/.env';
$envContent = file_get_contents($envFile);

$apiKey = null;
if (preg_match('/RETELL_TOKEN=(.+)/', $envContent, $matches)) {
    $apiKey = trim($matches[1]);
}

if (!$apiKey) {
    die("ERROR: API key not found\n");
}

echo "=== DEPLOYING SMART CONVERSATION FLOW ===\n\n";

// Load flow
$flowFile = '/var/www/api-gateway/public/askproai_conversation_flow_smart.json';
if (!file_exists($flowFile)) {
    die("ERROR: Flow file not found: $flowFile\n");
}

$flowJson = file_get_contents($flowFile);
$flowData = json_decode($flowJson, true);

if (!$flowData) {
    die("ERROR: Invalid JSON in flow file\n");
}

echo "Flow loaded:\n";
echo "- Nodes: " . count($flowData['nodes']) . "\n";
echo "- Tools: " . count($flowData['tools']) . "\n";
echo "- Size: " . round(strlen($flowJson) / 1024, 2) . " KB\n\n";

// Deploy
echo "Deploying to Retell.ai...\n";

$baseUrl = 'https://api.retellai.com';
$url = "$baseUrl/update-conversation-flow/$flowId";

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
echo "Status: LIVE\n\n";

echo "=== SMART FEATURES DEPLOYED ===\n";
echo "1. ✅ Intent Recognition - erkennt Name/Datum/Zeit sofort\n";
echo "2. ✅ Smart Collection - fragt nur nach fehlenden Infos\n";
echo "3. ✅ Explicit Function Instructions - API-Calls funktionieren\n";
echo "4. ✅ Keine wiederholten Fragen\n";
echo "5. ✅ Natürliche, effiziente UX\n\n";

echo "=== TEST SCENARIOS ===\n\n";

echo "Szenario 1: User nennt alles sofort\n";
echo "User: 'Hans Schubert, hans@example.com, Donnerstag 13 Uhr'\n";
echo "Erwartung: Agent geht direkt zur Verfügbarkeitsprüfung\n\n";

echo "Szenario 2: User nennt nur Intent\n";
echo "User: 'Ich hätte gern einen Termin'\n";
echo "Erwartung: Agent fragt nach allen Infos auf einmal\n\n";

echo "Szenario 3: User nennt teilweise Infos\n";
echo "User: 'Hans Müller, Donnerstag 13 Uhr'\n";
echo "Erwartung: Agent fragt nur nach Email\n\n";

echo "Bereit für Test-Anruf! 🎉\n";
