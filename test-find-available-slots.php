<?php
require_once __DIR__ . '/vendor/autoload.php';

$apiKey = 'cal_live_bd7aedbdf12c63fa7b23dd0bae6e5f02ee936231a37e926e21f256e8c96cf966';
$eventTypeId = 2026302;

echo "=== Suche nach ersten verfügbaren Slots ===\n\n";

// Teste die nächsten 30 Tage
for ($i = 0; $i < 30; $i++) {
    $date = new DateTime('now', new DateTimeZone('Europe/Berlin'));
    $date->add(new DateInterval('P' . $i . 'D'));
    
    $dateFrom = $date->format('Y-m-d\T00:00:00\Z');
    $dateTo = $date->format('Y-m-d\T23:59:59\Z');
    
    $url = "https://api.cal.com/v1/availability?apiKey=$apiKey&eventTypeId=$eventTypeId&dateFrom=$dateFrom&dateTo=$dateTo";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if (isset($data['slots']) && is_array($data['slots']) && count($data['slots']) > 0) {
            echo "✅ ERSTER VERFÜGBARER TAG: " . $date->format('l, d.m.Y') . "\n";
            echo "   Anzahl Slots: " . count($data['slots']) . "\n";
            echo "   Erste 3 Slots:\n";
            
            $counter = 0;
            foreach ($data['slots'] as $slot) {
                if ($counter >= 3) break;
                $slotTime = new DateTime($slot);
                echo "   - " . $slotTime->format('H:i') . " Uhr\n";
                $counter++;
            }
            
            echo "\n";
            break; // Stoppe nach dem ersten Tag mit verfügbaren Slots
        }
    }
}

// Zusätzlich: Prüfe Event-Type Einstellungen
echo "\n=== Event-Type Details prüfen ===\n";
$url = "https://api.cal.com/v1/event-types?apiKey=$apiKey";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $eventTypes = json_decode($response, true);
    
    if (isset($eventTypes['event_types'])) {
        foreach ($eventTypes['event_types'] as $type) {
            if ($type['id'] == $eventTypeId) {
                echo "Event-Type: " . $type['title'] . "\n";
                echo "Dauer: " . $type['length'] . " Minuten\n";
                
                if (isset($type['minimumBookingNotice'])) {
                    echo "⚠️  Vorlaufzeit: " . $type['minimumBookingNotice'] . " Minuten\n";
                    $hours = $type['minimumBookingNotice'] / 60;
                    $days = $hours / 24;
                    echo "   = " . number_format($hours, 1) . " Stunden = " . number_format($days, 1) . " Tage\n";
                }
                
                if (isset($type['periodType'])) {
                    echo "Buchungszeitraum: " . $type['periodType'] . "\n";
                }
                
                if (isset($type['periodDays'])) {
                    echo "Buchbar für: " . $type['periodDays'] . " Tage im Voraus\n";
                }
                
                break;
            }
        }
    }
}
?>
