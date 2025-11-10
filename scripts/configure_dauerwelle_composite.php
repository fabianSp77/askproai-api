<?php

/**
 * Configure Service 441 (Dauerwelle) as Composite Service
 * Based on Cal.com Event Type information
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "═══════════════════════════════════════════════════════════════\n";
echo "Service 441 (Dauerwelle) als Composite Service konfigurieren\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Dauerwelle Segment-Definitionen basierend auf Cal.com
$segments = [
    [
        'key' => 'A',
        'name' => 'Haare wickeln',
        'durationMin' => 50,
        'gapAfterMin' => 15,  // Pause nach Segment (typisch für chemische Prozesse)
        'order' => 1,
    ],
    [
        'key' => 'B',
        'name' => 'Fixierung auftragen',
        'durationMin' => 5,
        'gapAfterMin' => 10,  // Kurze Einwirkzeit
        'order' => 2,
    ],
    [
        'key' => 'C',
        'name' => 'Auswaschen & Pflege',
        'durationMin' => 15,  // Geschätzt (nicht in Cal.com angegeben)
        'gapAfterMin' => 0,
        'order' => 3,
    ],
    [
        'key' => 'D',
        'name' => 'Schneiden & Styling',
        'durationMin' => 40,
        'gapAfterMin' => 0,
        'order' => 4,
    ],
];

// Berechne Gesamtdauer
$totalDuration = 0;
foreach ($segments as $seg) {
    $totalDuration += $seg['durationMin'] + $seg['gapAfterMin'];
}

echo "📋 SEGMENT-KONFIGURATION:\n";
echo str_repeat("─", 63) . "\n";

foreach ($segments as $seg) {
    $gap = $seg['gapAfterMin'] > 0 ? " + {$seg['gapAfterMin']}min Pause" : "";
    echo "  {$seg['key']}. {$seg['name']}: {$seg['durationMin']}min{$gap}\n";
}

echo "\nGesamtdauer: {$totalDuration} Minuten\n\n";

echo str_repeat("─", 63) . "\n\n";

// Check current service configuration
$service = DB::table('services')->where('id', 441)->first();

if (!$service) {
    echo "❌ Service 441 nicht gefunden!\n";
    exit(1);
}

echo "📊 AKTUELLER SERVICE-STATUS:\n";
echo "  ID: {$service->id}\n";
echo "  Name: {$service->name}\n";
echo "  Composite: " . ($service->composite ? 'JA' : 'NEIN') . "\n";
echo "  Dauer: {$service->duration_minutes} min\n";
echo "  Preis: {$service->price} €\n\n";

echo str_repeat("─", 63) . "\n\n";

// Update service to composite
echo "💾 Aktualisiere Service 441 als Composite...\n\n";

try {
    DB::table('services')
        ->where('id', 441)
        ->update([
            'composite' => true,
            'segments' => json_encode($segments),
            'duration_minutes' => $totalDuration,
            'pause_bookable_policy' => 'free',  // Staff ist während Pausen verfügbar
            'updated_at' => now(),
        ]);

    echo "✅ Service 441 erfolgreich als Composite konfiguriert!\n\n";

    // Verify
    $updated = DB::table('services')->where('id', 441)->first();

    echo "📊 AKTUALISIERTER STATUS:\n";
    echo "  Composite: " . ($updated->composite ? '✅ JA' : '❌ NEIN') . "\n";
    echo "  Segmente: " . count(json_decode($updated->segments, true)) . "\n";
    echo "  Gesamtdauer: {$updated->duration_minutes} min\n";
    echo "  Pause Policy: {$updated->pause_bookable_policy}\n\n";

} catch (Exception $e) {
    echo "❌ Fehler: {$e->getMessage()}\n";
    exit(1);
}

echo str_repeat("═", 63) . "\n\n";

// Now create Event Type Mappings
echo "💾 Erstelle Event Type Mappings...\n\n";

$branchId = '34c4d48e-4753-4715-9c30-c55843a943e8';  // Friseur 1 Zentrale
$staffId = '010be4a7-3468-4243-bb0a-2223b8e5878c';   // Emma Williams

$eventTypeMappings = [
    'A' => 3757759,  // (1 von 4) Haare wickeln
    'B' => 3757800,  // (2 von 4) Fixierung auftragen
    'C' => 3757760,  // (3 von 4) Auswaschen & Pflege
    'D' => 3757761,  // (4 von 4) Schneiden & Styling
];

$created = 0;
$errors = 0;

foreach ($eventTypeMappings as $segmentKey => $eventTypeId) {
    try {
        // Check if mapping already exists
        $existing = DB::table('calcom_event_map')
            ->where('service_id', 441)
            ->where('segment_key', $segmentKey)
            ->where('staff_id', $staffId)
            ->first();

        if ($existing) {
            echo "  ⚠️  Segment {$segmentKey}: Mapping existiert bereits (ID: {$existing->id})\n";
            continue;
        }

        // Create mapping
        DB::table('calcom_event_map')->insert([
            'company_id' => 1,
            'branch_id' => $branchId,
            'service_id' => 441,
            'segment_key' => $segmentKey,
            'staff_id' => $staffId,
            'event_type_id' => $eventTypeId,
            'event_name_pattern' => "FRISEUR-ZENTRALE-441-{$segmentKey}",
            'hidden' => true,  // Segments should be hidden
            'sync_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $segmentName = $segments[array_search($segmentKey, array_column($segments, 'key'))]['name'];
        echo "  ✅ Segment {$segmentKey}: Event Type {$eventTypeId} gemappt ({$segmentName})\n";
        $created++;

    } catch (Exception $e) {
        echo "  ❌ Segment {$segmentKey}: Fehler - {$e->getMessage()}\n";
        $errors++;
    }
}

echo "\n" . str_repeat("─", 63) . "\n";
echo "📊 ZUSAMMENFASSUNG:\n";
echo "  ✅ Erstellt: {$created}\n";
echo "  ❌ Fehler: {$errors}\n";
echo str_repeat("═", 63) . "\n\n";

if ($created > 0) {
    echo "✅ ERFOLG! Dauerwelle (Service 441) ist jetzt vollständig konfiguriert!\n\n";

    // Show created mappings
    echo "📋 Erstellte Mappings:\n\n";

    $mappings = DB::table('calcom_event_map')
        ->where('service_id', 441)
        ->orderBy('segment_key')
        ->get();

    foreach ($mappings as $mapping) {
        echo "  Service 441 | Segment {$mapping->segment_key} → Event Type {$mapping->event_type_id}\n";
    }

    echo "\n" . str_repeat("═", 63) . "\n";
    echo "🎉 SERVICE 441 (DAUERWELLE) 100% READY!\n";
    echo str_repeat("═", 63) . "\n";
}
