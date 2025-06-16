<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Simuliere Retell.ai Webhook ===\n\n";

// Simuliere Webhook-Daten
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

// Sende Request an Webhook
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/api/retell/webhook');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($webhookData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";

// Prüfe Datenbank
echo "\n=== Prüfe Datenbank ===\n";

use App\Models\Call;
use App\Models\Appointment;

$call = Call::where('call_id', $webhookData['call_id'])->first();
if ($call) {
    echo "✅ Call gefunden: ID " . $call->id . "\n";
    
    $appointment = Appointment::where('call_id', $call->id)->first();
    if ($appointment) {
        echo "✅ Appointment gefunden: ID " . $appointment->id . "\n";
        echo "   Start: " . $appointment->start_time . "\n";
        echo "   Service: " . $appointment->service . "\n";
    } else {
        echo "❌ Kein Appointment gefunden\n";
    }
} else {
    echo "❌ Kein Call gefunden\n";
}
