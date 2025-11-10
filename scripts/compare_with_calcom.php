<?php

/**
 * Compare Database Services with Cal.com Event Types
 * Verify all services, composite segments, and event type mappings
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

$calcomApiKey = config('services.calcom.api_key');
$calcomBaseUrl = config('services.calcom.base_url');
$calcomApiVersion = config('services.calcom.api_version');

echo "═══════════════════════════════════════════════════════════════\n";
echo "Cal.com ↔ Database Service Vergleich\n";
echo "Friseur 1 Zentrale\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Get all services from database
$services = DB::table('services')
    ->where('company_id', 1)
    ->whereNotNull('calcom_event_type_id')
    ->where('is_active', true)
    ->orderBy('priority')
    ->get(['id', 'name', 'calcom_event_type_id', 'duration_minutes', 'price', 'composite', 'segments', 'is_active']);

echo "📊 DATENBANK SERVICES (Friseur 1):\n";
echo str_repeat("─", 79) . "\n";
printf("%-4s | %-40s | %-10s | %-6s | %-8s | %s\n",
    "ID", "Service Name", "Event Type", "Dauer", "Preis", "Typ");
echo str_repeat("─", 79) . "\n";

$compositeServices = [];
$standardServices = [];

foreach ($services as $svc) {
    $type = $svc->composite ? "COMPOSITE" : "Standard";
    printf("%-4s | %-40s | %-10s | %-6s | %-8s | %s\n",
        $svc->id,
        substr($svc->name, 0, 40),
        $svc->calcom_event_type_id ?? 'NULL',
        $svc->duration_minutes . 'min',
        number_format($svc->price, 2) . '€',
        $type
    );

    if ($svc->composite) {
        $compositeServices[$svc->id] = $svc;
    } else {
        $standardServices[$svc->id] = $svc;
    }
}

echo str_repeat("─", 79) . "\n";
echo "Total: " . count($services) . " Services (" . count($standardServices) . " Standard + " . count($compositeServices) . " Composite)\n\n";

// Composite Services Detail
if (!empty($compositeServices)) {
    echo str_repeat("═", 79) . "\n\n";
    echo "🎨 COMPOSITE SERVICES DETAIL:\n\n";

    foreach ($compositeServices as $svc) {
        echo "Service {$svc->id}: {$svc->name}\n";
        echo "  Haupt Event Type: {$svc->calcom_event_type_id}\n";
        echo "  Gesamtdauer: {$svc->duration_minutes} min\n";
        echo "  Preis: " . number_format($svc->price, 2) . " €\n";

        $segments = json_decode($svc->segments, true) ?? [];
        echo "  Segmente (" . count($segments) . "):\n";

        foreach ($segments as $seg) {
            $gap = isset($seg['gapAfterMin']) && $seg['gapAfterMin'] > 0
                ? " + {$seg['gapAfterMin']}min Pause"
                : "";
            echo "    {$seg['key']}. {$seg['name']}: {$seg['durationMin']}min{$gap}\n";
        }

        // Get mappings for this service
        $mappings = DB::table('calcom_event_map')
            ->where('service_id', $svc->id)
            ->orderBy('segment_key')
            ->get(['segment_key', 'event_type_id']);

        if ($mappings->count() > 0) {
            echo "  Event Type Mappings:\n";
            foreach ($mappings as $m) {
                echo "    Segment {$m->segment_key} → Event Type {$m->event_type_id}\n";
            }
        } else {
            echo "  ⚠️  Keine Event Type Mappings gefunden!\n";
        }

        echo "\n";
    }
}

// Check Cal.com availability for all event types
echo str_repeat("═", 79) . "\n\n";
echo "🔍 CAL.COM VERFÜGBARKEITS-CHECK:\n";
echo str_repeat("─", 79) . "\n\n";

$startTime = Carbon::now('Europe/Berlin')->addDays(1)->setTime(14, 0);
$endTime = Carbon::now('Europe/Berlin')->addDays(1)->setTime(15, 0);

$activeInCalcom = 0;
$inactiveInCalcom = 0;

// Check main event types
foreach ($services as $svc) {
    if (!$svc->calcom_event_type_id) {
        continue;
    }

    echo "Event Type {$svc->calcom_event_type_id} ({$svc->name})... ";

    try {
        $response = Http::withHeaders([
            'cal-api-version' => $calcomApiVersion,
            'Authorization' => "Bearer {$calcomApiKey}",
        ])->timeout(10)->get("{$calcomBaseUrl}/slots/available", [
            'eventTypeId' => $svc->calcom_event_type_id,
            'startTime' => $startTime->toIso8601String(),
            'endTime' => $endTime->toIso8601String(),
        ]);

        if ($response->successful()) {
            echo "✅ AKTIV\n";
            $activeInCalcom++;
        } else {
            echo "❌ NICHT ERREICHBAR (Status: {$response->status()})\n";
            $inactiveInCalcom++;
        }

    } catch (Exception $e) {
        echo "❌ FEHLER: {$e->getMessage()}\n";
        $inactiveInCalcom++;
    }

    usleep(300000); // Rate limiting
}

// Check segment event types
$allSegmentEventTypes = DB::table('calcom_event_map')
    ->whereIn('service_id', array_keys($compositeServices))
    ->get(['service_id', 'segment_key', 'event_type_id']);

if ($allSegmentEventTypes->count() > 0) {
    echo "\n";
    echo "🔍 SEGMENT EVENT TYPES CHECK:\n";
    echo str_repeat("─", 79) . "\n";

    foreach ($allSegmentEventTypes as $seg) {
        echo "Service {$seg->service_id} Segment {$seg->segment_key} (Event Type {$seg->event_type_id})... ";

        try {
            $response = Http::withHeaders([
                'cal-api-version' => $calcomApiVersion,
                'Authorization' => "Bearer {$calcomApiKey}",
            ])->timeout(10)->get("{$calcomBaseUrl}/slots/available", [
                'eventTypeId' => $seg->event_type_id,
                'startTime' => $startTime->toIso8601String(),
                'endTime' => $endTime->toIso8601String(),
            ]);

            if ($response->successful()) {
                echo "✅ AKTIV\n";
                $activeInCalcom++;
            } else {
                echo "❌ NICHT ERREICHBAR (Status: {$response->status()})\n";
                $inactiveInCalcom++;
            }

        } catch (Exception $e) {
            echo "❌ FEHLER: {$e->getMessage()}\n";
            $inactiveInCalcom++;
        }

        usleep(300000); // Rate limiting
    }
}

echo "\n" . str_repeat("─", 79) . "\n";
echo "📊 ERGEBNIS:\n";
echo "  ✅ Aktiv in Cal.com: {$activeInCalcom}\n";
echo "  ❌ Nicht erreichbar: {$inactiveInCalcom}\n";

if ($inactiveInCalcom > 0) {
    echo "\n⚠️  WARNUNG: Einige Event Types sind in Cal.com nicht erreichbar!\n";
    echo "   → Prüfe Cal.com UI ob diese Event Types aktiv sind\n";
}

echo "\n" . str_repeat("═", 79) . "\n\n";

// Summary
echo "📋 ZUSAMMENFASSUNG:\n\n";

echo "DATENBANK:\n";
echo "  • Total Services: " . count($services) . "\n";
echo "  • Standard Services: " . count($standardServices) . "\n";
echo "  • Composite Services: " . count($compositeServices) . "\n";
echo "  • Event Type Mappings: " . $allSegmentEventTypes->count() . "\n\n";

echo "CAL.COM VERFÜGBARKEIT:\n";
echo "  • Aktive Event Types: {$activeInCalcom}\n";
echo "  • Inaktive Event Types: {$inactiveInCalcom}\n\n";

if ($activeInCalcom === (count($services) + $allSegmentEventTypes->count())) {
    echo "✅ ALLE EVENT TYPES SIND IN CAL.COM AKTIV!\n";
} else {
    echo "⚠️  Einige Event Types sind nicht erreichbar\n";
}

echo "\n" . str_repeat("═", 79) . "\n";
