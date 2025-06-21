<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\Cache;
use App\Services\MCP\RetellMCPServer;

echo "DEBUGGING MCP CACHE\n";
echo str_repeat('=', 50) . "\n\n";

$cacheKey = 'mcp:retell:agents_with_phones:' . md5(json_encode(['company_id' => 1]));

echo "1. Checking cache key: $cacheKey\n";
$cachedData = Cache::get($cacheKey);

if ($cachedData) {
    echo "   ✅ Found cached data\n";
    if (isset($cachedData['is_mock']) && $cachedData['is_mock']) {
        echo "   ⚠️  Cached data is MOCK data!\n";
        echo "   Clearing cache...\n";
        Cache::forget($cacheKey);
        echo "   ✅ Cache cleared\n";
    } else {
        echo "   ✅ Cached data is real data\n";
        echo "   Agents: " . ($cachedData['total_agents'] ?? 0) . "\n";
    }
} else {
    echo "   No cached data found\n";
}

// Clear all retell cache
echo "\n2. Clearing all Retell MCP cache...\n";
$keys = [
    'mcp:retell:agents_with_phones:*',
    'mcp:retell:agent:*',
    'mcp:retell:phone_numbers:*'
];

foreach ($keys as $pattern) {
    // Laravel doesn't support wildcard cache deletion, so we'll flush retell keys
    echo "   Clearing pattern: $pattern\n";
}

// Just clear all cache to be sure
Cache::flush();
echo "   ✅ All cache cleared\n";

echo "\n3. Testing fresh API call...\n";
$mcpServer = new RetellMCPServer();
$result = $mcpServer->getAgentsWithPhoneNumbers(['company_id' => 1]);

if (isset($result['is_mock']) && $result['is_mock']) {
    echo "   ⚠️  Still returning mock data!\n";
    echo "   Error might be: " . ($result['error'] ?? 'Unknown') . "\n";
} else {
    echo "   ✅ Returning real data!\n";
    echo "   Agents: " . $result['total_agents'] . "\n";
}

echo "\n✅ CACHE DEBUG COMPLETE\n";