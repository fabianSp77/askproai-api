<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$apiKey = env('CALCOM_API_KEY');

echo "=== Test mit Team-Slug ===\n\n";

$ch = curl_init();

$data = [
    'eventTypeId' => 2026302,
    'start' => '2025-06-10T14:00:00+02:00',
    'responses' => [
        'name' => 'Test Team Booking',
        'email' => 'fabianspitzer@icloud.com'
    ],
    'timeZone' => 'Europe/Berlin',
    'language' => 'de',
    'metadata' => [
        'teamSlug' => 'askproai'
    ]
];

curl_setopt($ch, CURLOPT_URL, "https://api.cal.com/v1/bookings?apiKey=$apiKey");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: " . json_encode(json_decode($response, true), JSON_PRETTY_PRINT) . "\n";
