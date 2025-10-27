<?php

/**
 * Deploy V13 Flow - TELEFONNUMMER FIX
 *
 * ROOT CAUSE FIX:
 * - check_customer Tool hatte KEINEN call_id Parameter
 * - Alle anderen Tools (get_customer_appointments, cancel_appointment, reschedule_appointment) haben call_id
 * - Ohne call_id kann der Agent die Telefonnummer nicht übermitteln
 *
 * LÖSUNG:
 * - call_id als REQUIRED Parameter zu check_customer hinzugefügt
 * - phone_number bleibt als optionaler Parameter
 * - API kann jetzt call_id empfangen und from_number aus DB holen
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
echo "🔧 DEPLOYING V13 - TELEFONNUMMER FIX\n";
echo "═══════════════════════════════════════\n\n";

// Load V13 flow (V12 mit check_customer fix)
$flowFile = '/var/www/api-gateway/public/askproai_state_of_the_art_flow_2025_V12.json';
if (!file_exists($flowFile)) {
    die("❌ ERROR: Flow file not found: $flowFile\n");
}

$flowJson = file_get_contents($flowFile);
$flowData = json_decode($flowJson, true);

if (!$flowData) {
    die("❌ ERROR: Invalid JSON\n");
}

echo "📋 Flow: V13 (Telefonnummer-Erkennung FIX)\n";
echo "   - Nodes: " . count($flowData['nodes']) . "\n";
echo "   - Tools: " . count($flowData['tools']) . "\n";
echo "   - Size: " . round(strlen($flowJson) / 1024, 2) . " KB\n\n";

// Verify fix
$checkCustomerTool = null;
foreach ($flowData['tools'] as $tool) {
    if ($tool['name'] === 'check_customer') {
        $checkCustomerTool = $tool;
        break;
    }
}

if (!$checkCustomerTool) {
    die("❌ ERROR: check_customer tool not found!\n");
}

$hasCallId = isset($checkCustomerTool['parameters']['properties']['call_id']);
$isRequired = in_array('call_id', $checkCustomerTool['parameters']['required'] ?? []);

echo "🔍 CHECK_CUSTOMER TOOL VERIFICATION:\n";
echo "   ✅ call_id parameter: " . ($hasCallId ? "YES" : "NO") . "\n";
echo "   ✅ call_id required: " . ($isRequired ? "YES" : "NO") . "\n\n";

if (!$hasCallId || !$isRequired) {
    die("❌ ERROR: Fix not applied correctly!\n");
}

echo "🔧 ROOT CAUSE & FIX:\n";
echo "   ❌ Problem: check_customer hatte KEINEN call_id Parameter\n";
echo "   ✅ Fix: call_id als REQUIRED hinzugefügt (wie andere Tools)\n";
echo "   → Agent kann jetzt call_id senden\n";
echo "   → API holt from_number aus DB\n";
echo "   → Kunde wird automatisch erkannt!\n\n";

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
echo "🎉 V13 - LIVE!\n";
echo "═══════════════════════════════════════\n\n";

echo "📊 Production Status:\n";
echo "   Flow ID: $flowId\n";
echo "   Agent ID: agent_9a8202a740cd3120d96fcfda1e\n";
echo "   Version: V13 (Telefonnummer Fix)\n";
echo "   Status: 🟢 LIVE\n\n";

echo "✅ Was wurde gefixt:\n\n";
echo "🔧 TOOL PARAMETER FIX\n";
echo "   Vorher: check_customer hatte NUR phone_number Parameter\n";
echo "   Jetzt: check_customer hat call_id (REQUIRED) + phone_number (optional)\n";
echo "   Wie: Gleicher Pattern wie get_customer_appointments, cancel_appointment\n\n";

echo "📱 TELEFONNUMMER-FLUSS\n";
echo "   1. Retell startet Call → call_id wird generiert\n";
echo "   2. Agent ruft check_customer(call_id) auf\n";
echo "   3. API empfängt call_id\n";
echo "   4. API holt from_number aus calls Tabelle\n";
echo "   5. API sucht Customer mit dieser from_number\n";
echo "   6. Agent begrüßt Kunde mit Namen!\n\n";

echo "📞 BEREIT FÜR TESTANRUF!\n\n";
echo "Erwartetes Verhalten:\n";
echo "   ✅ Agent sendet call_id statt 'unknown'\n";
echo "   ✅ API findet from_number (+491604366218)\n";
echo "   ✅ Customer wird erkannt\n";
echo "   ✅ Begrüßung mit Namen!\n\n";
