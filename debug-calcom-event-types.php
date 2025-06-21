<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Services\CalcomV2Service;
use App\Models\Company;

echo "\n" . str_repeat('=', 60) . "\n";
echo "DEBUG CAL.COM EVENT TYPES\n";
echo str_repeat('=', 60) . "\n\n";

// Get company and API key
$company = Company::find(1);
if (!$company) {
    die("Company not found\n");
}

$apiKey = $company->calcom_api_key ? decrypt($company->calcom_api_key) : config('services.calcom.api_key');
echo "Using API key: " . substr($apiKey, 0, 20) . "...\n\n";

// Create service
$calcomService = new CalcomV2Service($apiKey);

// Get event types
echo "Fetching event types...\n\n";

try {
    $result = $calcomService->getEventTypes();
    
    if ($result['success'] ?? false) {
        echo "Found " . count($result['data']['event_types'] ?? []) . " event types:\n\n";
        
        foreach ($result['data']['event_types'] ?? [] as $eventType) {
            echo "ID: " . $eventType['id'] . "\n";
            echo "Title: " . $eventType['title'] . "\n";
            echo "Slug: " . $eventType['slug'] . "\n";
            echo "Length: " . $eventType['length'] . " minutes\n";
            echo "Active: " . ($eventType['hidden'] ? 'No' : 'Yes') . "\n";
            echo str_repeat('-', 40) . "\n";
        }
    } else {
        echo "Failed to fetch event types\n";
        print_r($result);
    }
    
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat('=', 60) . "\n";