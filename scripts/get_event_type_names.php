<?php

/**
 * Fetch Event Type details directly from Cal.com API
 * Get the actual names to verify composite service assignment
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$calcomApiKey = config('services.calcom.api_key');
$calcomBaseUrl = config('services.calcom.base_url');
$calcomApiVersion = config('services.calcom.api_version');

echo "═══════════════════════════════════════════════════════════════\n";
echo "Event Type Namen abrufen\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// All provided IDs grouped by batch
$idGroups = [
    'Dauerwelle' => [3757759, 3757800, 3757760, 3757761],
    'Gruppe 1 (vorher)' => [3757774, 3757775, 3757785, 3757786, 3757787, 3757801],
];

$allEventTypes = [];

foreach ($idGroups as $groupName => $ids) {
    echo "📦 {$groupName}:\n";
    echo str_repeat("─", 63) . "\n";

    foreach ($ids as $eventTypeId) {
        echo "Event Type {$eventTypeId}... ";

        try {
            // Try to get event type details directly
            $response = Http::withHeaders([
                'cal-api-version' => $calcomApiVersion,
                'Authorization' => "Bearer {$calcomApiKey}",
            ])->timeout(10)->get("{$calcomBaseUrl}/event-types/{$eventTypeId}");

            if ($response->successful()) {
                $data = $response->json();

                $name = $data['title'] ?? $data['name'] ?? $data['slug'] ?? 'Unbekannt';
                $duration = $data['length'] ?? $data['duration'] ?? '?';
                $slug = $data['slug'] ?? '';

                echo "✅\n";
                echo "  Name: {$name}\n";
                echo "  Dauer: {$duration} min\n";
                if ($slug) {
                    echo "  Slug: {$slug}\n";
                }

                // Check for composite pattern
                if (preg_match('/^(.+?):\s*(.+?)\s*\((\d+)\s+von\s+(\d+)\)/', $name, $matches)) {
                    $serviceName = trim($matches[1]);
                    $segmentDesc = trim($matches[2]);
                    $segNum = $matches[3];
                    $totalSegs = $matches[4];

                    echo "  ✅ COMPOSITE SEGMENT erkannt!\n";
                    echo "     Service: \"{$serviceName}\"\n";
                    echo "     Segment: \"{$segmentDesc}\" ({$segNum} von {$totalSegs})\n";

                    $allEventTypes[$eventTypeId] = [
                        'id' => $eventTypeId,
                        'name' => $name,
                        'service_name' => $serviceName,
                        'segment_desc' => $segmentDesc,
                        'segment_num' => (int)$segNum,
                        'total_segments' => (int)$totalSegs,
                        'duration' => $duration,
                    ];
                } else {
                    echo "  ⚠️  KEIN Composite Pattern im Namen\n";

                    $allEventTypes[$eventTypeId] = [
                        'id' => $eventTypeId,
                        'name' => $name,
                        'duration' => $duration,
                        'is_composite' => false,
                    ];
                }

            } else {
                echo "❌ Fehler {$response->status()}\n";
            }

        } catch (Exception $e) {
            echo "❌ Exception: {$e->getMessage()}\n";
        }

        echo "\n";
        usleep(300000); // Rate limiting
    }

    echo "\n";
}

echo str_repeat("═", 63) . "\n\n";

// Group by service name
echo "📊 GRUPPIERUNG NACH SERVICE:\n";
echo str_repeat("─", 63) . "\n\n";

$byService = [];

foreach ($allEventTypes as $et) {
    if (isset($et['service_name'])) {
        $serviceName = $et['service_name'];

        if (!isset($byService[$serviceName])) {
            $byService[$serviceName] = [];
        }

        $byService[$serviceName][] = $et;
    }
}

foreach ($byService as $serviceName => $segments) {
    echo "🎨 Service: \"{$serviceName}\"\n";
    echo "   Anzahl Segmente: " . count($segments) . "\n";
    echo "\n";

    // Sort by segment number
    usort($segments, fn($a, $b) => $a['segment_num'] <=> $b['segment_num']);

    foreach ($segments as $seg) {
        echo "   {$seg['segment_num']}. Event Type {$seg['id']}: {$seg['segment_desc']} ({$seg['duration']} min)\n";
    }

    echo "\n";

    // Try to match to database service
    $matchedServiceId = null;

    // Known mappings
    $serviceNameMap = [
        'Ansatzfärbung' => 440,
        'Ansatz + Längenausgleich' => 442,
        'Komplette Umfärbung (Blondierung)' => 444,
        'Komplette Umfärbung' => 444,
        'Blondierung' => 444,
        'Dauerwelle' => 441,
        'Balayage/Ombré' => 443,
        'Balayage' => 443,
        'Ombré' => 443,
    ];

    foreach ($serviceNameMap as $pattern => $serviceId) {
        if (stripos($serviceName, $pattern) !== false) {
            $matchedServiceId = $serviceId;
            break;
        }
    }

    if ($matchedServiceId) {
        echo "   → MATCHED zu Service ID: {$matchedServiceId}\n\n";

        // Generate mapping code
        if (count($segments) === 4) {
            echo "   💾 Mapping Code:\n";
            echo "   \$mappings_{$matchedServiceId} = [\n";

            $keys = ['A', 'B', 'C', 'D'];
            foreach ($segments as $index => $seg) {
                $key = $keys[$index] ?? '?';
                echo "       '{$key}' => {$seg['id']},  // ({$seg['segment_num']} von {$seg['total_segments']}) {$seg['segment_desc']}\n";
            }

            echo "   ];\n";
        } else {
            echo "   ⚠️  Anzahl Segmente ({count($segments)}) stimmt nicht mit erwarteten 4 überein!\n";
        }
    } else {
        echo "   ⚠️  KEIN Match zu bekanntem Service gefunden\n";
    }

    echo "\n";
}

echo str_repeat("═", 63) . "\n\n";

// Summary
echo "📋 ZUSAMMENFASSUNG:\n\n";

$compositeCount = count($byService);
$totalSegments = array_sum(array_map('count', $byService));

echo "Gefundene Composite Services: {$compositeCount}\n";
echo "Total Segmente: {$totalSegments}\n\n";

if ($compositeCount > 0) {
    echo "✅ Event Type Namen erfolgreich abgerufen!\n";
    echo "→ Mappings können jetzt erstellt werden\n\n";

    echo "NÄCHSTER SCHRITT:\n";
    echo "php scripts/create_composite_event_mappings.php\n";
} else {
    echo "❌ Keine Composite Services gefunden\n";
}

echo "\n" . str_repeat("═", 63) . "\n";
