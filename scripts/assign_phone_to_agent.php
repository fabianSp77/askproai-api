<?php
/**
 * Assign phone number to agent
 */

echo "╔═══════════════════════════════════════════════════════════════╗" . PHP_EOL;
echo "║  📞 ASSIGN PHONE NUMBER TO AGENT                             ║" . PHP_EOL;
echo "╚═══════════════════════════════════════════════════════════════╝" . PHP_EOL;
echo PHP_EOL;

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$agentId = 'agent_45daa54928c5768b52ba3db736';
$phoneNumber = '+493033081738';

echo "Agent ID: {$agentId}" . PHP_EOL;
echo "Phone Number: {$phoneNumber}" . PHP_EOL;
echo PHP_EOL;

// Update phone number with agent assignment
$payload = json_encode([
    'agent_id' => $agentId
]);

$ch = curl_init("https://api.retellai.com/update-phone-number/{$phoneNumber}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ SUCCESS: Phone number assigned to agent!" . PHP_EOL;
    echo PHP_EOL;

    // Verify assignment
    $ch = curl_init("https://api.retellai.com/list-phone-numbers");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $phoneNumbers = json_decode($response, true);

    echo "Verification:" . PHP_EOL;
    foreach ($phoneNumbers as $phone) {
        if ($phone['phone_number'] === $phoneNumber) {
            echo "✅ Phone: {$phone['phone_number']}" . PHP_EOL;
            echo "   Nickname: {$phone['nickname']}" . PHP_EOL;
            echo "   Agent ID: " . ($phone['agent_id'] ?? 'none') . PHP_EOL;

            if (isset($phone['agent_id']) && $phone['agent_id'] === $agentId) {
                echo "   ✅ Correctly assigned to Friseur 1 agent!" . PHP_EOL;
            }
            break;
        }
    }

    echo PHP_EOL;
    echo "╔═══════════════════════════════════════════════════════════════╗" . PHP_EOL;
    echo "║  ✅ PHONE NUMBER ASSIGNMENT COMPLETE                         ║" . PHP_EOL;
    echo "╚═══════════════════════════════════════════════════════════════╝" . PHP_EOL;
    echo PHP_EOL;
    echo "🎯 Agent is now ready to receive calls at: {$phoneNumber}" . PHP_EOL;
    echo "📋 Agent uses V77 conversation flow (Phone/Email optional)" . PHP_EOL;
    echo PHP_EOL;
    echo "🧪 Ready for testing!" . PHP_EOL;
    exit(0);
} else {
    echo "❌ FAILED: HTTP {$httpCode}" . PHP_EOL;
    echo $response . PHP_EOL;
    exit(1);
}
