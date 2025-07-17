<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== CHECK Resend E-Mail Status ===\n\n";

$apiKey = config('services.resend.key');

// Get recent emails from Resend
echo "1. Hole letzte E-Mails von Resend API:\n";

$ch = curl_init('https://api.resend.com/emails');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   HTTP Code: $httpCode\n";

if ($httpCode == 200) {
    $emails = json_decode($response, true);
    
    if (isset($emails['data']) && is_array($emails['data'])) {
        echo "   Gefundene E-Mails: " . count($emails['data']) . "\n\n";
        
        echo "2. Letzte E-Mails:\n";
        foreach (array_slice($emails['data'], 0, 10) as $email) {
            echo "   -------------------\n";
            echo "   ID: " . ($email['id'] ?? 'N/A') . "\n";
            echo "   To: " . ($email['to'] ?? 'N/A') . "\n";
            echo "   Subject: " . ($email['subject'] ?? 'N/A') . "\n";
            echo "   Status: " . ($email['status'] ?? 'N/A') . "\n";
            echo "   Created: " . ($email['created_at'] ?? 'N/A') . "\n";
            if (isset($email['last_event'])) {
                echo "   Last Event: " . $email['last_event'] . "\n";
            }
        }
    } else {
        echo "   Response: " . $response . "\n";
    }
} else {
    echo "   Error Response: " . $response . "\n";
}

echo "\n3. Sende neue Test-E-Mail mit eindeutiger ID:\n";
$uniqueId = 'diagnose-' . uniqid();

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
    'subject' => 'Resend Diagnose - ' . $uniqueId,
    'html' => '<h1>Resend Diagnose Test</h1>
               <p>Test ID: ' . $uniqueId . '</p>
               <p>Zeit: ' . now()->format('d.m.Y H:i:s') . '</p>
               <p>Diese E-Mail testet die Resend-Zustellung.</p>',
    'tags' => [
        ['name' => 'category', 'value' => 'diagnose'],
        ['name' => 'test_id', 'value' => $uniqueId]
    ]
]));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   HTTP Code: $httpCode\n";
echo "   Response: $response\n";

if ($httpCode == 200 || $httpCode == 201) {
    $responseData = json_decode($response, true);
    if (isset($responseData['id'])) {
        echo "\n   ✅ E-Mail erfolgreich erstellt!\n";
        echo "   Resend ID: " . $responseData['id'] . "\n";
        echo "   Test ID: " . $uniqueId . "\n\n";
        
        echo "=== NÄCHSTE SCHRITTE ===\n";
        echo "1. Gehen Sie zu: https://resend.com/emails\n";
        echo "2. Suchen Sie nach ID: " . $responseData['id'] . "\n";
        echo "3. Prüfen Sie den Status (sent, delivered, bounced, etc.)\n";
        echo "4. Prüfen Sie auch den Spam-Ordner\n";
    }
}

echo "\n=== MÖGLICHE PROBLEME ===\n";
echo "- Domain 'askproai.de' ist nicht verifiziert in Resend\n";
echo "- E-Mails werden von Apple/iCloud blockiert\n";
echo "- SPF/DKIM nicht korrekt konfiguriert\n";
echo "- Rate Limiting bei Resend\n";