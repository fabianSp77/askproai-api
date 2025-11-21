#!/usr/bin/env php
<?php

/**
 * CRITICAL FIX: Update duration_minutes for all Krückenberg services
 *
 * Problem: Services were created with 'duration' field (doesn't exist)
 * Result: All services have duration_minutes = NULL
 * Impact: Service selection always fails, wrong service always booked
 *
 * Run: php database/scripts/fix_kruckenberg_services_duration.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "   CRITICAL FIX: Krückenberg Services Duration\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// All 17 services with correct durations
$services = [
    // Herrenhaarschnitte (Men's Haircuts)
    ['name' => 'Herrenhaarschnitt Classic', 'duration_minutes' => 30, 'price' => 28.00],
    ['name' => 'Herrenhaarschnitt Premium', 'duration_minutes' => 45, 'price' => 38.00],
    ['name' => 'Herrenhaarschnitt + Bart', 'duration_minutes' => 60, 'price' => 48.00],
    ['name' => 'Herrenhaarschnitt + Waschen', 'duration_minutes' => 40, 'price' => 35.00],

    // Damenhaarschnitte (Women's Haircuts)
    ['name' => 'Damenhaarschnitt Kurz', 'duration_minutes' => 45, 'price' => 42.00],
    ['name' => 'Damenhaarschnitt Mittel', 'duration_minutes' => 60, 'price' => 55.00],
    ['name' => 'Damenhaarschnitt Lang', 'duration_minutes' => 75, 'price' => 68.00],
    ['name' => 'Damenhaarschnitt + Föhnen', 'duration_minutes' => 90, 'price' => 75.00],

    // Färben & Strähnchen (Coloring & Highlights)
    ['name' => 'Färben Kurzhaar', 'duration_minutes' => 90, 'price' => 65.00],
    ['name' => 'Färben Langhaar', 'duration_minutes' => 120, 'price' => 95.00],
    ['name' => 'Strähnchen Partial', 'duration_minutes' => 120, 'price' => 85.00],
    ['name' => 'Strähnchen Komplett', 'duration_minutes' => 150, 'price' => 120.00],

    // Spezialbehandlungen (Special Treatments)
    ['name' => 'Dauerwelle', 'duration_minutes' => 120, 'price' => 85.00],  // ← THE PROBLEMATIC ONE
    ['name' => 'Keratin-Behandlung', 'duration_minutes' => 180, 'price' => 150.00],
    ['name' => 'Hochsteckfrisur', 'duration_minutes' => 90, 'price' => 75.00],

    // Kinder & Basic (Kids & Basic)
    ['name' => 'Kinderhaarschnitt (bis 12 Jahre)', 'duration_minutes' => 30, 'price' => 18.00],
    ['name' => 'Waschen & Föhnen', 'duration_minutes' => 30, 'price' => 25.00],
];

echo "Found " . count($services) . " services to update\n\n";

DB::beginTransaction();

try {
    $updated = 0;
    $notFound = 0;

    foreach ($services as $serviceData) {
        echo "Processing: {$serviceData['name']}...\n";

        $count = DB::table('services')
            ->where('company_id', 1)
            ->where('name', $serviceData['name'])
            ->update([
                'duration_minutes' => $serviceData['duration_minutes'],
                'price' => $serviceData['price'],
                'updated_at' => now(),
            ]);

        if ($count > 0) {
            echo "   ✅ Updated: duration_minutes = {$serviceData['duration_minutes']} min, price = €{$serviceData['price']}\n";
            $updated += $count;
        } else {
            echo "   ⚠️  NOT FOUND in database\n";
            $notFound++;
        }

        echo "\n";
    }

    DB::commit();

    echo "═══════════════════════════════════════════════════════════════\n";
    echo "   SUMMARY\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "✅ Successfully updated: {$updated} services\n";
    echo "⚠️  Not found: {$notFound} services\n";
    echo "\n";

    // Verify the fix
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "   VERIFICATION\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    $allServices = DB::table('services')
        ->where('company_id', 1)
        ->orderBy('duration_minutes', 'ASC')
        ->get(['id', 'name', 'duration_minutes', 'price', 'is_default']);

    echo "All services for Company 1:\n\n";

    foreach ($allServices as $service) {
        $default = $service->is_default ? ' [DEFAULT]' : '';
        $duration = $service->duration_minutes ?? 'NULL';

        if ($service->duration_minutes === null) {
            echo "   ❌ {$service->id}: {$service->name} - {$duration} min - €{$service->price}{$default}\n";
        } else {
            echo "   ✅ {$service->id}: {$service->name} - {$duration} min - €{$service->price}{$default}\n";
        }
    }

    echo "\n";

    // Check for NULL durations
    $nullCount = DB::table('services')
        ->where('company_id', 1)
        ->whereNull('duration_minutes')
        ->count();

    if ($nullCount > 0) {
        echo "⚠️  WARNING: {$nullCount} services still have NULL duration_minutes!\n";
    } else {
        echo "✅ SUCCESS: All services now have valid duration_minutes!\n";
    }

    echo "\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "   FIX COMPLETE\n";
echo "═══════════════════════════════════════════════════════════════\n\n";
