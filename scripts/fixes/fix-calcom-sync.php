#!/usr/bin/env php
<?php
/**
 * Fix Cal.com Event Type Sync Issues
 * 
 * This script fixes Cal.com event type not found errors by:
 * 1. Verifying event type IDs
 * 2. Re-syncing from Cal.com
 * 3. Updating branch mappings
 * 
 * Error Code: CALCOM_001
 */

require_once __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Branch;
use App\Models\CalcomEventType;
use App\Services\CalcomV2Service;

echo "🔧 Cal.com Event Type Sync Fix Script\n";
echo "=====================================\n\n";

try {
    // Step 1: Check branches with Cal.com event types
    echo "1. Checking branches with Cal.com event types...\n";
    
    $branchesWithEventTypes = Branch::whereNotNull('calcom_event_type_id')->get();
    $branchesWithoutEventTypes = Branch::whereNull('calcom_event_type_id')->count();
    
    echo "   Branches with event types: {$branchesWithEventTypes->count()}\n";
    echo "   Branches without event types: {$branchesWithoutEventTypes}\n";

    // Step 2: Verify existing event type mappings
    echo "\n2. Verifying event type mappings...\n";
    $invalidMappings = [];
    
    foreach ($branchesWithEventTypes as $branch) {
        $eventType = CalcomEventType::find($branch->calcom_event_type_id);
        
        if (!$eventType) {
            echo "   ❌ Branch '{$branch->name}' references non-existent event type ID: {$branch->calcom_event_type_id}\n";
            $invalidMappings[] = $branch;
        } else {
            echo "   ✅ Branch '{$branch->name}' → Event Type '{$eventType->title}'\n";
        }
    }

    // Step 3: Sync event types from Cal.com
    echo "\n3. Syncing event types from Cal.com...\n";
    
    try {
        // Use the first branch with API key or default from config
        $branch = Branch::whereNotNull('calcom_api_key')->first();
        if (!$branch) {
            $branch = Branch::first();
            if (!$branch) {
                echo "   ❌ No branches found!\n";
                exit(1);
            }
        }
        
        $service = new CalcomV2Service($branch);
        $eventTypes = $service->getEventTypes();
        
        if (empty($eventTypes)) {
            echo "   ⚠️  No event types returned from Cal.com\n";
            echo "   Please check API key and Cal.com configuration\n";
        } else {
            echo "   ✅ Found " . count($eventTypes) . " event types in Cal.com\n";
            
            // Update local database
            foreach ($eventTypes as $eventTypeData) {
                $eventType = CalcomEventType::updateOrCreate(
                    ['external_id' => $eventTypeData['id']],
                    [
                        'title' => $eventTypeData['title'] ?? 'Unnamed Event',
                        'slug' => $eventTypeData['slug'] ?? '',
                        'description' => $eventTypeData['description'] ?? '',
                        'length' => $eventTypeData['length'] ?? 30,
                        'hidden' => $eventTypeData['hidden'] ?? false,
                        'price' => $eventTypeData['price'] ?? 0,
                        'currency' => $eventTypeData['currency'] ?? 'EUR',
                        'metadata' => $eventTypeData,
                    ]
                );
                
                echo "   - Synced: {$eventType->title} (ID: {$eventType->external_id})\n";
            }
        }
    } catch (\Exception $e) {
        echo "   ❌ Failed to sync from Cal.com: " . $e->getMessage() . "\n";
        echo "   Please check API credentials and network connection\n";
    }

    // Step 4: Fix invalid mappings
    if (!empty($invalidMappings)) {
        echo "\n4. Fixing invalid event type mappings...\n";
        
        $availableEventTypes = CalcomEventType::all();
        
        if ($availableEventTypes->isEmpty()) {
            echo "   ❌ No event types available to assign\n";
        } else {
            echo "   Available event types:\n";
            foreach ($availableEventTypes as $index => $eventType) {
                echo "   [{$index}] {$eventType->title} (ID: {$eventType->id}, External: {$eventType->external_id})\n";
            }
            
            foreach ($invalidMappings as $branch) {
                echo "\n   Branch '{$branch->name}' needs a valid event type.\n";
                echo "   Enter number to assign event type (or 'skip'): ";
                
                $handle = fopen("php://stdin", "r");
                $line = trim(fgets($handle));
                fclose($handle);
                
                if ($line === 'skip') {
                    echo "   Skipped.\n";
                    continue;
                }
                
                $index = intval($line);
                if (isset($availableEventTypes[$index])) {
                    $branch->calcom_event_type_id = $availableEventTypes[$index]->id;
                    $branch->save();
                    echo "   ✅ Assigned '{$availableEventTypes[$index]->title}' to branch '{$branch->name}'\n";
                } else {
                    echo "   ⚠️  Invalid selection, skipping.\n";
                }
            }
        }
    }

    // Step 5: Create default event type for branches without one
    echo "\n5. Checking branches without event types...\n";
    
    $branchesWithoutEventTypes = Branch::whereNull('calcom_event_type_id')->get();
    
    if ($branchesWithoutEventTypes->isNotEmpty()) {
        echo "   Found {$branchesWithoutEventTypes->count()} branches without event types\n";
        
        // Find or suggest a default event type
        $defaultEventType = CalcomEventType::where('title', 'LIKE', '%Standard%')
                                          ->orWhere('title', 'LIKE', '%Default%')
                                          ->orWhere('title', 'LIKE', '%Termin%')
                                          ->first();
        
        if ($defaultEventType) {
            echo "   Suggested default: {$defaultEventType->title}\n";
            echo "   Assign to all branches without event type? (yes/no): ";
            
            $handle = fopen("php://stdin", "r");
            $line = trim(fgets($handle));
            fclose($handle);
            
            if ($line === 'yes') {
                foreach ($branchesWithoutEventTypes as $branch) {
                    $branch->calcom_event_type_id = $defaultEventType->id;
                    $branch->save();
                }
                echo "   ✅ Assigned default event type to {$branchesWithoutEventTypes->count()} branches\n";
            }
        }
    }

    // Step 6: Test Cal.com connection
    echo "\n6. Testing Cal.com API connection...\n";
    
    $testBranch = Branch::whereNotNull('calcom_event_type_id')->first();
    if ($testBranch) {
        try {
            $service = new CalcomV2Service($testBranch);
            $testEventType = $service->getEventType($testBranch->calcom_event_type_id);
            
            if ($testEventType) {
                echo "   ✅ Successfully retrieved event type from Cal.com\n";
                echo "   - Title: " . ($testEventType['title'] ?? 'N/A') . "\n";
                echo "   - Length: " . ($testEventType['length'] ?? 'N/A') . " minutes\n";
            } else {
                echo "   ❌ Could not retrieve event type from Cal.com\n";
            }
        } catch (\Exception $e) {
            echo "   ❌ API test failed: " . $e->getMessage() . "\n";
        }
    }

    // Summary
    echo "\n✅ Cal.com sync fix completed!\n";
    echo "   - Total event types: " . CalcomEventType::count() . "\n";
    echo "   - Branches with valid mappings: " . Branch::whereNotNull('calcom_event_type_id')->count() . "\n";
    echo "   - Fixed invalid mappings: " . count($invalidMappings) . "\n";
    
    echo "\nNext steps:\n";
    echo "1. Verify Cal.com API key is set correctly\n";
    echo "2. Run 'php artisan calcom:sync-event-types' to sync regularly\n";
    echo "3. Check branch settings in admin panel\n";
    
    exit(0);

} catch (\Exception $e) {
    echo "\n❌ Error during fix process: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}