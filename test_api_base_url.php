<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Teste verschiedene Base URLs ===\n\n";

// Hole APP_URL aus .env
$appUrl = env('APP_URL', 'http://localhost');
echo "APP_URL aus .env: $appUrl\n\n";

$webhookData = [
    'call_id' => 'test_' . time(),
    'phone_number' => '+491234567890',
    '_datum__termin' => '2025-06-15',
    '_uhrzeit__termin' => '10:00',
    '_dienstleistung' => 'Herren: Waschen, Schneiden, Styling',
    '_name' => 'Max Mustermann',
    '_email' => 'max@example.com'
];

// Teste verschiedene Base URLs
$baseUrls = [
    'http://localhost',
    'http://127.0.0.1',
    'http://localhost:8000',
    $appUrl,
    'https://api.askproai.de'
];

foreach ($baseUrls as $baseUrl) {
    $url = $baseUrl . '/api/webhooks/retell';
    echo "Teste: $url\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($webhookData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "Fehler: $error\n";
    } else {
        echo "HTTP Code: $httpCode\n";
        if ($httpCode == 200 || $httpCode == 201) {
            echo "✅ Diese URL funktioniert!\n";
            echo "Response: " . substr($response, 0, 100) . "...\n";
            break;
        }
    }
    echo "---\n";
}
