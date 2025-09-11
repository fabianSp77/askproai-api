#!/usr/bin/env php
<?php

/**
 * Complete Cal.com V2 Migration Script
 * 
 * This script completes the migration of remaining V1 components to V2
 * Currently: event_types is still on V1
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Services\CalcomMigrationService;
use App\Services\CalcomV2Service;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n╔════════════════════════════════════════════════════╗\n";
echo "║     Cal.com V2 Migration Completion Script        ║\n";
echo "╚════════════════════════════════════════════════════╝\n\n";

try {
    $migrationService = app(CalcomMigrationService::class);
    $v2Service = app(CalcomV2Service::class);
    
    // Get current migration status
    $report = $migrationService->getMigrationReport();
    
    echo "📊 Current Migration Status:\n";
    echo "├─ Overall Progress: " . $report['overall_progress'] . "%\n";
    echo "├─ Days Until V1 Deprecation: " . $report['days_until_deprecation'] . "\n";
    echo "└─ Components Status:\n";
    
    foreach ($report['api_status'] as $feature => $status) {
        $icon = match($status) {
            'v2_ready' => '✅',
            'hybrid' => '🔄',
            'v1_only' => '❌',
            default => '❓'
        };
        echo "   ├─ {$icon} {$feature}: {$status}\n";
    }
    
    echo "\n🔄 Starting Event Types Migration...\n";
    
    // Step 1: Test V2 Event Types endpoint
    echo "├─ Testing V2 Event Types API...\n";
    
    try {
        // First, let's check if we can access V2 event types
        $testEventTypes = $v2Service->getEventTypes();
        echo "   ✅ V2 Event Types API is accessible\n";
        echo "   └─ Found " . count($testEventTypes) . " event types\n";
        
        // Step 2: Verify V2 functionality
        echo "├─ Verifying V2 Event Types functionality...\n";
        
        if (!empty($testEventTypes)) {
            $firstEventType = $testEventTypes[0];
            $eventTypeId = $firstEventType['id'] ?? null;
            
            if ($eventTypeId) {
                // Test getting a single event type
                $singleEventType = $v2Service->getEventType($eventTypeId);
                echo "   ✅ V2 Get Single Event Type: Working\n";
                
                // Test event type availability
                try {
                    $availability = $v2Service->getEventTypeAvailability($eventTypeId);
                    echo "   ✅ V2 Event Type Availability: Working\n";
                } catch (\Exception $e) {
                    echo "   ⚠️  V2 Event Type Availability: " . $e->getMessage() . "\n";
                }
            }
        }
        
        // Step 3: Migrate Event Types to V2
        echo "├─ Migrating Event Types to V2...\n";
        
        $migrationResult = $migrationService->migrateFeature('event_types');
        
        if ($migrationResult) {
            echo "   ✅ Event Types successfully migrated to V2!\n";
            
            // Update configuration
            echo "├─ Updating configuration...\n";
            
            // Update the CalcomMigrationService to reflect the new status
            $configPath = base_path('app/Services/CalcomMigrationService.php');
            $content = file_get_contents($configPath);
            
            // Update migration status
            $content = str_replace(
                "'event_types' => 'v1_only',",
                "'event_types' => 'v2_ready',",
                $content
            );
            
            // Update feature flag
            $content = str_replace(
                "'use_v2_event_types' => false,",
                "'use_v2_event_types' => true,",
                $content
            );
            
            file_put_contents($configPath, $content);
            echo "   ✅ Configuration updated\n";
            
            // Clear cache to ensure new settings take effect
            echo "├─ Clearing cache...\n";
            \Illuminate\Support\Facades\Artisan::call('cache:clear');
            echo "   ✅ Cache cleared\n";
            
        } else {
            echo "   ❌ Failed to migrate Event Types\n";
            echo "   └─ Check logs for detailed error information\n";
        }
        
    } catch (\Exception $e) {
        echo "   ❌ V2 Event Types API test failed: " . $e->getMessage() . "\n";
        echo "   └─ Event Types will remain on V1 for now\n";
    }
    
    echo "\n📊 Final Migration Status:\n";
    
    // Get updated report
    $finalReport = $migrationService->getMigrationReport();
    
    echo "├─ Overall Progress: " . $finalReport['overall_progress'] . "%\n";
    echo "└─ Components:\n";
    
    foreach ($finalReport['api_status'] as $feature => $status) {
        $icon = match($status) {
            'v2_ready' => '✅',
            'hybrid' => '🔄',
            'v1_only' => '❌',
            default => '❓'
        };
        echo "   ├─ {$icon} {$feature}: {$status}\n";
    }
    
    // Generate recommendations
    echo "\n📝 Recommendations:\n";
    
    if ($finalReport['overall_progress'] == 100) {
        echo "✅ All components successfully migrated to V2!\n";
        echo "   ├─ V1 API can be safely deprecated\n";
        echo "   ├─ Consider removing V1 fallback logic after stability period\n";
        echo "   └─ Monitor V2 performance and error rates\n";
    } else {
        $remaining = 100 - $finalReport['overall_progress'];
        echo "⚠️  {$remaining}% of components still need migration\n";
        echo "   ├─ Continue monitoring V1 deprecation timeline\n";
        echo "   ├─ Test remaining V1 components for V2 compatibility\n";
        echo "   └─ Maintain fallback mechanisms until fully migrated\n";
    }
    
    echo "\n✅ Migration script completed successfully!\n\n";
    
} catch (\Exception $e) {
    echo "\n❌ Migration Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}