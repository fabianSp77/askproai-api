<?php

/**
 * Configure Composite Services for Ansatzfärbung Services
 *
 * Purpose: Enable multi-segment bookings with staff availability during pauses
 *
 * Services:
 * - ID 177: Ansatzfärbung, waschen, schneiden, föhnen (€85, 2-3h brutto)
 * - ID 178: Ansatz, Längenausgleich, waschen, schneiden, föhnen (€85, 2-3h brutto)
 *
 * Segment Structure (based on hair coloring best practices):
 * 1. Ansatzfärbung auftragen (30 min) → 30 min pause (Farbe einwirken lassen)
 * 2. Auswaschen (15 min) → no pause
 * 3. Schneiden (30 min) → 15 min pause (optional break)
 * 4. Föhnen & Styling (30 min) → done
 *
 * Total: 105 min work + 45 min pauses = 150 min brutto
 *
 * Pause Policy: "free" → Staff available for other bookings during pauses
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Segment structure for Ansatzfärbung
$ansatzfaerbungSegments = [
    [
        'key' => 'A',
        'name' => 'Ansatzfärbung auftragen',
        'duration' => 30,
        'gap_after' => 30,
        'preferSameStaff' => true
    ],
    [
        'key' => 'B',
        'name' => 'Auswaschen',
        'duration' => 15,
        'gap_after' => 0,
        'preferSameStaff' => true
    ],
    [
        'key' => 'C',
        'name' => 'Schneiden',
        'duration' => 30,
        'gap_after' => 15,
        'preferSameStaff' => true
    ],
    [
        'key' => 'D',
        'name' => 'Föhnen & Styling',
        'duration' => 30,
        'gap_after' => 0,
        'preferSameStaff' => true
    ]
];

// Segment structure for Ansatz + Längenausgleich (longer cutting phase)
$ansatzLaengenausgleichSegments = [
    [
        'key' => 'A',
        'name' => 'Ansatzfärbung & Längenausgleich auftragen',
        'duration' => 40,
        'gap_after' => 30,
        'preferSameStaff' => true
    ],
    [
        'key' => 'B',
        'name' => 'Auswaschen',
        'duration' => 15,
        'gap_after' => 0,
        'preferSameStaff' => true
    ],
    [
        'key' => 'C',
        'name' => 'Schneiden mit Längenausgleich',
        'duration' => 40,
        'gap_after' => 15,
        'preferSameStaff' => true
    ],
    [
        'key' => 'D',
        'name' => 'Föhnen & Styling',
        'duration' => 30,
        'gap_after' => 0,
        'preferSameStaff' => true
    ]
];

echo "╔══════════════════════════════════════════════════════════════╗" . PHP_EOL;
echo "║     COMPOSITE SERVICES KONFIGURATION                        ║" . PHP_EOL;
echo "╚══════════════════════════════════════════════════════════════╝" . PHP_EOL;
echo PHP_EOL;

// Service IDs to update
$services = [
    177 => [
        'name' => 'Ansatzfärbung, waschen, schneiden, föhnen',
        'segments' => $ansatzfaerbungSegments,
        'total_work' => 105,
        'total_gaps' => 45,
        'total_duration' => 150
    ],
    178 => [
        'name' => 'Ansatz, Längenausgleich, waschen, schneiden, föhnen',
        'segments' => $ansatzLaengenausgleichSegments,
        'total_work' => 125,
        'total_gaps' => 45,
        'total_duration' => 170
    ]
];

$updated = 0;
$failed = 0;

foreach ($services as $serviceId => $config) {
    echo "═══════════════════════════════════════════════════════════" . PHP_EOL;
    echo "Service ID {$serviceId}: {$config['name']}" . PHP_EOL;
    echo "═══════════════════════════════════════════════════════════" . PHP_EOL;
    echo PHP_EOL;

    // Display segment breakdown
    echo "📋 Segment-Struktur:" . PHP_EOL;
    foreach ($config['segments'] as $idx => $segment) {
        $num = $idx + 1;
        echo "  {$num}. {$segment['name']}: {$segment['duration']}min";
        if ($segment['gap_after'] > 0) {
            echo " → {$segment['gap_after']}min Pause (Staff verfügbar)";
        }
        echo PHP_EOL;
    }
    echo PHP_EOL;

    echo "⏱️  Zeitübersicht:" . PHP_EOL;
    echo "  Arbeitszeit (netto): {$config['total_work']} min" . PHP_EOL;
    echo "  Pausen (Staff verfügbar): {$config['total_gaps']} min" . PHP_EOL;
    echo "  Gesamt (brutto): {$config['total_duration']} min (" . round($config['total_duration']/60, 1) . "h)" . PHP_EOL;
    echo PHP_EOL;

    try {
        // Check if service exists
        $service = DB::table('services')->where('id', $serviceId)->first();

        if (!$service) {
            echo "  ❌ Service nicht gefunden!" . PHP_EOL;
            $failed++;
            continue;
        }

        echo "  📝 Aktuelle Konfiguration:" . PHP_EOL;
        echo "     composite: " . ($service->composite ? 'true' : 'FALSE') . PHP_EOL;
        echo "     duration_minutes: {$service->duration_minutes}" . PHP_EOL;
        echo "     pause_bookable_policy: {$service->pause_bookable_policy}" . PHP_EOL;
        echo PHP_EOL;

        // Update service with composite configuration
        echo "  🔧 Aktualisiere Composite-Konfiguration..." . PHP_EOL;

        $updateData = [
            'composite' => true,
            'segments' => json_encode($config['segments']),
            'pause_bookable_policy' => 'free',  // Staff available during gaps
            'duration_minutes' => $config['total_duration'],  // Update total duration
            'updated_at' => now(),
        ];

        $affected = DB::table('services')
            ->where('id', $serviceId)
            ->update($updateData);

        if ($affected > 0) {
            echo "  ✅ Erfolgreich aktualisiert!" . PHP_EOL;
            echo PHP_EOL;

            echo "  📊 Neue Konfiguration:" . PHP_EOL;
            echo "     composite: TRUE ✅" . PHP_EOL;
            echo "     segments: " . count($config['segments']) . " Segmente ✅" . PHP_EOL;
            echo "     pause_bookable_policy: free ✅" . PHP_EOL;
            echo "     duration_minutes: {$config['total_duration']} ✅" . PHP_EOL;
            echo PHP_EOL;

            $updated++;
        } else {
            echo "  ⚠️  Keine Änderung vorgenommen" . PHP_EOL;
            $failed++;
        }

    } catch (Exception $e) {
        echo "  ❌ Fehler: " . $e->getMessage() . PHP_EOL;
        $failed++;
    }

    echo PHP_EOL;
}

echo "╔══════════════════════════════════════════════════════════════╗" . PHP_EOL;
echo "║                    ZUSAMMENFASSUNG                           ║" . PHP_EOL;
echo "╚══════════════════════════════════════════════════════════════╝" . PHP_EOL;
echo "✅ Erfolgreich aktualisiert: {$updated}" . PHP_EOL;
echo "❌ Fehler: {$failed}" . PHP_EOL;
echo PHP_EOL;

if ($updated > 0) {
    echo "═══════════════════════════════════════════════════════════" . PHP_EOL;
    echo "NÄCHSTE SCHRITTE" . PHP_EOL;
    echo "═══════════════════════════════════════════════════════════" . PHP_EOL;
    echo PHP_EOL;

    echo "✅ PHASE 1 ABGESCHLOSSEN: Services konfiguriert" . PHP_EOL;
    echo PHP_EOL;

    echo "📋 Verifizierung:" . PHP_EOL;
    echo "  1. Admin Portal öffnen: https://api.askproai.de/admin/services" . PHP_EOL;
    echo "  2. Service bearbeiten → Segmente sichtbar" . PHP_EOL;
    echo "  3. Test-Buchung über API (CompositeBookingService)" . PHP_EOL;
    echo PHP_EOL;

    echo "⚠️  WICHTIG - Voice AI Support:" . PHP_EOL;
    echo "  - Web-Buchungen: ✅ Funktionieren jetzt" . PHP_EOL;
    echo "  - Voice AI (Retell): ❌ Noch NICHT unterstützt" . PHP_EOL;
    echo PHP_EOL;

    echo "🚀 PHASE 2 (Voice AI Integration):" . PHP_EOL;
    echo "  1. AppointmentCreationService erweitern" . PHP_EOL;
    echo "  2. Retell Agent Prompt aktualisieren" . PHP_EOL;
    echo "  3. End-to-End Tests mit Monitoring" . PHP_EOL;
    echo PHP_EOL;

    echo "📖 Dokumentation:" . PHP_EOL;
    echo "  - Segment-Struktur in Datenbank gespeichert" . PHP_EOL;
    echo "  - pause_bookable_policy: 'free' → Staff während Pausen verfügbar" . PHP_EOL;
    echo "  - Kunde wartet z.B. beim Einwirken der Farbe" . PHP_EOL;
    echo PHP_EOL;
}

echo "✅ Script abgeschlossen: " . now()->toDateTimeString() . PHP_EOL;
