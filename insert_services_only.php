<?php

/**
 * Insert services using already-created Cal.com Event Type IDs
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$companyId = 1;
$branchId = '34c4d48e-4753-4715-9c30-c55843a943e8';
$calcomTeamId = 34209;

// Services with their Cal.com Event Type IDs (already created)
$services = [
    ['name' => 'Kinderhaarschnitt', 'duration' => 30, 'price' => 20.50, 'category' => 'Schnitt', 'description' => 'Kindgerechter Haarschnitt', 'event_type_id' => '3719738', 'notes' => null],
    ['name' => 'Trockenschnitt', 'duration' => 30, 'price' => 25.00, 'category' => 'Schnitt', 'description' => 'Haarschnitt ohne Waschen', 'event_type_id' => '3719739', 'notes' => null],
    ['name' => 'Waschen & Styling', 'duration' => 45, 'price' => 40.00, 'category' => 'Styling', 'description' => 'Haarwäsche und Styling', 'event_type_id' => '3719740', 'notes' => null],
    ['name' => 'Waschen, schneiden, föhnen', 'duration' => 60, 'price' => 45.00, 'category' => 'Schnitt', 'description' => 'Komplettservice', 'event_type_id' => '3719741', 'notes' => null],
    ['name' => 'Haarspende', 'duration' => 30, 'price' => 80.00, 'category' => 'Sonstiges', 'description' => 'Haarspende für wohltätige Zwecke', 'event_type_id' => '3719742', 'notes' => null],
    ['name' => 'Beratung', 'duration' => 30, 'price' => 30.00, 'category' => 'Beratung', 'description' => 'Professionelle Haarberatung', 'event_type_id' => '3719743', 'notes' => 'Bei Tressen, Balayage, Strähnen-Paket, Blondierung und Faceframe verpflichtend'],
    ['name' => 'Hairdetox', 'duration' => 15, 'price' => 12.50, 'category' => 'Pflege', 'description' => 'Intensive Haarentgiftung', 'event_type_id' => '3719744', 'notes' => 'Immer im Paket inkl. Leistung 1'],
    ['name' => 'Rebuild Treatment Olaplex', 'duration' => 15, 'price' => 15.50, 'category' => 'Pflege', 'description' => 'Wiederaufbau-Behandlung mit Olaplex', 'event_type_id' => '3719745', 'notes' => null],
    ['name' => 'Intensiv Pflege Maria Nila', 'duration' => 15, 'price' => 15.50, 'category' => 'Pflege', 'description' => 'Intensive Haarpflege mit Maria Nila Produkten', 'event_type_id' => '3719746', 'notes' => null],
    ['name' => 'Gloss', 'duration' => 30, 'price' => 45.00, 'category' => 'Färben', 'description' => 'Gloss-Behandlung für mehr Glanz', 'event_type_id' => '3719747', 'notes' => null],
    ['name' => 'Ansatzfärbung, waschen, schneiden, föhnen', 'duration' => 120, 'price' => 85.00, 'category' => 'Färben', 'description' => 'Komplett-Paket: Ansatzfärbung', 'event_type_id' => '3719748', 'notes' => null],
    ['name' => 'Ansatz, Längenausgleich, waschen, schneiden, föhnen', 'duration' => 120, 'price' => 85.00, 'category' => 'Färben', 'description' => 'Komplett-Paket: Ansatz und Längenausgleich', 'event_type_id' => '3719749', 'notes' => null],
    ['name' => 'Klassisches Strähnen-Paket', 'duration' => 120, 'price' => 125.00, 'category' => 'Färben', 'description' => 'Klassische Strähnentechnik', 'event_type_id' => '3719750', 'notes' => 'Individuelle Absprache erforderlich'],
    ['name' => 'Globale Blondierung', 'duration' => 120, 'price' => 185.00, 'category' => 'Färben', 'description' => 'Komplette Blondierung', 'event_type_id' => '3719751', 'notes' => 'Individuelle Absprache erforderlich'],
    ['name' => 'Strähnentechnik Balayage', 'duration' => 180, 'price' => 255.00, 'category' => 'Färben', 'description' => 'Moderne Balayage-Strähnentechnik', 'event_type_id' => '3719752', 'notes' => 'Individuelle Absprache erforderlich'],
    ['name' => 'Faceframe', 'duration' => 180, 'price' => 225.00, 'category' => 'Färben', 'description' => 'Faceframe-Strähnentechnik', 'event_type_id' => '3719753', 'notes' => 'Individuelle Absprache erforderlich'],
];

echo "=== SERVICES IN DATENBANK EINFÜGEN ===" . PHP_EOL;
echo "Anzahl: " . count($services) . PHP_EOL;
echo PHP_EOL;

$created = 0;
$failed = 0;

foreach ($services as $idx => $svc) {
    $num = $idx + 1;
    echo "[$num/" . count($services) . "] {$svc['name']}..." . PHP_EOL;

    try {
        // Insert Service into DB
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
            'calcom_event_type_id' => $svc['event_type_id'],
            'assignment_notes' => $svc['notes'], // Store notes here instead of ai_prompt_context
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        echo "  ✅ Service DB: {$serviceId}" . PHP_EOL;

        // Create Event Mapping
        DB::table('calcom_event_mappings')->insert([
            'calcom_event_type_id' => $svc['event_type_id'],
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
