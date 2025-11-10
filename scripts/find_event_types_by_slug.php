<?php

/**
 * Try to find Event Type IDs by testing Cal.com slugs
 * Based on the URL patterns from user's message
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

$calcomApiKey = config('services.calcom.api_key');
$calcomBaseUrl = config('services.calcom.base_url');
$calcomApiVersion = config('services.calcom.api_version');

echo "═══════════════════════════════════════════════════════════════\n";
echo "Event Type ID Suche über Cal.com Slugs\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "💡 Basierend auf den URLs aus deiner Nachricht:\n";
echo "   /team/friseur/ansatz-langenausgleich-formschnitt-3-von-4\n\n";

// Mögliche Slug-Patterns basierend auf Service-Namen
$slugPatterns = [
    // Service 440: Ansatzfärbung
    440 => [
        'A' => ['ansatzfarbung-auftragen-1-von-4', 'ansatzfaerbung-auftragen-1-von-4'],
        'B' => ['ansatzfarbung-auswaschen-2-von-4', 'ansatzfaerbung-auswaschen-2-von-4'],
        'C' => ['ansatzfarbung-formschnitt-3-von-4', 'ansatzfaerbung-formschnitt-3-von-4'],
        'D' => ['ansatzfarbung-fohnen-styling-4-von-4', 'ansatzfaerbung-foehnen-styling-4-von-4'],
    ],

    // Service 442: Ansatz + Längenausgleich
    442 => [
        'A' => ['ansatz-langenausgleich-auftragen-1-von-4', 'ansatz-laengenausgleich-auftragen-1-von-4'],
        'B' => ['ansatz-langenausgleich-auswaschen-2-von-4', 'ansatz-laengenausgleich-auswaschen-2-von-4'],
        'C' => ['ansatz-langenausgleich-formschnitt-3-von-4', 'ansatz-laengenausgleich-formschnitt-3-von-4'],
        'D' => ['ansatz-langenausgleich-fohnen-4-von-4', 'ansatz-laengenausgleich-foehnen-4-von-4'],
    ],

    // Service 444: Komplette Umfärbung (Blondierung)
    444 => [
        'A' => ['blondierung-auftragen-1-von-4', 'komplette-umfarbung-auftragen-1-von-4'],
        'B' => ['blondierung-auswaschen-2-von-4', 'komplette-umfarbung-auswaschen-2-von-4'],
        'C' => ['blondierung-formschnitt-3-von-4', 'komplette-umfarbung-formschnitt-3-von-4'],
        'D' => ['blondierung-fohnen-4-von-4', 'komplette-umfarbung-foehnen-4-von-4'],
    ],
];

$startTime = Carbon::now('Europe/Berlin')->addDays(1)->setTime(14, 0);
$endTime = Carbon::now('Europe/Berlin')->addDays(1)->setTime(15, 0);

echo "🔍 Teste Slug-Patterns mit Cal.com API...\n\n";

// Bekannte Event Type IDs zum Testen der Slug-Struktur
echo "📋 SCHRITT 1: Teste bekannte Event Type IDs um Pattern zu verstehen\n";
echo str_repeat("─", 63) . "\n";

$knownEventTypes = [
    3757697 => 'Ansatz + Längenausgleich (Haupt)',
    3757707 => 'Ansatzfärbung (Haupt)',
    3757773 => 'Komplette Umfärbung (Haupt)',
];

foreach ($knownEventTypes as $eventTypeId => $name) {
    echo "Testing Event Type {$eventTypeId} ({$name})...\n";

    try {
        $response = Http::withHeaders([
            'cal-api-version' => $calcomApiVersion,
            'Authorization' => "Bearer {$calcomApiKey}",
        ])->timeout(10)->get("{$calcomBaseUrl}/slots/available", [
            'eventTypeId' => $eventTypeId,
            'startTime' => $startTime->toIso8601String(),
            'endTime' => $endTime->toIso8601String(),
        ]);

        if ($response->successful()) {
            echo "  ✅ Event Type existiert und ist erreichbar\n";

            // Check for slug in response (if available)
            $data = $response->json();
            if (isset($data['data']['eventType'])) {
                $eventType = $data['data']['eventType'];
                if (isset($eventType['slug'])) {
                    echo "  📝 Slug: {$eventType['slug']}\n";
                }
            }
        } else {
            echo "  ❌ API Error: " . $response->status() . "\n";
        }
    } catch (Exception $e) {
        echo "  ❌ Exception: " . $e->getMessage() . "\n";
    }

    echo "\n";
    usleep(300000); // Rate limiting
}

echo str_repeat("─", 63) . "\n\n";
echo "📋 SCHRITT 2: Systematische ID-Suche (Event Types 3757600-3757900)\n";
echo str_repeat("─", 63) . "\n";
echo "⏱️  Dies wird einige Minuten dauern...\n\n";

$foundSegments = [];

// Suche in einem sinnvollen Bereich um die bekannten IDs
$searchRanges = [
    [3757690, 3757720], // Um Ansatz + Längenausgleich (3757697)
    [3757700, 3757780], // Um Ansatzfärbung (3757707) und Blondierung (3757773)
];

$totalTested = 0;
$found = 0;

foreach ($searchRanges as list($start, $end)) {
    echo "Range {$start}-{$end}:\n";

    for ($eventTypeId = $start; $eventTypeId <= $end; $eventTypeId++) {
        // Teste ob Event Type existiert
        try {
            $response = Http::withHeaders([
                'cal-api-version' => $calcomApiVersion,
                'Authorization' => "Bearer {$calcomApiKey}",
            ])->timeout(5)->get("{$calcomBaseUrl}/slots/available", [
                'eventTypeId' => $eventTypeId,
                'startTime' => $startTime->toIso8601String(),
                'endTime' => $endTime->toIso8601String(),
            ]);

            $totalTested++;

            if ($response->successful()) {
                $data = $response->json();

                // Try to extract name from response
                $eventTypeName = 'Unknown';
                if (isset($data['data']['eventType']['title'])) {
                    $eventTypeName = $data['data']['eventType']['title'];
                } elseif (isset($data['data']['eventType']['name'])) {
                    $eventTypeName = $data['data']['eventType']['name'];
                }

                // Check if name contains segment pattern
                if (preg_match('/\((\d+)\s+von\s+(\d+)\)/', $eventTypeName)) {
                    echo "  ✅ {$eventTypeId}: {$eventTypeName}\n";
                    $foundSegments[$eventTypeId] = $eventTypeName;
                    $found++;
                }
            }

            // Progress indicator every 10 IDs
            if ($totalTested % 10 === 0) {
                echo "  ...tested {$totalTested} IDs, found {$found} segments\n";
            }

        } catch (Exception $e) {
            // Silent fail, continue search
        }

        usleep(200000); // Rate limiting: 200ms zwischen Requests
    }

    echo "\n";
}

echo "\n" . str_repeat("═", 63) . "\n";
echo "📊 ERGEBNISSE\n";
echo str_repeat("═", 63) . "\n\n";

echo "Getestete Event Type IDs: {$totalTested}\n";
echo "Gefundene Segmente: {$found}\n\n";

if (!empty($foundSegments)) {
    echo "✅ GEFUNDENE SEGMENT EVENT TYPES:\n\n";

    foreach ($foundSegments as $eventTypeId => $name) {
        echo "Event Type {$eventTypeId}:\n";
        echo "  Name: {$name}\n";

        // Try to match to our services
        if (stripos($name, 'Ansatzfärbung') !== false && stripos($name, 'Längenausgleich') === false) {
            echo "  → Service 440 (Ansatzfärbung)\n";
        } elseif (stripos($name, 'Ansatz') !== false && stripos($name, 'Längenausgleich') !== false) {
            echo "  → Service 442 (Ansatz + Längenausgleich)\n";
        } elseif (stripos($name, 'Blondierung') !== false || stripos($name, 'Umfärbung') !== false) {
            echo "  → Service 444 (Komplette Umfärbung/Blondierung)\n";
        }

        echo "\n";
    }

    // Generate mapping code
    echo "💾 MAPPING CODE:\n\n";
    echo "// Copy this to scripts/create_composite_event_mappings.php\n\n";

    foreach ($foundSegments as $eventTypeId => $name) {
        // Extract segment number
        if (preg_match('/\((\d+)\s+von\s+(\d+)\)/', $name, $matches)) {
            $segNum = $matches[1];
            $keys = ['A', 'B', 'C', 'D', 'E', 'F'];
            $key = $keys[(int)$segNum - 1] ?? '?';

            echo "// '{$key}' => {$eventTypeId},  // {$name}\n";
        }
    }

} else {
    echo "❌ Keine Segment Event Types gefunden\n\n";
    echo "💡 Mögliche Gründe:\n";
    echo "   • Event Type IDs liegen außerhalb des Suchbereichs\n";
    echo "   • Event Types haben andere Namen (ohne Segment-Pattern)\n";
    echo "   • Event Types sind als 'hidden' markiert\n\n";
    echo "📝 Manuelle Erfassung erforderlich:\n";
    echo "   1. Cal.com UI öffnen: https://app.cal.com/event-types\n";
    echo "   2. Filter: 'Hidden' Event Types anzeigen\n";
    echo "   3. Nach \"(1 von 4)\", \"(2 von 4)\" etc. suchen\n";
    echo "   4. Event Type öffnen → URL zeigt ID\n";
}

echo "\n" . str_repeat("═", 63) . "\n";
