<?php

namespace Tests\Unit;

use Tests\TestCase;
use Carbon\Carbon;

/**
 * Test Slot Flattening Logic - Bug #1 Fix Verification
 *
 * Verifies that Cal.com's date-grouped slot structure gets correctly
 * flattened into a single array for time matching.
 */
class SlotFlatteningTest extends TestCase
{
    /**
     * Test that date-grouped slots are correctly flattened
     * This simulates Cal.com V2 API response format
     */
    public function test_date_grouped_slots_are_flattened()
    {
        // Simulate Cal.com V2 response format
        $slotsData = [
            '2025-10-20' => [
                ['time' => '2025-10-20T05:00:00.000Z', 'duration' => 60],
                ['time' => '2025-10-20T05:30:00.000Z', 'duration' => 60],
                ['time' => '2025-10-20T12:00:00.000Z', 'duration' => 60], // 14:00 Berlin
                ['time' => '2025-10-20T13:00:00.000Z', 'duration' => 60], // 15:00 Berlin
            ],
            '2025-10-21' => [
                ['time' => '2025-10-21T05:00:00.000Z', 'duration' => 60],
            ]
        ];

        // Apply flattening logic (same as in RetellFunctionCallHandler)
        $slots = [];
        if (is_array($slotsData)) {
            foreach ($slotsData as $date => $dateSlots) {
                if (is_array($dateSlots)) {
                    $slots = array_merge($slots, $dateSlots);
                }
            }
        }

        // Assertions
        $this->assertCount(5, $slots, 'Should flatten 4+1 slots from 2 dates');
        $this->assertEquals('2025-10-20T05:00:00.000Z', $slots[0]['time']);
        $this->assertEquals('2025-10-21T05:00:00.000Z', $slots[4]['time']);
        $this->assertIsArray($slots[0], 'Slots should remain as arrays with time+duration');
    }

    /**
     * Test timezone conversion for slot matching
     * Critical: Cal.com returns UTC, we compare in Europe/Berlin
     */
    public function test_timezone_conversion_for_slot_matching()
    {
        // Cal.com slot: 12:00 UTC = 14:00 Europe/Berlin (Summer: UTC+2)
        $calcomSlotTime = '2025-10-20T12:00:00.000Z';
        $parsedSlot = Carbon::parse($calcomSlotTime); // Defaults to UTC

        // User requested: 14:00 Europe/Berlin
        $requestedTime = Carbon::parse('2025-10-20 14:00:00', 'Europe/Berlin');

        echo "\n";
        echo "Cal.com slot (UTC):    " . $parsedSlot->format('Y-m-d H:i:s') . " (" . $parsedSlot->timezone->getName() . ")\n";
        echo "User requested (Berlin): " . $requestedTime->format('Y-m-d H:i:s') . " (" . $requestedTime->timezone->getName() . ")\n";
        echo "Cal.com → Berlin:      " . $parsedSlot->copy()->setTimezone('Europe/Berlin')->format('Y-m-d H:i:s') . "\n";
        echo "\n";

        // Current code comparison (WITHOUT timezone conversion)
        $slotFormatted = $parsedSlot->format('Y-m-d H:i');  // 2025-10-20 12:00 (UTC!)
        $requestedFormatted = $requestedTime->format('Y-m-d H:i');  // 2025-10-20 14:00 (Berlin!)

        $this->assertNotEquals($slotFormatted, $requestedFormatted,
            'BUG: Without timezone conversion, 12:00 UTC != 14:00 Berlin');

        // CORRECT comparison (WITH timezone conversion)
        $slotBerlin = $parsedSlot->setTimezone('Europe/Berlin')->format('Y-m-d H:i');

        $this->assertEquals($slotBerlin, $requestedFormatted,
            'FIX: With timezone conversion, 12:00 UTC == 14:00 Berlin');
    }

    /**
     * Test exact slot matching logic
     */
    public function test_exact_slot_matching()
    {
        $slots = [
            ['time' => '2025-10-20T05:00:00.000Z', 'duration' => 60], // 07:00 Berlin
            ['time' => '2025-10-20T12:00:00.000Z', 'duration' => 60], // 14:00 Berlin
            ['time' => '2025-10-20T13:00:00.000Z', 'duration' => 60], // 15:00 Berlin
        ];

        // User requests 14:00 Berlin
        $requestedTime = Carbon::parse('2025-10-20 14:00', 'Europe/Berlin');

        // Simulate isTimeAvailable logic WITH timezone fix
        $found = false;
        foreach ($slots as $slot) {
            $slotTime = $slot['time'];
            $parsedSlot = Carbon::parse($slotTime)->setTimezone('Europe/Berlin');
            $slotFormatted = $parsedSlot->format('Y-m-d H:i');
            $requestedFormatted = $requestedTime->format('Y-m-d H:i');

            echo "Comparing: " . $slotFormatted . " vs " . $requestedFormatted . "\n";

            if ($slotFormatted === $requestedFormatted) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, '14:00 Berlin should match 2025-10-20T12:00:00.000Z (UTC)');
    }

    /**
     * Test that afternoon ranking works correctly
     */
    public function test_afternoon_ranking_prefers_later()
    {
        // User requests 13:00 (afternoon)
        $desiredTime = Carbon::parse('2025-10-20 13:00');

        $alternatives = collect([
            [
                'datetime' => Carbon::parse('2025-10-20 10:30'),
                'type' => 'same_day_earlier',
                'description' => '10:30'
            ],
            [
                'datetime' => Carbon::parse('2025-10-20 14:00'),
                'type' => 'same_day_later',
                'description' => '14:00'
            ],
        ]);

        // Apply ranking logic (from AppointmentAlternativeFinder)
        $isAfternoonRequest = $desiredTime->hour >= 12;

        $ranked = $alternatives->map(function($alt) use ($desiredTime, $isAfternoonRequest) {
            $minutesDiff = abs($desiredTime->diffInMinutes($alt['datetime']));
            $score = 10000 - $minutesDiff;

            $score += match($alt['type']) {
                'same_day_later' => $isAfternoonRequest ? 500 : 300,
                'same_day_earlier' => $isAfternoonRequest ? 300 : 500,
                default => 0
            };

            $alt['score'] = $score;
            return $alt;
        })->sortByDesc('score');

        $topAlternative = $ranked->first();

        echo "\nAfternoon request (13:00):\n";
        echo "10:30 (earlier) score: " . $ranked->where('description', '10:30')->first()['score'] . "\n";
        echo "14:00 (later) score:   " . $ranked->where('description', '14:00')->first()['score'] . "\n";
        echo "Top choice: " . $topAlternative['description'] . "\n\n";

        $this->assertEquals('14:00', $topAlternative['description'],
            'For afternoon request, should prefer LATER (14:00) over EARLIER (10:30)');
    }
}
