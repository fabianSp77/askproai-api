<?php
/**
 * Alternative method: Get phone number details and try different assignment method
 */

echo "╔═══════════════════════════════════════════════════════════════╗" . PHP_EOL;
echo "║  📞 PHONE ASSIGNMENT - ALTERNATIVE METHOD                    ║" . PHP_EOL;
echo "╚═══════════════════════════════════════════════════════════════╝" . PHP_EOL;
echo PHP_EOL;

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$agentId = 'agent_45daa54928c5768b52ba3db736';
$phoneNumber = '+493033081738';

// Try to get phone number details
echo "Step 1: Getting phone number details..." . PHP_EOL;

$ch = curl_init("https://api.retellai.com/get-phone-number/{$phoneNumber}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $phoneDetails = json_decode($response, true);
    echo "✅ Phone details retrieved" . PHP_EOL;
    echo json_encode($phoneDetails, JSON_PRETTY_PRINT) . PHP_EOL;
    echo PHP_EOL;

    // Try updating with inbound_phone_number_id if available
    if (isset($phoneDetails['inbound_phone_number_id'])) {
        echo "Step 2: Trying to update with inbound_phone_number_id..." . PHP_EOL;

        $payload = json_encode([
            'agent_id' => $agentId
        ]);

        $phoneId = $phoneDetails['inbound_phone_number_id'];
        $ch = curl_init("https://api.retellai.com/update-phone-number/{$phoneId}");
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
            echo "✅ SUCCESS!" . PHP_EOL;
            echo $response . PHP_EOL;
        } else {
            echo "❌ FAILED: HTTP {$httpCode}" . PHP_EOL;
            echo $response . PHP_EOL;
        }
    }
} else {
    echo "❌ Failed to get phone details: HTTP {$httpCode}" . PHP_EOL;
    echo $response . PHP_EOL;
    echo PHP_EOL;

    // Try direct Retell documentation approach
    echo "Step 2: Manual assignment required" . PHP_EOL;
    echo PHP_EOL;
    echo "Please assign the phone number manually:" . PHP_EOL;
    echo "1. Go to: https://app.retellai.com/dashboard/phone-numbers" . PHP_EOL;
    echo "2. Find: +493033081738 (Friseur Testkunde)" . PHP_EOL;
    echo "3. Click 'Edit' or the phone number" . PHP_EOL;
    echo "4. Assign to agent: agent_45daa54928c5768b52ba3db736" . PHP_EOL;
    echo "   (Friseur 1 Agent V51 - Complete with All Features)" . PHP_EOL;
    echo PHP_EOL;
    echo "After assignment, the agent will receive calls on this number." . PHP_EOL;
}
