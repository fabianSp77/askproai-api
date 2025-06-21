<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\EventTypeNameParser;
use App\Models\Branch;
use App\Models\Company;

// Create mock objects for testing
$company = new Company();
$company->id = 1;
$company->name = 'AskProAI';

$branch = new Branch();
$branch->id = 1;
$branch->name = 'Berlin';
$branch->company = $company;

$parser = new EventTypeNameParser();

// Test case 1: Original Cal.com event type name
$originalEventTypeName = "AskProAI + aus Berlin + Beratung + 30% mehr Umsatz für Sie und besten Kundenservice 24/7";

echo "=== Test Case 1: Import Process Simulation ===\n";
echo "Original Cal.com Event Type Name: {$originalEventTypeName}\n\n";

// Step 1: Parse the name (this will fail because it doesn't match the schema)
$parsed = $parser->parseEventTypeName($originalEventTypeName);
echo "Parse Result:\n";
print_r($parsed);

// Step 2: Since parsing failed, the system uses the whole name as service name
echo "\nSince parsing failed, the import wizard would do this:\n";
$suggestedName = $parser->generateEventTypeName($branch, $originalEventTypeName);
echo "Generated Name: {$suggestedName}\n\n";

// Test case 2: What happens if we import this generated name again
echo "=== Test Case 2: Re-import Simulation ===\n";
echo "If someone imports this generated name again:\n";
$parsed2 = $parser->parseEventTypeName($suggestedName);
echo "Parse Result:\n";
print_r($parsed2);

if ($parsed2['success']) {
    echo "\nThis time it parses successfully!\n";
    echo "Branch: {$parsed2['branch_name']}\n";
    echo "Company: {$parsed2['company_name']}\n";
    echo "Service: {$parsed2['service_name']}\n";
    
    // And if we generate again...
    $suggestedName2 = $parser->generateEventTypeName($branch, $parsed2['service_name']);
    echo "\nIf we generate again: {$suggestedName2}\n";
}

// Test case 3: Better approach
echo "\n=== Test Case 3: Better Naming Strategy ===\n";
echo "Original: {$originalEventTypeName}\n\n";

// Extract a cleaner service name
$cleanServiceName = $originalEventTypeName;
// Remove company name if present
$cleanServiceName = str_ireplace(['AskProAI', 'aus Berlin'], '', $cleanServiceName);
// Remove extra symbols and spaces
$cleanServiceName = preg_replace('/\s*\+\s*/', ' ', $cleanServiceName);
$cleanServiceName = trim(preg_replace('/\s+/', ' ', $cleanServiceName));

echo "Extracted Service Name: {$cleanServiceName}\n";
$betterName = $parser->generateEventTypeName($branch, $cleanServiceName);
echo "Better Generated Name: {$betterName}\n";

// Alternative: Use just a short descriptive name
$shortServiceName = "Beratung 30min";
$shortName = $parser->generateEventTypeName($branch, $shortServiceName);
echo "Short Alternative: {$shortName}\n";