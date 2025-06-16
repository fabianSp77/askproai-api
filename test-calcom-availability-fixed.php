<?php
require_once __DIR__ . '/vendor/autoload.php';

// Aus der .env-Datei
$apiKey = 'cal_live_bd7aedbdf12085c5312c79ba73585920';
$userId = 1346408;
$username = 'fabianspitzer';
$teamSlug = 'askproai';

// Event-Types aus der .env
$eventTypes = [
    'Friseur' => 2281004,
    'Physio' => 2281265,
    'Tierarzt' => 2284875,
    'Herren' => 2031135,
    'Damen' => 2031368,
    '30min' => 2026302,
    '15min' => 2026301
];

echo "=== Cal.com Verfügbarkeitstest (korrigiert) ===\n\n";

// Teste verschiedene Event-Types
foreach ($eventTypes as $name => $eventTypeId) {
    echo "\n--- Teste Event-Type: $name (ID: $eventTypeId) ---\n";
    
    // Teste die nächsten 7 Tage
    for ($i = 0; $i < 7; $i++) {
        $date = new DateTime('now', new DateTimeZone('Europe/Berlin'));
        $date->add(new DateInterval('P' . $i . 'D'));
        
        $dateFrom = $date->format('Y-m-d\T00:00:00\Z');
        $dateTo = $date->format('Y-m-d\T23:59:59\Z');
        
        // Mit userId
        $url = "https://api.cal.com/v1/availability?apiKey=$apiKey&eventTypeId=$eventTypeId&userId=$userId&dateFrom=$dateFrom&dateTo=$dateTo";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            $slotCount = isset($data['slots']) ? count($data['slots']) : 0;
            
            if ($slotCount > 0) {
                echo "  Tag " . ($i + 1) . " (" . $date->format('d.m.Y') . "): ✅ $slotCount Slots verfügbar\n";
                
                // Zeige die ersten 3 Slots
                $counter = 0;
                foreach ($data['slots'] as $slot) {
                    if ($counter >= 3) break;
                    $slotTime = new DateTime($slot);
                    echo "    - " . $slotTime->format('H:i') . " Uhr\n";
                    $counter++;
                }
                
                break; // Stoppe nach dem ersten Tag mit verfügbaren Slots
            } else {
                echo "  Tag " . ($i + 1) . " (" . $date->format('d.m.Y') . "): ❌ Keine Slots\n";
            }
        } else {
            echo "  Tag " . ($i + 1) . ": Fehler - HTTP $httpCode\n";
            if ($httpCode !== 200) {
                $errorData = json_decode($response, true);
                echo "    Fehler: " . ($errorData['message'] ?? $response) . "\n";
            }
        }
    }
}

// Teste auch mit username statt userId
echo "\n\n--- Alternative: Teste mit username ---\n";
$eventTypeId = 2026302; // 30min Event-Type

$date = new DateTime('now', new DateTimeZone('Europe/Berlin'));
$dateFrom = $date->format('Y-m-d\T00:00:00\Z');
$dateTo = $date->add(new DateInterval('P7D'))->format('Y-m-d\T23:59:59\Z');

$url = "https://api.cal.com/v1/availability?apiKey=$apiKey&eventTypeId=$eventTypeId&username=$username&dateFrom=$dateFrom&dateTo=$dateTo";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo "Slots gefunden: " . (isset($data['slots']) ? count($data['slots']) : 0) . "\n";
}
?>
