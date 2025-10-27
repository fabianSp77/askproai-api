<?php

/**
 * Fix schedulingType with assignAllTeamMembers
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$baseUrl = rtrim(config('services.calcom.base_url'), '/');
$apiKey = config('services.calcom.api_key');
$teamId = 34209;

$eventTypeIds = range(3719738, 3719753); // All 16 new Event Types

echo "=== SCHEDULING TYPE KORRIGIEREN ===" . PHP_EOL;
echo "Anzahl: " . count($eventTypeIds) . PHP_EOL;
echo PHP_EOL;

$fixed = 0;
$failed = 0;

foreach ($eventTypeIds as $idx => $eventTypeId) {
    $num = $idx + 1;
    echo "[$num/" . count($eventTypeIds) . "] Event Type {$eventTypeId}..." . PHP_EOL;

    try {
        $payload = [
            'schedulingType' => 'ROUND_ROBIN',     // Uppercase!
            'assignAllTeamMembers' => true,        // ← DAS WAR DER FEHLENDE SCHLÜSSEL!
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'cal-api-version' => '2024-08-13',
            'Content-Type' => 'application/json'
        ])->patch($baseUrl . '/teams/' . $teamId . '/event-types/' . $eventTypeId, $payload);

        if ($response->successful()) {
            echo "  ✅ Aktualisiert" . PHP_EOL;
            $fixed++;
        } else {
            echo "  ❌ Fehler: " . $response->status() . PHP_EOL;
            echo "     " . $response->body() . PHP_EOL;
            $failed++;
        }

    } catch (Exception $e) {
        echo "  ❌ Exception: " . $e->getMessage() . PHP_EOL;
        $failed++;
    }
}

echo PHP_EOL;
echo "=== ZUSAMMENFASSUNG ===" . PHP_EOL;
echo "✅ Erfolgreich: {$fixed}" . PHP_EOL;
echo "❌ Fehler: {$failed}" . PHP_EOL;
