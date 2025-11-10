<?php

/**
 * Create Composite Event Type Mappings
 *
 * ANLEITUNG:
 * 1. Cal.com UI öffnen: https://app.cal.com/event-types
 * 2. Filter aktivieren: "Hidden" Event Types anzeigen
 * 3. Nach "(1 von 4)", "(2 von 4)" etc. suchen
 * 4. Event Type öffnen → URL zeigt ID: /event-types/[ID]
 * 5. IDs unten eintragen (die "XXXXX" ersetzen)
 * 6. Script ausführen: php scripts/create_composite_event_mappings.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "═══════════════════════════════════════════════════════════════\n";
echo "Composite Event Type Mappings erstellen\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Konstanten für Branch und Staff
$branchId = '34c4d48e-4753-4715-9c30-c55843a943e8';  // Friseur 1 Zentrale
$staffId = '010be4a7-3468-4243-bb0a-2223b8e5878c';   // Emma Williams

// ══════════════════════════════════════════════════════════════════
// HIER DIE EVENT TYPE IDs AUS CAL.COM EINTRAGEN!
// ══════════════════════════════════════════════════════════════════

// Service 440: Ansatzfärbung
// Cal.com Event Type Name Pattern: "Ansatzfärbung: [Segment-Name] (X von 4)"
$mappings_440 = [
    'A' => 3757749,  // Event Type ID für "(1 von 4) Ansatzfärbung auftragen"
    'B' => 3757708,  // Event Type ID für "(2 von 4) Auswaschen"
    'C' => 3757751,  // Event Type ID für "(3 von 4) Haarschnitt"
    'D' => 3757709,  // Event Type ID für "(4 von 4) Föhnen & Styling"
];

// Service 442: Ansatz + Längenausgleich
// Cal.com Event Type Name Pattern: "Ansatz + Längenausgleich: [Segment-Name] (X von 4)"
$mappings_442 = [
    'A' => 3757699,  // Event Type ID für "(1 von 4) Ansatzfärbung & Längenausgleich auftragen"
    'B' => 3757700,  // Event Type ID für "(2 von 4) Auswaschen"
    'C' => 3757706,  // Event Type ID für "(3 von 4) Formschnitt"
    'D' => 3757701,  // Event Type ID für "(4 von 4) Föhnen & Styling"
];

// Service 444: Komplette Umfärbung (Blondierung)
// Cal.com Event Type Name Pattern: "Komplette Umfärbung (Blondierung): [Segment-Name] (X von 4)"
$mappings_444 = [
    'A' => 3757803,  // Event Type ID für "(1 von 4) Blondierung auftragen"
    'B' => 3757804,  // Event Type ID für "(2 von 4) Auswaschen & Pflege"
    'C' => 3757805,  // Event Type ID für "(3 von 4) Formschnitt"
    'D' => 3757806,  // Event Type ID für "(4 von 4) Föhnen & Styling"
];

// ══════════════════════════════════════════════════════════════════
// AB HIER NICHTS MEHR ÄNDERN
// ══════════════════════════════════════════════════════════════════

$services = [
    440 => ['name' => 'Ansatzfärbung', 'mappings' => $mappings_440],
    442 => ['name' => 'Ansatz + Längenausgleich', 'mappings' => $mappings_442],
    444 => ['name' => 'Komplette Umfärbung (Blondierung)', 'mappings' => $mappings_444],
];

// Validation
$totalMissing = 0;
$completeServices = [];
$incompleteServices = [];

echo "📋 VALIDIERUNG:\n";
echo str_repeat("─", 63) . "\n";

foreach ($services as $serviceId => $config) {
    $missing = array_filter($config['mappings'], fn($id) => $id === null);
    $missingCount = count($missing);
    $totalMissing += $missingCount;

    if ($missingCount > 0) {
        echo "⚠️  Service {$serviceId} ({$config['name']}): {$missingCount} IDs fehlen\n";
        foreach ($missing as $key => $value) {
            echo "     Segment {$key}: Event Type ID fehlt\n";
        }
        $incompleteServices[$serviceId] = $config;
    } else {
        echo "✅ Service {$serviceId} ({$config['name']}): Alle 4 IDs vorhanden\n";
        $completeServices[$serviceId] = $config;
    }
}

echo "\n";

if (empty($completeServices)) {
    echo "❌ FEHLER: Keine Services mit vollständigen Event Type IDs!\n\n";
    echo "📝 ANLEITUNG:\n";
    echo "1. Cal.com UI öffnen: https://app.cal.com/event-types\n";
    echo "2. Filter aktivieren: 'Hidden' Event Types anzeigen\n";
    echo "3. Suche: \"(1 von 4)\", \"(2 von 4)\" etc.\n";
    echo "4. Event Type öffnen → URL zeigt ID\n";
    echo "5. IDs in diesem Script eintragen (Zeile 26-49)\n";
    echo "6. Script erneut ausführen\n\n";
    echo str_repeat("═", 63) . "\n";
    exit(1);
}

if (!empty($incompleteServices)) {
    echo "ℹ️  {$totalMissing} Event Type IDs fehlen noch für:\n";
    foreach ($incompleteServices as $serviceId => $config) {
        echo "   • Service {$serviceId}: {$config['name']}\n";
    }
    echo "\n";
}

echo "✅ " . count($completeServices) . " Service(s) bereit für Mapping-Erstellung\n\n";
echo str_repeat("─", 63) . "\n\n";

// Create mappings for complete services only
echo "💾 Erstelle Mappings für vollständige Services...\n\n";

$created = 0;
$errors = 0;

foreach ($completeServices as $serviceId => $config) {
    echo "Service {$serviceId}: {$config['name']}\n";

    foreach ($config['mappings'] as $segmentKey => $eventTypeId) {
        try {
            // Check if mapping already exists
            $existing = DB::table('calcom_event_map')
                ->where('service_id', $serviceId)
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
                'service_id' => $serviceId,
                'segment_key' => $segmentKey,
                'staff_id' => $staffId,
                'event_type_id' => $eventTypeId,
                'event_name_pattern' => "FRISEUR-ZENTRALE-{$serviceId}-{$segmentKey}",
                'hidden' => true,  // Segments should be hidden
                'sync_status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            echo "  ✅ Segment {$segmentKey}: Event Type {$eventTypeId} gemappt\n";
            $created++;

        } catch (Exception $e) {
            echo "  ❌ Segment {$segmentKey}: Fehler - {$e->getMessage()}\n";
            $errors++;
        }
    }

    echo "\n";
}

echo str_repeat("─", 63) . "\n";
echo "📊 ZUSAMMENFASSUNG:\n";
echo "  ✅ Erstellt: {$created}\n";
echo "  ❌ Fehler: {$errors}\n";
echo str_repeat("═", 63) . "\n\n";

if ($created > 0) {
    echo "✅ ERFOLG! Composite Event Type Mappings erstellt.\n\n";

    echo "🔍 VERIFIKATION:\n";
    echo "php scripts/verify_composite_system.php\n\n";

    echo "Erwartung: 7/7 Checks bestanden (100%)\n\n";

    // Show created mappings
    echo "📋 Erstellte Mappings:\n\n";

    $mappings = DB::table('calcom_event_map')
        ->whereIn('service_id', [440, 442, 444])
        ->orderBy('service_id')
        ->orderBy('segment_key')
        ->get();

    foreach ($mappings as $mapping) {
        echo "Service {$mapping->service_id} | Segment {$mapping->segment_key} → Event Type {$mapping->event_type_id}\n";
    }

    echo "\n" . str_repeat("═", 63) . "\n";
    echo "🎉 SYSTEM 100% READY!\n";
    echo str_repeat("═", 63) . "\n";
}
