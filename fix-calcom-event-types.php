<?php

// Fix Cal.com Event Type configuration

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Models\Branch;

$apiKey = 'cal_live_bd7aedbdf12085c5312c79ba73585920';

echo "=== Cal.com Event Type Configuration Fix ===\n\n";

// 1. Get all event types
echo "1. Fetching all Cal.com Event Types...\n";
$ch = curl_init("https://api.cal.com/v1/event-types?apiKey=$apiKey");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    $eventTypes = $data['event_types'] ?? [];
    echo "✅ Found " . count($eventTypes) . " event types:\n\n";
    
    foreach ($eventTypes as $et) {
        echo "ID: " . $et['id'] . "\n";
        echo "  - Title: " . $et['title'] . "\n";
        echo "  - Slug: " . $et['slug'] . "\n";
        echo "  - Length: " . $et['length'] . " minutes\n";
        echo "  - Active: " . (!$et['hidden'] ? 'Yes' : 'No') . "\n";
        echo "  - Team: " . ($et['team']['slug'] ?? 'personal') . "\n";
        echo "\n";
    }
    
    // 2. Check current branch configuration
    echo "\n2. Current Branch Configuration:\n";
    $branches = \DB::select("
        SELECT b.id, b.name, b.calcom_event_type_id, c.name as company_name
        FROM branches b
        JOIN companies c ON b.company_id = c.id
        WHERE b.is_active = 1
        AND b.calcom_event_type_id IS NOT NULL
        LIMIT 10
    ");
    
    foreach ($branches as $branch) {
        echo "Branch: " . $branch->name . " (Company: " . $branch->company_name . ")\n";
        echo "  - Current Event Type ID: " . $branch->calcom_event_type_id . "\n";
        
        // Check if this ID exists
        $exists = false;
        foreach ($eventTypes as $et) {
            if ($et['id'] == $branch->calcom_event_type_id) {
                $exists = true;
                echo "  - ✅ Event Type exists: " . $et['title'] . "\n";
                break;
            }
        }
        
        if (!$exists) {
            echo "  - ❌ Event Type ID " . $branch->calcom_event_type_id . " NOT FOUND!\n";
            
            // Suggest first available event type
            if (count($eventTypes) > 0) {
                $suggestion = $eventTypes[0];
                echo "  - 💡 Suggestion: Use ID " . $suggestion['id'] . " (" . $suggestion['title'] . ")\n";
            }
        }
        echo "\n";
    }
    
    // 3. Fix configuration
    echo "\n3. Fixing Configuration...\n";
    if (count($eventTypes) > 0) {
        $defaultEventType = $eventTypes[0];
        echo "Using default event type: " . $defaultEventType['id'] . " - " . $defaultEventType['title'] . "\n";
        
        // Update AskProAI Berlin branch
        $askproaiBerlin = '7362c5a9-7d2b-46cd-9bcb-d69f6a60c73b';
        \DB::statement("UPDATE branches SET calcom_event_type_id = ? WHERE id = ?", [
            $defaultEventType['id'],
            $askproaiBerlin
        ]);
        
        echo "✅ Updated AskProAI Berlin branch with Event Type ID: " . $defaultEventType['id'] . "\n";
    }
    
} else {
    echo "❌ Failed to get event types: HTTP $httpCode\n";
}