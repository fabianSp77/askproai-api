#!/usr/bin/env php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Services\MCP\HairSalonMCPServer;
use App\Models\Company;
use App\Models\Service;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "MCP Debug Test\n";
echo "==============\n\n";

// Test 1: Check company
$company = Company::find(1);
echo "Company ID 1: " . ($company ? $company->name : "NOT FOUND") . "\n";

// Test 2: Check services directly
$services = Service::where('company_id', 1)->where('active', 1)->get();
echo "Active services for company 1: " . $services->count() . "\n";

// Test 3: Test MCP server directly
$mcpServer = new HairSalonMCPServer();
echo "\nTest WITHOUT setSalonCompany:\n";
$result1 = $mcpServer->getServices(['company_id' => 1]);
echo "  Success: " . ($result1['success'] ? 'YES' : 'NO') . "\n";
echo "  Services: " . (isset($result1['services']) ? count($result1['services']) : 0) . "\n";

// Test 4: Test with setSalonCompany
echo "\nTest WITH setSalonCompany:\n";
$mcpServer->setSalonCompany($company);
$result2 = $mcpServer->getServices(['company_id' => 1]);
echo "  Success: " . ($result2['success'] ? 'YES' : 'NO') . "\n";
echo "  Services: " . (isset($result2['services']) ? count($result2['services']) : 0) . "\n";

if ($result2['success'] && count($result2['services']) > 0) {
    echo "\nFirst 3 services:\n";
    foreach (array_slice($result2['services'], 0, 3) as $service) {
        echo "  - {$service['name']}: {$service['price']}€\n";
    }
}

// Test 5: Test controller simulation
echo "\nSimulating Controller:\n";
$controller = new \App\Http\Controllers\RetellMCPBridgeController($mcpServer);

// Create a mock request
$request = new \Illuminate\Http\Request();
$request->merge([
    'jsonrpc' => '2.0',
    'id' => 1,
    'method' => 'list_services',
    'params' => ['company_id' => 1]
]);

$response = $controller->handle($request);
echo "  Response status: " . $response->getStatusCode() . "\n";
$content = json_decode($response->getContent(), true);
if (isset($content['result']['services'])) {
    echo "  Services returned: " . count($content['result']['services']) . "\n";
} else {
    echo "  Response: " . json_encode($content) . "\n";
}