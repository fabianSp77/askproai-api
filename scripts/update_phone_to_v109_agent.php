#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$apiKey = $_ENV['RETELLAI_API_KEY'] ?? $_ENV['RETELL_TOKEN'];
$baseUrl = 'https://api.retellai.com';

$phoneNumber = '+493033081738';
$oldAgentId = 'agent_c1d8dea0445f375857a55ffd61';  // V110.4 agent
$newAgentId = 'agent_45daa54928c5768b52ba3db736';  // V51 agent with V109 flow

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "  UPDATE PHONE TO V109 AGENT\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "Phone: $phoneNumber\n";
echo "Old Agent: $oldAgentId (V110.4)\n";
echo "New Agent: $newAgentId (V109)\n\n";

// Step 1: Verify phone exists
echo "Step 1: Verifying phone number exists...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/list-phone-numbers");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ Failed to list phone numbers\n";
    exit(1);
}

$numbers = json_decode($response, true);
$phoneExists = false;

foreach ($numbers as $number) {
    if (($number['phone_number'] ?? '') === $phoneNumber) {
        $phoneExists = true;
        echo "✅ Phone found\n";
        echo "   Phone: $phoneNumber\n";
        echo "   Current Agent: " . ($number['inbound_agent_id'] ?? 'NONE') . "\n\n";
        break;
    }
}

if (!$phoneExists) {
    echo "❌ Phone number not found\n";
    exit(1);
}

// Step 2: Update phone to use new agent
echo "Step 2: Updating phone to use V109 agent...\n";

$updatePayload = [
    'agent_id' => $newAgentId  // Use 'agent_id', not 'inbound_agent_id'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/update-phone-number/$phoneNumber");  // Use phone number directly
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($updatePayload));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Response: $response\n\n";

if ($httpCode !== 200) {
    echo "❌ Failed to update phone number\n";
    exit(1);
}

$updateResponse = json_decode($response, true);
if (isset($updateResponse['inbound_agent_id'])) {
    echo "✅ Phone number updated successfully\n";
    echo "   New Agent ID from response: " . $updateResponse['inbound_agent_id'] . "\n\n";
} else {
    echo "✅ Update request completed\n";
    echo "   Full response: " . json_encode($updateResponse, JSON_PRETTY_PRINT) . "\n\n";
}

// Step 3: Verify the change
echo "Step 3: Verifying change...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/list-phone-numbers");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey"
]);

$response = curl_exec($ch);
curl_close($ch);

$numbers = json_decode($response, true);

foreach ($numbers as $number) {
    if (($number['phone_number'] ?? '') === $phoneNumber) {
        $currentAgentId = $number['inbound_agent_id'] ?? 'NONE';

        echo "\n";
        echo "═══════════════════════════════════════════════════════════\n";
        echo "  VERIFICATION RESULTS\n";
        echo "═══════════════════════════════════════════════════════════\n\n";

        echo "Phone: $phoneNumber\n";
        echo "Agent ID: $currentAgentId\n\n";

        if ($currentAgentId === $newAgentId) {
            echo "✅ SUCCESS!\n";
            echo "   Phone is now using the V109 agent\n";
            echo "   Agent: Friseur 1 Agent V51 - Complete with All Features\n";
            echo "   Flow: conversation_flow_a58405e3f67a (V109)\n\n";

            echo "═══════════════════════════════════════════════════════════\n";
            echo "  🎉 PHONE CALL SHOULD NOW WORK!\n";
            echo "═══════════════════════════════════════════════════════════\n\n";

            echo "The agent will now send:\n";
            echo "  ✅ 'service_name' parameter (correct)\n";
            echo "  ✅ No 'function_name' parameter\n";
            echo "  ✅ Backend will accept the request\n\n";

            echo "Next step: Make a test call!\n";
            echo "  Phone: +493033081738\n";
            echo "  Test: 'Hans Schuster, Herrenhaarschnitt morgen um 10 Uhr'\n";
            echo "  Expected: Booking succeeds\n";

        } else {
            echo "⚠️  WARNING: Agent ID doesn't match\n";
            echo "   Expected: $newAgentId\n";
            echo "   Got: $currentAgentId\n";
        }

        break;
    }
}

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "  DONE\n";
echo "═══════════════════════════════════════════════════════════\n\n";
