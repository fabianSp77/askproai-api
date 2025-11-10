<?php

/**
 * Check all services for prices and durations
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "═══════════════════════════════════════════════════════════════\n";
echo "VOLLSTÄNDIGE PRÜFUNG: Preise & Dauern\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "📋 ALLE SERVICES - Preise & Dauern:\n";
echo str_repeat("─", 95) . "\n";
printf("%-3s %-2s | %-40s | %-12s | %-15s | %s\n", "ID", "", "Service Name", "Dauer", "Preis", "Probleme");
echo str_repeat("─", 95) . "\n";

$services = DB::table('services')
    ->where('company_id', 1)
    ->whereNotNull('calcom_event_type_id')
    ->orderBy('priority')
    ->get(['id', 'name', 'duration_minutes', 'price', 'composite', 'segments']);

$missingPrice = 0;
$missingDuration = 0;
$lowPrice = 0;
$compositeMissingSegmentDuration = 0;

foreach ($services as $svc) {
    $problems = [];

    // Check price
    $priceDisplay = $svc->price ? number_format($svc->price, 2, ',', '.') . ' €' : '❌ FEHLT';
    if (!$svc->price || $svc->price <= 0) {
        $problems[] = 'PREIS FEHLT';
        $missingPrice++;
    } elseif ($svc->price < 5) {
        $problems[] = 'PREIS ZU NIEDRIG';
        $lowPrice++;
    }

    // Check duration
    $durationDisplay = $svc->duration_minutes ? $svc->duration_minutes . ' min' : '❌ FEHLT';
    if (!$svc->duration_minutes || $svc->duration_minutes <= 0) {
        $problems[] = 'DAUER FEHLT';
        $missingDuration++;
    }

    $compIcon = $svc->composite ? '🎨' : '  ';
    $problemText = empty($problems) ? '✅' : '⚠️  ' . implode(', ', $problems);

    printf("%-3s %s | %-40s | %-12s | %-15s | %s\n",
        $svc->id,
        $compIcon,
        substr($svc->name, 0, 40),
        $durationDisplay,
        $priceDisplay,
        $problemText
    );

    // Für Composite Services: Segmente im Detail prüfen
    if ($svc->composite) {
        $segments = json_decode($svc->segments, true);
        if (empty($segments)) {
            echo "      ❌ KEINE SEGMENTE DEFINIERT\n";
            $compositeMissingSegmentDuration++;
        } else {
            echo "      Segmente:\n";
            foreach ($segments as $seg) {
                $segDur = isset($seg['durationMin']) ? $seg['durationMin'] . ' min' : '❌ FEHLT';
                $segGap = isset($seg['gapAfterMin']) && $seg['gapAfterMin'] > 0
                    ? ' + Pause ' . $seg['gapAfterMin'] . '-' . $seg['gapAfterMax'] . ' min'
                    : '';

                $segProblems = [];
                if (!isset($seg['durationMin']) || $seg['durationMin'] <= 0) {
                    $segProblems[] = 'DAUER FEHLT';
                    $compositeMissingSegmentDuration++;
                }

                $segStatus = empty($segProblems) ? '✅' : '⚠️  ' . implode(', ', $segProblems);

                printf("        %s. %-35s %s%s %s\n",
                    $seg['key'] ?? '?',
                    substr($seg['name'] ?? 'Unnamed', 0, 35),
                    $segDur,
                    $segGap,
                    $segStatus
                );
            }
        }
        echo "\n";
    }
}

echo "\n" . str_repeat("─", 95) . "\n";
echo "📊 ZUSAMMENFASSUNG:\n";
echo str_repeat("─", 95) . "\n";
echo "Gesamt Services: " . count($services) . "\n";
echo "  ❌ Fehlende Preise: " . $missingPrice . "\n";
echo "  ⚠️  Zu niedrige Preise (<5€): " . $lowPrice . "\n";
echo "  ❌ Fehlende Dauern (Haupt): " . $missingDuration . "\n";
echo "  ❌ Fehlende Segment-Dauern: " . $compositeMissingSegmentDuration . "\n";

$totalProblems = $missingPrice + $lowPrice + $missingDuration + $compositeMissingSegmentDuration;

echo "\n";
if ($totalProblems === 0) {
    echo "✅ ALLE CHECKS BESTANDEN! Keine Probleme gefunden.\n";
} else {
    echo "⚠️  PROBLEME GEFUNDEN: " . $totalProblems . " Fehler\n";
    echo "\nEmpfohlene Aktionen:\n";

    if ($missingPrice > 0) {
        echo "  • Preise für " . $missingPrice . " Services setzen\n";
    }
    if ($lowPrice > 0) {
        echo "  • Preise für " . $lowPrice . " Services erhöhen (aktuell <5€)\n";
    }
    if ($missingDuration > 0) {
        echo "  • Dauern für " . $missingDuration . " Services setzen\n";
    }
    if ($compositeMissingSegmentDuration > 0) {
        echo "  • Segment-Dauern für Composite Services korrigieren\n";
    }
}

echo "\n" . str_repeat("═", 95) . "\n";
