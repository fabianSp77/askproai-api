<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== CHECK E-Mail Delivery Status ===\n\n";

$apiKey = config('services.resend.key');

// Send a new test email and track it
echo "1. Sende neue Test-E-Mail und verfolge sie:\n";

$uniqueTag = 'track-' . uniqid();

$ch = curl_init('https://api.resend.com/emails');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'from' => 'info@askproai.de',
    'to' => ['fabianspitzer@icloud.com'],
    'subject' => 'Delivery Status Test - ' . $uniqueTag,
    'html' => '<h1>Delivery Status Test</h1>
               <p>Test ID: ' . $uniqueTag . '</p>
               <p>Zeit: ' . now()->format('d.m.Y H:i:s') . '</p>
               <p>Wenn Sie diese E-Mail erhalten, funktioniert die Zustellung.</p>',
    'tags' => [
        ['name' => 'test_id', 'value' => $uniqueTag]
    ]
]));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   HTTP Code: $httpCode\n";
$responseData = json_decode($response, true);

if ($httpCode == 200 || $httpCode == 201) {
    $emailId = $responseData['id'] ?? 'unknown';
    echo "   ✅ E-Mail erstellt mit ID: $emailId\n\n";
    
    // Wait a moment for processing
    echo "2. Warte 5 Sekunden auf Verarbeitung...\n";
    sleep(5);
    
    // Check status
    echo "\n3. Prüfe Delivery Status:\n";
    
    $ch = curl_init("https://api.resend.com/emails/{$emailId}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    
    $statusResponse = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($statusCode == 200) {
        $statusData = json_decode($statusResponse, true);
        
        echo "   Email ID: " . ($statusData['id'] ?? 'N/A') . "\n";
        echo "   Status: " . ($statusData['last_event'] ?? 'N/A') . "\n";
        echo "   To: " . (isset($statusData['to'][0]) ? $statusData['to'][0] : 'N/A') . "\n";
        echo "   From: " . ($statusData['from'] ?? 'N/A') . "\n";
        echo "   Subject: " . ($statusData['subject'] ?? 'N/A') . "\n";
        echo "   Created: " . ($statusData['created_at'] ?? 'N/A') . "\n";
        
        if (isset($statusData['last_event'])) {
            echo "\n   Delivery Status Details:\n";
            switch($statusData['last_event']) {
                case 'sent':
                    echo "   → E-Mail wurde an Resend gesendet\n";
                    break;
                case 'delivered':
                    echo "   → E-Mail wurde erfolgreich zugestellt!\n";
                    break;
                case 'bounced':
                    echo "   → E-Mail wurde abgelehnt (Bounce)\n";
                    break;
                case 'complained':
                    echo "   → E-Mail wurde als Spam markiert\n";
                    break;
                default:
                    echo "   → Status: " . $statusData['last_event'] . "\n";
            }
        }
    } else {
        echo "   Response: $statusResponse\n";
    }
} else {
    echo "   ❌ Error: $response\n";
}

echo "\n=== WICHTIGE HINWEISE ===\n";
echo "1. 'sent' bedeutet nur, dass Resend die E-Mail erhalten hat\n";
echo "2. 'delivered' bedeutet, dass die E-Mail erfolgreich zugestellt wurde\n";
echo "3. 'bounced' bedeutet, dass der Empfänger-Server die E-Mail abgelehnt hat\n\n";

echo "=== EMPFEHLUNGEN ===\n";
echo "1. Prüfen Sie das Resend Dashboard: https://resend.com/emails\n";
echo "2. Suchen Sie nach der E-Mail ID: $emailId\n";
echo "3. Prüfen Sie dort den detaillierten Status\n";
echo "4. Bei 'bounced': Prüfen Sie die Bounce-Nachricht\n";
echo "5. Testen Sie mit einer anderen E-Mail-Adresse (z.B. Gmail)\n";