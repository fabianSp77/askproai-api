<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Models\Appointment;

echo "=== Vollständiger Integrations-Test ===\n\n";

// 1. Prüfe Datenbankstruktur
echo "1. Prüfe Datenbankstruktur\n";
$hasCallId = \DB::select("SHOW COLUMNS FROM appointments LIKE 'call_id'");
if (empty($hasCallId)) {
    echo "   ❌ FEHLER: appointments Tabelle hat keine call_id Spalte!\n";
    echo "   Bitte führe die Migration aus.\n";
    exit(1);
} else {
    echo "   ✅ appointments.call_id Spalte vorhanden\n\n";
}

// 2. Zeige aktuelle Appointments Struktur
echo "2. Aktuelle Appointments Struktur:\n";
$columns = \DB::select("SHOW COLUMNS FROM appointments");
foreach ($columns as $col) {
    echo "   - " . $col->Field . " (" . $col->Type . ")\n";
}
echo "\n";

// 3. Simuliere Retell.ai Webhook
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

echo "3. Sende Webhook an: https://api.askproai.de/api/webhooks/retell\n";

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
        echo "4. Prüfe Datenbank-Einträge\n";
        
        // Warte kurz
        sleep(2);
        
        $call = Call::find($responseData['call_id']);
        
        if ($call) {
            echo "   ✅ Call gefunden:\n";
            echo "      - ID: " . $call->id . "\n";
            echo "      - Call ID: " . $call->call_id . "\n";
            echo "      - Phone: " . $call->phone_number . "\n\n";
        }
    }
}

        // Prüfe Appointment mit verschiedenen möglichen Beziehungen
        if ($call) {
            // Versuche über call_id
            $appointment = Appointment::where('call_id', $call->id)->first();
            
            // Falls nicht gefunden, versuche über customer_id
            if (!$appointment && isset($call->customer_id)) {
                $appointment = Appointment::where('customer_id', $call->customer_id)
                    ->orderBy('created_at', 'desc')
                    ->first();
            }
            
            if ($appointment) {
                echo "   ✅ Appointment gefunden:\n";
                echo "      - ID: " . $appointment->id . "\n";
                echo "      - Customer ID: " . $appointment->customer_id . "\n";
                echo "      - Starts at: " . $appointment->starts_at . "\n";
                echo "      - Status: " . $appointment->status . "\n";
                
                if ($appointment->external_id) {
                    echo "   ✅ Cal.com Booking erstellt:\n";
                    echo "      - External ID: " . $appointment->external_id . "\n";
                } else {
                    echo "   ⚠️  Kein Cal.com Booking gefunden\n";
                }
                
                // Zeige Payload falls vorhanden
                if ($appointment->payload) {
                    $payload = json_decode($appointment->payload, true);
                    echo "   📋 Payload:\n";
                    echo "      " . json_encode($payload, JSON_PRETTY_PRINT) . "\n";
                }
            } else {
                echo "   ❌ Kein Appointment gefunden\n";
                
                // Debug: Zeige letzte 5 Appointments
                echo "\n   📊 Letzte 5 Appointments in DB:\n";
                $recentAppointments = Appointment::orderBy('created_at', 'desc')->limit(5)->get();
                foreach ($recentAppointments as $apt) {
                    echo "      - ID: " . $apt->id . ", Customer: " . $apt->customer_id . ", Created: " . $apt->created_at . "\n";
                }
            }
        } else {
            echo "   ❌ Call nicht in Datenbank gefunden\n";
        }

echo "\n=== Test abgeschlossen ===\n";
