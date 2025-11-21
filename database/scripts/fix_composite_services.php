#!/usr/bin/env php
<?php

/**
 * CRITICAL FIX: Configure Composite Services for Friseur Eins
 *
 * Problem: 4 services should be composite but are configured as simple
 * - Dauerwelle (120 min)
 * - Färben Langhaar (120 min)
 * - Strähnchen Komplett (150 min)
 * - Keratin-Behandlung (180 min)
 *
 * Solution: Update services to composite=true and add segment definitions
 *
 * Run: php database/scripts/fix_composite_services.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Service;
use Illuminate\Support\Facades\DB;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "   CRITICAL FIX: Composite Services für Friseur Eins\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$compositeServices = [
    // 1. Dauerwelle (120 min) = 20 min + 25 min pause + 40 min
    [
        'name' => 'Dauerwelle',
        'composite' => true,
        'segments' => [
            [
                'key' => 'A',
                'name' => 'Vorbereitung & Auftrag',
                'durationMin' => 20,
                'durationMax' => 25,
                'gapAfterMin' => 25,
                'gapAfterMax' => 30,
                'allowedRoles' => ['stylist', 'senior_stylist'],
                'preferSameStaff' => true
            ],
            [
                'key' => 'B',
                'name' => 'Ausspülen & Styling',
                'durationMin' => 35,
                'durationMax' => 40,
                'gapAfterMin' => 0,
                'gapAfterMax' => 0,
                'allowedRoles' => ['stylist', 'senior_stylist'],
                'preferSameStaff' => true
            ]
        ],
        'pause_bookable_policy' => 'blocked',
        'reminder_policy' => 'single'
    ],

    // 2. Färben Langhaar (120 min) = 35 min + 35 min pause + 25 min
    [
        'name' => 'Färben Langhaar',
        'composite' => true,
        'segments' => [
            [
                'key' => 'A',
                'name' => 'Farbauftrag',
                'durationMin' => 30,
                'durationMax' => 40,
                'gapAfterMin' => 30,
                'gapAfterMax' => 40,
                'allowedRoles' => ['colorist', 'senior_stylist'],
                'preferSameStaff' => true
            ],
            [
                'key' => 'B',
                'name' => 'Ausspülen & Pflege',
                'durationMin' => 20,
                'durationMax' => 30,
                'gapAfterMin' => 0,
                'gapAfterMax' => 0,
                'allowedRoles' => ['stylist', 'colorist', 'senior_stylist'],
                'preferSameStaff' => false
            ]
        ],
        'pause_bookable_policy' => 'blocked',
        'reminder_policy' => 'single'
    ],

    // 3. Strähnchen Komplett (150 min) = 45 min + 35 min pause + 35 min
    [
        'name' => 'Strähnchen Komplett',
        'composite' => true,
        'segments' => [
            [
                'key' => 'A',
                'name' => 'Strähnchenauftrag',
                'durationMin' => 40,
                'durationMax' => 50,
                'gapAfterMin' => 30,
                'gapAfterMax' => 40,
                'allowedRoles' => ['colorist', 'senior_stylist'],
                'preferSameStaff' => true
            ],
            [
                'key' => 'B',
                'name' => 'Ausspülen & Styling',
                'durationMin' => 30,
                'durationMax' => 40,
                'gapAfterMin' => 0,
                'gapAfterMax' => 0,
                'allowedRoles' => ['stylist', 'colorist', 'senior_stylist'],
                'preferSameStaff' => false
            ]
        ],
        'pause_bookable_policy' => 'blocked',
        'reminder_policy' => 'single'
    ],

    // 4. Keratin-Behandlung (180 min) = 15 min + 5 min + 55 min + 60 min + 35 min
    [
        'name' => 'Keratin-Behandlung',
        'composite' => true,
        'segments' => [
            [
                'key' => 'A',
                'name' => 'Vorbereitung',
                'durationMin' => 15,
                'durationMax' => 20,
                'gapAfterMin' => 5,
                'gapAfterMax' => 10,
                'allowedRoles' => ['stylist', 'senior_stylist'],
                'preferSameStaff' => true
            ],
            [
                'key' => 'B',
                'name' => 'Keratin Auftrag',
                'durationMin' => 50,
                'durationMax' => 60,
                'gapAfterMin' => 55,
                'gapAfterMax' => 65,
                'allowedRoles' => ['senior_stylist'],
                'preferSameStaff' => true
            ],
            [
                'key' => 'C',
                'name' => 'Ausspülen & Föhnen',
                'durationMin' => 30,
                'durationMax' => 40,
                'gapAfterMin' => 0,
                'gapAfterMax' => 0,
                'allowedRoles' => ['stylist', 'senior_stylist'],
                'preferSameStaff' => true
            ]
        ],
        'pause_bookable_policy' => 'blocked',
        'reminder_policy' => 'single'
    ],
];

echo "Found " . count($compositeServices) . " services to convert to composite\n\n";

DB::beginTransaction();

try {
    $updated = 0;
    $notFound = 0;

    foreach ($compositeServices as $serviceData) {
        echo "Processing: {$serviceData['name']}...\n";

        $service = Service::where('company_id', 1)
            ->where('name', $serviceData['name'])
            ->first();

        if ($service) {
            $service->update([
                'composite' => true,
                'segments' => $serviceData['segments'],
                'pause_bookable_policy' => $serviceData['pause_bookable_policy'],
                'reminder_policy' => $serviceData['reminder_policy'],
                'updated_at' => now(),
            ]);

            echo "   ✅ Updated to composite with " . count($serviceData['segments']) . " segments\n";

            // Show segments
            foreach ($serviceData['segments'] as $segment) {
                $gap = $segment['gapAfterMin'] > 0
                    ? " → Pause {$segment['gapAfterMin']}-{$segment['gapAfterMax']} min"
                    : "";
                echo "      {$segment['key']}: {$segment['name']} ({$segment['durationMin']}-{$segment['durationMax']} min){$gap}\n";
            }

            $updated++;
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

    $allCompositeServices = Service::where('company_id', 1)
        ->where('composite', true)
        ->get(['id', 'name', 'duration_minutes', 'composite']);

    echo "All composite services for Company 1:\n\n";

    foreach ($allCompositeServices as $service) {
        $segments = $service->segments ?? [];
        $segmentCount = count($segments);

        echo "   ✅ {$service->id}: {$service->name}\n";
        echo "      Total Duration: {$service->duration_minutes} min\n";
        echo "      Segments: {$segmentCount}\n";

        if ($segmentCount > 0) {
            $totalActiveTime = 0;
            $totalGapTime = 0;

            foreach ($segments as $segment) {
                $totalActiveTime += $segment['durationMin'] ?? 0;
                $totalGapTime += $segment['gapAfterMin'] ?? 0;
            }

            echo "      Active Time: {$totalActiveTime} min\n";
            echo "      Gap Time: {$totalGapTime} min\n";
            echo "      Calculated Total: " . ($totalActiveTime + $totalGapTime) . " min\n";

            if (($totalActiveTime + $totalGapTime) != $service->duration_minutes) {
                echo "      ⚠️  WARNING: Total time mismatch!\n";
            }
        }

        echo "\n";
    }

    // Check for services that should be composite but aren't
    $shouldBeComposite = ['Dauerwelle', 'Färben Langhaar', 'Strähnchen Komplett', 'Keratin-Behandlung'];
    $simpleServices = Service::where('company_id', 1)
        ->whereIn('name', $shouldBeComposite)
        ->where('composite', false)
        ->get(['id', 'name']);

    if ($simpleServices->count() > 0) {
        echo "⚠️  WARNING: These services should be composite but aren't:\n";
        foreach ($simpleServices as $service) {
            echo "   - {$service->name} (ID: {$service->id})\n";
        }
        echo "\n";
    } else {
        echo "✅ SUCCESS: All required services are now composite!\n\n";
    }

} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "   FIX COMPLETE\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "NEXT STEPS:\n";
echo "1. Test composite booking via Filament Admin UI\n";
echo "2. Create Cal.com Event Types for each segment\n";
echo "3. Create CalcomEventMap entries for segment mappings\n";
echo "4. Update RetellFunctionCallHandler to support composite bookings\n";
echo "5. Test live phone call booking\n";
echo "\n";
