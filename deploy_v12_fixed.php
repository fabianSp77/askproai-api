<?php

/**
 * Deploy FIXED V12 Flow to Retell AI
 *
 * BUGFIXES:
 * 1. Telefonnummer-Erkennung: check_customer mit phone_number (REQUIRED)
 * 2. Weniger Wiederholungen: Global Prompt optimiert
 * 3. Workflow verbessert: Prüfen → Informieren → Bestätigen → Buchen
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
    die("❌ ERROR: API key not found\n");
}

echo "═══════════════════════════════════════\n";
echo "🔧 DEPLOYING FIXED FLOW V12\n";
echo "═══════════════════════════════════════\n\n";

// Load fixed flow
$flowFile = '/var/www/api-gateway/public/askproai_state_of_the_art_flow_2025_V12.json';
if (!file_exists($flowFile)) {
    die("❌ ERROR: Flow file not found: $flowFile\n");
}

$flowJson = file_get_contents($flowFile);
$flowData = json_decode($flowJson, true);

if (!$flowData) {
    die("❌ ERROR: Invalid JSON\n");
}

echo "📋 Flow: V12 (BUGFIX VERSION)\n";
echo "   - Nodes: " . count($flowData['nodes']) . "\n";
echo "   - Tools: " . count($flowData['tools']) . "\n";
echo "   - Size: " . round(strlen($flowJson) / 1024, 2) . " KB\n\n";

echo "🔧 BUGFIXES:\n";
echo "   ✅ Telefonnummer-Erkennung (check_customer mit phone_number REQUIRED)\n";
echo "   ✅ Weniger Wiederholungen (Global Prompt optimiert)\n";
echo "   ✅ Workflow verbessert (prüfen → informieren → bestätigen → buchen)\n\n";

echo "🚀 Deploying to Retell AI...\n\n";

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

echo "✅ DEPLOYMENT SUCCESSFUL!\n\n";
echo "═══════════════════════════════════════\n";
echo "🎉 FIXED FLOW V12 - LIVE!\n";
echo "═══════════════════════════════════════\n\n";

echo "📊 Production Status:\n";
echo "   Flow ID: $flowId\n";
echo "   Agent ID: agent_9a8202a740cd3120d96fcfda1e\n";
echo "   Version: V12 (Bugfix Release)\n";
echo "   Status: 🟢 LIVE\n\n";

echo "✅ Was wurde gefixt:\n\n";

echo "1️⃣ TELEFONNUMMER-ERKENNUNG\n";
echo "   Vorher: Agent fragt nach Name obwohl Kunde anruft\n";
echo "   Jetzt: Agent nutzt from_number für check_customer\n";
echo "   → Kunde wird automatisch erkannt!\n\n";

echo "2️⃣ WENIGER WIEDERHOLUNGEN\n";
echo "   Vorher: Agent fasst 3x zusammen (\"Also, um das zusammenzufassen...\")\n";
echo "   Jetzt: Nur EINE kurze Bestätigung am Ende\n";
echo "   → Effizienter und weniger nervig!\n\n";

echo "3️⃣ WORKFLOW OPTIMIERT\n";
echo "   Vorher: Bestätigen → Prüfen → \"Leider nicht verfügbar\"\n";
echo "   Jetzt: Prüfen → Informieren → Bestätigen → Buchen\n";
echo "   → User Feedback: \"Das hätt ich auch zuerst gemacht\" ist jetzt erfüllt!\n\n";

echo "📞 BEREIT FÜR NEUEN TESTANRUF!\n\n";
echo "Erwartete Verbesserungen:\n";
echo "   ✅ Kunde wird mit Namen begrüßt\n";
echo "   ✅ Keine unnötigen Zusammenfassungen\n";
echo "   ✅ Verfügbarkeit wird ZUERST geprüft\n";
echo "   ✅ Schnellerer, effizienterer Flow\n\n";
