<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Query directly without models to avoid tenant scope
$eventTypes = DB::table('calcom_event_types')
    ->select('id', 'name', 'branch_id', 'company_id', 'created_at', 'metadata')
    ->where('name', 'like', '%AskProAI%Berlin%')
    ->orWhere('name', 'like', '%–%')
    ->limit(10)
    ->get();

echo "=== CalCom Event Types in Database ===\n\n";
echo "Found " . count($eventTypes) . " event types\n\n";

foreach ($eventTypes as $eventType) {
    echo "ID: {$eventType->id}\n";
    echo "Name: {$eventType->name}\n";
    echo "Branch ID: {$eventType->branch_id}\n";
    echo "Company ID: {$eventType->company_id}\n";
    echo "Created: {$eventType->created_at}\n";
    
    if ($eventType->metadata) {
        $metadata = json_decode($eventType->metadata, true);
        if (isset($metadata['original_name'])) {
            echo "Original Name: {$metadata['original_name']}\n";
        }
    }
    
    // Check branch details
    if ($eventType->branch_id) {
        $branch = DB::table('branches')
            ->where('id', $eventType->branch_id)
            ->first();
        
        if ($branch) {
            echo "Branch Name: {$branch->name}\n";
            
            $company = DB::table('companies')
                ->where('id', $branch->company_id)
                ->first();
                
            if ($company) {
                echo "Company Name: {$company->name}\n";
            }
        }
    }
    
    echo "\n" . str_repeat('-', 80) . "\n\n";
}

// Look for the specific problematic example
echo "=== Looking for Specific Example ===\n";
$specific = DB::table('calcom_event_types')
    ->where('name', 'like', '%AskProAI – Berlin-AskProAI-AskProAI + aus Berlin%')
    ->first();

if ($specific) {
    echo "Found problematic event type:\n";
    echo "ID: {$specific->id}\n";
    echo "Full Name: {$specific->name}\n";
    echo "Name Length: " . strlen($specific->name) . " characters\n";
    
    // Analyze the name structure
    echo "\nAnalyzing name structure:\n";
    $parts = explode('-', $specific->name);
    foreach ($parts as $i => $part) {
        echo "Part {$i}: '{$part}'\n";
    }
}