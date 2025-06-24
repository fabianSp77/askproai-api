<?php

use App\Models\Branch;
use App\Models\CalcomEventType;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=================================\n";
echo "Fixing Invalid Branch Event Types\n";
echo "=================================\n\n";

// Find branches with invalid calcom_event_type_id
$branches = Branch::withoutGlobalScopes()->whereNotNull('calcom_event_type_id')->get();

foreach ($branches as $branch) {
    echo "Checking branch: {$branch->name} (ID: {$branch->id})\n";
    echo "  Current calcom_event_type_id: {$branch->calcom_event_type_id}\n";
    
    // Check if the event type exists
    $eventTypeExists = CalcomEventType::withoutGlobalScopes()
        ->where('id', $branch->calcom_event_type_id)
        ->exists();
    
    if (!$eventTypeExists) {
        echo "  ❌ Event type ID {$branch->calcom_event_type_id} does not exist!\n";
        
        // Find an existing event type for this branch
        $existingEventType = CalcomEventType::withoutGlobalScopes()
            ->where('branch_id', $branch->id)
            ->first();
        
        if ($existingEventType) {
            $branch->calcom_event_type_id = $existingEventType->id;
            $branch->save();
            echo "  ✅ Updated to use: {$existingEventType->name} (ID: {$existingEventType->id})\n";
        } else {
            // No event types for this branch, clear the invalid reference
            $branch->calcom_event_type_id = null;
            $branch->save();
            echo "  ⚠️  No event types found for branch, cleared invalid reference\n";
        }
    } else {
        echo "  ✅ Event type exists\n";
    }
    echo "\n";
}

echo "Fix completed!\n";