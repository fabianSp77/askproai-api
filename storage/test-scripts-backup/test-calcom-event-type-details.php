<?php
/**
 * Test script to check Cal.com event type details
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\CalcomV2Service;
use App\Models\Company;

echo "=== Cal.com Event Type Details Test ===\n\n";

try {
    // Get the company
    $companyId = 1;
    $company = Company::find($companyId);
    
    if (!$company) {
        die("Company not found!\n");
    }
    
    echo "Company: {$company->name}\n";
    
    // Check API key
    if (!$company->calcom_api_key) {
        die("No Cal.com API key configured!\n");
    }
    
    $apiKey = decrypt($company->calcom_api_key);
    echo "API Key: " . substr($apiKey, 0, 20) . "...\n\n";
    
    // Initialize Cal.com service
    $calcomService = new CalcomV2Service($apiKey);
    
    // First, let's get all event types
    echo "--- Fetching All Event Types ---\n";
    $eventTypesResponse = $calcomService->getEventTypes();
    
    if ($eventTypesResponse && isset($eventTypesResponse['event_types'])) {
        $eventTypes = $eventTypesResponse['event_types'];
        echo "Found " . count($eventTypes) . " event types\n\n";
        
        foreach ($eventTypes as $et) {
            echo "Event Type: {$et['title']} (ID: {$et['id']})\n";
            echo "  Slug: {$et['slug']}\n";
            echo "  Length: {$et['length']} minutes\n";
            
            // Check for hosts/users
            if (isset($et['hosts'])) {
                echo "  Hosts: " . count($et['hosts']) . "\n";
                foreach ($et['hosts'] as $host) {
                    echo "    - " . ($host['name'] ?? $host['username'] ?? 'Unknown') . " (" . ($host['email'] ?? 'No email') . ")\n";
                }
            } elseif (isset($et['users'])) {
                echo "  Users: " . count($et['users']) . "\n";
                foreach ($et['users'] as $user) {
                    echo "    - " . ($user['name'] ?? $user['username'] ?? 'Unknown') . " (" . ($user['email'] ?? 'No email') . ")\n";
                }
            } else {
                echo "  No hosts/users information\n";
            }
            
            echo "\n";
        }
    } else {
        echo "Failed to fetch event types\n";
    }
    
    // Try to get specific event type details
    $eventTypeId = 2563193;
    echo "\n--- Fetching Details for Event Type ID: {$eventTypeId} ---\n";
    
    $detailsResponse = $calcomService->getEventTypeDetails($eventTypeId);
    
    if ($detailsResponse['success']) {
        echo "✓ Successfully fetched details\n";
        $details = $detailsResponse['data'];
        
        echo "Title: " . ($details['title'] ?? 'N/A') . "\n";
        echo "Structure:\n";
        
        // Show the structure of the response
        foreach ($details as $key => $value) {
            if (is_array($value)) {
                echo "  {$key}: [Array with " . count($value) . " items]\n";
                if (in_array($key, ['hosts', 'users']) && !empty($value)) {
                    foreach ($value as $item) {
                        if (is_array($item)) {
                            echo "    - " . ($item['name'] ?? $item['username'] ?? 'Unknown') . "\n";
                        }
                    }
                }
            } else {
                echo "  {$key}: " . (is_string($value) ? $value : json_encode($value)) . "\n";
            }
        }
    } else {
        echo "✗ Failed to fetch details: " . ($detailsResponse['error'] ?? 'Unknown error') . "\n";
        
        // Try to show what the raw response looks like
        echo "\nRaw response:\n";
        print_r($detailsResponse);
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";