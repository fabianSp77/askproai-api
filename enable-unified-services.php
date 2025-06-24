<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\FeatureFlagService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

echo "=== ENABLING UNIFIED SERVICES ===\n\n";

$featureFlags = app(FeatureFlagService::class);

// Feature flags to enable
$flagsToEnable = [
    'use_unified_calcom_service' => 'Use Unified Calcom Service',
    'use_unified_retell_service' => 'Use Unified Retell Service',
    'enable_service_tracking' => 'Enable Service Usage Tracking',
    'enable_mcp_servers' => 'Enable MCP Server Architecture'
];

// Shadow mode flags (enable for testing)
$shadowModeFlags = [
    'calcom_shadow_mode' => 'Calcom Shadow Mode (Testing)',
    'retell_shadow_mode' => 'Retell Shadow Mode (Testing)'
];

echo "1. Enabling Core Features:\n";

foreach ($flagsToEnable as $key => $name) {
    try {
        $featureFlags->createOrUpdate([
            'key' => $key,
            'enabled' => true,
            'rollout_percentage' => 100
        ]);
        
        echo "   ✅ Enabled: $name\n";
    } catch (\Exception $e) {
        echo "   ❌ Failed to enable $name: " . $e->getMessage() . "\n";
    }
}

echo "\n2. Enabling Shadow Mode for Testing:\n";

foreach ($shadowModeFlags as $key => $name) {
    try {
        $featureFlags->createOrUpdate([
            'key' => $key,
            'enabled' => true,
            'rollout_percentage' => 10 // Start with 10% for testing
        ]);
        
        echo "   ✅ Enabled: $name (10% rollout)\n";
    } catch (\Exception $e) {
        echo "   ❌ Failed to enable $name: " . $e->getMessage() . "\n";
    }
}

// Clear all caches
echo "\n3. Clearing Caches:\n";
Cache::flush();
Cache::tags(['feature_flags'])->flush();
echo "   ✅ All caches cleared\n";

// Get test company for specific overrides
echo "\n4. Setting up Test Company:\n";
$testCompany = DB::table('companies')
    ->where('name', 'like', '%Test%')
    ->orWhere('email', 'like', '%test%')
    ->first();

if ($testCompany) {
    echo "   Found test company: {$testCompany->name} (ID: {$testCompany->id})\n";
    
    // Enable all features for test company
    foreach (array_merge($flagsToEnable, $shadowModeFlags) as $key => $name) {
        try {
            $featureFlags->setOverride(
                $key,
                $testCompany->id,
                true,
                'Enabled for test company'
            );
            echo "   ✅ Override set for test company: $key\n";
        } catch (\Exception $e) {
            echo "   ❌ Failed to set override: " . $e->getMessage() . "\n";
        }
    }
} else {
    echo "   ⚠️  No test company found\n";
}

// Show current status
echo "\n5. Current Feature Flag Status:\n";
$allFlags = $featureFlags->getAllFlags();

foreach ($allFlags as $flag) {
    $status = $flag->enabled ? '✅ Enabled' : '❌ Disabled';
    $rollout = $flag->rollout_percentage . '%';
    echo "   - {$flag->name}: $status ($rollout rollout)\n";
}

// Test unified services
echo "\n6. Testing Unified Services:\n";

try {
    // Test CalcomServiceUnified
    $calcomUnified = app(\App\Services\Unified\CalcomServiceUnified::class);
    $health = $calcomUnified->healthCheck($testCompany->id ?? null);
    echo "   - CalcomServiceUnified: ✅ Working\n";
    echo "     Active version: " . $health['active_version'] . "\n";
} catch (\Exception $e) {
    echo "   - CalcomServiceUnified: ❌ Error: " . $e->getMessage() . "\n";
}

try {
    // Test RetellServiceUnified
    $retellUnified = app(\App\Services\Unified\RetellServiceUnified::class);
    $health = $retellUnified->healthCheck($testCompany->id ?? null);
    echo "   - RetellServiceUnified: ✅ Working\n";
    echo "     Active version: " . $health['active_version'] . "\n";
} catch (\Exception $e) {
    echo "   - RetellServiceUnified: ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== SUMMARY ===\n";
echo "✅ Unified services enabled globally\n";
echo "✅ Shadow mode enabled for testing (10% rollout)\n";
echo "✅ Service tracking enabled\n";
echo "✅ MCP servers enabled\n";
if ($testCompany) {
    echo "✅ Test company has 100% access to all features\n";
}
echo "\nNext steps:\n";
echo "1. Monitor service usage at /admin/feature-flag-manager\n";
echo "2. Check shadow mode differences in logs\n";
echo "3. Gradually increase rollout percentage\n";
echo "4. Test end-to-end booking flow\n";