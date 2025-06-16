<?php
require_once '/var/www/api-gateway/vendor/autoload.php';

use App\Models\Company;

// Laravel Bootstrap
$app = require_once '/var/www/api-gateway/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Hole die Company mit Cal.com API Key
$company = Company::first();
if (!$company) {
    echo "Keine Company gefunden!\n";
    exit;
}

$apiKey = $company->calcom_api_key;
$eventTypeId = 2026302; // 30 Minuten Termin

echo "=== Cal.com Slots Test ===\n";
echo "API Key: " . substr($apiKey, 0, 20) . "...\n";
echo "Event Type ID: $eventTypeId\n\n";

// Test für verschiedene Tage
for ($i = 1; $i <= 7; $i++) {
    $date = new DateTime("+$i days");
    $dateStr = $date->format('Y-m-d');
    
    echo "Tag $i: " . $date->format('l, d.m.Y') . "\n";
    
    // Slots abrufen
    $url = "https://api.cal.com/v1/slots?" . http_build_query([
        'apiKey' => $apiKey,
        'eventTypeId' => $eventTypeId,
        'startTime' => $dateStr,
        'endTime' => $dateStr,
        'timeZone' => 'Europe/Berlin',
        'username' => 'askproai'  // Fügen Sie den Username hinzu
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        
        if (isset($data['slots']) && is_array($data['slots'])) {
            $slotCount = count($data['slots']);
            echo "  → $slotCount Slots verfügbar\n";
            
            if ($slotCount > 0) {
                echo "  → Erste verfügbare Zeiten:\n";
                $count = 0;
                foreach ($data['slots'] as $time => $slot) {
                    $dateTime = new DateTime($time);
                    echo "     - " . $dateTime->format('H:i') . " Uhr\n";
                    $count++;
                    if ($count >= 3) break;
                }
                
                // Speichere ersten verfügbaren Slot für Buchungstest
                if ($i === 1) {
                    $firstAvailableSlot = array_key_first($data['slots']);
                    echo "\n  → Erster verfügbarer Slot für Buchung: " . $firstAvailableSlot . "\n";
                }
            }
        } else {
            echo "  → Keine Slots gefunden oder unerwartetes Format\n";
            if (isset($data['message'])) {
                echo "  → Fehlermeldung: " . $data['message'] . "\n";
            }
        }
    } else {
        echo "  → Fehler: HTTP $httpCode\n";
        echo "  → Response: $response\n";
    }
    
    echo "\n";
}

// Verfügbarkeitsprüfung in größerem Zeitraum
echo "=== Verfügbarkeit nächste 30 Tage ===\n";
$startDate = new DateTime('tomorrow');
$endDate = new DateTime('+30 days');

$url = "https://api.cal.com/v1/availability?" . http_build_query([
    'apiKey' => $apiKey,
    'eventTypeId' => $eventTypeId,
    'dateFrom' => $startDate->format('Y-m-d'),
    'dateTo' => $endDate->format('Y-m-d'),
    'username' => 'askproai'
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if (isset($data['busy'])) {
        echo "Anzahl gebuchte Termine: " . count($data['busy']) . "\n";
    }
    if (isset($data['dateRanges'])) {
        echo "Anzahl verfügbare Zeiträume: " . count($data['dateRanges']) . "\n";
    }
}
