<?php

/**
 * Create Event Mappings for the 16 newly created services
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$companyId = 1;
$calcomTeamId = '34209';

// Event Type IDs (already created in Cal.com)
$eventTypeIds = [
    '3719738', // Kinderhaarschnitt
    '3719739', // Trockenschnitt
    '3719740', // Waschen & Styling
    '3719741', // Waschen, schneiden, föhnen
    '3719742', // Haarspende
    '3719743', // Beratung
    '3719744', // Hairdetox
    '3719745', // Rebuild Treatment Olaplex
    '3719746', // Intensiv Pflege Maria Nila
    '3719747', // Gloss
    '3719748', // Ansatzfärbung, waschen, schneiden, föhnen
    '3719749', // Ansatz, Längenausgleich, waschen, schneiden, föhnen
    '3719750', // Klassisches Strähnen-Paket
    '3719751', // Globale Blondierung
    '3719752', // Strähnentechnik Balayage
    '3719753', // Faceframe
];

echo "=== EVENT MAPPINGS ERSTELLEN ===" . PHP_EOL;
echo "Anzahl: " . count($eventTypeIds) . PHP_EOL;
echo PHP_EOL;

$created = 0;
$failed = 0;

foreach ($eventTypeIds as $idx => $eventTypeId) {
    $num = $idx + 1;
    echo "[$num/" . count($eventTypeIds) . "] Event Type ID: {$eventTypeId}..." . PHP_EOL;

    try {
        // Check if mapping already exists
        $existing = DB::table('calcom_event_mappings')
            ->where('calcom_event_type_id', $eventTypeId)
            ->where('company_id', $companyId)
            ->first();

        if ($existing) {
            echo "  ⚠️  Mapping existiert bereits (ID: {$existing->id})" . PHP_EOL;
            continue;
        }

        // Create Event Mapping
        $mappingId = DB::table('calcom_event_mappings')->insertGetId([
            'calcom_event_type_id' => $eventTypeId,
            'company_id' => $companyId,
            'calcom_team_id' => $calcomTeamId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        echo "  ✅ Mapping erstellt (ID: {$mappingId})" . PHP_EOL;
        $created++;

    } catch (Exception $e) {
        echo "  ❌ Exception: " . $e->getMessage() . PHP_EOL;
        $failed++;
    }

    echo PHP_EOL;
}

echo "=== ZUSAMMENFASSUNG ===" . PHP_EOL;
echo "✅ Erfolgreich: {$created}" . PHP_EOL;
echo "❌ Fehler: {$failed}" . PHP_EOL;
