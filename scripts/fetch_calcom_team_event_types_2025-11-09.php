<?php
/**
 * Fetch Cal.com team event types directly to see what data they contain
 */

$calcomApiKey = 'cal_live_c222d2419a4eb64fad7b767b3a756b23';
$teamId = 34209;

echo "=== CAL.COM TEAM EVENT TYPES ===\n\n";

$ch = curl_init("https://api.cal.com/v2/teams/{$teamId}/event-types");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$calcomApiKey}",
        "Content-Type: application/json",
        "cal-api-version: 2024-08-13"
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ Failed to fetch event types (HTTP {$httpCode})\n";
    echo "Response: {$response}\n";
    exit(1);
}

$data = json_decode($response, true);
$eventTypes = $data['data'] ?? [];

echo "Found " . count($eventTypes) . " event types\n\n";

// Find our 3 active services
$targetEventTypes = [
    3757770 => 'Herrenhaarschnitt',
    3757758 => 'Dauerwelle',
    3757710 => 'Balayage/Ombré'
];

echo "Looking for our 3 active services:\n\n";

foreach ($eventTypes as $eventType) {
    $id = $eventType['id'] ?? null;

    if (!isset($targetEventTypes[$id])) {
        continue;
    }

    echo "Event Type: {$targetEventTypes[$id]}\n";
    echo "  ID: {$id}\n";
    echo "  Title: " . ($eventType['title'] ?? 'N/A') . "\n";
    echo "  Slug: " . ($eventType['slug'] ?? 'N/A') . "\n";
    echo "  Length: " . ($eventType['lengthInMinutes'] ?? 'N/A') . " minutes\n";

    // Check various possible price fields
    if (isset($eventType['price'])) {
        echo "  Price: €{$eventType['price']}\n";
    } elseif (isset($eventType['metadata']['price'])) {
        echo "  Price (metadata): €{$eventType['metadata']['price']}\n";
    } else {
        echo "  Price: NOT SET\n";
    }

    echo "  Hidden: " . (($eventType['hidden'] ?? false) ? 'YES' : 'NO') . "\n";

    // Show all available keys
    echo "  Available keys: " . implode(', ', array_keys($eventType)) . "\n";

    echo "\n";
}

echo "=== END FETCH ===\n";
