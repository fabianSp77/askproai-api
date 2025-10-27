<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Retell\AppointmentCreationService;
use Illuminate\Support\Facades\DB;

echo "\n═══════════════════════════════════════════════════════════\n";
echo "🧪 VERIFYING BUG #9 FIX - Service Selection\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Get all services for Company 1 (Friseur1)
echo "📋 Available Services for Friseur1:\n";
$services = DB::table('services')
    ->where('company_id', 1)
    ->where('is_active', true)
    ->whereNotNull('calcom_event_type_id')
    ->orderBy('name')
    ->get(['id', 'name', 'priority', 'is_default']);

foreach ($services as $service) {
    echo sprintf("   ID %2d: %-30s (Priority: %d, Default: %s)\n",
        $service->id,
        $service->name,
        $service->priority,
        $service->is_default ? 'YES' : 'NO'
    );
}

echo "\n🔬 Testing Service Selection:\n\n";

$appointmentService = app(AppointmentCreationService::class);

$testCases = [
    ['service' => 'Herrenhaarschnitt', 'expected_id' => 42, 'expected_name' => 'Herrenhaarschnitt'],
    ['service' => 'Damenhaarschnitt', 'expected_id' => 41, 'expected_name' => 'Damenhaarschnitt'],
    ['service' => 'Kinderhaarschnitt', 'expected_id' => 167, 'expected_name' => 'Kinderhaarschnitt'],
    ['service' => 'herrenhaarschnitt', 'expected_id' => 42, 'expected_name' => 'Herrenhaarschnitt'], // Case insensitive
    ['service' => 'Herren Haarschnitt', 'expected_id' => 42, 'expected_name' => 'Herrenhaarschnitt'], // Fuzzy match
    ['service' => 'Waschen & Styling', 'expected_id' => 169, 'expected_name' => 'Waschen & Styling'],
];

$passed = 0;
$failed = 0;

foreach ($testCases as $test) {
    $bookingDetails = ['service' => $test['service']];

    echo "Test: \"{$test['service']}\"\n";

    $result = $appointmentService->findService($bookingDetails, 1, null);

    if ($result) {
        $success = ($result->id === $test['expected_id'] && $result->name === $test['expected_name']);

        if ($success) {
            echo "   ✅ PASS - Matched to: ID {$result->id} \"{$result->name}\"\n";
            $passed++;
        } else {
            echo "   ❌ FAIL - Got ID {$result->id} \"{$result->name}\", expected ID {$test['expected_id']} \"{$test['expected_name']}\"\n";
            $failed++;
        }
    } else {
        echo "   ❌ FAIL - No service found\n";
        $failed++;
    }
    echo "\n";
}

echo "═══════════════════════════════════════════════════════════\n";
echo "📊 RESULTS\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "Passed: $passed / " . count($testCases) . "\n";
echo "Failed: $failed / " . count($testCases) . "\n";

if ($failed === 0) {
    echo "\n✅ ALL TESTS PASSED - Bug #9 is FIXED!\n\n";
    echo "🧪 Next Step: Test with real call:\n";
    echo "   1. Call Retell number\n";
    echo "   2. Say: 'Ich möchte einen Herrenhaarschnitt'\n";
    echo "   3. Check logs for: '✅ Service matched successfully'\n";
    echo "   4. Verify service_id = 42 (not 41)\n\n";
} else {
    echo "\n❌ TESTS FAILED - Fix needs adjustment\n\n";
}
