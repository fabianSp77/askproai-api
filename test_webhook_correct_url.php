<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Test Webhook mit verschiedenen URLs ===\n\n";

$webhookData = [
    'call_id' => 'test_' . time(),
    'phone_number' => '+491234567890',
    '_datum__termin' => '2025-06-15',
    '_uhrzeit__termin' => '10:00',
    '_dienstleistung' => 'Herren: Waschen, Schneiden, Styling',
    '_name' => 'Max Mustermann',
    '_email' => 'max@example.com',
    'transcript' => 'Ich hätte gerne einen Termin für einen Haarschnitt.',
    'user_sentiment' => 'positive',
    'call_successful' => true,
    'duration' => 120
];

// Teste verschiedene mögliche URLs
$urls = [
    'http://localhost/api/webhooks/retell',
    'http://localhost/api/webhook/retell',
    'http://localhost/retell/webhook',
    'http://localhost/webhook/retell'
];

foreach ($urls as $url) {
    echo "Teste URL: $url\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($webhookData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    if ($httpCode != 404) {
        echo "Response: " . substr($response, 0, 200) . "\n";
        if ($httpCode == 200 || $httpCode == 201) {
            echo "✅ Diese URL funktioniert!\n";
            break;
        }
    }
    echo "---\n";
}
