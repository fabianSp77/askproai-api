<?php

/**
 * Script zum Anlegen aller Friseur Services
 * Company: Friseur 1 (ID: 1)
 * Branch: Friseur 1 Zentrale (ID: 34c4d48e-4753-4715-9c30-c55843a943e8)
 * Cal.com Team: Friseur (ID: 34209)
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Service;
use App\Services\CalcomService;
use Illuminate\Support\Facades\DB;

$companyId = 1;
$branchId = '34c4d48e-4753-4715-9c30-c55843a943e8';
$calcomTeamId = 34209;
$calcomService = new CalcomService();

// Services Definition
$services = [
    // Basis-Services
    [
        'name' => 'Herrenhaarschnitt',
        'duration' => 30,
        'price' => 25.00,
        'category' => 'Schnitt',
        'description' => 'Professioneller Herrenhaarschnitt',
        'notes' => null,
    ],
    [
        'name' => 'Kinderhaarschnitt',
        'duration' => 30,
        'price' => 20.50,
        'category' => 'Schnitt',
        'description' => 'Kindgerechter Haarschnitt',
        'notes' => null,
    ],
    [
        'name' => 'Trockenschnitt',
        'duration' => 30,
        'price' => 25.00,
        'category' => 'Schnitt',
        'description' => 'Haarschnitt ohne Waschen',
        'notes' => null,
    ],
    [
        'name' => 'Waschen & Styling',
        'duration' => 45,
        'price' => 40.00,
        'category' => 'Styling',
        'description' => 'Haarwäsche und Styling',
        'notes' => null,
    ],
    [
        'name' => 'Waschen, schneiden, föhnen',
        'duration' => 60,
        'price' => 45.00,
        'category' => 'Schnitt',
        'description' => 'Komplettservice: Waschen, Schneiden und Föhnen',
        'notes' => 'Leistung 1 für Haaraufbau und Gloss',
    ],
    [
        'name' => 'Haarspende',
        'duration' => 30,
        'price' => 80.00,
        'category' => 'Sonstiges',
        'description' => 'Haarspende für wohltätige Zwecke',
        'notes' => null,
    ],
    [
        'name' => 'Beratung',
        'duration' => 30,
        'price' => 30.00,
        'category' => 'Beratung',
        'description' => 'Professionelle Haarb eratung',
        'notes' => 'Bei Tressen, Balayage, Strähnen-Paket, Blondierung und Faceframe verpflichtend',
    ],

    // Zusätzliche Dienstleistungen
    [
        'name' => 'Hairdetox',
        'duration' => 15,
        'price' => 12.50,
        'category' => 'Pflege',
        'description' => 'Intensive Haarentgiftung',
        'notes' => 'Immer im Paket inkl. Leistung 1',
    ],
    [
        'name' => 'Rebuild Treatment Olaplex',
        'duration' => 15,
        'price' => 15.50,
        'category' => 'Pflege',
        'description' => 'Wiederaufbau-Behandlung mit Olaplex',
        'notes' => 'Immer im Paket inkl. Leistung 1',
    ],
    [
        'name' => 'Intensiv Pflege Maria Nila',
        'duration' => 15,
        'price' => 15.50,
        'category' => 'Pflege',
        'description' => 'Intensive Haarpflege mit Maria Nila Produkten',
        'notes' => 'Immer im Paket inkl. Leistung 1',
    ],
    [
        'name' => 'Gloss',
        'duration' => 30,
        'price' => 45.00,
        'category' => 'Färben',
        'description' => 'Gloss-Behandlung für mehr Glanz',
        'notes' => 'Immer im Paket inkl. Leistung 1',
    ],

    // Paketpreise
    [
        'name' => 'Ansatzfärbung, waschen, schneiden, föhnen',
        'duration' => 120,
        'price' => 85.00,
        'category' => 'Färben',
        'description' => 'Komplett-Paket: Ansatzfärbung mit Waschen, Schneiden und Föhnen',
        'notes' => 'Arbeitszeit mit Pausen: 30min + 30min Pause + 60min',
    ],
    [
        'name' => 'Ansatz, Längenausgleich, waschen, schneiden, föhnen',
        'duration' => 120,
        'price' => 85.00,
        'category' => 'Färben',
        'description' => 'Komplett-Paket: Ansatz und Längenausgleich mit Waschen, Schneiden und Föhnen',
        'notes' => 'Arbeitszeit mit Pausen: 30min + 30min Pause + 60min',
    ],
    [
        'name' => 'Klassisches Strähnen-Paket',
        'duration' => 120,
        'price' => 125.00,
        'category' => 'Färben',
        'description' => 'Klassische Strähnentechnik',
        'notes' => 'Individuelle Absprache erforderlich',
    ],
    [
        'name' => 'Globale Blondierung',
        'duration' => 120,
        'price' => 185.00,
        'category' => 'Färben',
        'description' => 'Komplette Blondierung der Haare',
        'notes' => 'Individuelle Absprache erforderlich',
    ],
    [
        'name' => 'Strähnentechnik Balayage',
        'duration' => 180,
        'price' => 255.00,
        'category' => 'Färben',
        'description' => 'Moderne Balayage-Strähnentechnik',
        'notes' => 'Individuelle Absprache erforderlich',
    ],
    [
        'name' => 'Faceframe',
        'duration' => 180,
        'price' => 225.00,
        'category' => 'Färben',
        'description' => 'Faceframe-Strähnentechnik für Gesichtsumrahmung',
        'notes' => 'Individuelle Absprache erforderlich',
    ],
];

echo "=== FRISEUR SERVICES ANLEGEN ===" . PHP_EOL;
echo PHP_EOL;
echo "Company ID: {$companyId}" . PHP_EOL;
echo "Branch ID: {$branchId}" . PHP_EOL;
echo "Cal.com Team ID: {$calcomTeamId}" . PHP_EOL;
echo "Anzahl Services: " . count($services) . PHP_EOL;
echo PHP_EOL;

$createdServices = [];
$createdCount = 0;
$skippedCount = 0;
$errorCount = 0;

foreach ($services as $index => $serviceData) {
    $num = $index + 1;
    echo "[$num/" . count($services) . "] {$serviceData['name']}..." . PHP_EOL;

    try {
        // Check if service already exists
        $existing = Service::where('company_id', $companyId)
            ->where('name', $serviceData['name'])
            ->first();

        if ($existing) {
            echo "  ⚠️  Service existiert bereits (ID: {$existing->id})" . PHP_EOL;
            $createdServices[] = $existing;
            $skippedCount++;
            continue;
        }

        // Step 1: Create Service in Database (without Event Type ID first)
        echo "  💾 Service in Datenbank anlegen..." . PHP_EOL;

        $service = new Service();
        $service->forceFill([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'name' => $serviceData['name'],
            'display_name' => null,
            'calcom_name' => $serviceData['name'],
            'slug' => \Illuminate\Support\Str::slug($serviceData['name']),
            'description' => $serviceData['description'],
            'category' => $serviceData['category'],
            'is_active' => true,
            'is_default' => false,
            'is_online' => true,
            'duration_minutes' => $serviceData['duration'],
            'buffer_time_minutes' => 0,
            'price' => $serviceData['price'],
            'calcom_event_type_id' => null, // Will be set after creation
            'schedule_id' => null,
            'booking_link' => null,
            'ai_prompt_context' => $serviceData['notes'],
        ]);

        $service->save();
        echo "  ✅ Service gespeichert (ID: {$service->id})" . PHP_EOL;

        // Step 2: Create Event Type in Cal.com using the Service object
        echo "  📞 Cal.com Event Type erstellen..." . PHP_EOL;

        try {
            $response = $calcomService->createEventType($service);

            if (!$response->successful()) {
                echo "  ❌ Cal.com API Fehler: " . $response->status() . PHP_EOL;
                echo "     " . $response->body() . PHP_EOL;

                // Delete service since Cal.com creation failed
                $service->delete();
                echo "  🗑️  Service gelöscht (Cal.com Fehler)" . PHP_EOL;
                $errorCount++;
                continue;
            }

            $eventTypeData = $response->json();
            $eventType = $eventTypeData['data'] ?? $eventTypeData;
            $eventTypeId = $eventType['id'] ?? null;

            if (!$eventTypeId) {
                echo "  ❌ Keine Event Type ID erhalten" . PHP_EOL;
                $service->delete();
                $errorCount++;
                continue;
            }

            echo "  ✅ Event Type erstellt (ID: {$eventTypeId})" . PHP_EOL;

            // Step 3: Update Service with Event Type ID
            $service->update(['calcom_event_type_id' => (string)$eventTypeId]);
            echo "  ✅ Service aktualisiert mit Event Type ID" . PHP_EOL;

        } catch (Exception $eventEx) {
            echo "  ❌ Cal.com Fehler: " . $eventEx->getMessage() . PHP_EOL;
            $service->delete();
            echo "  🗑️  Service gelöscht (Exception)" . PHP_EOL;
            $errorCount++;
            continue;
        }

        // Step 3: Create Event Mapping
        DB::table('calcom_event_mappings')->insert([
            'calcom_event_type_id' => (string)$eventTypeId,
            'calcom_team_id' => (string)$calcomTeamId,
            'company_id' => $companyId,
            'service_id' => $service->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        echo "  ✅ Event Mapping erstellt" . PHP_EOL;

        $createdServices[] = $service;
        $createdCount++;

    } catch (Exception $e) {
        echo "  ❌ Fehler: " . $e->getMessage() . PHP_EOL;
        $errorCount++;
    }

    echo PHP_EOL;
}

// Summary
echo "=== ZUSAMMENFASSUNG ===" . PHP_EOL;
echo "✅ Erfolgreich erstellt: {$createdCount}" . PHP_EOL;
echo "⚠️  Übersprungen (existieren): {$skippedCount}" . PHP_EOL;
echo "❌ Fehler: {$errorCount}" . PHP_EOL;
echo PHP_EOL;

if ($createdCount > 0) {
    echo "=== ERSTELLTE SERVICES ===" . PHP_EOL;
    foreach ($createdServices as $svc) {
        if ($svc->wasRecentlyCreated ?? false) {
            echo "  - {$svc->name} (ID: {$svc->id}, Event Type: {$svc->calcom_event_type_id})" . PHP_EOL;
        }
    }
}
