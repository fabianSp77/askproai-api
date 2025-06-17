<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Company;
use App\Models\CalcomEventType;
use App\Services\CalcomV2Service;

$company = Company::where('name', 'AskProAI')->first();

if (!$company) {
    echo "Company not found!\n";
    exit;
}

echo "=== SYNCING CAL.COM EVENT TYPES ===\n";
echo "Company: {$company->name}\n";
echo "API Key: " . ($company->calcom_api_key ? 'SET' : 'NOT SET') . "\n\n";

if (!$company->calcom_api_key) {
    echo "ERROR: No Cal.com API key set!\n";
    exit;
}

try {
    // Initialize Cal.com service with API key
    $apiKey = $company->calcom_api_key;
    try {
        // Try to decrypt if encrypted
        $apiKey = decrypt($apiKey);
    } catch (\Exception $e) {
        // Not encrypted, use as is
    }
    $calcomService = new CalcomV2Service($apiKey);
    
    // Get event types from Cal.com
    echo "Fetching event types from Cal.com...\n";
    $eventTypes = $calcomService->getEventTypes();
    
    if (empty($eventTypes)) {
        echo "No event types found in Cal.com!\n";
        exit;
    }
    
    echo "\nFound " . count($eventTypes) . " event types:\n";
    
    foreach ($eventTypes as $eventType) {
        echo "\n--- Event Type ---\n";
        echo "ID: " . $eventType['id'] . "\n";
        echo "Title: " . $eventType['title'] . "\n";
        echo "Slug: " . $eventType['slug'] . "\n";
        echo "Duration: " . $eventType['length'] . " minutes\n";
        
        // Update or create in database
        $dbEventType = CalcomEventType::updateOrCreate(
            [
                'company_id' => $company->id,
                'name' => $eventType['title'],
            ],
            [
                'calcom_id' => $eventType['id'],
                'slug' => $eventType['slug'],
                'duration' => $eventType['length'],
                'is_active' => true,
                'metadata' => $eventType,
            ]
        );
        
        echo "✓ Synced to database (DB ID: {$dbEventType->id})\n";
    }
    
    echo "\n=== SYNC COMPLETE ===\n";
    
    // Verify
    $count = CalcomEventType::where('company_id', $company->id)
        ->whereNotNull('calcom_id')
        ->count();
    echo "Total event types in database with Cal.com ID: {$count}\n";
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}