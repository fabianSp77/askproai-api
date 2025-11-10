#!/usr/bin/env php
<?php
/**
 * Upload call_id parameter_mapping fix via Conversation Flow API
 *
 * Strategy: Use /update-conversation-flow endpoint instead of /update-agent
 * Reason: More granular control, avoids full agent validation
 *
 * Date: 2025-11-08
 */

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$apiKey = $_ENV['RETELLAI_API_KEY'] ?? $_ENV['RETELL_TOKEN'];
$baseUrl = 'https://api.retellai.com';
$conversationFlowId = 'conversation_flow_a58405e3f67a'; // From agent config

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "  🚀 UPLOADING CALL_ID FIX VIA CONVERSATION FLOW API\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "\n";

echo "📋 Fix Details:\n";
echo "  Root Cause: Missing parameter_mapping for call_id\n";
echo "  Tools Fixed: start_booking, confirm_booking\n";
echo "  API Endpoint: /update-conversation-flow (not /update-agent)\n";
echo "\n";

echo "🔧 Configuration:\n";
echo "  Flow ID: $conversationFlowId\n";
echo "  Base URL: $baseUrl\n";
echo "  API Key: " . substr($apiKey, 0, 10) . "...\n";
echo "\n";

// Load fixed agent config
$configFile = __DIR__ . '/../retell_agent_v51_call_id_fixed_2025-11-08.json';

if (!file_exists($configFile)) {
    die("❌ Config file not found: $configFile\n");
}

echo "📖 Loading fixed config...\n";
$configJson = file_get_contents($configFile);
$configData = json_decode($configJson, true);

if (!$configData) {
    die("❌ Failed to parse JSON: " . json_last_error_msg() . "\n");
}

// Extract conversationFlow
if (!isset($configData['conversationFlow'])) {
    die("❌ conversationFlow not found in config\n");
}

$conversationFlow = $configData['conversationFlow'];
echo "✅ Loaded conversation flow with " . count($conversationFlow['tools']) . " tools\n";
echo "\n";

// Verify the fix is in place
echo "🔍 Verifying fix before upload...\n";
$toolsToCheck = ['start_booking', 'confirm_booking'];
$fixVerified = true;

foreach ($toolsToCheck as $toolName) {
    $found = false;
    foreach ($conversationFlow['tools'] as $tool) {
        if ($tool['name'] === $toolName) {
            $found = true;
            $mapping = $tool['parameter_mapping'] ?? null;

            echo "  Tool: $toolName\n";

            if ($mapping && isset($mapping['call_id']) && $mapping['call_id'] === '{{call_id}}') {
                echo "    ✅ parameter_mapping correct: " . json_encode($mapping) . "\n";
            } else {
                echo "    ❌ parameter_mapping WRONG or missing!\n";
                echo "    Current: " . json_encode($mapping) . "\n";
                $fixVerified = false;
            }
        }
    }

    if (!$found) {
        echo "  ⚠️  Tool not found: $toolName\n";
        $fixVerified = false;
    }
}

echo "\n";

if (!$fixVerified) {
    die("❌ Fix verification failed! Config not correct.\n");
}

echo "✅ Fix verified - config is correct!\n";
echo "\n";

// Prepare payload: send only tools array (most surgical approach)
$payload = [
    'tools' => $conversationFlow['tools']
];

// Prepare for upload
echo "📤 Uploading to Retell AI...\n";
echo "  Endpoint: PATCH /update-conversation-flow/$conversationFlowId\n";
echo "  Payload: tools array only (" . count($payload['tools']) . " tools)\n";
echo "  Payload size: " . number_format(strlen(json_encode($payload))) . " bytes\n";
echo "\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/update-conversation-flow/$conversationFlowId");
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "\n";

if ($httpCode === 200 || $httpCode === 201) {
    $result = json_decode($response, true);

    echo "═══════════════════════════════════════════════════════════\n";
    echo "  ✅ UPLOAD SUCCESSFUL!\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "\n";

    echo "📊 Conversation Flow Details:\n";
    echo "  Flow ID: " . ($result['conversation_flow_id'] ?? 'N/A') . "\n";
    echo "  Version: " . ($result['version'] ?? 'N/A') . "\n";
    echo "  Published: " . (($result['is_published'] ?? false) ? '✅ YES' : '⏳ NO (Draft)') . "\n";

    if (isset($result['tools'])) {
        echo "  Tools: " . count($result['tools']) . "\n";
    }

    echo "\n";

    // Save response
    $responseFile = __DIR__ . '/../call_id_fix_flow_upload_response_2025-11-08.json';
    file_put_contents($responseFile, json_encode($result, JSON_PRETTY_PRINT));
    echo "💾 Response saved: call_id_fix_flow_upload_response_2025-11-08.json\n";
    echo "\n";

    echo "═══════════════════════════════════════════════════════════\n";
    echo "  📋 NEXT STEPS\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "\n";

    if (!($result['is_published'] ?? false)) {
        echo "⚠️  Conversation flow is NOT published yet (Draft mode)\n";
        echo "\n";
        echo "The fix is in the draft version. Since the agent references\n";
        echo "this conversation flow, the fix will be active once you\n";
        echo "publish the conversation flow OR if the agent auto-uses drafts.\n";
        echo "\n";
    } else {
        echo "✅ Conversation flow is PUBLISHED and LIVE!\n";
        echo "\n";
    }

    echo "🧪 Testing:\n";
    echo "1. Call: +493033081738\n";
    echo "2. Request: 'Herrenhaarschnitt'\n";
    echo "3. Select alternative time (triggers two-step flow)\n";
    echo "4. Confirm booking\n";
    echo "5. Verify: No error, appointment created\n";
    echo "\n";

    echo "🔍 Verification:\n";
    echo "  Check latest appointment:\n";
    echo "  php artisan tinker\n";
    echo "  >>> \\App\\Models\\Appointment::latest()->first()\n";
    echo "\n";

    exit(0);

} else {
    echo "═══════════════════════════════════════════════════════════\n";
    echo "  ❌ UPLOAD FAILED!\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "\n";

    echo "Response:\n";
    echo $response . "\n";
    echo "\n";

    // Try to decode error
    $error = json_decode($response, true);
    if ($error) {
        echo "Error Details:\n";
        print_r($error);
        echo "\n";
    }

    exit(1);
}
