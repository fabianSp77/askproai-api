<?php
/**
 * Assign phone number +493033081738 to agent_45daa54928c5768b52ba3db736
 */

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$phoneNumber = '+493033081738';
$agentId = 'agent_45daa54928c5768b52ba3db736';

echo "=== ASSIGN PHONE NUMBER TO AGENT ===\n\n";

// 1. Get current phone number details
echo "1. Getting current phone number configuration...\n";

$ch = curl_init("https://api.retellai.com/list-phone-numbers");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]
]);

$response = curl_exec($ch);
$phoneNumbers = json_decode($response, true);
curl_close($ch);

$phoneNumberId = null;
$currentConfig = null;

foreach ($phoneNumbers as $phone) {
    if ($phone['phone_number'] === $phoneNumber) {
        $phoneNumberId = $phone['phone_number_id'];
        $currentConfig = $phone;
        echo "   Found phone: {$phoneNumber}\n";
        echo "   Phone ID: {$phoneNumberId}\n";
        echo "   Current Agent: " . ($phone['agent_id'] ?? 'NONE') . "\n";
        break;
    }
}

if (!$phoneNumberId) {
    die("❌ Phone number {$phoneNumber} not found in Retell!\n");
}

echo "\n";

// 2. Update phone number with agent assignment
echo "2. Assigning agent to phone number...\n";

$updateData = [
    'agent_id' => $agentId
];

$ch = curl_init("https://api.retellai.com/update-phone-number/{$phoneNumberId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'PATCH',
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => json_encode($updateData)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ Failed to update phone number. HTTP {$httpCode}\n";
    echo "Response: {$response}\n";
    exit(1);
}

$updated = json_decode($response, true);

echo "   ✅ Phone number updated!\n";
echo "   Phone: {$phoneNumber}\n";
echo "   Agent: {$agentId}\n";

echo "\n";

// 3. Verify the change
echo "3. Verifying assignment...\n";

$ch = curl_init("https://api.retellai.com/list-phone-numbers");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]
]);

$response = curl_exec($ch);
$phoneNumbers = json_decode($response, true);
curl_close($ch);

foreach ($phoneNumbers as $phone) {
    if ($phone['phone_number'] === $phoneNumber) {
        $assignedAgent = $phone['agent_id'] ?? 'NONE';
        echo "   Phone: {$phoneNumber}\n";
        echo "   Assigned Agent: {$assignedAgent}\n";

        if ($assignedAgent === $agentId) {
            echo "   ✅ VERIFIED - Agent correctly assigned!\n";
        } else {
            echo "   ❌ FAILED - Agent not assigned correctly!\n";
            echo "   Expected: {$agentId}\n";
            echo "   Got: {$assignedAgent}\n";
        }
        break;
    }
}

echo "\n=== ASSIGNMENT COMPLETE ===\n";
