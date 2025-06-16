<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$apiKey = env('CALCOM_API_KEY');

echo "=== Direkter API-Test ohne User-ID ===\n\n";

// Test mit verschiedenen Event-Types
$tests = [
    ['id' => 2026302, 'name' => '30 Minuten Termin'],
    ['id' => 2547902, 'name' => '30 Minuten Termin (Kopie)'],
    ['id' => 2026301, 'name' => '15 Minuten Termin']
];

foreach ($tests as $test) {
    echo "\nTeste Event-Type: {$test['name']} (ID: {$test['id']})\n";
    
    $ch = curl_init();
    
    $data = [
        'eventTypeId' => $test['id'],
        'start' => '2025-06-10T10:00:00+02:00',
        'responses' => [
            'name' => 'Test ohne UserID',
            'email' => 'fabianspitzer@icloud.com'
        ],
        'timeZone' => 'Europe/Berlin',
        'language' => 'de',
        'metadata' => new stdClass()
    ];
    
    curl_setopt($ch, CURLOPT_URL, "https://api.cal.com/v1/bookings?apiKey=$apiKey");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if ($httpCode == 200 && isset($result['uid'])) {
        echo "✅ ERFOLG! Buchungs-ID: " . $result['id'] . "\n";
        break; // Stoppe nach erfolgreichem Test
    } else {
        echo "❌ Fehler: " . ($result['message'] ?? 'Unbekannt') . "\n";
    }
}
