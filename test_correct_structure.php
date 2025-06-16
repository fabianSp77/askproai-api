<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Test mit korrekter Webhook-Struktur ===\n\n";

// Webhook-Daten im korrekten Format (basierend auf dem Controller)
$webhookData = [
    'call_id' => 'correct_test_' . time(),
    'call' => [
        'call_id' => 'correct_test_' . time(),
        'call_status' => 'completed',
        'from_number' => '+491601234567',
        'to_number' => '+4930123456',
        'call_analysis' => [
            'duration' => 180
        ],
        'cost' => 0.50,
        'transcript' => 'Ich möchte einen Termin buchen',
        'call_summary' => 'Kunde möchte Termin',
        'user_sentiment' => 'positive'
    ],
    'args' => [
        'name' => 'Test Kunde Korrekt',
        'email' => 'test@example.com',
        'dienstleistung' => 'Beratung',
        'datum' => '2025-06-20',
        'uhrzeit' => '15:00'
    ]
];

echo "Sende Webhook mit korrekter Struktur:\n";
echo json_encode($webhookData['args'], JSON_PRETTY_PRINT) . "\n\n";

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
echo "Body: $response\n\n";

// Warte und prüfe Ergebnis
if ($httpCode == 200) {
    sleep(2);
    $responseData = json_decode($response, true);
    
    if (isset($responseData['call_id'])) {
        $call = \App\Models\Call::find($responseData['call_id']);
        
        if ($call) {
            echo "\n✅ Call gefunden:\n";
            echo "   - ID: " . $call->id . "\n";
            echo "   - Name: " . $call->name . "\n";
            echo "   - Email: " . $call->email . "\n";
            echo "   - Datum: " . $call->datum_termin . "\n";
            echo "   - Uhrzeit: " . $call->uhrzeit_termin . "\n";
            echo "   - Dienstleistung: " . $call->dienstleistung . "\n";
        }
    }
}
