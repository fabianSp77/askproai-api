<?php

/**
 * Analyze provided Event Type IDs to match them to services
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
echo "Event Type IDs Analyse - Detail\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$providedIds = [3757803, 3757804, 3757805, 3757806];

echo "📋 Gegebene Event Type IDs:\n";
foreach ($providedIds as $id) {
    echo "  • {$id}\n";
}
echo "\n";

echo "🔍 HYPOTHESE:\n";
echo "Da die IDs consecutive sind (aufeinanderfolgend), gehören sie\n";
echo "wahrscheinlich zu EINEM Service mit 4 Segmenten.\n\n";

echo "📊 BEKANNTE COMPOSITE SERVICES:\n";
echo str_repeat("─", 63) . "\n";

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
        'segments' => [
            'A' => ['name' => 'Blondierung auftragen', 'duration' => 50],
            'B' => ['name' => 'Auswaschen & Pflege', 'duration' => 15],
            'C' => ['name' => 'Formschnitt', 'duration' => 40],
            'D' => ['name' => 'Föhnen & Styling', 'duration' => 30],
        ]
    ],
];

foreach ($compositeServices as $serviceId => $service) {
    echo "Service {$serviceId}: {$service['name']}\n";
    echo "  Haupt Event Type: {$service['hauptEventType']}\n";
    echo "  Segmente:\n";
    foreach ($service['segments'] as $key => $seg) {
        echo "    {$key}. {$seg['name']} ({$seg['duration']} min)\n";
    }
    echo "\n";
}

echo str_repeat("─", 63) . "\n\n";

// Berechne Distanz zu Haupt-Event-Types
echo "🎯 DISTANZ-ANALYSE:\n";
echo "Gegebene IDs liegen im Bereich: 3757803 - 3757806\n\n";

foreach ($compositeServices as $serviceId => $service) {
    $hauptId = $service['hauptEventType'];
    $distance = min($providedIds) - $hauptId;

    echo "Service {$serviceId} ({$service['name']}):\n";
    echo "  Haupt ID: {$hauptId}\n";
    echo "  Distanz zu 3757803: ";

    if ($distance > 0) {
        echo "+{$distance} (Segment IDs liegen NACH Haupt-ID)\n";
    } else {
        echo "{$distance} (Segment IDs liegen VOR Haupt-ID)\n";
    }

    echo "\n";
}

echo str_repeat("─", 63) . "\n\n";

// ID-Bereich Analyse
echo "🔢 ID-BEREICH ANALYSE:\n\n";

$allKnownIds = [
    3757697 => 'Ansatz + Längenausgleich (Haupt)',
    3757707 => 'Ansatzfärbung (Haupt)',
    3757773 => 'Blondierung (Haupt)',
];

$allKnownIds = array_merge($allKnownIds, array_combine(
    $providedIds,
    array_map(fn($i) => "Segment Event Type ?", $providedIds)
));

ksort($allKnownIds);

echo "Sortierte Event Type IDs:\n";
foreach ($allKnownIds as $id => $desc) {
    $marker = in_array($id, $providedIds) ? '←' : '';
    echo "  {$id}: {$desc} {$marker}\n";
}

echo "\n" . str_repeat("─", 63) . "\n\n";

// Matching-Logik
echo "💡 MATCHING-LOGIK:\n\n";

echo "Die IDs 3757803-3757806 liegen zwischen:\n";
echo "  • 3757707 (Ansatzfärbung)\n";
echo "  • 3757773 (Blondierung)\n\n";

echo "Da sie consecutive und in der Mitte liegen, könnten sie zu\n";
echo "EINEM der drei Services gehören.\n\n";

echo "HYPOTHESEN:\n";
echo "1. Service 440 (Ansatzfärbung) - Distanz: +" . (3757803 - 3757707) . "\n";
echo "   → WAHRSCHEINLICH: Segmente wurden nach Haupt-Event erstellt\n\n";

echo "2. Service 442 (Ansatz + Längenausgleich) - Distanz: +" . (3757803 - 3757697) . "\n";
echo "   → MÖGLICH: Aber größere Distanz\n\n";

echo "3. Service 444 (Blondierung) - Distanz: " . (3757803 - 3757773) . "\n";
echo "   → UNWAHRSCHEINLICH: Segmente würden VOR Haupt-Event liegen\n\n";

echo str_repeat("─", 63) . "\n\n";

// Empfehlung
echo "🎯 EMPFEHLUNG:\n\n";

$distances = [
    440 => abs(3757803 - 3757707),
    442 => abs(3757803 - 3757697),
    444 => abs(3757803 - 3757773),
];

asort($distances);
$closestService = array_key_first($distances);
$closestName = $compositeServices[$closestService]['name'];

echo "Basierend auf ID-Nähe:\n";
echo "→ WAHRSCHEINLICH Service {$closestService}: {$closestName}\n\n";

echo "MAPPING:\n";
$keys = ['A', 'B', 'C', 'D'];
foreach ($providedIds as $index => $id) {
    $key = $keys[$index];
    $segNum = $index + 1;
    $segName = $compositeServices[$closestService]['segments'][$key]['name'];
    echo "  {$key} ({$segNum} von 4): Event Type {$id} → {$segName}\n";
}

echo "\n" . str_repeat("─", 63) . "\n\n";

// Generiere Code
echo "💾 GENERIERTER CODE:\n\n";
echo "// Für scripts/create_composite_event_mappings.php\n\n";

echo "// Service {$closestService}: {$closestName}\n";
echo "\$mappings_{$closestService} = [\n";
foreach ($providedIds as $index => $id) {
    $key = $keys[$index];
    $segNum = $index + 1;
    $segName = $compositeServices[$closestService]['segments'][$key]['name'];
    echo "    '{$key}' => {$id},  // ({$segNum} von 4) {$segName}\n";
}
echo "];\n\n";

echo str_repeat("═", 63) . "\n\n";

echo "⚠️  WICHTIG:\n";
echo "Dies ist eine AUTOMATISCHE ZUORDNUNG basierend auf ID-Nähe.\n";
echo "Bitte überprüfe in Cal.com UI ob die Zuordnung korrekt ist!\n\n";

echo "✅ Wenn korrekt: Code in create_composite_event_mappings.php einfügen\n";
echo "❌ Wenn falsch: Manuell die richtigen IDs zuordnen\n\n";
