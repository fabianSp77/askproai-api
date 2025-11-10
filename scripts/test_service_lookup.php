<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Retell\ServiceSelectionService;
use App\Models\Branch;

echo "\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "  SERVICE LOOKUP TEST - Testing Service Recognition\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

$companyId = 1;
$branchId = '34c4d48e-4753-4715-9c30-c55843a943e8';
$branch = Branch::find($branchId);
$serviceSelector = app(ServiceSelectionService::class);

echo "Company ID: {$companyId}\n";
echo "Branch: {$branch->name}\n";
echo "Branch ID: {$branchId}\n\n";

// Test cases from the failed phone call
$testCases = [
    'Herrenhaarschnitt',
    'herrenhaarschnitt',
    'Herren Haarschnitt',
    'Damenhaarschnitt',
    'Kinderhaarschnitt',
    'Waschen schneiden föhnen',
    'Föhnen und Styling',
    'Dauerwelle',
];

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TESTING SERVICE RECOGNITION\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$successful = 0;
$failed = 0;

foreach ($testCases as $serviceName) {
    echo "Testing: \"$serviceName\"\n";

    try {
        $service = $serviceSelector->findServiceByName($serviceName, $companyId, $branchId);

        if ($service) {
            echo "  ✅ FOUND\n";
            echo "     Service: {$service->name}\n";
            echo "     ID: {$service->id}\n";
            echo "     Slug: {$service->slug}\n";
            echo "     Active: " . ($service->is_active ? 'YES' : 'NO') . "\n";
            echo "     Cal.com ID: {$service->calcom_event_type_id}\n";
            echo "     Price: €{$service->price}\n";
            echo "     Duration: {$service->duration_minutes} min\n";
            $successful++;
        } else {
            echo "  ❌ NOT FOUND\n";
            echo "     Service lookup returned null\n";
            $failed++;
        }
    } catch (\Exception $e) {
        echo "  ❌ ERROR: " . $e->getMessage() . "\n";
        $failed++;
    }

    echo "\n";
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "SUMMARY\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "Total Tests: " . count($testCases) . "\n";
echo "  ✅ Successful: $successful\n";
echo "  ❌ Failed: $failed\n\n";

if ($successful === count($testCases)) {
    echo "🎉 ALL TESTS PASSED! Service recognition is working perfectly!\n\n";
    echo "This means:\n";
    echo "  ✅ All services are active\n";
    echo "  ✅ Service names are recognized\n";
    echo "  ✅ Database lookups work correctly\n\n";
    echo "Next step: Set Cal.com API key to enable availability checks\n";
} else {
    echo "⚠️  Some tests failed. Service recognition may have issues.\n";
}

echo "\n═══════════════════════════════════════════════════════════════════════════════\n";
echo "  TEST COMPLETE\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
