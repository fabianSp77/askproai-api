<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\ImprovedEventTypeNameParser;
use App\Models\Branch;
use App\Models\Company;

// Create mock objects
$company = new Company();
$company->id = 1;
$company->name = 'AskProAI';

$branch = new Branch();
$branch->id = 1;
$branch->name = 'Berlin';
$branch->company = $company;

$parser = new ImprovedEventTypeNameParser();

// Test cases with various marketing-style names
$testCases = [
    "AskProAI + aus Berlin + Beratung + 30% mehr Umsatz für Sie und besten Kundenservice 24/7",
    "30 Minuten Termin mit Fabian Spitzer",
    "Erstberatung - kostenlos und unverbindlich!",
    "Premium Service Paket - 2 Stunden intensive Beratung",
    "Quick Call - 15min",
    "AskProAI Vollservice - Alles aus einer Hand für Ihren Erfolg",
    "Beratungsgespräch für neue Kunden",
];

echo "=== Improved Event Type Name Parser Test ===\n\n";

foreach ($testCases as $i => $originalName) {
    echo "Test Case " . ($i + 1) . ":\n";
    echo "Original: {$originalName}\n";
    
    // Extract clean service name
    $cleanService = $parser->extractCleanServiceName($originalName, $branch, $company);
    echo "Extracted Service: {$cleanService}\n";
    
    // Generate names in different formats
    echo "Generated Names:\n";
    echo "  - Standard: " . $parser->generateEventTypeName($branch, $cleanService, 'standard') . "\n";
    echo "  - Compact: " . $parser->generateEventTypeName($branch, $cleanService, 'compact') . "\n";
    echo "  - Full: " . $parser->generateEventTypeName($branch, $cleanService, 'full') . "\n";
    echo "  - Service First: " . $parser->generateEventTypeName($branch, $cleanService, 'service_first') . "\n";
    
    echo "\n" . str_repeat('-', 60) . "\n\n";
}

// Test the full import analysis
echo "=== Import Analysis Test ===\n\n";

$eventTypes = [
    ['id' => 1, 'title' => "AskProAI + aus Berlin + Beratung + 30% mehr Umsatz für Sie und besten Kundenservice 24/7", 'length' => 30],
    ['id' => 2, 'title' => "Berlin-AskProAI-Erstberatung", 'length' => 60],
    ['id' => 3, 'title' => "München-OtherCompany-Service", 'length' => 45],
];

$analysis = $parser->analyzeEventTypesForImport($eventTypes, $branch);

foreach ($analysis as $i => $result) {
    echo "Event Type " . ($i + 1) . ":\n";
    echo "  Original: " . $result['original']['title'] . "\n";
    echo "  Suggested Name: " . $result['suggested_name'] . "\n";
    echo "  Action: " . $result['suggested_action'] . "\n";
    if ($result['warning']) {
        echo "  Warning: " . $result['warning'] . "\n";
    }
    echo "\n";
}