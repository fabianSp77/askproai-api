<?php
/**
 * Fetch Cal.com Event Type details for the 3 active services
 */

$calcomApiKey = 'cal_live_5aa0ec8c80bd3de2c2c18f15fc95c6aa';
$eventTypeIds = [
    3757770, // Herrenhaarschnitt
    3757758, // Dauerwelle
    3757710  // Balayage/Ombré
];

echo "=== CAL.COM EVENT TYPE DETAILS ===\n\n";

foreach ($eventTypeIds as $eventTypeId) {
    echo "Event Type ID: {$eventTypeId}\n";

    $ch = curl_init("https://api.cal.com/v2/event-types/{$eventTypeId}");
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
        echo "  ❌ Failed to fetch (HTTP {$httpCode})\n";
        echo "  Response: {$response}\n\n";
        continue;
    }

    $eventType = json_decode($response, true);

    if (isset($eventType['data'])) {
        $data = $eventType['data'];
        echo "  Title: {$data['title']}\n";
        echo "  Length: " . ($data['lengthInMinutes'] ?? 'NOT SET') . " minutes\n";
        echo "  Slug: {$data['slug']}\n";

        // Check for price in metadata or custom fields
        if (isset($data['price'])) {
            echo "  Price: €{$data['price']}\n";
        } elseif (isset($data['metadata']['price'])) {
            echo "  Price (metadata): €{$data['metadata']['price']}\n";
        } else {
            echo "  Price: NOT SET\n";
        }

        echo "\n";
    } else {
        echo "  ⚠️  Unexpected response structure\n";
        echo json_encode($eventType, JSON_PRETTY_PRINT) . "\n\n";
    }
}

echo "=== END FETCH ===\n";
