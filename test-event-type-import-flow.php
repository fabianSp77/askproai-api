<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\EventTypeNameParser;
use App\Services\SmartEventTypeNameParser;
use App\Models\Branch;
use App\Models\Company;

// Test Suite for Event Type Import Flow
echo "=== Event Type Import Flow Analysis ===\n\n";

// Test 1: EventTypeNameParser functionality
echo "Test 1: EventTypeNameParser\n";
echo "----------------------------\n";

$parser = new EventTypeNameParser();

// Test parsing
$testNames = [
    'Berlin Mitte-Test Company-Consultation 30min',
    'Frankfurt-ABC Corp-Hair Styling',
    'Just a service name',
    'Two-Parts-Only',
    ''
];

foreach ($testNames as $name) {
    $result = $parser->parseEventTypeName($name);
    echo "Input: '$name'\n";
    echo "Success: " . ($result['success'] ? 'YES' : 'NO') . "\n";
    if ($result['success']) {
        echo "  Branch: {$result['branch_name']}\n";
        echo "  Company: {$result['company_name']}\n";
        echo "  Service: {$result['service_name']}\n";
    } else {
        echo "  Error: {$result['error']}\n";
    }
    echo "\n";
}

// Test service extraction
echo "\nTest: Service Name Extraction\n";
$marketingNames = [
    'AskProAI + 30% mehr Umsatz + Beratung',
    '24/7 besten Kundenservice + Haarschnitt',
    'Massage + aus Berlin',
    'Test + + Service',
    'This is a very long service name that should be truncated to prevent database issues and display problems'
];

foreach ($marketingNames as $name) {
    $extracted = $parser->extractServiceName($name);
    echo "Input: '$name'\n";
    echo "Extracted: '$extracted'\n\n";
}

// Test 2: SmartEventTypeNameParser
echo "\nTest 2: SmartEventTypeNameParser\n";
echo "---------------------------------\n";

$smartParser = new SmartEventTypeNameParser();

$smartTestCases = [
    'AskProAI + 30% mehr Umsatz + Haarschnitt aus Berlin',
    'ModernHair - Färben und Styling 24/7',
    'FitXpert Frankfurt - 60 Min Personal Training',
    'Test Demo Event',
    'Erstberatung für Sie und besten Kundenservice',
    '30 Minuten Beratung',
    ''
];

foreach ($smartTestCases as $name) {
    $extracted = $smartParser->extractCleanServiceName($name);
    echo "Input: '$name'\n";
    echo "Extracted: '$extracted'\n\n";
}

// Test 3: Branch matching logic
echo "\nTest 3: Branch Matching\n";
echo "------------------------\n";

// Create mock branch object
$company = new Company();
$company->name = 'Test Company';
$company->id = 1;

$branch = new Branch();
$branch->name = 'Berlin Mitte';
$branch->company_id = 1;
$branch->setRelation('company', $company);

$branchTests = [
    'Berlin Mitte' => true,
    'berlin mitte' => true,
    'Berlin' => true,
    'Mitte' => true,
    'Frankfurt' => false,
    'Berlin Mite' => true, // Typo but similar
];

foreach ($branchTests as $testName => $expected) {
    $result = $parser->validateBranchMatch($testName, $branch);
    echo "Testing '$testName' against 'Berlin Mitte': ";
    echo $result ? 'MATCH' : 'NO MATCH';
    echo " (Expected: " . ($expected ? 'MATCH' : 'NO MATCH') . ")";
    echo $result === $expected ? " ✓" : " ✗";
    echo "\n";
}

// Test 4: Event type analysis for import
echo "\nTest 4: Event Type Analysis\n";
echo "----------------------------\n";

$eventTypes = [
    ['id' => 1, 'title' => 'Berlin Mitte-Test Company-Service A', 'active' => true],
    ['id' => 2, 'title' => 'Frankfurt-Test Company-Service B', 'active' => true],
    ['id' => 3, 'title' => 'Invalid Format Service', 'active' => true],
    ['id' => 4, 'title' => 'Test Demo Event', 'active' => true],
    ['id' => 5, 'title' => 'Berlin Mitte-Test Company-Inactive', 'active' => false],
];

$results = $parser->analyzeEventTypesForImport($eventTypes, $branch);

foreach ($results as $i => $result) {
    echo "\nEvent Type " . ($i + 1) . ": '{$result['original']['title']}'\n";
    echo "  Parsed Success: " . ($result['parsed']['success'] ? 'YES' : 'NO') . "\n";
    echo "  Matches Branch: " . ($result['matches_branch'] ? 'YES' : 'NO') . "\n";
    echo "  Suggested Action: {$result['suggested_action']}\n";
    if ($result['warning']) {
        echo "  Warning: {$result['warning']}\n";
    }
    echo "  Suggested Name: {$result['suggested_name']}\n";
}

// Test 5: Name generation formats
echo "\nTest 5: Name Generation Formats\n";
echo "--------------------------------\n";

$serviceName = 'Consultation Service';
echo "Service: '$serviceName'\n";
echo "Generated name: " . $parser->generateEventTypeName($branch, $serviceName) . "\n\n";

$formats = $smartParser->generateNameFormats($branch, $serviceName);
echo "Smart Parser Formats:\n";
foreach ($formats as $key => $format) {
    echo "  $key: $format\n";
}

// Test 6: Cal.com API Response Parsing
echo "\nTest 6: Cal.com API Response Structure\n";
echo "---------------------------------------\n";

// Simulate Cal.com v2 response structure
$calcomV2Response = [
    'data' => [
        'eventTypeGroups' => [
            [
                'groupName' => 'Team Events',
                'eventTypes' => [
                    [
                        'id' => 123,
                        'title' => 'Team Consultation',
                        'slug' => 'team-consultation',
                        'length' => 60,
                        'schedulingType' => 'COLLECTIVE',
                        'active' => true,
                        'users' => [
                            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@test.com'],
                            ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@test.com']
                        ],
                        'hosts' => [
                            ['userId' => 1, 'isFixed' => true, 'priority' => 1],
                            ['userId' => 2, 'isFixed' => false, 'priority' => 2]
                        ],
                        'team' => [
                            'id' => 10,
                            'name' => 'Support Team'
                        ]
                    ]
                ]
            ]
        ]
    ]
];

echo "Cal.com v2 Response Structure:\n";
echo "- Has eventTypeGroups: " . (isset($calcomV2Response['data']['eventTypeGroups']) ? 'YES' : 'NO') . "\n";

$eventTypes = [];
foreach ($calcomV2Response['data']['eventTypeGroups'] as $group) {
    if (isset($group['eventTypes'])) {
        $eventTypes = array_merge($eventTypes, $group['eventTypes']);
    }
}

echo "- Extracted event types: " . count($eventTypes) . "\n";

if (count($eventTypes) > 0) {
    $firstEvent = $eventTypes[0];
    echo "\nFirst Event Type Details:\n";
    echo "  ID: {$firstEvent['id']}\n";
    echo "  Title: {$firstEvent['title']}\n";
    echo "  Scheduling Type: {$firstEvent['schedulingType']}\n";
    echo "  Users assigned: " . count($firstEvent['users']) . "\n";
    
    if (isset($firstEvent['users']) && count($firstEvent['users']) > 0) {
        echo "\n  User Details:\n";
        foreach ($firstEvent['users'] as $user) {
            echo "    - {$user['name']} ({$user['email']}), ID: {$user['id']}\n";
        }
    }
    
    if (isset($firstEvent['hosts']) && count($firstEvent['hosts']) > 0) {
        echo "\n  Host Configuration:\n";
        foreach ($firstEvent['hosts'] as $host) {
            echo "    - User ID {$host['userId']}: ";
            echo "Fixed=" . ($host['isFixed'] ? 'YES' : 'NO');
            echo ", Priority={$host['priority']}\n";
        }
    }
}

// Test 7: Import Selection Logic
echo "\nTest 7: Import Selection Logic\n";
echo "------------------------------\n";

$selectionTestEvents = [
    ['title' => 'Berlin Mitte-Test Company-Consultation', 'active' => true],
    ['title' => 'Frankfurt-Other Company-Service', 'active' => true],
    ['title' => 'Test Demo Event', 'active' => true],
    ['title' => 'Example Test Service', 'active' => true],
    ['title' => 'Berlin Mitte-Test Company-Inactive Service', 'active' => false],
];

echo "Smart selection for branch 'Berlin Mitte':\n";
foreach ($selectionTestEvents as $i => $event) {
    $shouldSelect = false;
    
    // Parse to check branch match
    $parsed = $parser->parseEventTypeName($event['title']);
    if ($parsed['success'] && $parser->validateBranchMatch($parsed['branch_name'], $branch)) {
        $shouldSelect = true;
    }
    
    // Check for test/demo events
    $lowerTitle = strtolower($event['title']);
    if (strpos($lowerTitle, 'test') !== false || 
        strpos($lowerTitle, 'demo') !== false ||
        strpos($lowerTitle, 'example') !== false) {
        $shouldSelect = false;
    }
    
    // Check if inactive
    if (!$event['active']) {
        $shouldSelect = false;
    }
    
    echo "  Event " . ($i + 1) . ": '{$event['title']}' - ";
    echo $shouldSelect ? "SELECT" : "SKIP";
    echo " (Active: " . ($event['active'] ? 'YES' : 'NO') . ")\n";
}

echo "\n=== Analysis Complete ===\n";