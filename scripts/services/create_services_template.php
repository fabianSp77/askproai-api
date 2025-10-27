<?php

/**
 * Service Creation Template
 *
 * Wiederverwendbares Script zum Anlegen neuer Services mit Cal.com Integration
 *
 * USAGE:
 * 1. Kopiere dieses Template: cp create_services_template.php create_services_YOUR_COMPANY.php
 * 2. Passe Configuration und Services an (siehe unten)
 * 3. Führe aus: php create_services_YOUR_COMPANY.php
 *
 * Version: 1.0
 * Last Updated: 2025-10-23
 * Tested: ✅ Friseur 1 (16 Services erfolgreich)
 */

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

// ============================================================================
// CONFIGURATION - HIER ANPASSEN!
// ============================================================================

$companyId = 1;                                          // ← Company ID (siehe Runbook)
$branchId = '34c4d48e-4753-4715-9c30-c55843a943e8';    // ← Branch ID (siehe Runbook)
$calcomTeamId = 34209;                                   // ← Cal.com Team ID (siehe Runbook)

// Cal.com API Configuration
$baseUrl = rtrim(config('services.calcom.base_url'), '/');
$apiKey = config('services.calcom.api_key');

// ============================================================================
// SERVICES DEFINITION - HIER SERVICES DEFINIEREN!
// ============================================================================

$services = [
    // BEISPIEL 1: Basis-Service
    [
        'name' => 'Beispiel Service',
        'duration' => 30,                    // Dauer in Minuten
        'price' => 25.00,                    // Preis in EUR
        'category' => 'Schnitt',             // Kategorie (siehe unten)
        'description' => 'Service Beschreibung',
        'notes' => null,                     // Optional: Notizen für AI
    ],

    // BEISPIEL 2: Service mit Notizen
    [
        'name' => 'Premium Service',
        'duration' => 60,
        'price' => 50.00,
        'category' => 'Pflege',
        'description' => 'Premium Service mit Beratung',
        'notes' => 'Immer im Paket mit Basis-Service',  // Wird in assignment_notes gespeichert
    ],

    // TODO: Weitere Services hier hinzufügen...
];

/*
 * KATEGORIEN (standardisiert):
 * - Schnitt      → Haarschnitte
 * - Färben       → Färbungen, Strähnchen
 * - Pflege       → Treatments, Pflege
 * - Styling      → Waschen, Föhnen, Styling
 * - Beratung     → Beratungsgespräche
 * - Sonstiges    → Alles andere
 */

// ============================================================================
// SCRIPT LOGIC - NICHT ÄNDERN!
// ============================================================================

echo "=== SERVICES & EVENT TYPES ERSTELLEN ===" . PHP_EOL;
echo "Company ID: {$companyId}" . PHP_EOL;
echo "Branch ID: {$branchId}" . PHP_EOL;
echo "Team ID: {$calcomTeamId}" . PHP_EOL;
echo "Anzahl: " . count($services) . PHP_EOL;
echo PHP_EOL;

$created = 0;
$failed = 0;
$errors = [];

foreach ($services as $idx => $svc) {
    $num = $idx + 1;
    echo "[$num/" . count($services) . "] {$svc['name']}..." . PHP_EOL;

    try {
        // Step 1: Create Event Type in Cal.com
        echo "  📞 Cal.com Event Type erstellen..." . PHP_EOL;

        $payload = [
            'lengthInMinutes' => $svc['duration'],
            'title' => $svc['name'],
            'slug' => \Illuminate\Support\Str::slug($svc['name']),
            'description' => $svc['description'],
            'schedulingType' => 'COLLECTIVE',  // Required for team events
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'cal-api-version' => '2024-08-13',
            'Content-Type' => 'application/json'
        ])->post($baseUrl . '/teams/' . $calcomTeamId . '/event-types', $payload);

        if (!$response->successful()) {
            echo "  ❌ Cal.com Fehler: " . $response->status() . PHP_EOL;
            echo "     " . $response->body() . PHP_EOL;
            $errors[] = [
                'service' => $svc['name'],
                'step' => 'Cal.com Event Type',
                'error' => $response->body()
            ];
            $failed++;
            continue;
        }

        $data = $response->json();
        $eventType = $data['data'] ?? $data;
        $eventTypeId = $eventType['id'] ?? null;

        if (!$eventTypeId) {
            echo "  ❌ Keine Event Type ID erhalten" . PHP_EOL;
            $errors[] = [
                'service' => $svc['name'],
                'step' => 'Cal.com Event Type ID',
                'error' => 'No ID in response'
            ];
            $failed++;
            continue;
        }

        echo "  ✅ Event Type: {$eventTypeId}" . PHP_EOL;

        // Step 2: Insert Service into Database
        echo "  💾 Service in Datenbank anlegen..." . PHP_EOL;

        $serviceId = DB::table('services')->insertGetId([
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
            'assignment_notes' => $svc['notes'], // Store notes here
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        echo "  ✅ Service DB: {$serviceId}" . PHP_EOL;

        // Step 3: Create Event Mapping
        echo "  🔗 Event Mapping erstellen..." . PHP_EOL;

        DB::table('calcom_event_mappings')->insert([
            'calcom_event_type_id' => (string)$eventTypeId,
            'company_id' => $companyId,
            'calcom_team_id' => (string)$calcomTeamId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        echo "  ✅ Mapping erstellt" . PHP_EOL;
        $created++;

    } catch (Exception $e) {
        echo "  ❌ Exception: " . $e->getMessage() . PHP_EOL;
        $errors[] = [
            'service' => $svc['name'],
            'step' => 'Exception',
            'error' => $e->getMessage()
        ];
        $failed++;
    }

    echo PHP_EOL;
}

// Summary
echo "=== ZUSAMMENFASSUNG ===" . PHP_EOL;
echo "✅ Erfolgreich: {$created}" . PHP_EOL;
echo "❌ Fehler: {$failed}" . PHP_EOL;
echo PHP_EOL;

// Error Details
if (count($errors) > 0) {
    echo "=== FEHLER-DETAILS ===" . PHP_EOL;
    foreach ($errors as $error) {
        echo "Service: {$error['service']}" . PHP_EOL;
        echo "  Step: {$error['step']}" . PHP_EOL;
        echo "  Error: {$error['error']}" . PHP_EOL;
        echo PHP_EOL;
    }
}

// Verification
if ($created > 0) {
    echo "=== VERIFICATION ===" . PHP_EOL;

    $allServices = DB::table('services')
        ->where('company_id', $companyId)
        ->orderBy('id', 'desc')
        ->limit($created)
        ->get();

    echo "Neu erstellte Services:" . PHP_EOL;
    foreach ($allServices as $s) {
        echo "  ✓ {$s->name} | ID: {$s->id} | Event Type: {$s->calcom_event_type_id} | €{$s->price}" . PHP_EOL;
    }
    echo PHP_EOL;

    echo "Next Steps:" . PHP_EOL;
    echo "1. Admin Portal prüfen: https://api.askproai.de/admin/services" . PHP_EOL;
    echo "2. Cal.com prüfen: https://app.cal.com/event-types?teamId={$calcomTeamId}" . PHP_EOL;
    echo "3. ServiceNameExtractor testen (siehe Runbook)" . PHP_EOL;
    echo "4. Voice AI End-to-End Test durchführen" . PHP_EOL;
}
