<?php

/**
 * List all Event Types from Cal.com (including team event types)
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

$calcomApiKey = config('services.calcom.api_key');
$calcomBaseUrl = config('services.calcom.base_url');
$calcomApiVersion = config('services.calcom.api_version');

echo "═══════════════════════════════════════════════════════════════\n";
echo "Cal.com Event Types Liste\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// IDs die wir suchen
$searchIds = [
    // Dauerwelle
    3757759, 3757800, 3757760, 3757761,
    // Gruppe 1
    3757774, 3757775, 3757785, 3757786, 3757787, 3757801,
];

echo "🔍 Suche nach Event Type IDs:\n";
foreach ($searchIds as $id) {
    echo "  • {$id}\n";
}
echo "\n" . str_repeat("─", 63) . "\n\n";

// Try to fetch all event types
echo "📋 Abrufen aller Event Types...\n\n";

try {
    // Try /event-types endpoint
    $response = Http::withHeaders([
        'cal-api-version' => $calcomApiVersion,
        'Authorization' => "Bearer {$calcomApiKey}",
    ])->timeout(30)->get("{$calcomBaseUrl}/event-types");

    if ($response->successful()) {
        $data = $response->json();

        echo "✅ Event Types abgerufen\n\n";

        $eventTypes = $data['event_types'] ?? $data['data'] ?? $data ?? [];

        if (is_array($eventTypes) && !empty($eventTypes)) {
            echo "Anzahl Event Types: " . count($eventTypes) . "\n\n";

            $foundIds = [];
            $compositeEventTypes = [];

            foreach ($eventTypes as $et) {
                $id = $et['id'] ?? null;
                $name = $et['title'] ?? $et['name'] ?? $et['slug'] ?? 'Unbekannt';
                $duration = $et['length'] ?? $et['duration'] ?? '?';

                // Check if this is one of our search IDs
                if (in_array($id, $searchIds)) {
                    $foundIds[] = $id;

                    echo "✅ GEFUNDEN: Event Type {$id}\n";
                    echo "   Name: {$name}\n";
                    echo "   Dauer: {$duration} min\n";

                    // Check for composite pattern
                    if (preg_match('/^(.+?):\s*(.+?)\s*\((\d+)\s+von\s+(\d+)\)/', $name, $matches)) {
                        $serviceName = trim($matches[1]);
                        $segmentDesc = trim($matches[2]);
                        $segNum = (int)$matches[3];
                        $totalSegs = (int)$matches[4];

                        echo "   ✅ COMPOSITE SEGMENT!\n";
                        echo "      Service: \"{$serviceName}\"\n";
                        echo "      Segment: \"{$segmentDesc}\" ({$segNum} von {$totalSegs})\n";

                        if (!isset($compositeEventTypes[$serviceName])) {
                            $compositeEventTypes[$serviceName] = [];
                        }

                        $compositeEventTypes[$serviceName][] = [
                            'id' => $id,
                            'name' => $name,
                            'service_name' => $serviceName,
                            'segment_desc' => $segmentDesc,
                            'segment_num' => $segNum,
                            'total_segments' => $totalSegs,
                            'duration' => $duration,
                        ];
                    }

                    echo "\n";
                }
            }

            echo str_repeat("─", 63) . "\n\n";

            // Summary
            $notFound = array_diff($searchIds, $foundIds);

            echo "📊 ERGEBNIS:\n";
            echo "   Gefunden: " . count($foundIds) . " von " . count($searchIds) . "\n";

            if (!empty($notFound)) {
                echo "   Nicht gefunden: " . count($notFound) . "\n";
                echo "   IDs: " . implode(", ", $notFound) . "\n";
            }

            echo "\n";

            // Group and display composite services
            if (!empty($compositeEventTypes)) {
                echo str_repeat("─", 63) . "\n\n";
                echo "🎨 COMPOSITE SERVICES:\n\n";

                foreach ($compositeEventTypes as $serviceName => $segments) {
                    // Sort by segment number
                    usort($segments, fn($a, $b) => $a['segment_num'] <=> $b['segment_num']);

                    echo "Service: \"{$serviceName}\"\n";
                    echo "Anzahl Segmente: " . count($segments) . "\n\n";

                    foreach ($segments as $seg) {
                        echo "  {$seg['segment_num']}. Event Type {$seg['id']}: {$seg['segment_desc']} ({$seg['duration']} min)\n";
                    }

                    echo "\n";

                    // Try to match to database service
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

                    $matchedServiceId = null;
                    foreach ($serviceNameMap as $pattern => $serviceId) {
                        if (stripos($serviceName, $pattern) !== false) {
                            $matchedServiceId = $serviceId;
                            break;
                        }
                    }

                    if ($matchedServiceId) {
                        echo "  → Service ID in DB: {$matchedServiceId}\n\n";

                        if (count($segments) === 4) {
                            echo "  💾 Mapping Code:\n";
                            echo "  \$mappings_{$matchedServiceId} = [\n";

                            $keys = ['A', 'B', 'C', 'D'];
                            foreach ($segments as $index => $seg) {
                                $key = $keys[$index] ?? '?';
                                echo "      '{$key}' => {$seg['id']},  // ({$seg['segment_num']} von {$seg['total_segments']}) {$seg['segment_desc']}\n";
                            }

                            echo "  ];\n\n";
                        } else {
                            echo "  ⚠️  Anzahl Segmente stimmt nicht: " . count($segments) . " statt 4\n\n";
                        }
                    } else {
                        echo "  ⚠️  Kein Match zu DB Service\n\n";
                    }
                }
            }

        } else {
            echo "⚠️  Keine Event Types in Response gefunden\n";
            echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
        }

    } else {
        echo "❌ API Fehler: " . $response->status() . "\n";
        echo "Response: " . $response->body() . "\n";
    }

} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("═", 63) . "\n";
