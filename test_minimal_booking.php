<?php
require 'vendor/autoload.php';

$apiKey = 'cal_live_e9aa2c4d18e0fd79cf4f8dddb90903da';
$eventTypeId = 2026302; // Wir passen das später an

// Hole erst die Event Type Details
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.cal.com/v2/event-types/{$eventTypeId}?apiKey={$apiKey}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$eventResponse = curl_exec($ch);
$eventData = json_decode($eventResponse, true);
curl_close($ch);

if (!isset($eventData['data'])) {
    die("Event Type nicht gefunden\n");
}

$event = $eventData['data'];
echo "Event Type: {$event['title']} (ID: {$event['id']})\n";

// Finde den nächsten verfügbaren Slot
$dateFrom = (new DateTime())->add(new DateInterval('P1D'))->format('Y-m-d');
$dateTo = (new DateTime())->add(new DateInterval('P7D'))->format('Y-m-d');

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.cal.com/v2/slots/available?apiKey={$apiKey}&eventTypeId={$eventTypeId}&startTime={$dateFrom}&endTime={$dateTo}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$slotsResponse = curl_exec($ch);
$slotsData = json_decode($slotsResponse, true);
curl_close($ch);

if (empty($slotsData['data']['slots'])) {
    die("Keine verfügbaren Slots gefunden\n");
}

$slot = $slotsData['data']['slots'][0];
echo "Verwende Slot: {$slot['time']}\n";

// Minimale Buchung
$bookingData = [
    'start' => $slot['time'],
    'eventTypeId' => (int)$eventTypeId,
    'attendee' => [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'timeZone' => 'Europe/Berlin'
    ],
    'language' => 'de'
];

echo "Sende Buchung...\n";
echo "Request: " . json_encode($bookingData, JSON_PRETTY_PRINT) . "\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.cal.com/v2/bookings?apiKey={$apiKey}");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($bookingData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status: {$httpCode}\n";
echo "Response: " . json_encode(json_decode($response), JSON_PRETTY_PRINT) . "\n";
