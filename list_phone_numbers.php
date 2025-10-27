<?php

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['RETELLAI_API_KEY'] ?? $_ENV['RETELL_TOKEN'] ?? null;

echo "═══════════════════════════════════════════════════════\n";
echo "📞 LISTING ALL PHONE NUMBERS\n";
echo "═══════════════════════════════════════════════════════\n\n";

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
    echo "❌ Failed to fetch phone numbers! HTTP $httpCode\n";
    echo "Response: $response\n";
    exit(1);
}

$data = json_decode($response, true);
$phoneNumbers = $data['phone_numbers'] ?? [];

echo "Total Phone Numbers: " . count($phoneNumbers) . "\n\n";

foreach ($phoneNumbers as $phone) {
    echo "─────────────────────────────────────────\n";
    echo "Phone Number: " . ($phone['phone_number'] ?? 'N/A') . "\n";
    echo "Pretty: " . ($phone['phone_number_pretty'] ?? 'N/A') . "\n";
    echo "Nickname: " . ($phone['nickname'] ?? 'N/A') . "\n";
    echo "Area Code: " . ($phone['area_code'] ?? 'N/A') . "\n\n";

    echo "Inbound Agent ID: " . ($phone['inbound_agent_id'] ?? 'N/A') . "\n";
    echo "Inbound Agent Version: " . ($phone['inbound_agent_version'] ?? 'N/A') . "\n\n";

    echo "Outbound Agent ID: " . ($phone['outbound_agent_id'] ?? 'N/A') . "\n";
    echo "Outbound Agent Version: " . ($phone['outbound_agent_version'] ?? 'N/A') . "\n\n";

    if (isset($phone['last_modification_timestamp'])) {
        $timestamp = $phone['last_modification_timestamp'] / 1000;
        $dateTime = new DateTime('@' . $timestamp);
        $dateTime->setTimezone(new DateTimeZone('Europe/Berlin'));
        echo "Last Modified: " . $dateTime->format('Y-m-d H:i:s') . " (Berlin)\n";
    }

    echo "\n";
}

echo "═══════════════════════════════════════════════════════\n";
