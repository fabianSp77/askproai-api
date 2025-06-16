<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Vollständiger Integrations-Test ===\n\n";

// 1. Simuliere Retell.ai Webhook
$webhookData = [
    'call_id' => 'integration_test_' . time(),
    'phone_number' => '+491234567890',
    '_datum__termin' => '2025-06-16',
    '_uhrzeit__termin' => '14:30',
    '_dienstleistung' => 'Herren: Waschen, Schneiden, Styling',
    '_name' => 'Integration Test Kunde',
    '_email' => 'fabianspitzer@icloud.com',
    'transcript' => 'Ich möchte einen Termin für Haarschnitt buchen.',
    'user_sentiment' => 'positive',
    'call_successful' => true,
    'duration' => 180
];

echo "1. Sende Webhook an: https://api.askproai.de/api/webhooks/retell\n";

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

echo "   HTTP Code: $httpCode\n";
echo "   Response: $response\n\n";

if ($httpCode == 200) {
    $responseData = json_decode($response, true);
    
    if (isset($responseData['call_id'])) {
        echo "2. Prüfe Datenbank-Einträge\n";
        
        use App\Models\Call;
        use App\Models\Appointment;
        
        $call = Call::find($responseData['call_id']);
        
        if ($call) {
            echo "   ✅ Call gefunden:\n";
            echo "      - ID: " . $call->id . "\n";
            echo "      - Name: " . $call->name . "\n";
            echo "      - Email: " . $call->email . "\n";
            echo "      - Call ID: " . $call->call_id . "\n";
            
            // Prüfe Appointment
            $appointment = Appointment::where('call_id', $call->id)->first();
            
            if ($appointment) {
                echo "   ✅ Appointment gefunden:\n";
                echo "      - ID: " . $appointment->id . "\n";
                echo "      - Start: " . $appointment->start_time . "\n";
                echo "      - Service: " . $appointment->service . "\n";
                
                if ($appointment->calcom_booking_id) {
                    echo "   ✅ Cal.com Booking erstellt:\n";
                    echo "      - Booking ID: " . $appointment->calcom_booking_id . "\n";
                } else {
                    echo "   ⚠️  Keine Cal.com Booking ID gefunden\n";
                }
            } else {
                echo "   ❌ Kein Appointment gefunden\n";
            }
        }
    }
}

echo "\n=== Test abgeschlossen ===\n";
