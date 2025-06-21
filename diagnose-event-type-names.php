<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CalcomEventType;
use App\Services\EventTypeNameParser;
use App\Models\Company;

// Set a company context first
$company = Company::first();
if ($company) {
    app()->instance('current_company_id', $company->id);
}

// Get some example event types with long names
$eventTypes = CalcomEventType::withoutGlobalScopes()
    ->where('name', 'like', '%AskProAI%Berlin%')
    ->orWhere('name', 'like', '%–%')
    ->limit(5)
    ->get();

echo "=== Event Type Name Analysis ===\n\n";

foreach ($eventTypes as $eventType) {
    echo "ID: {$eventType->id}\n";
    echo "Current Name: {$eventType->name}\n";
    echo "Branch ID: {$eventType->branch_id}\n";
    
    // Try to parse the name
    $parser = new EventTypeNameParser();
    $parsed = $parser->parseEventTypeName($eventType->name);
    
    echo "Parsed Result:\n";
    echo "  - Success: " . ($parsed['success'] ? 'Yes' : 'No') . "\n";
    if ($parsed['success']) {
        echo "  - Branch: {$parsed['branch_name']}\n";
        echo "  - Company: {$parsed['company_name']}\n";
        echo "  - Service: {$parsed['service_name']}\n";
    } else {
        echo "  - Error: {$parsed['error']}\n";
    }
    
    // Check if branch exists
    if ($eventType->branch_id) {
        $branch = \App\Models\Branch::find($eventType->branch_id);
        if ($branch) {
            echo "Branch Details:\n";
            echo "  - Name: {$branch->name}\n";
            echo "  - Company: " . ($branch->company ? $branch->company->name : 'N/A') . "\n";
            
            // Generate proper name
            $properName = $parser->generateEventTypeName($branch, $parsed['service_name'] ?? $eventType->name);
            echo "Suggested Name: {$properName}\n";
        }
    }
    
    echo "\n" . str_repeat('-', 80) . "\n\n";
}

// Show how the parser processes a complex name
echo "=== Parser Behavior Test ===\n\n";

$parser = new EventTypeNameParser();

$testName = "AskProAI – Berlin-AskProAI-AskProAI + aus Berlin + Beratung + 30% mehr Umsatz für Sie und besten Kundenservice 24/7";
echo "Test Name: {$testName}\n";

$parsed = $parser->parseEventTypeName($testName);
echo "Parse Result:\n";
print_r($parsed);

// Test with different separators
$testNames = [
    "Berlin-AskProAI-Beratung",
    "AskProAI – Berlin-AskProAI-Service",
    "AskProAI + aus Berlin + Beratung + 30% mehr Umsatz"
];

echo "\n=== Different Separator Tests ===\n";
foreach ($testNames as $name) {
    echo "\nTesting: {$name}\n";
    $result = $parser->parseEventTypeName($name);
    if ($result['success']) {
        echo "  Branch: {$result['branch_name']}\n";
        echo "  Company: {$result['company_name']}\n";
        echo "  Service: {$result['service_name']}\n";
    } else {
        echo "  Failed: {$result['error']}\n";
    }
}