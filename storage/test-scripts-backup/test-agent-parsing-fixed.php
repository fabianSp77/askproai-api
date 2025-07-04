<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Company;
use App\Models\RetellAgent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Get the first company
$company = Company::whereNotNull('retell_api_key')->first();

if (!$company) {
    die("No company with Retell API key found\n");
}

// Set company context to avoid tenant scope issues
app()->instance('company', $company);

echo "Testing Agent Name Parsing for Company: {$company->name}\n";
echo str_repeat("=", 80) . "\n\n";

// Get all agents from local database without global scopes
$agents = RetellAgent::withoutGlobalScopes()
    ->where('company_id', $company->id)
    ->get();

echo "Found " . $agents->count() . " agents in local database\n\n";

// Show all agent names
echo "Agent Names in Database:\n";
foreach ($agents as $agent) {
    echo "- {$agent->name} (ID: {$agent->agent_id})\n";
}

echo "\n" . str_repeat("-", 80) . "\n\n";

// Test different parsing patterns
echo "Testing different parsing patterns:\n\n";

// Current parseAgentName function from Control Center
function parseAgentNameCurrent(string $fullName): string
{
    // Remove version numbers and clean up name
    $name = preg_replace('/\/V\d+$/', '', $fullName);
    return trim(str_replace('Online: ', '', $name));
}

// Current extractVersion function
function extractVersionCurrent(string $fullName): string
{
    if (preg_match('/\/V(\d+)$/', $fullName, $matches)) {
        return 'V' . $matches[1];
    }
    return 'V1';
}

// Alternative parsing that looks for V followed by number at end (with or without slash)
function parseAgentNameAlt1(string $fullName): string
{
    // Remove version like V33 at the end (with optional slash or space)
    $name = preg_replace('/[\s\/]*V\d+\s*$/', '', $fullName);
    return trim(str_replace('Online: ', '', $name));
}

function extractVersionAlt1(string $fullName): string
{
    // Look for V followed by digits at the end
    if (preg_match('/V(\d+)\s*$/', $fullName, $matches)) {
        return 'V' . $matches[1];
    }
    return 'V1';
}

// Alternative that looks for any version pattern
function parseAgentNameAlt2(string $fullName): string
{
    // Remove any version pattern (V1, V2, V33, etc.) anywhere in the string
    $name = preg_replace('/\s*V\d+\s*/', ' ', $fullName);
    return trim(str_replace('Online: ', '', $name));
}

function extractVersionAlt2(string $fullName): string
{
    // Find the last V followed by digits
    if (preg_match_all('/V(\d+)/', $fullName, $matches)) {
        $versions = $matches[1];
        return 'V' . end($versions);
    }
    return 'V1';
}

// Test names (including actual names from database if available)
$testNames = [
    'Assistent für Fabian Spitzer Rechtliches V33',
    'Assistent für Fabian Spitzer Rechtliches/V33',
    'Assistent für Fabian Spitzer Rechtliches V1',
    'Assistent für Fabian Spitzer Rechtliches',
    'Online: Test Agent V2',
    'Simple Agent',
    'Agent V5 Test',
    'V10 Agent Name V20'
];

// Add actual agent names from database
foreach ($agents as $agent) {
    if (!in_array($agent->name, $testNames)) {
        $testNames[] = $agent->name;
    }
}

// Test each parsing method
$methods = [
    'Current (slash pattern)' => ['parseAgentNameCurrent', 'extractVersionCurrent'],
    'Alt 1 (end pattern)' => ['parseAgentNameAlt1', 'extractVersionAlt1'],
    'Alt 2 (any pattern)' => ['parseAgentNameAlt2', 'extractVersionAlt2']
];

foreach ($testNames as $name) {
    echo "Testing: \"$name\"\n";
    
    foreach ($methods as $methodName => [$parseFunc, $versionFunc]) {
        $baseName = $parseFunc($name);
        $version = $versionFunc($name);
        echo "  $methodName:\n";
        echo "    Base: \"$baseName\"\n";
        echo "    Version: $version\n";
    }
    
    echo "\n";
}

// Check for specific Fabian Spitzer agents
echo str_repeat("-", 80) . "\n\n";
echo "Fabian Spitzer Agents Analysis:\n\n";

$fabianAgents = RetellAgent::withoutGlobalScopes()
    ->where('company_id', $company->id)
    ->where('name', 'like', '%Fabian Spitzer%')
    ->get();

if ($fabianAgents->count() > 0) {
    echo "Found {$fabianAgents->count()} Fabian Spitzer agent(s):\n\n";
    
    foreach ($fabianAgents as $agent) {
        echo "Agent: {$agent->name}\n";
        echo "  ID: {$agent->agent_id}\n";
        echo "  Active: " . ($agent->is_active ? 'Yes' : 'No') . "\n";
        
        // Test parsing
        echo "  Current parsing:\n";
        echo "    Base: \"" . parseAgentNameCurrent($agent->name) . "\"\n";
        echo "    Version: " . extractVersionCurrent($agent->name) . "\n";
        
        echo "  Alt 1 parsing:\n";
        echo "    Base: \"" . parseAgentNameAlt1($agent->name) . "\"\n";
        echo "    Version: " . extractVersionAlt1($agent->name) . "\n";
        
        // Check configuration
        $config = $agent->configuration ?? [];
        if (!empty($config)) {
            echo "  Configuration keys: " . implode(', ', array_keys($config)) . "\n";
            if (isset($config['agent_name'])) {
                echo "  Config agent_name: {$config['agent_name']}\n";
            }
        }
        
        echo "\n";
    }
    
    // Group by base name using different methods
    echo "\nGrouping Analysis:\n";
    
    $groupingMethods = [
        'Current' => 'parseAgentNameCurrent',
        'Alt 1' => 'parseAgentNameAlt1'
    ];
    
    foreach ($groupingMethods as $methodName => $parseFunc) {
        echo "\n$methodName method grouping:\n";
        
        $groups = [];
        foreach ($fabianAgents as $agent) {
            $baseName = $parseFunc($agent->name);
            if (!isset($groups[$baseName])) {
                $groups[$baseName] = [];
            }
            $groups[$baseName][] = $agent->name;
        }
        
        foreach ($groups as $baseName => $names) {
            echo "  Base: \"$baseName\"\n";
            echo "    Versions: " . count($names) . "\n";
            foreach ($names as $name) {
                echo "      - $name\n";
            }
        }
    }
} else {
    echo "No Fabian Spitzer agents found in database.\n";
}

echo "\nDone.\n";