<?php
/**
 * Debug phone number structure
 */

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$phoneNumber = '+493033081738';

echo "=== DEBUG PHONE NUMBER STRUCTURE ===\n\n";

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
        echo "Full structure for {$phoneNumber}:\n\n";
        echo json_encode($phone, JSON_PRETTY_PRINT);
        echo "\n\n";

        echo "Available keys:\n";
        foreach (array_keys($phone) as $key) {
            echo "  - {$key}\n";
        }
        break;
    }
}

echo "\n=== END DEBUG ===\n";
