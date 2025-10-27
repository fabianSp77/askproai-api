<?php

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['RETELLAI_API_KEY'] ?? $_ENV['RETELL_TOKEN'] ?? null;

// Command line arguments
$phoneNumber = $argv[1] ?? null;
$agentVersion = $argv[2] ?? null;

if (!$phoneNumber || !$agentVersion) {
    echo "Usage: php update_phone_number_version.php <phone_number> <agent_version>\n";
    echo "Example: php update_phone_number_version.php +493033081738 42\n";
    exit(1);
}

echo "═══════════════════════════════════════════════════════\n";
echo "📞 UPDATE PHONE NUMBER VERSION\n";
echo "═══════════════════════════════════════════════════════\n\n";

echo "Phone Number: $phoneNumber\n";
echo "Target Version: $agentVersion\n\n";

// Step 1: Get current phone number details
echo "📥 Step 1: Fetching current phone number details...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.retellai.com/list-phone-numbers");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("❌ Failed to fetch phone numbers! HTTP $httpCode\n");
}

$phoneNumbers = json_decode($response, true);

// Find the specific phone number
$currentPhone = null;
foreach ($phoneNumbers as $phone) {
    if ($phone['phone_number'] === $phoneNumber) {
        $currentPhone = $phone;
        break;
    }
}

if (!$currentPhone) {
    die("❌ Phone number $phoneNumber not found!\n");
}

echo "✅ Found phone number!\n";
echo "   Current Inbound Version: " . ($currentPhone['inbound_agent_version'] ?? 'N/A') . "\n";
echo "   Current Outbound Version: " . ($currentPhone['outbound_agent_version'] ?? 'N/A') . "\n\n";

// Step 2: Update phone number version
echo "📤 Step 2: Updating phone number to version $agentVersion...\n";

$payload = [
    'inbound_agent_version' => (int)$agentVersion,
];

// Also update outbound_agent_version if it exists
if (isset($currentPhone['outbound_agent_id'])) {
    $payload['outbound_agent_version'] = (int)$agentVersion;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.retellai.com/update-phone-number/" . urlencode($phoneNumber));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
]);

$updateResponse = curl_exec($ch);
$updateHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($updateHttpCode !== 200) {
    echo "❌ Failed to update phone number! HTTP $updateHttpCode\n";
    echo "Response: $updateResponse\n";
    exit(1);
}

$updatedPhone = json_decode($updateResponse, true);

echo "✅ Phone number updated successfully!\n\n";

echo "📊 Updated Details:\n";
echo "   Phone Number: " . ($updatedPhone['phone_number'] ?? 'N/A') . "\n";
echo "   Nickname: " . ($updatedPhone['nickname'] ?? 'N/A') . "\n";
echo "   Inbound Agent ID: " . ($updatedPhone['inbound_agent_id'] ?? 'N/A') . "\n";
echo "   Inbound Agent Version: " . ($updatedPhone['inbound_agent_version'] ?? 'N/A') . "\n";

if (isset($updatedPhone['outbound_agent_id'])) {
    echo "   Outbound Agent ID: " . ($updatedPhone['outbound_agent_id'] ?? 'N/A') . "\n";
    echo "   Outbound Agent Version: " . ($updatedPhone['outbound_agent_version'] ?? 'N/A') . "\n";
}

if (isset($updatedPhone['last_modification_timestamp'])) {
    $timestamp = $updatedPhone['last_modification_timestamp'] / 1000;
    $dateTime = new DateTime('@' . $timestamp);
    $dateTime->setTimezone(new DateTimeZone('Europe/Berlin'));
    echo "   Last Modified: " . $dateTime->format('Y-m-d H:i:s') . " (Berlin)\n";
}

echo "\n═══════════════════════════════════════════════════════\n";
echo "🎉 SUCCESS!\n";
echo "═══════════════════════════════════════════════════════\n\n";

echo "⏳ Changes are effective immediately.\n";
echo "🧪 Test calls to $phoneNumber will now use version $agentVersion\n\n";
