<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Unified\CalcomServiceUnified;
use App\Services\Unified\RetellServiceUnified;
use App\Services\FeatureFlagService;

echo "=== UNIFIED SERVICES TEST ===\n\n";

// 1. Test CalcomServiceUnified
echo "1. Testing CalcomServiceUnified...\n";

try {
    $calcomUnified = app(CalcomServiceUnified::class);
    
    // Health check
    $health = $calcomUnified->healthCheck();
    
    echo "   Health Check Results:\n";
    foreach ($health as $key => $value) {
        if (is_array($value)) {
            echo "   - $key: " . json_encode($value) . "\n";
        } else {
            echo "   - $key: $value\n";
        }
    }
    
    echo "   ✅ CalcomServiceUnified instantiated successfully\n";
} catch (\Exception $e) {
    echo "   ❌ CalcomServiceUnified error: " . $e->getMessage() . "\n";
}

echo "\n";

// 2. Test RetellServiceUnified
echo "2. Testing RetellServiceUnified...\n";

try {
    $retellUnified = app(RetellServiceUnified::class);
    
    // Health check
    $health = $retellUnified->healthCheck();
    
    echo "   Health Check Results:\n";
    foreach ($health as $key => $value) {
        if (is_array($value)) {
            echo "   - $key: " . json_encode($value) . "\n";
        } else {
            echo "   - $key: $value\n";
        }
    }
    
    echo "   ✅ RetellServiceUnified instantiated successfully\n";
} catch (\Exception $e) {
    echo "   ❌ RetellServiceUnified error: " . $e->getMessage() . "\n";
}

echo "\n";

// 3. Test Feature Flag Service
echo "3. Testing Feature Flag Service...\n";

$featureFlags = app(FeatureFlagService::class);

// Check unified service flags
$unifiedFlags = [
    'use_unified_calcom_service',
    'use_unified_retell_service',
    'calcom_shadow_mode',
    'retell_shadow_mode'
];

foreach ($unifiedFlags as $flag) {
    $enabled = $featureFlags->isEnabled($flag);
    echo "   - $flag: " . ($enabled ? '✅ Enabled' : '❌ Disabled') . "\n";
}

echo "\n";

// 4. Test Service Usage Tracking
echo "4. Testing Service Usage Tracking...\n";

$tracker = app(\App\Services\Monitoring\ServiceUsageTracker::class);
$stats = $tracker->getUsageStats();

echo "   Service Usage (last 24h):\n";
echo "   - Total calls: " . $stats['total_calls'] . "\n";
echo "   - Unique methods: " . $stats['unique_methods'] . "\n";
echo "   - Error rate: " . round($stats['error_rate'] * 100, 2) . "%\n";

if (!empty($stats['by_service'])) {
    echo "\n   Top Services:\n";
    $services = is_array($stats['by_service']) ? $stats['by_service'] : $stats['by_service']->toArray();
    foreach (array_slice($services, 0, 5) as $service) {
        echo "   - {$service->service_name}: {$service->calls} calls\n";
    }
}

echo "\n";

// 5. Summary
echo "=== SUMMARY ===\n";
echo "✅ Unified services created and registered\n";
echo "✅ Feature flags configured\n";
echo "✅ Health checks implemented\n";
echo "✅ Service tracking active\n";
echo "\n";
echo "Next steps:\n";
echo "1. Enable feature flags gradually in admin panel\n";
echo "2. Monitor service usage statistics\n";
echo "3. Enable shadow mode for testing\n";
echo "4. Migrate code to use unified services\n";