<?php

/**
 * Analyze Event Types with No Slots
 *
 * Deep analysis of the 5 event types that return no available slots
 */

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "═══════════════════════════════════════════════════════════════\n";
echo "Cal.com Event Types - Deep Analysis of NO SLOTS Issues\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$calcomApiKey = config('services.calcom.api_key');
$calcomBaseUrl = config('services.calcom.base_url');
$calcomApiVersion = config('services.calcom.api_version');

// The 5 problematic event types
$eventTypes = [
    3757697 => 'Ansatz + Längenausgleich',
    3757707 => 'Ansatzfärbung',
    3757710 => 'Balayage/Ombré',
    3757758 => 'Dauerwelle',
    3757773 => 'Komplette Umfärbung (Blondierung)',
];

foreach ($eventTypes as $eventTypeId => $name) {
    echo "🔍 Analyzing: {$name} (Event: {$eventTypeId})\n";
    echo str_repeat("─", 63) . "\n";

    // Try different time ranges
    $timeRanges = [
        'Tomorrow 9-18h' => [
            'start' => Carbon::now('Europe/Berlin')->addDays(1)->setTime(9, 0, 0),
            'end' => Carbon::now('Europe/Berlin')->addDays(1)->setTime(18, 0, 0),
        ],
        'Next 7 days' => [
            'start' => Carbon::now('Europe/Berlin')->addDays(1)->setTime(9, 0, 0),
            'end' => Carbon::now('Europe/Berlin')->addDays(8)->setTime(18, 0, 0),
        ],
        'Next 30 days' => [
            'start' => Carbon::now('Europe/Berlin')->addDays(1)->setTime(9, 0, 0),
            'end' => Carbon::now('Europe/Berlin')->addDays(31)->setTime(18, 0, 0),
        ],
    ];

    foreach ($timeRanges as $rangeName => $range) {
        $response = Http::withHeaders([
            'cal-api-version' => $calcomApiVersion,
            'Authorization' => "Bearer {$calcomApiKey}",
        ])->timeout(10)->get("{$calcomBaseUrl}/slots/available", [
            'eventTypeId' => $eventTypeId,
            'startTime' => $range['start']->toIso8601String(),
            'endTime' => $range['end']->toIso8601String(),
        ]);

        if ($response->successful()) {
            $slotsData = $response->json('data.slots') ?? [];
            $totalSlots = 0;
            foreach ($slotsData as $date => $dateSlots) {
                $totalSlots += count($dateSlots);
            }

            if ($totalSlots > 0) {
                echo "   ✅ {$rangeName}: {$totalSlots} slots found!\n";

                // Show first few dates with slots
                $datesWithSlots = array_keys($slotsData);
                if (!empty($datesWithSlots)) {
                    echo "      First available dates: " . implode(', ', array_slice($datesWithSlots, 0, 3)) . "\n";
                }
            } else {
                echo "   ❌ {$rangeName}: No slots\n";
            }
        } else {
            echo "   ⚠️  {$rangeName}: API Error " . $response->status() . "\n";
        }
    }

    echo "\n";
}

echo str_repeat("═", 63) . "\n";
echo "📊 Possible Reasons for NO SLOTS:\n\n";

echo "1. **Service Duration Too Long**\n";
echo "   → Färbe-Services benötigen 60-120 Min\n";
echo "   → Verfügbare Zeitfenster könnten zu kurz sein\n\n";

echo "2. **Host Availability Not Set**\n";
echo "   → Check 'Availability' tab for each host\n";
echo "   → Ensure working hours are configured\n\n";

echo "3. **Buffer Times Too Large**\n";
echo "   → Check 'Before Event Buffer' and 'After Event Buffer'\n";
echo "   → Large buffers reduce available slots\n\n";

echo "4. **Date Overrides Active**\n";
echo "   → Check if temporary blocks are set\n";
echo "   → Check 'Date Overrides' in Cal.com\n\n";

echo "5. **Hosts Not Assigned to Event Type**\n";
echo "   → Ensure both Fabian entries exist for these services\n";
echo "   → Check 'Team' tab in Cal.com\n\n";

echo str_repeat("═", 63) . "\n";
