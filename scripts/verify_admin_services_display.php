<?php

/**
 * Verify Admin Services Page Display
 * Check all data that should be shown correctly
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "═══════════════════════════════════════════════════════════════\n";
echo "Admin Services Page - Daten Verifikation\n";
echo "URL: https://api.askproai.de/admin/services\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Get all services
$services = DB::table('services')
    ->where('company_id', 1)
    ->orderBy('priority')
    ->get();

echo "📊 ALLE SERVICES FÜR FRISEUR 1:\n";
echo str_repeat("─", 100) . "\n";
printf("%-4s | %-40s | %-8s | %-6s | %-10s | %-12s | %s\n",
    "ID", "Name", "Preis", "Dauer", "Event Type", "Aktiv", "Typ");
echo str_repeat("─", 100) . "\n";

$issues = [];
$compositeServices = [];

foreach ($services as $svc) {
    $price = $svc->price ? number_format($svc->price, 2) . '€' : '❌ FEHLT';
    $duration = $svc->duration_minutes ? $svc->duration_minutes . 'min' : '❌ FEHLT';
    $eventType = $svc->calcom_event_type_id ?? '❌ FEHLT';
    $active = $svc->is_active ? '✅' : '❌';
    $type = $svc->composite ? 'COMPOSITE' : 'Standard';

    printf("%-4s | %-40s | %-8s | %-6s | %-10s | %-12s | %s\n",
        $svc->id,
        substr($svc->name, 0, 40),
        $price,
        $duration,
        $eventType,
        $active,
        $type
    );

    // Check for issues
    if (!$svc->price || $svc->price <= 0) {
        $issues[] = "Service {$svc->id} ({$svc->name}): Preis fehlt oder ist 0";
    }

    if (!$svc->duration_minutes || $svc->duration_minutes <= 0) {
        $issues[] = "Service {$svc->id} ({$svc->name}): Dauer fehlt oder ist 0";
    }

    if (!$svc->calcom_event_type_id) {
        $issues[] = "Service {$svc->id} ({$svc->name}): Event Type ID fehlt";
    }

    if (!$svc->is_active) {
        $issues[] = "Service {$svc->id} ({$svc->name}): Ist INAKTIV";
    }

    if ($svc->composite) {
        $compositeServices[] = $svc;
    }
}

echo str_repeat("─", 100) . "\n";
echo "Total: " . count($services) . " Services\n\n";

// Check for issues
if (!empty($issues)) {
    echo "⚠️  GEFUNDENE PROBLEME:\n";
    foreach ($issues as $issue) {
        echo "  • {$issue}\n";
    }
    echo "\n";
} else {
    echo "✅ KEINE PROBLEME GEFUNDEN - Alle Basis-Daten korrekt!\n\n";
}

// Detailed composite services check
if (!empty($compositeServices)) {
    echo str_repeat("═", 100) . "\n\n";
    echo "🎨 COMPOSITE SERVICES DETAIL-ÜBERPRÜFUNG:\n\n";

    foreach ($compositeServices as $svc) {
        echo "Service {$svc->id}: {$svc->name}\n";
        echo str_repeat("─", 100) . "\n";

        // Basic info
        echo "Basis-Informationen:\n";
        echo "  • Preis: " . number_format($svc->price, 2) . " €\n";
        echo "  • Gesamtdauer: {$svc->duration_minutes} min\n";
        echo "  • Event Type ID: {$svc->calcom_event_type_id}\n";
        echo "  • Aktiv: " . ($svc->is_active ? 'Ja ✅' : 'Nein ❌') . "\n";
        echo "  • Pause Policy: " . ($svc->pause_bookable_policy ?? 'NICHT GESETZT ❌') . "\n";
        echo "\n";

        // Segments
        $segments = json_decode($svc->segments, true);
        if (!$segments) {
            echo "  ❌ FEHLER: Keine Segmente definiert!\n\n";
            continue;
        }

        echo "Segmente (" . count($segments) . " Stück):\n";

        $totalCalculatedDuration = 0;
        foreach ($segments as $seg) {
            $segDuration = $seg['durationMin'] ?? 0;
            $segGap = $seg['gapAfterMin'] ?? 0;
            $totalCalculatedDuration += $segDuration + $segGap;

            $gapText = $segGap > 0 ? " + {$segGap}min Pause" : "";
            echo "  {$seg['key']}. {$seg['name']}: {$segDuration}min{$gapText}\n";
        }

        echo "\n";

        // Verify duration calculation
        echo "Dauer-Überprüfung:\n";
        echo "  • Berechnete Dauer (Segmente + Pausen): {$totalCalculatedDuration} min\n";
        echo "  • Gespeicherte Gesamtdauer: {$svc->duration_minutes} min\n";

        if ($totalCalculatedDuration === $svc->duration_minutes) {
            echo "  ✅ Dauer stimmt überein!\n";
        } else {
            $diff = abs($totalCalculatedDuration - $svc->duration_minutes);
            echo "  ⚠️  ABWEICHUNG: {$diff} Minuten Differenz\n";
        }

        echo "\n";

        // Event Type Mappings
        $mappings = DB::table('calcom_event_map')
            ->where('service_id', $svc->id)
            ->orderBy('segment_key')
            ->get(['segment_key', 'event_type_id', 'hidden']);

        echo "Event Type Mappings:\n";
        if ($mappings->count() === 0) {
            echo "  ❌ KEINE MAPPINGS GEFUNDEN!\n";
        } else if ($mappings->count() < count($segments)) {
            echo "  ⚠️  NUR {$mappings->count()} von " . count($segments) . " Segmenten gemappt!\n";
        } else {
            echo "  ✅ Alle " . count($segments) . " Segmente gemappt\n";
            foreach ($mappings as $m) {
                $hiddenStatus = $m->hidden ? '🔒 Hidden' : '👁️  Visible';
                echo "    Segment {$m->segment_key} → Event Type {$m->event_type_id} ({$hiddenStatus})\n";
            }
        }

        echo "\n";

        // What should be displayed in Admin UI
        echo "📋 Erwartete Anzeige im Admin:\n";
        echo "  • Name: \"{$svc->name}\"\n";
        echo "  • Typ-Badge: \"COMPOSITE\" oder \"Mehrteilig\"\n";
        echo "  • Preis: " . number_format($svc->price, 2) . " € (Gesamtpreis)\n";
        echo "  • Dauer: {$svc->duration_minutes} min (Gesamtdauer inkl. Pausen)\n";
        echo "  • Segmente: " . count($segments) . " Teile\n";
        echo "  • Segment-Details sollten aufklappbar/sichtbar sein\n";

        echo "\n" . str_repeat("─", 100) . "\n\n";
    }
}

// Check Filament Resource configuration
echo str_repeat("═", 100) . "\n\n";
echo "🔍 FILAMENT ADMIN UI KONFIGURATION:\n\n";

$resourceFile = app_path('Filament/Resources/ServiceResource.php');

if (file_exists($resourceFile)) {
    echo "✅ ServiceResource.php gefunden\n";
    echo "Pfad: {$resourceFile}\n\n";

    // Check if composite field is displayed
    $content = file_get_contents($resourceFile);

    echo "Überprüfe Composite-Felder:\n";

    if (strpos($content, 'composite') !== false) {
        echo "  ✅ 'composite' Field gefunden\n";
    } else {
        echo "  ⚠️  'composite' Field nicht gefunden\n";
    }

    if (strpos($content, 'segments') !== false) {
        echo "  ✅ 'segments' Field gefunden\n";
    } else {
        echo "  ⚠️  'segments' Field nicht gefunden\n";
    }

    if (strpos($content, 'duration_minutes') !== false) {
        echo "  ✅ 'duration_minutes' Field gefunden\n";
    } else {
        echo "  ⚠️  'duration_minutes' Field nicht gefunden\n";
    }

    if (strpos($content, 'pause_bookable_policy') !== false) {
        echo "  ✅ 'pause_bookable_policy' Field gefunden\n";
    } else {
        echo "  ⚠️  'pause_bookable_policy' Field nicht gefunden\n";
    }

} else {
    echo "❌ ServiceResource.php NICHT GEFUNDEN!\n";
    echo "Erwarteter Pfad: {$resourceFile}\n";
}

echo "\n" . str_repeat("═", 100) . "\n\n";

// Summary
echo "📊 ZUSAMMENFASSUNG:\n\n";

$totalServices = count($services);
$activeServices = count(array_filter($services->toArray(), fn($s) => $s->is_active));
$compositeCount = count($compositeServices);
$standardCount = $totalServices - $compositeCount;

echo "Services:\n";
echo "  • Total: {$totalServices}\n";
echo "  • Aktiv: {$activeServices}\n";
echo "  • Standard: {$standardCount}\n";
echo "  • Composite: {$compositeCount}\n";
echo "\n";

echo "Datenqualität:\n";
if (empty($issues)) {
    echo "  ✅ Alle Basis-Daten korrekt (Preis, Dauer, Event Type ID)\n";
} else {
    echo "  ⚠️  " . count($issues) . " Probleme gefunden (siehe oben)\n";
}

echo "\n";

echo "Composite Services:\n";
if ($compositeCount > 0) {
    $allMapped = true;
    foreach ($compositeServices as $cs) {
        $mappingCount = DB::table('calcom_event_map')->where('service_id', $cs->id)->count();
        $segments = json_decode($cs->segments, true);
        if ($mappingCount < count($segments)) {
            $allMapped = false;
            break;
        }
    }

    if ($allMapped) {
        echo "  ✅ Alle Composite Services vollständig gemappt\n";
    } else {
        echo "  ⚠️  Einige Composite Services unvollständig gemappt\n";
    }
}

echo "\n" . str_repeat("═", 100) . "\n\n";

echo "🌐 ADMIN SEITE TESTEN:\n";
echo "URL: https://api.askproai.de/admin/services\n\n";

echo "Zu prüfen:\n";
echo "  1. ✅ Alle {$totalServices} Services werden angezeigt\n";
echo "  2. ✅ Composite Services haben Badge/Kennzeichnung\n";
echo "  3. ✅ Dauern werden korrekt angezeigt (inkl. Segmente + Pausen)\n";
echo "  4. ✅ Preise werden korrekt angezeigt\n";
echo "  5. ✅ Event Type IDs sind sichtbar\n";
echo "  6. ✅ Segment-Details sind sichtbar/aufklappbar\n";
echo "  7. ✅ Status (Aktiv/Inaktiv) korrekt angezeigt\n\n";

echo str_repeat("═", 100) . "\n";
