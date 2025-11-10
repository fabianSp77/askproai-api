<?php

/**
 * Intelligent Segment Matching - Find Event Type IDs by name pattern
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "═══════════════════════════════════════════════════════════════\n";
echo "INTELLIGENTES SEGMENT-MATCHING: Namen-Analyse\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Hole alle Services
$allServices = DB::table('services')
    ->where('company_id', 1)
    ->get(['id', 'name', 'calcom_event_type_id', 'duration_minutes']);

echo "🔍 Suche nach Segment-Services mit \"(X von Y)\" Pattern...\n\n";

$segmentServices = [];

foreach ($allServices as $svc) {
    // Pattern: (1 von 4), (2 von 4), etc.
    if (preg_match('/\((\d+)\s+von\s+(\d+)\)/', $svc->name, $matches)) {
        $segmentNum = (int)$matches[1];
        $totalSegments = (int)$matches[2];

        // Extract base name (before the colon if present)
        $parts = explode(':', $svc->name);
        $baseName = trim($parts[0]);

        // Extract segment description (between colon and segment marker)
        $segmentDesc = '';
        if (count($parts) > 1) {
            $segmentDesc = preg_replace('/\s*\(\d+\s+von\s+\d+\).*$/', '', trim($parts[1]));
        }

        if (!isset($segmentServices[$baseName])) {
            $segmentServices[$baseName] = [];
        }

        $segmentServices[$baseName][$segmentNum] = [
            'id' => $svc->id,
            'name' => $svc->name,
            'segment_desc' => $segmentDesc,
            'event_type_id' => $svc->calcom_event_type_id,
            'duration' => $svc->duration_minutes,
            'segment_num' => $segmentNum,
            'total_segments' => $totalSegments
        ];
    }
}

if (empty($segmentServices)) {
    echo "❌ Keine Segment-Services in der Datenbank gefunden\n";
    echo "   Services werden vermutlich nur in Cal.com verwaltet\n\n";
    echo "💡 Das bedeutet:\n";
    echo "   • Segment Event Types existieren nur in Cal.com\n";
    echo "   • Sie sind NICHT in unserer services Tabelle\n";
    echo "   • Wir müssen sie manuell aus Cal.com UI ablesen\n\n";
} else {
    echo "✅ Gefundene Segment-Gruppen:\n\n";

    foreach ($segmentServices as $baseName => $segments) {
        echo "📦 {$baseName} (" . count($segments) . " Segmente)\n";
        echo str_repeat("─", 63) . "\n";

        ksort($segments);

        foreach ($segments as $segNum => $seg) {
            printf("   %d. %s\n", $segNum, $seg['name']);
            printf("      DB ID: %d | Event Type: %s | Dauer: %d min\n",
                $seg['id'],
                $seg['event_type_id'] ?? 'NULL',
                $seg['duration'] ?? 0
            );

            if (!empty($seg['segment_desc'])) {
                printf("      Beschreibung: %s\n", $seg['segment_desc']);
            }
        }
        echo "\n";
    }
}

echo str_repeat("─", 63) . "\n\n";

// Jetzt mit Composite Services matchen
echo "🎯 MATCHING mit Composite Services:\n\n";

$compositeServices = DB::table('services')
    ->where('composite', true)
    ->where('company_id', 1)
    ->get(['id', 'name', 'segments']);

foreach ($compositeServices as $composite) {
    $compositeSegments = json_decode($composite->segments, true) ?? [];

    echo "🎨 Service {$composite->id}: {$composite->name}\n";
    echo "   Definierte Segmente in DB: " . count($compositeSegments) . "\n";

    // Suche nach ähnlichen Namen in den Segment-Services
    $bestMatch = null;
    $bestScore = 0;

    foreach ($segmentServices as $baseName => $segments) {
        // String-Ähnlichkeit berechnen
        similar_text(
            strtolower($composite->name),
            strtolower($baseName),
            $score
        );

        if ($score > $bestScore) {
            $bestScore = $score;
            $bestMatch = $baseName;
        }
    }

    if ($bestMatch && $bestScore > 50) {
        echo "   ✅ MATCH GEFUNDEN: \"{$bestMatch}\" (Score: " . round($bestScore, 1) . "%)\n";

        if (isset($segmentServices[$bestMatch])) {
            echo "\n   📋 Event Type IDs für Mapping:\n";

            $segmentKeys = ['A', 'B', 'C', 'D', 'E', 'F'];
            $keyIndex = 0;

            foreach ($segmentServices[$bestMatch] as $segNum => $seg) {
                $key = $segmentKeys[$keyIndex] ?? '?';

                printf("      Segment %s (von %d): Event Type ID %s",
                    $key,
                    $segNum,
                    $seg['event_type_id'] ?? 'NULL'
                );

                if (!empty($seg['segment_desc'])) {
                    printf(" (%s)", $seg['segment_desc']);
                }

                echo "\n";
                $keyIndex++;
            }

            echo "\n   💾 Mapping-Code:\n";
            echo "   \$mappings_{$composite->id} = [\n";

            $keyIndex = 0;
            foreach ($segmentServices[$bestMatch] as $segNum => $seg) {
                $key = $segmentKeys[$keyIndex] ?? '?';
                $eventTypeId = $seg['event_type_id'] ?? 'NULL';

                printf("       '%s' => %s,  // (%d von %d) %s\n",
                    $key,
                    $eventTypeId,
                    $segNum,
                    $seg['total_segments'],
                    $seg['segment_desc'] ?? 'Segment ' . $segNum
                );

                $keyIndex++;
            }
            echo "   ];\n";
        }
    } else {
        echo "   ⚠️  KEIN MATCH gefunden\n";
        echo "      Bester Score: " . round($bestScore, 1) . "% mit \"{$bestMatch}\"\n";
        echo "      → Segment Event Types müssen manuell aus Cal.com abgelesen werden\n";
    }

    echo "\n";
}

echo str_repeat("═", 63) . "\n";
echo "\n📝 ZUSAMMENFASSUNG:\n\n";

if (empty($segmentServices)) {
    echo "❌ Keine Segment-Services in der Datenbank\n";
    echo "   → Event Types existieren nur in Cal.com\n";
    echo "   → Müssen manuell aus Cal.com UI abgelesen werden\n\n";

    echo "📋 Benötigte Event Types aus Cal.com:\n\n";

    foreach ($compositeServices as $composite) {
        $compositeSegments = json_decode($composite->segments, true) ?? [];

        echo "Service {$composite->id}: {$composite->name}\n";

        $segmentKeys = ['A', 'B', 'C', 'D', 'E', 'F'];
        for ($i = 0; $i < count($compositeSegments); $i++) {
            $key = $segmentKeys[$i];
            $seg = $compositeSegments[$i];
            $segNum = $i + 1;

            echo sprintf("  %s (%d von %d): \"%s: %s\"\n",
                $key,
                $segNum,
                count($compositeSegments),
                $composite->name,
                $seg['name'] ?? 'Unnamed'
            );
        }
        echo "\n";
    }
} else {
    $matchCount = 0;
    foreach ($compositeServices as $composite) {
        foreach ($segmentServices as $baseName => $segments) {
            similar_text(
                strtolower($composite->name),
                strtolower($baseName),
                $score
            );

            if ($score > 50) {
                $matchCount++;
                break;
            }
        }
    }

    echo "✅ Gefundene Matches: {$matchCount} von " . count($compositeServices) . "\n";

    if ($matchCount > 0) {
        echo "   → Event Type IDs können automatisch gemappt werden!\n";
    } else {
        echo "   → Event Type IDs müssen manuell zugeordnet werden\n";
    }
}

echo "\n" . str_repeat("═", 63) . "\n";
