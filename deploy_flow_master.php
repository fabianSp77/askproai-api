<?php

/**
 * MASTER DEPLOYMENT SCRIPT FÜR RETELL CONVERSATION FLOWS
 *
 * WICHTIG: Dieses Script macht 2 Dinge:
 * 1. Update Conversation Flow (PATCH)
 * 2. Publish Agent (POST) ← KRITISCH! Sonst ist Update nicht live!
 *
 * Usage: php deploy_flow_master.php [flow_file.json] [description]
 */

// Configuration
$FLOW_ID = 'conversation_flow_da76e7c6f3ba';
$AGENT_ID = 'agent_616d645570ae613e421edb98e7'; // Conversation Flow Agent (RICHTIG!)

// Arguments
$flowFile = $argv[1] ?? '/var/www/api-gateway/public/askproai_state_of_the_art_flow_2025_V12.json';
$description = $argv[2] ?? 'Flow Update';

// Read API key
$envFile = __DIR__ . '/.env';
$envContent = file_get_contents($envFile);
$apiKey = null;
if (preg_match('/RETELL_TOKEN=(.+)/', $envContent, $matches)) {
    $apiKey = trim($matches[1]);
}

if (!$apiKey) {
    die("❌ ERROR: API key not found\n");
}

echo "═══════════════════════════════════════\n";
echo "🚀 RETELL FLOW DEPLOYMENT\n";
echo "═══════════════════════════════════════\n\n";
echo "Description: $description\n";
echo "Flow ID: $FLOW_ID\n";
echo "Agent ID: $AGENT_ID\n\n";

// Load flow
if (!file_exists($flowFile)) {
    die("❌ ERROR: Flow file not found: $flowFile\n");
}

$flowJson = file_get_contents($flowFile);
$flowData = json_decode($flowJson, true);

if (!$flowData) {
    die("❌ ERROR: Invalid JSON\n");
}

echo "📋 Flow Info:\n";
echo "   - Nodes: " . count($flowData['nodes']) . "\n";
echo "   - Tools: " . count($flowData['tools']) . "\n";
echo "   - Size: " . round(strlen($flowJson) / 1024, 2) . " KB\n\n";

// STEP 1: Update Flow
echo "═══════════════════════════════════════\n";
echo "STEP 1/2: Updating Conversation Flow\n";
echo "═══════════════════════════════════════\n\n";

$updateUrl = "https://api.retellai.com/update-conversation-flow/$FLOW_ID";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $updateUrl,
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
    echo "❌ STEP 1 FAILED!\n";
    echo "HTTP: $httpCode\n";
    echo "Response: $response\n";
    exit(1);
}

echo "✅ Flow updated successfully!\n\n";

// STEP 2: Publish Agent (KRITISCH!)
echo "═══════════════════════════════════════\n";
echo "STEP 2/2: Publishing Agent (LIVE)\n";
echo "═══════════════════════════════════════\n\n";
echo "⚠️  WICHTIG: Ohne Publish ist das Update NICHT live!\n\n";

$publishUrl = "https://api.retellai.com/publish-agent/$AGENT_ID";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $publishUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ STEP 2 FAILED!\n";
    echo "HTTP: $httpCode\n";
    echo "Response: $response\n";
    echo "\n⚠️  ACHTUNG: Flow wurde updated, aber NICHT published!\n";
    echo "             Der Agent nutzt noch die alte Version!\n";
    exit(1);
}

echo "✅ Agent published successfully!\n\n";

// Success Summary
echo "═══════════════════════════════════════\n";
echo "🎉 DEPLOYMENT SUCCESSFUL!\n";
echo "═══════════════════════════════════════\n\n";

echo "✅ Status:\n";
echo "   1. Flow updated   ✅\n";
echo "   2. Agent published ✅\n\n";

echo "🟢 LIVE STATUS:\n";
echo "   Flow ID: $FLOW_ID\n";
echo "   Agent ID: $AGENT_ID\n";
echo "   Status: 🟢 PUBLISHED & LIVE\n\n";

echo "📞 Ready for production calls!\n\n";

// Log deployment
$logEntry = date('Y-m-d H:i:s') . " - $description - Flow: $FLOW_ID - Agent: $AGENT_ID - Status: SUCCESS\n";
file_put_contents(__DIR__ . '/deployment_log.txt', $logEntry, FILE_APPEND);

echo "📝 Logged to deployment_log.txt\n";
