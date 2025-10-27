<?php

/**
 * Deploy V14 Flow - FINAL TELEFONNUMMER FIX
 *
 * ROOT CAUSE (nach mehreren Tests):
 * - V12: check_customer hatte NUR phone_number Parameter
 * - V13: check_customer bekam call_id Parameter (REQUIRED)
 * - ABER: Agent sendet call_id="unknown" weil er den Wert nicht kennt!
 *
 * ECHTE LÖSUNG:
 * - Retell sendet AUTOMATISCH ein "call" Object mit call_id an ALLE Custom Tools
 * - Request Structure: { "name": "tool_name", "call": { "call_id": "..." }, "args": {...} }
 * - Tool Parameter für call_id ist NICHT nötig!
 * - API wurde gefixt: holt call_id aus request.call.call_id (statt args.call_id)
 * - Flow wurde gefixt: call_id Parameter komplett ENTFERNT
 *
 * FLOW:
 * 1. Retell ruft check_customer auf (ohne Parameter)
 * 2. Retell sendet automatisch: { "call": { "call_id": "call_xxx" } }
 * 3. API extrahiert call_id aus call Object
 * 4. API holt from_number aus DB
 * 5. API findet Customer
 * 6. Agent begrüßt mit Namen!
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
echo "🔧 DEPLOYING V14 - FINAL FIX\n";
echo "═══════════════════════════════════════\n\n";

// Load V14 flow
$flowFile = '/var/www/api-gateway/public/askproai_state_of_the_art_flow_2025_V12.json';
if (!file_exists($flowFile)) {
    die("❌ ERROR: Flow file not found: $flowFile\n");
}

$flowJson = file_get_contents($flowFile);
$flowData = json_decode($flowJson, true);

if (!$flowData) {
    die("❌ ERROR: Invalid JSON\n");
}

echo "📋 Flow: V14 (Final Telefonnummer Fix)\n";
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

$hasParameters = count($checkCustomerTool['parameters']['properties'] ?? []) > 0;
$hasRequired = count($checkCustomerTool['parameters']['required'] ?? []) > 0;

echo "🔍 CHECK_CUSTOMER TOOL VERIFICATION:\n";
echo "   ✅ Parameter entfernt: " . ($hasParameters ? "NO (FALSCH!)" : "YES") . "\n";
echo "   ✅ Required leer: " . ($hasRequired ? "NO (FALSCH!)" : "YES") . "\n";
echo "   → Retell sendet call_id automatisch im 'call' Object\n\n";

if ($hasParameters || $hasRequired) {
    die("❌ ERROR: Tool hat noch Parameter! call_id muss komplett entfernt werden.\n");
}

echo "🔧 FINAL FIX:\n";
echo "   ❌ V12: NUR phone_number Parameter\n";
echo "   ❌ V13: call_id + phone_number Parameter → Agent sendet 'unknown'\n";
echo "   ✅ V14: KEINE Parameter → Retell sendet call_id automatisch!\n\n";

echo "📱 WIE ES FUNKTIONIERT:\n";
echo "   1. Agent ruft check_customer() auf (keine Parameter nötig)\n";
echo "   2. Retell sendet automatisch: { call: { call_id: 'call_xxx' } }\n";
echo "   3. API extrahiert call_id aus call Object\n";
echo "   4. API holt from_number aus DB (via call_id)\n";
echo "   5. API sucht Customer (via from_number)\n";
echo "   6. API gibt zurück: 'Willkommen zurück, [Name]!'\n";
echo "   7. Agent begrüßt Kunde mit Namen!\n\n";

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
echo "🎉 V14 - LIVE!\n";
echo "═══════════════════════════════════════\n\n";

echo "📊 Production Status:\n";
echo "   Flow ID: $flowId\n";
echo "   Agent ID: agent_9a8202a740cd3120d96fcfda1e\n";
echo "   Version: V14 (Final Fix)\n";
echo "   Status: 🟢 LIVE\n\n";

echo "📞 BEREIT FÜR TESTANRUF!\n\n";
echo "Erwartetes Verhalten:\n";
echo "   ✅ Agent ruft check_customer() ohne Parameter auf\n";
echo "   ✅ Retell sendet call Object automatisch\n";
echo "   ✅ API extrahiert call_id aus call.call_id\n";
echo "   ✅ API findet from_number: +491604366218\n";
echo "   ✅ API findet Customer: Hansi Hinterseher (ID 338)\n";
echo "   ✅ Agent sagt: 'Guten Tag, Herr Hinterseher! Wie kann ich Ihnen helfen?'\n\n";

echo "🎯 KEIN FRAGEN NACH NAME MEHR!\n\n";
