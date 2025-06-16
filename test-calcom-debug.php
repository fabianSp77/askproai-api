<?php
require_once __DIR__ . '/vendor/autoload.php';

$apiKey = 'cal_live_bd7aedbdf12085c5312c79ba73585920';
$eventTypeId = 2026302;

echo "=== Cal.com Debug Test ===\n\n";

// 1. Event-Types abrufen
echo "1. Teste Event-Types Endpoint:\n";
$url = "https://api.cal.com/v1/event-types?apiKey=$apiKey";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($error) {
    echo "CURL Error: $error\n";
}
echo "Response (erste 500 Zeichen): " . substr($response, 0, 500) . "\n\n";

// 2. Spezifischen Event-Type abrufen
echo "2. Teste spezifischen Event-Type:\n";
$url = "https://api.cal.com/v1/event-types/$eventTypeId?apiKey=$apiKey";

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
    echo "Event-Type gefunden:\n";
    echo "- ID: " . ($data['id'] ?? 'N/A') . "\n";
    echo "- Title: " . ($data['title'] ?? 'N/A') . "\n";
    echo "- Length: " . ($data['length'] ?? 'N/A') . " Minuten\n";
    echo "- minimumBookingNotice: " . ($data['minimumBookingNotice'] ?? 'N/A') . " Minuten\n";
    echo "- periodType: " . ($data['periodType'] ?? 'N/A') . "\n";
    echo "- periodDays: " . ($data['periodDays'] ?? 'N/A') . "\n";
    echo "- periodCountCalendarDays: " . ($data['periodCountCalendarDays'] ?? 'N/A') . "\n";
    echo "- periodStartDate: " . ($data['periodStartDate'] ?? 'N/A') . "\n";
    echo "- periodEndDate: " . ($data['periodEndDate'] ?? 'N/A') . "\n";
} else {
    echo "Response: " . substr($response, 0, 500) . "\n";
}

echo "\n3. Teste Verfügbarkeit mit verschiedenen Zeiträumen:\n";

// Test heute + verschiedene Tage
$testDays = [0, 7, 14, 21, 30, 60, 90];

foreach ($testDays as $days) {
    $date = new DateTime('now', new DateTimeZone('Europe/Berlin'));
    $date->add(new DateInterval('P' . $days . 'D'));
    
    $dateFrom = $date->format('Y-m-d\T00:00:00\Z');
    $dateTo = $date->format('Y-m-d\T23:59:59\Z');
    
    echo "\nTeste +" . $days . " Tage (" . $date->format('d.m.Y') . "):\n";
    
    $url = "https://api.cal.com/v1/availability?apiKey=$apiKey&eventTypeId=$eventTypeId&dateFrom=$dateFrom&dateTo=$dateTo";
    
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
        if (isset($data['slots']) && is_array($data['slots'])) {
            $slotCount = count($data['slots']);
            echo "Slots gefunden: $slotCount\n";
            
            if ($slotCount > 0) {
                echo "✅ VERFÜGBAR! Erste 3 Slots:\n";
                $counter = 0;
                foreach ($data['slots'] as $slot) {
                    if ($counter >= 3) break;
                    $slotTime = new DateTime($slot);
                    echo "   - " . $slotTime->format('d.m.Y H:i') . "\n";
                    $counter++;
                }
            }
        } else {
            echo "Keine Slots-Daten in der Antwort\n";
        }
    } else {
        echo "Fehler-Response: " . substr($response, 0, 200) . "\n";
    }
}

// 4. Test mit größerem Zeitraum
echo "\n4. Teste großen Zeitraum (30 Tage ab heute):\n";
$dateFrom = (new DateTime('now', new DateTimeZone('Europe/Berlin')))->format('Y-m-d\T00:00:00\Z');
$dateTo = (new DateTime('now', new DateTimeZone('Europe/Berlin')))->add(new DateInterval('P30D'))->format('Y-m-d\T23:59:59\Z');

$url = "https://api.cal.com/v1/availability?apiKey=$apiKey&eventTypeId=$eventTypeId&dateFrom=$dateFrom&dateTo=$dateTo";

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
    
    if (isset($data['busy'])) {
        echo "Gebuchte Termine: " . count($data['busy']) . "\n";
    }
    
    if (isset($data['slots'])) {
        echo "Verfügbare Slots gesamt: " . count($data['slots']) . "\n";
        
        // Gruppiere Slots nach Datum
        $slotsByDate = [];
        foreach ($data['slots'] as $slot) {
            $slotDate = substr($slot, 0, 10);
            if (!isset($slotsByDate[$slotDate])) {
                $slotsByDate[$slotDate] = 0;
            }
            $slotsByDate[$slotDate]++;
        }
        
        echo "\nSlots pro Tag:\n";
        ksort($slotsByDate);
        foreach ($slotsByDate as $date => $count) {
            echo "  $date: $count Slots\n";
        }
    }
}
?>
