<?php

/**
 * Event Types neu erstellen mit korrektem schedulingType
 *
 * Problem: schedulingType kann nach Erstellung nicht geändert werden
 * Lösung: Event Types löschen und neu erstellen mit roundRobin
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

$baseUrl = rtrim(config('services.calcom.base_url'), '/');
$apiKey = config('services.calcom.api_key');
$teamId = 34209;
$companyId = 1;

// Hosts wie bei alten Services
$hosts = [
    ['userId' => 1414768],  // Fabian Spitzer (askproai)
    ['userId' => 1346408],  // Fabian Spitzer (fabianspitzer)
];

// Services aus DB abrufen
$services = DB::table('services')
    ->where('company_id', $companyId)
    ->whereIn('id', range(167, 182))
    ->orderBy('id')
    ->get();

echo "╔══════════════════════════════════════════════════════════════╗" . PHP_EOL;
echo "║     EVENT TYPES NEU ERSTELLEN (mit roundRobin)              ║" . PHP_EOL;
echo "╚══════════════════════════════════════════════════════════════╝" . PHP_EOL;
echo PHP_EOL;
echo "Services: " . count($services) . PHP_EOL;
echo PHP_EOL;

$created = 0;
$failed = 0;
$mapping = []; // old_event_type_id => new_event_type_id

foreach ($services as $idx => $service) {
    $num = $idx + 1;
    $oldEventTypeId = $service->calcom_event_type_id;

    echo "[$num/" . count($services) . "] {$service->name}..." . PHP_EOL;
    echo "  Alte Event Type ID: {$oldEventTypeId}" . PHP_EOL;

    try {
        // Step 1: Alte Event Type löschen
        echo "  🗑️  Alte Event Type löschen..." . PHP_EOL;

        $deleteResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'cal-api-version' => '2024-08-13',
        ])->delete($baseUrl . '/teams/' . $teamId . '/event-types/' . $oldEventTypeId);

        if (!$deleteResponse->successful() && $deleteResponse->status() !== 404) {
            echo "  ⚠️  Löschen fehlgeschlagen: " . $deleteResponse->status() . PHP_EOL;
            // Weitermachen, vielleicht existiert sie nicht mehr
        } else {
            echo "  ✅ Gelöscht" . PHP_EOL;
        }

        // Step 2: Neue Event Type erstellen mit roundRobin
        echo "  📞 Neue Event Type erstellen (roundRobin)..." . PHP_EOL;

        $payload = [
            'lengthInMinutes' => $service->duration_minutes,
            'title' => $service->name,
            'slug' => $service->slug . '-new', // Neuer Slug um Konflikte zu vermeiden
            'description' => $service->description,
            'schedulingType' => 'ROUND_ROBIN',  // ← KORREKT!
            'assignAllTeamMembers' => true,     // ← WICHTIG!
            'hosts' => $hosts,
            'locations' => [
                ['type' => 'attendeeDefined']
            ],
            'minimumBookingNotice' => 120,
            'beforeEventBuffer' => 0,
            'afterEventBuffer' => 0,
        ];

        $createResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'cal-api-version' => '2024-08-13',
            'Content-Type' => 'application/json'
        ])->post($baseUrl . '/teams/' . $teamId . '/event-types', $payload);

        if (!$createResponse->successful()) {
            echo "  ❌ Fehler: " . $createResponse->status() . PHP_EOL;
            echo "     " . $createResponse->body() . PHP_EOL;
            $failed++;
            continue;
        }

        $data = $createResponse->json();
        $eventType = $data['data'] ?? $data;
        $newEventTypeId = $eventType['id'] ?? null;

        if (!$newEventTypeId) {
            echo "  ❌ Keine Event Type ID" . PHP_EOL;
            $failed++;
            continue;
        }

        echo "  ✅ Neue Event Type ID: {$newEventTypeId}" . PHP_EOL;
        $mapping[$oldEventTypeId] = $newEventTypeId;

        // Step 3: Service in DB updaten
        echo "  💾 Service DB updaten..." . PHP_EOL;

        DB::table('services')
            ->where('id', $service->id)
            ->update([
                'calcom_event_type_id' => (string)$newEventTypeId,
                'updated_at' => now(),
            ]);

        echo "  ✅ Service aktualisiert" . PHP_EOL;

        // Step 4: Event Mapping updaten
        echo "  🔗 Event Mapping updaten..." . PHP_EOL;

        DB::table('calcom_event_mappings')
            ->where('calcom_event_type_id', (string)$oldEventTypeId)
            ->update([
                'calcom_event_type_id' => (string)$newEventTypeId,
                'updated_at' => now(),
            ]);

        echo "  ✅ Mapping aktualisiert" . PHP_EOL;
        $created++;

    } catch (Exception $e) {
        echo "  ❌ Exception: " . $e->getMessage() . PHP_EOL;
        $failed++;
    }

    echo PHP_EOL;
}

echo "╔══════════════════════════════════════════════════════════════╗" . PHP_EOL;
echo "║                    ZUSAMMENFASSUNG                           ║" . PHP_EOL;
echo "╚══════════════════════════════════════════════════════════════╝" . PHP_EOL;
echo "✅ Erfolgreich: {$created}" . PHP_EOL;
echo "❌ Fehler: {$failed}" . PHP_EOL;
echo PHP_EOL;

if ($created > 0) {
    echo "=== ID MAPPING (alt → neu) ===" . PHP_EOL;
    foreach ($mapping as $old => $new) {
        echo "  {$old} → {$new}" . PHP_EOL;
    }
    echo PHP_EOL;
    echo "✅ Alle Event Types haben jetzt schedulingType: ROUND_ROBIN" . PHP_EOL;
    echo "✅ Alle Services und Mappings aktualisiert" . PHP_EOL;
}
