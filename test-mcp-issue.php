#!/usr/bin/env php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Services\MCP\HairSalonMCPServer;
use App\Models\Company;
use App\Models\Service;
use App\Http\Controllers\RetellMCPBridgeController;
use Illuminate\Http\Request;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "MCP Issue Debug\n";
echo "===============\n\n";

// Check services directly
echo "1. Services in database for company_id=1:\n";
$services = Service::where('company_id', 1)->where('active', 1)->get();
echo "   Found: " . $services->count() . " services\n";
foreach ($services->take(3) as $service) {
    echo "   - {$service->name} ({$service->price}€)\n";
}

// Test MCP Server
echo "\n2. Test MCP Server directly:\n";
$company = Company::find(1);
$mcpServer = new HairSalonMCPServer();

// First without setting company
echo "   Without setSalonCompany:\n";
$result1 = $mcpServer->getServices(['company_id' => 1]);
echo "   - Success: " . ($result1['success'] ? 'YES' : 'NO') . "\n";
echo "   - Services: " . (isset($result1['services']) ? count($result1['services']) : 0) . "\n";

// Then with setting company
echo "\n   With setSalonCompany:\n";
$mcpServer->setSalonCompany($company);
$result2 = $mcpServer->getServices(['company_id' => 1]);
echo "   - Success: " . ($result2['success'] ? 'YES' : 'NO') . "\n";
echo "   - Services: " . (isset($result2['services']) ? count($result2['services']) : 0) . "\n";

// Test controller
echo "\n3. Test Controller with fresh instance:\n";
$freshMcpServer = new HairSalonMCPServer();
$controller = new RetellMCPBridgeController($freshMcpServer);

$request = new Request();
$request->merge([
    'jsonrpc' => '2.0',
    'id' => 'test',
    'method' => 'list_services',
    'params' => ['company_id' => 1]
]);

$response = $controller->handle($request);
$content = json_decode($response->getContent(), true);

echo "   - HTTP Status: " . $response->getStatusCode() . "\n";
if (isset($content['result']['services'])) {
    echo "   - Services returned: " . count($content['result']['services']) . "\n";
    if (count($content['result']['services']) > 0) {
        echo "   - First service: " . $content['result']['services'][0]['name'] . "\n";
    }
} else {
    echo "   - Response: " . json_encode($content) . "\n";
}

// Check if issue is in handleListServices
echo "\n4. Debug handleListServices method:\n";
$testParams = ['company_id' => 1];
$testCompany = Company::find($testParams['company_id']);
echo "   - Company found: " . ($testCompany ? $testCompany->name : 'NO') . "\n";

$testMcp = new HairSalonMCPServer();
if ($testCompany) {
    $testMcp->setSalonCompany($testCompany);
    echo "   - Company set on MCP server\n";
}

$testResult = $testMcp->getServices($testParams);
echo "   - Direct getServices result:\n";
echo "     Success: " . ($testResult['success'] ? 'YES' : 'NO') . "\n";
echo "     Services: " . (isset($testResult['services']) ? count($testResult['services']) : 0) . "\n";

echo "\nDONE\n";