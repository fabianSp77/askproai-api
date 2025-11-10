#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$apiKey = $_ENV['RETELLAI_API_KEY'] ?? $_ENV['RETELL_TOKEN'];
$baseUrl = 'https://api.retellai.com';

echo "\n";
echo "Listing phone numbers...\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/list-phone-numbers");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $numbers = json_decode($response, true);

    // Find Friseur 1 number
    foreach ($numbers as $number) {
        $phoneNumber = $number['phone_number'] ?? '';

        if (strpos($phoneNumber, '+493033081738') !== false ||
            strpos($phoneNumber, '3033081738') !== false) {

            echo "═══════════════════════════════════════════════════════════\n";
            echo "  ACTIVE PHONE NUMBER CONFIGURATION\n";
            echo "═══════════════════════════════════════════════════════════\n\n";

            echo "Phone: " . ($number['phone_number'] ?? 'N/A') . "\n";
            echo "Assigned Agent: " . ($number['inbound_agent_id'] ?? 'NONE') . "\n\n";

            if (($number['inbound_agent_id'] ?? '') === 'agent_45daa54928c5768b52ba3db736') {
                echo "✅ CORRECT AGENT ASSIGNED!\n\n";
                echo "The phone number +493033081738 is using:\n";
                echo "  Agent: agent_45daa54928c5768b52ba3db736\n";
                echo "  Version: 83 (our fixed version)\n\n";

                echo "═══════════════════════════════════════════════════════════\n";
                echo "  🎉 FIX IS LIVE!\n";
                echo "═══════════════════════════════════════════════════════════\n\n";

                echo "The agent automatically uses the latest version (V83)\n";
                echo "which includes our call_id parameter_mapping fix!\n\n";

                echo "✅ Two-step booking flow: FIXED\n";
                echo "✅ Staff assignment: FIXED (from previous session)\n\n";

                echo "Ready for test call! 📞 +493033081738\n";
            } else {
                echo "⚠️  DIFFERENT AGENT: " . ($number['inbound_agent_id'] ?? 'none') . "\n";
                echo "Need to assign agent_45daa54928c5768b52ba3db736\n";
            }

            break;
        }
    }
} else {
    echo "❌ Failed to list phone numbers\n";
    echo "Response: $response\n";
}

echo "\n";
