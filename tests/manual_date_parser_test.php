<?php

/**
 * Manual Date Parser Test for "15.1" Bug Fix
 *
 * Phase 1.3.3: Verify German short date format parsing
 *
 * Run: php tests/manual_date_parser_test.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\Retell\DateTimeParser;
use Carbon\Carbon;

// Set Berlin timezone
date_default_timezone_set('Europe/Berlin');

echo "═══════════════════════════════════════════════════════════════\n";
echo "📅 GERMAN SHORT DATE FORMAT TEST (Phase 1.3.3)\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$parser = new DateTimeParser();

// Simulate today's date for testing
$today = Carbon::now('Europe/Berlin');
echo "🕐 Testing Date: " . $today->format('Y-m-d (l, F)') . "\n";
echo "   Current Month: " . $today->month . " (" . $today->format('F') . ")\n";
echo "   Current Day: " . $today->day . "\n\n";

// Test scenarios
$testCases = [
    // Format: [input, expected_behavior, description]
    ['15.1', 'Current or next occurrence', 'User says "fünfzehnte Punkt eins"'],
    ['15.10', 'October 15th', 'User says "fünfzehnter zehnter"'],
    ['5.11', 'November 5th', 'User says "fünfter elfter"'],
    ['20.12', 'December 20th', 'User says "zwanzigster zwölfter"'],
    ['1.1', 'Next year January 1st', 'User says "erster Punkt eins"'],
    ['25.10', 'October 25th', 'User says "fünfundzwanzigster zehnter"'],
    ['heute', 'Today', 'Relative date'],
    ['morgen', 'Tomorrow', 'Relative date'],
];

echo "TEST CASES:\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

foreach ($testCases as $index => $testCase) {
    [$input, $expectedBehavior, $description] = $testCase;

    echo "Test Case " . ($index + 1) . ": $description\n";
    echo "Input: \"$input\"\n";
    echo "Expected: $expectedBehavior\n";

    try {
        $result = $parser->parseDateString($input);

        if ($result) {
            $resultCarbon = Carbon::parse($result);
            $daysDiff = $resultCarbon->diffInDays($today, false); // negative if future
            $isPast = $resultCarbon->isPast();

            echo "✅ Parsed: $result ({$resultCarbon->format('l, F j, Y')})\n";
            echo "   Days from now: " . abs($daysDiff) . ($daysDiff < 0 ? " (future)" : " (past)") . "\n";
            echo "   Month: " . $resultCarbon->month . " (" . $resultCarbon->format('F') . ")\n";
            echo "   Is Past: " . ($isPast ? "Yes ⚠️" : "No ✅") . "\n";

            // Validation checks
            if ($isPast && abs($daysDiff) > 1) {
                echo "   ⚠️  WARNING: Result is in the past!\n";
            }

            // Specific validation for "15.1" format
            if ($input === '15.1') {
                if ($resultCarbon->month === 1 && $today->month !== 1) {
                    echo "   ❌ ERROR: Interpreted as January (should be October or next occurrence!)\n";
                } elseif ($resultCarbon->month === 10 || ($resultCarbon->month === 1 && $resultCarbon->year > $today->year)) {
                    echo "   ✅ CORRECT: Interpreted as current/next month occurrence\n";
                }
            }
        } else {
            echo "❌ FAILED: Could not parse\n";
        }
    } catch (\Exception $e) {
        echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    }

    echo "\n";
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "SPECIFIC '15.1' SCENARIO TEST\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Specific test for the bug: "15.1" should be October, not January
echo "Bug Fix Verification:\n";
echo "User says: \"fünfzehnte Punkt eins\" → STT transcribes: \"15.1\"\n\n";

$result = $parser->parseDateString('15.1');
if ($result) {
    $resultCarbon = Carbon::parse($result);
    $currentMonth = $today->month;

    echo "Parsed Date: $result\n";
    echo "Parsed Month: " . $resultCarbon->month . " (" . $resultCarbon->format('F') . ")\n";
    echo "Current Month: $currentMonth (" . $today->format('F') . ")\n\n";

    // Check if correctly interpreted
    if ($currentMonth === 10) { // October
        if ($resultCarbon->month === 1) {
            echo "❌ FAILED: Interpreted as January (BUG NOT FIXED!)\n";
            echo "   Expected: October 15th or next year's January\n";
        } elseif ($resultCarbon->month === 10) {
            echo "✅ PASSED: Correctly interpreted as October 15th\n";
        } elseif ($resultCarbon->month === 1 && $resultCarbon->year > $today->year) {
            echo "✅ PASSED: Correctly interpreted as next year's January (15th passed)\n";
        }
    } else {
        echo "ℹ️  INFO: Test running in month $currentMonth, adjust expectations\n";
        echo "   Logic: Month 1 in input should be interpreted as current or next occurrence\n";
    }
} else {
    echo "❌ CRITICAL FAILURE: Could not parse '15.1'\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "TEST COMPLETE\n";
echo "═══════════════════════════════════════════════════════════════\n";
