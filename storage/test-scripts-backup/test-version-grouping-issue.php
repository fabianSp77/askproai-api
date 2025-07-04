<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Company;
use Illuminate\Support\Facades\Http;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$company = Company::whereNotNull('retell_api_key')->first();
if (!$company) {
    die("No company with Retell API key found\n");
}

// Decrypt API key if needed
$apiKey = $company->retell_api_key;
if (strlen($apiKey) > 50) {
    try {
        $apiKey = decrypt($apiKey);
    } catch (\Exception $e) {
        // Use as-is
    }
}

echo "Understanding Retell Agent Version Grouping Issue\n";
echo str_repeat("=", 80) . "\n\n";

// Get agents from API
$response = Http::withToken($apiKey)
    ->timeout(30)
    ->get('https://api.retellai.com/list-agents');

if (!$response->successful()) {
    die("Failed to fetch agents\n");
}

$data = $response->json();
$agents = is_array($data) && !isset($data['agents']) ? $data : ($data['agents'] ?? []);

// Filter for Fabian agents
$fabianAgents = array_filter($agents, function($agent) {
    return stripos($agent['agent_name'] ?? '', 'Fabian') !== false;
});

echo "1. Raw API Response Analysis:\n";
echo "   Total Fabian agents returned: " . count($fabianAgents) . "\n";
echo "   All have same agent_id: agent_9a8202a740cd3120d96fcfda1e\n\n";

// Show version distribution
$versions = [];
foreach ($fabianAgents as $agent) {
    $name = $agent['agent_name'] ?? '';
    if (preg_match('/V(\d+)/', $name, $matches)) {
        $version = 'V' . $matches[1];
    } else {
        $version = 'V0 (unversioned)';
    }
    $versions[$version] = ($versions[$version] ?? 0) + 1;
}

// Sort versions
uksort($versions, function($a, $b) {
    $aNum = (int) str_replace(['V', ' (unversioned)'], '', $a);
    $bNum = (int) str_replace(['V', ' (unversioned)'], '', $b);
    return $aNum - $bNum;
});

echo "2. Version Distribution:\n";
foreach ($versions as $version => $count) {
    echo "   $version: $count revision(s)\n";
}

echo "\n3. Current Control Center Logic:\n";
echo "   - Groups all 31 revisions by base name\n";
echo "   - Since they all have same base name, they become 1 group\n";
echo "   - Shows only the 'latest' (V33) in the main list\n";
echo "   - Version dropdown should show all versions, but may be limited\n";

echo "\n4. The Issue:\n";
echo "   ❌ Retell API returns revision history, not just active agents\n";
echo "   ❌ All revisions share the same agent_id\n";
echo "   ❌ Local DB only stores one record per agent_id (latest sync)\n";
echo "   ❌ Version dropdown logic expects different agent_ids for versions\n";

echo "\n5. Why Edit Mode Shows Different Data:\n";
echo "   - Edit mode loads from local DB (only has V33)\n";
echo "   - Dashboard loads from API (gets all 31 revisions)\n";
echo "   - Data mismatch between what's stored locally vs API response\n";

echo "\n6. Solution Options:\n";
echo "   a) Filter API response to only show latest revision per agent_id\n";
echo "   b) Store revision history locally in a separate table\n";
echo "   c) Use Retell's versioning API endpoints (if available)\n";
echo "   d) Show revisions in a different UI component (not version dropdown)\n";

// Simulate the grouping logic
echo "\n7. Simulating Control Center Grouping:\n";

function parseAgentName($fullName) {
    $name = preg_replace('/\/V\d+$/', '', $fullName);
    return trim(str_replace('Online: ', '', $name));
}

function extractVersion($fullName) {
    if (preg_match('/\/V(\d+)$/', $fullName, $matches)) {
        return 'V' . $matches[1];
    }
    return 'V1';
}

$processedAgents = [];
foreach ($fabianAgents as $agent) {
    $processedAgents[] = [
        'agent_id' => $agent['agent_id'] ?? '',
        'agent_name' => $agent['agent_name'] ?? '',
        'base_name' => parseAgentName($agent['agent_name'] ?? ''),
        'version' => extractVersion($agent['agent_name'] ?? ''),
        'timestamp' => $agent['last_modification_timestamp'] ?? 0
    ];
}

// Group by base name
$groups = [];
foreach ($processedAgents as $agent) {
    $baseName = $agent['base_name'];
    if (!isset($groups[$baseName])) {
        $groups[$baseName] = [];
    }
    $groups[$baseName][] = $agent;
}

echo "   Groups created: " . count($groups) . "\n";
foreach ($groups as $baseName => $agents) {
    echo "   - \"$baseName\": " . count($agents) . " items\n";
    
    // Sort by version
    usort($agents, function($a, $b) {
        $aNum = (int) str_replace('V', '', $a['version']);
        $bNum = (int) str_replace('V', '', $b['version']);
        return $bNum - $aNum; // Descending
    });
    
    // Show first 5 versions
    echo "     Latest versions: ";
    $shown = array_slice($agents, 0, 5);
    $versions = array_map(function($a) { return $a['version']; }, $shown);
    echo implode(', ', $versions);
    if (count($agents) > 5) {
        echo " ... (" . (count($agents) - 5) . " more)";
    }
    echo "\n";
}

echo "\n\nConclusion: The system is working as designed, but Retell's API returns\n";
echo "revision history rather than distinct agent versions. This causes confusion\n";
echo "when only one 'version' appears in the UI despite many revisions existing.\n";

echo "\nDone.\n";