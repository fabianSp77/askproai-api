<?php
/**
 * Test All Fixes from 2025-11-07 Deployment
 *
 * Tests:
 * 1. Booking Notice (60min)
 * 2. get_alternatives message generation
 * 3. Conversation Flow error handling
 * 4. Token optimization impact
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

echo "╔═══════════════════════════════════════════════════════════════╗" . PHP_EOL;
echo "║  🧪 TESTING ALL FIXES - Call #1694 Deployment               ║" . PHP_EOL;
echo "╚═══════════════════════════════════════════════════════════════╝" . PHP_EOL;
echo PHP_EOL;

$passed = 0;
$failed = 0;

// ============================================================================
// TEST #1: Booking Notice Change
// ============================================================================
echo "═══ TEST #1: Booking Notice (120min → 60min) ═══" . PHP_EOL;

$service = \App\Models\Service::find(438);

if (!$service) {
    echo "❌ FAIL: Service #438 not found" . PHP_EOL;
    $failed++;
} else {
    echo "Service: {$service->name}" . PHP_EOL;
    echo "Booking Notice: {$service->minimum_booking_notice} minutes" . PHP_EOL;

    if ($service->minimum_booking_notice == 60) {
        echo "✅ PASS: Booking notice = 60 minutes (correct)" . PHP_EOL;
        $passed++;
    } else {
        echo "❌ FAIL: Booking notice = {$service->minimum_booking_notice} (expected 60)" . PHP_EOL;
        $failed++;
    }

    // Test scenario: Call at 14:58, want 16:00 (62min advance)
    echo PHP_EOL . "Test Scenario:" . PHP_EOL;
    echo "  Current time: 14:58" . PHP_EOL;
    echo "  Requested: 16:00" . PHP_EOL;
    echo "  Advance: 62 minutes" . PHP_EOL;

    $now = Carbon::create(2025, 11, 7, 14, 58, 0, 'Europe/Berlin');
    $requested = Carbon::create(2025, 11, 7, 16, 0, 0, 'Europe/Berlin');
    $advanceMinutes = $now->diffInMinutes($requested);

    echo "  Calculated advance: {$advanceMinutes} minutes" . PHP_EOL;
    echo "  Required: {$service->minimum_booking_notice} minutes" . PHP_EOL;

    if ($advanceMinutes >= $service->minimum_booking_notice) {
        echo "✅ PASS: 62min > 60min → Booking ALLOWED" . PHP_EOL;
        $passed++;
    } else {
        echo "❌ FAIL: 62min < {$service->minimum_booking_notice}min → Would be BLOCKED" . PHP_EOL;
        $failed++;
    }
}

echo PHP_EOL;

// ============================================================================
// TEST #2: get_alternatives Message Generation
// ============================================================================
echo "═══ TEST #2: get_alternatives Message Generation ═══" . PHP_EOL;

// Create test alternatives
$desiredDate = Carbon::create(2025, 11, 7, 16, 0, 0, 'Europe/Berlin'); // Friday
$mondayDate = Carbon::create(2025, 11, 10, 8, 50, 0, 'Europe/Berlin'); // Monday

$finder = new \App\Services\AppointmentAlternativeFinder();

// Use reflection to test private method generateDateDescription
$reflection = new ReflectionClass($finder);
$method = $reflection->getMethod('generateDateDescription');
$method->setAccessible(true);

// Test 1: Same day
$sameDay = $desiredDate->copy();
$description = $method->invoke($finder, $sameDay, $desiredDate);
echo "Same day description: '{$description}'" . PHP_EOL;
if ($description === 'am gleichen Tag') {
    echo "✅ PASS: Same day = 'am gleichen Tag'" . PHP_EOL;
    $passed++;
} else {
    echo "❌ FAIL: Expected 'am gleichen Tag', got '{$description}'" . PHP_EOL;
    $failed++;
}

// Test 2: Monday (different day)
$description = $method->invoke($finder, $mondayDate, $desiredDate);
echo "Different day description: '{$description}'" . PHP_EOL;
if (strpos($description, 'Montag') !== false) {
    echo "✅ PASS: Monday shows 'Montag' in description" . PHP_EOL;
    $passed++;
} else {
    echo "❌ FAIL: Expected 'Montag' in description, got '{$description}'" . PHP_EOL;
    $failed++;
}

// Test 3: Check that formatGermanWeekday is NOT used in descriptions anymore
$fileContent = file_get_contents(__DIR__ . '/../app/Services/AppointmentAlternativeFinder.php');
$descriptionMatches = preg_match_all('/\'description\'.*formatGermanWeekday/', $fileContent, $matches);

echo PHP_EOL . "Checking code for formatGermanWeekday in descriptions:" . PHP_EOL;
echo "  Occurrences: {$descriptionMatches}" . PHP_EOL;

if ($descriptionMatches <= 0) {
    echo "✅ PASS: No formatGermanWeekday in description assignments" . PHP_EOL;
    $passed++;
} else {
    echo "❌ FAIL: Found {$descriptionMatches} formatGermanWeekday in descriptions (should be 0)" . PHP_EOL;
    $failed++;
}

echo PHP_EOL;

// ============================================================================
// TEST #3: Conversation Flow Changes
// ============================================================================
echo "═══ TEST #3: Conversation Flow V76 ═══" . PHP_EOL;

$flowPath = '/tmp/conversation_flow_v76_with_v74.json';

if (!file_exists($flowPath)) {
    echo "❌ FAIL: Flow backup not found at {$flowPath}" . PHP_EOL;
    $failed++;
} else {
    $flow = json_decode(file_get_contents($flowPath), true);

    // Test version
    echo "Flow Version: " . ($flow['version'] ?? 'unknown') . PHP_EOL;
    if (($flow['version'] ?? 0) >= 76) {
        echo "✅ PASS: Version 76+ (has all fixes)" . PHP_EOL;
        $passed++;
    } else {
        echo "❌ FAIL: Version < 76 (old version)" . PHP_EOL;
        $failed++;
    }

    // Test node count
    $nodeCount = count($flow['nodes'] ?? []);
    echo "Node count: {$nodeCount}" . PHP_EOL;
    if ($nodeCount >= 31) {
        echo "✅ PASS: 31+ nodes (error handler added)" . PHP_EOL;
        $passed++;
    } else {
        echo "❌ FAIL: Only {$nodeCount} nodes (expected 31+)" . PHP_EOL;
        $failed++;
    }

    // Test error handler node exists
    $errorNode = null;
    foreach ($flow['nodes'] as $node) {
        if ($node['id'] === 'node_collect_missing_data') {
            $errorNode = $node;
            break;
        }
    }

    if ($errorNode) {
        echo "✅ PASS: Error handler node 'node_collect_missing_data' exists" . PHP_EOL;
        echo "   Name: " . ($errorNode['name'] ?? 'unknown') . PHP_EOL;
        $passed++;
    } else {
        echo "❌ FAIL: Error handler node not found" . PHP_EOL;
        $failed++;
    }

    // Test start_booking has error edge
    $startBookingNode = null;
    foreach ($flow['nodes'] as $node) {
        if ($node['id'] === 'func_start_booking') {
            $startBookingNode = $node;
            break;
        }
    }

    if ($startBookingNode) {
        $hasErrorEdge = false;
        foreach ($startBookingNode['edges'] ?? [] as $edge) {
            if ($edge['destination_node_id'] === 'node_collect_missing_data') {
                $hasErrorEdge = true;
                break;
            }
        }

        if ($hasErrorEdge) {
            echo "✅ PASS: func_start_booking has error edge to handler" . PHP_EOL;
            $passed++;
        } else {
            echo "❌ FAIL: func_start_booking missing error edge" . PHP_EOL;
            $failed++;
        }
    } else {
        echo "❌ FAIL: func_start_booking node not found" . PHP_EOL;
        $failed++;
    }

    // Test global prompt size
    $promptSize = strlen($flow['global_prompt'] ?? '');
    echo PHP_EOL . "Global Prompt size: {$promptSize} chars" . PHP_EOL;
    if ($promptSize >= 3000 && $promptSize <= 3500) {
        echo "✅ PASS: Prompt size optimized (~3131 chars)" . PHP_EOL;
        $passed++;
    } else {
        echo "❌ FAIL: Prompt size {$promptSize} (expected ~3131)" . PHP_EOL;
        $failed++;
    }

    // Test intent router optimization
    $intentNode = null;
    foreach ($flow['nodes'] as $node) {
        if ($node['id'] === 'intent_router') {
            $intentNode = $node;
            break;
        }
    }

    if ($intentNode) {
        $bookingEdge = null;
        foreach ($intentNode['edges'] ?? [] as $edge) {
            if ($edge['id'] === 'edge_intent_to_book') {
                $bookingEdge = $edge;
                break;
            }
        }

        if ($bookingEdge) {
            $intentPromptSize = strlen($bookingEdge['transition_condition']['prompt'] ?? '');
            echo "Intent booking prompt size: {$intentPromptSize} chars" . PHP_EOL;
            if ($intentPromptSize < 300) {
                echo "✅ PASS: Intent prompt optimized (<300 chars, was ~500)" . PHP_EOL;
                $passed++;
            } else {
                echo "❌ FAIL: Intent prompt not optimized ({$intentPromptSize} chars)" . PHP_EOL;
                $failed++;
            }
        }
    }
}

echo PHP_EOL;

// ============================================================================
// TEST #4: Simulated Function Call Test
// ============================================================================
echo "═══ TEST #4: Simulated Backend Function Test ═══" . PHP_EOL;

// Simulate get_alternatives call
$companyId = 1;
$branchId = 'branch_uuid_friseur1'; // Use actual or test branch

try {
    $service = \App\Models\Service::where('company_id', $companyId)
        ->where('name', 'Herrenhaarschnitt')
        ->where('is_active', true)
        ->first();

    if ($service) {
        echo "Service found: {$service->name} (ID: {$service->id})" . PHP_EOL;
        echo "Event Type ID: {$service->calcom_event_type_id}" . PHP_EOL;
        echo "Booking Notice: {$service->minimum_booking_notice} min" . PHP_EOL;
        echo "✅ PASS: Service lookup works" . PHP_EOL;
        $passed++;
    } else {
        echo "❌ FAIL: Service not found" . PHP_EOL;
        $failed++;
    }
} catch (\Exception $e) {
    echo "❌ FAIL: Service lookup error: " . $e->getMessage() . PHP_EOL;
    $failed++;
}

echo PHP_EOL;

// ============================================================================
// SUMMARY
// ============================================================================
echo "╔═══════════════════════════════════════════════════════════════╗" . PHP_EOL;
echo "║  📊 TEST SUMMARY                                             ║" . PHP_EOL;
echo "╚═══════════════════════════════════════════════════════════════╝" . PHP_EOL;
echo PHP_EOL;

$total = $passed + $failed;
$percentage = $total > 0 ? round(($passed / $total) * 100) : 0;

echo "Tests Passed: {$passed}/{$total} ({$percentage}%)" . PHP_EOL;
echo "Tests Failed: {$failed}/{$total}" . PHP_EOL;
echo PHP_EOL;

if ($failed === 0) {
    echo "✅ ALL TESTS PASSED - Fixes deployed successfully!" . PHP_EOL;
    echo PHP_EOL;
    echo "🎯 Ready for live test call with:" . PHP_EOL;
    echo "   - Same-day booking (1h notice)" . PHP_EOL;
    echo "   - Error communication (missing phone)" . PHP_EOL;
    echo "   - Correct alternative day messages" . PHP_EOL;
    echo "   - Optimized token usage" . PHP_EOL;
    exit(0);
} else {
    echo "❌ SOME TESTS FAILED - Review fixes above" . PHP_EOL;
    exit(1);
}
