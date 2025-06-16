<?php
require_once __DIR__ . '/vendor/autoload.php';

$apiKey = 'cal_live_bd7aedbdf12085c5312c79ba73585920';

echo "=== VOLLSTÄNDIGE CAL.COM ANALYSE ===\n\n";

// 1. Alle Event-Types abrufen, die zu diesem API-Key gehören
echo "1. Event-Types für diesen API-Key:\n";
echo "=====================================\n";

$url = "https://api.cal.com/v1/event-types?apiKey=$apiKey";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    
    if (isset($data['event_types']) && is_array($data['event_types'])) {
        echo "Gefundene Event-Types: " . count($data['event_types']) . "\n\n";
        
        foreach ($data['event_types'] as $eventType) {
            echo "Event-Type:\n";
            echo "  ID: " . $eventType['id'] . "\n";
            echo "  Title: " . $eventType['title'] . "\n";
            echo "  UserId: " . $eventType['userId'] . "\n";
            echo "  TeamId: " . ($eventType['teamId'] ?? 'null') . "\n";
            echo "  Length: " . $eventType['length'] . " min\n";
            echo "  Hidden: " . ($eventType['hidden'] ? 'Ja' : 'Nein') . "\n";
            echo "  MinimumBookingNotice: " . $eventType['minimumBookingNotice'] . " min\n";
            echo "  ScheduleId: " . ($eventType['scheduleId'] ?? 'null') . "\n";
            echo "  Slug: " . $eventType['slug'] . "\n";
            
            // Teste Verfügbarkeit für DIESEN Event-Type mit SEINER userId
            $testUserId = $eventType['userId'];
            $testEventTypeId = $eventType['id'];
            
            $tomorrow = new DateTime('tomorrow', new DateTimeZone('Europe/Berlin'));
            $dateFrom = $tomorrow->format('Y-m-d\T00:00:00\Z');
            $dateTo = $tomorrow->format('Y-m-d\T23:59:59\Z');
            
            $availUrl = "https://api.cal.com/v1/availability?apiKey=$apiKey&eventTypeId=$testEventTypeId&userId=$testUserId&dateFrom=$dateFrom&dateTo=$dateTo";
            
            $ch2 = curl_init();
            curl_setopt($ch2, CURLOPT_URL, $availUrl);
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            
            $availResponse = curl_exec($ch2);
            $availHttpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
            curl_close($ch2);
            
            echo "  Verfügbarkeitstest (morgen): ";
            if ($availHttpCode === 200) {
                $availData = json_decode($availResponse, true);
                $slotCount = isset($availData['slots']) ? count($availData['slots']) : 0;
                echo "✅ OK - $slotCount Slots verfügbar\n";
            } else {
                echo "❌ Fehler $availHttpCode\n";
            }
            
            echo "  ---\n\n";
        }
    }
} else {
    echo "Fehler beim Abrufen der Event-Types: HTTP $httpCode\n";
}

// 2. Team-Informationen prüfen
echo "\n2. Team-Informationen:\n";
echo "=====================================\n";

$teamSlug = 'askproai';
$url = "https://api.cal.com/v1/teams/$teamSlug?apiKey=$apiKey";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo "Team gefunden!\n";
    echo "  Name: " . ($data['name'] ?? 'N/A') . "\n";
    echo "  ID: " . ($data['id'] ?? 'N/A') . "\n";
} else {
    echo "Kein Team-Zugriff oder Team existiert nicht (HTTP $httpCode)\n";
}

// 3. User-Informationen
echo "\n3. User-Informationen:\n";
echo "=====================================\n";

$url = "https://api.cal.com/v1/me?apiKey=$apiKey";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo "User-Details:\n";
    echo "  ID: " . ($data['id'] ?? 'N/A') . "\n";
    echo "  Username: " . ($data['username'] ?? 'N/A') . "\n";
    echo "  Email: " . ($data['email'] ?? 'N/A') . "\n";
    echo "  Name: " . ($data['name'] ?? 'N/A') . "\n";
} else {
    echo "Fehler beim Abrufen der User-Informationen (HTTP $httpCode)\n";
}
?>
