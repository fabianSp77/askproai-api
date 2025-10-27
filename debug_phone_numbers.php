<?php

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['RETELLAI_API_KEY'] ?? $_ENV['RETELL_TOKEN'] ?? null;

echo "═══════════════════════════════════════════════════════\n";
echo "🔍 DEBUG PHONE NUMBERS API\n";
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

echo "HTTP Status Code: $httpCode\n\n";

echo "Raw Response:\n";
echo $response . "\n\n";

echo "Response Length: " . strlen($response) . " bytes\n\n";

$data = json_decode($response, true);

echo "Decoded JSON:\n";
echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

echo "Keys in response:\n";
if (is_array($data)) {
    foreach (array_keys($data) as $key) {
        echo "  - $key\n";
    }
}
