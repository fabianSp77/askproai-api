#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$apiKey = $_ENV['RETELLAI_API_KEY'] ?? $_ENV['RETELL_TOKEN'];
$baseUrl = 'https://api.retellai.com';

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "  CHECKING PHONE NUMBER CONFIGURATION\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// List all phone numbers
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
    echo "❌ Failed to fetch phone numbers\n";
    echo "HTTP Status: $httpCode\n";
    echo "Response: $response\n";
    exit(1);
}

$numbers = json_decode($response, true);
echo "Found " . count($numbers) . " phone numbers\n\n";

$targetPhone = '+493033081738';
$found = false;

foreach ($numbers as $number) {
    $phoneNumber = $number['phone_number'] ?? '';

    if ($phoneNumber === $targetPhone) {
        $found = true;

        echo "📞 Phone Number: $phoneNumber\n";
        echo "🤖 Agent ID: " . ($number['inbound_agent_id'] ?? 'NONE') . "\n";
        echo "📝 Nickname: " . ($number['nickname'] ?? 'N/A') . "\n\n";

        $agentId = $number['inbound_agent_id'] ?? null;

        if ($agentId) {
            // Get agent details
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "$baseUrl/get-agent/$agentId");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer $apiKey"
            ]);

            $agentResponse = curl_exec($ch);
            $agentHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($agentHttpCode === 200) {
                $agent = json_decode($agentResponse, true);

                echo "═══════════════════════════════════════════════════════════\n";
                echo "  AGENT DETAILS\n";
                echo "═══════════════════════════════════════════════════════════\n\n";

                echo "🏷️  Agent Name: " . ($agent['agent_name'] ?? 'N/A') . "\n";
                echo "🆔 Agent ID: " . ($agent['agent_id'] ?? 'N/A') . "\n";

                if (isset($agent['response_engine'])) {
                    echo "\n--- Response Engine ---\n";

                    if (isset($agent['response_engine']['llm_id'])) {
                        echo "🧠 LLM ID: " . $agent['response_engine']['llm_id'] . "\n";
                    }

                    if (isset($agent['response_engine']['conversation_config_id'])) {
                        echo "🔄 Conversation Flow ID: " . $agent['response_engine']['conversation_config_id'] . "\n";
                    }
                }

                echo "\n";
                echo "═══════════════════════════════════════════════════════════\n";
                echo "  VERSION CHECK\n";
                echo "═══════════════════════════════════════════════════════════\n\n";

                $agentName = $agent['agent_name'] ?? '';

                echo "Expected: Contains 'V109'\n";
                echo "Actual:   $agentName\n\n";

                if (strpos($agentName, 'V110.4') !== false || strpos($agentName, 'V110') !== false) {
                    echo "🚨 PROBLEM FOUND!\n";
                    echo "   Phone is using V110.4 or V110 (old version)\n";
                    echo "   Need to update to V109\n\n";

                    echo "═══════════════════════════════════════════════════════════\n";
                    echo "  CRITICAL: PHONE USING WRONG VERSION\n";
                    echo "═══════════════════════════════════════════════════════════\n\n";

                    echo "This explains the phone call failure:\n";
                    echo "  - V110.4 sends 'service' parameter (wrong)\n";
                    echo "  - V110.4 sends 'function_name' parameter (shouldn't exist)\n";
                    echo "  - Backend rejects these parameters\n\n";

                    echo "FIX: Update phone to use correct agent with V109\n";

                } elseif (strpos($agentName, 'V109') !== false) {
                    echo "✅ CORRECT VERSION!\n";
                    echo "   Phone is using V109\n";
                    echo "   All parameter fixes should be active\n";

                } else {
                    echo "⚠️  UNKNOWN VERSION!\n";
                    echo "   Agent name doesn't contain V109 or V110\n";
                    echo "   Please check manually\n";
                }

            } else {
                echo "❌ Failed to fetch agent details\n";
                echo "HTTP Status: $agentHttpCode\n";
            }
        } else {
            echo "⚠️  No agent assigned to this phone number!\n";
        }

        break;
    }
}

if (!$found) {
    echo "❌ Phone number $targetPhone not found!\n\n";
    echo "Available phone numbers:\n";
    foreach ($numbers as $number) {
        echo "  - " . ($number['phone_number'] ?? 'N/A') . " (Agent: " . ($number['inbound_agent_id'] ?? 'NONE') . ")\n";
    }
}

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "  DONE\n";
echo "═══════════════════════════════════════════════════════════\n\n";
