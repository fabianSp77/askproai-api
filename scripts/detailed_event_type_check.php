<?php

/**
 * Detailed check of 6 Event Type IDs - try to extract names/metadata
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
echo "Detaillierte Event Type Analyse\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$idsToCheck = [3757774, 3757775, 3757785, 3757786, 3757787, 3757801];

$startTime = Carbon::now('Europe/Berlin')->addDays(1)->setTime(14, 0);
$endTime = Carbon::now('Europe/Berlin')->addDays(1)->setTime(15, 0);

$eventTypeDetails = [];

foreach ($idsToCheck as $id) {
    echo "Event Type {$id}:\n";
    echo str_repeat("─", 63) . "\n";

    try {
        $response = Http::withHeaders([
            'cal-api-version' => $calcomApiVersion,
            'Authorization' => "Bearer {$calcomApiKey}",
        ])->timeout(10)->get("{$calcomBaseUrl}/slots/available", [
            'eventTypeId' => $id,
            'startTime' => $startTime->toIso8601String(),
            'endTime' => $endTime->toIso8601String(),
        ]);

        if ($response->successful()) {
            $data = $response->json();

            echo "  Status: ✅ AKTIV\n";

            // Try to extract event type info
            $eventTypeName = null;
            $duration = null;
            $slug = null;

            // Check different possible response structures
            if (isset($data['eventType'])) {
                $et = $data['eventType'];
                $eventTypeName = $et['title'] ?? $et['name'] ?? $et['slug'] ?? null;
                $duration = $et['length'] ?? $et['duration'] ?? null;
                $slug = $et['slug'] ?? null;
            } elseif (isset($data['data']['eventType'])) {
                $et = $data['data']['eventType'];
                $eventTypeName = $et['title'] ?? $et['name'] ?? $et['slug'] ?? null;
                $duration = $et['length'] ?? $et['duration'] ?? null;
                $slug = $et['slug'] ?? null;
            }

            if ($eventTypeName) {
                echo "  Name: {$eventTypeName}\n";
            } else {
                echo "  Name: ❌ Nicht verfügbar\n";
            }

            if ($duration) {
                echo "  Dauer: {$duration} min\n";
            }

            if ($slug) {
                echo "  Slug: {$slug}\n";
            }

            // Check if name contains composite pattern
            if ($eventTypeName && preg_match('/\((\d+)\s+von\s+(\d+)\)/', $eventTypeName, $matches)) {
                $segNum = $matches[1];
                $totalSegs = $matches[2];
                echo "  → COMPOSITE SEGMENT: {$segNum} von {$totalSegs} ✅\n";

                // Try to determine service
                if (stripos($eventTypeName, 'Ansatzfärbung') !== false && stripos($eventTypeName, 'Längenausgleich') === false) {
                    echo "  → WAHRSCHEINLICH: Service 440 (Ansatzfärbung)\n";
                } elseif (stripos($eventTypeName, 'Ansatz') !== false && stripos($eventTypeName, 'Längenausgleich') !== false) {
                    echo "  → WAHRSCHEINLICH: Service 442 (Ansatz + Längenausgleich)\n";
                } elseif (stripos($eventTypeName, 'Blondierung') !== false || stripos($eventTypeName, 'Umfärbung') !== false) {
                    echo "  → WAHRSCHEINLICH: Service 444 (Komplette Umfärbung)\n";
                }
            } else {
                echo "  → KEIN Composite Segment Pattern erkannt\n";
            }

            // Store details
            $eventTypeDetails[$id] = [
                'name' => $eventTypeName,
                'duration' => $duration,
                'slug' => $slug,
                'response' => $data
            ];

        } else {
            echo "  Status: ❌ Fehler {$response->status()}\n";
            echo "  Error: " . $response->body() . "\n";
        }

    } catch (Exception $e) {
        echo "  Status: ❌ Exception\n";
        echo "  Error: " . $e->getMessage() . "\n";
    }

    echo "\n";
    usleep(500000); // Rate limiting: 500ms
}

echo str_repeat("═", 63) . "\n\n";

echo "📊 ZUSAMMENFASSUNG:\n\n";

$compositeSegments = [];
$nonComposites = [];

foreach ($eventTypeDetails as $id => $details) {
    if ($details['name'] && preg_match('/\((\d+)\s+von\s+(\d+)\)/', $details['name'])) {
        $compositeSegments[$id] = $details;
    } else {
        $nonComposites[$id] = $details;
    }
}

if (!empty($compositeSegments)) {
    echo "✅ COMPOSITE SEGMENTE gefunden: " . count($compositeSegments) . "\n\n";

    foreach ($compositeSegments as $id => $details) {
        echo "  Event Type {$id}: {$details['name']}\n";
    }
    echo "\n";
} else {
    echo "❌ KEINE Composite Segmente erkannt\n\n";
}

if (!empty($nonComposites)) {
    echo "ℹ️  Andere Event Types: " . count($nonComposites) . "\n\n";

    foreach ($nonComposites as $id => $details) {
        $name = $details['name'] ?? 'Unbekannt';
        echo "  Event Type {$id}: {$name}\n";
    }
    echo "\n";
}

echo str_repeat("═", 63) . "\n\n";

// Recommendations
echo "💡 EMPFEHLUNG:\n\n";

if (!empty($compositeSegments)) {
    echo "Die gefundenen Composite Segmente können gemappt werden.\n";
    echo "Prüfe die Namen, um zu bestätigen, dass sie zu Service 440 oder 442 gehören.\n\n";

    // Generate mapping suggestions
    echo "VORGESCHLAGENE MAPPINGS:\n\n";

    // Group by service based on name patterns
    $service440Segments = [];
    $service442Segments = [];

    foreach ($compositeSegments as $id => $details) {
        $name = $details['name'];

        if (stripos($name, 'Ansatzfärbung') !== false && stripos($name, 'Längenausgleich') === false) {
            $service440Segments[$id] = $details;
        } elseif (stripos($name, 'Ansatz') !== false && stripos($name, 'Längenausgleich') !== false) {
            $service442Segments[$id] = $details;
        }
    }

    if (!empty($service440Segments)) {
        echo "Service 440 (Ansatzfärbung):\n";
        foreach ($service440Segments as $id => $details) {
            echo "  Event Type {$id}: {$details['name']}\n";
        }
        echo "\n";
    }

    if (!empty($service442Segments)) {
        echo "Service 442 (Ansatz + Längenausgleich):\n";
        foreach ($service442Segments as $id => $details) {
            echo "  Event Type {$id}: {$details['name']}\n";
        }
        echo "\n";
    }

} else {
    echo "❌ Keine Composite Segment Event Types gefunden.\n\n";
    echo "Diese Event Type IDs gehören wahrscheinlich NICHT zu den Composite Services.\n\n";
    echo "MÖGLICHE GRÜNDE:\n";
    echo "  1. Es sind andere Services (z.B. einzelne Standard-Services)\n";
    echo "  2. Die Namen werden von der API nicht zurückgegeben (Hidden Event Types)\n";
    echo "  3. Die IDs gehören zu einem anderen Mandanten/Team\n\n";
    echo "NÄCHSTE SCHRITTE:\n";
    echo "  1. Prüfe in Cal.com UI, welche Services diese IDs repräsentieren\n";
    echo "  2. Suche nach den korrekten Segment Event Types mit Pattern '(X von 4)'\n";
    echo "  3. Stelle sicher, dass Filter 'Hidden' aktiviert ist\n\n";
}

echo str_repeat("═", 63) . "\n";
