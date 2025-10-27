<?php

/**
 * Direktes Erstellen von Cal.com Event Types und Services
 * Umgeht die Service-Validierung durch direkte Cal.com API Calls
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

$companyId = 1;
$branchId = '34c4d48e-4753-4715-9c30-c55843a943e8';
$calcomTeamId = 34209;

$baseUrl = rtrim(config('services.calcom.base_url'), '/');
$apiKey = config('services.calcom.api_key');

// Services Definition
$services = [
    ['name' => 'Kinderhaarschnitt', 'duration' => 30, 'price' => 20.50, 'category' => 'Schnitt', 'description' => 'Kindgerechter Haarschnitt'],
    ['name' => 'Trockenschnitt', 'duration' => 30, 'price' => 25.00, 'category' => 'Schnitt', 'description' => 'Haarschnitt ohne Waschen'],
    ['name' => 'Waschen & Styling', 'duration' => 45, 'price' => 40.00, 'category' => 'Styling', 'description' => 'Haarwäsche und Styling'],
    ['name' => 'Waschen, schneiden, föhnen', 'duration' => 60, 'price' => 45.00, 'category' => 'Schnitt', 'description' => 'Komplettservice'],
    ['name' => 'Haarspende', 'duration' => 30, 'price' => 80.00, 'category' => 'Sonstiges', 'description' => 'Haarspende für wohltätige Zwecke'],
    ['name' => 'Beratung', 'duration' => 30, 'price' => 30.00, 'category' => 'Beratung', 'description' => 'Professionelle Haarberatung', 'notes' => 'Bei Tressen, Balayage, Strähnen-Paket, Blondierung und Faceframe verpflichtend'],
    ['name' => 'Hairdetox', 'duration' => 15, 'price' => 12.50, 'category' => 'Pflege', 'description' => 'Intensive Haarentgiftung', 'notes' => 'Immer im Paket inkl. Leistung 1'],
    ['name' => 'Rebuild Treatment Olaplex', 'duration' => 15, 'price' => 15.50, 'category' => 'Pflege', 'description' => 'Wiederaufbau-Behandlung mit Olaplex'],
    ['name' => 'Intensiv Pflege Maria Nila', 'duration' => 15, 'price' => 15.50, 'category' => 'Pflege', 'description' => 'Intensive Haarpflege mit Maria Nila Produkten'],
    ['name' => 'Gloss', 'duration' => 30, 'price' => 45.00, 'category' => 'Färben', 'description' => 'Gloss-Behandlung für mehr Glanz'],
    ['name' => 'Ansatzfärbung, waschen, schneiden, föhnen', 'duration' => 120, 'price' => 85.00, 'category' => 'Färben', 'description' => 'Komplett-Paket: Ansatzfärbung'],
    ['name' => 'Ansatz, Längenausgleich, waschen, schneiden, föhnen', 'duration' => 120, 'price' => 85.00, 'category' => 'Färben', 'description' => 'Komplett-Paket: Ansatz und Längenausgleich'],
    ['name' => 'Klassisches Strähnen-Paket', 'duration' => 120, 'price' => 125.00, 'category' => 'Färben', 'description' => 'Klassische Strähnentechnik', 'notes' => 'Individuelle Absprache erforderlich'],
    ['name' => 'Globale Blondierung', 'duration' => 120, 'price' => 185.00, 'category' => 'Färben', 'description' => 'Komplette Blondierung', 'notes' => 'Individuelle Absprache erforderlich'],
    ['name' => 'Strähnentechnik Balayage', 'duration' => 180, 'price' => 255.00, 'category' => 'Färben', 'description' => 'Moderne Balayage-Strähnentechnik', 'notes' => 'Individuelle Absprache erforderlich'],
    ['name' => 'Faceframe', 'duration' => 180, 'price' => 225.00, 'category' => 'Färben', 'description' => 'Faceframe-Strähnentechnik', 'notes' => 'Individuelle Absprache erforderlich'],
];

echo "=== SERVICES & EVENT TYPES ERSTELLEN ===" . PHP_EOL;
echo "Team ID: {$calcomTeamId}" . PHP_EOL;
echo "Anzahl: " . count($services) . PHP_EOL;
echo PHP_EOL;

$created = 0;
$failed = 0;

foreach ($services as $idx => $svc) {
    $num = $idx + 1;
    echo "[$num/" . count($services) . "] {$svc['name']}..." . PHP_EOL;

    try {
        // Step 1: Create Event Type in Cal.com
        $payload = [
            'lengthInMinutes' => $svc['duration'],
            'title' => $svc['name'],
            'slug' => \Illuminate\Support\Str::slug($svc['name']),
            'description' => $svc['description'],
            'schedulingType' => 'COLLECTIVE', // Required: any team member can handle booking
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'cal-api-version' => '2024-08-13',
            'Content-Type' => 'application/json'
        ])->post($baseUrl . '/teams/' . $calcomTeamId . '/event-types', $payload);

        if (!$response->successful()) {
            echo "  ❌ Cal.com Fehler: " . $response->status() . PHP_EOL;
            echo "     " . $response->body() . PHP_EOL;
            $failed++;
            continue;
        }

        $data = $response->json();
        $eventType = $data['data'] ?? $data;
        $eventTypeId = $eventType['id'] ?? null;

        if (!$eventTypeId) {
            echo "  ❌ Keine Event Type ID" . PHP_EOL;
            $failed++;
            continue;
        }

        echo "  ✅ Event Type: {$eventTypeId}" . PHP_EOL;

        // Step 2: Insert Service directly into DB (bypass validation)
        DB::table('services')->insert([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'name' => $svc['name'],
            'slug' => \Illuminate\Support\Str::slug($svc['name']),
            'description' => $svc['description'],
            'category' => $svc['category'],
            'is_active' => true,
            'is_default' => false,
            'is_online' => true,
            'duration_minutes' => $svc['duration'],
            'buffer_time_minutes' => 0,
            'price' => $svc['price'],
            'calcom_event_type_id' => (string)$eventTypeId,
            'ai_prompt_context' => $svc['notes'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $serviceId = DB::getPdo()->lastInsertId();
        echo "  ✅ Service DB: {$serviceId}" . PHP_EOL;

        // Step 3: Create Event Mapping
        DB::table('calcom_event_mappings')->insert([
            'calcom_event_type_id' => (string)$eventTypeId,
            'calcom_team_id' => (string)$calcomTeamId,
            'company_id' => $companyId,
            'service_id' => $serviceId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        echo "  ✅ Mapping erstellt" . PHP_EOL;
        $created++;

    } catch (Exception $e) {
        echo "  ❌ Exception: " . $e->getMessage() . PHP_EOL;
        $failed++;
    }

    echo PHP_EOL;
}

echo "=== ZUSAMMENFASSUNG ===" . PHP_EOL;
echo "✅ Erfolgreich: {$created}" . PHP_EOL;
echo "❌ Fehler: {$failed}" . PHP_EOL;
