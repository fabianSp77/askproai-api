<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Test mit alternativer Webhook-Struktur ===\n\n";

// Alternative Struktur mit call_data
$webhookData = [
    'call_data' => [
        'call_id' => 'alt_test_' . time(),
        'status' => 'completed',
        'from_number' => '+491601234567',
        'to_number' => '+4930123456'
    ],
    'args' => [
        'name' => 'Alternative Test',
        'email' => 'alt.test@example.com',
        'dienstleistung' => 'Haarschnitt',
        'datum' => 'morgen',  // Test mit "morgen"
        'uhrzeit' => '14:30'
    ]
];

echo "Teste mit 'morgen' als Datum:\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.askproai.de/api/webhooks/retell');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($webhookData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response:\n";
echo "HTTP Code: $httpCode\n";
echo "Body: $response\n";
