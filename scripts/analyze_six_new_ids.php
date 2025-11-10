<?php

/**
 * Analyze 6 new Event Type IDs to match them to Services 440 and 442
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
echo "Analyse von 6 neuen Event Type IDs\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$newIds = [3757801, 3757787, 3757786, 3757785, 3757775, 3757774];

echo "📋 Gegebene Event Type IDs (unsortiert):\n";
foreach ($newIds as $id) {
    echo "  • {$id}\n";
}
echo "\n";

// Sortiere IDs
sort($newIds);

echo "📊 Sortierte IDs:\n";
foreach ($newIds as $id) {
    echo "  • {$id}\n";
}
echo "\n";

echo str_repeat("─", 63) . "\n\n";

$compositeServices = [
    440 => [
        'name' => 'Ansatzfärbung',
        'hauptEventType' => 3757707,
        'segments' => [
            'A' => ['name' => 'Ansatzfärbung auftragen', 'duration' => 30],
            'B' => ['name' => 'Auswaschen', 'duration' => 15],
            'C' => ['name' => 'Formschnitt', 'duration' => 30],
            'D' => ['name' => 'Föhnen & Styling', 'duration' => 30],
        ]
    ],
    442 => [
        'name' => 'Ansatz + Längenausgleich',
        'hauptEventType' => 3757697,
        'segments' => [
            'A' => ['name' => 'Ansatzfärbung & Längenausgleich auftragen', 'duration' => 40],
            'B' => ['name' => 'Auswaschen', 'duration' => 15],
            'C' => ['name' => 'Formschnitt', 'duration' => 40],
            'D' => ['name' => 'Föhnen & Styling', 'duration' => 30],
        ]
    ],
    444 => [
        'name' => 'Komplette Umfärbung (Blondierung)',
        'hauptEventType' => 3757773,
        'status' => '✅ BEREITS KOMPLETT (3757803-3757806)'
    ]
];

echo "🎯 DISTANZ-ANALYSE zu bekannten Haupt-Event-Types:\n";
echo str_repeat("─", 63) . "\n\n";

foreach ($newIds as $id) {
    echo "Event Type {$id}:\n";

    foreach ([440, 442, 444] as $serviceId) {
        $service = $compositeServices[$serviceId];
        $hauptId = $service['hauptEventType'];
        $distance = $id - $hauptId;
        $absDistance = abs($distance);

        $direction = $distance > 0 ? "NACH" : "VOR";
        echo "  • Service {$serviceId} ({$service['name']}): ";
        echo sprintf("%+d (%d IDs %s Haupt-ID %d)\n", $distance, $absDistance, $direction, $hauptId);
    }
    echo "\n";
}

echo str_repeat("─", 63) . "\n\n";

// Gruppiere IDs nach Nähe
echo "📦 GRUPPIERUNG nach ID-Nähe:\n";
echo str_repeat("─", 63) . "\n\n";

// Finde consecutive Gruppen
$groups = [];
$currentGroup = [];

foreach ($newIds as $index => $id) {
    if (empty($currentGroup)) {
        $currentGroup[] = $id;
    } else {
        $lastId = end($currentGroup);
        if ($id - $lastId <= 2) {
            // Consecutive oder sehr nah
            $currentGroup[] = $id;
        } else {
            // Neue Gruppe
            if (count($currentGroup) > 0) {
                $groups[] = $currentGroup;
            }
            $currentGroup = [$id];
        }
    }
}
if (count($currentGroup) > 0) {
    $groups[] = $currentGroup;
}

foreach ($groups as $gIndex => $group) {
    $gNum = $gIndex + 1;
    echo "Gruppe {$gNum}: " . count($group) . " IDs\n";
    echo "  IDs: " . implode(", ", $group) . "\n";
    echo "  Range: " . min($group) . " - " . max($group) . "\n";

    // Berechne durchschnittliche Distanz zu jedem Service
    $avgDistances = [];
    foreach ([440, 442, 444] as $serviceId) {
        $service = $compositeServices[$serviceId];
        $hauptId = $service['hauptEventType'];

        $totalDist = 0;
        foreach ($group as $id) {
            $totalDist += abs($id - $hauptId);
        }
        $avgDist = $totalDist / count($group);
        $avgDistances[$serviceId] = $avgDist;
    }

    asort($avgDistances);
    $closestService = array_key_first($avgDistances);

    echo "  Nächster Service: {$closestService} ({$compositeServices[$closestService]['name']}) ";
    echo "- Ø Distanz: " . round($avgDistances[$closestService], 1) . "\n";
    echo "\n";
}

echo str_repeat("─", 63) . "\n\n";

echo "💡 ANALYSE & EMPFEHLUNG:\n";
echo str_repeat("─", 63) . "\n\n";

// Wir brauchen 4 IDs für Service 440 und 4 IDs für Service 442
echo "BENÖTIGT:\n";
echo "  • Service 440 (Ansatzfärbung): 4 Segment-IDs\n";
echo "  • Service 442 (Ansatz + Längenausgleich): 4 Segment-IDs\n";
echo "  Total: 8 IDs\n\n";

echo "GEGEBEN: 6 IDs\n\n";

echo "⚠️  PROBLEM: Wir haben 6 IDs, benötigen aber 8.\n\n";

echo "MÖGLICHE SZENARIEN:\n";
echo "1. Einige IDs gehören nicht zu Composite Services\n";
echo "2. Es fehlen noch 2 IDs\n";
echo "3. Einige IDs gehören zu einem anderen Service\n\n";

// Schaue nach der besten Gruppierung
echo "BESTE ZUORDNUNG basierend auf ID-Distanzen:\n\n";

// Option 1: Gruppe mit 4 consecutive IDs finden
$fourGroups = [];
foreach ($groups as $group) {
    if (count($group) >= 4) {
        $fourGroups[] = array_slice($group, 0, 4);
    }
}

if (!empty($fourGroups)) {
    echo "✅ Gefunden: Gruppe(n) mit 4 consecutive IDs\n\n";

    foreach ($fourGroups as $gIndex => $group) {
        sort($group);

        echo "Gruppe " . ($gIndex + 1) . ": " . implode(", ", $group) . "\n";

        // Berechne Distanzen
        $distances = [];
        foreach ([440, 442] as $serviceId) {
            $service = $compositeServices[$serviceId];
            $hauptId = $service['hauptEventType'];

            $totalDist = 0;
            foreach ($group as $id) {
                $totalDist += abs($id - $hauptId);
            }
            $avgDist = $totalDist / count($group);
            $distances[$serviceId] = $avgDist;
        }

        asort($distances);
        $closestService = array_key_first($distances);

        echo "  → WAHRSCHEINLICH Service {$closestService}: {$compositeServices[$closestService]['name']}\n";
        echo "     Durchschnittliche Distanz: " . round($distances[$closestService], 1) . "\n\n";

        // Zeige Mapping
        echo "  MAPPING:\n";
        $keys = ['A', 'B', 'C', 'D'];
        foreach ($group as $index => $id) {
            $key = $keys[$index];
            $segNum = $index + 1;
            $segName = $compositeServices[$closestService]['segments'][$key]['name'];
            echo "    {$key} ({$segNum} von 4): Event Type {$id} → {$segName}\n";
        }
        echo "\n";
    }
}

echo str_repeat("─", 63) . "\n\n";

// Versuche alle 6 IDs zuzuordnen
echo "🔍 SYSTEMATISCHE ZUORDNUNG (alle 6 IDs):\n";
echo str_repeat("─", 63) . "\n\n";

// Teste alle möglichen 4er-Kombinationen
$allCombinations = [];

// Erzeuge alle Kombinationen von 4 IDs aus 6
$n = count($newIds);
for ($i = 0; $i < $n - 3; $i++) {
    for ($j = $i + 1; $j < $n - 2; $j++) {
        for ($k = $j + 1; $k < $n - 1; $k++) {
            for ($l = $k + 1; $l < $n; $l++) {
                $combo = [$newIds[$i], $newIds[$j], $newIds[$k], $newIds[$l]];
                sort($combo);

                // Prüfe ob consecutive
                $isConsecutive = true;
                for ($m = 0; $m < 3; $m++) {
                    if ($combo[$m + 1] - $combo[$m] > 2) {
                        $isConsecutive = false;
                        break;
                    }
                }

                if ($isConsecutive) {
                    $allCombinations[] = $combo;
                }
            }
        }
    }
}

echo "Gefundene consecutive 4er-Gruppen: " . count($allCombinations) . "\n\n";

if (count($allCombinations) > 0) {
    foreach ($allCombinations as $cIndex => $combo) {
        echo "Kombination " . ($cIndex + 1) . ": " . implode(", ", $combo) . "\n";

        // Berechne Distanzen zu Services 440 und 442
        $distances = [];
        foreach ([440, 442] as $serviceId) {
            $service = $compositeServices[$serviceId];
            $hauptId = $service['hauptEventType'];

            $totalDist = 0;
            foreach ($combo as $id) {
                $totalDist += abs($id - $hauptId);
            }
            $avgDist = $totalDist / 4;
            $distances[$serviceId] = $avgDist;
        }

        asort($distances);
        $closestService = array_key_first($distances);

        echo "  → Service {$closestService}: {$compositeServices[$closestService]['name']}\n";
        echo "     Ø Distanz: " . round($distances[$closestService], 1) . "\n\n";
    }
}

echo str_repeat("═", 63) . "\n\n";

echo "📝 NÄCHSTER SCHRITT:\n\n";
echo "Script wird jetzt alle 6 IDs via Cal.com API testen...\n\n";

// Teste alle IDs via API
$startTime = Carbon::now('Europe/Berlin')->addDays(1)->setTime(14, 0);
$endTime = Carbon::now('Europe/Berlin')->addDays(1)->setTime(15, 0);

$activeIds = [];
$inactiveIds = [];

foreach ($newIds as $id) {
    echo "Testing Event Type {$id}... ";

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
            echo "✅ AKTIV\n";
            $activeIds[] = $id;
        } else {
            echo "❌ NICHT ERREICHBAR (Status: {$response->status()})\n";
            $inactiveIds[] = $id;
        }
    } catch (Exception $e) {
        echo "❌ FEHLER: {$e->getMessage()}\n";
        $inactiveIds[] = $id;
    }

    usleep(300000); // Rate limiting
}

echo "\n" . str_repeat("═", 63) . "\n\n";

echo "📊 ERGEBNIS:\n";
echo "  ✅ Aktive Event Types: " . count($activeIds) . "\n";
echo "  ❌ Inaktive/Nicht erreichbar: " . count($inactiveIds) . "\n\n";

if (count($activeIds) === 6) {
    echo "✅ ALLE 6 EVENT TYPES SIND AKTIV!\n\n";
    echo "→ Bereit für Mapping-Erstellung\n";
} elseif (count($activeIds) >= 4) {
    echo "✅ Mindestens 4 Event Types aktiv\n";
    echo "→ Ein Service kann vollständig gemappt werden\n";
}

echo "\n" . str_repeat("═", 63) . "\n";
